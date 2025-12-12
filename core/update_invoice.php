<?php
// core/update_invoice.php

header('Content-Type: application/json; charset=utf-8');

// আলাদা session_start() দিচ্ছি না,
// কারণ তুমি dbcon.php-এর ভেতরেই session_start() রেখেছো।
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
if (!is_array($payload) || !isset($payload['invoice']) || !isset($payload['id'])) {
    send_json([
        'success' => false,
        'message' => 'Invalid JSON payload (need id + invoice)'
    ], 400);
}

$invoiceId = (int)$payload['id'];
if ($invoiceId <= 0) {
    send_json([
        'success' => false,
        'message' => 'Invalid invoice id'
    ], 400);
}

$invoice = $payload['invoice'];
$encodedInvoice = json_encode($invoice, JSON_UNESCAPED_UNICODE);
if ($encodedInvoice === false) {
    send_json([
        'success' => false,
        'message' => 'Failed to encode invoice JSON'
    ], 400);
}

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('PDO connection $pdo not found. Check dbcon.php');
    }

    // আগে দেখি ইনভয়েসটা আছে কিনা
    $check = $pdo->prepare("SELECT id FROM invoices WHERE id = :id LIMIT 1");
    $check->execute([':id' => $invoiceId]);
    if (!$check->fetch()) {
        send_json([
            'success' => false,
            'message' => 'Invoice not found'
        ], 404);
    }

    // 1) invoices টেবিলে UPDATE
    $stmt = $pdo->prepare("
        UPDATE invoices 
        SET data = :data 
        WHERE id = :id
    ");
    $stmt->execute([
        ':data' => $encodedInvoice,
        ':id'   => $invoiceId
    ]);

    // 2) invoice_logs এ লগ
    $username = $_SESSION['username'] ?? null;

    $stmtLog = $pdo->prepare("
        INSERT INTO invoice_logs (invoice_id, action, action_user, data)
        VALUES (:invoice_id, 'update', :action_user, :data)
    ");
    $stmtLog->execute([
        ':invoice_id'  => $invoiceId,
        ':action_user' => $username,
        ':data'        => $encodedInvoice
    ]);

    send_json([
        'success' => true,
        'message' => 'Invoice updated successfully',
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
