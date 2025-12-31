<?php //accounts/index.php
require_once '../auth/config.php';
require_login();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}


/* -------------------- UI Prefs (per-user, DB persisted) -------------------- */
function current_user_id(): int
{
    // Try common session shapes from auth/config.php
    if (!empty($_SESSION['user_id']))
        return (int) $_SESSION['user_id'];
    if (!empty($_SESSION['user']['id']))
        return (int) $_SESSION['user']['id'];
    if (!empty($_SESSION['auth']['id']))
        return (int) $_SESSION['auth']['id'];
    if (!empty($_SESSION['id']))
        return (int) $_SESSION['id'];
    return 0;
}

function get_ui_prefs(PDO $pdo, int $userId): array
{
    $defaults = [
        'show_insert' => 1,
        'show_view' => 1,
        'show_sheet' => 1,
    ];
    if ($userId <= 0)
        return $defaults;

    // Prefer users.ui_prefs JSON/TEXT if available (user requested "users table")
    try {
        $col = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'ui_prefs'");
        $col->execute();
        if ($col->fetch()) {
            $st = $pdo->prepare("SELECT ui_prefs FROM users WHERE id = :id LIMIT 1");
            $st->execute([':id' => $userId]);
            $raw = $st->fetchColumn();
            if ($raw) {
                $arr = json_decode((string) $raw, true);
                if (is_array($arr)) {
                    return array_merge($defaults, array_intersect_key($arr, $defaults));
                }
            }
            return $defaults;
        }
    } catch (Throwable $e) {
        // fall through
    }

    // Fallback table if users.ui_prefs doesn't exist
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_ui_prefs (
            user_id INT PRIMARY KEY,
            prefs JSON NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        $st = $pdo->prepare("SELECT prefs FROM user_ui_prefs WHERE user_id = :id LIMIT 1");
        $st->execute([':id' => $userId]);
        $raw = $st->fetchColumn();
        if ($raw) {
            $arr = json_decode((string) $raw, true);
            if (is_array($arr)) {
                return array_merge($defaults, array_intersect_key($arr, $defaults));
            }
        }
    } catch (Throwable $e) {
        // ignore and return defaults
    }

    return $defaults;
}

function save_ui_prefs(PDO $pdo, int $userId, array $prefs): bool
{
    if ($userId <= 0)
        return false;

    $allowed = ['show_insert' => 1, 'show_view' => 1, 'show_sheet' => 1];
    $clean = [];
    foreach ($allowed as $k => $_) {
        $clean[$k] = !empty($prefs[$k]) ? 1 : 0;
    }
    $json = json_encode($clean, JSON_UNESCAPED_UNICODE);

    // Prefer users.ui_prefs
    try {
        $col = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'ui_prefs'");
        $col->execute();
        if ($col->fetch()) {
            $st = $pdo->prepare("UPDATE users SET ui_prefs = :prefs WHERE id = :id");
            return $st->execute([':prefs' => $json, ':id' => $userId]);
        }
    } catch (Throwable $e) {
        // fall through
    }

    // Fallback table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_ui_prefs (
            user_id INT PRIMARY KEY,
            prefs JSON NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        $st = $pdo->prepare("INSERT INTO user_ui_prefs (user_id, prefs) VALUES (:id, :prefs)
                             ON DUPLICATE KEY UPDATE prefs = VALUES(prefs)");
        return $st->execute([':id' => $userId, ':prefs' => $json]);
    } catch (Throwable $e) {
        return false;
    }
}

// AJAX endpoint (no URL parameters, no PHP session flags—DB only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_ui_prefs') {
    header('Content-Type: application/json; charset=utf-8');

    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'CSRF failed']);
        exit;
    }

    $uid = current_user_id();
    $prefs = [
        'show_insert' => (int) ($_POST['show_insert'] ?? 0),
        'show_view' => (int) ($_POST['show_view'] ?? 0),
        'show_sheet' => (int) ($_POST['show_sheet'] ?? 0),
    ];

    $ok = save_ui_prefs($pdo, $uid, $prefs);
    echo json_encode(['ok' => (bool) $ok, 'prefs' => $prefs]);
    exit;
}

function valid_date_ymd($s): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $s);
    return $dt && $dt->format('Y-m-d') === $s;
}

/**
 * Build a WHERE + params for a given date expression.
 * $dateExpr examples:
 *   - accounts: `date`
 *   - invoices: CAST(JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.invoiceDate')) AS DATE)
 */
