<?php
require_once 'config.php';
require_login();

// ====== Delete Handler (POST + Trash) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_school') {
    $id      = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $userId  = $_SESSION['user_id'] ?? null;

    if ($id > 0) {
        try {
            $pdo->beginTransaction();

            // 1) আগে school data নিয়ে আসি
            $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $school = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($school) {
                // 2) ছবি move করি uploads/trash_schools এ
                $photoPath      = $school['photo_path'] ?? null;
                $trashPhotoPath = null;

                if (!empty($photoPath)) {
                    $oldFull = __DIR__ . '/' . $photoPath;
                    if (is_file($oldFull)) {
                        $trashDir = __DIR__ . '/uploads/trash_schools';
                        if (!is_dir($trashDir)) {
                            mkdir($trashDir, 0777, true);
                        }

                        $fileName = basename($photoPath);
                        $newFull  = $trashDir . '/' . $fileName;

                        if (@rename($oldFull, $newFull)) {
                            // trash table এ এই path রাখব
                            $trashPhotoPath = 'uploads/trash_schools/' . $fileName;
                        } else {
                            // move ব্যর্থ হলে পুরানো path-ই রেখে দিচ্ছি
                            $trashPhotoPath = $photoPath;
                        }
                    } else {
                        // ফাইল নাই, তাহলে পুরানো path-ই রেখে দিচ্ছি
                        $trashPhotoPath = $photoPath;
                    }
                }

                // 3) school_trash এ insert
                $stmtTrash = $pdo->prepare("
                    INSERT INTO school_trash (
                        school_id,
                        district, upazila, school_name, mobile, status,
                        photo_path,
                        created_by, updated_by, deleted_by
                    )
                    VALUES (
                        :school_id,
                        :district, :upazila, :school_name, :mobile, :status,
                        :photo_path,
                        :created_by, :updated_by, :deleted_by
                    )
                ");

                $stmtTrash->execute([
                    ':school_id'  => $school['id'],
                    ':district'   => $school['district'],
                    ':upazila'    => $school['upazila'],
                    ':school_name'=> $school['school_name'],
                    ':mobile'     => $school['mobile'],
                    ':status'     => $school['status'],
                    ':photo_path' => $trashPhotoPath,
                    ':created_by' => $school['created_by'] ?? null,
                    ':updated_by' => $school['updated_by'] ?? null,
                    ':deleted_by' => $userId,
                ]);

                // 4) এই স্কুলের সব নোট note_trash এ কপি করি
                $stmtNotesTrash = $pdo->prepare("
                    INSERT INTO note_trash (
                        original_note_id,
                        school_id,
                        note_text,
                        note_date,
                        updated_by,
                        created_at,
                        deleted_by,
                        deleted_at
                    )
                    SELECT
                        n.id AS original_note_id,
                        n.school_id,
                        n.note_text,
                        n.note_date,
                        n.updated_by,
                        n.created_at,
                        :deleted_by AS deleted_by,
                        NOW()       AS deleted_at
                    FROM school_notes AS n
                    WHERE n.school_id = :school_id
                ");
                $stmtNotesTrash->execute([
                    ':deleted_by' => $userId,
                    ':school_id'  => $school['id'],
                ]);
            }

            // 5) আসল school_notes টেবিল থেকে delete
            $stmtDelNotes = $pdo->prepare("DELETE FROM school_notes WHERE school_id = :id");
            $stmtDelNotes->execute([':id' => $id]);

            // 6) আসল schools টেবিল থেকে delete
            $stmtDel = $pdo->prepare("DELETE FROM schools WHERE id = :id");
            $stmtDel->execute([':id' => $id]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            // চাইলে এখানে error log করতে পারো
        }
    }

    header("Location: schools.php");
    exit;
}


// ====== Filters ======
$filterDistrict = trim($_GET['district'] ?? '');
$filterUpazila  = trim($_GET['upazila'] ?? '');
$filterStatus   = trim($_GET['status'] ?? '');
$filtersActive  = ($filterDistrict !== '' || $filterUpazila !== '' || $filterStatus !== '');

// ====== Filter options ======
$districts = $pdo->query("
    SELECT DISTINCT district 
    FROM schools 
    WHERE district IS NOT NULL AND district <> '' 
    ORDER BY district ASC
")->fetchAll(PDO::FETCH_COLUMN);

$upazilas = $pdo->query("
    SELECT DISTINCT upazila 
    FROM schools 
    WHERE upazila IS NOT NULL AND upazila <> '' 
    ORDER BY upazila ASC
")->fetchAll(PDO::FETCH_COLUMN);

$statuses = $pdo->query("
    SELECT DISTINCT status 
    FROM schools 
    WHERE status IS NOT NULL AND status <> '' 
    ORDER BY status ASC
")->fetchAll(PDO::FETCH_COLUMN);

// ====== সব স্কুল (filter apply করে) ======
$sql = "SELECT * FROM schools WHERE 1=1";
$params = [];

if ($filterDistrict !== '') {
    $sql .= " AND district = :district";
    $params[':district'] = $filterDistrict;
}
if ($filterUpazila !== '') {
    $sql .= " AND upazila = :upazila";
    $params[':upazila'] = $filterUpazila;
}
if ($filterStatus !== '') {
    $sql .= " AND status = :status";
    $params[':status'] = $filterStatus;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ====== Layout variables ======
$pageTitle   = 'Schools - School List';
$pageHeading = 'School List';
$activeMenu  = 'schools';

require 'layout_header.php';
?>

<div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-bold text-slate-800">School List</h2>

    <div class="flex items-center gap-2">
        <!-- Filter Toggle Button -->
        <button
            type="button"
            id="filterToggleBtn"
            onclick="toggleFilter()"
            class="px-3 py-2 rounded border border-slate-300 text-xs sm:text-sm text-slate-700 hover:bg-slate-100">
            <?php echo $filtersActive ? 'Hide Filters' : 'Show Filters'; ?>
        </button>

        <!-- Add School Button -->
        <a href="school_create.php"
           class="px-4 py-2 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700">
            + Add School
        </a>
    </div>
</div>

<!-- 🔍 Filters Section (Toggle-able) -->
<div id="filterSection" class="<?php echo $filtersActive ? '' : 'hidden'; ?>">
    <form method="GET" class="mb-4 bg-white rounded-xl shadow p-3 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">District</label>
            <select name="district" class="p-2 border rounded text-sm min-w-[150px]">
                <option value="">All</option>
                <?php foreach ($districts as $d): ?>
                    <option value="<?php echo htmlspecialchars($d); ?>"
                        <?php echo ($filterDistrict === $d) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($d); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Upazila</label>
            <select name="upazila" class="p-2 border rounded text-sm min-w-[150px]">
                <option value="">All</option>
                <?php foreach ($upazilas as $u): ?>
                    <option value="<?php echo htmlspecialchars($u); ?>"
                        <?php echo ($filterUpazila === $u) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($u); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
            <select name="status" class="p-2 border rounded text-sm min-w-[140px]">
                <option value="">All</option>
                <?php foreach ($statuses as $st): ?>
                    <option value="<?php echo htmlspecialchars($st); ?>"
                        <?php echo ($filterStatus === $st) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($st); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit"
                class="px-4 py-2 rounded bg-slate-900 text-white text-sm hover:bg-slate-800">
                Apply
            </button>
            <a href="schools.php"
                class="px-4 py-2 rounded bg-slate-200 text-slate-700 text-sm hover:bg-slate-300">
                Reset
            </a>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow p-3 overflow-x-auto">
    <?php if (!$schools): ?>
        <p class="text-center text-gray-500 text-sm py-4">কোনো স্কুল পাওয়া যায়নি।</p>
    <?php else: ?>
        <table class="min-w-full text-sm border-collapse">
            <thead>
                <tr class="bg-slate-100 text-left">
                    <th class="p-2 border">ID</th>
                    <th class="p-2 border">Photo</th>
                    <th class="p-2 border" style="min-width: 150px;">School Name</th>
                    <th class="p-2 border" style="min-width: 150px;">Address</th>
                    <th class="p-2 border">Mobile</th>
                    <th class="p-2 border">Status</th>
                    <th class="p-2 border">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schools as $s): ?>
                    <?php
                    $address = trim(($s['district'] ?? '') .
                        (($s['district'] ?? '') && ($s['upazila'] ?? '') ? ', ' : '') .
                        ($s['upazila'] ?? ''));
                    if ($address === '') $address = 'N/A';

                    $statusClass = $s['status'] === 'Approved'
                        ? 'text-green-600'
                        : 'text-orange-600';
                    ?>
                    <tr class="hover:bg-slate-50">
                        <td class="p-2 border align-center"><?php echo (int) $s['id']; ?></td>

                        <td class="p-2 border align-center">
                            <?php if (!empty($s['photo_path'])): ?>
                                <img src="<?php echo htmlspecialchars($s['photo_path']); ?>"
                                    class="h-10 w-16 object-cover rounded border" alt="photo">
                            <?php else: ?>
                                <span class="text-xs text-gray-400">No photo</span>
                            <?php endif; ?>
                        </td>

                        <td class="p-2 border align-center font-semibold">
                            <?php echo htmlspecialchars($s['school_name']); ?>
                        </td>

                        <td class="p-2 border align-center text-xs text-gray-700">
                            <?php echo htmlspecialchars($address); ?>
                        </td>

                        <td class="p-2 border align-center">
                            <span class="text-xs">
                                <?php echo htmlspecialchars($s['mobile'] ?? ''); ?>
                            </span>
                        </td>

                        <td class="p-2 border align-center">
                            <span class="text-xs font-semibold <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($s['status']); ?>
                            </span>
                        </td>

                        <td class="p-2 border align-center">
                            <div class="flex items-center gap-1 text-xs">
                                <!-- Edit -->
                                <a href="school_edit.php?id=<?php echo (int)$s['id']; ?>"
                                    class="px-3 py-1 rounded bg-slate-800 text-white text-center hover:bg-slate-900">
                                    Edit
                                </a>

                                <!-- Delete -->
                                <form method="POST"
                                      onsubmit="return confirm('এই স্কুলটি delete করতে নিশ্চিত?');">
                                    <input type="hidden" name="action" value="delete_school">
                                    <input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>">
                                    <button type="submit"
                                            class="px-3 py-1 rounded bg-red-600 text-white text-center hover:bg-red-700">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
    function toggleFilter() {
        const section = document.getElementById('filterSection');
        const btn = document.getElementById('filterToggleBtn');
        if (!section || !btn) return;

        const isHidden = section.classList.contains('hidden');
        if (isHidden) {
            section.classList.remove('hidden');
            btn.textContent = 'Hide Filters';
        } else {
            section.classList.add('hidden');
            btn.textContent = 'Show Filters';
        }
    }
</script>

<?php
require 'layout_footer.php';
