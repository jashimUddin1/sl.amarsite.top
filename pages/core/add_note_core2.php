<?php
require_once '../../auth/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id = isset($_POST['school_id']) ? intval($_POST['school_id']) : 0;
    $note_text = isset($_POST['note_text']) ? trim($_POST['note_text']) : '';
    $notification_time = !empty($_POST['notification_time']) ? $_POST['notification_time'] : NULL;
    $next_meeting = !empty($_POST['next_meeting']) ? $_POST['next_meeting'] : NULL;

    // ভ্যালিডেশন
    if ($school_id <= 0 || empty($note_text)) {
        $_SESSION['error'] = "অনুগ্রহ করে প্রয়োজনীয় তথ্য সঠিকভাবে দিন।";
        header("Location: ../add_run_note.php?school_id=" . $school_id);
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO add_run_note (school_id, note_text, notification_time, next_meeting, created_at) VALUES (:school_id, :note_text, :notification_time, :next_meeting, NOW())");
        
        $inserted = $stmt->execute([
            ':school_id' => $school_id,
            ':note_text' => $note_text,
            ':notification_time' => $notification_time,
            ':next_meeting' => $next_meeting
        ]);

        if ($inserted) {
            $_SESSION['success'] = "নতুন নোট সফলভাবে যুক্ত করা হয়েছে!";
        } else {
            $_SESSION['error'] = "নোট যুক্ত করতে সমস্যা হয়েছে।";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "ত্রুটি: " . $e->getMessage();
    }

    header("Location: ../add_run_note.php?school_id=" . $school_id);
    exit();
} else {
    header("Location: ../add_run.php");
    exit();
}