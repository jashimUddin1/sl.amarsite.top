<?php
// accounts/core/update_core.php (তুমি যে নামে রেখেছো সেটাই রাখবে)
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
    $_SESSION['flash_error'] = 'Invalid date';
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
    $_SESSION['flash_error'] = 'Invalid method';
    header("Location: ../index.php");
    exit;
}

/* ---------- Validate category ---------- */
$allowedCats = ['Buy','Marketing Cost','Office Supply','Repair','Transport','Rent','Utilities','Revenue','Other'];
$category = trim($cat_raw);
if (!in_array($category, $allowedCats, true)) {
    $_SESSION['flash_error'] = 'Invalid category';
    header("Location: ../index.php");
    exit;
}

try {
    $pdo->beginTransaction();

    /* ---------- 1) Fetch old data (must be yours) ---------- */
    $oldSql = "SELECT id, user_id, date, description, method, amount, category, type, created_at, updated_at
               FROM accounts
               WHERE id = :id AND user_id = :user_id
               LIMIT 1";
    $oldStmt = $pdo->prepare($oldSql);
    $oldStmt->execute([
        ':id' => $id,
        ':user_id' => $user_id
    ]);
    $oldRow = $oldStmt->fetch(PDO::FETCH_ASSOC);

    if (!$oldRow) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = 'Record not found or not yours';
        header("Location: ../index.php");
        exit;
    }

    /* ---------- 2) Update accounts ---------- */
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
        // কিছু পরিবর্তনই হয়নি (same data) — তবু log চাইলে log করব না
        $pdo->commit();
        $_SESSION['flash_error'] = 'Nothing updated (same data)';
        header("Location: ../index.php");
        exit;
    }

    /* ---------- 3) Prepare new data (log) ---------- */
    $newRow = [
        'id'          => $id,
        'user_id'     => $user_id,
        'date'        => $date,
        'description' => $description,
        'method'      => $method,
        'amount'      => $amount,
        'category'    => $category,
        'type'        => $type,
    ];

    /* ---------- 4) Insert note_logs ---------- */
    $logSql = "INSERT INTO note_logs
              (note_id, school_id, user_id, action, old_text, new_text, action_at)
              VALUES
              (:note_id, :school_id, :user_id, :action, :old_text, :new_text, NOW())";

    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([
        ':note_id'   => null,
        ':school_id' => null,
        ':user_id'   => $user_id,
        ':action'    => 'Entry Updated',
        ':old_text'  => json_encode($oldRow, JSON_UNESCAPED_UNICODE),
        ':new_text'  => json_encode($newRow, JSON_UNESCAPED_UNICODE),
    ]);

    $pdo->commit();
    $_SESSION['flash_success'] = 'Record updated successfully';

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // error_log($e->getMessage());
    $_SESSION['flash_error'] = 'Update failed';
}

header("Location: ../index.php");
exit;
