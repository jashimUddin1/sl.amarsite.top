<?php
require_once '../../auth/config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$user_school_id = $_SESSION['school_id'] ?? null; // যদি session এ থাকে (optional)

function json_ok($data = []) {
  echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE);
  exit;
}
function json_fail($msg, $code=400) {
  http_response_code($code);
  echo json_encode(['ok' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

/**
 * NOTE:
 * - $pdo তোমার config.php থেকে আসছে ধরে নিচ্ছি।
 * - যদি $pdo না থাকে, config.php তে যেটা আছে সেটা অনুযায়ী ঠিক করে নেবে।
 */

try {
  if ($action === 'list') {
    // চাইলে school-wise filter করতে পারো:
    // $school_id = $_GET['school_id'] ?? $user_school_id;
    $school_id = $_GET['school_id'] ?? null;

    if ($school_id) {
      $stmt = $pdo->prepare("SELECT id, school_id, data, created_at, updated_at FROM invoices WHERE school_id = ? ORDER BY id DESC");
      $stmt->execute([$school_id]);
    } else {
      $stmt = $pdo->query("SELECT id, school_id, data, created_at, updated_at FROM invoices ORDER BY id DESC");
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // decode JSON for frontend convenience
    $invoices = array_map(function($r){
      $j = json_decode($r['data'], true);
      if (!is_array($j)) $j = [];
      return [
        'id' => (int)$r['id'],
        'school_id' => $r['school_id'],
        'data' => $j,
        'created_at' => $r['created_at'],
        'updated_at' => $r['updated_at'],
      ];
    }, $rows);

    json_ok(['invoices' => $invoices]);
  }

  if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) json_fail("Invalid invoice id");

    $stmt = $pdo->prepare("SELECT id, school_id, data, created_at, updated_at FROM invoices WHERE id = ?");
    $stmt->execute([$id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r) json_fail("Invoice not found", 404);

    $j = json_decode($r['data'], true);
    if (!is_array($j)) $j = [];

    json_ok([
      'invoice' => [
        'id' => (int)$r['id'],
        'school_id' => $r['school_id'],
        'data' => $j,
        'created_at' => $r['created_at'],
        'updated_at' => $r['updated_at'],
      ]
    ]);
  }

  if ($action === 'save') {
    // create/update
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) json_fail("Invalid JSON payload");

    $id = (int)($payload['id'] ?? 0);
    $school_id = $payload['school_id'] ?? null;
    $data = $payload['data'] ?? null;

    if (!$school_id) json_fail("school_id required");
    if (!is_array($data)) json_fail("data object required");

    // (optional) minimal validation
    if (!isset($data['invoiceNumber'], $data['invoiceDate'], $data['billTo'], $data['items'], $data['totals'])) {
      json_fail("Missing required invoice fields");
    }

    $data_json = json_encode($data, JSON_UNESCAPED_UNICODE);

    if ($id > 0) {
      $stmt = $pdo->prepare("UPDATE invoices SET school_id = ?, data = ?, updated_at = NOW() WHERE id = ?");
      $stmt->execute([$school_id, $data_json, $id]);
      json_ok(['id' => $id, 'mode' => 'updated']);
    } else {
      $stmt = $pdo->prepare("INSERT INTO invoices (school_id, data, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
      $stmt->execute([$school_id, $data_json]);
      $newId = (int)$pdo->lastInsertId();
      json_ok(['id' => $newId, 'mode' => 'created']);
    }
  }

  if ($action === 'delete') {
    $payload = json_decode(file_get_contents('php://input'), true);
    $id = (int)($payload['id'] ?? 0);
    if (!$id) json_fail("Invalid invoice id");

    $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
    $stmt->execute([$id]);

    json_ok(['deleted' => true]);
  }

  json_fail("Unknown action", 404);

} catch (Exception $e) {
  json_fail("Server error: " . $e->getMessage(), 500);
}
