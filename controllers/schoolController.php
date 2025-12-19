<?php
// controllers/schoolController.php
require_once dirname(__DIR__) . '/auth/config.php';
require_login();

$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../schools/schools.php');
    exit;
}

if ($action !== 'delete_school') {
    header('Location: ../schools/schools.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    $_SESSION['school_errors'] = ['Invalid school id.'];
    header('Location: ../schools/schools.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // 1) school data
    $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $school = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($school) {

        // ✅ uploads path (project root)
        $rootDir = dirname(__DIR__); // school_list
        $photoPath      = $school['photo_path'] ?? null;
        $trashPhotoPath = null;

        // 2) move photo to uploads/trash_schools
        if (!empty($photoPath)) {
            $oldFull = $rootDir . '/' . ltrim($photoPath, '/'); // ✅ root + relative
            if (is_file($oldFull)) {

                $trashDir = $rootDir . '/uploads/trash_schools';
                if (!is_dir($trashDir)) {
                    mkdir($trashDir, 0777, true);
                }

                $fileName = basename($photoPath);
                $newFull  = $trashDir . '/' . $fileName;

                if (@rename($oldFull, $newFull)) {
                    $trashPhotoPath = 'uploads/trash_schools/' . $fileName;
                } else {
                    $trashPhotoPath = $photoPath;
                }
            } else {
                $trashPhotoPath = $photoPath;
            }
        }

        // 3) insert school_trash
        $stmtTrash = $pdo->prepare("
            INSERT INTO school_trash (
                school_id,
                district, upazila, school_name, mobile, status,
                photo_path,
                created_by, updated_by, deleted_by
            )
            VALUES (
                :school_id,
                :district, :upazila, :school_name, :mobile, :status,
                :photo_path,
                :created_by, :updated_by, :deleted_by
            )
        ");

        $stmtTrash->execute([
            ':school_id'   => $school['id'],
            ':district'    => $school['district'],
            ':upazila'     => $school['upazila'],
            ':school_name' => $school['school_name'],
            ':mobile'      => $school['mobile'],
            ':status'      => $school['status'],
            ':photo_path'  => $trashPhotoPath,
            ':created_by'  => $school['created_by'] ?? null,
            ':updated_by'  => $school['updated_by'] ?? null,
            ':deleted_by'  => $userId,
        ]);

        // 4) notes -> note_trash
        $stmtNotesTrash = $pdo->prepare("
            INSERT INTO note_trash (
                original_note_id,
                school_id,
                note_text,
                note_date,
                updated_by,
                created_at,
                deleted_by,
                deleted_at
            )
            SELECT
                n.id AS original_note_id,
                n.school_id,
                n.note_text,
                n.note_date,
                n.updated_by,
                n.created_at,
                :deleted_by AS deleted_by,
                NOW()       AS deleted_at
            FROM school_notes AS n
            WHERE n.school_id = :school_id
        ");
        $stmtNotesTrash->execute([
            ':deleted_by' => $userId,
            ':school_id'  => $school['id'],
        ]);
    }

    // 5) delete from school_notes
    $stmtDelNotes = $pdo->prepare("DELETE FROM school_notes WHERE school_id = :id");
    $stmtDelNotes->execute([':id' => $id]);

    // 6) delete from schools
    $stmtDel = $pdo->prepare("DELETE FROM schools WHERE id = :id");
    $stmtDel->execute([':id' => $id]);

    $pdo->commit();

    $_SESSION['school_success'] = "School deleted successfully (moved to trash).";

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['school_errors'] = ["Delete failed: " . $e->getMessage()];
}

header('Location: ../schools/schools.php');
exit;
