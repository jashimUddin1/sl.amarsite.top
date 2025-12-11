<?php
require_once '../config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$noteId   = isset($_POST['note_id']) ? (int) $_POST['note_id'] : 0;
$schoolId = isset($_POST['school_id']) ? (int) $_POST['school_id'] : 0;
$noteText = trim($_POST['note_text'] ?? '');

if ($noteId <= 0 || $schoolId <= 0 || $noteText === '') {
    $_SESSION['note_error'] = 'নোট আপডেট করা যায়নি।';
    header('Location: ../note_view.php?school_id=' . $schoolId);
    exit;
}

try {
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

    $_SESSION['note_success'] = 'নোট সফলভাবে আপডেট হয়েছে।';
} catch (Exception $e) {
    $_SESSION['note_error'] = 'নোট আপডেট করতে সমস্যা হয়েছে।';
}

header('Location: ../note_view.php?school_id=' . $schoolId);
exit;
