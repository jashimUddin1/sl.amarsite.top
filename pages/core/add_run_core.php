<?php
require_once '../../auth/config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['school_name'])) {

    $school_name     = trim($_POST['school_name'] ?? '');
    $phone_number    = trim($_POST['phone_number'] ?? '');
    $division        = trim($_POST['division'] ?? '');
    $district        = trim($_POST['district'] ?? '');
    $upazila         = trim($_POST['upazila'] ?? '');
    $address_details = trim($_POST['address_details'] ?? '');
    
    $monthly_fee     = !empty($_POST['monthly_fee']) ? intval($_POST['monthly_fee']) : 0;
    $yearly_fee      = !empty($_POST['yearly_fee']) ? intval($_POST['yearly_fee']) : 0;
    $website_fee      = !empty($_POST['website_fee']) ? intval($_POST['website_fee']) : 0;

    if (empty($school_name) || empty($phone_number) || empty($division) || empty($district) || empty($upazila)) {
        $_SESSION['error'] = 'সবগুলো প্রয়োজনীয় (* চিহ্নিত) ফিল্ড সঠিকভাবে পূরণ করুন!';
        header('Location: ' . base_url('pages/add_run.php'));
        exit();
    }

    // ৪. PDO Prepared Statement দিয়ে SQL ইনসার্ট (add_run টেবিলে)
    try {
        $sql = "INSERT INTO add_run (school_name, phone_number, division, district, upazila, address_details, monthly_fee, yearly_fee, website_fee) 
                VALUES (:school_name, :phone_number, :division, :district, :upazila, :address_details, :monthly_fee, :yearly_fee, :website_fee)";

        $stmt = $pdo->prepare($sql);

        // প্যারামিটার বাইন্ড করে কোয়েরি এক্সিকিউট
        $stmt->execute([
            ':school_name'     => $school_name,
            ':phone_number'    => $phone_number,
            ':division'        => $division,
            ':district'        => $district,
            ':upazila'         => $upazila,
            ':address_details' => $address_details,
            ':monthly_fee'     => $monthly_fee,
            ':yearly_fee'      => $yearly_fee,
            ':website_fee'     => $website_fee
        ]);

        $_SESSION['success'] = 'স্কুল সফলভাবে যোগ করা হয়েছে!';

    } catch (PDOException $e) {
        $_SESSION['error'] = 'ডাটাবেজে তথ্য সংরক্ষণ করতে সমস্যা হয়েছে: ' . $e->getMessage();
    }

    // ৫. রিডাইরেক্ট (Dynamic URL ব্যবহারের জন্য base_url() ফাংশন যুক্ত করা হয়েছে)
    header('Location: ' . base_url('pages/add_run.php'));
    exit();

} else {
    // সরাসরি ফাইলটি এক্সেস করলে হোমপেজে ফেরত পাঠানো
    header('Location: ' . base_url('index.php'));
    exit();
}