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

/* ===============================
   GROUP + CRUD ORDER SORTING
================================ */
function detectGroup(string $action): string {
    $a = strtolower(trim($action));
    if (strpos($a, 'school') !== false)  return 'School';
    if (strpos($a, 'note') !== false)    return 'Note';
    if (strpos($a, 'invoice') !== false) return 'Invoice';
    if (strpos($a, 'account') !== false) return 'Accounts';
    return 'Other';
}

function detectCrudOrder(string $action): int {
    $a = strtolower(trim($action));

    if (strpos($a, 'create') !== false || strpos($a, 'add') !== false) return 1;
    if (strpos($a, 'update') !== false || strpos($a, 'edit') !== false) return 2;
    if (strpos($a, 'delete') !== false || strpos($a, 'remove') !== false) return 3;

    return 9;
}

$groupPriority = [
    'School'   => 1,
    'Note'     => 2,
    'Invoice'  => 3,
    'Accounts' => 4,
    'Other'    => 99
];

usort($rows, function ($x, $y) use ($groupPriority) {
    $ax = $x['action'] ?? '';
    $ay = $y['action'] ?? '';

    $gx = detectGroup($ax);
    $gy = detectGroup($ay);

    // group order
    $gcmp = ($groupPriority[$gx] ?? 999) <=> ($groupPriority[$gy] ?? 999);
    if ($gcmp !== 0) return $gcmp;

    // CRUD order
    $cx = detectCrudOrder($ax);
    $cy = detectCrudOrder($ay);
    if ($cx !== $cy) return $cx <=> $cy;

    // final fallback
    return strcasecmp($ax, $ay);
});

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<title>Note Logs Action Summary</title>

<style>
body{
  font-family: Arial, sans-serif;
  padding: 18px;
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
  color:#2563eb;
}

.muted{
  color:#64748b;
  font-size:12px;
}

/* ===== BUTTON ===== */
.btn{
  margin-top:auto;
  display:inline-block;
  text-align:center;
  padding:8px 10px;
  border-radius:8px;
  background:#2563eb;
  color:#fff;
  text-decoration:none;
  font-size:13px;
}
.btn:hover{
  background:#1d4ed8;
}
</style>
</head>
<body>

<h2 style="text-align:center;margin-bottom:14px;">
  note_logs → Action Summary
</h2>

<?php if(!$rows): ?>
  <p style="text-align:center;color:#666;">কোনো ডাটা পাওয়া যায়নি।</p>
<?php else: ?>
<ul>
  <?php foreach($rows as $r): 
    $action = $r['action'] ?? '';
    $count  = (int)($r['total'] ?? 0);
  ?>
    <li>
      <div class="action"><?= h($action) ?></div>
      <div class="count"><?= $count ?></div>
      <div class="muted">Total logs</div>

      <a class="btn" href="logs.php?action=<?= urlencode($action) ?>">
        View Logs
      </a>
    </li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>

</body>
</html>
