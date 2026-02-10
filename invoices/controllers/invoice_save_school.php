<?php //controllers/invoice_save_shcool.php
require_once '../../auth/config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
    exit;
}

$user_id = $_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0);
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Invalid JSON payload']);
    exit;
}

$school_id = (int) ($payload['school_id'] ?? 0);
$data = $payload['data'] ?? null;

if ($school_id <= 0 || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Missing School Id or data']);
    exit;
}

$in_no = (int)($payload['in_no'] ?? ($payload['invoice_number'] ?? 0));
if ($in_no <= 0) {
    // fallback (old clients): try from JSON
    $in_no = (int)($data['invoiceNumber'] ?? 0);
}
// keep JSON invoiceNumber for backward compatibility
$data['invoiceNumber'] = $in_no;
if ($in_no <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Invalid Invoice Number (in_no)']);
    exit;
}

// ✅ paid_at logic for CREATE (UNPAID/PAID based on totals.status)
$status = strtoupper((string)($data['totals']['status'] ?? ''));
$paid_at = ($status === 'PAID') ? date('Y-m-d H:i:s') : null;

// akhane aro validation or logic lekha jabe 


try {
    $pdo->beginTransaction();

    //duplicate check (GLOBAL: full table)
    $chk = $pdo->prepare("
        SELECT id
        FROM invoices
        WHERE CAST(JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.invoiceNumber')) AS UNSIGNED) = :inv
        LIMIT 1
    ");
    $chk->execute(['inv' => $in_no]);
    $exists = $chk->fetchColumn();

    if ($exists) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'এই Invoice Number ইতিমধ্যে আছে । নতুন নম্বর দিন।']);
        exit;
    }

    // all ok now start insert
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);

    // ✅ paid_at সহ insert
    $ins = $pdo->prepare("INSERT INTO invoices (school_id, in_no, data, paid_at, created_at) VALUES (:school_id, :in_no, :data, :paid_at, NOW())");
    $ins->execute([
        'school_id' => $school_id,
        'in_no' => $in_no,
        'data' => $json,
        'paid_at' => $paid_at
    ]);

    $invoice_id = (int) $pdo->lastInsertId();

    //note_logs insert this information
    $action = 'Invoice Create';
    $log = $pdo->prepare("INSERT INTO note_logs (user_id, school_id, action, new_text, action_at) VALUES (:user_id, :school_id, :action, :new_text, NOW()) ");
    $log->execute([
        'user_id' => $user_id,
        'school_id' => $school_id,
        'action' => $action,
        'new_text' => $json
    ]);

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'msg' => 'Invoice save Successfully!',
        'invoice_id' => $invoice_id,
        'in_no' => $in_no,
        'invoice_number' => $in_no
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();

    //database and mysql error hangle
    if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'এই Invoice Number ইতিমধ্যে আছে। নতুন নম্বর দিন।']);
        exit;
    }

    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Server error', 'err' => $e->getMessage()]);
}
