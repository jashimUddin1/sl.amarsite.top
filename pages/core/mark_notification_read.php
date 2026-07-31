<?php
require_once '../../auth/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_id'])) {
    $noteId = intval($_POST['note_id']);

    try {
        $stmt = $pdo->prepare("UPDATE add_run_note SET is_read = 1 WHERE id = :id");
        $stmt->execute([':id' => $noteId]);
        
        $_SESSION['success'] = "নোটিফিকেশনটি Read হিসেবে মার্ক করা হয়েছে।";
    } catch (PDOException $e) {
        $_SESSION['error'] = "কোনো একটি সমস্যা হয়েছে।";
    }
}

// আগের পেজে রিডাইরেক্ট করা
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();