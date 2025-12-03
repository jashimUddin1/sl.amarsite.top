<?php
require_once 'config.php';
require_login();

/**
 * বড় সাইজের ছবি compress করে ছোট JPEG বানানোর helper
 * (Facebook-এর মতো behavior; 5–10MB মোবাইল ফটোকে অনেক কমিয়ে ফেলে)
 *
 * @param string $field     ফর্মের input name (যেমন 'photo', 'ePhoto')
 * @param string $uploadDir আপলোড ডিরেক্টরি
 * @param int    $maxWidth  সর্বোচ্চ width (px)
 * @param int    $quality   JPEG quality (0–100)
 * @return string|null      সেভ হওয়া ফাইলের path অথবা null
 */
function processCompressedImageUpload($field, $uploadDir = 'uploads/', $maxWidth = 800, $quality = 60)
{
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmpName = $_FILES[$field]['tmp_name'];

    if (!is_uploaded_file($tmpName)) {
        return null;
    }

    $imgInfo = @getimagesize($tmpName);
    if ($imgInfo === false) {
        return null;
    }

    list($width, $height, $type) = $imgInfo;

    // শুধু JPEG/PNG সাপোর্ট রাখলাম, চাইলে WebP ইত্যাদি বাড়ানো যাবে
    if ($type === IMAGETYPE_JPEG) {
        $src = imagecreatefromjpeg($tmpName);
    } elseif ($type === IMAGETYPE_PNG) {
        $src = imagecreatefrompng($tmpName);
    } else {
        return null;
    }

    if (!$src) {
        return null;
    }

    // নতুন width/height নির্ধারণ
    if ($width > $maxWidth) {
        $newWidth  = $maxWidth;
        $newHeight = (int) round(($height * $newWidth) / $width);
    } else {
        $newWidth  = $width;
        $newHeight = $height;
    }

    $dst = imagecreatetruecolor($newWidth, $newHeight);

    // background সাদা করে দিচ্ছি (PNG transparency → white)
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white);

    imagecopyresampled(
        $dst,
        $src,
        0, 0, 0, 0,
        $newWidth,
        $newHeight,
        $width,
        $height
    );

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = 'school_' . time() . '_' . rand(1000, 9999) . '.jpg';
    $target   = rtrim($uploadDir, '/') . '/' . $fileName;

    imagejpeg($dst, $target, $quality);

    imagedestroy($src);
    imagedestroy($dst);

    return $target;
}

// ================== DELETE SCHOOL ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_school') {

    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        // পুরনো ডাটা নাও
        $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $school = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($school) {
            // trash_schools/log table এ ইনসার্ট
            $stmtT = $pdo->prepare("
                INSERT INTO trash_schools 
                    (school_id, district, upazila, school_name, mobile, status, photo_path, deleted_by) 
                VALUES 
                    (:school_id, :district, :upazila, :school_name, :mobile, :status, :photo_path, :deleted_by)
            ");
            $stmtT->execute([
                ':school_id'  => $school['id'],
                ':district'   => $school['district'],
                ':upazila'    => $school['upazila'],
                ':school_name'=> $school['school_name'],
                ':mobile'     => $school['mobile'],
                ':status'     => $school['status'],
                ':photo_path' => $school['photo_path'],
                ':deleted_by' => $_SESSION['user_id'] ?? null,
            ]);

            // main schools থেকে delete
            $stmtD = $pdo->prepare("DELETE FROM schools WHERE id = :id");
            $stmtD->execute([':id' => $id]);
        }
    }

    header("Location: index.php");
    exit;
}

