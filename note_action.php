<?php
require_once "config.php";
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action   = $_POST['action']   ?? '';
    $noteId   = isset($_POST['note_id'])   ? (int)$_POST['note_id']   : 0;
    $schoolId = isset($_POST['school_id']) ? (int)$_POST['school_id'] : 0;
    $userId   = $_SESSION['user_id'] ?? null;

    if ($noteId > 0 && in_array($action, ['update', 'delete'], true)) {

        // আগে note–এর পুরনো ডাটা নিয়ে আসি (school_id + old_text)
        $stmt = $pdo->prepare("
            SELECT school_id, note_text
            FROM school_notes
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $noteId]);
        $note = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$note) {
            // note না পেলে কিছু করার নেই, সোজা ফিরে যাই
            header("Location: index.php");
            exit;
        }

        $schoolIdFromDb = (int)$note['school_id'];
        $oldText        = $note['note_text'];

        if ($action === 'update') {

            $newText = trim($_POST['note_text'] ?? '');

            if ($newText !== '') {
                // 1) note আপডেট
                $stmtUp = $pdo->prepare("
                    UPDATE school_notes
                    SET note_text = :t,
                        updated_by = :u
                    WHERE id = :id
                ");
                $stmtUp->execute([
                    ':t'  => $newText,
                    ':u'  => $userId,
                    ':id' => $noteId,
                ]);

                // 2) log টেবিলে insert
                $stmtLog = $pdo->prepare("
                    INSERT INTO note_logs (note_id, school_id, user_id, action, old_text, new_text)
                    VALUES (:note_id, :school_id, :user_id, :action, :old_text, :new_text)
                ");
                $stmtLog->execute([
                    ':note_id'   => $noteId,
                    ':school_id' => $schoolIdFromDb,
                    ':user_id'   => $userId,
                    ':action'    => 'update',
                    ':old_text'  => $oldText,
                    ':new_text'  => $newText,
                ]);
            }

        } elseif ($action === 'delete') {

            // delete করার আগে log টেবিলে পুরনো টেক্সট রেখে দেই
            $stmtLog = $pdo->prepare("
                INSERT INTO note_logs (note_id, school_id, user_id, action, old_text, new_text)
                VALUES (:note_id, :school_id, :user_id, :action, :old_text, NULL)
            ");
            $stmtLog->execute([
                ':note_id'   => $noteId,
                ':school_id' => $schoolIdFromDb,
                ':user_id'   => $userId,
                ':action'    => 'delete',
                ':old_text'  => $oldText,
            ]);

            // তারপর আসল note ডিলিট করি
            $stmtDel = $pdo->prepare("DELETE FROM school_notes WHERE id = :id");
            $stmtDel->execute([':id' => $noteId]);
        }
    }
}

// কাজ শেষে মূল পেজে ফিরে যাই
header("Location: index.php");
exit;
