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
$noteText = trim($_POST['note_text'] ?? '');

if ($noteId <= 0 || $schoolId <= 0 || $noteText === '') {
    $_SESSION['note_error'] = 'নোট আপডেট করা যায়নি। প্রয়োজনীয় তথ্য সঠিক নয়।';
    header('Location: ../note_view.php?school_id=' . $schoolId);
    exit;
}

try {
    // 🔹 1) আগের নোটটা আগে নেব, যাতে old_text লগ করা যায়
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
        // নোটই না পাওয়া গেলে, আপডেট করার কোনো মানে নেই
        $_SESSION['note_error'] = 'নোট পাওয়া যায়নি, আপডেট করা যায়নি।';
        header('Location: ../note_view.php?school_id=' . $schoolId);
        exit;
    }

    $oldText = $oldRow['note_text'] ?? '';

    // চাইলে এখানে চেক করতে পারো: পরিবর্তন না হলে কিছুই করার দরকার নেই
    if ($oldText === $noteText) {
        $_SESSION['note_success'] = 'কোনো পরিবর্তন করা হয়নি (নোট একই ছিল)।';
        header('Location: ../note_view.php?school_id=' . $schoolId);
        exit;
    }

    // 🔹 2) মূল নোট আপডেট
    $stmt = $pdo->prepare("
        UPDATE school_notes
        SET note_text = :note_text
        WHERE id = :id AND school_id = :school_id
    ");
    $stmt->execute([
        ':note_text' => $noteText,
        ':id'        => $noteId,
        ':school_id' => $schoolId,
    ]);

    // 🔹 3) note_logs এ লগ ইনসার্ট
    $logStmt = $pdo->prepare("
        INSERT INTO note_logs (note_id, school_id, user_id, action, old_text, new_text, action_at)
        VALUES (:note_id, :school_id, :user_id, :action, :old_text, :new_text, NOW())
    ");

    $logStmt->execute([
        ':note_id'   => $noteId,
        ':school_id' => $schoolId,
        ':user_id'   => $user_id,
        ':action'    => 'update note',  
        ':old_text'  => $oldText,
        ':new_text'  => $noteText,
    ]);

    $_SESSION['note_success'] = 'নোট সফলভাবে আপডেট হয়েছে এবং লগ সংরক্ষণ করা হয়েছে।';

} catch (Exception $e) {
    // চাইলে ডিবাগের জন্য চালু রাখতে পারো
    // echo "<pre>".htmlspecialchars($e->getMessage())."</pre>";
    $_SESSION['note_error'] = 'নোট আপডেট করতে সমস্যা হয়েছে।';
}

header('Location: ../note_view.php?school_id=' . $schoolId);
exit;

