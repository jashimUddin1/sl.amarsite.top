<?php
declare(strict_types=1);

require_once __DIR__ . '/../../auth/config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents("php://input");
$payload = json_decode($raw ?? '', true);

if (!is_array($payload)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'msg' => 'Invalid JSON body']);
  exit;
}

$invoiceId = (int)($payload['invoice_id'] ?? 0);
$schoolId  = (int)($payload['school_id'] ?? 0);
$data      = $payload['data'] ?? null;

if ($invoiceId <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'msg' => 'Invalid invoice_id']);
  exit;
}

if ($schoolId <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'msg' => 'Invalid school_id']);
  exit;
}

if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'msg' => 'Invalid data']);
  exit;
}



// ✅ আগে invoice আছে কিনা + school_id ম্যাচ করে কিনা
$stmt = $pdo->prepare("SELECT id, school_id FROM invoices WHERE id = ?");
$stmt->execute([$invoiceId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  http_response_code(404);
  echo json_encode(['ok' => false, 'msg' => 'এই invoice_id ডাটাবেজে নেই।']);
  exit;
}

if ((int)$row['school_id'] !== $schoolId) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'msg' => 'এই invoice_id এই school এর নয়।']);
  exit;
}

// ✅ Update
$stmt = $pdo->prepare("UPDATE invoices SET data = ? WHERE id = ?");
$stmt->execute([json_encode($data, JSON_UNESCAPED_UNICODE), $invoiceId]);

echo json_encode(['ok' => true, 'msg' => 'Updated']);
