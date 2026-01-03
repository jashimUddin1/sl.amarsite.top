<?php // pages/dashboard.php
require_once '../auth/config.php';
require_login();

// ====== Basic Counts ======
$totalSchools    = (int)($pdo->query("SELECT COUNT(*) FROM schools")->fetchColumn() ?? 0);
$approvedSchools = (int)($pdo->query("SELECT COUNT(*) FROM schools WHERE status = 'Approved'")->fetchColumn() ?? 0);
$pendingSchools  = (int)($pdo->query("SELECT COUNT(*) FROM schools WHERE status = 'Pending'")->fetchColumn() ?? 0);
$trashedSchools  = (int)($pdo->query("SELECT COUNT(*) FROM school_trash")->fetchColumn() ?? 0);

// নোটস আর users থাকলে তাদেরও কাউন্ট
$totalNotes = 0;
try { $totalNotes = (int)($pdo->query("SELECT COUNT(*) FROM school_notes")->fetchColumn() ?? 0); } catch (Exception $e) {}
$totalUsers = 0;
try { $totalUsers = (int)($pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?? 0); } catch (Exception $e) {}

// ====== Latest Schools (সর্বশেষ ৫টা) ======
$latestSchools = [];
try {
    $stmt = $pdo->query("
        SELECT s.id, s.school_name, s.district, s.upazila, s.status, u.name AS created_name
        FROM schools s
        LEFT JOIN users u ON s.created_by = u.id
        ORDER BY s.id DESC
        LIMIT 5
    ");
    $latestSchools = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $latestSchools = []; }

// ====== Latest Note Logs (সর্বশেষ ৫টা অ্যাকশন) ======
$latestLogs = [];
try {
    $stmt = $pdo->query("
        SELECT nl.id, nl.action, nl.action_at, nl.school_id, s.school_name, u.name AS user_name
        FROM note_logs nl
        LEFT JOIN schools s ON nl.school_id = s.id
        LEFT JOIN users  u ON nl.user_id   = u.id
        ORDER BY nl.action_at DESC
        LIMIT 5
    ");
    $latestLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $latestLogs = []; }

// ====== Income Summary Range ======
$range = $_GET['range'] ?? 'lifetime'; // default
$allowedRanges = ['today','this_month','this_year','last_year','lifetime','custom'];
if (!in_array($range, $allowedRanges, true)) $range = 'this_month';

$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';

$isValidDate = function(string $d): bool {
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
};

$selected = match ($range) {
    'today'      => 'Today',
    'this_month' => 'This Month',
    'this_year'  => 'This Year',
    'last_year'  => 'Last Year',
    'lifetime'   => 'Life Time',
    'custom'     => ($from && $to ? ($from . ' to ' . $to) : 'Custom'),
    default      => 'This Month',
};

// SQL WHERE based on JSON invoiceDate
$where = "1=1"; // lifetime
if ($range === 'today') {
    $where = "STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, '$.invoiceDate')), '%Y-%m-%d') = CURDATE()";
} elseif ($range === 'this_month') {
    $where = "DATE_FORMAT(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, '$.invoiceDate')), '%Y-%m-%d'), '%Y-%m')
              = DATE_FORMAT(CURDATE(), '%Y-%m')";
} elseif ($range === 'this_year') {
    $where = "YEAR(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, '$.invoiceDate')), '%Y-%m-%d')) = YEAR(CURDATE())";
} elseif ($range === 'last_year') {
    $where = "YEAR(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, '$.invoiceDate')), '%Y-%m-%d')) = YEAR(CURDATE()) - 1";
} elseif ($range === 'custom') {
    if ($isValidDate($from) && $isValidDate($to)) {
        $where = "STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, '$.invoiceDate')), '%Y-%m-%d')
                  BETWEEN :from AND :to";
        $selected = $from . ' to ' . $to;
    } else {
        // invalid custom => fallback this_month
        $range = 'this_month';
        $selected = 'This Month';
        $where = "DATE_FORMAT(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, '$.invoiceDate')), '%Y-%m-%d'), '%Y-%m')
                  = DATE_FORMAT(CURDATE(), '%Y-%m')";
    }
}

// ====== Income Summary (3 cards + separate counts) ======
$income = [
    'total'           => 0.0,
    'collected'       => 0.0,
    'due'             => 0.0,
    'income_count'    => 0,  // matched invoices count
    'collected_count' => 0,  // pay>0 invoices count
    'due_count'       => 0,  // due>0 invoices count
];

try {
    $sql = "
        SELECT
            COUNT(*) AS income_count,

            COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.totals.total')) AS DECIMAL(12,2))), 0) AS total_income,

            COALESCE(SUM(
                CASE
                    WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.totals.pay')) AS DECIMAL(12,2)) > 0
                    THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.totals.pay')) AS DECIMAL(12,2))
                    ELSE 0
                END
            ), 0) AS total_collected,

            COALESCE(SUM(
                CASE
                    WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.totals.due')) AS DECIMAL(12,2)) > 0
                    THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.totals.due')) AS DECIMAL(12,2))
                    ELSE 0
                END
            ), 0) AS total_due,

            COALESCE(SUM(
                CASE
                    WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.totals.pay')) AS DECIMAL(12,2)) > 0 THEN 1
                    ELSE 0
                END
            ), 0) AS collected_count,

            COALESCE(SUM(
                CASE
                    WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.totals.due')) AS DECIMAL(12,2)) > 0 THEN 1
                    ELSE 0
                END
            ), 0) AS due_count

        FROM invoices
        WHERE $where
    ";

    $stmt = $pdo->prepare($sql);
    if ($range === 'custom' && strpos($where, ':from') !== false) {
        $stmt->bindValue(':from', $from);
        $stmt->bindValue(':to', $to);
    }
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $income['income_count']    = (int)($row['income_count'] ?? 0);
    $income['collected_count'] = (int)($row['collected_count'] ?? 0);
    $income['due_count']       = (int)($row['due_count'] ?? 0);

    $income['total']     = (float)($row['total_income'] ?? 0);
    $income['collected'] = (float)($row['total_collected'] ?? 0);
    $income['due']       = (float)($row['total_due'] ?? 0);

} catch (Exception $e) {
    // debug চাইলে:
    // echo '<pre>'.$e->getMessage().'</pre>';
}

// ====== Layout Vars ======
$pageTitle   = 'Dashboard - School List';
$pageHeading = 'Dashboard';
$activeMenu  = 'dashboard';

require '../layout/layout_header.php';
?>

<!-- Income Summary Parent Block (Header + Range + Cards) -->
<div class="bg-white rounded-xl shadow p-4 mb-6">

    <!-- Header + Range (mobile one-row) -->
    <div class="flex items-center justify-between gap-2 mb-4">
        <h2 class="text-sm sm:text-base font-semibold text-slate-800 whitespace-nowrap">
            Income Summary <span class="text-slate-500 text-xs sm:text-sm">(<?= htmlspecialchars($selected) ?>)</span>
        </h2>

        <form method="get"
              class="flex items-center gap-1 sm:gap-2 flex-nowrap"
              id="rangeForm"
              title="Select range to filter income summary">

            <select name="range" id="rangeSelect"
                    class="border rounded-md px-2 py-1 text-xs sm:text-sm"
                    title="Range">
                <option value="today"      <?= $range==='today'?'selected':'' ?>>Today</option>
                <option value="this_month" <?= $range==='this_month'?'selected':'' ?>>This Month</option>
                <option value="this_year"  <?= $range==='this_year'?'selected':'' ?>>This Year</option>
                <option value="last_year"  <?= $range==='last_year'?'selected':'' ?>>Last Year</option>
                <option value="lifetime"   <?= $range==='lifetime'?'selected':'' ?>>Life Time</option>
                <option value="custom"     <?= $range==='custom'?'selected':'' ?>>Custom</option>
            </select>

            <div id="customFields" class="flex items-center gap-1">
                <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"
                       class="border rounded-md px-2 py-1 text-xs sm:text-sm"
                       title="From (YYYY-MM-DD)">
                <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"
                       class="border rounded-md px-2 py-1 text-xs sm:text-sm"
                       title="To (YYYY-MM-DD)">
            </div>

            <button class="px-2.5 sm:px-3 py-1 rounded-md bg-indigo-600 text-white text-xs sm:text-sm hover:bg-indigo-700"
                    title="Apply">
                Apply
            </button>
        </form>
    </div>

    <script>
    (function(){
      const sel = document.getElementById('rangeSelect');
      const custom = document.getElementById('customFields');
      function toggleCustom(){
        if (!sel || !custom) return;
        custom.style.display = (sel.value === 'custom') ? 'flex' : 'none';
      }
      if (sel){
        sel.addEventListener('change', toggleCustom);
        toggleCustom();
      }
    })();
    </script>

    <!-- Cards: mobile => income full width, next row 2 cards -->
    <div class="grid gap-3 grid-cols-2 md:grid-cols-3">

        <!-- Total Income (full width on small) -->
        <div class="bg-slate-50 rounded-xl border border-slate-100 p-4 col-span-2 md:col-span-1">
            <div class="flex items-center justify-between">
                <div class="text-xs text-slate-500">Total Income</div>
                <div class="text-[11px] text-slate-500" title="Matched invoices count">
                    (<?= (int)$income['income_count'] ?>)
                </div>
            </div>
            <div class="text-2xl font-bold text-slate-800 mt-1">
                ৳ <?= number_format($income['total'], 2); ?>
            </div>
            <a href="/pages/income_details.php?type=income&range=<?= urlencode($range) ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>"
               class="inline-block text-xs text-indigo-600 hover:underline mt-2">
                View Details
            </a>
        </div>

        <!-- Total Collected -->
        <div class="bg-slate-50 rounded-xl border border-slate-100 p-4">
            <div class="flex items-center justify-between">
                <div class="text-xs text-slate-500">Total Collected</div>
                <div class="text-[11px] text-slate-500" title="Invoices where pay > 0">
                    (<?= (int)$income['collected_count'] ?>)
                </div>
            </div>
            <div class="text-2xl font-bold text-emerald-600 mt-1">
                ৳ <?= number_format($income['collected'], 2); ?>
            </div>
            <a href="/pages/income_details.php?type=collected&range=<?= urlencode($range) ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>"
               class="inline-block text-xs text-indigo-600 hover:underline mt-2">
                View Details
            </a>
        </div>

        <!-- Total Due -->
        <div class="bg-slate-50 rounded-xl border border-slate-100 p-4">
            <div class="flex items-center justify-between">
                <div class="text-xs text-slate-500">Total Due</div>
                <div class="text-[11px] text-slate-500" title="Invoices where due > 0">
                    (<?= (int)$income['due_count'] ?>)
                </div>
            </div>
            <div class="text-2xl font-bold text-red-500 mt-1">
                ৳ <?= number_format($income['due'], 2); ?>
            </div>
            <a href="/pages/income_details.php?type=due&range=<?= urlencode($range) ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>"
               class="inline-block text-xs text-indigo-600 hover:underline mt-2">
                View Details
            </a>
        </div>

    </div>
</div>

<!-- Top Stat Cards (mobile 2-col, desktop 4-col) -->
<div class="grid gap-4 grid-cols-2 md:grid-cols-4 mb-6">

    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs text-slate-500 mb-1">Total Schools</div>
        <div class="text-2xl font-bold text-slate-800 mb-1"><?php echo $totalSchools; ?></div>
        <a href="/schools/schools.php"
           class="inline-block text-xs text-indigo-600 hover:underline">
            View All
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs text-slate-500 mb-1">Approved Schools</div>
        <div class="text-2xl font-bold text-green-600 mb-1"><?php echo $approvedSchools; ?></div>
        <a href="/schools/schools.php?status=Approved"
           class="inline-block text-xs text-indigo-600 hover:underline">
            View Approved
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs text-slate-500 mb-1">Pending Schools</div>
        <div class="text-2xl font-bold text-orange-500 mb-1"><?php echo $pendingSchools; ?></div>
        <a href="/schools/schools.php?status=Pending"
           class="inline-block text-xs text-indigo-600 hover:underline">
            View Pending
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs text-slate-500 mb-1">Trashed Schools</div>
        <div class="text-2xl font-bold text-red-500 mb-1"><?php echo $trashedSchools; ?></div>
        <a href="/pages/trash.php"
           class="inline-block text-xs text-indigo-600 hover:underline">
            Open Trash
        </a>
    </div>
</div>

<!-- Second row: Notes + Users -->
<div class="grid gap-4 md:grid-cols-3 mb-6">

    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs text-slate-500 mb-1">Total Notes</div>
        <div class="text-2xl font-bold text-slate-800 mb-1"><?php echo $totalNotes; ?></div>
        <a href="../notes/notes_all.php"
           class="inline-block text-xs text-indigo-600 hover:underline">
            View all
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs text-slate-500 mb-1">Total Users</div>
        <div class="text-2xl font-bold text-slate-800 mb-1"><?php echo $totalUsers; ?></div>
        <a href="user_reports.php"
           class="inline-block text-xs text-indigo-600 hover:underline">
            View User Activity
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs text-slate-500 mb-1">Quick Actions</div>
        <div class="flex flex-wrap gap-2 mt-2 text-sm">
            <a href="/schools/school_create.php"
               class="px-3 py-1.5 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                + Add School
            </a>
            <a href="/schools/schools.php"
               class="px-3 py-1.5 rounded bg-slate-800 text-white hover:bg-slate-900">
                Manage Schools
            </a>
            <a href="/logs/logs.php"
               class="px-3 py-1.5 rounded bg-emerald-600 text-white hover:bg-emerald-700">
                View Logs
            </a>
            <a href="/invoices/invoices.php"
               class="px-3 py-1.5 rounded bg-orange-600 text-white hover:bg-orange-700">
                View Invoices
            </a>
        </div>
    </div>
</div>

<!-- Bottom: Latest Schools + Recent Activity -->
<div class="grid gap-4 lg:grid-cols-2">

    <!-- Latest Schools -->
    <div class="bg-white rounded-xl shadow p-4">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-sm font-semibold text-slate-800">Latest Schools</h2>
            <a href="../schools/schools.php"
               class="text-xs text-indigo-600 hover:underline">
                View All
            </a>
        </div>

        <?php if (!$latestSchools): ?>
            <p class="text-[13px] text-slate-500">কোনো স্কুল পাওয়া যায়নি।</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-left">
                            <th class="p-2 border">ID</th>
                            <th class="p-2 border">Name</th>
                            <th class="p-2 border">Address</th>
                            <th class="p-2 border">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latestSchools as $s): ?>
                            <?php
                            $addr = trim(($s['district'] ?? '') .
                                (($s['district'] ?? '') && ($s['upazila'] ?? '') ? ', ' : '') .
                                ($s['upazila'] ?? ''));
                            if ($addr === '') $addr = 'N/A';

                            $statusClass = ($s['status'] === 'Approved') ? 'text-green-600' : 'text-orange-600';
                            ?>
                            <tr class="hover:bg-slate-50">
                                <td class="p-2 border align-top"><?php echo (int)$s['id']; ?></td>
                                <td class="p-2 border align-top">
                                    <div class="font-semibold text-[13px]">
                                        <?php echo htmlspecialchars($s['school_name']); ?>
                                    </div>
                                    <?php if (!empty($s['created_name'])): ?>
                                        <div class="text-[11px] text-slate-500">
                                            Created by: <?php echo htmlspecialchars($s['created_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-2 border align-top text-[12px] text-slate-700">
                                    <?php echo htmlspecialchars($addr); ?>
                                </td>
                                <td class="p-2 border align-top">
                                    <span class="text-[11px] font-semibold <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($s['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Note Activity -->
    <div class="bg-white rounded-xl shadow p-4">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-sm font-semibold text-slate-800">Recent Note Activity</h2>
            <a href="<?= base_url('/logs/logs.php') ?>"
               class="text-xs text-indigo-600 hover:underline">
                View All Logs
            </a>
        </div>

        <?php if (!$latestLogs): ?>
            <p class="text-[13px] text-slate-500">কোনো নোট অ্যাকশন পাওয়া যায়নি।</p>
        <?php else: ?>
            <ul class="space-y-2 text-[13px]">
                <?php foreach ($latestLogs as $log): ?>
                    <?php
                    $action = $log['action'] ?? '';
                    $actionLabel = ucfirst($action);
                    $badgeClass = 'bg-slate-100 text-slate-700';
                    if ($action === 'create') $badgeClass = 'bg-emerald-50 text-emerald-700';
                    elseif ($action === 'update') $badgeClass = 'bg-blue-50 text-blue-700';
                    elseif ($action === 'delete') $badgeClass = 'bg-red-50 text-red-700';

                    // $schoolName = $log['school_name'] ?? ('School #' . (int)$log['school_id']); old
                    $schoolName = $log['school_name'] ?? ('Accounts');
                    $userName   = $log['user_name']   ?? 'Unknown User';
                    $time       = $log['action_at']   ?? '';
                    ?>
                    <li class="border border-slate-100 rounded-lg px-3 py-2 hover:bg-slate-50">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-semibold"><?php echo htmlspecialchars($schoolName); ?></span>
                            <span class="text-[11px] px-2 py-0.5 rounded-full <?php echo $badgeClass; ?>">
                                <?php echo htmlspecialchars($actionLabel); ?>
                            </span>
                        </div>
                        <div class="text-[11px] text-slate-500 flex justify-between">
                            <span>By: <?php echo htmlspecialchars($userName); ?></span>
                            <span><?php echo htmlspecialchars($time); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

</div>

<?php require '../layout/layout_footer.php'; ?>
