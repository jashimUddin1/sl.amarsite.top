<?php //add_run_note.php
require_once '../auth/config.php';

if (!isset($_GET['school_id']) || intval($_GET['school_id']) <= 0) {
    header("Location: add_run.php");
    exit();
}

$school_id = isset($_GET['school_id']) ? intval($_GET['school_id']) : 0;

$schoolStmt = $pdo->prepare("SELECT * FROM add_run WHERE id = :id ");
$schoolStmt->execute([":id" => $school_id]);
$school = $schoolStmt->fetch(PDO::FETCH_ASSOC);

if (!$school) {
    die('School not found!');
}

$noteStmt = $pdo->prepare("SELECT * FROM add_run_note WHERE school_id = :school_id ORDER BY id DESC");
$noteStmt->execute([':school_id' => $school_id]);
$notes = $noteStmt->fetchAll(PDO::FETCH_ASSOC);


// ====== Layout Vars ======
$pageTitle = 'Add Run Note - School List';
$pageHeading = 'Add Run Note';
$activeMenu = 'addRun';

require '../layout/layout_header.php';
?>

<div class="add_run_note">
    <div class="note_header d-flex justify-content-between align-items-center pb-3">
        <div class="note_header_left">
            <a href="add_run.php" class="btn btn-primary">Back</a>
        </div>
        <div class="note_header_content text-center">
            <h2 class="fs-3"><?= htmlspecialchars($school['school_name']); ?></h2>
            <h5><?= htmlspecialchars($school['district']) . ", " . htmlspecialchars($school['upazila']);  ?></h5>
        </div>
        <div class="note_header_right">
            <button type="button" class="btn btn-success"
                data-bs-toggle="modal"
                data-bs-target="#addNoteModal">
                Add note
            </button>
        </div>
    </div>
    <div class="note_body">
        <div class="card-header bg-dark px-4 py-3 mb-3 rounded-2 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white fw-bold"><i class="bi bi-journal-text text-primary me-2"></i>Note History</h5>
            <span class="badge bg-primary rounded-3"><?= count($notes); ?> Notes Found</span>
        </div>

        <!-- ====== Success & Error Notification Messages ====== -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <?= $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <!-- =================================================== -->

        <?php if (!empty($notes)): ?>
            <div class="timeline">
                <?php foreach ($notes as $note): ?>
                    <div class="card mb-3 border-0 bg-light shadow-sm">
                        <div class="card-body">
                            <!-- Note Content -->
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <p class="card-text text-dark mb-2" style="white-space: pre-line;">
                                    <?= htmlspecialchars($note['note_text']); ?>
                                </p>
                                <div class="noteAction text-end flex-shrink-0">
                                    <!-- Edit Button -->
                                    <button type="button" class="btn btn-outline-primary mb-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editNoteModal_<?= $note['id']; ?>"
                                        title="Edit Note">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <!-- Delete Button Form -->
                                    <form action="core/note_delete_core.php" method="POST" class="d-inline" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই নোটটি মুছে ফেলতে চান?');">
                                        <input type="hidden" name="note_id" value="<?= $note['id']; ?>">
                                        <input type="hidden" name="school_id" value="<?= $school_id; ?>">
                                        <button type="submit" class="btn btn-outline-danger mb-1" title="Delete Note">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

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
                এই স্কুলের কোনো নোট পাওয়া যায়নি।
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- ================= Add Note Modal ================= -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog  modal-dialog-centered">
        <div class="modal-content text-start">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Add New Note</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="core/add_note_core2.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="school_id" value="<?= $school_id; ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Note Text <span class="text-danger">*</span></label>
                        <textarea name="note_text" class="form-control" rows="4" placeholder="নোটের বিবরণ লিখুন..." required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Notification Time (Reminder)</label>
                            <input type="datetime-local" name="notification_time" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Next Meeting Time</label>
                            <input type="datetime-local" name="next_meeting" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"> Save Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= Dynamic Edit Note Modals ================= -->
<?php if (!empty($notes)): ?>
    <?php foreach ($notes as $note): ?>
        <div class="modal fade" id="editNoteModal_<?= $note['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content text-start">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Note</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="core/note_edit_core.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="note_id" value="<?= $note['id']; ?>">
                            <input type="hidden" name="school_id" value="<?= $school_id; ?>">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Note Text</label>
                                <textarea name="note_text" class="form-control" rows="4" required><?= htmlspecialchars($note['note_text']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Notification Time</label>
                                <input type="datetime-local" name="notification_time" class="form-control" value="<?= !empty($note['notification_time']) ? date('Y-m-d\TH:i', strtotime($note['notification_time'])) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Next Meeting Time</label>
                                <input type="datetime-local" name="next_meeting" class="form-control" value="<?= !empty($note['next_meeting']) ? date('Y-m-d\TH:i', strtotime($note['next_meeting'])) : ''; ?>">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require '../layout/layout_footer.php'; ?>