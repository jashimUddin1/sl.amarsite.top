<?php
require_once '../auth/config.php';
require_login();

$search = trim($_GET['q'] ?? '');

/*
|--------------------------------------------------------------------------
| 1) school_trash map (school_id => name, address, photo)
|--------------------------------------------------------------------------
*/
$trashMap = [];

$st = $pdo->query("
    SELECT school_id, school_name, district, upazila, photo_path
    FROM school_trash
    ORDER BY id DESC
");

foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $t) {
    $sid = (int)($t['school_id'] ?? 0);
    if ($sid <= 0) continue;

    if (!isset($trashMap[$sid])) {
        $trashMap[$sid] = [
            'school_name' => $t['school_name'] ?? null,
            'address' => trim(
                ($t['district'] ?? '') .
                (($t['district'] && $t['upazila']) ? ', ' : '') .
                ($t['upazila'] ?? '')
            ),
            'photo_path' => $t['photo_path'] ?? null,
        ];
    }
}

/*
|--------------------------------------------------------------------------
| 2) helper: old_text (JSON) থেকে school info বের করা
|--------------------------------------------------------------------------
*/
function extractSchoolFromLogOldText(?string $oldText): ?array
{
    if (!$oldText) return null;

    $json = json_decode($oldText, true);
    if (!is_array($json)) return null;

    if (!isset($json['school_trash']) || !is_array($json['school_trash'])) return null;

    $st = $json['school_trash'];

    return [
        'school_name' => $st['school_name'] ?? null,
        'address' => trim(
            ($st['district'] ?? '') .
            (($st['district'] && $st['upazila']) ? ', ' : '') .
            ($st['upazila'] ?? '')
        ),
        'photo_path' => $st['photo_path'] ?? null,
    ];
}

/*
|--------------------------------------------------------------------------
| 3) note_logs.old_text map (school_id => name, address, photo)
|--------------------------------------------------------------------------
*/
$logMap = [];

$lg = $pdo->query("
    SELECT school_id, old_text
    FROM note_logs
    WHERE school_id IS NOT NULL
      AND old_text IS NOT NULL
    ORDER BY id DESC
");

foreach ($lg->fetchAll(PDO::FETCH_ASSOC) as $l) {
    $sid = (int)($l['school_id'] ?? 0);
    if ($sid <= 0) continue;

    if (!isset($logMap[$sid])) {
        $parsed = extractSchoolFromLogOldText($l['old_text'] ?? null);
        if ($parsed) {
            $logMap[$sid] = $parsed;
        }
    }
}

/*
|--------------------------------------------------------------------------
| 4) main logs list (schools join আগের মতোই) + photo_path যোগ
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        nl.school_id,
        MAX(s.school_name) AS school_name,
        MAX(CONCAT_WS(', ', s.district, s.upazila)) AS address,
        MAX(s.photo_path) AS photo_path,
        CASE WHEN MAX(s.id) IS NULL THEN 'Deactive' ELSE 'Active' END AS activity
    FROM note_logs nl
    LEFT JOIN schools s ON s.id = nl.school_id
    WHERE nl.school_id IS NOT NULL
    GROUP BY nl.school_id
    ORDER BY nl.school_id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// layout variables
$pageTitle   = 'Logs - School List';
$pageHeading = 'Logs';
$activeMenu  = 'logs';

require '../layout/layout_header.php';
?>

<style>
/* simple image viewer (no bootstrap required) */
.imgviewer-backdrop{
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.65);
  display: none;
  align-items: center;
  justify-content: center;
  padding: 16px;
  z-index: 9999;
}
.imgviewer-backdrop.show{ display: flex; }
.imgviewer-box{
  background: #fff;
  border-radius: 10px;
  max-width: 900px;
  width: 100%;
  overflow: hidden;
}
.imgviewer-head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:10px 12px;
  border-bottom: 1px solid #e5e7eb;
}
.imgviewer-body{
  padding: 10px;
}
.imgviewer-body img{
  width: 100%;
  height: auto;
  display:block;
}
.thumb-img{
  width: 44px;
  height: 28px;
  object-fit: cover;
  border-radius: 4px;
  border: 1px solid #e5e7eb;
  cursor: pointer;
}
</style>

<div class="bg-white rounded-xl shadow p-4 mb-4">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
        <div class="flex-1 min-w-[220px]">
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Search (ID / Name / Address)
            </label>
            <input type="text" id="liveSearch"
                   class="w-full p-2 border rounded text-sm"
                   placeholder="Type id, school name, or address..."
                   value="<?= htmlspecialchars($search); ?>">
            <p class="mt-2 text-[11px] text-slate-500">
                Total: <span id="totalCount"><?= (int)count($rows) ?></span> |
                Matched: <span id="matchedCount"><?= (int)count($rows) ?></span>
            </p>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow p-3 overflow-x-auto">
<?php if (!$rows): ?>
    <p class="text-center text-gray-500 text-sm py-4">কোনো লগ পাওয়া যায়নি।</p>
