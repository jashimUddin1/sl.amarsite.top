<script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById('schoolSearchInput');
        // টেবিলের সবকটি রো (Tr) সিলেক্ট করা হচ্ছে (tbody এর ভেতর)
        const tableRows = document.querySelectorAll('.table tbody tr');

        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
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


<!-- Edit Note Modal -->
<div class="modal fade" id="editNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">✏️ Edit Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editNoteForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="note_id" id="edit_note_id">

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Note Content</label>
                        <textarea name="note_text" id="edit_note_text" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notification Time</label>
                        <input type="datetime-local" name="notification_time" id="edit_notification_time" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Next Meeting Time</label>
                        <input type="datetime-local" name="next_meeting" id="edit_next_meeting" class="form-control">
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

<!-- Note Edit & Delete AJAX Script -->
<script>
    document.addEventListener("DOMContentLoaded", function() {

        // ১. Edit বাটনে ক্লিক করলে Modal-এ ডাটা সেট করা
        $(document).on('click', '.edit-note-btn', function() {
            let noteId = $(this).data('id');
            let noteText = $(this).data('note');
            let notifTime = $(this).data('notif');
            let meetingTime = $(this).data('meeting');

            $('#edit_note_id').val(noteId);
            $('#edit_note_text').val(noteText);

            // datetime-local ইনপুট ফরম্যাট অ্যাডজাস্ট করা (YYYY-MM-DDTHH:MM)
            if (notifTime) {
                $('#edit_notification_time').val(notifTime.replace(" ", "T").substring(0, 16));
            } else {
                $('#edit_notification_time').val('');
            }

            if (meetingTime) {
                $('#edit_next_meeting').val(meetingTime.replace(" ", "T").substring(0, 16));
            } else {
                $('#edit_next_meeting').val('');
            }

            // Notification Modal খোলা থাকলে তা বন্ধ করে Edit Modal ওপেন করা
            $('#notificationModal').modal('hide');
            $('#editNoteModal').modal('show');
        });

        // ২. Edit Note Form Submit (AJAX)
        $('#editNoteForm').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                url: 'core/edit_delete_note_core.php', // ফাইল পাথটি আপনার প্রজেক্ট অনুযায়ী সঠিক আছে কিনা চেক করবেন
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        location.reload(); // সফল হলে পেজ রিফ্রেশ হবে
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while updating the note.');
                }
            });
        });

        // ৩. Delete Note Action (AJAX)
        $(document).on('click', '.delete-note-btn', function() {
            let noteId = $(this).data('id');

            if (confirm('আপনি কি সত্যিই এই নোটটি মুছে ফেলতে চান?')) {
                $.ajax({
                    url: 'core/edit_delete_note_core.php',
                    type: 'POST',
                    data: {
                        action: 'delete',
                        note_id: noteId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert(response.message);
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while deleting the note.');
                    }
                });
            }
        });

    });
</script>

