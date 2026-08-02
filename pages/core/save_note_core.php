<?php
require_once '../../auth/config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id         = $_POST['school_id'] ?? null;
    $note_text         = trim($_POST['note_text'] ?? '');
    $next_meeting      = !empty($_POST['next_meeting']) ? $_POST['next_meeting'] : null;
    $notification_time =  $next_meeting; //!empty($_POST['notification_time']) ? $_POST['notification_time'] : null;

    if (!empty($school_id) && is_numeric($school_id) && !empty($note_text)) {
        try {
            // নতুন টেবিলে ডাটা ইনসার্ট ক্যোয়ারি
            $stmt = $pdo->prepare("INSERT INTO add_run_note (school_id, note_text, next_meeting, notification_time) VALUES (:school_id, :note_text, :next_meeting, :notification_time)");
            
            $stmt->bindParam(':school_id', $school_id, PDO::PARAM_INT);
            $stmt->bindParam(':note_text', $note_text);
            $stmt->bindParam(':next_meeting', $next_meeting);
            $stmt->bindParam(':notification_time', $notification_time);

            if ($stmt->execute()) {
                $_SESSION['success'] = "নোট ও নোটিফিকেশন তথ্য সফলভাবে সেভ করা হয়েছে।";
            } else {
                $_SESSION['error'] = "তথ্য সেভ করতে সমস্যা হয়েছে!";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "প্রয়োজনীয় ঘরগুলো সঠিকভাবে পূরণ করুন!";
    }
} else {
    $_SESSION['error'] = "অবৈধ অনুরোধ!";
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();