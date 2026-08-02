<?php
require_once '../../auth/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['school_id'])) {

    $school_id       = intval($_POST['school_id']);
    $school_name     = trim($_POST['school_name'] ?? '');
    $phone_number    = trim($_POST['phone_number'] ?? '');
    $division        = trim($_POST['division'] ?? '');
    $district        = trim($_POST['district'] ?? '');
    $upazila         = trim($_POST['upazila'] ?? '');
    $address_details = trim($_POST['address_details'] ?? '');

    $monthly_fee     = !empty($_POST['monthly_fee']) ? intval($_POST['monthly_fee']) : 0;
    $yearly_fee      = !empty($_POST['yearly_fee']) ? intval($_POST['yearly_fee']) : 0;
    $website_fee     = !empty($_POST['$website_fee']) ? intval($_POST['$website_fee']) : 0;
    $students        = !empty($_POST['students']) ? intval($_POST['students']) : 0;
    $sColor = !empty($_POST['sColor']) ? htmlspecialchars(trim($_POST['sColor'])) : 'grey';

    // Validation
    if (empty($school_id) || empty($school_name) || empty($phone_number) || empty($division) || empty($district) || empty($upazila)) {
        $_SESSION['error'] = 'সবগুলো প্রয়োজনীয় (* চিহ্নিত) ফিল্ড সঠিকভাবে পূরণ করুন!';
        header('Location: ' . base_url('pages/add_run.php'));
        exit();
    }

    try {
        $sql = "UPDATE add_run SET 
                    school_name = :school_name, 
                    phone_number = :phone_number, 
                    division = :division, 
                    district = :district, 
                    upazila = :upazila, 
                    address_details = :address_details, 
                    monthly_fee = :monthly_fee, 
                    yearly_fee = :yearly_fee, 
                    website_fee = :website_fee,
                    students_number = :students,
                    s_color = :sColor
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':school_name'     => $school_name,
            ':phone_number'    => $phone_number,
            ':division'        => $division,
            ':district'        => $district,
            ':upazila'         => $upazila,
            ':address_details' => $address_details,
            ':monthly_fee'     => $monthly_fee,
            ':yearly_fee'      => $yearly_fee,
            ':website_fee'     => $website_fee,
            ':students'        => $students,
            ':sColor'          => $sColor,
            ':id'              => $school_id
        ]);

        $_SESSION['success'] = 'স্কুলের তথ্য সফলভাবে আপডেট করা হয়েছে!';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'তথ্য আপডেট করতে সমস্যা হয়েছে: ' . $e->getMessage();
    }

    header('Location: ' . base_url('pages/add_run.php'));
    exit();
} else {
    header('Location: ' . base_url('index.php'));
    exit();
}
