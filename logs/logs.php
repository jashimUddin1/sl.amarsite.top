<?php
require_once '../auth/config.php';
require_login();

$search = trim($_GET['q'] ?? '');

// school-wise logs info
$sql = "
    SELECT
        nl.school_id,
        MAX(s.school_name) AS school_name,
        MAX(CONCAT_WS(', ', s.district, s.upazila)) AS address,
        CASE WHEN MAX(s.id) IS NULL THEN 'Deactive' ELSE 'Active' END AS activity
    FROM note_logs nl
    LEFT JOIN schools s ON s.id = nl.school_id
    WHERE nl.school_id IS NOT NULL
";

$params = [];

if ($search !== '') {
    $sql .= " AND s.school_name LIKE :q";
    $params[':q'] = '%' . $search . '%';
}

$sql .= "
    GROUP BY nl.school_id
    ORDER BY nl.school_id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// layout variables
$pageTitle   = 'Logs - School List';
$pageHeading = 'Logs';
$activeMenu  = 'logs';

require '../layout/layout_header.php';
?>

<div class="bg-white rounded-xl shadow p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Search by School Name
            </label>
            <input type="text" name="q"
                   class="w-full p-2 border rounded text-sm"
                   placeholder="Type school name..."
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="flex gap-2">
            <button type="submit"
                    class="px-4 py-2 rounded bg-slate-900 text-white text-sm hover:bg-slate-800">
                Search
            </button>
            <a href="logs.php"
               class="px-4 py-2 rounded bg-slate-200 text-slate-700 text-sm hover:bg-slate-300">
                Reset
            </a>
        </div>
    </form>
    <p class="mt-2 text-[11px] text-slate-500">
        এখানে শুধুমাত্র যেসব স্কুলের note_logs আছে সেগুলোর সারসংক্ষেপ দেখানো হচ্ছে।
    </p>
</div>

<div class="bg-white rounded-xl shadow p-3 overflow-x-auto">
    <?php if (!$rows): ?>
        <p class="text-center text-gray-500 text-sm py-4">কোনো লগ পাওয়া যায়নি।</p>
    <?php else: ?>
        <table class="min-w-full text-sm border-collapse">
            <thead>
                <tr class="bg-slate-100 text-left">
                    <th class="p-2 border">ID</th>
                    <th class="p-2 border" style="min-width: 150px;">School Name</th>
                    <th class="p-2 border" style="min-width: 160px;">Address</th>
                    <th class="p-2 border">Activity</th>
                    <th class="p-2 border">History</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php
                    $schoolId   = (int)$r['school_id'];
                    $schoolName = $r['school_name'] ?? '(Deleted School #' . $schoolId . ')';
                    $address    = $r['address'] ?: 'N/A';
                    $activity   = $r['activity'] ?? 'Deactive';
                    $activityClass = ($activity === 'Active') ? 'text-green-600' : 'text-red-600';
                    ?>
                    <tr class="hover:bg-slate-50">
                        <td class="p-2 border align-top"><?php echo $schoolId; ?></td>
                        <td class="p-2 border align-top font-semibold">
                            <?php echo htmlspecialchars($schoolName); ?>
                        </td>
                        <td class="p-2 border align-top text-xs text-slate-700">
                            <?php echo htmlspecialchars($address); ?>
                        </td>
                        <td class="p-2 border align-top">
                            <span class="text-xs font-semibold <?php echo $activityClass; ?>">
                                <?php echo htmlspecialchars($activity); ?>
                            </span>
                        </td>
                        <td class="p-2 border align-top">
                            <a href="logs_history.php?school_id=<?php echo $schoolId; ?>"
                               class="px-3 py-1 rounded bg-blue-600 text-white text-xs hover:bg-blue-700">
                                View History
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
require '../layout/layout_footer.php';
