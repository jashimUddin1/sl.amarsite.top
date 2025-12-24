<?php
// controllers/invoice_save_school.php
require_once '../auth/config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    echo json_encode(['ok' => false, 'message' => 'Invalid JSON']);
    exit;
}

$mode      = $payload['mode'] ?? 'new_school_new_invoice';
$schoolId  = isset($payload['school_id']) ? (int)$payload['school_id'] : 0;
$invoiceId = isset($payload['invoice_id']) ? (int)$payload['invoice_id'] : 0;
$data      = $payload['data'] ?? null;

if (!is_array($data)) {
    echo json_encode(['ok' => false, 'message' => 'Missing data']);
    exit;
}

try {
    // যদি school_id না থাকে, তাহলে billTo থেকে নতুন school তৈরি
    if ($schoolId <= 0) {
        $schoolName = trim((string)($data['billTo']['school'] ?? ''));
        $clientName = trim((string)($data['billTo']['name']  ?? ''));
        $phone      = trim((string)($data['billTo']['phone'] ?? ''));

        // আপনি চাইলে এখানে validation দিতে পারেন (যেমন schoolName না থাকলে error)
        $stmt = $pdo->prepare("INSERT INTO schools (school_name, client_name, mobile) VALUES (:sn, :cn, :mb)");
        $stmt->execute([
            ':sn' => $schoolName,
            ':cn' => $clientName,
            ':mb' => $phone,
        ]);
        $schoolId = (int)$pdo->lastInsertId();
    }

    // JSON encode
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Edit invoice
    if ($mode === 'edit_invoice' && $invoiceId > 0) {
        $stmt = $pdo->prepare("UPDATE invoices SET school_id = :sid, data = :data WHERE id = :id");
        $stmt->execute([
            ':sid'  => $schoolId,
            ':data' => $json,
            ':id'   => $invoiceId,
        ]);

        echo json_encode(['ok' => true, 'mode' => 'edit_invoice', 'invoice_id' => $invoiceId, 'school_id' => $schoolId]);
        exit;
    }

    // New invoice (existing school OR new school)
    $stmt = $pdo->prepare("INSERT INTO invoices (school_id, data) VALUES (:sid, :data)");
    $stmt->execute([
        ':sid'  => $schoolId,
        ':data' => $json,
    ]);
    $newInvoiceId = (int)$pdo->lastInsertId();

    echo json_encode(['ok' => true, 'mode' => 'new_invoice', 'invoice_id' => $newInvoiceId, 'school_id' => $schoolId]);
    exit;

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    exit;
}
