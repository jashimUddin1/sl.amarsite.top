<?php
require_once '../auth/config.php';
require_login();
require_once '../helper_functions/image_helper.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header("Location: /school_list/schools/schools.php");
    exit;
}

// পুরনো school ডাটা
$stmt = $pdo->prepare("SELECT * FROM schools WHERE id = :id");
$stmt->execute([':id' => $id]);
$school = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$school) {
    header("Location: /school_list/schools/schools.php");
    exit;
}

$errors = [];

// initial values
$district = $school['district'] ?? '';
$upazila = $school['upazila'] ?? '';
$schoolName = $school['school_name'] ?? '';
$clientName = $school['client_name'] ?? '';   // ✅ client_name
$mobile = $school['mobile'] ?? '';
$m_fee = $school['m_fee'] ?? '';
$y_fee = $school['y_fee'] ?? '';
$status = $school['status'] ?? 'Pending';
$photoPath = $school['photo_path'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $district = trim($_POST['district'] ?? '');
    $upazila = trim($_POST['upazila'] ?? '');
    $schoolName = trim($_POST['school_name'] ?? '');
    $clientName = trim($_POST['client_name'] ?? '');  // ✅ client_name POST
    $mobile = trim($_POST['mobile'] ?? '');
    $m_fee = trim($_POST['m_fee'] ?? '');
    $y_fee = trim($_POST['y_fee'] ?? '');
    $status = trim($_POST['status'] ?? 'Pending');

    if ($district === '')
        $errors[] = "District অবশ্যই দিতে হবে।";
    if ($upazila === '')
        $errors[] = "Upazila অবশ্যই দিতে হবে।";
    if ($schoolName === '')
        $errors[] = "School name অবশ্যই দিতে হবে।";

    $newPhotoPath = $photoPath;

    if (!empty($_FILES['photo']['name'])) {
        [$compressedPath, $imgError] = compress_school_image($_FILES['photo'], 1200, 70);
        if ($imgError !== null) {
            $errors[] = $imgError;
        } else {
            // পুরনো ফাইল delete (optional)
            if (!empty($photoPath)) {
                $oldFile = __DIR__ . '/' . $photoPath;
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }
            $newPhotoPath = $compressedPath;
        }
    }

    if (empty($errors)) {
        $stmtUp = $pdo->prepare("
            UPDATE schools
            SET district    = :district,
                upazila     = :upazila,
                school_name = :school_name,
                client_name = :client_name,   
                mobile      = :mobile,
                m_fee       = :m_fee,
                y_fee       = :y_fee,
                status      = :status,
                photo_path  = :photo_path,
                updated_by  = :updated_by
            WHERE id = :id
        ");

        $stmtUp->execute([
            ':district' => $district,
            ':upazila' => $upazila,
            ':school_name' => $schoolName,
            ':client_name' => $clientName,  
            ':mobile' => $mobile,
            ':m_fee' => $m_fee,
            ':y_fee' => $y_fee,
            ':status' => $status,
            ':photo_path' => $newPhotoPath,
            ':updated_by' => $userId,
            ':id' => $id,
        ]);

        // 🔹 note_logs এ লগ ইনসার্ট
        $logStmt = $pdo->prepare("
            INSERT INTO note_logs (school_id, user_id, action, old_text, new_text, action_at)
            VALUES (:school_id, :user_id, :action, :old_text, :new_text, NOW())
        ");

        $logStmt->execute([
            ':school_id' => $id,
            ':user_id' => $userId,
            ':action' => 'update school',
            ':old_text' => null,
            ':new_text' => null,
        ]);

        $_SESSION['school_success'] = 'স্কুল সফলভাবে আপডেট হয়েছে |';
        header("Location: schools.php");
        exit;
    }
}

$pageTitle = 'Edit School - School List';
$pageHeading = 'Edit School';
$activeMenu = 'schools';

require '../layout/layout_header.php';
?>

<div class="max-w-xl mx-auto bg-white rounded-xl shadow p-4">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-bold text-slate-800">
            Edit: <?php echo htmlspecialchars($schoolName); ?>
        </h2>
        <a href="schools.php"
            class="text-xs sm:text-sm px-3 py-1.5 rounded border border-slate-300 text-slate-700 hover:bg-slate-100">
            ◀ Back to List
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="mb-3 rounded bg-red-50 border border-red-200 text-red-700 text-sm p-2">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-3">
        <div class="flex gap-3" style="max-width: 97%">
            <div class="col-6">
                <label class="block text-xs font-semibold mb-1 text-slate-700">
                    District<span class="text-red-500">*</span>
                </label>
                <input type="text" name="district" class="w-full p-2 border rounded text-sm"
                    value="<?php echo htmlspecialchars($district); ?>" required>
            </div>

            <div class="col-6">
                <label class="block text-xs font-semibold mb-1 text-slate-700">
                    Upazila<span class="text-red-500">*</span>
                </label>
                <input type="text" name="upazila" class="w-full p-2 border rounded text-sm"
                    value="<?php echo htmlspecialchars($upazila); ?>" required>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1 text-slate-700">
                School Name<span class="text-red-500">*</span>
            </label>
            <input type="text" name="school_name" class="w-full p-2 border rounded text-sm"
                value="<?php echo htmlspecialchars($schoolName); ?>" required>
        </div>

        <div class="flex gap-3" style="max-width: 97%">
            <div class="col-6">
                <label class="block text-xs font-semibold mb-1 text-slate-700">Client Name</label>
                <input type="text" name="client_name" class="w-full p-2 border rounded text-sm"
                    value="<?php echo htmlspecialchars($clientName); ?>">
            </div>

            <div class="col-6">
                <label class="block text-xs font-semibold mb-1 text-slate-700">Mobile</label>
                <input type="text" name="mobile" class="w-full p-2 border rounded text-sm"
                    value="<?php echo htmlspecialchars($mobile); ?>">
            </div>

        </div>

        <div class="flex gap-3" style="max-width: 97%">
            <div class="col-6"> 

                <label class="block text-xs font-semibold mb-1 text-slate-700">Monthly Fee</label>
                <input type="number" step="0.01" name="m_fee" value="<?= htmlspecialchars($m_fee) ?>" 
                    class="form-control">
            </div>

            <div class="col-6">
                <label class="block text-xs font-semibold mb-1 text-slate-700">Yearly Fee</label>
                <input type="number" step="0.01" name="y_fee" value="<?= htmlspecialchars($y_fee) ?>"
                    class="form-control">
            </div>

        </div>



        <div class="flex justify-between gap-2" style="max-width: 97%">
            <div class="col-8">
                <label class="block text-xs font-semibold mb-1 text-slate-700">Current Photo</label>
                <?php if (!empty($photoPath)): ?>
                    <img src="../<?php echo htmlspecialchars($photoPath); ?>" class="rounded border mb-2"
                        style="width: 16rem; height: auto;">
                <?php else: ?>
                    <p class="text-xs text-slate-500 mb-2">No photo uploaded.</p>
                <?php endif; ?>

                <label class="block text-xs font-semibold mb-1 text-slate-700">Change Photo (optional)</label>
                <input type="file" name="photo" accept="image/*" class="w-full text-sm">
                <p class="text-[11px] text-slate-500 mt-1">
                    নতুন ছবি দিলে পুরানো ছবি delete হয়ে যাবে।
                </p>
            </div>

            <div class="col-4" style="position: relative;">
                <div class="status" style="min-height: 155px;">
                    <label class="block text-xs font-semibold mb-1 text-slate-700">Status</label>
                    <select name="status" class="w-full p-2 border rounded text-sm">
                        <option value="Pending" <?php echo ($status === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo ($status === 'Approved') ? 'selected' : ''; ?>>Approved
                        </option>
                    </select>
                </div>


                <div class="pt-2 flex justify-end gap-2 row col-md-12"
                    style="position: absolute; bottom: 0; right: 0; max-width: 100%;">
                    <a href="schools.php"
                        class="px-4 py-2 rounded border border-slate-300 text-sm text-slate-700 hover:bg-slate-100 col-md-6">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-4 py-2 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700 col-md-6">
                        Update
                    </button>
                </div>
            </div>

        </div>

    </form>
</div>

<?php require '../layout/layout_footer.php'; ?>