function build_range_where(string $range, string $from, string $to, string $today, string $dateExpr): array
{
    $where = "1=1";
    $params = [];

    if ($range === 'today') {
        $where .= " AND $dateExpr = :d";
        $params[':d'] = $today;

    } elseif ($range === 'this_month') {
        $start = (new DateTime('first day of this month'))->format('Y-m-d');
        $end = (new DateTime('last day of this month'))->format('Y-m-d');
        $where .= " AND $dateExpr BETWEEN :from AND :to";
        $params[':from'] = $start;
        $params[':to'] = $end;

    } elseif ($range === 'this_year') {
        $y = date('Y');
        $start = (new DateTime("first day of January $y"))->format('Y-m-d');
        $end = (new DateTime("last day of December $y"))->format('Y-m-d');
        $where .= " AND $dateExpr BETWEEN :from AND :to";
        $params[':from'] = $start;
        $params[':to'] = $end;

    } elseif ($range === 'last_year') {
        $y = date('Y') - 1;
        $start = (new DateTime("first day of January $y"))->format('Y-m-d');
        $end = (new DateTime("last day of December $y"))->format('Y-m-d');
        $where .= " AND $dateExpr BETWEEN :from AND :to";
        $params[':from'] = $start;
        $params[':to'] = $end;

    } elseif ($range === 'custom') {
        if (valid_date_ymd($from) && valid_date_ymd($to)) {
            if ($from > $to) {
                $tmp = $from;
                $from = $to;
                $to = $tmp;
            }
            $where .= " AND $dateExpr BETWEEN :from AND :to";
            $params[':from'] = $from;
            $params[':to'] = $to;
        } else {
            // invalid -> fallback this month
            $start = (new DateTime('first day of this month'))->format('Y-m-d');
            $end = (new DateTime('last day of this month'))->format('Y-m-d');
            $where .= " AND $dateExpr BETWEEN :from AND :to";
            $params[':from'] = $start;
            $params[':to'] = $end;
        }

    } elseif ($range === 'lifetime') {
        // no filter
    } else {
        // unknown -> fallback this month
        $start = (new DateTime('first day of this month'))->format('Y-m-d');
        $end = (new DateTime('last day of this month'))->format('Y-m-d');
        $where .= " AND $dateExpr BETWEEN :from AND :to";
        $params[':from'] = $start;
        $params[':to'] = $end;
    }

    return [$where, $params];
}

/* -------------------- Filter inputs -------------------- */
// Default range: this month
$range = $_GET['range'] ?? 'this_month';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$sheet = $_GET['sheet'] ?? 'all';
if (!in_array($sheet, ['all', 'income', 'expense'], true)) {
    $sheet = 'all';
}

$today = (new DateTime('today'))->format('Y-m-d');

/* -------------------- WHERE for accounts + invoices -------------------- */
[$whereAcc, $paramsAcc] = build_range_where($range, $from, $to, $today, "`date`");

// ✅ sheet filter for accounts
if ($sheet === 'income') {
    $whereAcc .= " AND type='income'";
} elseif ($sheet === 'expense') {
    $whereAcc .= " AND type='expense'";
}

$invDateExpr = "CAST(JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.invoiceDate')) AS DATE)";
[$whereInv, $paramsInv] = build_range_where($range, $from, $to, $today, $invDateExpr);

/* -------------------- Normalize invalid custom -> show this month in UI too -------------------- */
if ($range === 'custom' && !(valid_date_ymd($from) && valid_date_ymd($to))) {
    $range = 'this_month';
    $from = $to = '';
}

/* -------------------- Selected label text -------------------- */
$selected = 'Today';
if ($range === 'today') {
    $selected = 'Today';
} elseif ($range === 'this_month') {
    $selected = 'This Month';
} elseif ($range === 'this_year') {
    $selected = 'This Year';
} elseif ($range === 'last_year') {
    $selected = 'Last Year';
} elseif ($range === 'lifetime') {
    $selected = 'Life Time';
} elseif ($range === 'custom') {
    $selected = ($from && $to) ? ("Custom: $from → $to") : 'Custom';
}

/* -------------------- Combined rows: accounts + paid invoices -------------------- */

$includeInvoices = ($sheet !== 'expense');

$sql = "
    SELECT 
        'account' AS source,
        a.id AS row_id,
        a.`date` AS txn_date,
        a.description,
        a.amount,
        a.type,
        a.method,
        a.category,
        NULL AS in_no
    FROM accounts a
    WHERE $whereAcc
";

if ($includeInvoices) {
    $sql .= "
    UNION ALL

    SELECT
        'invoice' AS source,
        i.id AS row_id,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(i.`data`, '$.invoiceDate')) AS DATE) AS txn_date,
        CONCAT(
            'Invoice #', i.in_no,
            ' - ',
            COALESCE(JSON_UNQUOTE(JSON_EXTRACT(i.`data`, '$.billTo.school')), '')
        ) AS description,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(i.`data`, '$.totals.total')) AS DECIMAL(12,2)) AS amount,
        'income' AS type,
        'Invoice' AS method,
        'invoice' AS category,
        i.in_no AS in_no
    FROM invoices i
    WHERE 
        JSON_UNQUOTE(JSON_EXTRACT(i.`data`, '$.totals.status')) = 'PAID'
        AND $whereInv
    ";
}

