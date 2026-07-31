<?php
require_once '../auth/config.php';

// ====== Fetch School List from Database ======
try {
    $stmt = $pdo->prepare("SELECT * FROM add_run ORDER BY id DESC");
    $stmt->execute();
    $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $schools = [];
    $fetchError = $e->getMessage();
}


// ====== Fetch Unread Notifications ======
try {
    $notifStmt = $pdo->prepare("
        SELECT n.*, s.school_name 
        FROM add_run_note n
        JOIN add_run s ON n.school_id = s.id
        WHERE n.notification_time IS NOT NULL 
          AND n.notification_time <= NOW()
          AND n.is_read = 0
        ORDER BY n.notification_time DESC
    ");
    $notifStmt->execute();
    $pendingNotifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
    $notifCount = count($pendingNotifications);
} catch (PDOException $e) {
    $pendingNotifications = [];
    $notifCount = 0;
}


// ====== Layout Vars ======
$pageTitle = 'Add Run - School List';
$pageHeading = 'Add Run';
$activeMenu = 'addRun';

require '../layout/layout_header.php';
?>

<div class="add_run_wrapper">
    <div class="add_run_header d-flex justify-content-between align-items-center">
        <button data-bs-toggle="modal" data-bs-target="#addSchoolModal" class="btn btn-success">Add School</button>

        <!-- 2. Search Bar (মাঝখানে যুক্ত করা হলো) -->
        <div class="search-box flex-grow-1 mx-md-3" style="max-width: 400px;">
            <div class="input-group">
                <input type="text" id="schoolSearchInput" class="form-control  ps-0" placeholder=" Search by name, phone, or location..." autocomplete="off">
            </div>
        </div>

        <!-- Notification Bell Button -->
        <button type="button" class="btn btn-success position-relative" data-bs-toggle="modal" data-bs-target="#notificationModal" style="width: 42px; height: 42px;">
            <i class="bi bi-bell-fill fs-5"></i>
            <?php if ($notifCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= $notifCount > 99 ? '99+' : $notifCount; ?>
                    <span class="visually-hidden">unread notifications</span>
                </span>
            <?php endif; ?>
        </button>
    </div>
</div><br>

<!-- Alert Messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- School List Table Card -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive" style="min-height: 75vh;">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">School Name</th>
                        <th scope="col">Phone</th>
                        <th scope="col">Location</th>
                        <!-- <th scope="col">Address Details</th> -->
                        <th scope="col">M Fee</th>
                        <th scope="col">Y Fee</th>
                        <th scope="col">W Fee</th>
                        <th scope="col">Last Note</th>
                        <th class="text-center" scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($schools)): ?>
                        <?php foreach ($schools as $index => $school): ?>
                            <?php
                            // নির্দিষ্ট স্কুলটির সকল নোট ডাটাবেজ থেকে নিয়ে আসা (লুপের ভেতর)
                            $noteStmt = $pdo->prepare("SELECT * FROM add_run_note WHERE school_id = :school_id ORDER BY created_at DESC");
                            $noteStmt->execute([':school_id' => $school['id']]);
                            $schoolNotes = $noteStmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <tr>
                                <th><?= $index + 1; ?></th>
                                <td class="fw-bold"><?= htmlspecialchars($school['school_name']); ?></td>
                                <td><?= htmlspecialchars($school['phone_number']); ?></td>
                                <td>
                                    <?= htmlspecialchars($school['district']); ?>,
                                    <?= htmlspecialchars($school['upazila']); ?>
                                </td>
                                <td>৳<?= number_format($school['monthly_fee']); ?></td>
                                <td>৳<?= number_format($school['yearly_fee']); ?></td>
                                <td>৳<?= number_format($school['website_fee']); ?></td>
                                <!-- Last Note Column -->
                                <td>
                                    <?php if (!empty($schoolNotes)): ?>
                                        <!-- সবচেয়ে নতুন নোটটি দেখাবে -->
                                        <span class="d-inline-block text-truncate" style="max-width: 180px;" title="<?= htmlspecialchars($schoolNotes[0]['note_text']); ?>">
                                            <?= htmlspecialchars($schoolNotes[0]['note_text']); ?>
                                        </span>
                                        <br>
                                        <small class="text-muted fs-7" style="font-size: 0.75rem;">
                                            <i class="bi bi-clock"></i> <?= date('d M, h:i A', strtotime($schoolNotes[0]['next_meeting'])); ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted fs-7">No note added</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <!-- 1. View Notes Icon Button -->
                                        <button type="button"
                                            class="btn btn-sm btn-light text-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewNotesModal<?= $school['id']; ?>"
                                            title="View All Notes">
                                            <i class="bi bi-eye fs-6"></i>
                                        </button>

                                        <!-- 2. Add Note / Remark Icon Button -->
                                        <button type="button"
                                            class="btn btn-sm btn-light text-info"
                                            data-bs-toggle="modal"
                                            data-bs-target="#noteModal<?= $school['id']; ?>"
                                            title="Add Note">
                                            <i class="bi bi-journal-text fs-6"></i>
                                        </button>

                                        <!-- 3. Three-dot Dropdown Menu -->
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light text-secondary rounded-circle"
                                                type="button"
                                                id="actionDropdown<?= $school['id']; ?>"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                                style="width: 32px; height: 32px; padding: 0;">
                                                <i class="bi bi-three-dots-vertical fs-6"></i>
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="actionDropdown<?= $school['id']; ?>">
                                                <li>
                                                    <button class="dropdown-item d-flex align-items-center gap-2"
                                                        type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editSchoolModal<?= $school['id']; ?>">
                                                        <i class="bi bi-pencil-square text-primary"></i> Edit
                                                    </button>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <form action="core/add_run_delete_core.php" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই স্কুলটি ডিলিট করতে চান?');" class="m-0">
                                                        <input type="hidden" name="school_id" value="<?= $school['id']; ?>">
                                                        <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Edit School Modal -->
                            <div class="modal fade" id="editSchoolModal<?= $school['id']; ?>" tabindex="-1" aria-labelledby="editSchoolModalLabel<?= $school['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content text-start">

                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editSchoolModalLabel<?= $school['id']; ?>">Edit School Information</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <form id="editSchoolForm<?= $school['id']; ?>" action="core/add_run_edit_core.php" method="POST">

                                                <input type="hidden" name="school_id" value="<?= $school['id']; ?>">

                                                <!-- School Name & Phone Number -->
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">School Name <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="school_name" value="<?= htmlspecialchars($school['school_name']); ?>" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                                        <input type="tel" class="form-control" name="phone_number" value="<?= htmlspecialchars($school['phone_number']); ?>" required>
                                                    </div>
                                                </div>

                                                <!-- Address Selection Section -->
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Division <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="division" value="<?= htmlspecialchars($school['division']); ?>" required>
                                                    </div>

                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">District <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="district" value="<?= htmlspecialchars($school['district']); ?>" required>
                                                    </div>

                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Sub-District / Upazila <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="upazila" value="<?= htmlspecialchars($school['upazila']); ?>" required>
                                                    </div>
                                                </div>

                                                <!-- Detailed Address -->
                                                <div class="mb-3">
                                                    <label class="form-label">Address Line (Road / Village / Area)</label>
                                                    <input type="text" class="form-control" name="address_details" value="<?= htmlspecialchars($school['address_details']); ?>">
                                                </div>

                                                <!-- Monthly Fee & Yearly Fee -->
                                                <div class="row">
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Monthly Fee <span class="text-danger">*</span></label>
                                                        <input type="number" class="form-control" name="monthly_fee" value="<?= $school['monthly_fee']; ?>">
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Yearly Fee <span class="text-danger">*</span></label>
                                                        <input type="number" class="form-control" name="yearly_fee" value="<?= $school['yearly_fee']; ?>">
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Website Fee (opt)</label>
                                                        <input type="number" class="form-control" name="website_fee" value="<?= $school['website_fee']; ?>">
                                                    </div>
                                                </div>

                                            </form>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" form="editSchoolForm<?= $school['id']; ?>" class="btn btn-primary">Update School</button>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <!-- Note & Notification Modal Structure (লুপের ভেতরে নিয়ে আসা হয়েছে) -->
                            <div class="modal fade" id="noteModal<?= $school['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content text-start">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Note & Reminder - <?= htmlspecialchars($school['school_name']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="noteForm<?= $school['id']; ?>" action="core/save_note_core.php" method="POST">
                                                <input type="hidden" name="school_id" value="<?= $school['id']; ?>">

                                                <!-- Note Text Field -->
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Note / Remark <span class="text-danger">*</span></label>
                                                    <textarea class="form-control" name="note_text" rows="3" placeholder="Write any note or discussion details..." required></textarea>
                                                </div>

                                                <!-- Next Meeting & Notification Time -->
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label fw-bold">Next Meeting</label>
                                                        <input type="datetime-local" class="form-control" name="next_meeting">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label fw-bold">Notification Time</label>
                                                        <input type="datetime-local" class="form-control" name="notification_time">
                                                    </div>
                                                </div>

                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" form="noteForm<?= $school['id']; ?>" class="btn btn-info text-white">Save Note</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- View Notes Modal Structure (লুপের ভেতরে নিয়ে আসা হয়েছে) -->
                            <div class="modal fade" id="viewNotesModal<?= $school['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content text-start">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="bi bi-journal-bookmark text-primary me-2"></i>Notes History - <?= htmlspecialchars($school['school_name']); ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">

                                            <?php if (!empty($schoolNotes)): ?>
                                                <div class="timeline">
                                                    <?php foreach ($schoolNotes as $note): ?>
                                                        <div class="card mb-3 border-0 bg-light shadow-sm">
                                                            <div class="card-body">
                                                                <!-- Note Content -->
                                                                <p class="card-text text-dark mb-2" style="white-space: pre-line;">
                                                                    <?= htmlspecialchars($note['note_text']); ?>
                                                                </p>

                                                                <hr class="my-2 text-muted">

                                                                <!-- Dates & Times -->
                                                                <div class="d-flex flex-wrap justify-content-between align-items-center fs-7 text-muted gap-2">
                                                                    <div>
                                                                        <i class="bi bi-clock me-1"></i>
                                                                        <strong>Added:</strong> <?= date('d M, Y h:i A', strtotime($note['created_at'])); ?>
                                                                    </div>

                                                                    <?php if (!empty($note['next_meeting'])): ?>
                                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                                            <i class="bi bi-calendar-event me-1"></i> Next Meeting: <?= date('d M, Y h:i A', strtotime($note['next_meeting'])); ?>
                                                                        </span>
                                                                    <?php endif; ?>

                                                                    <?php if (!empty($note['notification_time'])): ?>
                                                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                                                            <i class="bi bi-bell me-1"></i> Reminder: <?= date('d M, Y h:i A', strtotime($note['notification_time'])); ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center py-4 text-muted">
                                                    <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
                                                    কোনো নোট বা রিমাইন্ডার পাওয়া যায়নি।
                                                </div>
                                            <?php endif; ?>

                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                কোনো স্কুলের তথ্য পাওয়া যায়নি।
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add School Modal Structure -->
<div class="modal fade" id="addSchoolModal" tabindex="-1" aria-labelledby="addSchoolModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="addSchoolModalLabel">Add New School</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="addSchoolForm" action="core/add_run_core.php" method="POST">

                    <!-- School Name & Phone Number -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="schoolName" class="form-label">School Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="schoolName" name="school_name" placeholder="Enter school name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phoneNumber" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phoneNumber" name="phone_number" placeholder="017XXXXXXXX" required>
                        </div>
                    </div>

                    <!-- Address Selection Section (Required) -->
                    <div class="row">
                        <!-- Division -->
                        <div class="col-md-4 mb-3">
                            <label for="division" class="form-label">Division <span class="text-danger">*</span></label>
                            <select class="form-select" id="division" name="division" required>
                                <option value="" selected disabled>Select Division</option>
                                <option value="Dhaka">Dhaka</option>
                                <option value="Chattogram">Chattogram</option>
                                <option value="Rajshahi">Rajshahi</option>
                                <option value="Khulna">Khulna</option>
                                <option value="Barishal">Barishal</option>
                                <option value="Sylhet">Sylhet</option>
                                <option value="Rangpur">Rangpur</option>
                                <option value="Mymensingh">Mymensingh</option>
                            </select>
                        </div>

                        <!-- District -->
                        <div class="col-md-4 mb-3">
                            <label for="district" class="form-label">District <span class="text-danger">*</span></label>
                            <select class="form-select" id="district" name="district" required disabled>
                                <option value="" selected disabled>Select District</option>
                            </select>
                        </div>

                        <!-- Upazila / Sub-District -->
                        <div class="col-md-4 mb-3">
                            <label for="upazila" class="form-label">Sub-District / Upazila <span class="text-danger">*</span></label>
                            <select class="form-select" id="upazila" name="upazila" required disabled>
                                <option value="" selected disabled>Select Sub-District</option>
                            </select>
                        </div>
                    </div>

                    <!-- Detailed Address -->
                    <div class="mb-3">
                        <label for="addressDetails" class="form-label">Address Line (Road / Village / Area)</label>
                        <input type="text" class="form-control" id="addressDetails" name="address_details" placeholder="House/Road no, Area etc.">
                    </div>

                    <!-- Monthly Fee & Yearly Fee -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="monthlyFee" class="form-label">Monthly Fee<span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="monthlyFee" name="monthly_fee" placeholder="00" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="yearlyFee" class="form-label">Yearly Fee<span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="yearlyFee" name="yearly_fee" placeholder="00" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="websiteFee" class="form-label">Website fee(op)</label>
                            <input type="number" class="form-control" id="websiteFee" name="website_fee" placeholder="00">
                        </div>

                    </div>

                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addSchoolForm" class="btn btn-success">Add School</button>
            </div>

        </div>
    </div>
</div>


<!-- Notification List Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content text-start">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title d-flex align-items-center gap-2" id="notificationModalLabel">
                    <i class="bi bi-bell me-1"></i> Reminders & Notifications
                    <?php if ($notifCount > 0): ?>
                        <span class="badge bg-light text-primary rounded-pill fs-7"><?= $notifCount; ?> Unread</span>
                    <?php endif; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="max-height: 65vh; overflow-y: auto;">
                <?php if (!empty($pendingNotifications)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($pendingNotifications as $notif): ?>
                            <div class="list-group-item p-3 border-bottom hover-bg-light">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="mb-0 fw-bold text-primary">
                                        <i class="bi bi-building me-1"></i> <?= htmlspecialchars($notif['school_name']); ?>
                                    </h6>
                                    <small class="text-danger fw-semibold bg-danger-subtle px-2 py-1 rounded border border-danger-subtle">
                                        <i class="bi bi-alarm me-1"></i> <?= date('d M Y, h:i A', strtotime($notif['notification_time'])); ?>
                                    </small>
                                </div>

                                <p class="mb-2 text-secondary" style="white-space: pre-line; font-size: 0.95rem;">
                                    <?= htmlspecialchars($notif['note_text']); ?>
                                </p>

                                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top fs-7">
                                    <!-- Added Date & Next Meeting Info -->
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="text-muted">
                                            <i class="bi bi-clock-history me-1"></i> Added: <?= date('d M, h:i A', strtotime($notif['created_at'])); ?>
                                        </span>

                                        <!-- Next Meeting Time (যদি ডাটাবেজে সেট করা থাকে) -->
                                        <?php if (!empty($notif['next_meeting'])): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                <i class="bi bi-calendar-event me-1"></i> Next Meeting: <?= date('d M Y, h:i A', strtotime($notif['next_meeting'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">
                                                <i class="bi bi-calendar-x me-1"></i> No Meeting Set
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Mark as Read Button -->
                                    <form action="core/mark_notification_read.php" method="POST" class="m-0">
                                        <input type="hidden" name="note_id" value="<?= $notif['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-check2-circle me-1"></i> Done
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-bell-slash fs-1 d-block mb-3 text-secondary"></i>
                        <h5>কোনো Unread নোটিফিকেশন নেই!</h5>
                        <p class="mb-0 fs-7">সব রিমাইন্ডার দেখা হয়ে গেছে।</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<!-- Dynamic Dropdown JS Script -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const locationData = {
            "Dhaka": {
                "Dhaka": ["Adabor", "Badda", "Bangshal", "Biman Bandar", "Cantonment", "Chakbazar", "Dhanmondi", "Dhamrai", "Dohar", "Gendaria", "Gulshan", "Hazaribagh", "Jatrabari", "Kadamtali", "Kafrul", "Kalabagan", "Kamrangirchar", "Keraniganj", "Khilgaon", "Khilkhet", "Kotwali", "Lalbagh", "Mirpur", "Mohammadpur", "Motijheel", "Nawabganj", "New Market", "Pallabi", "Paltan", "Ramna", "Rampur", "Rupnagar", "Sabujbagh", "Savar", "Shah Ali", "Shahbagh", "Sher-e-Bangla Nagar", "Shyampur", "Sutrapr", "Tejgaon", "Tejgaon Industrial Area", "Turag", "Uttara East", "Uttara West", "Vatara"],
                "Gazipur": ["Gazipur Sadar", "Kaliakair", "Kaliganj", "Kapasia", "Sreepur", "Tongi East", "Tongi West"],
                "Narayanganj": ["Araihazar", "Bandar", "Narayanganj Sadar", "Rupganj", "Sonargaon", "Siddhirganj"],
                "Tangail": ["Basail", "Bhuapur", "Delduar", "Dhanbari", "Ghatail", "Gopalpur", "Kalihati", "Madhupur", "Mirzapur", "Nagarpur", "Sakhipur", "Tangail Sadar"],
                "Faridpur": ["Alfadanga", "Bhanga", "Boalmari", "Charbhadrasan", "Faridpur Sadar", "Madhukhali", "Nagarkanda", "Sadarpur", "Saltha"],
                "Gopalganj": ["Gopalganj Sadar", "Kashiani", "Kotalipara", "Muksudpur", "Tungipara"],
                "Kishoreganj": ["Itna", "Katiadi", "Bhairab", "Nikli", "Uthali", "Kishoreganj Sadar", "Karimganj", "Bajitpur", "Kaptatai", "Mithamain", "Pakundia", "Tarail", "Hossainpur"],
                "Madaripur": ["Madaripur Sadar", "Kalkini", "Rajoir", "Shibchar", "Dasar"],
                "Manikganj": ["Manikganj Sadar", "Singair", "Shivalaya", "Saturia", "Harirampur", "Gheor", "Daulatpur"],
                "Munshiganj": ["Munshiganj Sadar", "Tongibari", "Sreenagar", "Lohajang", "Garia", "Sirajdikhan"],
                "Rajbari": ["Rajbari Sadar", "Goalanda", "Pangsha", "Baliakandi", "Kalukhali"],
                "Shariatpur": ["Shariatpur Sadar", "Naria", "Zajira", "Gosairhat", "Bhedarganj", "Damudya"],
                "Narsingdi": ["Narsingdi Sadar", "Belabo", "Monohardi", "Palash", "Raipura", "Shibpur"]
            },
            "Chattogram": {
                "Chattogram": ["Anwara", "Banshkhali", "Boalkhali", "Chandanaish", "Fatikchhari", "Hathazari", "Lohagara", "Mirsarai", "Patiya", "Rangunia", "Raozan", "Sandwip", "Satkania", "Sitakunda", "Karnafuli", "Bayezid Bostami", "Chandgaon", "Double Mooring", "Halishahar", "Kotwali", "Khulshi", "Panchlaish", "Pahartali", "Patenga"],
                "Cox's Bazar": ["Cox's Bazar Sadar", "Chakaria", "Kutubdia", "Maheshkhali", "Ramu", "Teknaf", "Ukhia", "Pekua", "Eidgaon"],
                "Cumilla": ["Barura", "Brahmanpara", "Burichang", "Chandina", "Chauddagram", "Daudkandi", "Debidwar", "Homna", "Laksam", "Monohargonj", "Meghna", "Muradnagar", "Nangalkot", "Cumilla Adarsha Sadar", "Cumilla Sadar Dakshin", "Titas", "Lalmai"],
                "Feni": ["Feni Sadar", "Chhagalnaiya", "Daganbhuiyan", "Parshuram", "Fulgazi", "Sonagazi"],
                "Noakhali": ["Noakhali Sadar", "Begumganj", "Chatkhil", "Companiganj", "Hatiya", "Senbagh", "Subarnachar", "Sonaimuri", "Kabirhat"],
                "Brahmanbaria": ["Brahmanbaria Sadar", "Ashuganj", "Akhaura", "Bancharampur", "Bijoynagar", "Kasba", "Nabinagar", "Nasirnagar", "Sarail"],
                "Chandpur": ["Chandpur Sadar", "Faridganj", "Haimchar", "Hajiganj", "Kachua", "Matlab Dakshin", "Matlab Uttar", "Shahrasti"],
                "Lakshmipur": ["Lakshmipur Sadar", "Raipur", "Ramganj", "Ramgati", "Kamalnagar"],
                "Khagrachhari": ["Khagrachhari Sadar", "Dighinala", "Lakshmichhari", "Mahalchhari", "Manikchhari", "Matiranga", "Panchhari", "Ramgarh", "Guimara"],
                "Rangamati": ["Rangamati Sadar", "Belaichhari", "Baghaichhari", "Barkal", "Juraichhari", "Kaptai", "Kawkhali", "Langadu", "Naniarchar", "Rajasthali"],
                "Bandarban": ["Bandarban Sadar", "Thanchi", "Lama", "Naikhongchhari", "Rowangchhari", "Ruma", "Ali Kadam"]
            },
            "Rajshahi": {
                "Rajshahi": ["Bagha", "Bagmara", "Charghat", "Durgapur", "Godagari", "Mohanpur", "Paba", "Puthia", "Tanore", "Boalia", "Rajputra", "Motihar", "Shah Makhdum", "Chandrima"],
                "Bogra": ["Bogra Sadar", "Adamdighi", "Dhunat", "Dhupchanchia", "Gabtali", "Kahaloo", "Nandigram", "Sariakandi", "Shajahanpur", "Sherpur", "Shibganj", "Sonatala"],
                "Pabna": ["Pabna Sadar", "Atgharia", "Bera", "Bhangura", "Chatmohar", "Faridpur", "Ishwardi", "Santhia", "Sujanagar"],
                "Joypurhat": ["Joypurhat Sadar", "Akkelpur", "Kalai", "Khetlal", "Panchbibi"],
                "Naogaon": ["Naogaon Sadar", "Atrai", "Badalgachhi", "Dhamoirhat", "Manda", "Mahadevpur", "Niamatpur", "Patnitala", "Raninagar", "Sapahar", "Porsha"],
                "Natore": ["Natore Sadar", "Baraigram", "Bagatipara", "Gurudaspur", "Lalpur", "Singra", "Naldanga"],
                "Nawabganj": ["Chapai Nawabganj Sadar", "Bholahat", "Gomastapur", "Nachole", "Shibganj"],
                "Sirajganj": ["Sirajganj Sadar", "Belkuchi", "Chauhali", "Kamarkhanda", "Kazipur", "Rayganj", "Shahjadpur", "Tarash", "Ullapara"]
            },
            "Khulna": {
                "Khulna": ["Batiaghata", "Dacope", "Dumuria", "Dighalia", "Koyra", "Paikgachha", "Phultala", "Rupsha", "Terokhada", "Aranghata", "Daulatpur", "Harintana", "Khalishpur", "Khan Jahan Ali", "Khulna Sadar", "Sonadanga"],
                "Bagerhat": ["Bagerhat Sadar", "Mongla", "Chitalmari", "Fakirhat", "Kachua", "Mollahat", "Rampal", "Sarankhola", "Morrelganj"],
                "Shatkhira": ["Satkhira Sadar", "Assasuni", "Debhata", "Kalaroa", "Kaliganj", "Shyamnagar", "Tala"],
                "Jessore": ["Jessore Sadar", "Abhaynagar", "Bagherpara", "Chaugachha", "Jhikargachha", "Keshabpur", "Manirampur", "Sharsha"],
                "Jhenaidah": ["Jhenaidah Sadar", "Harakundu", "Kaliganj", "Kotchandpur", "Maheshpur", "Sailkupa"],
                "Kushtia": ["Kushtia Sadar", "Kumarkhali", "Daulatpur", "Mirpur", "Bheramara", "Khoksa"],
                "Magura": ["Magura Sadar", "Mohammadpur", "Shalisha", "Sreepur"],
                "Meherpur": ["Meherpur Sadar", "Gangni", "Mujibnagar"],
                "Narail": ["Narail Sadar", "Kalia", "Lohagara"],
                "Chuadanga": ["Chuadanga Sadar", "Alamdanga", "Damurhuda", "Jibannagar"]
            },
            "Barishal": {
                "Barishal": ["Barishal Sadar", "Agailjhara", "Babuganj", "Bakerganj", "Banaripara", "Gaurnadi", "Hizla", "Mehendiganj", "Muladi", "Wazirpur"],
                "Barguna": ["Barguna Sadar", "Amatali", "Bamna", "Betagi", "Patharghata", "Taltali"],
                "Bhola": ["Bhola Sadar", "Burhanuddin", "Char Fasson", "Daulatkhan", "Lalmohan", "Manpura", "Tazumuddin"],
                "Jhalokati": ["Jhalokati Sadar", "Kathalia", "Nalchity", "Rajapur"],
                "Patuakhali": ["Patuakhali Sadar", "Bawphal", "Dashmina", "Galachipa", "Kalapara", "Mirzaganj", "Dumki", "Rangabali"],
                "Pirojpur": ["Pirojpur Sadar", "Bhandaria", "Kawkhali", "Mathbaria", "Nazirpur", "Nesarabad (Swarupkati)", "Zianagar"]
            },
            "Sylhet": {
                "Sylhet": ["Sylhet Sadar", "Balaganj", "Beanibazar", "Bishwanath", "Companiganj", "Fenchuganj", "Golapganj", "Gowainghat", "Jaintiapur", "Kanaighat", "Zakiganj", "South Surma", "Osmani Nagar"],
                "Habiganj": ["Habiganj Sadar", "Ajmiriganj", "Bahubal", "Baniyachong", "Chhatak", "Chunarughat", "Nabiganj", "Madhabpur", "Lakhai", "Sayestaganj"],
                "Maulvibazar": ["Maulvibazar Sadar", "Barlekha", "Juri", "Kamalganj", "Kulaura", "Rajnagar", "Sreemangal"],
                "Sunamganj": ["Sunamganj Sadar", "Bishwamambharpur", "Chhatak", "Derai", "Dharamapasha", "Dowarabazar", "Jagannathpur", "Jamalganj", "Shantiganj", "Sullah", "Tahirpur", "Madhyanagar"]
            },
            "Rangpur": {
                "Rangpur": ["Rangpur Sadar", "Badarganj", "Gangachara", "Kaunia", "Mithapukur", "Pirgachha", "Pirganj", "Taraganj"],
                "Dinajpur": ["Dinajpur Sadar", "Birampur", "Birganj", "Biral", "Bochaganj", "Chirirbandar", "Phulbari", "Ghoraghat", "Hakimpur", "Kaharole", "Khansama", "Nawabganj", "Parbatipur"],
                "Gaibandha": ["Gaibandha Sadar", "Phulchhari", "Gobindaganj", "Palashbari", "Sadullapur", "Saghata", "Sundarganj"],
                "Kurigram": ["Kurigram Sadar", "Bhurungamari", "Char Rajibpur", "Chilmari", "Phulbari", "Nageshwari", "Rajarhat", "Raomari", "Ulipur"],
                "Lalmonirhat": ["Lalmonirhat Sadar", "Aditmari", "Hatibandha", "Kaliganj", "Patgram"],
                "Nilphamari": ["Nilphamari Sadar", "Dimla", "Domar", "Jaldhaka", "Kishoreganj", "Saidpur"],
                "Panchagarh": ["Panchagarh Sadar", "Atwari", "Boda", "Debiganj", "Tetulia"],
                "Thakurgaon": ["Thakurgaon Sadar", "Baliadangi", "Haripur", "Pirganj", "Ranisankail"]
            },
            "Mymensingh": {
                "Mymensingh": ["Mymensingh Sadar", "Bhaluka", "Dhobaura", "Fulbaria", "Gaffargaon", "Gauripur", "Haluaghat", "Ishwarganj", "Muktagachha", "Nandail", "Phulpur", "Trishal", "Tara Khanda"],
                "Jamalpur": ["Jamalpur Sadar", "Baksiganj", "Dewanganj", "Isampur", "Madarganj", "Melandaha", "Sarishabari"],
                "Netrokona": ["Netrokona Sadar", "Atpara", "Barhatta", "Durgapur", "Khaliajuri", "Kalmakanda", "Kendua", "Madan", "Mohanganj", "Purbadhala"],
                "Sherpur": ["Sherpur Sadar", "Jhenaigati", "Nakla", "Nalitabari", "Sreebardi"]
            }
        };

        const divisionSelect = document.getElementById('division');
        const districtSelect = document.getElementById('district');
        const upazilaSelect = document.getElementById('upazila');

        // ১. ডিভিশন পরিবর্তনের ইভেন্ট
        divisionSelect.addEventListener('change', function() {
            const selectedDivision = this.value;

            // জেলা ও উপজিলা ড্রপডাউন রিসেট
            districtSelect.innerHTML = '<option value="" selected disabled>Select District</option>';
            upazilaSelect.innerHTML = '<option value="" selected disabled>Select Sub-District</option>';
            upazilaSelect.disabled = true;

            if (selectedDivision && locationData[selectedDivision]) {
                districtSelect.disabled = false;
                Object.keys(locationData[selectedDivision]).forEach(function(district) {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    districtSelect.appendChild(option);
                });
            } else {
                districtSelect.disabled = true;
            }
        });

        // ২. জেলা পরিবর্তনের ইভেন্ট
        districtSelect.addEventListener('change', function() {
            const selectedDivision = divisionSelect.value;
            const selectedDistrict = this.value;

            // উপজিলা ড্রপডাউন রিসেট
            upazilaSelect.innerHTML = '<option value="" selected disabled>Select Sub-District</option>';

            if (selectedDivision && selectedDistrict && locationData[selectedDivision][selectedDistrict]) {
                upazilaSelect.disabled = false;
                locationData[selectedDivision][selectedDistrict].forEach(function(upazila) {
                    const option = document.createElement('option');
                    option.value = upazila;
                    option.textContent = upazila;
                    upazilaSelect.appendChild(option);
                });
            } else {
                upazilaSelect.disabled = true;
            }
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const searchInput = document.getElementById('schoolSearchInput');
        // টেবিলের সবকটি রো (Tr) সিলেক্ট করা হচ্ছে (tbody এর ভেতর)
        const tableRows = document.querySelectorAll('.table tbody tr');

        if (searchInput) {
            searchInput.addEventListener('keyup', function () {
                const searchTerm = this.value.toLowerCase().trim();

                tableRows.forEach(row => {
                    // কোনো রো-তে যদি 'কোনো স্কুলের তথ্য পাওয়া যায়নি' বা empty মেসেজ থাকে তা স্কিপ করবে
                    if (row.children.length === 1) return;

                    // রো-এর সম্পূর্ণ টেক্সট নেওয়া হচ্ছে
                    const rowText = row.textContent.toLowerCase();

                    // যদি সার্চের লেখা টেক্সটের সাথে মিলে যায় তবে দেখাবে, না মিললে হাইড করে দেবে
                    if (rowText.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });
</script>

<?php require '../layout/layout_footer.php'; ?>