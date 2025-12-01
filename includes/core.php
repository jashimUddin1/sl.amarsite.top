<?php
require_once 'config.php';
require_login();

// ================== CREATE SCHOOL ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_school') {

    $schoolName = trim($_POST['schoolName'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $upazila = trim($_POST['upazila'] ?? '');
    $status = $_POST['status'] ?? 'Pending';
    $noteText = trim($_POST['note'] ?? '');

    if ($schoolName !== '' && $district !== '' && $upazila !== '') {

        // photo upload
        $photoPath = null;
        if (!empty($_FILES['photo']['name'])) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $fileName = 'school_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $target = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                $photoPath = $target;
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO schools (district, upazila, school_name, mobile, status, photo_path, updated_by)
            VALUES (:district, :upazila, :school_name, :mobile, :status, :photo_path, :updated_by)
        ");
        $stmt->execute([
            ':district' => $district,
            ':upazila' => $upazila,
            ':school_name' => $schoolName,
            ':mobile' => $mobile,
            ':status' => $status,
            ':photo_path' => $photoPath,
            ':updated_by' => $_SESSION['user_id'] ?? null,
        ]);


        $schoolId = $pdo->lastInsertId();

        if ($noteText !== '') {
            $stmt = $pdo->prepare("
                INSERT INTO school_notes (school_id, note_text, updated_by)
                VALUES (:school_id, :note_text, :updated_by)
            ");
            $stmt->execute([
                ':school_id' => $schoolId,
                ':note_text' => $noteText,
                ':updated_by' => $_SESSION['user_id'] ?? null,
            ]);
        }

    }

    header("Location: index.php");
    exit;
}

// ================== ADD NOTE ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_note') {
    $schoolId = (int) ($_POST['school_id'] ?? 0);
    $noteText = trim($_POST['note_text'] ?? '');

    if ($schoolId > 0 && $noteText !== '') {
        $stmt = $pdo->prepare("
            INSERT INTO school_notes (school_id, note_text, updated_by)
            VALUES (:school_id, :note_text, :updated_by)
        ");
        $stmt->execute([
            ':school_id'  => $schoolId,
            ':note_text'  => $noteText,
            ':updated_by' => $_SESSION['user_id'] ?? null,
        ]);
    }



    header("Location: index.php");
    exit;
}

// ================== UPDATE SCHOOL ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_school') {

    $id = (int) ($_POST['id'] ?? 0);
    $schoolName = trim($_POST['eSchoolName'] ?? '');
    $mobile = trim($_POST['eMobile'] ?? '');
    $district = trim($_POST['eDistrict'] ?? '');
    $upazila = trim($_POST['eUpazila'] ?? '');
    $status = $_POST['eStatus'] ?? 'Pending';

    if ($id > 0 && $schoolName !== '' && $district !== '' && $upazila !== '') {

        $photoSql = '';
        $params = [
            ':id' => $id,
            ':district' => $district,
            ':upazila' => $upazila,
            ':school_name' => $schoolName,
            ':mobile' => $mobile,
            ':status' => $status,
        ];

        if (!empty($_FILES['ePhoto']['name'])) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = pathinfo($_FILES['ePhoto']['name'], PATHINFO_EXTENSION);
            $fileName = 'school_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $target = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['ePhoto']['tmp_name'], $target)) {
                $photoSql = ", photo_path = :photo_path";
                $params[':photo_path'] = $target;
            }
        }

        $params[':updated_by'] = $_SESSION['user_id'] ?? null;

        $sql = "
            UPDATE schools
            SET district=:district,
                upazila=:upazila,
                school_name=:school_name,
                mobile=:mobile,
                status=:status,
                updated_by=:updated_by
                $photoSql
            WHERE id=:id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

    }

    header("Location: index.php");
    exit;
}

// ================== FILTER VALUES FROM GET ==================
$filterDistrict = $_GET['district'] ?? '';
$filterUpazila = $_GET['upazila'] ?? '';
$filterStatus = $_GET['status'] ?? '';

// ================== FETCH SCHOOLS ==================
$sql = "SELECT * FROM schools WHERE 1";
$params = [];
if ($filterDistrict !== '') {
    $sql .= " AND district = :f_district";
    $params[':f_district'] = $filterDistrict;
}
if ($filterUpazila !== '') {
    $sql .= " AND upazila = :f_upazila";
    $params[':f_upazila'] = $filterUpazila;
}
if ($filterStatus !== '') {
    $sql .= " AND status = :f_status";
    $params[':f_status'] = $filterStatus;
}
$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== DISTINCT DISTRICTS & UPAZILAS ==================
$districts = $pdo->query("SELECT DISTINCT district FROM schools ORDER BY district ASC")->fetchAll(PDO::FETCH_COLUMN);

if ($filterDistrict !== '') {
    $stmtU = $pdo->prepare("SELECT DISTINCT upazila FROM schools WHERE district = :d ORDER BY upazila ASC");
    $stmtU->execute([':d' => $filterDistrict]);
    $upazilas = $stmtU->fetchAll(PDO::FETCH_COLUMN);
} else {
    $upazilas = $pdo->query("SELECT DISTINCT upazila FROM schools ORDER BY upazila ASC")->fetchAll(PDO::FETCH_COLUMN);
}

// ================== FETCH NOTES BY SCHOOL ==================
$notesBySchool = [];
if (!empty($schools)) {
    $ids = array_column($schools, 'id');
    $in = implode(',', array_map('intval', $ids));
    $stmtN = $pdo->query("SELECT * FROM school_notes WHERE school_id IN ($in) ORDER BY note_date DESC");
    foreach ($stmtN as $row) {
        $notesBySchool[$row['school_id']][] = $row;
    }
}
?>