$sql .= " ORDER BY txn_date DESC, source ASC, row_id DESC";

$execParams = $paramsAcc;
if ($includeInvoices) {
    $execParams = array_merge($execParams, $paramsInv);
}

$stmt = $pdo->prepare($sql);
$stmt->execute($execParams);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* -------------------- Running balance for table (in displayed order) -------------------- */
$runningBalance = 0.0;
foreach ($rows as &$r) {
    $amt = (float) ($r['amount'] ?? 0);
    if (($r['type'] ?? '') === 'income') {
        $runningBalance += $amt;
    } elseif (($r['type'] ?? '') === 'expense') {
        $runningBalance -= $amt;
    }
    $r['balance'] = $runningBalance;
}
unset($r);



/* -------------------- Summary cards -------------------- */

// -------------------- Summary for cards --------------------
$accIncome = 0.0;
$accExpense = 0.0;
$invIncome = 0.0;

if ($sheet !== 'expense') {
    // accounts income
    $accIncomeStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM accounts WHERE type='income' AND $whereAcc");
    $accIncomeStmt->execute($paramsAcc);
    $accIncome = (float) $accIncomeStmt->fetchColumn();
}

if ($sheet !== 'income') {
    // accounts expense
    $accExpenseStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM accounts WHERE type='expense' AND $whereAcc");
    $accExpenseStmt->execute($paramsAcc);
    $accExpense = (float) $accExpenseStmt->fetchColumn();
}

