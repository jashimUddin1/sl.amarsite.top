<?php
require_once "config.php";
require_login();

$schoolId = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;
if ($schoolId <= 0) {
    die("Invalid school ID.");
}

// school name নেব
$stmtS = $pdo->prepare("SELECT school_name FROM schools WHERE id = :id");
$stmtS->execute([':id' => $schoolId]);
$school = $stmtS->fetch(PDO::FETCH_ASSOC);
$schoolName = $school['school_name'] ?? null;

// নির্দিষ্ট school_id এর সব log
$stmt = $pdo->prepare("
    SELECT 
        l.*, 
        s.school_name,
        u.name AS user_name
    FROM note_logs l
    LEFT JOIN schools s ON l.school_id = s.id
    LEFT JOIN users u   ON l.user_id   = u.id
    WHERE l.school_id = :sid
    ORDER BY l.id DESC
");
$stmt->execute([':sid' => $schoolId]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<title>Note History - School <?php echo htmlspecialchars($schoolName ?? ('#'.$schoolId)); ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen p-6">

<div class="max-w-6xl mx-auto bg-white shadow rounded-xl p-6">

    <div class="flex justify-between items-center mb-4">
        <div>
            <h1 class="text-2xl font-bold text-indigo-600">🧾 Note History</h1>
            <p class="text-sm text-gray-600 mt-1">
                School: 
                <span class="font-semibold">
                    <?php echo htmlspecialchars($schoolName ?? ('(Deleted) ID: '.$schoolId)); ?>
                </span>
            </p>
        </div>
        <a href="logs.php" class="text-indigo-600 hover:underline text-sm">◀ Back to School List</a>
    </div>

    <?php if (!$logs): ?>
        <p class="text-center text-gray-500">এই স্কুলের কোনো log পাওয়া যায়নি।</p>
    <?php else: ?>

    <div class="overflow-x-auto">
    <table class="w-full border-collapse">
        <thead>
            <tr class="bg-gray-200 text-left text-sm">
                <th class="p-2 border">Log ID</th>
                <th class="p-2 border">Action</th>
                <th class="p-2 border">User</th>
                <th class="p-2 border">Old Text</th>
                <th class="p-2 border">New Text</th>
                <th class="p-2 border">Date</th>
            </tr>
        </thead>
        <tbody>

        <?php foreach ($logs as $log): ?>
            <?php
                $color = $log['action'] === 'delete' ? 'text-red-600 font-bold'
                         : ($log['action'] === 'update' ? 'text-blue-600 font-bold'
                         : 'text-green-600 font-bold');
            ?>
            <tr class="text-sm hover:bg-gray-50">
                <td class="p-2 border"><?php echo $log['id']; ?></td>

                <td class="p-2 border <?php echo $color; ?>">
                    <?php echo ucfirst($log['action']); ?>
                </td>

                <td class="p-2 border">
                    <?php echo htmlspecialchars($log['user_name'] ?? 'Unknown'); ?>
                </td>

                <td class="p-2 border whitespace-pre-line text-gray-700">
                    <?php echo nl2br(htmlspecialchars($log['old_text'] ?? '')); ?>
                </td>

                <td class="p-2 border whitespace-pre-line text-gray-700">
                    <?php echo nl2br(htmlspecialchars($log['new_text'] ?? '')); ?>
                </td>

                <td class="p-2 border text-gray-600">
                    <?php echo htmlspecialchars($log['action_at']); ?>
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
