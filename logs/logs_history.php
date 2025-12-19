<?php
// logs_history.php
require_once '../auth/config.php';
require_login();

// --- Input: school_id ---
$schoolId = isset($_GET['school_id']) ? (int) $_GET['school_id'] : 0;
if ($schoolId <= 0) {
    $pageTitle   = 'Log History - School List';
    $pageHeading = 'Log History';
    $activeMenu  = 'logs';
    require '../layout/layout_header.php';
    ?>
    <div class="bg-white rounded-xl shadow p-6">
        <p class="text-sm text-red-600">ভুল school আইডি দেওয়া হয়েছে।</p>
        <a href="logs.php"
           class="inline-block mt-3 px-4 py-2 rounded bg-slate-800 text-white text-sm hover:bg-slate-900">
            ← Back to Logs
        </a>
    </div>
    <?php
    require '../layout/layout_footer.php';
    exit;
}

// --- School basic info (যদি এখনও schools টেবিলে থাকে) ---
$school = null;
try {
    $stmt = $pdo->prepare("
        SELECT id, school_name, district, upazila, status
        FROM schools
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $schoolId]);
    $school = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // কিছু না, logs টেবিল থাকলেই history দেখাব
}

// --- Note logs for this school ---
$stmt = $pdo->prepare("
    SELECT
        nl.id,
        nl.action,
        nl.action_at,
        nl.user_id,
        u.name AS user_name
    FROM note_logs nl
    LEFT JOIN users u ON nl.user_id = u.id
    WHERE nl.school_id = :school_id
    ORDER BY nl.action_at DESC, nl.id DESC
");
$stmt->execute([':school_id' => $schoolId]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Layout header ---
$pageTitle   = 'Log History - School List';
$pageHeading = 'School Log History';
$activeMenu  = 'logs';

require '../layout/layout_header.php';
?>

<div class="bg-white rounded-xl shadow p-4 md:p-6">

    <!-- Top: Title + Back button -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-800 mb-1">
                স্কুলের নোট হিস্টোরি
            </h2>
            <p class="text-xs text-slate-500">
                School ID: <?php echo (int)$schoolId; ?>
            </p>
        </div>

        <div class="flex gap-2">
            <a href="logs.php"
               class="px-3 py-1.5 rounded border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">
                ← Back to Logs
            </a>

            <?php if ($school): ?>
                <a href="school_edit.php?id=<?php echo (int)$school['id']; ?>"
                   class="px-3 py-1.5 rounded bg-slate-800 text-white text-sm hover:bg-slate-900">
                    Edit School
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- School info card -->
    <div class="border border-slate-200 rounded-lg p-3 mb-4 bg-slate-50">
        <?php if ($school): ?>
            <div class="text-sm font-semibold text-slate-800">
                <?php echo htmlspecialchars($school['school_name']); ?>
            </div>
            <div class="text-xs text-slate-600 mt-0.5">
                <?php
                $addrParts = [];
                if (!empty($school['district'])) $addrParts[] = $school['district'];
                if (!empty($school['upazila']))  $addrParts[] = $school['upazila'];
                echo htmlspecialchars(implode(', ', $addrParts));
                ?>
            </div>
            <div class="mt-1">
                <?php
                $status = $school['status'] ?? 'Pending';
                $badgeClass = $status === 'Approved'
                    ? 'bg-emerald-100 text-emerald-800'
                    : 'bg-orange-100 text-orange-800';
                ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium <?php echo $badgeClass; ?>">
                    Status: <?php echo htmlspecialchars($status); ?>
                </span>
            </div>
        <?php else: ?>
            <div class="text-sm text-red-600 font-semibold">
                এই স্কুলটি মূল তালিকা থেকে মুছে ফেলা হয়েছে (বা পাওয়া যায়নি)।
            </div>
            <div class="text-xs text-slate-600 mt-1">
                তারপরও note_logs টেবিলে থাকা সব activity নিচে দেখানো হচ্ছে।
            </div>
        <?php endif; ?>
    </div>

    <!-- Logs table -->
    <?php if (!$logs): ?>
        <p class="text-sm text-slate-500">
            এই স্কুলের জন্য কোনো note activity পাওয়া যায়নি।
        </p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs border border-slate-200 border-collapse">
                <thead>
                <tr class="bg-slate-100">
                    <th class="p-2 border border-slate-200 text-left">#</th>
                    <th class="p-2 border border-slate-200 text-left">Action</th>
                    <th class="p-2 border border-slate-200 text-left">User</th>
                    <th class="p-2 border border-slate-200 text-left">Time</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $sl = 1;
                foreach ($logs as $log):
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

                    $userName = $log['user_name'] ?? 'Unknown User';
                    $time     = $log['action_at'] ?? '';
                    ?>
                    <tr class="hover:bg-slate-50">
                        <td class="p-2 border border-slate-200 align-top">
                            <?php echo $sl++; ?>
                        </td>
                        <td class="p-2 border border-slate-200 align-top">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-medium <?php echo $badgeClass; ?>">
                                <?php echo htmlspecialchars($actionLabel); ?>
                            </span>
                        </td>
                        <td class="p-2 border border-slate-200 align-top">
                            <?php echo htmlspecialchars($userName); ?>
                        </td>
                        <td class="p-2 border border-slate-200 align-top">
                            <span class="text-[11px] text-slate-700">
                                <?php echo htmlspecialchars($time); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<?php
require '../layout/layout_footer.php';
