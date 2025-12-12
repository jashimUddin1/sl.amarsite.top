<?php
require_once '../config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$user_id  = $_SESSION['user_id'] ?? null;
$noteId   = isset($_POST['note_id']) ? (int) $_POST['note_id'] : 0;
$schoolId = isset($_POST['school_id']) ? (int) $_POST['school_id'] : 0;

if ($noteId <= 0 || $schoolId <= 0) {
    $_SESSION['note_error'] = 'নোট ডিলিট করা যায়নি।';
    header('Location: ../note_view.php?school_id=' . $schoolId);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM school_notes WHERE id = :id AND school_id = :school_id");
    $stmt->execute([
        ':id'        => $noteId,
        ':school_id' => $schoolId,
    ]);

    // 🔹 note_logs এ লগ ইনসার্ট
    $logStmt = $pdo->prepare("
        INSERT INTO note_logs (note_id, school_id, user_id, action, old_text, new_text, action_at)
        VALUES (:note_id, :school_id, :user_id, :action, :old_text, :new_text, NOW())
    ");

    $logStmt->execute([
        ':note_id'   => $noteId,
        ':school_id' => $schoolId,
        ':user_id'   => $user_id,
        ':action'    => 'delete note', 
        ':old_text'  => null,
        ':new_text'  => null,
    ]);

    $_SESSION['note_success'] = 'নোট ডিলিট করা হয়েছে।';
} catch (Exception $e) {
    $_SESSION['note_error'] = 'নোট ডিলিট করতে সমস্যা হয়েছে।';
}

header('Location: ../note_view.php?school_id=' . $schoolId);
exit;
