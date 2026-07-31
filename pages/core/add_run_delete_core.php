<?php
require_once '../../auth/config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $school_id = isset($_POST['school_id']) ? trim($_POST['school_id']) : null;

    if (!empty($school_id) && is_numeric($school_id)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM add_run WHERE id = :id");
            $stmt->bindParam(':id', $school_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "স্কুলের তথ্যসহ সফলভাবে স্কুলটি ডিলিট করা হয়েছে।";
            } else {
                $_SESSION['error'] = "তথ্য ডিলিট করতে সমস্যা হয়েছে! আবার চেষ্টা করুন।";
            }

        } catch (PDOException $e) {
            // ডাটাবেজ সংক্রান্ত কোনো এরর আসলে
            $_SESSION['error'] = "Database Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "অবৈধ বা ভুল তথ্য পাঠানো হয়েছে!";
    }

} else {
    // সরাসরি ফাইলটি এক্সেস করতে চাইলে রিডাইরেক্ট করা
    $_SESSION['error'] = "সরাসরি এক্সেস অনুমোদিত নয়!";
}

// আগের পেজে রিডাইরেক্ট করে দেওয়া
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();