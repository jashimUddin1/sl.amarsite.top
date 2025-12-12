<?php
// core/delete_invoice.php

header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

function send_json($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// শুধু POST allow
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json([
        'success' => false,
        'message' => 'Method not allowed'
    ], 405);
}

// body পড়া
$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
    send_json([
        'success' => false,
        'message' => 'Empty request body'
    ], 400);
}

$payload = json_decode($raw, true);
if (!is_array($payload) || !isset($payload['id'])) {
    send_json([
        'success' => false,
        'message' => 'Invalid JSON payload (need id)'
    ], 400);
}

$invoiceId = (int)$payload['id'];
$reason    = isset($payload['reason']) ? trim($payload['reason']) : null;

if ($invoiceId <= 0) {
    send_json([
        'success' => false,
        'message' => 'Invalid invoice id'
    ], 400);
}

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('PDO connection $pdo not found. Check dbcon.php');
    }

    // প্রথমে ইনভয়েসটা নিয়ে আসি
    $stmt = $pdo->prepare("SELECT id, data FROM invoices WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $invoiceId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        send_json([
            'success' => false,
            'message' => 'Invoice not found'
        ], 404);
    }

    $dataJson = $row['data'];
    $deletedBy = $_SESSION['username'] ?? null;

    // ১) invoice_trash এ কপি করি (snapshot)
    $stmtTrash = $pdo->prepare("
        INSERT INTO invoice_trash (invoice_id, data, deleted_by, reason)
        VALUES (:invoice_id, :data, :deleted_by, :reason)
    ");
    $stmtTrash->execute([
        ':invoice_id' => $invoiceId,
        ':data'       => $dataJson,
        ':deleted_by' => $deletedBy,
        ':reason'     => $reason
    ]);

    // ২) invoice_logs এ delete লগ দিই
    $stmtLog = $pdo->prepare("
        INSERT INTO invoice_logs (invoice_id, action, action_user, data)
        VALUES (:invoice_id, 'delete', :action_user, :data)
    ");
    $stmtLog->execute([
        ':invoice_id'  => $invoiceId,
        ':action_user' => $deletedBy,
        ':data'        => $dataJson
    ]);

    // ৩) আসল invoices টেবিল থেকে ডিলিট করি
    $stmtDel = $pdo->prepare("DELETE FROM invoices WHERE id = :id");
    $stmtDel->execute([':id' => $invoiceId]);

    send_json([
        'success' => true,
        'message' => 'Invoice deleted successfully',
        'id'      => $invoiceId
    ]);

} catch (PDOException $e) {
    send_json([
        'success' => false,
        'message' => 'Database Error',
        'error'   => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    send_json([
        'success' => false,
        'message' => 'Server Error',
        'error'   => $e->getMessage()
    ], 500);
}
