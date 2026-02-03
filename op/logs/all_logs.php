<?php
// logs/logs.php
require_once '../auth/config.php';
require_login();

/**
 * note_logs columns:
 * id, note_id, school_id, user_id, action, old_text, new_text, action_at
 */

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ---------- Filters ----------
$q        = trim($_GET['q'] ?? '');
$actionF  = trim($_GET['action'] ?? '');
$schoolId = trim($_GET['school_id'] ?? '');
$userId   = trim($_GET['user_id'] ?? '');
$from     = trim($_GET['from'] ?? '');
$to       = trim($_GET['to'] ?? '');

$isValidDate = function(string $d): bool {
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
};

// ---------- Pagination ----------
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 30;
$offset   = ($page - 1) * $perPage;

// ---------- Action groups (for label when school_name missing) ----------
$accountsActions = ['Entry add', 'Entry Updated', 'Entry delete'];
$invoiceActions  = ['Simple Invoice Created', 'INVOICE UPDATED', 'Invoice delete'];

// ---------- Build WHERE ----------
$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(nl.action LIKE :q OR nl.old_text LIKE :q OR nl.new_text LIKE :q OR s.school_name LIKE :q OR u.name LIKE :q)";
    $params[':q'] = "%{$q}%";
}
if ($actionF !== '') {
    $where[] = "nl.action = :action";
    $params[':action'] = $actionF;
}
if ($schoolId !== '' && ctype_digit($schoolId)) {
    $where[] = "nl.school_id = :school_id";
    $params[':school_id'] = (int)$schoolId;
}
if ($userId !== '' && ctype_digit($userId)) {
    $where[] = "nl.user_id = :user_id";
    $params[':user_id'] = (int)$userId;
}
if ($from !== '' && $isValidDate($from)) {
    $where[] = "DATE(nl.action_at) >= :from";
    $params[':from'] = $from;
}
if ($to !== '' && $isValidDate($to)) {
    $where[] = "DATE(nl.action_at) <= :to";
    $params[':to'] = $to;
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

// ---------- Get distinct actions for dropdown ----------
$actions = [];
try {
    $stmtA = $pdo->query("SELECT action, COUNT(*) total FROM note_logs GROUP BY action ORDER BY total DESC, action ASC");
    $actions = $stmtA->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $actions = [];
}

// ---------- Total count ----------
$totalRows = 0;
try {
    $stmtC = $pdo->prepare("
        SELECT COUNT(*)
        FROM note_logs nl
        LEFT JOIN schools s ON nl.school_id = s.id
        LEFT JOIN users  u ON nl.user_id   = u.id
        $whereSql
    ");
    $stmtC->execute($params);
    $totalRows = (int)($stmtC->fetchColumn() ?? 0);
} catch (Exception $e) {
    $totalRows = 0;
}

$totalPages = max(1, (int)ceil($totalRows / $perPage));

// ---------- Fetch logs (latest first) ----------
$logs = [];
try {
    $sql = "
        SELECT
            nl.id, nl.note_id, nl.school_id, nl.user_id,
            nl.action, nl.old_text, nl.new_text, nl.action_at,
            s.school_name,
            u.name AS user_name
        FROM note_logs nl
        LEFT JOIN schools s ON nl.school_id = s.id
        LEFT JOIN users  u ON nl.user_id   = u.id
        $whereSql
        ORDER BY nl.action_at DESC, nl.id DESC
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $logs = [];
}

// ---------- Layout vars ----------
$pageTitle   = 'Logs - School List';
$pageHeading = 'Logs';
$activeMenu  = 'logs';

require '../layout/layout_header.php';

// helper: build query string for pagination links
function build_qs(array $extra = []) {
    $q = array_merge($_GET, $extra);
    return http_build_query($q);
}
?>

<div class="bg-white rounded-xl shadow p-4 mb-6">
    <div class="flex items-center justify-between gap-2 mb-3">
        <h2 class="text-sm sm:text-base font-semibold text-slate-800">All Logs</h2>
        <div class="text-xs text-slate-500">
            Total: <?= (int)$totalRows ?>
        </div>
    </div>

    <form method="get" class="grid grid-cols-1 md:grid-cols-6 gap-2">
        <input type="text" name="q" value="<?= h($q) ?>"
               class="border rounded-md px-3 py-2 text-sm "
               placeholder="action/text/school/user">

        <select name="action" class="border rounded-md px-3 py-2 text-sm">
            <option value="">All Actions</option>
            <?php foreach ($actions as $a): ?>
                <?php $val = (string)($a['action'] ?? ''); if ($val === '') continue; ?>
                <option value="<?= h($val) ?>" <?= $actionF === $val ? 'selected' : '' ?>>
                    <?= h($val) ?> (<?= (int)($a['total'] ?? 0) ?>)
                </option>
            <?php endforeach; ?>
        </select>

         <input type="number" name="school_id" value="<?= h($schoolId) ?>"
               class="border rounded-md px-3 py-2 text-sm"
               placeholder="School ID">

        <!--<input type="number" name="user_id" value="<?= h($userId) ?>"
               class="border rounded-md px-3 py-2 text-sm"
               placeholder="User ID"> -->

        <div class="flex items-center gap-2 md:col-span-2">
            <input type="date" name="from" value="<?= h($from) ?>"
                   class="border rounded-md px-3 py-2 text-sm w-full" title="From">
            <input type="date" name="to" value="<?= h($to) ?>"
                   class="border rounded-md px-3 py-2 text-sm w-full" title="To">
        </div>

        <div class="flex items-center gap-2">
            <button class="px-3 py-2 rounded-md bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                Apply
            </button>
            <a href="<?= h($_SERVER['PHP_SELF']) ?>"
               class="px-3 py-2 rounded-md bg-slate-100 text-slate-800 text-sm hover:bg-slate-200">
                Reset
            </a>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow p-4">
    <?php if (!$logs): ?>
        <p class="text-[13px] text-slate-500">কোনো লগ পাওয়া যায়নি।</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-left">
                        <th class="p-2 border">#</th>
                        <th class="p-2 border">When</th>
                        <th class="p-2 border">School/Type</th>
                        <th class="p-2 border">Action</th>
                        <th class="p-2 border">By</th>
                        <th class="p-2 border">Note ID</th>
                        <th class="p-2 border">Changes</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $i => $log): ?>
                    <?php
                    $actionRaw = $log['action'] ?? '';
                    $action = trim($actionRaw);

                    $isAccounts = in_array($action, $accountsActions, true);
                    $isInvoice  = in_array($action, $invoiceActions, true);

                    // School/Type label
                    $schoolLabel = $log['school_name'] ?? ($isAccounts ? 'Accounts' : ($isInvoice ? 'Invoices' : 'Activity'));

                    // Pretty action label
                    $actionLabel = $action !== '' ? ucwords(strtolower($action)) : 'Activity';

                    // Badge color
                    $badgeClass = 'bg-slate-100 text-slate-700';
                    if ($isAccounts) $badgeClass = 'bg-emerald-50 text-emerald-700';
                    elseif ($isInvoice) $badgeClass = 'bg-orange-50 text-orange-700';

                    $userName = $log['user_name'] ?? ('User #' . (int)($log['user_id'] ?? 0));
                    $when     = $log['action_at'] ?? '';

                    $oldText = (string)($log['old_text'] ?? '');
                    $newText = (string)($log['new_text'] ?? '');

                    $rowNo = $offset + $i + 1;
                    ?>
                    <tr class="hover:bg-slate-50 align-top">
                        <td class="p-2 border"><?= (int)$rowNo ?></td>
                        <td class="p-2 border whitespace-nowrap"><?= h($when) ?></td>
                        <td class="p-2 border">
                            <div class="font-semibold text-[13px]"><?= h($schoolLabel) ?></div>
                            <?php if (!empty($log['school_id'])): ?>
                                <div class="text-[11px] text-slate-500">School ID: <?= (int)$log['school_id'] ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="p-2 border">
                            <span class="text-[11px] px-2 py-0.5 rounded-full <?= h($badgeClass) ?>">
                                <?= h($actionLabel) ?>
                            </span>
                            <div class="text-[11px] text-slate-500 mt-1">Log ID: <?= (int)$log['id'] ?></div>
                        </td>
                        <td class="p-2 border">
                            <div class="font-semibold text-[13px]"><?= h($userName) ?></div>
                            <?php if (!empty($log['user_id'])): ?>
                                <div class="text-[11px] text-slate-500">User ID: <?= (int)$log['user_id'] ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="p-2 border whitespace-nowrap">
                            <?= !empty($log['note_id']) ? (int)$log['note_id'] : '—' ?>
                        </td>
                        <td class="p-2 border">
                            <details class="text-[12px]">
                                <summary class="cursor-pointer text-indigo-600 hover:underline">
                                    View Old/New
                                </summary>

                                <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
                                    <div class="border rounded-lg p-2 bg-slate-50">
                                        <div class="text-[11px] font-semibold text-slate-600 mb-1">Old</div>
                                        <div class="whitespace-pre-wrap break-words text-slate-800"><?= h($oldText ?: '—') ?></div>
                                    </div>
                                    <div class="border rounded-lg p-2 bg-slate-50">
                                        <div class="text-[11px] font-semibold text-slate-600 mb-1">New</div>
                                        <div class="whitespace-pre-wrap break-words text-slate-800"><?= h($newText ?: '—') ?></div>
                                    </div>
                                </div>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between mt-4 text-sm">
            <div class="text-slate-500">
                Page <?= (int)$page ?> / <?= (int)$totalPages ?>
            </div>

            <div class="flex items-center gap-2">
                <?php if ($page > 1): ?>
                    <a class="px-3 py-1.5 rounded bg-slate-100 hover:bg-slate-200"
                       href="?<?= h(build_qs(['page' => 1])) ?>">First</a>
                    <a class="px-3 py-1.5 rounded bg-slate-100 hover:bg-slate-200"
                       href="?<?= h(build_qs(['page' => $page - 1])) ?>">Prev</a>
                <?php endif; ?>

                <?php if ($page < $totalPages): ?>
                    <a class="px-3 py-1.5 rounded bg-slate-100 hover:bg-slate-200"
                       href="?<?= h(build_qs(['page' => $page + 1])) ?>">Next</a>
                    <a class="px-3 py-1.5 rounded bg-slate-100 hover:bg-slate-200"
                       href="?<?= h(build_qs(['page' => $totalPages])) ?>">Last</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require '../layout/layout_footer.php'; ?>
