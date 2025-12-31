<?php
// accounts/core/delete_core.php
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


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_error'] = 'Invalid request method';
    header("Location: " . safe_return_url('../index.php'));
    exit;
}

if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
    $_SESSION['flash_error'] = 'Invalid CSRF token';
    header("Location: " . safe_return_url('../index.php'));
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    $_SESSION['flash_error'] = 'Unauthorized';
    header("Location: " . safe_return_url('../index.php'));
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash_error'] = 'Invalid id';
    header("Location: " . safe_return_url('../index.php'));
    exit;
}

try {
    $pdo->beginTransaction();

    // 1) Fetch the account row first (must be yours)
    $fetchSql = "SELECT id, user_id, date, description, method, amount, category, type
                 FROM accounts
                 WHERE id = :id AND user_id = :user_id
                 LIMIT 1";
    $fetchStmt = $pdo->prepare($fetchSql);
    $fetchStmt->execute([
        ':id' => $id,
        ':user_id' => $user_id
    ]);
    $row = $fetchStmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = 'Delete failed (not found or not yours)';
        header("Location: " . safe_return_url('../index.php'));
        exit;
    }

    // 2) Insert into accounts_trash
    // accounts_trash columns:
    // id, del_acc_id, user_id, date, description, method, amount, category, type, deleted_at
    $trashSql = "INSERT INTO accounts_trash
                (del_acc_id, user_id, date, description, method, amount, category, type, deleted_at)
                VALUES
                (:del_acc_id, :user_id, :date, :description, :method, :amount, :category, :type, NOW())";
    $trashStmt = $pdo->prepare($trashSql);
    $trashStmt->execute([
        ':del_acc_id'   => (int)$row['id'],
        ':user_id'      => $user_id,
        ':date'         => $row['date'],
        ':description'  => $row['description'],
        ':method'       => $row['method'],
        ':amount'       => $row['amount'],
        ':category'     => $row['category'],
        ':type'         => $row['type'],
    ]);

    // 3) Delete from accounts
    $delStmt = $pdo->prepare("DELETE FROM accounts WHERE id = :id AND user_id = :user_id LIMIT 1");
    $delStmt->execute([
        ':id' => $id,
        ':user_id' => $user_id
    ]);

    if ($delStmt->rowCount() === 0) {
        // Row existed earlier, so this is unexpected. Roll back to avoid half-operations.
        $pdo->rollBack();
        $_SESSION['flash_error'] = 'Delete failed (could not delete)';
        header("Location: " . safe_return_url('../index.php'));
        exit;
    }

    // 4) Insert note_logs
    // note_id = NULL, school_id = NULL
    // action = 'Entry delete'
    // old_text = accounts id
    // new_text = data (JSON)
    $logSql = "INSERT INTO note_logs
              (note_id, school_id, user_id, action, old_text, new_text, action_at)
              VALUES
              (:note_id, :school_id, :user_id, :action, :old_text, :new_text, NOW())";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([
        ':note_id'   => null,
        ':school_id' => null,
        ':user_id'   => $user_id,
        ':action'    => 'Entry delete',
        ':old_text'  => (string)$row['id'], // only id as requested
        ':new_text'  => json_encode($row, JSON_UNESCAPED_UNICODE),
    ]);

    $pdo->commit();
    $_SESSION['flash_success'] = 'Record deleted successfully';

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // error_log($e->getMessage());
    $_SESSION['flash_error'] = 'Delete failed';
}

header("Location: " . safe_return_url('../index.php'));
exit;
