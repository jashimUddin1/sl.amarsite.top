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

// // form field default values
// $district = '';
// $upazila = '';
// $schoolName = '';
// $mobile = '';
// $status = 'Pending';

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $district = trim($_POST['district'] ?? '');
    $upazila = trim($_POST['upazila'] ?? '');
    $schoolName = trim($_POST['school_name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $status = trim($_POST['status'] ?? 'Pending');

    if ($district === '')
        $errors[] = "District অবশ্যই দিতে হবে।";
    if ($upazila === '')
        $errors[] = "Upazila অবশ্যই দিতে হবে।";
    if ($schoolName === '')
        $errors[] = "School name অবশ্যই দিতে হবে।";

    // photo upload (optional + compress)
    $photoPath = null;
    if (!empty($_FILES['photo']['name'])) {
        [$photoPath, $imgError] = compress_school_image($_FILES['photo'], 1200, 70);
        if ($imgError !== null) {
            $errors[] = $imgError;
        }
    }

    if (empty($errors)) {
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
            ':district' => $district,
            ':upazila' => $upazila,
            ':school_name' => $schoolName,
            ':mobile' => $mobile,
            ':status' => $status,
            ':photo_path' => $photoPath,
            ':created_by' => $userId,
            ':updated_by' => $userId,
        ]);


        header("Location: ../schools.php");
        exit;
    }
// }

$pageTitle = 'Add School - School List';
$pageHeading = 'Add School';
$activeMenu = 'schools';

