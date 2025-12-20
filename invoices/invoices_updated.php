<?php
require_once '../auth/config.php';
require_login();

$pageTitle = 'Saved Invoices';
$activeMenu = 'invoices';

if (file_exists('../layout/layout_header_invoices.php')) {
    require '../layout/layout_header_invoices.php';
} elseif (file_exists('../layout/layout_header.php')) {
    require '../layout/layout_header.php';
}

/**
 * Filters (optional):
 *  - q: invoice number or school name (partial match)
 *  - status: UNPAID | PAID | PARTIAL
 *  - from: YYYY-MM-DD
 *  - to: YYYY-MM-DD
 */
$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');

$allowedStatus = ['UNPAID', 'PAID', 'PARTIAL'];
if ($status !== '' && !in_array($status, $allowedStatus, true)) {
    $status = '';
}

// Build SQL with JSON_EXTRACT based on your "2nd JSON" schema
$sql = "
    SELECT
        id,
        school_id,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.invoiceNumber')) AS UNSIGNED) AS invoice_number,
        JSON_UNQUOTE(JSON_EXTRACT(data, '$.invoiceDate')) AS invoice_date,
        JSON_UNQUOTE(JSON_EXTRACT(data, '$.invoiceStyle')) AS invoice_style,
        JSON_UNQUOTE(JSON_EXTRACT(data, '$.billTo.school')) AS school_name,
        JSON_UNQUOTE(JSON_EXTRACT(data, '$.billTo.phone')) AS phone,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.totals.total')) AS DECIMAL(12,2)) AS total_amount,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.totals.pay')) AS DECIMAL(12,2)) AS pay_amount,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.totals.due')) AS DECIMAL(12,2)) AS due_amount,
        JSON_UNQUOTE(JSON_EXTRACT(data, '$.totals.status')) AS payment_status,
        created_at
    FROM invoices
    WHERE 1=1
";

$params = [];

if ($status !== '') {
    $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.totals.status')) = :status ";
    $params['status'] = $status;
}

if ($from !== '') {
    // invoiceDate is stored as YYYY-MM-DD in your JSON
    $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.invoiceDate')) >= :from ";
    $params['from'] = $from;
}
if ($to !== '') {
    $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.invoiceDate')) <= :to ";
    $params['to'] = $to;
}

if ($q !== '') {
    // Match invoice number (as text) OR school name (partial)
    $sql .= "
        AND (
            JSON_UNQUOTE(JSON_EXTRACT(data, '$.billTo.school')) LIKE :q_like
            OR JSON_UNQUOTE(JSON_EXTRACT(data, '$.invoiceNumber')) LIKE :q_like
        )
    ";
    $params['q_like'] = '%' . $q . '%';
}

$sql .= " ORDER BY id DESC ";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo '<div class="container py-4"><div class="alert alert-danger">DB Error: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
    if (file_exists('../layout/layout_footer.php')) require '../layout/layout_footer.php';
    exit;
}

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<div class="container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-0">Saved Invoices</h4>
            <div class="text-muted small">মোট: <?= count($rows) ?> টি</div>
        </div>

        <div class="d-flex gap-2">
            <?php if (file_exists('invoice_create.php')): ?>
                <a class="btn btn-success btn-sm" href="invoice_create.php">+ New Invoice</a>
            <?php endif; ?>
        </div>
    </div>

    <form class="card card-body mb-3" method="get">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label mb-1">Search (Invoice No / School)</label>
                <input type="text" name="q" value="<?= h($q) ?>" class="form-control form-control-sm" placeholder="e.g. 11 / আল-আবরার">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['UNPAID','PAID','PARTIAL'] as $s): ?>
                        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label mb-1">From</label>
                <input type="date" name="from" value="<?= h($from) ?>" class="form-control form-control-sm">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label mb-1">To</label>
                <input type="date" name="to" value="<?= h($to) ?>" class="form-control form-control-sm">
            </div>

            <div class="col-6 col-md-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm w-100" type="submit">Filter</button>
                <a class="btn btn-outline-secondary btn-sm w-100" href="invoices.php">Reset</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th style="width:60px;">#</th>
                            <th style="width:120px;">Invoice No</th>
                            <th style="width:120px;">Date</th>
                            <th>School / Client</th>
                            <th style="width:120px;">Total</th>
                            <th style="width:120px;">Pay</th>
                            <th style="width:120px;">Due</th>
                            <th style="width:120px;">Status</th>
                            <th style="width:160px;">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (!$rows): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">কোনো ইনভয়েস পাওয়া যায়নি</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $i => $r): ?>
                            <?php
                                $st = strtoupper((string)($r['payment_status'] ?? 'UNPAID'));
                                $badge = 'secondary';
                                if ($st === 'PAID') $badge = 'success';
                                elseif ($st === 'PARTIAL') $badge = 'warning';
                            ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td class="text-center fw-semibold"><?= h($r['invoice_number']) ?></td>
                                <td class="text-center"><?= h($r['invoice_date']) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= h($r['school_name'] ?: '-') ?></div>
                                    <?php if (!empty($r['phone'])): ?>
                                        <div class="text-muted small"><?= h($r['phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= number_format((float)($r['total_amount'] ?? 0), 2) ?></td>
                                <td class="text-end"><?= number_format((float)($r['pay_amount'] ?? 0), 2) ?></td>
                                <td class="text-end"><?= number_format((float)($r['due_amount'] ?? 0), 2) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $badge ?>"><?= h($st) ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if (file_exists('invoice_view.php')): ?>
                                        <a class="btn btn-primary btn-sm" href="invoice_view.php?id=<?= (int)$r['id'] ?>">View</a>
                                    <?php endif; ?>
                                    <?php if (file_exists('invoice_print.php')): ?>
                                        <a class="btn btn-outline-secondary btn-sm" href="invoice_print.php?id=<?= (int)$r['id'] ?>">Print</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php
if (file_exists('../layout/layout_footer.php')) {
    require '../layout/layout_footer.php';
}
?>
