<?php
require_once 'config.php';
require_login();

// ================== RESTORE SCHOOL ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'restore_school') {

    $trashId = (int) ($_POST['trash_id'] ?? 0);

    if ($trashId > 0) {

        // 1) trash_schools থেকে ডাটা আনো
        $stmt = $pdo->prepare("SELECT * FROM trash_schools WHERE id = :id");
        $stmt->execute([':id' => $trashId]);
        $trash = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($trash) {
            $oldSchoolId = $trash['school_id'];

            // 2) আবার schools টেবিলে insert (নতুন id হবে)
            $stmtIns = $pdo->prepare("
                INSERT INTO schools (district, upazila, school_name, mobile, status, photo_path, updated_by)
                VALUES (:district, :upazila, :school_name, :mobile, :status, :photo_path, :updated_by)
            ");

            $stmtIns->execute([
                ':district'    => $trash['district'],
                ':upazila'     => $trash['upazila'],
                ':school_name' => $trash['school_name'],
                ':mobile'      => $trash['mobile'],
                ':status'      => $trash['status'],
                ':photo_path'  => $trash['photo_path'],
                ':updated_by'  => $trash['deleted_by'] ?? ($_SESSION['user_id'] ?? null),
            ]);

            $newSchoolId = (int) $pdo->lastInsertId();

            // 3) পুরনো school_id এর সব note নতুন school_id তে ম্যাপ করো
            //    মানে delete হওয়া স্কুলের note গুলো আবার এই নতুন restored school এ লেগে যাবে
            $stmtNotes = $pdo->prepare("
                UPDATE school_notes 
                SET school_id = :new_id 
                WHERE school_id = :old_id
            ");
            $stmtNotes->execute([
                ':new_id' => $newSchoolId,
                ':old_id' => $oldSchoolId,
            ]);

            // 4) এখন trash_schools থেকে এন্ট্রিটা delete করে দাও
            $stmtDel = $pdo->prepare("DELETE FROM trash_schools WHERE id = :id");
            $stmtDel->execute([':id' => $trashId]);
        }
    }

    header("Location: trash.php");
    exit;
}

// ================== FETCH TRASHED SCHOOLS ==================
$stmt = $pdo->query("SELECT * FROM trash_schools ORDER BY deleted_at DESC");
$trashedSchools = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trashed Schools</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200 min-h-screen">

<div class="max-w-6xl mx-auto p-6">

    <!-- Top bar -->
    <div class="flex justify-between items-center mb-6">
        <a href="index.php" class="text-indigo-600 font-bold hover:underline">
            ⬅️ Back to Schools
        </a>

        <h1 class="text-2xl font-bold text-center flex-1 text-red-600">
            🗑️ Trashed Schools
        </h1>

        <span class="text-gray-600 text-sm">
            Total: <?php echo count($trashedSchools); ?>
        </span>
    </div>

    <?php if (!$trashedSchools): ?>
        <p class="text-center text-gray-500 mt-10">
            কোনো ডিলিটেড স্কুল নেই।
        </p>
    <?php else: ?>
        <div class="grid md:grid-cols-2 gap-6">
            <?php foreach ($trashedSchools as $t): ?>
                <div class="bg-white p-4 rounded-xl shadow">

                    <?php if (!empty($t['photo_path'])): ?>
                        <img src="<?php echo htmlspecialchars($t['photo_path']); ?>"
                             class="w-full h-40 object-cover rounded mb-3">
                    <?php endif; ?>

                    <h2 class="text-xl font-bold mb-1">
                        <?php echo htmlspecialchars($t['school_name']); ?>
                    </h2>

                    <p class="text-gray-600">
                        <?php echo htmlspecialchars($t['district'] . ', ' . $t['upazila']); ?>
                    </p>

                    <?php if (!empty($t['mobile'])): ?>
                        <p class="text-gray-700 text-sm mt-1">
                            📞 <?php echo htmlspecialchars($t['mobile']); ?>
                        </p>
                    <?php endif; ?>

                    <p class="text-sm mt-2 font-semibold">
                        Status:
                        <span class="<?php echo $t['status'] === 'Approved' ? 'text-green-600' : 'text-orange-600'; ?>">
                            <?php echo htmlspecialchars($t['status']); ?>
                        </span>
                    </p>

                    <p class="text-xs text-gray-500 mt-1">
                        Deleted at: <?php echo htmlspecialchars($t['deleted_at']); ?>
                    </p>

                    <div class="mt-4 flex justify-between items-center">
                        <!-- Old School ID info (optional) -->
                        <span class="text-xs text-gray-400">
                            Old ID: <?php echo (int) $t['school_id']; ?>
                        </span>

                        <!-- Restore form -->
                        <form method="POST"
                              onsubmit="return confirm('এই স্কুলটি রিস্টোর করতে চান?');">
                            <input type="hidden" name="action" value="restore_school">
                            <input type="hidden" name="trash_id"
                                   value="<?php echo (int) $t['id']; ?>">
                            <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-sm">
                                🔁 Restore
                            </button>
                        </form>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
