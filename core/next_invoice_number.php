<?php
// core/next_invoice_number.php

header('Content-Type: application/json; charset=utf-8');

// এখানে আর session_start() দিচ্ছি না, কারণ তুমি dbcon.php এর ভিতরেই session_start রেখেছো
require_once '../config.php';

function send_json($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('PDO connection $pdo not found. Check dbcon.php');
    }

    // invoices.id এর max + 1 হিসাব
    $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_invoice FROM invoices");
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);

    $next = isset($row['next_invoice']) ? (int)$row['next_invoice'] : 1;

    send_json([
        'success'       => true,
        'next_invoice'  => $next,
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
