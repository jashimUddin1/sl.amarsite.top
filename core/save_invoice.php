<?php
// core/save_invoice.php


header('Content-Type: application/json; charset=utf-8');

require_once '../config.php'; // ধরেছি: invoice/db/dbcon.php

// শুদ্ধ JSON রেসপন্স পাঠানোর হেল্পার
function send_json($array, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($array);
    exit;
}

// শুধু POST allow
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json([
        'success' => false,
        'message' => 'Method Not Allowed'
    ], 405);
}

// raw body পড়া
$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
    send_json([
        'success' => false,
        'message' => 'Empty request body'
    ], 400);
}

// JSON decode
$payload = json_decode($raw, true);
if (!is_array($payload) || !array_key_exists('invoice', $payload)) {
    send_json([
        'success' => false,
        'message' => 'Invalid JSON payload'
    ], 400);
}

// invoice data encode
$invoice = $payload['invoice'];
$encodedInvoice = json_encode($invoice, JSON_UNESCAPED_UNICODE);

if ($encodedInvoice === false) {
    send_json([
        'success' => false,
        'message' => 'Failed to encode invoice JSON'
    ], 400);
}

try {
    // dbcon.php থেকে আসা PDO আছে কিনা নিশ্চিত হই
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('PDO connection $pdo not found. Check dbcon.php.');
    }

    // 1) invoices টেবিলে ইনসার্ট
    $stmt = $pdo->prepare("INSERT INTO invoices (data) VALUES (:data)");
    $stmt->execute(array(':data' => $encodedInvoice));

    $invoiceId = $pdo->lastInsertId();

    // 2) invoice_logs টেবিলে লগ
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : null;

    $stmtLog = $pdo->prepare("
        INSERT INTO invoice_logs (invoice_id, action, action_user, data)
        VALUES (:invoice_id, 'create', :action_user, :data)
    ");
    $stmtLog->execute(array(
        ':invoice_id'  => $invoiceId,
        ':action_user' => $username,
        ':data'        => $encodedInvoice
    ));

    // 3) success JSON রিটার্ন
    send_json([
        'success' => true,
        'message' => 'Invoice saved successfully',
        'id'      => $invoiceId
    ]);

} catch (PDOException $e) {
    // Database level error
    send_json([
        'success' => false,
        'message' => 'Database Error',
        'error'   => $e->getMessage()
    ], 500);

} catch (Exception $e) {
    // অন্য যেকোনো error
    send_json([
        'success' => false,
        'message' => 'Server Error',
        'error'   => $e->getMessage()
    ], 500);
}
