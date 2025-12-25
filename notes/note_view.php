<?php
require_once '../auth/config.php';
require_login();

$schoolId = isset($_GET['school_id']) ? (int) $_GET['school_id'] : 0;
if ($schoolId <= 0) {
    header('Location: /index.php');
    exit;
}

// স্কুলের basic info
$stmt = $pdo->prepare("SELECT * FROM schools WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $schoolId]);
$school = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$school) {
    header('Location: /index.php');
    exit;
}

// স্কুলের সব নোট
$notesStmt = $pdo->prepare("
    SELECT sn.*, a.name AS admin_name
    FROM school_notes sn
    LEFT JOIN users a ON sn.updated_by = a.id
    WHERE sn.school_id = :school_id
    ORDER BY sn.id DESC
");
$notesStmt->execute([':school_id' => $schoolId]);
$notes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Notes - ' . ($school['school_name'] ?? 'School');
$pageHeading = 'Notes - ' . ($school['school_name'] ?? '');
$activeMenu = 'home';

require '../layout/layout_header.php';

$dayBnMap = [
    'Sunday' => 'রবিবার',
    'Monday' => 'সোমবার',
    'Tuesday' => 'মঙ্গলবার',
    'Wednesday' => 'বুধবার',
    'Thursday' => 'বৃহস্পতিবার',
    'Friday' => 'শুক্রবার',
    'Saturday' => 'শনিবার',
];

function getBanglaDayFromDateTime(?string $dt, array $map): string
{
    if (empty($dt))
        return 'N/A';
    $ts = strtotime($dt);
    if ($ts === false)
        return 'N/A';
    $dayEn = date('l', $ts);
    return $map[$dayEn] ?? 'N/A';
}
?>
<style>
    .close-custom {
        padding: 12px 12px !important;
    }
</style>

<div class="container-lg my-4">

    <?php if (!empty($_SESSION['note_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
            <?php
            echo htmlspecialchars($_SESSION['note_success']);
            unset($_SESSION['note_success']);
            ?>
            <button type="button" class="btn-close close-custom" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['note_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
            <?php
            echo htmlspecialchars($_SESSION['note_error']);
            unset($_SESSION['note_error']);
            ?>
            <button type="button" class="btn-close close-custom" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">
                Notes - <?php echo htmlspecialchars($school['school_name']); ?>
            </h4>
            <p class="small text-muted mb-0">
                School ID: <?php echo (int) $school['id']; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="../index.php" class="btn btn-sm btn-secondary">← Back to List</a>

            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal"
                data-bs-target="#addNoteModalInline">
                + Add Note
            </button>
        </div>
    </div>

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
                                <th>Note</th>
                                <th>Next Meeting</th>
                                <th>Day</th>
                                <th style="width:150px;">Created By</th>
                                <th style="width:150px;">Created At</th>
                                <th style="width:120px;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $sl = 1; ?>
                            <?php foreach ($notes as $n): ?>
                                <?php
                                $nextMeet = $n['next_meet'] ?? null;
                                $dayNameBn = getBanglaDayFromDateTime($nextMeet, $dayBnMap);
                                ?>
                                <tr class="small">
                                    <td><?php echo $sl++; ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($n['note_text'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars($nextMeet ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($dayNameBn); ?></td>
                                    <td><?php echo htmlspecialchars($n['admin_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($n['created_at'] ?? 'N/A'); ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-edit-note"
                                            data-bs-toggle="modal" data-bs-target="#editNoteModal"
                                            data-note-id="<?php echo (int) ($n['id'] ?? 0); ?>"
                                            data-note-text="<?php echo htmlspecialchars($n['note_text'] ?? ''); ?>"
                                            data-next-meet="<?php echo htmlspecialchars($nextMeet ?? ''); ?>">
                                            Edit
                                        </button>

                                        <form method="POST" action="core/delete_note_core.php" class="d-inline"
                                            onsubmit="return confirm('নোট ডিলিট করতে চান?');">
                                            <input type="hidden" name="note_id" value="<?php echo (int) ($n['id'] ?? 0); ?>">
                                            <input type="hidden" name="school_id" value="<?php echo (int) $schoolId; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Inline Add Note Modal -->
<div class="modal fade" id="addNoteModalInline" tabindex="-1" aria-labelledby="addNoteModalInlineLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="../core/add_note_core.php" class="modal-content">
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="addNoteModalInlineLabel">Add Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="school_id" value="<?php echo (int) $schoolId; ?>">
                <div class="mb-2">
                    <label class="form-label small mb-1">Note</label>
                    <textarea name="note_text" rows="3"
                        placeholder="Write note for <?php echo htmlspecialchars($school['school_name']); ?>"
                        class="form-control form-control-sm" required></textarea>
                </div>

                <div class="mb-2">
                    <label for="nextMeeting" class="form-label small mb-1">Next Meeting</label>
                    <input type="datetime-local" id="nextMeeting" name="next_meeting_date"
                        class="form-control form-control-sm">
                </div>
            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-sm btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Note Modal -->
<div class="modal fade" id="editNoteModal" tabindex="-1" aria-labelledby="editNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="core/update_note_core.php" class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="editNoteModalLabel">Edit Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="note_id" id="editNoteId">
                <input type="hidden" name="school_id" value="<?php echo (int) $schoolId; ?>">

                <div class="mb-2">
                    <label class="form-label small mb-1">Note</label>
                    <textarea name="note_text" id="editNoteText" rows="4" class="form-control form-control-sm"
                        required></textarea>
                </div>

                <div class="mb-2">
                    <label for="nextMeeting" class="form-label small mb-1">Next Meeting</label>
                    <input type="datetime-local" id="nextMeeting" name="next_meeting_date"
                        class="form-control form-control-sm">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-sm btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editButtons = document.querySelectorAll('.btn-edit-note');
        const editNoteIdInput = document.getElementById('editNoteId');
        const editNoteTextInput = document.getElementById('editNoteText');
        const nextMeetingInput = document.getElementById('nextMeeting');

        editButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const noteId = this.getAttribute('data-note-id');
                const noteText = this.getAttribute('data-note-text') || '';
                const nextMeet = this.getAttribute('data-next-meet') || '';

                if (editNoteIdInput) editNoteIdInput.value = noteId;
                if (editNoteTextInput) editNoteTextInput.value = noteText;

                // MySQL DATETIME -> datetime-local (YYYY-MM-DDTHH:MM)
                if (nextMeetingInput) {
                    if (nextMeet) {
                        const formatted = nextMeet.replace(' ', 'T').slice(0, 16);
                        nextMeetingInput.value = formatted;
                    } else {
                        nextMeetingInput.value = '';
                    }
                }
            });
        });
    });
</script>

<?php require '../layout/layout_footer.php'; ?>