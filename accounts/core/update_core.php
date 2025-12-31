<?php
require_once "../../auth/config.php";
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_error'] = 'Invalid request method';
    header("Location: ../index.php");
    exit;
}

if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
    $_SESSION['flash_error'] = 'Invalid CSRF token';
    header("Location: ../index.php");
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    $_SESSION['flash_error'] = 'Unauthorized';
    header("Location: ../index.php");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash_error'] = 'Invalid id';
    header("Location: ../index.php");
    exit;
}

$date_raw   = $_POST['date'] ?? '';
$desc_raw   = $_POST['description'] ?? '';
$amount_raw = $_POST['amount'] ?? '';
$type_raw   = $_POST['type'] ?? '';
$method_raw = $_POST['payment_method'] ?? '';
$cat_raw    = $_POST['category'] ?? '';

// date validate
$dt = DateTime::createFromFormat('Y-m-d', $date_raw);
if (!$dt || $dt->format('Y-m-d') !== $date_raw) {
    $_SESSION['flash_error'] = 'Invalid date';
    header("Location: ../index.php");
    exit;
}
$date = $dt->format('Y-m-d');

// description
$description = trim($desc_raw);
if ($description === '' || mb_strlen($description) > 255) {
    $_SESSION['flash_error'] = 'Invalid description';
    header("Location: ../index.php");
    exit;
}

// amount
if (!is_numeric($amount_raw)) {
    $_SESSION['flash_error'] = 'Invalid amount';
    header("Location: ../index.php");
    exit;
}
$amount = (float)$amount_raw;
if ($amount < 0) {
    $_SESSION['flash_error'] = 'Amount must be >= 0';
    header("Location: ../index.php");
    exit;
}

// type
$type = strtolower(trim($type_raw));
if (!in_array($type, ['income', 'expense'], true)) {
    $_SESSION['flash_error'] = 'Invalid type';
    header("Location: ../index.php");
    exit;
}

// method
$allowedMethods = ['Cash','bKash','Nagad','Bank','Card','Other'];
$method = trim($method_raw);
if (!in_array($method, $allowedMethods, true)) {
    $_SESSION['flash_error'] = 'Invalid method';
    header("Location: ../index.php");
    exit;
}

// category
$allowedCats = ['buy','marketing_cost','office_supply','cost2','Transport','Rent','Utilities','revenue','Other'];
$category = trim($cat_raw);
if (!in_array($category, $allowedCats, true)) {
    $_SESSION['flash_error'] = 'Invalid category';
    header("Location: ../index.php");
    exit;
}

try {
    $sql = "UPDATE accounts
            SET date = :date,
                description = :description,
                method = :method,
                amount = :amount,
                category = :category,
                type = :type,
                updated_at = NOW()
            WHERE id = :id AND user_id = :user_id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':date' => $date,
        ':description' => $description,
        ':method' => $method,
        ':amount' => $amount,
        ':category' => $category,
        ':type' => $type,
        ':id' => $id,
        ':user_id' => $user_id
    ]);

    if ($stmt->rowCount() === 0) {
        $_SESSION['flash_error'] = 'Nothing updated (not found or not yours)';
    } else {
        $_SESSION['flash_success'] = 'Record updated successfully';
    }

} catch (Throwable $e) {
    // error_log($e->getMessage());
    $_SESSION['flash_error'] = 'Update failed';
}

header("Location: ../index.php");
exit;
