<?php
// accounts/core/add_core.php
require_once "../../auth/config.php";
require_login();

/* ---------- Basic request check ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_error'] = 'Invalid request method';
    header("Location: ../index.php");
    exit;
}

if (($_POST['action'] ?? '') !== 'insert_add') {
    $_SESSION['flash_error'] = 'Invalid action';
    header("Location: ../index.php");
    exit;
}

/* ---------- Collect raw inputs ---------- */
$date_raw   = $_POST['date'] ?? '';
$desc_raw   = $_POST['description'] ?? '';
$amount_raw = $_POST['amount'] ?? '';
$type_raw   = $_POST['type'] ?? '';
$method_raw = $_POST['payment_method'] ?? '';
$cat_raw    = $_POST['category'] ?? '';

/* ---------- Validate date (YYYY-MM-DD strict) ---------- */
$dt = DateTime::createFromFormat('Y-m-d', $date_raw);
if (!$dt || $dt->format('Y-m-d') !== $date_raw) {
    $_SESSION['flash_error'] = 'Invalid date format';
    header("Location: ../index.php");
    exit;
}
$date = $dt->format('Y-m-d');

/* ---------- Validate description ---------- */
$description = trim($desc_raw);
if ($description === '' || mb_strlen($description) > 255) {
    $_SESSION['flash_error'] = 'Invalid description';
    header("Location: ../index.php");
    exit;
}

/* ---------- Validate amount ---------- */
if (!is_numeric($amount_raw) || (float)$amount_raw < 0) {
    $_SESSION['flash_error'] = 'Invalid amount';
    header("Location: ../index.php");
    exit;
}
$amount = (float)$amount_raw;

/* ---------- Validate type ---------- */
$type = strtolower(trim($type_raw));
if (!in_array($type, ['income', 'expense'], true)) {
    $_SESSION['flash_error'] = 'Invalid type';
    header("Location: ../index.php");
    exit;
}

/* ---------- Validate method ---------- */
$allowedMethods = ['Cash','bKash','Nagad','Bank','Card','Other'];
$method = trim($method_raw);
if (!in_array($method, $allowedMethods, true)) {
    $_SESSION['flash_error'] = 'Invalid payment method';
    header("Location: ../index.php");
    exit;
}

/* ---------- Validate category ---------- */
$allowedCats = [
    'Buy','Marketing Cost','Office Supply', 'Repair',
    'Transport','Rent','Utilities','Revenue','Other'
];
$category = trim($cat_raw);
if (!in_array($category, $allowedCats, true)) {
    $_SESSION['flash_error'] = 'Invalid category';
    header("Location: ../index.php");
    exit;
}

/* ---------- Get user id ---------- */
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id || !is_numeric($user_id)) {
    $_SESSION['flash_error'] = 'Unauthorized user';
    header("Location: ../index.php");
    exit;
}
$user_id = (int)$user_id;

/* ---------- Insert using PDO + note_logs (transaction) ---------- */
try {
    $pdo->beginTransaction();

    // 1) accounts insert
    $sql = "INSERT INTO accounts
            (user_id, date, description, method, amount, category, type, created_at, updated_at)
            VALUES
            (:user_id, :date, :description, :method, :amount, :category, :type, NOW(), NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id'     => $user_id,
        ':date'        => $date,
        ':description' => $description,
        ':method'      => $method,
        ':amount'      => $amount,
        ':category'    => $category,
        ':type'        => $type
    ]);

    $account_id = (int)$pdo->lastInsertId();

    // 2) note_logs insert (account entry)
    $new_text_arr = [
        'account_id'     => $account_id,
        'user_id'        => $user_id,
        'date'           => $date,
        'description'    => $description,
        'payment_method' => $method,
        'amount'         => $amount,
        'category'       => $category,
        'type'           => $type,
    ];

    $logSql = "INSERT INTO note_logs
              (note_id, school_id, user_id, action, old_text, new_text, action_at)
              VALUES
              (:note_id, :school_id, :user_id, :action, :old_text, :new_text, NOW())";

    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([
        ':note_id'   => null,
        ':school_id' => null,
        ':user_id'   => $user_id,
        ':action'    => 'Add Entry',
        ':old_text'  => null,
        ':new_text'  => json_encode($new_text_arr, JSON_UNESCAPED_UNICODE),
    ]);

    $pdo->commit();

    $_SESSION['flash_success'] = 'Record added successfully';

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // production এ চাইলে লগ অন করো:
    // error_log($e->getMessage());

    $_SESSION['flash_error'] = 'Failed to add record';
}

header("Location: ../index.php");
exit;