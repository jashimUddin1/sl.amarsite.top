<?php // extra/actions.php
require_once '../auth/config.php';
require_login();

// ====== Fetch action-wise counts ======
$sql = "
    SELECT action, COUNT(*) AS total
    FROM note_logs
    GROUP BY action
";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ====== Total logs (for percentage) ======
$totalLogs = array_sum(array_column($rows, 'total'));

// ====== Action meta: group + order + color ======
function actionMeta(string $action): array
{
    $a = strtolower($action);

    // ---- GROUP ORDER ----
    if (str_contains($a, 'school'))   $group = 1;
    elseif (str_contains($a, 'note')) $group = 2;
    elseif (str_contains($a, 'invoice')) $group = 3;
    elseif (str_contains($a, 'account')) $group = 4;
    else $group = 99;

    // ---- ACTION ORDER ----
    if (str_contains($a, 'create') || str_contains($a, 'add')) $order = 1;
    elseif (str_contains($a, 'update') || str_contains($a, 'edit')) $order = 2;
    elseif (str_contains($a, 'delete')) $order = 3;
    else $order = 9;

    // ---- COLOR ----
    if ($group === 1) $color = '#2563eb';      // School - blue
    elseif ($group === 2) $color = '#16a34a';  // Note - green
    elseif ($group === 3) $color = '#9333ea';  // Invoice - purple
    elseif ($group === 4) $color = '#ea580c';  // Accounts - orange
    else $color = '#64748b';

    return [$group, $order, $color];
}

// ====== Sort rows by required serial ======
usort($rows, function($x, $y){
    [$gx, $ox] = actionMeta($x['action'] ?? '');
    [$gy, $oy] = actionMeta($y['action'] ?? '');

    if ($gx !== $gy) return $gx <=> $gy;
    return $ox <=> $oy;
});
?>
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<title>Note Logs Action Summary</title>

<style>
body{
  font-family: Arial, sans-serif;
  padding:18px;
  background:#f8fafc;
}

/* ===== GRID ===== */
ul{
  list-style:none;
  padding:0;
  max-width:1100px;
  margin:0 auto;

  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));
  gap:12px;
}

/* ===== CARD ===== */
li{
  background:#fff;
  border:1px solid #e5e7eb;
  border-radius:12px;
  padding:14px;
  display:flex;
  flex-direction:column;
  gap:8px;
}

/* ===== TEXT ===== */
.action{
  font-size:14px;
  font-weight:600;
  color:#0f172a;
}

.count{
  font-size:26px;
  font-weight:bold;
}

/* ===== PROGRESS ===== */
.progress{
  height:8px;
  background:#e5e7eb;
  border-radius:999px;
  overflow:hidden;
}
.progress span{
  display:block;
  height:100%;
  border-radius:999px;
}

/* ===== BUTTON ===== */
.btn{
  margin-top:auto;
  display:inline-block;
  text-align:center;
  padding:8px 10px;
  border-radius:8px;
  color:#fff;
  text-decoration:none;
  font-size:13px;
}
.muted{
  color:#64748b;
  font-size:12px;
}
</style>
</head>
<body>

<h2 style="text-align:center;margin-bottom:14px;">
  📊 note_logs → Action Summary
</h2>

<?php if(!$rows): ?>
  <p style="text-align:center;color:#666;">কোনো ডাটা পাওয়া যায়নি।</p>
<?php else: ?>
<ul>
  <?php foreach($rows as $r): 
    $action = $r['action'] ?? '';
    $count  = (int)($r['total'] ?? 0);

    [$g, $o, $color] = actionMeta($action);
    $percent = $totalLogs > 0 ? round(($count / $totalLogs) * 100) : 0;
  ?>
    <li>
      <div class="action"><?= h($action) ?></div>

      <div class="count" style="color:<?= $color ?>">
        <?= $count ?>
      </div>

      <div class="progress">
        <span style="width:<?= $percent ?>%; background:<?= $color ?>"></span>
      </div>

      <div class="muted">
        <?= $percent ?>% of total logs
      </div>

      <a class="btn"
         style="background:<?= $color ?>"
         href="logs.php?action=<?= urlencode($action) ?>">
         View Logs
      </a>
    </li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>

</body>
</html>
