<?php
require_once '../../auth/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note_id = intval($_POST['note_id']);
    $school_id = intval($_POST['school_id']);

    try {
        $stmt = $pdo->prepare("DELETE FROM add_run_note WHERE id = :id");
        $deleted = $stmt->execute([':id' => $note_id]);

        if ($deleted) {
            $_SESSION['success'] = "নোট সফলভাবে মুছে ফেলা হয়েছে!";
        } else {
            $_SESSION['error'] = "নোট মুছে ফেলতে কোনো সমস্যা হয়েছে।";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "ত্রুটি: " . $e->getMessage();
    }

    header("Location: ../add_run_note.php?school_id=" . $school_id);
    exit();
}