<?php
require_once '../auth/config.php';
require_login();

/*
 note_logs columns:
 id, note_id, school_id, user_id, action, old_text, new_text, action_at
*/

// ====== Filters ======
$actionFilter = trim($_GET['action'] ?? '');
$search       = trim($_GET['q'] ?? '');

// ====== Build SQL ======
$sql = "
    SELECT
        nl.*,
        u.name AS user_name,
        s.school_name,
        s.district,
        s.upazila
    FROM note_logs nl
    LEFT JOIN users u   ON u.id = nl.user_id
    LEFT JOIN schools s ON s.id = nl.school_id
    WHERE 1=1
";

$params = [];

if ($actionFilter !== '') {
    $sql .= " AND nl.action = :action";
    $params[':action'] = $actionFilter;
}

if ($search !== '') {
    $sql .= " AND (
        nl.action LIKE :q
        OR s.school_name LIKE :q
        OR u.name LIKE :q
        OR nl.old_text LIKE :q
        OR nl.new_text LIKE :q
    )";
    $params[':q'] = '%' . $search . '%';
}

$sql .= " ORDER BY nl.action_at DESC, nl.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ====== Layout ======
$pageTitle   = 'Logs';
$pageHeading = 'Logs';
$activeMenu  = 'logs';

require '../layout/layout_header.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>

<div class="bg-white rounded-xl shadow p-4 mb-4">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
        <h2 class="text-lg font-semibold text-slate-800">
            Logs <?php if ($actionFilter): ?>
                <span class="text-sm text-slate-500">(<?= h($actionFilter) ?>)</span>
            <?php endif; ?>
        </h2>

        <form method="get" class="flex gap-2">
            <?php if ($actionFilter): ?>
                <input type="hidden" name="action" value="<?= h($actionFilter) ?>">
            <?php endif; ?>

            <input type="text" name="q"
                   value="<?= h($search) ?>"
                   placeholder="Search logs..."
                   class="border rounded px-3 py-1.5 text-sm">

            <button class="px-3 py-1.5 rounded bg-slate-900 text-white text-sm">
                Search
            </button>

            <a href="logs.php"
               class="px-3 py-1.5 rounded bg-slate-200 text-slate-700 text-sm">
                Reset
            </a>
        </form>
    </div>

    <p class="text-xs text-slate-500">
        Total logs: <?= count($logs) ?>
    </p>
</div>

<div class="bg-white rounded-xl shadow p-3 overflow-x-auto">
<?php if (!$logs): ?>
    <p class="text-center text-slate-500 text-sm py-4">কোনো লগ পাওয়া যায়নি।</p>
<?php else: ?>
<table class="min-w-full text-sm border-collapse">
    <thead>
        <tr class="bg-slate-100 text-left">
            <th class="p-2 border">#</th>
            <th class="p-2 border">School</th>
            <th class="p-2 border">Action</th>
            <th class="p-2 border">User</th>
            <th class="p-2 border">Time</th>
            <th class="p-2 border">History</th>
        </tr>
    </thead>
    <tbody>
    <?php $i=1; foreach ($logs as $log): ?>
        <?php
        $schoolId   = (int)($log['school_id'] ?? 0);

        // school name fallback
        $schoolName = $log['school_name']
            ?? ($schoolId ? 'Deleted School #' . $schoolId : 'N/A');

        $address = trim(
            ($log['district'] ?? '') .
            (($log['district'] ?? '') && ($log['upazila'] ?? '') ? ', ' : '') .
            ($log['upazila'] ?? '')
        );

        $userName = $log['user_name'] ?? 'Unknown';
        ?>
        <tr class="hover:bg-slate-50 align-top">
            <td class="p-2 border"><?= $i++ ?></td>

            <td class="p-2 border">
                <div class="font-semibold text-[13px]"><?= h($schoolName) ?></div>
                <?php if ($address): ?>
                    <div class="text-[11px] text-slate-500"><?= h($address) ?></div>
                <?php endif; ?>
                <?php if ($schoolId): ?>
                    <div class="text-[11px] text-slate-400">ID: <?= $schoolId ?></div>
                <?php endif; ?>
            </td>

            <td class="p-2 border">
                <span class="text-xs px-2 py-0.5 rounded bg-slate-200">
                    <?= h($log['action'] ?? '') ?>
                </span>
            </td>

            <td class="p-2 border text-xs">
                <?= h($userName) ?>
            </td>

            <td class="p-2 border text-xs whitespace-nowrap">
                <?= h($log['action_at'] ?? '') ?>
            </td>

            <td class="p-2 border text-xs">
                <?php if ($schoolId): ?>
                    <a href="../logs/logs_history.php?school_id=<?= $schoolId ?>"
                       class="px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700">
                        View
                    </a>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</div>

<?php require '../layout/layout_footer.php'; ?>
