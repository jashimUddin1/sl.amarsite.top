<?php
require_once "config.php";
require_login();

// Search input
$q = trim($_GET['q'] ?? '');

// Distinct school list from note_logs + schools
// (যে যে school_id কখনও logs এ এসেছে, তাদের activity দেখি)
$sql = "
    SELECT 
        l.school_id AS id,
        s.school_name,
        s.district,
        s.upazila,
        CASE 
            WHEN s.id IS NULL THEN 'Deactive'
            ELSE 'Active'
        END AS activity
    FROM note_logs l
    LEFT JOIN schools s ON l.school_id = s.id
    WHERE l.school_id IS NOT NULL
";

$params = [];

if ($q !== '') {
    // শুধুমাত্র নাম match হলে show
    $sql .= " AND s.school_name LIKE :q";
    $params[':q'] = '%' . $q . '%';
}

$sql .= "
    GROUP BY l.school_id, s.school_name, s.district, s.upazila, s.id
    ORDER BY id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<title>School Activity Logs</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen p-6">

<div class="max-w-5xl mx-auto bg-white shadow rounded-xl p-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-indigo-600">📚 School Activity</h1>
        <a href="index.php" class="text-indigo-600 hover:underline text-sm">◀ Back to Dashboard</a>
    </div>

    <!-- Search Form -->
    <form method="GET" class="mb-4 flex gap-2">
        <input
            type="text"
            name="q"
            value="<?php echo htmlspecialchars($q); ?>"
            placeholder="Search by school name..."
            class="flex-1 p-2 border rounded"
        >
        <button
            type="submit"
            class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700"
        >
            Search
        </button>
        <?php if ($q !== ''): ?>
            <a href="logs.php" class="px-3 py-2 text-sm bg-gray-200 rounded hover:bg-gray-300">
                Reset
            </a>
        <?php endif; ?>
    </form>

    <?php if (!$schools): ?>
        <p class="text-center text-gray-500">কোনো school activity পাওয়া যায়নি।</p>
    <?php else: ?>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="bg-gray-200 text-left">
                    <th class="p-2 border">ID</th>
                    <th class="p-2 border">School Name</th>
                    <th class="p-2 border">Address</th>
                    <th class="p-2 border">Activity</th>
                    <th class="p-2 border">History</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schools as $row): ?>
                    <?php
                        $id      = (int)$row['id'];
                        $name    = $row['school_name'] ?? null;
                        $district= $row['district'] ?? '';
                        $upazila = $row['upazila'] ?? '';

                        $address = trim($district . ($district && $upazila ? ', ' : '') . $upazila);
                        if ($address === '') {
                            $address = 'N/A';
                        }

                        $isActive = $row['activity'] === 'Active';
                        $activityClass = $isActive ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold';
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="p-2 border"><?php echo $id; ?></td>
                        <td class="p-2 border">
                            <?php echo htmlspecialchars($name ?? 'N/A (Deleted)'); ?>
                        </td>
                        <td class="p-2 border">
                            <?php echo htmlspecialchars($address); ?>
                        </td>
                        <td class="p-2 border <?php echo $activityClass; ?>">
                            <?php echo $isActive ? 'Active' : 'Deactive'; ?>
                        </td>
                        <td class="p-2 border">
                            <a
                                href="logs_history.php?school_id=<?php echo $id; ?>"
                                class="px-3 py-1 inline-block bg-blue-600 text-white rounded hover:bg-blue-700 text-xs"
                            >
                                History
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>
</div>

</body>
</html>
