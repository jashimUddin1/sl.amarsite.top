<?php
declare(strict_types=1);

require_once __DIR__ . '/../../auth/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$user_id = $_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0);

// Current month range
// Selected month
$ymNow = $_POST['month'] ?? date('Y-m');

$monthStart = $ymNow . '-01 00:00:00';

$monthEnd = date(
    'Y-m-t 23:59:59',
    strtotime($ymNow . '-01')
);

function safe_json(array $arr): string
{
    return json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function numberToWords(int $number): string
{
    $ones = [
        0 => 'Zero',
        1 => 'One',
        2 => 'Two',
        3 => 'Three',
        4 => 'Four',
        5 => 'Five',
        6 => 'Six',
        7 => 'Seven',
        8 => 'Eight',
        9 => 'Nine',
        10 => 'Ten',
        11 => 'Eleven',
        12 => 'Twelve',
        13 => 'Thirteen',
        14 => 'Fourteen',
        15 => 'Fifteen',
        16 => 'Sixteen',
        17 => 'Seventeen',
        18 => 'Eighteen',
        19 => 'Nineteen',
    ];

    $tens = [
        2 => 'Twenty',
        3 => 'Thirty',
        4 => 'Forty',
        5 => 'Fifty',
        6 => 'Sixty',
        7 => 'Seventy',
        8 => 'Eighty',
        9 => 'Ninety',
    ];

    if ($number < 20) {
        return $ones[$number];
    }

    if ($number < 100) {
        $ten = intdiv($number, 10);
        $rest = $number % 10;
        return $tens[$ten] . ($rest ? ' ' . $ones[$rest] : '');
    }

    if ($number < 1000) {
        $hundred = intdiv($number, 100);
        $rest = $number % 100;
        return $ones[$hundred] . ' Hundred' . ($rest ? ' ' . numberToWords($rest) : '');
    }

    if ($number < 100000) {
        $thousand = intdiv($number, 1000);
        $rest = $number % 1000;
        return numberToWords($thousand) . ' Thousand' . ($rest ? ' ' . numberToWords($rest) : '');
    }

    if ($number < 10000000) {
        $lakh = intdiv($number, 100000);
        $rest = $number % 100000;
        return numberToWords($lakh) . ' Lakh' . ($rest ? ' ' . numberToWords($rest) : '');
    }

    $crore = intdiv($number, 10000000);
    $rest = $number % 10000000;
    return numberToWords($crore) . ' Crore' . ($rest ? ' ' . numberToWords($rest) : '');
}

try {
    // Approved schools
    $approvedStmt = $pdo->prepare("
        SELECT id, school_name, client_name, mobile, m_fee
        FROM schools
        WHERE status = 'Approved'
        ORDER BY id ASC
    ");
    $approvedStmt->execute();
    $schools = $approvedStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$schools) {
        $_SESSION['flash'] = [
            'type' => 'info',
            'msg' => 'Approved school পাওয়া যায়নি।'
        ];
        header('Location: ../invoices.php');
        exit;
    }

    // Existing invoice check for this month
    $invCheck = $pdo->prepare("
        SELECT id, data, created_at
        FROM invoices
        WHERE school_id = :sid
          AND created_at BETWEEN :ms AND :me
        ORDER BY id DESC
        LIMIT 50
    ");

    $pending = [];

    foreach ($schools as $s) {
        $sid = (int)$s['id'];

        $invCheck->execute([
            ':sid' => $sid,
            ':ms' => $monthStart,
            ':me' => $monthEnd
        ]);

        $list = $invCheck->fetchAll(PDO::FETCH_ASSOC);

        $hasThisMonth = false;

        foreach ($list as $inv) {
            $data = json_decode($inv['data'] ?? '', true);
            $invDate = $data['invoiceDate'] ?? null;

            if ($invDate) {
                $ts = strtotime((string)$invDate);
                if ($ts && date('Y-m', $ts) === $ymNow) {
                    $hasThisMonth = true;
                    break;
                }
            } else {
                $ts = strtotime((string)($inv['created_at'] ?? ''));
                if ($ts && date('Y-m', $ts) === $ymNow) {
                    $hasThisMonth = true;
                    break;
                }
            }
        }

        if (!$hasThisMonth) {
            $pending[] = $s;
        }
    }

    if (!$pending) {
        $_SESSION['flash'] = [
            'type' => 'info',
            'msg' => date('F Y', strtotime($ymNow . '-01')) . ' মাসের সব approved স্কুলের invoice আগেই আছে।'
        ];
        header('Location: ../invoices.php');
        exit;
    }

    $pdo->beginTransaction();

    // Safe next invoice number
    $mxRow = $pdo->query("
        SELECT COALESCE(MAX(in_no), 0) AS mx
        FROM invoices
        FOR UPDATE
    ")->fetch(PDO::FETCH_ASSOC);

    $nextNo = (int)($mxRow['mx'] ?? 0) + 1;

    $insert = $pdo->prepare("
        INSERT INTO invoices (in_no, school_id, data, created_at, updated_at)
        VALUES (:in_no, :school_id, :data, NOW(), NOW())
    ");

    $logStmt = $pdo->prepare("
        INSERT INTO note_logs (user_id, school_id, action, new_text, action_at)
        VALUES (:user_id, :school_id, :action, :new_text, NOW())
    ");

    $created = 0;
    $monthLabel = date('M', strtotime($ymNow . '-01'));

    foreach ($pending as $s) {
        $schoolId = (int)$s['id'];
        $fee = (float)($s['m_fee'] ?? 0);

        if ($fee <= 0) {
            continue;
        }

        $payload = [
            'invoiceNumber' => $nextNo,
            'invoiceDate' => $ymNow . '-01',
            'invoiceStyle' => 'classic',
            'billTo' => [
                'school' => $s['school_name'] ?? ('School ID: ' . $schoolId),
                'name' => $s['client_name'] ?? '',
                'phone' => $s['mobile'] ?? ''
            ],
            'items' => [
                [
                    'desc' => 'Software Subscription Fee (' . $monthLabel . ')',
                    'qty_raw' => '1',
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
            'note' => numberToWords((int)$fee) . ' Taka Only.'
        ];

        $json = safe_json($payload);

        $insert->execute([
            ':in_no' => $nextNo,
            ':school_id' => $schoolId,
            ':data' => $json,
        ]);

        // note_logs insert
        $logStmt->execute([
            ':user_id' => $user_id,
            ':school_id' => $schoolId,
            ':action' => 'Invoice Auto Create',
            ':new_text' => $json
        ]);

        $nextNo++;
        $created++;
    }

    $pdo->commit();

    $_SESSION['flash'] = [
        'type' => 'success',
        'msg' => "Auto-generated invoice: {$created} টি (" . date('F Y', strtotime($ymNow . '-01')) . ")"
    ];
    header('Location: ../invoices.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['flash'] = [
        'type' => 'danger',
        'msg' => 'Auto-generate failed: ' . $e->getMessage()
    ];
    header('Location: ../invoices.php');
    exit;
}

