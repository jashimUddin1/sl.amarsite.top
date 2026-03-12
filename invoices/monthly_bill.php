<?php
require_once '../auth/config.php';
require_login();

// ====== Layout variables ======
$pageTitle   = 'Schools - Bill List';
$pageHeading = 'School List';
$activeMenu  = 'invoices';

require '../layout/layout_header.php';

$selectedYear = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
if ($selectedYear < 2000 || $selectedYear > 2100) {
    $selectedYear = (int) date('Y');
}

$months = [
    1  => 'Jan',
    2  => 'Feb',
    3  => 'Mar',
    4  => 'Apr',
    5  => 'May',
    6  => 'Jun',
    7  => 'Jul',
    8  => 'Aug',
    9  => 'Sep',
    10 => 'Oct',
    11 => 'Nov',
    12 => 'Dec',
];

// =========================
// helper
// =========================
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalizeInvoiceStatus($status, $due = 0, $paidAt = null): string
{
    $status = strtoupper(trim((string)$status));
    $due = (float)$due;

    if ($due <= 0) {
        return 'Paid';
    }

    if (in_array($status, ['PARTIAL', 'PARTIALLY PAID', 'PARTIAL PAID'])) {
        return 'Partial';
    }

    return 'Unpaid';
}

// =========================
// get approved schools
// =========================
$approvedStmt = $pdo->prepare("
    SELECT id, school_name, district, upazila, m_fee
    FROM schools
    WHERE status = 'Approved'
    ORDER BY id ASC
");
$approvedStmt->execute();
$schoolRows = $approvedStmt->fetchAll(PDO::FETCH_ASSOC);

// school id wise map
$schools = [];
foreach ($schoolRows as $school) {
    $schools[(int)$school['id']] = $school;
}

// =========================
// get invoices
// =========================
$invoiceStmt = $pdo->prepare("
    SELECT id, in_no, school_id, data, paid_at
    FROM invoices
    ORDER BY id DESC
");
$invoiceStmt->execute();
$invoiceRows = $invoiceStmt->fetchAll(PDO::FETCH_ASSOC);

// =========================
// build invoice map
// school_id => month => invoice info
// only schools with invoice of selected year
// =========================
$invoiceMap = [];
$schoolHasInvoice = [];

foreach ($invoiceRows as $row) {
    $json = json_decode($row['data'], true);

    if (!is_array($json) || empty($json['invoiceDate'])) {
        continue;
    }

    $time = strtotime($json['invoiceDate']);
    if (!$time) {
        continue;
    }

    $year  = (int) date('Y', $time);
    $month = (int) date('n', $time);

    if ($year !== $selectedYear) {
        continue;
    }

    $schoolId = (int) $row['school_id'];

    if (!isset($schools[$schoolId])) {
        continue;
    }

    $due = (float)($json['totals']['due'] ?? 0);

    $status = normalizeInvoiceStatus(
        $json['totals']['status'] ?? 'UNPAID',
        $due,
        $row['paid_at'] ?? null
    );

    // same month এ একাধিক invoice থাকলে latest row রাখবে
    if (!isset($invoiceMap[$schoolId][$month])) {
        $invoiceMap[$schoolId][$month] = [
            'invoice_id' => (int)$row['id'],
            'status'     => $status,
        ];
    }

    $schoolHasInvoice[$schoolId] = true;
}

// =========================
// year list for dropdown
// =========================
$currentYear = (int)date('Y');
$startYear = $currentYear - 5;
$endYear   = $currentYear + 2;
?>

<style>
    .top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    table {
        border-collapse: collapse;
        width: 100%;
    }

    th,
    td {
        border: 1px solid #ccc;
        padding: 6px;
        text-align: center;
    }

    th {
        background: #f5f5f5;
    }

    .school-cell {
        text-align: left;
    }

    .badge {
        padding: 3px 6px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        display: inline-block;
    }

    .badge-paid {
        background: #d4edda;
        color: #155724;
    }

    .badge-unpaid {
        background: #f8d7da;
        color: #721c24;
    }

    .badge-partial {
        background: #fff3cd;
        color: #856404;
    }

    .badge-nobill {
        background: #e2e3e5;
        color: #383d41;
    }

    .year-form {
        margin: 0;
    }

    .year-form select {
        padding: 4px 8px;
    }

    .fee-badge {
        display: inline-block;
        margin-top: 3px;
        padding: 2px 6px;
        font-size: 12px;
        background: #eef2ff;
        color: #1e3a8a;
        border-radius: 4px;
        font-weight: 600;
    }
</style>

<div>
    <div class="top-bar">
        <div>
            <strong>Year: <?php echo e($selectedYear); ?></strong>
        </div>

        <div>
            <form method="GET" class="year-form">
                <select name="year" onchange="this.form.submit()">
                    <?php for ($year = $endYear; $year >= $startYear; $year--): ?>
                        <option value="<?php echo $year; ?>" <?php echo ($selectedYear === $year) ? 'selected' : ''; ?>>
                            <?php echo $year; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>SL</th>
                <th>School Name</th>

                <?php foreach ($months as $monthName): ?>
                    <th><?php echo e($monthName); ?></th>
                <?php endforeach; ?>

                <th>Paid</th>
                <th>Unpaid</th>
            </tr>
        </thead>

        <tbody>
            <?php if (!empty($schoolHasInvoice)): ?>
                <?php
                $sl = 1;
                foreach ($schoolHasInvoice as $schoolId => $hasInvoice):
                    $school = $schools[$schoolId];
                    $paidCount = 0;
                    $unpaidCount = 0;
                ?>
                    <tr>
                        <td><?php echo $sl++; ?></td>

                        <td class="school-cell">
                            <h4 class="fw-bold">
                                <?php echo e($school['school_name']); ?>
                            </h4>
                            <span class="text-secondary fw-bold">
                                <?php echo e($school['district']); ?>, <?php echo e($school['upazila']); ?>
                            </span>
                            <br>
                            <span class="fw-bold fs-6 text-primary">
                               Fee <?php echo number_format($school['m_fee']); ?>৳
                            </span>
                        </td>

                        <?php foreach ($months as $monthNo => $monthName): ?>
                            <td>
                                <?php
                                if (isset($invoiceMap[$schoolId][$monthNo])) {
                                    $status = $invoiceMap[$schoolId][$monthNo]['status'];
                                    $invoiceId = $invoiceMap[$schoolId][$monthNo]['invoice_id'];

                                    if ($status === 'Paid') {
                                        echo '<a href="../invoices/invoice_edit.php?invoice_id=' . $invoiceId . '">
                                                <span class="badge badge-paid">Paid</span>
                                             </a>';
                                        $paidCount++;
                                    } elseif ($status === 'Partial') {
                                        echo '<a href="../invoices/invoice_edit.php?invoice_id=' . $invoiceId . '">
                                                <span class="badge badge-partial">Partial</span>
                                              </a>';
                                    } else {
                                        echo '<a href="../invoices/invoice_edit.php?invoice_id=' . $invoiceId . '">
                                                <span class="badge badge-unpaid">Unpaid</span>
                                              </a>';
                                        $unpaidCount++;
                                    }
                                } else {
                                    echo '<span class="badge badge-nobill">No Bill</span>';
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>

                        <td><?php echo $paidCount; ?></td>
                        <td><?php echo $unpaidCount; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="16">No invoice found for <?php echo e($selectedYear); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require '../layout/layout_footer.php'; ?>