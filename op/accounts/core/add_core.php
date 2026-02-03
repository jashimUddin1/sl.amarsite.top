<?php
// accounts/core/add_core.php
require_once "../../auth/config.php";
require_login();

function safe_return_url(string $fallback = '../index.php'): string {
    $ret = $_POST['return'] ?? '';
    if ($ret === '') return $fallback;

    // only allow relative internal urls
    $parts = parse_url($ret);
    $path = $parts['path'] ?? '';
    $qs   = isset($parts['query']) ? ('?' . $parts['query']) : '';

    // allow only your accounts index page
    if ($path !== '' && (str_ends_with($path, '/accounts/index.php') || str_ends_with($path, '/accounts/index_up.php') || str_ends_with($path, '/accounts/index.php'))) {
        return $path . $qs;
    }
    // if it's relative like "index.php?sheet=income"
    if ($path === 'index.php' || $path === './index.php' || $path === '../index.php') {
        return '../index.php' . $qs;
    }
    return $fallback;
}


/* ---------- Basic request check ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_error'] = 'Invalid request method';
    header("Location: " . safe_return_url('../index.php'));
    exit;
}

if (($_POST['action'] ?? '') !== 'insert_add') {
    $_SESSION['flash_error'] = 'Invalid action';
    header("Location: " . safe_return_url('../index.php'));
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
    header("Location: " . safe_return_url('../index.php'));
    exit;
}
$date = $dt->format('Y-m-d');

/* ---------- Validate description ---------- */
$description = trim($desc_raw);
if ($description === '' || mb_strlen($description) > 255) {
    $_SESSION['flash_error'] = 'Invalid description';
    header("Location: " . safe_return_url('../index.php'));
    exit;
}

/* ---------- Validate amount ---------- */
if (!is_numeric($amount_raw) || (float)$amount_raw < 0) {
    $_SESSION['flash_error'] = 'Invalid amount';
    header("Location: " . safe_return_url('../index.php'));
    exit;
}
$amount = (float)$amount_raw;

/* ---------- Validate type ---------- */
$type = strtolower(trim($type_raw));
if (!in_array($type, ['income', 'expense'], true)) {
    $_SESSION['flash_error'] = 'Invalid type';
    header("Location: " . safe_return_url('../index.php'));
    exit;
}

/* ---------- Validate method ---------- */
$allowedMethods = ['Cash','bKash','Nagad','Bank','Card','Other'];
$method = trim($method_raw);
if (!in_array($method, $allowedMethods, true)) {
    $_SESSION['flash_error'] = 'Invalid payment method';
    header("Location: " . safe_return_url('../index.php'));
    exit;
}

/* ---------- Validate category ---------- */
$allowedCats = [
    'Buy','Marketing Cost','Office Supply', 'Repair', 'Transport','Rent','Utilities','Revenue',  'Bike Service Cost', 'Office Cost',     'Bike Oil Cost',     'Nasta Pani',     'Paper Cost',     'Raja',     'Yasin' ,'Other'
];

$category = trim($cat_raw);
if (!in_array($category, $allowedCats, true)) {
    $_SESSION['flash_error'] = 'Invalid category';
    header("Location: " . safe_return_url('../index.php'));
    exit;
}

/* ---------- Get user id ---------- */
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id || !is_numeric($user_id)) {
    $_SESSION['flash_error'] = 'Unauthorized user';
    header("Location: " . safe_return_url('../index.php'));
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
        ':action'    => 'Entry Add',
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

header("Location: " . safe_return_url('../index.php'));
exit;