// ================== CREATE SCHOOL ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_school') {

    $schoolName = trim($_POST['schoolName'] ?? '');
    $mobile     = trim($_POST['mobile'] ?? '');
    $district   = trim($_POST['district'] ?? '');
    $upazila    = trim($_POST['upazila'] ?? '');
    $status     = $_POST['status'] ?? 'Pending';
    $noteText   = trim($_POST['note'] ?? '');

    if ($schoolName !== '' && $district !== '' && $upazila !== '') {

        // photo upload with compression
        $photoPath = processCompressedImageUpload('photo', 'uploads/', 800, 60);

        $stmt = $pdo->prepare("
            INSERT INTO schools (district, upazila, school_name, mobile, status, photo_path, updated_by)
            VALUES (:district, :upazila, :school_name, :mobile, :status, :photo_path, :updated_by)
        ");
        $stmt->execute([
            ':district'    => $district,
            ':upazila'     => $upazila,
            ':school_name' => $schoolName,
            ':mobile'      => $mobile,
            ':status'      => $status,
            ':photo_path'  => $photoPath,
            ':updated_by'  => $_SESSION['user_id'] ?? null,
        ]);

        $schoolId = $pdo->lastInsertId();

        if ($noteText !== '') {
            $stmt = $pdo->prepare("
                INSERT INTO school_notes (school_id, note_text, updated_by)
                VALUES (:school_id, :note_text, :updated_by)
            ");
            $stmt->execute([
                ':school_id'  => $schoolId,
                ':note_text'  => $noteText,
                ':updated_by' => $_SESSION['user_id'] ?? null,
            ]);
        }
    }

    header("Location: index.php");
    exit;
}

// ================== ADD NOTE ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_note') {
    $schoolId = (int) ($_POST['school_id'] ?? 0);
    $noteText = trim($_POST['note_text'] ?? '');

    if ($schoolId > 0 && $noteText !== '') {
        $stmt = $pdo->prepare("
            INSERT INTO school_notes (school_id, note_text, updated_by)
            VALUES (:school_id, :note_text, :updated_by)
        ");
        $stmt->execute([
            ':school_id'  => $schoolId,
            ':note_text'  => $noteText,
            ':updated_by' => $_SESSION['user_id'] ?? null,
        ]);
    }

    header("Location: index.php");
    exit;
}

// ================== UPDATE SCHOOL ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_school') {

    $id         = (int) ($_POST['id'] ?? 0);
    $schoolName = trim($_POST['eSchoolName'] ?? '');
    $mobile     = trim($_POST['eMobile'] ?? '');
    $district   = trim($_POST['eDistrict'] ?? '');
    $upazila    = trim($_POST['eUpazila'] ?? '');
    $status     = $_POST['eStatus'] ?? 'Pending';

    if ($id > 0 && $schoolName !== '' && $district !== '' && $upazila !== '') {

        $photoSql = '';
        $params = [
            ':id'          => $id,
            ':district'    => $district,
            ':upazila'     => $upazila,
            ':school_name' => $schoolName,
            ':mobile'      => $mobile,
            ':status'      => $status,
        ];

        // compressed new photo (optional)
        if (!empty($_FILES['ePhoto']['name'])) {
            $newPath = processCompressedImageUpload('ePhoto', 'uploads/', 800, 60);
            if ($newPath) {
                $photoSql              = ", photo_path = :photo_path";
                $params[':photo_path'] = $newPath;
            }
        }

        $params[':updated_by'] = $_SESSION['user_id'] ?? null;

        $sql = "
            UPDATE schools
            SET district    = :district,
                upazila     = :upazila,
                school_name = :school_name,
                mobile      = :mobile,
                status      = :status,
                updated_by  = :updated_by
                $photoSql
            WHERE id = :id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    header("Location: index.php");
    exit;
}

// ================== FILTER VALUES FROM GET ==================
$filterDistrict = $_GET['district'] ?? '';
$filterUpazila  = $_GET['upazila'] ?? '';
$filterStatus   = $_GET['status'] ?? '';

// ================== FETCH SCHOOLS ==================
$sql    = "SELECT * FROM schools WHERE 1";
$params = [];

if ($filterDistrict !== '') {
    $sql .= " AND district = :f_district";
    $params[':f_district'] = $filterDistrict;
}
if ($filterUpazila !== '') {
    $sql .= " AND upazila = :f_upazila";
    $params[':f_upazila'] = $filterUpazila;
}
if ($filterStatus !== '') {
    $sql .= " AND status = :f_status";
    $params[':f_status'] = $filterStatus;
}
$sql .= " ORDER BY id DESC";

$stmt    = $pdo->prepare($sql);
$stmt->execute($params);
$schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== SCHOOL COUNTS ==================
$totalSchools = $pdo->query("SELECT COUNT(*) FROM schools")->fetchColumn();
$approved     = $pdo->query("SELECT COUNT(*) FROM schools WHERE status='Approved'")->fetchColumn();
$pending      = $pdo->query("SELECT COUNT(*) FROM schools WHERE status='Pending'")->fetchColumn();

// ================== DISTINCT DISTRICTS & UPAZILAS ==================
$districts = $pdo->query("SELECT DISTINCT district FROM schools ORDER BY district ASC")->fetchAll(PDO::FETCH_COLUMN);

if ($filterDistrict !== '') {
    $stmtU = $pdo->prepare("SELECT DISTINCT upazila FROM schools WHERE district = :d ORDER BY upazila ASC");
    $stmtU->execute([':d' => $filterDistrict]);
    $upazilas = $stmtU->fetchAll(PDO::FETCH_COLUMN);
} else {
    $upazilas = $pdo->query("SELECT DISTINCT upazila FROM schools ORDER BY upazila ASC")->fetchAll(PDO::FETCH_COLUMN);
}

// ================== FETCH NOTES BY SCHOOL ==================
$notesBySchool = [];
if (!empty($schools)) {
    $ids = array_column($schools, 'id');
    $in  = implode(',', array_map('intval', $ids));

    // ধরে নিচ্ছি school_notes টেবিলে note_date কলাম আছে (DATETIME/TIMESTAMP)
    $stmtN = $pdo->query("SELECT * FROM school_notes WHERE school_id IN ($in) ORDER BY note_date DESC");

    foreach ($stmtN as $row) {
        $notesBySchool[$row['school_id']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Note Manager - Final</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .modal-bg {
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-center {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-100 to-indigo-100 min-h-screen">

    <div class="max-w-6xl mx-auto p-6">

        <!-- Top Header: Logs | Heading | Trash -->
        <div class="flex justify-between items-center mb-4">

            <!-- Left: Logs Button -->
            <a href="logs.php"
               class="px-4 py-2 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition">
                Logs
            </a>

            <!-- Center Heading -->
            <h1 class="text-3xl font-bold text-indigo-600 text-center flex-1 md:text-2x1 sm:text-2x1">
                 <span class="hidden md:inline">📘  School Note Manager</span>
            </h1>

            <!-- Right: Trash Button -->
            <a href="trash.php"
               class="px-4 py-2 bg-red-600 text-white rounded-lg shadow hover:bg-red-700 transition">
                🗑️ Trash
            </a>

        </div>

        <!-- Stats Bar -->
        <div class="text-center mb-6 text-lg font-semibold text-gray-700">
            মোট স্কুল:
            <span class="text-indigo-600"><?php echo (int) $totalSchools; ?></span> |
            Approved:
            <span class="text-green-600"><?php echo (int) $approved; ?></span> |
            Pending:
            <span class="text-orange-600"><?php echo (int) $pending; ?></span>
        </div>

        <!-- Upload Button -->
        <div class="text-center mb-6">
            <button type="button" onclick="openUploadModal()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-bold shadow">
                Upload School
            </button>
        </div>

        <!-- Filter Section -->
        <form method="GET" class="bg-white shadow p-6 rounded-xl mb-8">
            <h2 class="text-xl font-bold mb-4">🔍 Filter School</h2>
            <div class="grid md:grid-cols-3 gap-4">
                <select name="district" id="filterDistrict" onchange="this.form.submit()" class="p-2 border rounded">
                    <option value="">District</option>
                    <?php foreach ($districts as $d): ?>
                        <option value="<?php echo htmlspecialchars($d); ?>"
                            <?php if ($d === $filterDistrict) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($d); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="upazila" id="filterUpazila" onchange="this.form.submit()" class="p-2 border rounded">
                    <option value="">Upazila</option>
                    <?php foreach ($upazilas as $u): ?>
                        <option value="<?php echo htmlspecialchars($u); ?>"
                            <?php if ($u === $filterUpazila) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($u); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="status" id="filterStatus" onchange="this.form.submit()" class="p-2 border rounded">
                    <option value="">All Status</option>
                    <option value="Pending"  <?php if ($filterStatus === 'Pending')  echo 'selected'; ?>>Pending</option>
                    <option value="Approved" <?php if ($filterStatus === 'Approved') echo 'selected'; ?>>Approved</option>
                </select>
            </div>
            <button type="submit"
                class="mt-4 w-full bg-gray-800 text-white p-3 rounded-xl font-bold hover:bg-gray-900">
                Apply Filter
            </button>
        </form>

        <!-- School List -->
        <div id="schoolList" class="grid md:grid-cols-2 gap-6">
            <?php if (!$schools): ?>
                <p class="text-center text-gray-500 col-span-2">কোনো স্কুল পাওয়া যায়নি।</p>
            <?php else: ?>
                <?php foreach ($schools as $s): ?>
                    <?php
                    $sid       = $s['id'];
                    $notes     = $notesBySchool[$sid] ?? [];
                    $noteCount = count($notes);
                    $latestNote = $noteCount ? $notes[0] : null;
                    ?>
                    <div class="bg-white p-4 shadow rounded-xl relative">
                        <?php if (!empty($s['photo_path'])): ?>
                            <img src="<?php echo htmlspecialchars($s['photo_path']); ?>"
                                class="w-full h-48 object-cover rounded mb-3" alt="School Photo">
                        <?php endif; ?>

                        <h3 class="text-xl font-bold"><?php echo htmlspecialchars($s['school_name']); ?></h3>
                        <p class="text-gray-600"><?php echo htmlspecialchars($s['mobile']); ?></p>
                        <p class="text-sm text-indigo-600 font-bold">
                            <?php echo htmlspecialchars($s['district'] . ', ' . $s['upazila']); ?>
                        </p>
                        <p class="mt-2 font-semibold">
                            Status:
                            <span class="<?php echo $s['status'] === 'Approved' ? 'text-green-600' : 'text-orange-600'; ?>">
                                <?php echo htmlspecialchars($s['status']); ?>
                            </span>
                        </p>

                        <!-- Notes title + View all button -->
                        <div class="mt-3 flex items-center justify-between">
                            <span class="font-bold">Notes:</span>
                            <?php if ($noteCount > 1): ?>
                                <button type="button"
                                    class="text-sm text-blue-600 hover:underline"
                                    onclick="openNotesModal(this)"
                                    data-id="<?php echo $sid; ?>"
                                    data-name="<?php echo htmlspecialchars($s['school_name'], ENT_QUOTES); ?>">
                                    View all (<?php echo $noteCount; ?>)
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Latest note -->
                        <?php if ($latestNote): ?>
                            <div class="bg-gray-100 p-2 rounded mt-2">
                                <p><?php echo nl2br(htmlspecialchars($latestNote['note_text'])); ?></p>
                                <small class="text-gray-500">
                                    <?php echo htmlspecialchars($latestNote['note_date']); ?>
                                </small>
                            </div>
                        <?php else: ?>
                            <p class="text-sm text-gray-400 mt-1">No notes yet.</p>
                        <?php endif; ?>

                        <div class="flex gap-2 mt-3">
                            <button type="button"
                                onclick="openEditModal(this)"
                                class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700"
                                data-id="<?php echo $sid; ?>"
                                data-name="<?php echo htmlspecialchars($s['school_name'], ENT_QUOTES); ?>"
                                data-mobile="<?php echo htmlspecialchars($s['mobile'], ENT_QUOTES); ?>"
                                data-district="<?php echo htmlspecialchars($s['district'], ENT_QUOTES); ?>"
                                data-upazila="<?php echo htmlspecialchars($s['upazila'], ENT_QUOTES); ?>"
                                data-status="<?php echo htmlspecialchars($s['status'], ENT_QUOTES); ?>">
                                ✏️ Edit
                            </button>
                            <button type="button"
                                onclick="openAddNoteModal(<?php echo $sid; ?>)"
                                class="flex-1 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                                📝 Add Note
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- Upload Modal -->
    <div id="uploadModal" class="modal-bg modal-center fixed inset-0 hidden z-50">
        <div class="bg-white p-6 rounded-xl w-full max-w-md relative shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-800">📤 Upload School</h2>
            <form method="POST" enctype="multipart/form-data" class="grid gap-4">
                <input type="hidden" name="action" value="create_school">

                <input name="district" type="text" placeholder="District" class="p-2 border rounded" required>
                <input name="upazila" type="text" placeholder="Upazila" class="p-2 border rounded" required>
                <input name="schoolName" type="text" placeholder="School Name" class="p-2 border rounded" required>
                <input name="mobile" type="text" placeholder="Mobile Number" class="p-2 border rounded">
                <input name="photo" type="file" accept="image/*" class="p-2 border rounded" required>

                <select name="status" class="p-2 border rounded">
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                </select>

                <textarea name="note" placeholder="First Note লিখুন..." class="p-2 border rounded"></textarea>

                <div class="flex justify-end gap-2 mt-2">
                    <button type="button" onclick="closeUploadModal()"
                        class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal-bg modal-center fixed inset-0 hidden z-50">
        <div class="bg-white p-6 rounded-xl w-full max-w-md relative shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-800">✏️ Edit School</h2>
            <form method="POST" enctype="multipart/form-data" class="grid gap-4">
                <input type="hidden" name="action" value="update_school">
                <input type="hidden" name="id" id="eId">

                <input id="eSchoolName" name="eSchoolName" type="text" placeholder="School Name"
                    class="p-2 border rounded" required>
                <input id="eMobile" name="eMobile" type="text" placeholder="Mobile Number"
                    class="p-2 border rounded">
                <input id="eDistrict" name="eDistrict" type="text" placeholder="District"
                    class="p-2 border rounded" required>
                <input id="eUpazila" name="eUpazila" type="text" placeholder="Upazila"
                    class="p-2 border rounded" required>

                <select id="eStatus" name="eStatus" class="p-2 border rounded">
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                </select>

                <input id="ePhoto" name="ePhoto" type="file" accept="image/*" class="p-2 border rounded">

                <div class="flex justify-between items-center mt-2">

                    <!-- Delete Button -->
                    <button type="button"
                        onclick="deleteSchool()"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                        Delete
                    </button>

                    <div class="flex gap-2">
                        <button type="button" onclick="closeEditModal()"
                            class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>

                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Note Modal -->
    <div id="addNoteModal" class="modal-bg modal-center fixed inset-0 hidden z-50">
        <div class="bg-white p-6 rounded-xl w-full max-w-md relative shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-800">📝 Add Note</h2>
            <form method="POST" class="grid gap-4">
                <input type="hidden" name="action" value="add_note">
                <input type="hidden" name="school_id" id="note_school_id">
                <textarea name="note_text" id="addNoteText" placeholder="Write note here..."
                    class="p-2 border rounded w-full" required></textarea>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" onclick="closeAddNoteModal()"
                        class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Add Note</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Notes Modal -->
    <div id="notesModal" class="modal-bg modal-center fixed inset-0 hidden z-50">
        <div class="bg-white p-6 rounded-xl w-full max-w-[95vh] relative shadow-xl max-h-[95vh] overflow-y-auto">
            <h2 id="notesModalTitle" class="text-2xl font-bold mb-4 text-gray-800">
                📓 All Notes
            </h2>
            <div id="notesModalContent" class="space-y-3"></div>

            <div class="flex justify-end mt-4">
                <button onclick="closeNotesModal()"
                    class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        // =============== Upload Modal ===============
        function openUploadModal() {
            document.getElementById("uploadModal").classList.remove("hidden");
        }

        function closeUploadModal() {
            document.getElementById("uploadModal").classList.add("hidden");
        }

        // =============== Edit Modal ===============
        function openEditModal(btn) {
            document.getElementById("eId").value         = btn.dataset.id;
            document.getElementById("eSchoolName").value = btn.dataset.name;
            document.getElementById("eMobile").value     = btn.dataset.mobile;
            document.getElementById("eDistrict").value   = btn.dataset.district;
            document.getElementById("eUpazila").value    = btn.dataset.upazila;
            document.getElementById("eStatus").value     = btn.dataset.status;

            document.getElementById("ePhoto").value = "";

            document.getElementById("editModal").classList.remove("hidden");
        }

        function closeEditModal() {
            document.getElementById("editModal").classList.add("hidden");
        }

        function deleteSchool() {
            const id = document.getElementById("eId").value;

            if (!id) {
                alert("ID missing! Cannot delete.");
                return;
            }

            if (confirm("Are you sure you want to delete this school?")) {
                const form = document.createElement("form");
                form.method = "POST";
                form.action = "";

                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_school">
                    <input type="hidden" name="id" value="${id}">
                `;

                document.body.appendChild(form);
                form.submit();
            }
        }

        // =============== Add Note Modal ===============
        function openAddNoteModal(id) {
            document.getElementById("note_school_id").value = id;
            document.getElementById("addNoteText").value    = "";
            document.getElementById("addNoteModal").classList.remove("hidden");
        }

        function closeAddNoteModal() {
            document.getElementById("addNoteModal").classList.add("hidden");
        }

        // =============== View All Notes Modal ===============
        function openNotesModal(btn) {
            const id   = btn.dataset.id;
            const name = btn.dataset.name;

            const titleEl = document.getElementById("notesModalTitle");
            if (titleEl) {
                titleEl.textContent = "📓 All Notes - " + name;
            }

            const modal   = document.getElementById("notesModal");
            const content = document.getElementById("notesModalContent");
            content.innerHTML = "<p class='text-gray-500'>Loading...</p>";

            fetch("notes.php?id=" + encodeURIComponent(id))
                .then(function (res) {
                    return res.text();
                })
                .then(function (html) {
                    content.innerHTML = html;
                })
                .catch(function () {
                    content.innerHTML = "<p class='text-red-600'>Could not load notes.</p>";
                });

            modal.classList.remove("hidden");
        }

        function closeNotesModal() {
            document.getElementById("notesModal").classList.add("hidden");
        }
    </script>

</body>
</html>
