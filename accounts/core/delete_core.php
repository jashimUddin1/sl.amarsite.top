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

try {
    $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = :id AND user_id = :user_id LIMIT 1");
    $stmt->execute([
        ':id' => $id,
        ':user_id' => $user_id
    ]);

    if ($stmt->rowCount() === 0) {
        $_SESSION['flash_error'] = 'Delete failed (not found or not yours)';
    } else {
        $_SESSION['flash_success'] = 'Record deleted successfully';
    }

} catch (Throwable $e) {
    // error_log($e->getMessage());
    $_SESSION['flash_error'] = 'Delete failed';
}

header("Location: ../index.php");
exit;