<?php else: ?>
    <table class="min-w-full text-sm border-collapse" id="logsTable">
        <thead>
            <tr class="bg-slate-100 text-left">
                <th class="p-2 border">Photo</th>
                <th class="p-2 border">ID</th>
                <th class="p-2 border" style="min-width: 160px;">School Name</th>
                <th class="p-2 border" style="min-width: 200px;">Address</th>
                <th class="p-2 border">Activity</th>
                <th class="p-2 border">History</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <?php
            $schoolId = (int)($r['school_id'] ?? 0);

            // fallback
            $schoolName = $r['school_name'] ?? null;
            $address    = $r['address'] ?? null;
            $photoPath  = $r['photo_path'] ?? null;

            if (!$schoolName && isset($trashMap[$schoolId])) {
                $schoolName = $trashMap[$schoolId]['school_name'] ?? null;
                $address    = $trashMap[$schoolId]['address'] ?? null;
                $photoPath  = $trashMap[$schoolId]['photo_path'] ?? $photoPath;
            }

            if (!$schoolName && isset($logMap[$schoolId])) {
                $schoolName = $logMap[$schoolId]['school_name'] ?? null;
                $address    = $logMap[$schoolId]['address'] ?? null;
                $photoPath  = $logMap[$schoolId]['photo_path'] ?? $photoPath;
            }

            if (!$schoolName) $schoolName = 'School #' . $schoolId;
            if (!$address) $address = 'N/A';

            $activity = $r['activity'] ?? 'Deactive';
            $activityClass = ($activity === 'Active') ? 'text-green-600' : 'text-red-600';

            // for JS search (id + name + address)
            $searchBlob = strtolower(trim($schoolId . ' ' . $schoolName . ' ' . $address));
            ?>
            <tr class="hover:bg-slate-50 js-row"
                data-search="<?= htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8'); ?>">
                <td class="p-2 border align-top">
                    <?php if (!empty($photoPath)): ?>
                        <img
                            src="../<?= htmlspecialchars($photoPath); ?>"
                            class="thumb-img js-thumb"
                            data-full="../<?= htmlspecialchars($photoPath); ?>"
                            alt="photo"
                        >
                    <?php else: ?>
                        <span class="text-xs text-slate-400">—</span>
                    <?php endif; ?>
                </td>

                <td class="p-2 border align-top"><?= (int)$schoolId; ?></td>

                <td class="p-2 border align-top font-semibold">
                    <?= htmlspecialchars($schoolName); ?>
                </td>

                <td class="p-2 border align-top text-xs text-slate-700">
                    <?= htmlspecialchars($address); ?>
                </td>

                <td class="p-2 border align-top">
                    <span class="text-xs font-semibold <?= $activityClass; ?>">
                        <?= htmlspecialchars($activity); ?>
                    </span>
                </td>

                <td class="p-2 border align-top">
                    <a href="logs_history.php?school_id=<?= (int)$schoolId; ?>"
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

<!-- Image Viewer -->
<div class="imgviewer-backdrop" id="imgViewer">
  <div class="imgviewer-box">
    <div class="imgviewer-head">
      <div class="text-sm font-semibold text-slate-800">Photo Preview</div>
      <button type="button" id="imgViewerClose"
              class="px-3 py-1 rounded bg-slate-200 text-slate-800 text-sm">
        Close
      </button>
    </div>
    <div class="imgviewer-body">
      <img src="" alt="preview" id="imgViewerImg">
    </div>
  </div>
</div>

<script>
(function(){
  const input = document.getElementById('liveSearch');
  const rows  = document.querySelectorAll('.js-row');
  const matchedEl = document.getElementById('matchedCount');
  const totalEl   = document.getElementById('totalCount');

  // total
  if (totalEl) totalEl.textContent = rows.length;

  function applyFilter(){
    const q = (input?.value || '').trim().toLowerCase();
    let matched = 0;

    rows.forEach(tr => {
      const blob = (tr.getAttribute('data-search') || '');
      const ok = (q === '') ? true : blob.includes(q);
      tr.style.display = ok ? '' : 'none';
      if (ok) matched++;
    });

    if (matchedEl) matchedEl.textContent = matched;

    // URL-এ q রাখবে (optional, refresh করলে text থাকবে)
    const url = new URL(window.location.href);
    if (q) url.searchParams.set('q', input.value.trim());
    else url.searchParams.delete('q');
    window.history.replaceState({}, '', url.toString());
  }

  // debounce
  let t = null;
  if (input){
    input.addEventListener('input', function(){
      clearTimeout(t);
      t = setTimeout(applyFilter, 150);
    });
  }

  // initial filter if q exists
  applyFilter();

  // image viewer
  const viewer = document.getElementById('imgViewer');
  const viewerImg = document.getElementById('imgViewerImg');
  const closeBtn = document.getElementById('imgViewerClose');

  document.addEventListener('click', function(e){
    const thumb = e.target.closest('.js-thumb');
    if (thumb){
      const full = thumb.getAttribute('data-full');
      if (viewerImg && full) viewerImg.src = full;
      if (viewer) viewer.classList.add('show');
    }
  });

  function closeViewer(){
    if (viewer) viewer.classList.remove('show');
    if (viewerImg) viewerImg.src = '';
  }

  if (closeBtn) closeBtn.addEventListener('click', closeViewer);
  if (viewer) viewer.addEventListener('click', function(e){
    if (e.target === viewer) closeViewer();
  });

  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') closeViewer();
  });
})();
</script>

<?php
require '../layout/layout_footer.php';
