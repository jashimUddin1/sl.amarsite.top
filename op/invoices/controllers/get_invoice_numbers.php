<?php 
declare(strict_types=1);

require_once __DIR__ . '/../../auth/config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
    // ✅ এখন JSON থেকে না, টেবিলের in_no কলাম থেকে ইনভয়েস নম্বর আনবো
    $stmt = $pdo->query("SELECT in_no FROM invoices WHERE in_no IS NOT NULL AND in_no > 0 ORDER BY in_no DESC");
    $numbers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // string normalize + unique
    $numbers = array_values(array_unique(array_filter(array_map(static function ($v) {
        $s = trim((string)$v);
        return $s === '' ? null : $s;
    }, $numbers))));

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
