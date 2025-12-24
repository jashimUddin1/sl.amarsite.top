<?php
declare(strict_types=1);

require_once __DIR__ . '/../../auth/config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
    // JSON data থেকে invoiceNumber বের করছি
    $sql = "
        SELECT
            CAST(JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.invoiceNumber')) AS UNSIGNED) AS inv_no
        FROM invoices
        WHERE JSON_EXTRACT(`data`, '$.invoiceNumber') IS NOT NULL
    ";
    $stmt = $pdo->query($sql);

    $nums = [];
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $n = (int)($r['inv_no'] ?? 0);
        if ($n > 0) $nums[] = $n;
    }

    echo json_encode(['ok' => true, 'numbers' => $nums], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Server error', 'err' => $e->getMessage()]);
}
