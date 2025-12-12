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

// datetime-local থেকে আসবে: 2025-12-20T14:30
$nextMeetingRaw  = $_POST['next_meeting_date'] ?? null;
$nextMeetingDate = null;

if (!empty($nextMeetingRaw)) {
    // 2025-12-20T14:30 → 2025-12-20 14:30:00
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $nextMeetingRaw);
    if ($dt !== false) {
        $nextMeetingDate = $dt->format('Y-m-d H:i:s');
    }
}

if ($schoolId <= 0 || $noteText === '') {
    $_SESSION['note_error'] = 'নোট সেভ করা যায়নি। প্রয়োজনীয় তথ্য পাওয়া যায়নি।';
    header('Location: ../index.php');
    exit;
}

try {
    $user_id = $_SESSION['user_id'] ?? null;

    // 🔹 মূল নোট ইনসার্ট
    $stmt = $pdo->prepare("
        INSERT INTO school_notes (school_id, note_text, next_meet, updated_by, created_at)
        VALUES (:school_id, :note_text, :next_meet, :updated_by, NOW())
    ");

    $stmt->execute([
        ':school_id'  => $schoolId,
        ':note_text'  => $noteText,
        ':next_meet'  => $nextMeetingDate, // NULL হতে পারে, বা DATETIME string
        ':updated_by' => $user_id,
    ]);

    // ইনসার্ট হওয়া note এর আইডি
    $noteId = $pdo->lastInsertId();

    // 🔹 note_logs এ লগ ইনসার্ট
    $logStmt = $pdo->prepare("
        INSERT INTO note_logs (note_id, school_id, user_id, action, old_text, new_text, action_at)
        VALUES (:note_id, :school_id, :user_id, :action, :old_text, :new_text, NOW())
    ");

    $logStmt->execute([
        ':note_id'   => $noteId,
        ':school_id' => $schoolId,
        ':user_id'   => $user_id,
        ':action'    => 'add note',   
        ':old_text'  => null,       // নতুন note, তাই পুরনো টেক্সট নেই
        ':new_text'  => $noteText,
    ]);

    $_SESSION['note_success'] = 'নোট সফলভাবে যুক্ত করা হয়েছে এবং লগ সংরক্ষণ করা হয়েছে।';

    header('Location: ../index.php');
    exit;

} catch (Exception $e) {
    // ডিবাগ করলে সক্রিয় রাখো, লাইভে গেলে comment করে শুধু generic error রাখো
    // echo "<pre>".htmlspecialchars($e->getMessage())."</pre>";

    $_SESSION['note_error'] = 'নোট সেভ করতে সমস্যা হয়েছে।';
    header('Location: ../index.php');
    exit;
}
