<?php
// notes_all.php
require_once '../auth/config.php';
require_login();

/* =========================
   Filter values (POST)
========================= */
$school_id  = (int)($_POST['school_id'] ?? 0);
$day        = trim($_POST['day'] ?? '');
$q          = trim($_POST['q'] ?? '');

/* =========================
   Dropdown data
========================= */
$schools = $pdo->query("
    SELECT id, school_name
    FROM schools
    ORDER BY school_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   Build query dynamically
========================= */
$where  = [];
$params = [];

$sql = "
SELECT
    sn.*,
    s.school_name,
    u.name AS admin_name
FROM school_notes sn
LEFT JOIN schools s ON s.id = sn.school_id
LEFT JOIN users   u ON u.id = sn.updated_by
";

if ($school_id > 0) {
    $where[] = "sn.school_id = :school_id";
    $params[':school_id'] = $school_id;
}

if ($day !== '') {
    $where[] = "sn.next_meet IS NOT NULL AND DAYNAME(sn.next_meet) = :day";
    $params[':day'] = $day;
}

if ($q !== '') {
    $where[] = "sn.note_text LIKE :q";
    $params[':q'] = "%{$q}%";
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY sn.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   Day map (Bangla)
========================= */
$dayBnMap = [
    'Sunday'    => 'রবিবার',
    'Monday'    => 'সোমবার',
    'Tuesday'   => 'মঙ্গলবার',
    'Wednesday' => 'বুধবার',
    'Thursday'  => 'বৃহস্পতিবার',
    'Friday'    => 'শুক্রবার',
    'Saturday'  => 'শনিবার',
];

function banglaDay(?string $dt, array $map): string {
    if (empty($dt)) return 'N/A';
    $ts = strtotime($dt);
    if ($ts === false) return 'N/A';
    return $map[date('l', $ts)] ?? 'N/A';
}

$pageTitle  = 'All Notes';
$activeMenu = 'notes';
require '../layout/layout_header.php';
?>

<div class="container-lg my-4">

    <h4 class="mb-3">All School Notes</h4>

    <!-- ================= FILTER FORM ================= -->
    <form method="POST" class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">

                <div class="col-md-4">
                    <label class="form-label small">School</label>
                    <select name="school_id" class="form-select form-select-sm">
                        <option value="0">All Schools</option>
                        <?php foreach ($schools as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($school_id==$s['id'])?'selected':'' ?>>
                                <?= htmlspecialchars($s['school_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small">Day (Next Meet)</label>
                    <select name="day" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($dayBnMap as $en=>$bn): ?>
                            <option value="<?= $en ?>" <?= ($day===$en)?'selected':'' ?>>
                                <?= $bn ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small">Search Note</label>
                    <input type="text" name="q"
                           value="<?= htmlspecialchars($q) ?>"
                           class="form-control form-control-sm"
                           placeholder="নোট লিখে খুঁজুন">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-sm btn-primary w-100">Filter</button>
                    <a href="notes_all.php"
                       class="btn btn-sm btn-outline-secondary w-100">
                        Reset
                    </a>
                </div>

            </div>
        </div>
    </form>

    <!-- ================= TABLE ================= -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if (!$notes): ?>
                <p class="text-center text-muted small py-3 mb-0">
                    কোনো নোট পাওয়া যায়নি।
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr class="small">
                                <th>#</th>
                                <th>School</th>
                                <th>Note</th>
                                <th>Next Meet</th>
                                <th>Day</th>
                                <th>Admin</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $sl=1; foreach ($notes as $n): ?>
                            <tr class="small">
                                <td><?= $sl++ ?></td>
                                <td><?= htmlspecialchars($n['school_name'] ?? 'N/A') ?></td>
                                <td><?= nl2br(htmlspecialchars($n['note_text'])) ?></td>
                                <td><?= htmlspecialchars($n['next_meet'] ?? 'N/A') ?></td>
                                <td><?= banglaDay($n['next_meet'] ?? null, $dayBnMap) ?></td>
                                <td><?= htmlspecialchars($n['admin_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($n['created_at']) ?></td>
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
