<?php
require_once '../auth/config.php';
require_login();

/*
|--------------------------------------------------------------------------
| 1) Action summary from DB
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT action, COUNT(*) AS total
    FROM note_logs
    GROUP BY action
";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| 2) Helper
|--------------------------------------------------------------------------
*/
function h($s){
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| 3) Action → Group + Order mapping
|--------------------------------------------------------------------------
| group order:
| 1 = School
| 2 = Note
| 3 = Invoice
| 4 = Accounts
*/
function actionMeta(string $action): array
{
    $a = strtolower($action);

    // group
    if (str_contains($a, 'school'))      $group = 1;
    elseif (str_contains($a, 'note'))    $group = 2;
    elseif (str_contains($a, 'invoice')) $group = 3;
    elseif (str_contains($a, 'account')) $group = 4;
    else $group = 99;

    // order inside group
    if (str_contains($a, 'create') || str_contains($a, 'add'))      $order = 1;
    elseif (str_contains($a, 'update') || str_contains($a, 'edit')) $order = 2;
    elseif (str_contains($a, 'delete'))                              $order = 3;
    else $order = 9;

    return [$group, $order];
}

/*
|--------------------------------------------------------------------------
| 4) Sort rows (custom logical order)
|--------------------------------------------------------------------------
*/
usort($rows, function($x, $y){
    [$gx, $ox] = actionMeta($x['action']);
    [$gy, $oy] = actionMeta($y['action']);

    if ($gx !== $gy) return $gx <=> $gy;
    return $ox <=> $oy;
});

/*
|--------------------------------------------------------------------------
| 5) Total count
|--------------------------------------------------------------------------
*/
$grandTotal = array_sum(array_column($rows, 'total'));

/*
|--------------------------------------------------------------------------
| 6) Action colors
|--------------------------------------------------------------------------
*/
function actionColor(string $action): string
{
    $a = strtolower($action);

    if (str_contains($a, 'create') || str_contains($a, 'add')) {
        return '#16a34a'; // green
    }
    if (str_contains($a, 'update') || str_contains($a, 'edit')) {
        return '#2563eb'; // blue
    }
    if (str_contains($a, 'delete')) {
        return '#dc2626'; // red
    }
    return '#6b7280'; // gray
}
?>
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<title>Logs Action Summary</title>

<style>
body{
    font-family: system-ui, Arial, sans-serif;
    padding:18px;
    background:#f8fafc;
}

h2{
    margin-bottom:6px;
}

.muted{
    font-size:13px;
    color:#6b7280;
    margin-bottom:14px;
}

ul{
    list-style:none;
    padding:0;
    max-width:1100px;
    display:grid;
    grid-template-columns:repeat(4, 1fr);
    gap:12px;
}

li{
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:14px;
    box-shadow:0 2px 6px rgba(0,0,0,.04);
}

.action-title{
    font-size:14px;
    font-weight:600;
    margin-bottom:6px;
}

.count{
    font-size:13px;
    color:#374151;
    margin-bottom:6px;
}

.progress-wrap{
    background:#e5e7eb;
    border-radius:999px;
    height:10px;
    overflow:hidden;
}

.progress{
    height:100%;
    border-radius:999px;
}

.percent{
    font-size:12px;
    margin-top:4px;
    color:#374151;
    text-align:right;
}
</style>
</head>

<body>

<h2>📊 Logs Action Summary</h2>
<div class="muted">
    মোট লগ: <?= h($grandTotal) ?>
</div>

<?php if (!$rows): ?>
    <p>কোনো ডাটা পাওয়া যায়নি।</p>
<?php else: ?>
<ul>
<?php foreach ($rows as $r): 
    $action = $r['action'];
    $count  = (int)$r['total'];
    $percent = $grandTotal > 0 ? round(($count / $grandTotal) * 100, 2) : 0;
    $color = actionColor($action);
?>
    <li>
        <div class="action-title"><?= h($action) ?></div>

        <div class="count">
            <?= h($count) ?> বার
        </div>

        <div class="progress-wrap">
            <div class="progress"
                 style="width:<?= $percent ?>%; background:<?= $color ?>;">
            </div>
        </div>

        <div class="percent"><?= h($percent) ?>%</div>
    </li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

</body>
</html>
