<?php
declare(strict_types=1);

require_once __DIR__ . '/../../auth/config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
    // invoices টেবিল থেকে data JSON নিয়ে আসি
    $stmt = $pdo->query("SELECT id, data FROM invoices ORDER BY id DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $numbers = [];
    foreach ($rows as $r) {
        $data = json_decode($r['data'] ?? '', true);
        if (!is_array($data)) continue;

        // invoiceNumber JSON এর ভিতর থেকে
        $inv = $data['invoiceNumber'] ?? null;

        // numeric/string যাই থাকুক, trim করে string হিসেবে রাখি
        if ($inv !== null && $inv !== '') {
            $invStr = trim((string)$inv);
            if ($invStr !== '') {
                $numbers[] = $invStr;
            }
        }
    }

    // duplicates remove
    $numbers = array_values(array_unique($numbers));

    echo json_encode([
        'ok' => true,
        'invoiceNumbers' => $numbers
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'Server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