if ($includeInvoices) {
    // invoices paid total (income)
    $invIncomeStmt = $pdo->prepare("
        SELECT COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.totals.total')) AS DECIMAL(12,2))),0)
        FROM invoices
        WHERE JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.totals.status')) = 'PAID'
          AND $whereInv
    ");
    $invIncomeStmt->execute($paramsInv);
    $invIncome = (float) $invIncomeStmt->fetchColumn();
}

// records count (accounts + paid invoices)
$accCountStmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE $whereAcc");
$accCountStmt->execute($paramsAcc);
$accCount = (int) $accCountStmt->fetchColumn();

$invCount = 0;
if ($includeInvoices) {
    $invCountStmt = $pdo->prepare("
        SELECT COUNT(*) FROM invoices
        WHERE JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.totals.status')) = 'PAID'
          AND $whereInv
    ");
    $invCountStmt->execute($paramsInv);
    $invCount = (int) $invCountStmt->fetchColumn();
}

$totalIncome = $accIncome + $invIncome;
$totalExpense = $accExpense;
$recordsCount = $accCount + $invCount;
$balance = $totalIncome - $totalExpense;

// -------------------- Sheet links (preserve range) --------------------
$qsBase = ['range' => $range];
if ($range === 'custom') {
    $qsBase['from'] = $from;
    $qsBase['to'] = $to;
}

$allUrl = '?' . http_build_query(array_merge($qsBase, ['sheet' => 'all']));
$incomeUrl = '?' . http_build_query(array_merge($qsBase, ['sheet' => 'income']));
$expenseUrl = '?' . http_build_query(array_merge($qsBase, ['sheet' => 'expense']));

$pageTitle = 'Accounts - School Note Manager';
$pageHeading = 'Accounts';
$activeMenu = 'accounts';
require '../layout/layout_header_index.php';

/* -------------------- UI Prefs for rendering -------------------- */
$uiPrefs = get_ui_prefs($pdo, current_user_id());
$showInsert = (int) ($uiPrefs['show_insert'] ?? 1);
$showView = (int) ($uiPrefs['show_view'] ?? 1);
$showSheet = (int) ($uiPrefs['show_sheet'] ?? 1);
?>
<style>
    .app-wrap {
        max-width: 1200px;
        margin: 28px auto;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .06);
        padding: 26px 26px 22px;
    }

    .top-row .form-control,
    .top-row .form-select {
        height: 46px;
        border-radius: 10px;
    }

    .btn-add {
        height: 46px;
        border-radius: 10px;
        font-weight: 600;
    }

    .stat {
        border-radius: 10px;
        padding: 16px 18px;
        border: 1px solid rgba(0, 0, 0, .05);
        min-height: 92px;
    }

    .stat .label {
        font-size: 15px;
        color: #111;
    }

    .stat .value {
        font-size: 30px;
        font-weight: 800;
        margin-top: 6px;
        line-height: 1;
    }

    .bg-soft-green {
        background: #dff8e8;
    }

    .bg-soft-red {
        background: #fbe1e1;
    }

    .bg-soft-blue {
        background: #dbe9ff;
    }

    .bg-soft-purple {
        background: #efe5ff;
    }

    table thead th {
        background: #e9ecef !important;
        font-weight: 700;
        border-bottom: 0;
        position: sticky;
        top: 0;
        z-index: 2;
    }

    /* Only table scroll */
    .table-wrap {
        border-radius: 10px;
        overflow: auto;
        border: 1px solid rgba(0, 0, 0, .06);
        max-height: 420px;
    }

    @media (max-width: 767.98px) {
        .mobile-sm-text {
            font-size: 11px;
        }
    }
</style>

<div class="app-wrap position-relative">

    <button type="button" class="btn btn-light btn-sm shadow-sm ui-settings-btn px-md-2 py-2 py-md-1"
        data-bs-toggle="modal" data-bs-target="#uiSettingsModal" title="Settings"
        style="position:absolute; top: 0px; right: 0px; z-index:5; font-size: 11px; background-color: #b8b8b8; font-weight: bold;">

        <!-- Desktop & Tablet (md and up): ⋯ -->
        <i class="bi bi-three-dots d-none d-md-inline"></i>

        <!-- Mobile only: ⋮ -->
        <i class="bi bi-three-dots-vertical d-inline d-md-none"></i>

    </button>


    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Header + Filter -->
    <div
        class="d-flex flex-column flex-md-row align-items-center align-items-md-start justify-content-between gap-2 mb-3">
        <h2 class="fs-2 m-0 text-center text-md-start">
            <span class="text-success">Income</span>
            &
            <span class="text-danger">Expense</span>
            <span class="text-primary">Tracker</span>
        </h2>

        <div class="d-flex flex-column flex-sm-row align-items-center gap-2">
            <div class="small text-muted text-center text-md-end">
                Income Summary
                <span class="text-secondary">(<?= htmlspecialchars($selected, ENT_QUOTES, 'UTF-8') ?>)</span>
            </div>


            <!-- Desktop & Tab (md and up) -->
             <?php if($showSheet == 1): ?>
            <div id="sheetSystemDesktop" class="me-2 d-none d-md-block <?= ($showSheet ? '' : 'd-none') ?>">
                <select class="form-select form-select-sm" onchange="if(this.value) window.location.href=this.value">

                    <option value="<?= htmlspecialchars($allUrl, ENT_QUOTES, 'UTF-8') ?>" <?= ($sheet === 'all') ? 'selected' : '' ?>>
                        All Sheet
                    </option>

                    <option value="<?= htmlspecialchars($incomeUrl, ENT_QUOTES, 'UTF-8') ?>" <?= ($sheet === 'income') ? 'selected' : '' ?>>
                        Income Sheet
                    </option>

                    <option value="<?= htmlspecialchars($expenseUrl, ENT_QUOTES, 'UTF-8') ?>" <?= ($sheet === 'expense') ? 'selected' : '' ?>>
                        Expense Sheet
                    </option>
                </select>
            </div>
            <?php endif ?>

            <!-- Mobile only (below md) -->
            <div id="sheetSystemMobile"
                class="btn-group me-2 d-inline-flex d-md-none <?= ($showSheet ? '' : 'd-none') ?>" role="group"
                aria-label="Sheets">

                <a href="<?= htmlspecialchars($allUrl, ENT_QUOTES, 'UTF-8') ?>"
                    class="btn btn-sm <?= ($sheet === 'all') ? 'btn-dark' : 'btn-outline-dark' ?>">All</a>

                <a href="<?= htmlspecialchars($incomeUrl, ENT_QUOTES, 'UTF-8') ?>"
                    class="btn btn-sm <?= ($sheet === 'income') ? 'btn-success' : 'btn-outline-success' ?>">Income</a>

                <a href="<?= htmlspecialchars($expenseUrl, ENT_QUOTES, 'UTF-8') ?>"
                    class="btn btn-sm <?= ($sheet === 'expense') ? 'btn-danger' : 'btn-outline-danger' ?>">Expense</a>
            </div>



            <form method="get" class="d-flex align-items-center gap-2 flex-nowrap" id="rangeForm"
                title="Select range to filter income summary">
                <input type="hidden" name="sheet" value="<?= htmlspecialchars($sheet, ENT_QUOTES, 'UTF-8') ?>">
                <select name="range" id="rangeSelect" class="form-select form-select-sm" style="width:auto;"
                    title="Range">
                    <option value="today" <?= ($range === 'today') ? 'selected' : '' ?>>Today</option>
                    <option value="this_month" <?= ($range === 'this_month') ? 'selected' : '' ?>>This Month</option>
                    <option value="this_year" <?= ($range === 'this_year') ? 'selected' : '' ?>>This Year</option>
                    <option value="last_year" <?= ($range === 'last_year') ? 'selected' : '' ?>>Last Year</option>
                    <option value="lifetime" <?= ($range === 'lifetime') ? 'selected' : '' ?>>Life Time</option>
                    <option value="custom" <?= ($range === 'custom') ? 'selected' : '' ?>>Custom</option>
                </select>

                <!-- Custom only -->
                <div id="customFields" class="d-none align-items-center gap-2">
                    <input type="date" name="from"
                        value="<?= htmlspecialchars($range === 'custom' ? $from : '', ENT_QUOTES, 'UTF-8') ?>"
                        class="form-control form-control-sm" title="From (YYYY-MM-DD)">
                    <input type="date" name="to"
                        value="<?= htmlspecialchars($range === 'custom' ? $to : '', ENT_QUOTES, 'UTF-8') ?>"
                        class="form-control form-control-sm" title="To (YYYY-MM-DD)">
                </div>

                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const sel = document.getElementById('rangeSelect');
            const custom = document.getElementById('customFields');

            function toggleCustom() {
                if (!sel || !custom) return;

                if (sel.value === 'custom') {
                    custom.classList.remove('d-none');
                    custom.classList.add('d-flex');
                } else {
                    custom.classList.add('d-none');
                    custom.classList.remove('d-flex');
                }
            }

            if (sel) {
                sel.addEventListener('change', toggleCustom);
                toggleCustom();
            }
        })();
    </script>

    <!-- Dashboard cards -->
    <div class="row g-3 my-3">
        <div class="col-6 col-md-3">
            <div class="stat bg-soft-green">
                <div class="label"><span class="d-none d-sm-inline">Total </span>Income</div>
                <div class="value text-success"><?= number_format($totalIncome, 0) ?></div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat bg-soft-red">
                <div class="label"><span class="d-none d-sm-inline">Total </span>Expense</div>
                <div class="value text-danger"><?= number_format($totalExpense, 0) ?></div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat bg-soft-blue">
                <div class="label">Balance</div>
                <div class="value text-primary"><?= number_format($balance, 0) ?></div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat bg-soft-purple">
                <div class="label">Records</div>
                <div class="value text-dark"><?= number_format($recordsCount, 0) ?></div>
            </div>
        </div>
    </div>

    <!-- Add entry desktop/tab -->
    <div class="d-none d-md-inline">
        <form action="core/add_core.php" method="post"
            class="row g-3 align-items-center top-row <?= ($showInsert ? '' : 'd-none') ?>">
            <input type="hidden" name="action" value="insert_add">

            <div class="col-12 col-md-2">
                <input type="date" class="form-control" id="date" name="date" required>
            </div>

            <div class="col-12 col-md-4">
                <input type="text" class="form-control" id="desc" name="description" placeholder="Description"
                    maxlength="255" required>
            </div>

            <div class="col-12 col-md-2">
                <input type="number" class="form-control" id="amount" name="amount" placeholder="Amount" min="0"
                    step="0.01" required>
            </div>

            <div class="col-6 col-md-1">
                <select class="form-select" name="type" required>
                    <option value="expense">Expense</option>
                    <option value="income">Income</option>
                </select>
            </div>

            <div class="col-6 col-md-1">
                <select class="form-select" name="payment_method" id="payment_method" required>
                    <option value="Cash">Cash</option>
                    <option value="bKash">bKash</option>
                    <option value="Nagad">Nagad</option>
                    <option value="Bank">Bank</option>
                    <option value="Card">Card</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="col-12 col-md-1">
                <select class="form-select" name="category" id="category" required>
                    <option value="" selected disabled>Select Category</option>
                    <option value="Buy">Buy</option>
                    <option value="Marketing Cost">Marketing Cost</option>
                    <option value="Office Supply">Office Supply</option>
                    <option value="Repair">Repair</option>
                    <option value="Transport">Transport</option>
                    <option value="Rent">Rent</option>
                    <option value="Utilities">Utilities</option>
                    <option value="Revenue">Revenue</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="col-12 col-md-1 d-grid">
                <button type="submit" class="btn btn-success btn-add" id="addBtn">Add</button>
            </div>
        </form>
    </div>

    <!-- Add entry mobile only -->
    <div class="d-inline d-md-none <?= ($showInsert ? '' : 'd-none') ?>">
        <form action="core/add_core.php" method="post"
            class="row g-3 align-items-center top-row <?= ($showInsert ? '' : 'd-none') ?>">
            <input type="hidden" name="action" value="insert_add">

            <div class="col-6 col-md-2">
                <input type="date" class="form-control" id="date" name="date" required>
            </div>

            <div class="col-6 col-md-1">
                <select class="form-select" name="type" required>
                    <option value="expense">Expense</option>
                    <option value="income">Income</option>
                </select>
            </div>

            <div class="col-12 col-md-4">
                <input type="text" class="form-control" id="desc" name="description" placeholder="Description"
                    maxlength="255" required>
            </div>

            <div class="col-4 col-md-2">
                <input type="number" class="form-control" id="amount" name="amount" placeholder="Amount" min="0"
                    step="0.01" required>
            </div>



            <div class="col-4 col-md-1">
                <select class="form-select" name="payment_method" id="payment_method" required>
                    <option value="Cash">Cash</option>
                    <option value="bKash">bKash</option>
                    <option value="Nagad">Nagad</option>
                    <option value="Bank">Bank</option>
                    <option value="Card">Card</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="col-4 col-md-1">
                <select class="form-select" name="category" id="category" required>
                    <option value="" selected disabled>Select Category</option>
                    <option value="buy">buy</option>
                    <option value="marketing_cost">Marketing Cost</option>
                    <option value="office_supply">Office Supply</option>
                    <option value="cost2">cost2</option>
                    <option value="Transport">Transport</option>
                    <option value="Rent">Rent</option>
                    <option value="Utilities">Utilities</option>
                    <option value="revenue">Revenue</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="col-12 col-md-1 d-grid">
                <button type="submit" class="btn btn-success btn-add" id="addBtn">Add</button>
            </div>
        </form>
    </div>


    <!-- Table -->
    <div class="table-wrap mt-4">
        <table class="table table-bordered table-hover align-middle mb-0">
            <thead>
                <tr class="text-center">
                    <th style="width:120px;"> Date </th>
                    <th> Description </th>

                    <th>
                        <span class="d-none d-md-inline">Amount</span>
                        <span class="d-inline d-md-none">Tk</span>
                    </th>

                    <th>
                        <span class="d-none d-md-inline">Balance</span>
                        <span class="d-inline d-md-none">Bal</span>
                    </th>

                    <th>
                        <span class="d-none d-md-inline">Action</span>
                        <span class="d-inline d-md-none">Act</span>
                    </th>
                </tr>
            </thead>


            <tbody id="tbody">
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['txn_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="mobile-sm-text">
                                <?= htmlspecialchars($r['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>

                            </td>
                            <td class="text-end"><?= number_format((float) ($r['amount'] ?? 0), 0) ?></td>
                            <td
                                class="text-end fw-semibold <?= ((float) ($r['balance'] ?? 0) < 0) ? 'text-danger' : 'text-success' ?>">
                                <?= number_format((float) ($r['balance'] ?? 0), 0) ?>
                            </td>

                            <td class="text-center">

                                <?php if (($r['source'] ?? '') === 'account'): ?>

                                    <!-- Desktop / Tab -->
                                    <div class="d-none d-md-flex gap-2 justify-content-center">

                                        <!-- VIEW -->
                                        <button type="button"
                                            class="btn btn-outline-secondary btn-sm btn-view <?= ($showView ? '' : 'd-none') ?>"
                                            data-bs-toggle="modal" data-bs-target="#viewModal"
                                            data-row='<?= htmlspecialchars(json_encode($r), ENT_QUOTES, "UTF-8") ?>' title="View">
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <!-- EDIT -->
                                        <button type="button" class="btn btn-outline-primary btn-sm btn-edit" data-bs-toggle="modal"
                                            data-bs-target="#editModal" data-id="<?= (int) $r['row_id'] ?>"
                                            data-date="<?= htmlspecialchars($r['txn_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-description="<?= htmlspecialchars($r['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-amount="<?= htmlspecialchars($r['amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-type="<?= htmlspecialchars($r['type'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-method="<?= htmlspecialchars($r['method'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-category="<?= htmlspecialchars($r['category'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- DELETE -->
                                        <form action="core/delete_core.php" method="post"
                                            onsubmit="return confirm('Delete this record?')" class="m-0">
                                            <input type="hidden" name="csrf"
                                                value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id" value="<?= (int) $r['row_id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>

                                    </div>

                                    <!-- Mobile -->
                                    <div class="d-flex d-md-none flex-column gap-1 align-items-center">

                                        <!-- VIEW -->
                                        <button type="button" class="btn btn-outline-secondary btn-sm w-100 btn-view"
                                            data-bs-toggle="modal" data-bs-target="#viewModal"
                                            data-row='<?= htmlspecialchars(json_encode($r), ENT_QUOTES, "UTF-8") ?>'>
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <!-- EDIT -->
                                        <button type="button" class="btn btn-outline-primary btn-sm w-100 btn-edit"
                                            data-bs-toggle="modal" data-bs-target="#editModal" data-id="<?= (int) $r['row_id'] ?>"
                                            data-date="<?= htmlspecialchars($r['txn_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-description="<?= htmlspecialchars($r['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-amount="<?= htmlspecialchars($r['amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-type="<?= htmlspecialchars($r['type'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-method="<?= htmlspecialchars($r['method'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            data-category="<?= htmlspecialchars($r['category'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- DELETE -->
                                        <form action="core/delete_core.php" method="post"
                                            onsubmit="return confirm('Delete this record?')" class="w-100 m-0">
                                            <input type="hidden" name="csrf"
                                                value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id" value="<?= (int) $r['row_id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>

                                    </div>

                                <?php elseif (($r['source'] ?? '') === 'invoice'): ?>

                                    <!-- INVOICE -->

                                    <!-- Desktop / Tab : side by side -->
                                    <div class="d-none d-md-flex justify-content-center align-items-center gap-2">

                                        <button type="button"
                                            class="btn btn-outline-secondary btn-sm btn-view <?= ($showView ? '' : 'd-none') ?>"
                                            data-bs-toggle="modal" data-bs-target="#viewModal"
                                            data-row='<?= htmlspecialchars(json_encode($r), ENT_QUOTES, "UTF-8") ?>'
                                            title="View Invoice">
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <span class="badge bg-info text-dark">
                                            Paid Invoice
                                        </span>

                                    </div>

                                    <!-- Mobile : up-down -->
                                    <div class="d-flex d-md-none flex-column align-items-center gap-1">

                                        <button type="button" class="btn btn-outline-secondary btn-sm w-100 btn-view"
                                            data-bs-toggle="modal" data-bs-target="#viewModal"
                                            data-row='<?= htmlspecialchars(json_encode($r), ENT_QUOTES, "UTF-8") ?>'>
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <span class="badge bg-info text-dark">
                                            Invoice
                                        </span>

                                    </div>

                                <?php endif; ?>


                            </td>

                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Modal (only for accounts rows) -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <form action="core/update_core.php" method="post" id="editForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body row g-2">
                        <input type="hidden" name="csrf"
                            value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" id="edit_id">

                        <div class="col-6">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="date" id="edit_date" required>
                        </div>

                        <div class="col-6 d-flex flex-column justify-content-end">
                            <label class="form-label">Amount</label>
                            <input type="number" class="form-control" name="amount" id="edit_amount" min="0" step="0.01"
                                required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" name="description" id="edit_description"
                                maxlength="255" required>
                        </div>

                        <div class="col-4">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type" id="edit_type" required>
                                <option value="expense">Expense</option>
                                <option value="income">Income</option>
                            </select>
                        </div>

                        <div class="col-4">
                            <label class="form-label">Method</label>
                            <select class="form-select" name="payment_method" id="edit_method" required>
                                <option value="Cash">Cash</option>
                                <option value="bKash">bKash</option>
                                <option value="Nagad">Nagad</option>
                                <option value="Bank">Bank</option>
                                <option value="Card">Card</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="col-4">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category" id="edit_category" required>
                                <option value="buy">buy</option>
                                <option value="marketing_cost">Marketing Cost</option>
                                <option value="office_supply">Office Supply</option>
                                <option value="cost2">cost2</option>
                                <option value="Transport">Transport</option>
                                <option value="Rent">Rent</option>
                                <option value="Utilities">Utilities</option>
                                <option value="revenue">Revenue</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>

<script>
    // Default date = today (only if empty)
    (function setToday() {
        const dateEl = document.getElementById("date");
        if (!dateEl || dateEl.value) return;
        const t = new Date();
        const yyyy = t.getFullYear();
        const mm = String(t.getMonth() + 1).padStart(2, "0");
        const dd = String(t.getDate()).padStart(2, "0");
        dateEl.value = `${yyyy}-${mm}-${dd}`;
    })();

    // Fill edit modal
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-edit');
        if (!btn) return;

        document.getElementById('edit_id').value = btn.dataset.id || '';
        document.getElementById('edit_date').value = btn.dataset.date || '';
        document.getElementById('edit_description').value = btn.dataset.description || '';
        document.getElementById('edit_amount').value = btn.dataset.amount || '';
        document.getElementById('edit_type').value = (btn.dataset.type || 'expense').toLowerCase();
        document.getElementById('edit_method').value = btn.dataset.method || 'Cash';
        document.getElementById('edit_category').value = btn.dataset.category || 'Other';
    });
</script>


<!-- Settings Modal -->
<div class="modal fade" id="uiSettingsModal" tabindex="-1" aria-labelledby="uiSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uiSettingsModalLabel">Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="toggleInsert" <?= ($showInsert ? 'checked' : '') ?>>
                    <label class="form-check-label" for="toggleInsert">Insert Entry show</label>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="toggleView" <?= ($showView ? 'checked' : '') ?>>
                    <label class="form-check-label" for="toggleView">View button show</label>
                </div>

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="toggleSheet" <?= ($showSheet ? 'checked' : '') ?>>
                    <label class="form-check-label" for="toggleSheet">Sheet system show</label>
                </div>

                <div class="small text-muted mt-3">
                    Notes: Sheet change in desktop mood are reload required.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Entry Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <tbody id="viewModalBodyRows"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const csrf = "<?= htmlspecialchars($_SESSION['csrf'] ?? '', ENT_QUOTES, 'UTF-8') ?>";

        function qs(sel, root = document) { return root.querySelector(sel); }
        function qsa(sel, root = document) { return Array.from(root.querySelectorAll(sel)); }

        function applyPrefs(prefs) {
            const showInsert = !!prefs.show_insert;
            const showView = !!prefs.show_view;
            const showSheet = !!prefs.show_sheet;

            // Insert/Add sections
            qsa('.top-row').forEach(el => el.classList.toggle('d-none', !showInsert));
            // Mobile wrapper for add entry
            qsa('.d-inline.d-md-none').forEach(el => {
                if (el.querySelector('form.top-row')) el.classList.toggle('d-none', !showInsert);
            });

            // View buttons
            qsa('.btn-view').forEach(el => el.classList.toggle('d-none', !showView));

            // Sheet system (desktop + mobile)
            const sheetMobile = qs('#sheetSystemMobile');
            const sheetDesktop = qs('#sheetSystemDesktop');

            [sheetMobile, sheetDesktop].forEach(el => {
                if (!el) return;
                // Use force-hide to override Bootstrap responsive display utilities (e.g., d-md-block)
                el.classList.toggle('force-hide', !showSheet);
            });
}

        async function savePrefs() {
            const prefs = {
                show_insert: qs('#toggleInsert')?.checked ? 1 : 0,
                show_view: qs('#toggleView')?.checked ? 1 : 0,
                show_sheet: qs('#toggleSheet')?.checked ? 1 : 0
            };

            applyPrefs(prefs);

            const fd = new FormData();
            fd.append('action', 'save_ui_prefs');
            fd.append('csrf', csrf);
            fd.append('show_insert', prefs.show_insert);
            fd.append('show_view', prefs.show_view);
            fd.append('show_sheet', prefs.show_sheet);

            try {
                const res = await fetch(window.location.pathname, { method: 'POST', body: fd, credentials: 'same-origin' });
                // If something fails, keep UI as-is; user will notice next refresh if not saved.
                await res.json().catch(() => null);
            } catch (e) { /* ignore */ }
        }

        // Toggle listeners
        ['toggleInsert', 'toggleView', 'toggleSheet'].forEach(id => {
            const el = qs('#' + id);
            if (el) el.addEventListener('change', savePrefs);
        });

        // View modal populate
        const viewModal = document.getElementById('viewModal');
        if (viewModal) {
            viewModal.addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                const tbody = document.getElementById('viewModalBodyRows');
                if (!tbody) return;

                tbody.innerHTML = '';
                let row = null;

                try {
                    row = btn?.dataset?.row ? JSON.parse(btn.dataset.row) : null;
                } catch (e) {
                    row = null;
                }

                if (!row || typeof row !== 'object') {
                    tbody.innerHTML = '<tr><td class="text-muted">No data found.</td></tr>';
                    return;
                }

                // Render all fields
                Object.keys(row).forEach((k) => {
                    const v = row[k];
                    const tr = document.createElement('tr');
                    const th = document.createElement('th');
                    th.style.width = '32%';
                    th.textContent = k;

                    const td = document.createElement('td');
                    if (v === null || v === undefined) td.textContent = '';
                    else if (typeof v === 'object') td.textContent = JSON.stringify(v, null, 2);
                    else td.textContent = String(v);

                    tr.appendChild(th);
                    tr.appendChild(td);
                    tbody.appendChild(tr);
                });
            });
        }

        // Initial apply (server-rendered already, but keeps JS in sync)
        applyPrefs({
            show_insert: <?= (int) $showInsert ?>,
            show_view: <?= (int) $showView ?>,
            show_sheet: <?= (int) $showSheet ?>
        });
    })();
</script>


<?php require '../layout/layout_footer.php'; ?>