<?php // controllers/invoice_delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../auth/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../invoices.php');
    exit;
}

$user_id  = (int)($_SESSION['user_id'] ?? 0);
$deleteId = (int)($_POST['delete_id'] ?? 0);
$reason   = trim((string)($_POST['reason'] ?? ''));

if ($user_id <= 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Unauthorized.'];
    header('Location: ../invoices.php');
    exit;
}

if ($deleteId <= 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Invalid invoice id.'];
    header('Location: ../invoices.php');
    exit;
}

if ($reason === '') {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Delete reason is required.'];
    header('Location: ../invoices.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // 1) Fetch invoice
    $fetchSql = "SELECT id, in_no, school_id, data
                 FROM invoices
                 WHERE id = :id
                 LIMIT 1";
    $fetchStmt = $pdo->prepare($fetchSql);
    $fetchStmt->execute([':id' => $deleteId]);
    $row = $fetchStmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $pdo->rollBack();
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Invoice not found.'];
        header('Location: ../invoices.php');
        exit;
    }

    $schoolId = null;
    if (isset($row['school_id']) && (int)$row['school_id'] > 0) {
        $schoolId = (int)$row['school_id'];
    }

    // 2) Insert into invoice_trash
    $trashSql = "INSERT INTO invoice_trash
                (invoice_id, data, deleted_by, deleted_at, reason)
                VALUES
                (:invoice_id, :data, :deleted_by, NOW(), :reason)";
    $trashStmt = $pdo->prepare($trashSql);
    $trashStmt->execute([
        ':invoice_id' => (int)$row['id'],
        ':data'       => (string)$row['data'], 
        ':deleted_by' => $user_id,
        ':reason'     => $reason,
    ]);

    // 3) Delete from invoices
    $delStmt = $pdo->prepare("DELETE FROM invoices WHERE id = :id LIMIT 1");
    $delStmt->execute([':id' => $deleteId]);

    if ($delStmt->rowCount() === 0) {
        $pdo->rollBack();
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Delete failed (could not delete).'];
        header('Location: ../invoices.php');
        exit;
    }

    // 4) Insert note_logs (reason সহ)
    $logPayload = [
        'invoice_id' => (int)$row['id'],
        'in_no'      => (int)$row['in_no'],
        'school_id'  => $schoolId,
        'reason'     => $reason,
        'data'       => json_decode((string)$row['data'], true) ?: (string)$row['data'],
    ];

    $logSql = "INSERT INTO note_logs
              (note_id, school_id, user_id, action, old_text, new_text, action_at)
              VALUES
              (:note_id, :school_id, :user_id, :action, :old_text, :new_text, NOW())";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([
        ':note_id'   => null,
        ':school_id' => $schoolId, 
        ':user_id'   => $user_id,
        ':action'    => 'Invoice delete',
        ':old_text'  => (string)$row['id'],
        ':new_text'  => json_encode($logPayload, JSON_UNESCAPED_UNICODE),
    ]);

    $pdo->commit();
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Invoice deleted successfully.'];

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // error_log($e->getMessage());
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Delete failed.']; //for debug to add . $e->getMessage()
}

header('Location: ../invoices.php');
exit;
