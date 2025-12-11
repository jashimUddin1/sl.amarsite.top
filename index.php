<?php
require_once 'config.php';
require_login();

// ====== Filters (GET) ======
$filterDistrict = trim($_GET['district'] ?? '');
$filterUpazila = trim($_GET['upazila'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

// ====== Filter options (district / upazila / status) ======
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

// ====== মোট কাউন্ট ======
$totalSchools = (int) ($pdo->query("SELECT COUNT(*) FROM schools")->fetchColumn() ?? 0);
$approvedSchools = (int) ($pdo->query("SELECT COUNT(*) FROM schools WHERE status = 'Approved'")->fetchColumn() ?? 0);
$pendingSchools = (int) ($pdo->query("SELECT COUNT(*) FROM schools WHERE status = 'Pending'")->fetchColumn() ?? 0);

// ====== Filter অনুযায়ী স্কুল লিস্ট ======
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

// ====== প্রতিটি স্কুল অনুযায়ী নোটগুলো আলাদা করা ======
$notesBySchool = [];
if (!empty($schools)) {
    $ids = array_column($schools, 'id'); // schools টেবিলের id
    $ids = array_map('intval', $ids);
    $in = implode(',', $ids);

    // school_notes থেকে সব নোট এনে school_id অনুযায়ী গ্রুপ করব
    // এখানে note_date DESC, তাই index 0 = সর্বশেষ নোট
    $stmtN = $pdo->query("SELECT * FROM school_notes WHERE school_id IN ($in) ORDER BY id DESC");

    foreach ($stmtN as $row) {
        $notesBySchool[$row['school_id']][] = $row;
    }
}


// ====== Layout vars ======
$pageTitle = 'Home - School Note Manager';
$pageHeading = 'Home';
$activeMenu = 'home';

require 'layout_header_index.php';

// session test
// $_SESSION['note_error'] = 'testing session message';
?>

<style>
    /* পূরো পেজের ব্যাকগ্রাউন্ড গ্রেডিয়েন্ট */
    .page-bg {
        min-height: calc(100vh - 4rem);
        background: linear-gradient(135deg, #dbeafe, #e0e7ff);
        /* হালকা blue → indigo */
        padding: 1.5rem 0;
    }

    @media (max-width: 576px) {
        .page-bg {
            min-height: auto !important;
            padding: 0.5rem 0 !important;
        }
    }

    /* Filter bar এক লাইনে + ছোট input */
    .filter-bar-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .filter-bar {
        min-width: max-content;
    }

    .filter-bar .form-select,
    .filter-bar .form-control,
    .filter-bar .btn {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
    }

    .close-custom {
        padding: 12px 12px !important;
    }
</style>

<div class="page-bg">
    <div class="container-lg">

        <p class="text-center small text-muted mb-4">
            মোট স্কুল:
            <span class="fw-semibold"><?php echo $totalSchools; ?></span> |
            Approved:
            <span class="fw-semibold text-success"><?php echo $approvedSchools; ?></span> |
            Pending:
            <span class="fw-semibold text-warning"><?php echo $pendingSchools; ?></span>
        </p>

        <?php if (!empty($_SESSION['note_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center justify-content-between small py-2 mb-2"
                role="alert">
                <div class="me-2">
                    <?php
                    echo htmlspecialchars($_SESSION['note_success']);
                    unset($_SESSION['note_success']);
                    ?>
                </div>
                <button type="button" class="btn-close btn-sm ms-auto close-custom" data-bs-dismiss="alert"
                    aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['note_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center justify-content-between small py-2 mb-3"
                role="alert">
                <div class="me-2">
                    <?php
                    echo htmlspecialchars($_SESSION['note_error']);
                    unset($_SESSION['note_error']);
                    ?>
                </div>
                <button type="button" class="btn-close btn-sm ms-auto close-custom" data-bs-dismiss="alert"
                    aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-body py-3">

                <div class="filter-bar-wrapper">
                    <form method="GET" class="d-flex align-items-center gap-2 filter-bar mb-0">

                        <!-- District -->
                        <select name="district" class="form-select form-select-sm">
                            <option value="">District</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?php echo htmlspecialchars($d); ?>" <?php echo ($filterDistrict === $d) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Upazila -->
                        <select name="upazila" class="form-select form-select-sm">
                            <option value="">Upazila</option>
                            <?php foreach ($upazilas as $u): ?>
                                <option value="<?php echo htmlspecialchars($u); ?>" <?php echo ($filterUpazila === $u) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Status -->
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Status</option>
                            <?php foreach ($statuses as $st): ?>
                                <option value="<?php echo htmlspecialchars($st); ?>" <?php echo ($filterStatus === $st) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($st); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Apply / Reset -->
                        <button type="submit" class="btn btn-dark btn-sm flex-shrink-0">
                            Apply
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary btn-sm flex-shrink-0">
                            Reset
                        </a>
                    </form>
                </div>

            </div>
        </div>

        <!-- School List (Card View) -->
        <?php if (!$schools): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center text-muted small">
                    কোনো স্কুল পাওয়া যায়নি।
                </div>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 g-3" id="schoolList">
                <?php foreach ($schools as $s): ?>
                    <?php
                    $sid = (int) $s['id'];
                    $notes = $notesBySchool[$sid] ?? [];
                    $noteCount = count($notes);
                    $latestNote = $noteCount ? $notes[0] : null; // note_date DESC বলে index 0 = latest
            
                    $address = trim(
                        ($s['district'] ?? '') .
                        (($s['district'] ?? '') && ($s['upazila'] ?? '') ? ', ' : '') .
                        ($s['upazila'] ?? '')
                    );
                    if ($address === '')
                        $address = 'N/A';

                    $status = $s['status'] ?? '';
                    $statusClass = ($status === 'Approved')
                        ? 'text-success'
                        : 'text-warning';

                    $photo = $s['photo_path'] ?? null;
                    ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <!-- Photo -->
                            <?php if (!empty($photo)): ?>
                                <img src="<?php echo htmlspecialchars($photo); ?>" class="card-img-top"
                                    style="height:220px; object-fit:cover;">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center border-bottom"
                                    style="height:220px; font-size:0.8rem; color:#9ca3af;">
                                    No Photo
                                </div>
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column">
                                <!-- Basic Info -->
                                <h3 class="h6 fw-bold mb-1">
                                    <?php echo htmlspecialchars($s['school_name']); ?>
                                </h3>

                                <?php if (!empty($s['mobile'])): ?>
                                    <p class="text-muted small mb-1">
                                        <a href="tel:<?php echo htmlspecialchars($s['mobile']); ?>"
                                            class="text-primary text-decoration-none">
                                            <span style="color:blue;">📞</span> <?php echo htmlspecialchars($s['mobile']); ?>
                                        </a>
                                    </p>
                                <?php endif; ?>

                                <p class="small text-primary fw-semibold mb-2">
                                    <?php echo htmlspecialchars($address); ?>
                                </p>

                                <!-- Status -->
                                <p class="mb-2 small fw-semibold">
                                    Status:
                                    <span class="<?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </p>

                                <!-- Notes section -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small fw-semibold">Notes:</span>

                                        <?php if ($noteCount > 1): ?>
                                            <button type="button" class="btn btn-link btn-sm p-0 small text-decoration-none"
                                                onclick="openNotesModal(<?php echo $sid; ?>)">
                                                View all (<?php echo $noteCount; ?>)
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($latestNote): ?>
                                        <div class="small text-muted border rounded p-2 bg-light mb-0">
                                            <div>
                                                <?php echo nl2br(htmlspecialchars($latestNote['note_text'])); ?>
                                            </div>
                                            <div class="mt-1 text-secondary">
                                                <small><?php echo htmlspecialchars($latestNote['note_date']); ?></small>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <p class="small text-muted mb-0">এখনও কোনো নোট যোগ করা হয়নি।</p>
                                    <?php endif; ?>
                                </div>


                                <!-- Actions -->
                                <div class="mt-auto d-flex gap-2">
                                    <!-- সব নোট লিস্ট + edit/delete -->
                                    <a href="note_view.php?school_id=<?php echo (int) $s['id']; ?>"
                                        class="btn btn-primary btn-sm w-50">
                                        Manage Note
                                    </a>

                                    <!-- নতুন নোট যোগ করার জন্য modal open -->
                                    <button type="button" class="btn btn-success btn-sm w-50 btn-open-add-note"
                                        data-bs-toggle="modal" data-bs-target="#addNoteModal"
                                        data-school-id="<?php echo (int) $s['id']; ?>"
                                        data-school-name="<?php echo htmlspecialchars($s['school_name']); ?>">
                                        Add Note
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="core/add_note_core.php" class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="addNoteModalLabel">Add Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <input type="hidden" name="school_id" id="noteSchoolId">

                <div class="mb-2">
                    <label class="form-label small mb-1">School</label>
                    <input type="text" class="form-control form-control-sm" id="noteSchoolName" readonly>
                </div>

                <div class="mb-2">
                    <label for="noteText" class="form-label small mb-1">Note</label>
                    <textarea name="note_text" id="noteText" rows="4" class="form-control form-control-sm"
                        placeholder="Write your note here..." required></textarea>
                </div>

            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-sm btn-primary">Save Note</button>
            </div>
        </form>
    </div>
</div>

<!-- All Notes Modal -->
<div class="modal fade" id="notesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title">All Notes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="notesModalContent" class="d-flex flex-column gap-2 small">
                    <!-- JS দিয়ে notes.php থেকে content আসবে -->
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>


<script>
    function openNotesModal(schoolId) {
        const modalEl = document.getElementById('notesModal');
        const contentEl = document.getElementById('notesModalContent');

        if (!modalEl || !contentEl) return;

        contentEl.innerHTML = "<p class='text-muted small mb-0'>Loading...</p>";

        // notes.php থেকে HTML রেসপন্স নিয়ে আসছি
        fetch('notes.php?id=' + encodeURIComponent(schoolId))
            .then(function (res) {
                return res.text();
            })
            .then(function (html) {
                contentEl.innerHTML = html;
            })
            .catch(function () {
                contentEl.innerHTML = "<p class='text-danger small mb-0'>নোট লোড করতে সমস্যা হয়েছে।</p>";
            });

        // Bootstrap Modal show
        if (typeof bootstrap !== 'undefined') {
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            // fallback (যদি bootstrap js লোড না থাকে)
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
        }
    }

    fetch("core/notification_core.php")
        .then(res => res.json())
        .then(data => {
            if (data.length > 0) {
                document.getElementById("notify-badge").innerText = data.length;
                document.getElementById("notify-badge").style.display = "inline-block";
            }
        });

</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const addNoteButtons = document.querySelectorAll('.btn-open-add-note');
        const schoolIdInput = document.getElementById('noteSchoolId');
        const schoolNameInput = document.getElementById('noteSchoolName');

        addNoteButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const schoolId = this.getAttribute('data-school-id');
                const schoolName = this.getAttribute('data-school-name');

                if (schoolIdInput) schoolIdInput.value = schoolId;
                if (schoolNameInput) schoolNameInput.value = schoolName;
            });
        });
    });
</script>



<?php
require 'layout_footer.php';
