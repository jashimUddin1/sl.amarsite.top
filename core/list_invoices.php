<?php
// core/list_invoices.php
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';  

function send_json($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// শুধু লগইন থাকলে দেখা যাবে চাইলে (না চাইলে এই if ব্লকটা কমেন্ট করে দাও)
if (!isset($_SESSION['user_id'])) {
    send_json([
        'success' => false,
        'message' => 'Unauthorized'
    ], 401);
}

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('PDO connection missing. Check dbcon.php');
    }

    // ইচ্ছা হলে user ভিত্তিক ফিল্টার করতে পারো
    $stmt = $pdo->query("
        SELECT id, data, created_at, updated_at
        FROM invoices
        ORDER BY id ASC
    ");

    $list = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data = json_decode($row['data'], true);
        if (!is_array($data)) {
            continue;
        }

        // DB আইডি রেখে দিচ্ছি
        $data['dbId'] = (int)$row['id'];

        // যদি data তে নিজে থেকে createdAt না থাকে, DB এর সময় বসিয়ে দিচ্ছি
        if (!isset($data['createdAt']) && isset($row['created_at'])) {
            $data['createdAt'] = $row['created_at'];
        }

        // frontend এ একটা id থাকাই ভাল
        if (!isset($data['id'])) {
            $data['id'] = 'db-' . $row['id'];
        }

        $list[] = $data;
    }

    send_json([
        'success'  => true,
        'invoices' => $list
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
