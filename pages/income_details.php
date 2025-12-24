<?php
require_once '../auth/config.php';
require_login();

$range = $_GET['range'] ?? 'this_month';
$allowedRanges = ['today', 'this_month', 'this_year', 'last_year', 'lifetime', 'custom'];
if (!in_array($range, $allowedRanges, true))
    $range = 'this_month';

$type = $_GET['type'] ?? 'collected';
$allowedTypes = ['income', 'collected', 'due'];
if (!in_array($type, $allowedTypes, true))
    $type = 'collected';

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$isValidDate = function (string $d): bool {
    return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
};

$selected = match ($range) {
    'today' => 'Today',
    'this_month' => 'This Month',
    'this_year' => 'This Year',
    'last_year' => 'Last Year',
    'lifetime' => 'Life Time',
    'custom' => ($from && $to ? ($from . ' to ' . $to) : 'Custom'),
    default => 'This Month',
};

// WHERE based on JSON invoiceDate
$where = "1=1"; // lifetime
if ($range === 'today') {
    $where = "STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(i.data, '$.invoiceDate')), '%Y-%m-%d') = CURDATE()";
} elseif ($range === 'this_month') {
    $where = "DATE_FORMAT(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(i.data, '$.invoiceDate')), '%Y-%m-%d'), '%Y-%m')
              = DATE_FORMAT(CURDATE(), '%Y-%m')";
} elseif ($range === 'this_year') {
    $where = "YEAR(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(i.data, '$.invoiceDate')), '%Y-%m-%d')) = YEAR(CURDATE())";
} elseif ($range === 'last_year') {
    $where = "YEAR(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(i.data, '$.invoiceDate')), '%Y-%m-%d')) = YEAR(CURDATE()) - 1";
} elseif ($range === 'custom') {
    if ($isValidDate($from) && $isValidDate($to)) {
        $where = "STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(i.data, '$.invoiceDate')), '%Y-%m-%d')
                  BETWEEN :from AND :to";
        $selected = $from . ' to ' . $to;
    } else {
        $range = 'this_month';
        $selected = 'This Month';
        $where = "DATE_FORMAT(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(i.data, '$.invoiceDate')), '%Y-%m-%d'), '%Y-%m')
                  = DATE_FORMAT(CURDATE(), '%Y-%m')";
    }
}

// Type wise condition
$typeWhere = "1=1";
if ($type === 'collected') {
    $typeWhere = "CAST(JSON_UNQUOTE(JSON_EXTRACT(i.data, '$.totals.pay')) AS DECIMAL(12,2)) > 0";
} elseif ($type === 'due') {
    $typeWhere = "CAST(JSON_UNQUOTE(JSON_EXTRACT(i.data, '$.totals.due')) AS DECIMAL(12,2)) > 0";
}

$titleShortMap = [
    'income' => 'Income Details',
    'collected' => 'Collected Details',
    'due' => 'Due Details',
];

$cardTitleMap = [
    'income' => 'Total Income Details',
    'collected' => 'Total Collected Details',
    'due' => 'Total Due Details',
];

$pageTitle = $titleShortMap[$type] . ' - School List';
$pageHeading = $titleShortMap[$type];  // শুধু income/collected/due Details
$activeMenu = 'dashboard';


require '../layout/layout_header.php';

// Fetch rows
$sql = "
SELECT
  i.id,
  i.school_id,
  s.school_name,
  JSON_UNQUOTE(JSON_EXTRACT(i.data, '$.invoiceNumber')) AS invoice_no,
  JSON_UNQUOTE(JSON_EXTRACT(i.data, '$.invoiceDate'))   AS invoice_date,
  CAST(JSON_UNQUOTE(JSON_EXTRACT(i.data, '$.totals.total')) AS DECIMAL(12,2)) AS total_amount,
  CAST(JSON_UNQUOTE(JSON_EXTRACT(i.data, '$.totals.pay'))   AS DECIMAL(12,2)) AS paid_amount,
  CAST(JSON_UNQUOTE(JSON_EXTRACT(i.data, '$.totals.due'))   AS DECIMAL(12,2)) AS due_amount,
  JSON_UNQUOTE(JSON_EXTRACT(i.data, '$.totals.status'))     AS pay_status
FROM invoices i
LEFT JOIN schools s ON s.id = i.school_id
WHERE $where AND $typeWhere
ORDER BY i.id DESC
";

$stmt = $pdo->prepare($sql);
if ($range === 'custom' && strpos($where, ':from') !== false) {
    $stmt->bindValue(':from', $from);
    $stmt->bindValue(':to', $to);
}
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary total
$sum = 0.0;
foreach ($rows as $r) {
    if ($type === 'due')
        $sum += (float) $r['due_amount'];
    elseif ($type === 'income')
        $sum += (float) $r['total_amount'];
    else
        $sum += (float) $r['paid_amount'];
}

$summaryLabel = $summaryLabelMap[$type] ?? 'Summary';
?>

<div class="bg-white rounded-xl shadow p-4">

    <div class="flex items-center justify-between mb-2">

        <h2 class="text-sm md:text-base lg:text-lg font-semibold text-slate-800">
            <?= htmlspecialchars($cardTitleMap[$type] ?? 'Details') ?>
            <span class="text-slate-500 text-xs md:text-sm">
                (<?= htmlspecialchars($selected) ?>)
            </span>
        </h2>



        <div class="flex items-center gap-2">
            <span class="text-sm md:text-base lg:text-lg font-semibold text-slate-800 me-2">
                ৳ <?= number_format($sum, 0) ?>
            </span>

            <a href="/school_list/pages/dashboard.php?range=<?= urlencode($range) ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>"
                class="btn btn-outline-dark btn-sm" title="Back to Dashboard">
                Back
            </a>
        </div>
    </div>



    <?php if (!$rows): ?>
        <p class="text-[13px] text-slate-500">এই ফিল্টারে কোনো ইনভয়েস পাওয়া যায়নি।</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-left">

                        <th class="p-2 border">Invoice</th>
                        <th class="p-2 border">School</th>
                        <th class="p-2 border">Date</th>
                        <th class="p-2 border">Total</th>
                        <th class="p-2 border">Paid</th>
                        <th class="p-2 border">Due</th>
                        <th class="p-2 border">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr class="hover:bg-slate-50">

                            <td class="p-2 border">#<?= htmlspecialchars((string) $r['invoice_no']) ?></td>
                            <td class="p-2 border"><?= htmlspecialchars($r['school_name'] ?? ('School #' . $r['school_id'])) ?>
                            </td>
                            <td class="p-2 border"><?= htmlspecialchars((string) $r['invoice_date']) ?></td>
                            <td class="p-2 border">৳ <?= number_format((float) $r['total_amount'], 0) ?></td>
                            <td class="p-2 border">৳ <?= number_format((float) $r['paid_amount'], 0) ?></td>
                            <td class="p-2 border">৳ <?= number_format((float) $r['due_amount'], 0) ?></td>
                            <td class="p-2 border"><?= htmlspecialchars((string) $r['pay_status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require '../layout/layout_footer.php'; ?>