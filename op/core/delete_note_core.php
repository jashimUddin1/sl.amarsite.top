<?php
require_once '../auth/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../notes/note_view.php');
    exit;
}

$user_id  = $_SESSION['user_id'] ?? null;
$noteId   = isset($_POST['note_id']) ? (int) $_POST['note_id'] : 0;
$schoolId = isset($_POST['school_id']) ? (int) $_POST['school_id'] : 0;

if ($noteId <= 0 || $schoolId <= 0) {
    $_SESSION['note_error'] = 'নোট ডিলিট করা যায়নি।';
    header('Location: ../notes/note_view.php?school_id=' . $schoolId);
    exit;
}

try {
    // fetch deleted note
    $stmtOld = $pdo->prepare("
        SELECT note_text
        FROM school_notes
        WHERE id = :id AND school_id = :school_id
        LIMIT 1
    ");
    $stmtOld->execute([
        ':id'        => $noteId,
        ':school_id' => $schoolId,
    ]);

    $oldRow = $stmtOld->fetch(PDO::FETCH_ASSOC);

    if (!$oldRow) {
        $_SESSION['note_error'] = 'নোট পাওয়া যায়নি।';
        header('Location: ../notes/note_view.php?school_id=' . $schoolId);
        exit;
    }

    $oldText = $oldRow['note_text'] ?? '';

    // note delete 
    $stmt = $pdo->prepare("
        DELETE FROM school_notes
        WHERE id = :id AND school_id = :school_id
    ");
    $stmt->execute([
        ':id'        => $noteId,
        ':school_id' => $schoolId,
    ]);

    // insert into note_logs 
    $logStmt = $pdo->prepare("
        INSERT INTO note_logs
            (note_id, school_id, user_id, action, old_text, new_text, action_at)
        VALUES
            (:note_id, :school_id, :user_id, :action, :old_text, :new_text, NOW())
    ");

    $logStmt->execute([
        ':note_id'   => $noteId,
        ':school_id' => $schoolId,
        ':user_id'   => $user_id,
        ':action'    => 'Delete Note',
        ':old_text'  => $oldText,    
        ':new_text'  => null,
    ]);

    $_SESSION['note_success'] = 'নোট ডিলিট করা হয়েছে এবং লগ সংরক্ষণ করা হয়েছে।';

} catch (Exception $e) {
    // if debug:
    // error_log($e->getMessage());
    $_SESSION['note_error'] = 'নোট ডিলিট করতে সমস্যা হয়েছে।';
}

header('Location: ../notes/note_view.php?school_id=' . $schoolId);
exit;
