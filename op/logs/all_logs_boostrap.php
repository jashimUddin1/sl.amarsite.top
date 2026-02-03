<?php
// logs/logs.php
require_once '../auth/config.php';
require_login();

/**
 * note_logs columns:
 * id, note_id, school_id, user_id, action, old_text, new_text, action_at
 */

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ---------- Filters ----------
$q        = trim($_GET['q'] ?? '');
$actionF  = trim($_GET['action'] ?? '');
$schoolId = trim($_GET['school_id'] ?? '');
$userId   = trim($_GET['user_id'] ?? '');
$from     = trim($_GET['from'] ?? '');
$to       = trim($_GET['to'] ?? '');

$isValidDate = function(string $d): bool {
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
};

// ---------- Pagination ----------
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 30;
$offset   = ($page - 1) * $perPage;

// ---------- Action groups (for label when school_name missing) ----------
$accountsActions = ['Entry add', 'Entry Updated', 'Entry delete'];
$invoiceActions  = ['Simple Invoice Created', 'INVOICE UPDATED', 'Invoice delete'];

// ---------- Build WHERE ----------
$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(nl.action LIKE :q OR nl.old_text LIKE :q OR nl.new_text LIKE :q OR s.school_name LIKE :q OR u.name LIKE :q)";
    $params[':q'] = "%{$q}%";
}
if ($actionF !== '') {
    $where[] = "nl.action = :action";
    $params[':action'] = $actionF;
}
if ($schoolId !== '' && ctype_digit($schoolId)) {
    $where[] = "nl.school_id = :school_id";
    $params[':school_id'] = (int)$schoolId;
}
if ($userId !== '' && ctype_digit($userId)) {
    $where[] = "nl.user_id = :user_id";
    $params[':user_id'] = (int)$userId;
}
if ($from !== '' && $isValidDate($from)) {
    $where[] = "DATE(nl.action_at) >= :from";
    $params[':from'] = $from;
}
if ($to !== '' && $isValidDate($to)) {
    $where[] = "DATE(nl.action_at) <= :to";
    $params[':to'] = $to;
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

// ---------- Get distinct actions for dropdown ----------
$actions = [];
try {
    $stmtA = $pdo->query("SELECT action, COUNT(*) total FROM note_logs GROUP BY action ORDER BY total DESC, action ASC");
    $actions = $stmtA->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $actions = [];
}

// ---------- Total count ----------
$totalRows = 0;
try {
    $stmtC = $pdo->prepare("
        SELECT COUNT(*)
        FROM note_logs nl
        LEFT JOIN schools s ON nl.school_id = s.id
        LEFT JOIN users  u ON nl.user_id   = u.id
        $whereSql
    ");
    $stmtC->execute($params);
    $totalRows = (int)($stmtC->fetchColumn() ?? 0);
} catch (Exception $e) {
    $totalRows = 0;
}

$totalPages = max(1, (int)ceil($totalRows / $perPage));

// ---------- Fetch logs (latest first) ----------
$logs = [];
try {
    $sql = "
        SELECT
            nl.id, nl.note_id, nl.school_id, nl.user_id,
            nl.action, nl.old_text, nl.new_text, nl.action_at,
            s.school_name,
            u.name AS user_name
        FROM note_logs nl
        LEFT JOIN schools s ON nl.school_id = s.id
        LEFT JOIN users  u ON nl.user_id   = u.id
        $whereSql
        ORDER BY nl.action_at DESC, nl.id DESC
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $logs = [];
}

// ---------- Layout vars ----------
$pageTitle   = 'Logs - School List';
$pageHeading = 'Logs';
$activeMenu  = 'logs';

require '../layout/layout_header.php';

// helper: build query string for pagination links
function build_qs(array $extra = []) {
    $q = array_merge($_GET, $extra);
    return http_build_query($q);
}
?>

<!-- Filters Card -->
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
      <h5 class="mb-0">All Logs</h5>
      <div class="text-muted small">Total: <?= (int)$totalRows ?></div>
    </div>

    <form method="get" class="row g-2">
      <div class="col-4 col-md-2">
        <input type="text" name="q" value="<?= h($q) ?>" class="form-control"
               placeholder="action/text/school/user">
      </div>

      <div class="col-4 col-md-2">
        <select name="action" class="form-select">
          <option value="">All Actions</option>
          <?php foreach ($actions as $a): ?>
            <?php $val = (string)($a['action'] ?? ''); if ($val === '') continue; ?>
            <option value="<?= h($val) ?>" <?= $actionF === $val ? 'selected' : '' ?>>
              <?= h($val) ?> (<?= (int)($a['total'] ?? 0) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-4 col-md-2">
        <input type="number" name="school_id" value="<?= h($schoolId) ?>" class="form-control" placeholder="School ID">
      </div>

      <div class="col-6 col-md-2">
        <input type="number" name="user_id" value="<?= h($userId) ?>" class="form-control" placeholder="User ID">
      </div>

      <div class="col-6 col-md-1">
        <input type="date" name="from" value="<?= h($from) ?>" class="form-control" title="From">
      </div>
      <div class="col-6 col-md-1">
        <input type="date" name="to" value="<?= h($to) ?>" class="form-control" title="To">
      </div>

      <div class="col-12 col-md-2 d-flex gap-2 mt-1">
        <button class="btn btn-primary btn-sm text-center align-items-center">Apply</button>
        <a href="<?= h($_SERVER['PHP_SELF']) ?>" class="btn btn-outline-secondary btn-sm text-center align-items-center">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Logs Table Card -->
<div class="card shadow-sm">
  <div class="card-body">
    <?php if (!$logs): ?>
      <div class="alert alert-secondary mb-0">কোনো লগ পাওয়া যায়নি।</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:60px;">#</th>
              <th style="width:170px;">When</th>
              <th>School/Type</th>
              <th style="width:200px;">Action</th>
              <th style="width:170px;">By</th>
              <th style="width:90px;">Note ID</th>
              <th style="min-width:260px;">Changes</th>
            </tr>
          </thead>

          <tbody>
          <?php foreach ($logs as $i => $log): ?>
            <?php
              $actionRaw = $log['action'] ?? '';
              $action = trim($actionRaw);

              $isAccounts = in_array($action, $accountsActions, true);
              $isInvoice  = in_array($action, $invoiceActions, true);

              $schoolLabel = $log['school_name'] ?? ($isAccounts ? 'Accounts' : ($isInvoice ? 'Invoices' : 'Activity'));
              $actionLabel = $action !== '' ? ucwords(strtolower($action)) : 'Activity';

              // Bootstrap badge color
              $badgeClass = 'bg-secondary';
              if ($isAccounts) $badgeClass = 'bg-success';
              elseif ($isInvoice) $badgeClass = 'bg-warning text-dark';

              $userName = $log['user_name'] ?? ('User #' . (int)($log['user_id'] ?? 0));
              $when     = $log['action_at'] ?? '';
              $oldText  = (string)($log['old_text'] ?? '');
              $newText  = (string)($log['new_text'] ?? '');
              $rowNo    = $offset + $i + 1;
            ?>
            <tr>
              <td><?= (int)$rowNo ?></td>
              <td class="text-nowrap"><?= h($when) ?></td>

              <td>
                <div class="fw-semibold"><?= h($schoolLabel) ?></div>
                <?php if (!empty($log['school_id'])): ?>
                  <div class="text-muted small">School ID: <?= (int)$log['school_id'] ?></div>
                <?php endif; ?>
              </td>

              <td>
                <span class="badge <?= h($badgeClass) ?>"><?= h($actionLabel) ?></span>
                <div class="text-muted small mt-1">Log ID: <?= (int)$log['id'] ?></div>
              </td>

              <td>
                <div class="fw-semibold"><?= h($userName) ?></div>
                <?php if (!empty($log['user_id'])): ?>
                  <div class="text-muted small">User ID: <?= (int)$log['user_id'] ?></div>
                <?php endif; ?>
              </td>

              <td class="text-nowrap">
                <?= !empty($log['note_id']) ? (int)$log['note_id'] : '—' ?>
              </td>

              <td>
                <details>
                  <summary class="text-primary" style="cursor:pointer;">View Old/New</summary>

                  <div class="row g-2 mt-2">
                    <div class="col-12 col-md-6">
                      <div class="p-2 border rounded bg-light">
                        <div class="text-muted small fw-semibold mb-1">Old</div>
                        <div class="small" style="white-space:pre-wrap; word-break:break-word;"><?= h($oldText ?: '—') ?></div>
                      </div>
                    </div>

                    <div class="col-12 col-md-6">
                      <div class="p-2 border rounded bg-light">
                        <div class="text-muted small fw-semibold mb-1">New</div>
                        <div class="small" style="white-space:pre-wrap; word-break:break-word;"><?= h($newText ?: '—') ?></div>
                      </div>
                    </div>
                  </div>
                </details>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>

        </table>
      </div>

      <!-- Pagination -->
      <div class="d-flex align-items-center justify-content-between mt-3">
        <div class="text-muted small">Page <?= (int)$page ?> / <?= (int)$totalPages ?></div>

        <div class="btn-group" role="group" aria-label="Pagination">
          <?php if ($page > 1): ?>
            <a class="btn btn-outline-secondary btn-sm" href="?<?= h(build_qs(['page' => 1])) ?>">First</a>
            <a class="btn btn-outline-secondary btn-sm" href="?<?= h(build_qs(['page' => $page - 1])) ?>">Prev</a>
          <?php endif; ?>

          <?php if ($page < $totalPages): ?>
            <a class="btn btn-outline-secondary btn-sm" href="?<?= h(build_qs(['page' => $page + 1])) ?>">Next</a>
            <a class="btn btn-outline-secondary btn-sm" href="?<?= h(build_qs(['page' => $totalPages])) ?>">Last</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require '../layout/layout_footer.php'; ?>
