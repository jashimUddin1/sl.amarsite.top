<?php
require_once '../auth/config.php';
require_login();

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

$monthMap = [
    'jan' => 1,
    'feb' => 2,
    'mar' => 3,
    'apr' => 4,
    'may' => 5,
    'jun' => 6,
    'jul' => 7,
    'aug' => 8,
    'sep' => 9,
    'oct' => 10,
    'nov' => 11,
    'dec' => 12
];

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalizeInvoiceStatus($status, $due = 0): string
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

/**
 * Extract billing month and year from item description.
 *
 * Supported examples:
 * - Software subscription fee (Dec 2025)
 * - Software subscription fee (Jan 2026)
 * - Software subscription fee (Jan)
 * - Software subscription fee (Feb)
 *
 * Returns:
 * [
 *   'month' => int|null,
 *   'year'  => int|null
 * ]
 */
function extractMonthYearFromDesc($desc, array $monthMap, int $defaultYear): array
{
    $result = [
        'month' => null,
        'year'  => null,
    ];

    $desc = trim((string)$desc);
    if ($desc === '') {
        return $result;
    }

    if (!preg_match('/\((.*?)\)/', $desc, $match)) {
        return $result;
    }

    $inside = trim($match[1]);
    if ($inside === '') {
        return $result;
    }

    // Normalize spaces
    $inside = preg_replace('/\s+/', ' ', $inside);

    /**
     * Match:
     * Dec
     * Dec 2025
     * January
     * January 2026
     */
    if (preg_match('/^([A-Za-z]+)(?:\s+(\d{4}))?$/', $inside, $parts)) {
        $monthText = strtolower(substr($parts[1], 0, 3));

        if (isset($monthMap[$monthText])) {
            $result['month'] = $monthMap[$monthText];
            $result['year']  = isset($parts[2]) ? (int)$parts[2] : $defaultYear;
        }
    }

    return $result;
}

// Get all approved schools only
$approvedStmt = $pdo->prepare("
    SELECT id, school_name, district, upazila, m_fee
    FROM schools
    WHERE status = 'Approved'
    ORDER BY id ASC
");
$approvedStmt->execute();
$schoolRows = $approvedStmt->fetchAll(PDO::FETCH_ASSOC);

$schools = [];
foreach ($schoolRows as $school) {
    $schools[(int)$school['id']] = $school;
}

// Get invoices
$invoiceStmt = $pdo->prepare("
    SELECT id, in_no, school_id, data, paid_at
    FROM invoices
    ORDER BY id DESC
");
$invoiceStmt->execute();
$invoiceRows = $invoiceStmt->fetchAll(PDO::FETCH_ASSOC);

$invoiceMap = [];

foreach ($invoiceRows as $row) {
    $json = json_decode($row['data'], true);
    if (!$json) {
        continue;
    }

    if (empty($json['invoiceDate'])) {
        continue;
    }

    $time = strtotime($json['invoiceDate']);
    if (!$time) {
        continue;
    }

    $invoiceYear  = (int) date('Y', $time);
    $invoiceMonth = (int) date('n', $time);
    $schoolId     = (int) $row['school_id'];

    // Invoice school must exist in approved school list
    if (!isset($schools[$schoolId])) {
        continue;
    }

    $due = (float)($json['totals']['due'] ?? 0);
    $status = normalizeInvoiceStatus(
        $json['totals']['status'] ?? 'UNPAID',
        $due
    );

    if (empty($json['items']) || !is_array($json['items'])) {
        continue;
    }

    foreach ($json['items'] as $item) {
        $desc = strtolower(trim($item['desc'] ?? ''));

        if (
            strpos($desc, 'subscription') === false &&
            strpos($desc, 'software subscription fee') === false
        ) {
            continue;
        }

        $parsed = extractMonthYearFromDesc($item['desc'] ?? '', $monthMap, $invoiceYear);

        $targetMonth = $parsed['month'];
        $targetYear  = $parsed['year'];

        // Fallback: if no month found in desc, use invoice date month/year
        if (!$targetMonth) {
            $targetMonth = $invoiceMonth;
        }

        if (!$targetYear) {
            $targetYear = $invoiceYear;
        }

        // Only keep entries that belong to selected year
        if ($targetYear !== $selectedYear) {
            continue;
        }

        // Keep first matched invoice for a school-month
        if (!isset($invoiceMap[$schoolId][$targetMonth])) {
            $invoiceMap[$schoolId][$targetMonth] = [
                'invoice_id' => (int)$row['id'],
                'status'     => $status
            ];
        }
    }
}

$currentYear = (int) date('Y');
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
        text-decoration: none;
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

    .invoice-link {
        text-decoration: none;
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
            <?php if (!empty($schools)): ?>
                <?php
                $sl = 1;
                foreach ($schools as $schoolId => $school):
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
                                Fee <?php echo number_format((float)$school['m_fee']); ?>৳
                            </span>
                        </td>

                        <?php foreach ($months as $monthNo => $monthName): ?>
                            <td>
                                <?php
                                if (isset($invoiceMap[$schoolId][$monthNo])) {
                                    $status = $invoiceMap[$schoolId][$monthNo]['status'];
                                    $invoiceId = (int)$invoiceMap[$schoolId][$monthNo]['invoice_id'];

                                    if ($status === 'Paid') {
                                        echo '<a class="invoice-link" href="../invoices/invoice_edit.php?invoice_id=' . $invoiceId . '">
                                                <span class="badge badge-paid">Paid</span>
                                              </a>';
                                        $paidCount++;
                                    } elseif ($status === 'Partial') {
                                        echo '<a class="invoice-link" href="../invoices/invoice_edit.php?invoice_id=' . $invoiceId . '">
                                                <span class="badge badge-partial">Partial</span>
                                              </a>';
                                    } else {
                                        echo '<a class="invoice-link" href="../invoices/invoice_edit.php?invoice_id=' . $invoiceId . '">
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
                    <td colspan="16">No approved school found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require '../layout/layout_footer.php'; ?>