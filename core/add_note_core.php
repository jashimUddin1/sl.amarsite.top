<?php
// core/add_note_core.php
require_once '../config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$schoolId = isset($_POST['school_id']) ? (int) $_POST['school_id'] : 0;
$noteText = trim($_POST['note_text'] ?? '');

if ($schoolId <= 0 || $noteText === '') {
    $_SESSION['note_error'] = 'নোট সেভ করা যায়নি। প্রয়োজনীয় তথ্য পাওয়া যায়নি।';
    header('Location: ../index.php');
    exit;
}

// ধরে নিচ্ছি নোট রাখার টেবিলের নাম: school_notes
// কলাম: id, school_id, note_text, created_by, created_at
try {
    $user_id = $_SESSION['user_id'] ?? null; // যদি session এ admin_id থাকে

    $stmt = $pdo->prepare("
        INSERT INTO school_notes (school_id, note_text, updated_by, created_at)
        VALUES (:school_id, :note_text, :updated_by, NOW())
    ");

    $stmt->execute([
        ':school_id'  => $schoolId,
        ':note_text'  => $noteText,
        ':updated_by' => $user_id,
    ]);

    $_SESSION['note_success'] = 'নোট সফলভাবে যুক্ত করা হয়েছে।';
} catch (Exception $e) {
    $_SESSION['note_error'] = 'নোট সেভ করতে সমস্যা হয়েছে।';
}

header('Location: ../index.php');
exit;
