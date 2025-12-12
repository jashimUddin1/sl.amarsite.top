<?php //core/create_core.php
require_once __DIR__ . '/../config.php';
require_login();
require_once __DIR__ . '/../image_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'create_school') {

    // আগের পেজ (referer) থাকলে সেটায় ফিরে যাবে
    if (!empty($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        // কেউ direct ঢুকলে (কোন referer নাই) → fallback page
        header('Location: ../schools.php');
    }
    exit;
}

$errors = [];

$district   = trim($_POST['district']     ?? '');
$upazila    = trim($_POST['upazila']      ?? '');
$schoolName = trim($_POST['school_name']  ?? '');
$mobile     = trim($_POST['mobile']       ?? '');
$status     = trim($_POST['status']       ?? 'Pending');

if ($district === '') {
    $errors[] = "District অবশ্যই দিতে হবে।";
}
if ($upazila === '') {
    $errors[] = "Upazila অবশ্যই দিতে হবে।";
}
if ($schoolName === '') {
    $errors[] = "School name অবশ্যই দিতে হবে।";
}

// photo upload (optional + compress)
$photoPath = null;
if (!empty($_FILES['photo']['name'])) {
    [$photoPath, $imgError] = compress_school_image($_FILES['photo'], 1200, 70);
    if ($imgError !== null) {
        $errors[] = $imgError;
    }
}

// যদি error থাকে → সেশন এ রেখে ফিরে যাও
if (!empty($errors)) {
    $_SESSION['school_errors'] = $errors;
    $_SESSION['school_old'] = [
        'district'    => $district,
        'upazila'     => $upazila,
        'school_name' => $schoolName,
        'mobile'      => $mobile,
        'status'      => $status,
    ];

    if (!empty($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        header('Location: ../schools.php');
    }
    exit;
}

$userId = $_SESSION['user_id'] ?? null;

try {
    // 🔹 schools টেবিলে insert
    $stmt = $pdo->prepare("
        INSERT INTO schools (
            district, upazila, school_name, mobile, status,
            photo_path, created_by, updated_by
        )
        VALUES (
            :district, :upazila, :school_name, :mobile, :status,
            :photo_path, :created_by, :updated_by
        )
    ");
    $stmt->execute([
        ':district'    => $district,
        ':upazila'     => $upazila,
        ':school_name' => $schoolName,
        ':mobile'      => $mobile,
        ':status'      => $status,
        ':photo_path'  => $photoPath,
        ':created_by'  => $userId,
        ':updated_by'  => $userId,
    ]);

    // নতুন school_id
    $schoolId = (int)$pdo->lastInsertId();

    // 🔹 history/log data JSON আকারে বানাই
    $newData = [
        'district'    => $district,
        'upazila'     => $upazila,
        'school_name' => $schoolName,
        'mobile'      => $mobile,
        'status'      => $status,
        'photo_path'  => $photoPath,
    ];
    $newDataJson = json_encode($newData, JSON_UNESCAPED_UNICODE);

    // 🔹 note_logs এ insert (school create log)
    // note_logs schema:
    // id, note_id, school_id, user_id, action enum('create','update','delete'),
    // old_text, new_text, action_at
    $logStmt = $pdo->prepare("
        INSERT INTO note_logs (note_id, school_id, user_id, action, old_text, new_text, action_at)
        VALUES (:note_id, :school_id, :user_id, :action, :old_text, :new_text, NOW())
    ");

    $logStmt->execute([
        ':note_id'   => null,          // স্কুল create, কোনো note না
        ':school_id' => $schoolId,
        ':user_id'   => $userId,
        ':action'    => 'create school',     
        ':old_text'  => null,          
        ':new_text'  => $newDataJson,  // নতুন ডাটা JSON আকারে
    ]);

    // ... INSERT সফল হলে
    $_SESSION['school_success'] = 'স্কুল সফলভাবে তৈরি হয়েছে এবং লগ সংরক্ষণ হয়েছে।';

    header("Location: ../schools.php");
    exit;

} catch (Exception $e) {
    // চাইলে debug করতে:
    // echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";

    $_SESSION['school_errors'] = ['ডাটাবেজে সমস্যা হয়েছে, পরে আবার চেষ্টা করুন।'];

    if (!empty($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        header('Location: ../schools.php');
    }
    exit;
}
