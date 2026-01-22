<?php
require_once '../auth/config.php';
require_login();

$range = $_GET['range'] ?? 'this_month';
$from  = $_GET['from'] ?? '';
$to    = $_GET['to'] ?? '';
$category = $_GET['category'] ?? '';

$allowedCategories = ['Raja', 'Yasin'];
if (!in_array($category, $allowedCategories, true)) {
    $category = 'Raja';
}

$isValidDate = function (string $d): bool {
    return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
};

$whereAcc = "1=1";

if ($range === 'today') {
    $whereAcc = "`date` = CURDATE()";
} elseif ($range === 'this_month') {
    $whereAcc = "DATE_FORMAT(`date`, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
} elseif ($range === 'this_year') {
    $whereAcc = "YEAR(`date`) = YEAR(CURDATE())";
} elseif ($range === 'last_year') {
    $whereAcc = "YEAR(`date`) = YEAR(CURDATE()) - 1";
} elseif ($range === 'custom') {
    if ($isValidDate($from) && $isValidDate($to)) {
        $whereAcc = "`date` BETWEEN :from AND :to";
    } else {
        $range = 'this_month';
        $whereAcc = "DATE_FORMAT(`date`, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
    }
}

// ===== Fetch rows =====
$rows = [];
$totalAmount = 0;

try {
    $sql = "
        SELECT id, date, description, method, amount, category, type, created_at
        FROM accounts
        WHERE $whereAcc
          AND type = 'expense'
          AND category = :category
        ORDER BY date DESC, id DESC
    ";

    $stmt = $pdo->prepare($sql);

    if ($range === 'custom' && strpos($whereAcc, ':from') !== false) {
        $stmt->bindValue(':from', $from);
        $stmt->bindValue(':to', $to);
    }

    $stmt->bindValue(':category', $category);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as $r) {
        $totalAmount += (float) ($r['amount'] ?? 0);
    }
} catch (Exception $e) {
    $rows = [];
}

$pageTitle = $category . " Withdraw Details";
$pageHeading = $category . " Withdraw Details";
$activeMenu = 'dashboard';

require '../layout/layout_header.php';
?>

<div class="bg-white rounded-xl shadow p-4 mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-base font-semibold text-slate-800">
           <a href="dashboard.php?range=<?= urlencode($range) ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>"
           class="text-sm text-indigo-600 hover:underline">
            ← Back to Dashboard
        </a>
        </h2>

        <div class="text-sm text-slate-600">
            Total: <span class="font-semibold">৳ <?= number_format($totalAmount, 0) ?></span>
        </div>
    </div>

    <div class="mt-3 overflow-x-auto">
        <table class="min-w-full text-sm border-collapse">
            <thead>
                <tr class="bg-slate-100 text-left">
                    <th class="p-2 border">Date</th>
                    <th class="p-2 border">Description</th>
                    <th class="p-2 border">Method</th>
                    <th class="p-2 border text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="4" class="p-3 border text-slate-500">
                            No records found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="p-2 border"><?= htmlspecialchars($r['date'] ?? '') ?></td>
                            <td class="p-2 border"><?= htmlspecialchars($r['description'] ?? '') ?></td>
                            <td class="p-2 border"><?= htmlspecialchars($r['method'] ?? '') ?></td>
                            <td class="p-2 border text-right font-semibold">
                                ৳ <?= number_format((float)($r['amount'] ?? 0), 0) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require '../layout/layout_footer.php'; ?>
