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
try {
    $totalNotes = (int)($pdo->query("SELECT COUNT(*) FROM school_notes")->fetchColumn() ?? 0);
} catch (Exception $e) {
    // যদি school_notes না থাকে তবে 0-ই থাকবে
}

$totalUsers = 0;
try {
    $totalUsers = (int)($pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?? 0);
} catch (Exception $e) {
    // যদি users টেবিল না থাকে তবে 0
}

// ====== Latest Schools (সর্বশেষ ৫টা) ======
$latestSchools = [];
try {
    $stmt = $pdo->query("
        SELECT
            s.id,
            s.school_name,
            s.district,
            s.upazila,
            s.status,
            u.name AS created_name
        FROM schools s
        LEFT JOIN users u ON s.created_by = u.id
        ORDER BY s.id DESC
        LIMIT 5
    ");
    $latestSchools = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $latestSchools = [];
}

// ====== Latest Note Logs (সর্বশেষ ৫টা অ্যাকশন) ======
$latestLogs = [];
try {
    $stmt = $pdo->query("
        SELECT
            nl.id,
            nl.action,
            nl.action_at,
            nl.school_id,
            s.school_name,
            u.name AS user_name
        FROM note_logs nl
        LEFT JOIN schools s ON nl.school_id = s.id
        LEFT JOIN users  u ON nl.user_id   = u.id
        ORDER BY nl.action_at DESC
        LIMIT 5
    ");
    $latestLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $latestLogs = [];
}

// ====== Layout Vars ======
$pageTitle   = 'Dashboard - School List';
$pageHeading = 'Dashboard';
$activeMenu  = 'dashboard';

require '../layout/layout_header.php';
?>

<!-- Top Stat Cards -->
<div class="grid gap-4 md:grid-cols-4 mb-6">

    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs text-slate-500 mb-1">Total Schools</div>
        <div class="text-2xl font-bold text-slate-800 mb-1"><?php echo $totalSchools; ?></div>
        <a href="/school_list/schools/schools.php"
           class="inline-block text-xs text-indigo-600 hover:underline">
            View All 
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs text-slate-500 mb-1">Approved Schools</div>
        <div class="text-2xl font-bold text-green-600 mb-1"><?php echo $approvedSchools; ?></div>
        <a href="/school_list/schools/schools.php?status=Approved"
           class="inline-block text-xs text-indigo-600 hover:underline">
            View Approved 
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs text-slate-500 mb-1">Pending Schools</div>
        <div class="text-2xl font-bold text-orange-500 mb-1"><?php echo $pendingSchools; ?></div>
        <a href="/school_list/schools/schools.php?status=Pending"
           class="inline-block text-xs text-indigo-600 hover:underline">
            View Pending 
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs text-slate-500 mb-1">Trashed Schools</div>
        <div class="text-2xl font-bold text-red-500 mb-1"><?php echo $trashedSchools; ?></div>
        <a href="/school_list/pages/trash.php"
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
        <p class="text-[11px] text-slate-500">
            সকল স্কুলের উপর দেওয়া মোট নোট সংখ্যা।
        </p>
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
            <a href="/school_list/schools/school_create.php"
               class="px-3 py-1.5 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                + Add School
            </a>
            <a href="/school_list/schools/schools.php"
               class="px-3 py-1.5 rounded bg-slate-800 text-white hover:bg-slate-900">
                Manage Schools
            </a>
            <a href="/school_list/logs/logs.php"
               class="px-3 py-1.5 rounded bg-emerald-600 text-white hover:bg-emerald-700">
                View Logs
            </a>
            <a href="/school_list/invoices/invoices.php"
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
            <a href="schools.php"
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

                            $statusClass = ($s['status'] === 'Approved')
                                ? 'text-green-600'
                                : 'text-orange-600';
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
            <a href="/school_list/logs/logs.php"
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

                    if ($action === 'create') {
                        $badgeClass = 'bg-emerald-50 text-emerald-700';
                    } elseif ($action === 'update') {
                        $badgeClass = 'bg-blue-50 text-blue-700';
                    } elseif ($action === 'delete') {
                        $badgeClass = 'bg-red-50 text-red-700';
                    }

                    $schoolName = $log['school_name'] ?? ('School #' . (int)$log['school_id']);
                    $userName   = $log['user_name']   ?? 'Unknown User';
                    $time       = $log['action_at']   ?? '';
                    ?>
                    <li class="border border-slate-100 rounded-lg px-3 py-2 hover:bg-slate-50">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-semibold">
                                <?php echo htmlspecialchars($schoolName); ?>
                            </span>
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

<?php
require '../layout/layout_footer.php';
