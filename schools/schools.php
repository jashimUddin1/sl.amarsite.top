<?php // schools/schools.php
require_once '../auth/config.php';
require_login();


// ====== Filters ======
$filterDistrict = trim($_GET['district'] ?? '');
$filterUpazila = trim($_GET['upazila'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$filtersActive = ($filterDistrict !== '' || $filterUpazila !== '' || $filterStatus !== '');

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
$pageTitle = 'Schools - School List';
$pageHeading = 'School List';
$activeMenu = 'schools';

require '../layout/layout_header.php';

?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">

    <!-- Left: Title + Search -->
    <div class="flex items-center gap-3 flex-wrap">
        <h2 class="text-xl font-bold text-slate-800 whitespace-nowrap">
            School List
        </h2>

        <!-- 🔍 Search bar -->
        <input type="text" name="search" id="schoolSearchInput" placeholder="Search school..."
            class="px-3 py-2 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 w-56"
            onkeyup="searchSchoolTable()">
    </div>

    <!-- Right: Buttons -->
    <div class="flex items-center gap-2">
        <button type="button" id="filterToggleBtn" onclick="toggleFilter()"
            class="px-3 py-2 rounded border border-slate-300 text-xs sm:text-sm text-slate-700 hover:bg-slate-100">
            <?php echo $filtersActive ? 'Hide Filters' : 'Show Filters'; ?>
        </button>

        <a href="school_create.php" class="px-4 py-2 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700">
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
                    <option value="<?php echo htmlspecialchars($d); ?>" <?php echo ($filterDistrict === $d) ? 'selected' : ''; ?>>
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
                    <option value="<?php echo htmlspecialchars($u); ?>" <?php echo ($filterUpazila === $u) ? 'selected' : ''; ?>>
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
                    <option value="<?php echo htmlspecialchars($st); ?>" <?php echo ($filterStatus === $st) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($st); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 rounded bg-slate-900 text-white text-sm hover:bg-slate-800">
                Apply
            </button>
            <a href="schools.php" class="px-4 py-2 rounded bg-slate-200 text-slate-700 text-sm hover:bg-slate-300">
                Reset
            </a>
        </div>
    </form>
</div>

<?php
// Success message
if (!empty($_SESSION['school_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center justify-content-between small py-2 mb-2"
        role="alert">
        <div class="me-2">
            <?php
            echo htmlspecialchars($_SESSION['school_success']);
            unset($_SESSION['school_success']);
            ?>
        </div>
        <button type="button" class="btn-close btn-sm ms-auto p-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php
// Error message (একাধিক error থাকলে list আকারে)
if (!empty($_SESSION['school_errors']) && is_array($_SESSION['school_errors'])): ?>
    <div class="alert alert-danger alert-dismissible fade show small py-2 mb-3" role="alert">
        <ul class="mb-0 ps-3">
            <?php foreach ($_SESSION['school_errors'] as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['school_errors']); ?>
<?php endif; ?>


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
                    <th class="p-2 border">Fee y/m</th>
                    <th class="p-2 border">Status</th>
                    <th class="p-2 border text-center">Actions</th>
                    <th class="p-2 border">Print</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schools as $s): ?>
                    <?php
                    $address = trim(($s['district'] ?? '') .
                        (($s['district'] ?? '') && ($s['upazila'] ?? '') ? ', ' : '') .
                        ($s['upazila'] ?? ''));
                    if ($address === '')
                        $address = 'N/A';

                    $statusClass = $s['status'] === 'Approved'
                        ? 'text-green-600'
                        : 'text-orange-600';
                    ?>
                    <tr class="hover:bg-slate-50">
                        <td class="p-2 border align-center"><?php echo (int) $s['id']; ?></td>

                        <td class="p-2 border align-center">
                            <?php if (!empty($s['photo_path'])): ?>
                                <img src="../<?php echo htmlspecialchars($s['photo_path']); ?>"
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
                            <span class="text-xs">
                                <?php echo number_format((float) ($s['y_fee'] ?? 0), 0); ?> /
                                <?php echo number_format((float) ($s['m_fee'] ?? 0), 0); ?>
                            </span>
                        </td>

                        <td class="p-2 border align-center">
                            <span class="text-xs font-semibold <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($s['status']); ?>
                            </span>
                        </td>

                        <td class="p-2 border  align-center">
                            <div class="flex items-center justify-center gap-1 text-xs">
                                <!-- Edit -->
                                <a href="school_edit.php?id=<?php echo (int) $s['id']; ?>"
                                    class="px-3 py-1 rounded bg-slate-800 text-white text-center hover:bg-slate-900">
                                    Edit
                                </a>

                                <!-- Delete -->
                                <form method="POST" action="/school_list/controllers/schoolController.php"
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
                        <td class="border text-center align-center">
                            <button class="text-blue-600 hover:text-blue-800"><a
                                    href="../invoices/invoice_school_final.php?school_id=<?php echo (int) $s['id']; ?>"
                                    class="p-2 btn btn-outline-scondary">📄</a>
                            </button>
                            <a href="../invoices/invoices_by_school.php?school_id=<?php echo (int) $s['id']; ?>"
                                class="btn btn-outline-success btn-sm" title="View all invoices">
                                ⋮
                            </a>
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
<script>
    function searchSchoolTable() {
        const input = document.getElementById('schoolSearchInput');
        const filter = input.value.toLowerCase();
        const rows = document.querySelectorAll('table tbody tr');

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    }
</script>


<?php
require '../layout/layout_footer.php';
