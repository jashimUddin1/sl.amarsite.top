<?php
require_once '../auth/config.php';
require_login();

/**
 * Filters (POST)
 */
$filters = [
    'school_id'    => isset($_POST['school_id']) ? (int)$_POST['school_id'] : 0,
    'updated_by'   => isset($_POST['updated_by']) ? (int)$_POST['updated_by'] : 0,
    'has_nextmeet' => isset($_POST['has_nextmeet']) ? trim($_POST['has_nextmeet']) : '', // '', 'yes', 'no'
    'day'          => isset($_POST['day']) ? trim($_POST['day']) : '', // English day name: Monday...
    'date_from'    => isset($_POST['date_from']) ? trim($_POST['date_from']) : '', // YYYY-MM-DD
    'date_to'      => isset($_POST['date_to']) ? trim($_POST['date_to']) : '',     // YYYY-MM-DD
    'q'            => isset($_POST['q']) ? trim($_POST['q']) : '',
];

/**
 * Dropdown data
 */
$schools = $pdo->query("SELECT id, school_name FROM schools ORDER BY school_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$admins  = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

/**
 * Build dynamic query
 */
$where = [];
$params = [];

$sql = "
SELECT
  sn.*,
  s.school_name,
  u.name AS admin_name
FROM school_notes sn
LEFT JOIN schools s ON s.id = sn.school_id
LEFT JOIN users u ON u.id = sn.updated_by
";

if ($filters['school_id'] > 0) {
    $where[] = "sn.school_id = :school_id";
    $params[':school_id'] = $filters['school_id'];
}

if ($filters['updated_by'] > 0) {
    $where[] = "sn.updated_by = :updated_by";
    $params[':updated_by'] = $filters['updated_by'];
}

if ($filters['has_nextmeet'] === 'yes') {
    $where[] = "sn.next_meet IS NOT NULL AND sn.next_meet <> ''";
}
if ($filters['has_nextmeet'] === 'no') {
    $where[] = "(sn.next_meet IS NULL OR sn.next_meet = '')";
}

if ($filters['day'] !== '') {
    // MySQL: DAYNAME(datetime) returns Sunday..Saturday
    $where[] = "sn.next_meet IS NOT NULL AND DAYNAME(sn.next_meet) = :day";
    $params[':day'] = $filters['day'];
}

if ($filters['date_from'] !== '') {
    $where[] = "DATE(sn.created_at) >= :date_from";
    $params[':date_from'] = $filters['date_from'];
}

if ($filters['date_to'] !== '') {
    $where[] = "DATE(sn.created_at) <= :date_to";
    $params[':date_to'] = $filters['date_to'];
}

if ($filters['q'] !== '') {
    $where[] = "sn.note_text LIKE :q";
    $params[':q'] = '%' . $filters['q'] . '%';
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY sn.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Day mapping (display)
 */
$dayBnMap = [
    'Sunday'    => 'রবিবার',
    'Monday'    => 'সোমবার',
    'Tuesday'   => 'মঙ্গলবার',
    'Wednesday' => 'বুধবার',
    'Thursday'  => 'বৃহস্পতিবার',
    'Friday'    => 'শুক্রবার',
    'Saturday'  => 'শনিবার',
];

function bnDayFromDatetime(?string $dt, array $map): string {
    if (empty($dt)) return 'N/A';
    $ts = strtotime($dt);
    if ($ts === false) return 'N/A';
    $en = date('l', $ts);
    return $map[$en] ?? 'N/A';
}

$pageTitle = 'All Notes';
$activeMenu = 'home';
require '../layout/layout_header.php';
?>

<div class="container-lg my-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">All School Notes</h4>
            <small class="text-muted">Filter করে যেকোনো নোট খুঁজে নাও</small>
        </div>
    </div>

    <!-- Filters -->
    <form method="POST" class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">

                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1">School</label>
                    <select name="school_id" class="form-select form-select-sm">
                        <option value="0">All Schools</option>
                        <?php foreach ($schools as $s): ?>
                            <option value="<?= (int)$s['id']; ?>" <?= ($filters['school_id']==(int)$s['id'])?'selected':''; ?>>
                                <?= htmlspecialchars($s['school_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label small mb-1">Admin</label>
                    <select name="updated_by" class="form-select form-select-sm">
                        <option value="0">All</option>
                        <?php foreach ($admins as $a): ?>
                            <option value="<?= (int)$a['id']; ?>" <?= ($filters['updated_by']==(int)$a['id'])?'selected':''; ?>>
                                <?= htmlspecialchars($a['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label small mb-1">Next Meet</label>
                    <select name="has_nextmeet" class="form-select form-select-sm">
                        <option value=""   <?= ($filters['has_nextmeet']==='')?'selected':''; ?>>All</option>
                        <option value="yes" <?= ($filters['has_nextmeet']==='yes')?'selected':''; ?>>Only with date</option>
                        <option value="no"  <?= ($filters['has_nextmeet']==='no')?'selected':''; ?>>Only empty</option>
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label small mb-1">Day (from next_meet)</label>
                    <select name="day" class="form-select form-select-sm">
                        <option value="" <?= ($filters['day']==='')?'selected':''; ?>>All</option>
                        <?php foreach (['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d): ?>
                            <option value="<?= $d; ?>" <?= ($filters['day']===$d)?'selected':''; ?>>
                                <?= htmlspecialchars($dayBnMap[$d] ?? $d); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-1">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']); ?>" class="form-control form-control-sm">
                </div>

                <div class="col-6 col-md-1">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']); ?>" class="form-control form-control-sm">
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small mb-1">Search text</label>
                    <input type="text" name="q" value="<?= htmlspecialchars($filters['q']); ?>" class="form-control form-control-sm" placeholder="যেমন: ডিসেম্বর, জানুয়ারী, মিটিং...">
                </div>

                <div class="col-12 col-md-2 d-flex gap-2">
                    <button class="btn btn-sm btn-primary w-100" type="submit">Filter</button>
                    <a class="btn btn-sm btn-outline-secondary w-100" href="notes_all.php">Reset</a>
                </div>

            </div>
        </div>
    </form>

    <!-- Results -->
    <div class="card shadow-sm">
        <div class="card-body p-0">

            <?php if (!$notes): ?>
                <p class="text-center text-muted small py-3 mb-0">কোনো নোট পাওয়া যায়নি।</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr class="small">
                                <th style="width:60px;">#</th>
                                <th style="width:180px;">School</th>
                                <th>Note</th>
                                <th style="width:170px;">Next Meet</th>
                                <th style="width:110px;">Day</th>
                                <th style="width:140px;">Admin</th>
                                <th style="width:170px;">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $sl = 1; ?>
                            <?php foreach ($notes as $n): ?>
                                <?php
                                    $nextMeet = $n['next_meet'] ?? null;
                                    $dayBn = bnDayFromDatetime($nextMeet, $dayBnMap);
                                ?>
                                <tr class="small">
                                    <td><?= $sl++; ?></td>
                                    <td><?= htmlspecialchars($n['school_name'] ?? 'N/A'); ?></td>
                                    <td><?= nl2br(htmlspecialchars($n['note_text'] ?? '')); ?></td>
                                    <td><?= htmlspecialchars($nextMeet ?? 'N/A'); ?></td>
                                    <td><?= htmlspecialchars($dayBn); ?></td>
                                    <td><?= htmlspecialchars($n['admin_name'] ?? 'N/A'); ?></td>
                                    <td><?= htmlspecialchars($n['created_at'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </div>

</div>

<?php require '../layout/layout_footer.php'; ?>
