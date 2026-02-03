<?php
// core/trash_core.php
require_once '../../auth/config.php';
require_login();

$userId = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../trash.php");
    exit;
}

$action = $_POST['action'] ?? '';
$trashId = isset($_POST['trash_id']) ? (int) $_POST['trash_id'] : 0;

if ($trashId <= 0) {
    header("Location: ../trash.php");
    exit;
}

// ---------- RESTORE ----------
if ($action === 'restore_trash') {
    try {
        $pdo->beginTransaction();

        // 1) trash row আনো
        $stmt = $pdo->prepare("SELECT * FROM school_trash WHERE id = :id");
        $stmt->execute([':id' => $trashId]);
        $trash = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($trash) {
            $photoPath = $trash['photo_path'] ?? null;
            $newPhotoPath = $photoPath;
            $schoolId = (int) ($trash['school_id'] ?? 0);

            // 2) যদি trash_schools এ থাকে, তাহলে আবার uploads/schools এ move
            if (!empty($photoPath) && strpos($photoPath, 'uploads/trash_schools/') === 0) {
                $oldFull = dirname(__DIR__) . '/' . ltrim($photoPath, '/'); // ✅ project root + relative

                if (is_file($oldFull)) {
                    $mainDir = dirname(__DIR__) . '/uploads/schools';
                    if (!is_dir($mainDir)) {
                        mkdir($mainDir, 0777, true);
                    }

                    $baseName = basename($photoPath);
                    $targetFull = $mainDir . '/' . $baseName;

                    // নাম conflict হলে suffix যোগ
                    if (is_file($targetFull)) {
                        $nameNoExt = pathinfo($baseName, PATHINFO_FILENAME);
                        $ext = pathinfo($baseName, PATHINFO_EXTENSION);
                        $baseName = $nameNoExt . '_' . time() . '.' . $ext;
                        $targetFull = $mainDir . '/' . $baseName;
                    }

                    if (@rename($oldFull, $targetFull)) {
                        $newPhotoPath = 'uploads/schools/' . $baseName;
                    }
                }
            }

            // 3) schools এ আবার insert (পুরোনো school_id দিয়ে)
            $stmtIns = $pdo->prepare("
                INSERT INTO schools (
                    id,
                    district, upazila, school_name, mobile, status,
                    photo_path, created_by, updated_by
                )
                VALUES (
                    :id,
                    :district, :upazila, :school_name, :mobile, :status,
                    :photo_path, :created_by, :updated_by
                )
            ");

            $stmtIns->execute([
                ':id' => $schoolId,
                ':district' => $trash['district'],
                ':upazila' => $trash['upazila'],
                ':school_name' => $trash['school_name'],
                ':mobile' => $trash['mobile'],
                ':status' => $trash['status'],
                ':photo_path' => $newPhotoPath,
                ':created_by' => $trash['created_by'] ?? null,
                ':updated_by' => $userId,
            ]);

            // 4) note_trash -> school_notes restore
            $stmtRestoreNotes = $pdo->prepare("
                INSERT INTO school_notes (
                    school_id,
                    note_text,
                    note_date,
                    updated_by,
                    created_at
                )
                SELECT
                    school_id,
                    note_text,
                    note_date,
                    updated_by,
                    created_at
                FROM note_trash
                WHERE school_id = :school_id
            ");
            $stmtRestoreNotes->execute([':school_id' => $schoolId]);

            // 5) note_trash cleanup
            $stmtDelNotesTrash = $pdo->prepare("DELETE FROM note_trash WHERE school_id = :school_id");
            $stmtDelNotesTrash->execute([':school_id' => $schoolId]);

            // 6) school_trash row delete
            $stmtDelTrash = $pdo->prepare("DELETE FROM school_trash WHERE id = :id");
            $stmtDelTrash->execute([':id' => $trashId]);

            // 7) note_logs insert (Restore)

            $stmtLog = $pdo->prepare("
                INSERT INTO note_logs (note_id, school_id, user_id, action, old_text, new_text, action_at)
                VALUES (NULL, :school_id, :user_id, :action, :old_text, :new_text, NOW())
            ");

            $stmtLog->execute([
                ':school_id' => $schoolId,
                ':user_id' => $userId,
                ':action' => 'School Restore',
                ':old_text' => 'Trash -> Active',
                ':new_text' => 'Restored from trash',
            ]);

        }
        $pdo->commit();
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'School restored successfully!.'];

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Restore failed: ' . $e->getMessage()];
    }

    header("Location: ../trash.php");
    exit;
}

// ---------- PERMANENT DELETE ----------
if ($action === 'delete_trash') {
    try {
        $pdo->beginTransaction();

        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '')
            $reason = 'No reason provided';


        $stmt = $pdo->prepare("SELECT * FROM school_trash WHERE id = :id");
        $stmt->execute([':id' => $trashId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $schoolId = (int) ($row['school_id'] ?? 0);
            $photoPath = $row['photo_path'] ?? null;

            // ✅ note_trash থেকে এই school এর সব নোট আনো (old_text এ রাখার জন্য)
            $notesTrash = [];
            if ($schoolId > 0) {
                $stmtNt = $pdo->prepare("SELECT * FROM note_trash WHERE school_id = :school_id ORDER BY id DESC");
                $stmtNt->execute([':school_id' => $schoolId]);
                $notesTrash = $stmtNt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            // ✅ old_text এ “সব data” (school_trash row + note_trash rows)
            $oldPayload = [
                'school_trash' => $row,
                'note_trash' => $notesTrash,
            ];

            $oldTextAll = json_encode($oldPayload, JSON_UNESCAPED_UNICODE);
            if ($oldTextAll === false)
                $oldTextAll = 'JSON encode failed';


            // ছবিটা delete
            if (!empty($photoPath) && strpos($photoPath, 'uploads/trash_schools/') === 0) {
                $fileFull = dirname(__DIR__) . '/' . ltrim($photoPath, '/');
                if (is_file($fileFull)) {
                    @unlink($fileFull);
                }
            }

            // note_trash থেকে delete
            if ($schoolId > 0) {
                $stmtDelNotes = $pdo->prepare("DELETE FROM note_trash WHERE school_id = :school_id");
                $stmtDelNotes->execute([':school_id' => $schoolId]);
            }

            // school_trash delete
            $stmtDel = $pdo->prepare("DELETE FROM school_trash WHERE id = :id");
            $stmtDel->execute([':id' => $trashId]);

            // note_logs insert (Permanent Delete)
            $stmtLog = $pdo->prepare("
                INSERT INTO note_logs (note_id, school_id, user_id, action, old_text, new_text, action_at)
                VALUES (NULL, :school_id, :user_id, :action, :old_text, :new_text, NOW())
            ");

            $stmtLog->execute([
                ':school_id' => $schoolId,
                ':user_id' => $userId,
                ':action' => 'School Permanently Delete',
                ':old_text' => $oldTextAll,
                ':new_text' => 'Reason: ' . $reason,
            ]);
        }

        $pdo->commit();
        $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'School permanently deleted.'];
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Permanent delete failed: ' . $e->getMessage()];
    }

    header("Location: ../trash.php");
    exit;
}

// fallback
header("Location: ../trash.php");
exit;
