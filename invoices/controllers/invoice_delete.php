<?php
declare(strict_types=1);

require_once __DIR__ . '/../../auth/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../invoices.php');
    exit;
}

$deleteId = (int)($_POST['delete_id'] ?? 0);

if ($deleteId <= 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Invalid invoice id.'];
    header('Location: ../invoices.php');
    exit;
}


$stmt = $pdo->prepare("DELETE FROM invoices WHERE id = :id");
$ok = $stmt->execute([':id' => $deleteId]);

$_SESSION['flash'] = $ok
    ? ['type' => 'success', 'msg' => 'Invoice deleted successfully.']
    : ['type' => 'danger', 'msg' => 'Delete failed.'];

header('Location: ../invoices.php');
exit;
