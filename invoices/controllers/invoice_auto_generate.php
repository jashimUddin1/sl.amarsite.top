<?php
declare(strict_types=1);

require_once __DIR__ . '/../../auth/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$user_id = $_SESSION['user_id'] ?? null;

// ✅ Month range
$monthStart = date('Y-m-01 00:00:00');
$monthEnd   = date('Y-m-t 23:59:59');
$ymNow      = date('Y-m');

// ✅ Helper
function safe_json(array $arr): string {
    return json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

try {
    // ✅ Approved schools
    $approvedStmt = $pdo->prepare("
        SELECT id, school_name, client_name, mobile, m_fee
        FROM schools
        WHERE status='approved' OR status=1
        ORDER BY id ASC
    ");
    $approvedStmt->execute();
    $schools = $approvedStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$schools) {
        $_SESSION['flash'] = ['type' => 'info', 'msg' => 'Approved school পাওয়া যায়নি।'];
        header('Location: ../invoices.php');
        exit;
    }

    // ✅ Check invoice exists for a school in this month
    $invCheck = $pdo->prepare("
        SELECT id, data, created_at
        FROM invoices
        WHERE school_id = :sid
          AND created_at BETWEEN :ms AND :me
        ORDER BY id DESC
        LIMIT 50
    ");

    // ✅ Find schools with no invoice this month
    $pending = [];
    foreach ($schools as $s) {
        $sid = (int)$s['id'];

        $invCheck->execute([':sid' => $sid, ':ms' => $monthStart, ':me' => $monthEnd]);
        $list = $invCheck->fetchAll(PDO::FETCH_ASSOC);

        $hasThisMonth = false;
        foreach ($list as $inv) {
            $data = json_decode($inv['data'] ?? '', true);
            $invDate = $data['invoiceDate'] ?? null;

            if ($invDate) {
                $ts = strtotime($invDate);
                if ($ts && date('Y-m', $ts) === $ymNow) {
                    $hasThisMonth = true;
                    break;
                }
            } else {
                $ts = strtotime($inv['created_at'] ?? '');
                if ($ts && date('Y-m', $ts) === $ymNow) {
                    $hasThisMonth = true;
                    break;
                }
            }
        }

        if (!$hasThisMonth) $pending[] = $s;
    }

    if (!$pending) {
        $_SESSION['flash'] = ['type' => 'info', 'msg' => 'এই মাসে সব approved স্কুলের invoice আগেই আছে।'];
        header('Location: ../invoices.php');
        exit;
    }

    // ✅ Transaction start
    $pdo->beginTransaction();

    // ✅ Next invoice number (in_no)
    // NOTE: concurrency থাকলে separate sequence table best; আপাতত transaction + FOR UPDATE দিয়ে safe করা হলো
    $mxRow = $pdo->query("SELECT COALESCE(MAX(in_no),0) AS mx FROM invoices FOR UPDATE")->fetch(PDO::FETCH_ASSOC);
    $nextNo = (int)($mxRow['mx'] ?? 0) + 1;

    $insert = $pdo->prepare("
        INSERT INTO invoices (in_no, school_id, data, created_at, updated_at)
        VALUES (:in_no, :school_id, :data, NOW(), NOW())
    ");

    $created = 0;

    foreach ($pending as $s) {
        $fee = (float)($s['m_fee'] ?? 0);

        // fee <= 0 হলে invoice বানাবেন কি না (আপনি চাইলে বাদ দিন)
        if ($fee <= 0) continue;

        $payload = [
            'invoiceDate' => date('Y-m-d'),
            'billTo' => [
                'school' => $s['school_name'] ?? ('School ID: ' . $s['id']),
                'client_name' => $s['client_name'] ?? '',
                'mobile' => $s['mobile'] ?? ''
            ],
            'items' => [
                [
                    'description' => 'Monthly Fee',
                    'qty' => 1,
                    'rate' => $fee,
                    'amount' => $fee
                ]
            ],
            'totals' => [
                'total' => $fee,
                'pay' => 0,
                'due' => $fee,
                'status' => 'UNPAID'
            ],
            'note' => ''
        ];

        $insert->execute([
            ':in_no' => $nextNo,
            ':school_id' => (int)$s['id'],
            ':data' => safe_json($payload),
        ]);

        $nextNo++;
        $created++;
    }

    $pdo->commit();

    $_SESSION['flash'] = ['type' => 'success', 'msg' => "Auto-generated invoice: {$created} টি (এই মাসের জন্য)"];
    header('Location: ../invoices.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Auto-generate failed: ' . $e->getMessage()];
    header('Location: ../invoices.php');
    exit;
}
