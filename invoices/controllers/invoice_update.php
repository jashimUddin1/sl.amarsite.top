<?php
declare(strict_types=1);

require_once __DIR__ . '/../../auth/config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents("php://input");
$payload = json_decode($raw ?? '', true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Invalid JSON body'], JSON_UNESCAPED_UNICODE);
    exit;
}

$invoiceId = (int)($payload['invoice_id'] ?? 0);
$schoolId  = (int)($payload['school_id'] ?? 0);
$inNo      = (int)($payload['in_no'] ?? 0);
$data      = $payload['data'] ?? null;

if ($invoiceId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Invalid invoice_id'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($schoolId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Invalid school_id'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Invalid data'], JSON_UNESCAPED_UNICODE);
    exit;
}

# ✅ in_no না পাঠালে fallback (পুরোনো ক্লায়েন্ট হলে)
if ($inNo <= 0) {
    $inNo = (int)($data['invoiceNumber'] ?? 0);
}
if ($inNo <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Invalid in_no (invoice number)'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * ✅ Changed-only old/new বের করার ফাংশন
 * return: [oldChangedArray, newChangedArray]
 */
function diff_old_new($old, $new): array
{
    if (is_array($old) && is_array($new)) {
        $oldChanged = [];
        $newChanged = [];

        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));

        foreach ($keys as $k) {
            $oldExists = array_key_exists($k, $old);
            $newExists = array_key_exists($k, $new);

            $ov = $oldExists ? $old[$k] : null;
            $nv = $newExists ? $new[$k] : null;

            if (is_array($ov) && is_array($nv)) {
                [$oc, $nc] = diff_old_new($ov, $nv);
                if (!empty($oc) || !empty($nc)) {
                    $oldChanged[$k] = $oc;
                    $newChanged[$k] = $nc;
                }
                continue;
            }

            if ($ov !== $nv) {
                $oldChanged[$k] = $oldExists ? $ov : null;
                $newChanged[$k] = $newExists ? $nv : null;
            }
        }

        return [$oldChanged, $newChanged];
    }

    if ($old !== $new) {
        return [$old, $new];
    }

    return [[], []];
}

try {
    $pdo->beginTransaction();

    // ✅ আগে invoice আছে কিনা + school_id ম্যাচ করে কিনা
    $stmt = $pdo->prepare("SELECT id, school_id, in_no, data FROM invoices WHERE id = ? LIMIT 1");
    $stmt->execute([$invoiceId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'এই invoice_id ডাটাবেজে নেই।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ((int)$row['school_id'] !== $schoolId) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'এই invoice_id এই school এর নয়।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ✅ আগের raw JSON string
    $oldText = (string)($row['data'] ?? '');

    // ✅ old JSON decode
    $oldArr = json_decode($oldText, true);
    if (!is_array($oldArr)) $oldArr = [];

    // ✅ Duplicate check (GLOBAL) — একই in_no অন্য invoice এ আছে কিনা (current invoice বাদে)
    $chk = $pdo->prepare("SELECT id FROM invoices WHERE in_no = :in_no AND id <> :id LIMIT 1");
    $chk->execute(['in_no' => $inNo, 'id' => $invoiceId]);
    $exists = $chk->fetchColumn();

    if ($exists) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'এই Invoice Number (in_no) ইতিমধ্যে আছে। নতুন নম্বর দিন।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ✅ Backward compatibility: JSON এর ভিতরে invoiceNumber sync করে রাখি
    $data['invoiceNumber'] = $inNo;

    // ✅ (LAST STEP) আগে diff বের করে দেখি—কিছুই change হয়নি কিনা
    [$oldChanged, $newChanged] = diff_old_new($oldArr, $data);

    if (empty($oldChanged) && empty($newChanged)) {
        $pdo->rollBack();
        http_response_code(422);
        echo json_encode(['ok' => false, 'msg' => 'Nothing changed'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ✅ UPDATE করার আগেই old/new status ধরে রাখি (আপনার JSON অনুযায়ী totals.status)
    $oldStatus = strtoupper((string)($oldArr['totals']['status'] ?? ''));
    $newStatus = strtoupper((string)($data['totals']['status'] ?? ''));

    // ✅ Update (in_no + data)
    $stmt = $pdo->prepare("UPDATE invoices SET in_no = :in_no, data = :data WHERE id = :id");
    $stmt->execute([
        'in_no' => $inNo,
        'data'  => json_encode($data, JSON_UNESCAPED_UNICODE),
        'id'    => $invoiceId,
    ]);

    // ✅ paid_at logic (UNPAID <-> PAID)
    if ($oldStatus !== $newStatus) {
        if ($oldStatus === 'UNPAID' && $newStatus === 'PAID') {
            $paidStmt = $pdo->prepare("UPDATE invoices SET paid_at = NOW() WHERE id = :id");
            $paidStmt->execute(['id' => $invoiceId]);
        } elseif ($oldStatus === 'PAID' && $newStatus === 'UNPAID') {
            $paidStmt = $pdo->prepare("UPDATE invoices SET paid_at = NULL WHERE id = :id");
            $paidStmt->execute(['id' => $invoiceId]);
        }
    }

    // note_logs (optional) — থাকলে লগ লিখি; না থাকলে স্কিপ
    try {
        $userId = $_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0);
        if ($userId) {
            $action = 'Invoice Updated';

            $log = $pdo->prepare("INSERT INTO note_logs (user_id, school_id, action, old_text, new_text, action_at) VALUES (:user_id, :school_id, :action, :old_text, :new_text, NOW())");
            $log->execute([
                'user_id'   => (int)$userId,
                'school_id' => $schoolId,
                'action'    => $action,
                'old_text'  => json_encode($oldChanged, JSON_UNESCAPED_UNICODE),
                'new_text'  => json_encode($newChanged, JSON_UNESCAPED_UNICODE),
            ]);
        }
    } catch (Throwable $ignored) {
        // ignore logging failure
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'msg' => 'Updated', 'in_no' => $inNo], JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    if ((int)($e->errorInfo[1] ?? 0) === 1062) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'এই Invoice Number (in_no) ইতিমধ্যে আছে। নতুন নম্বর দিন।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Server error', 'err' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
