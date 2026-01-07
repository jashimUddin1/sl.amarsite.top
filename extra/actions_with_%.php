<?php
require_once '../auth/config.php';
require_login();

// ====== Fetch action-wise counts ======
$sql = "
    SELECT action, COUNT(*) AS total
    FROM note_logs
    GROUP BY action
    ORDER BY total DESC, action ASC
";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ====== Total logs (for percentage) ======
$grandTotal = 0;
foreach ($rows as $r) {
    $grandTotal += (int)($r['total'] ?? 0);
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ====== Action-wise color map ======
function actionColor(string $action): array {
    $a = strtolower($action);

    if (str_contains($a, 'delete'))  return ['#dc2626', '#fee2e2']; // red
    if (str_contains($a, 'restore')) return ['#16a34a', '#dcfce7']; // green
    if (str_contains($a, 'update'))  return ['#2563eb', '#dbeafe']; // blue
    if (str_contains($a, 'create') || str_contains($a, 'add'))
        return ['#7c3aed', '#ede9fe']; // purple
    if (str_contains($a, 'invoice')) return ['#ea580c', '#ffedd5']; // orange

    return ['#475569', '#f1f5f9']; // default slate
}
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
  gap:14px;
}

/* ===== CARD ===== */
li{
  background:#fff;
  border:1px solid #e5e7eb;
  border-radius:14px;
  padding:14px;
  display:flex;
  flex-direction:column;
  gap:10px;
}

/* ===== TEXT ===== */
.action{
  font-size:14px;
  font-weight:600;
}

.count{
  font-size:26px;
  font-weight:bold;
}

.percent{
  font-size:12px;
  font-weight:600;
}

/* ===== PROGRESS ===== */
.progress{
  height:8px;
  border-radius:999px;
  background:#e5e7eb;
  overflow:hidden;
}
.progress-bar{
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
.btn:hover{ opacity:.9 }

.muted{
  font-size:12px;
  color:#64748b;
}
</style>
</head>
<body>

<h2 style="text-align:center;margin-bottom:16px;">
  📊 note_logs → Action Summary
</h2>

<p style="text-align:center;font-size:13px;color:#64748b;margin-bottom:18px;">
  Total logs: <b><?= $grandTotal ?></b>
</p>

<?php if(!$rows): ?>
  <p style="text-align:center;color:#666;">কোনো ডাটা পাওয়া যায়নি।</p>
<?php else: ?>
<ul>
<?php foreach($rows as $r):
    $action = (string)($r['action'] ?? '');
    $count  = (int)($r['total'] ?? 0);
    $percent = $grandTotal > 0 ? round(($count / $grandTotal) * 100, 1) : 0;

    [$mainColor, $bgColor] = actionColor($action);
?>
  <li style="border-top:4px solid <?= $mainColor ?>">

    <div class="action" style="color:<?= $mainColor ?>">
      <?= h($action) ?>
    </div>

    <div class="count" style="color:<?= $mainColor ?>">
      <?= $count ?>
    </div>

    <div class="percent" style="color:<?= $mainColor ?>">
      <?= $percent ?>%
    </div>

    <!-- Progress bar -->
    <div class="progress">
      <div class="progress-bar"
           style="width:<?= $percent ?>%; background:<?= $mainColor ?>;">
      </div>
    </div>

    <div class="muted">
      of <?= $grandTotal ?> total logs
    </div>

    <a class="btn"
       style="background:<?= $mainColor ?>;"
       href="logs.php?action=<?= urlencode($action) ?>">
       View Logs
    </a>

  </li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

</body>
</html>
