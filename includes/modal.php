<!-- Upload Modal -->
<div id="uploadModal" class="modal-bg modal-center fixed inset-0 hidden z-50">
    <div class="bg-white p-6 rounded-xl w-full max-w-md relative shadow-xl">
        <h2 class="text-xl font-bold mb-4 text-gray-800">📤 Upload School</h2>
        <form method="POST" enctype="multipart/form-data" class="grid gap-4">
            <input type="hidden" name="action" value="create_school">

            <input name="district" type="text" placeholder="District" class="p-2 border rounded" required>
            <input name="upazila" type="text" placeholder="Upazila" class="p-2 border rounded" required>
            <input name="schoolName" type="text" placeholder="School Name" class="p-2 border rounded" required>
            <input name="mobile" type="text" placeholder="Mobile Number" class="p-2 border rounded" required>
            <input name="photo" type="file" accept="image/*" class="p-2 border rounded" required>

            <select name="status" class="p-2 border rounded">
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
            </select>

            <textarea name="note" placeholder="First Note লিখুন..." class="p-2 border rounded"></textarea>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeUploadModal()"
                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Submit</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal-bg modal-center fixed inset-0 hidden z-50">
    <div class="bg-white p-6 rounded-xl w-full max-w-md relative shadow-xl">
        <h2 class="text-xl font-bold mb-4 text-gray-800">✏️ Edit School</h2>
        <form method="POST" enctype="multipart/form-data" class="grid gap-4">
            <input type="hidden" name="action" value="update_school">
            <input type="hidden" name="id" id="eId">

            <input id="eSchoolName" name="eSchoolName" type="text" placeholder="School Name" class="p-2 border rounded"
                required>
            <input id="eMobile" name="eMobile" type="text" placeholder="Mobile Number" class="p-2 border rounded">
            <input id="eDistrict" name="eDistrict" type="text" placeholder="District" class="p-2 border rounded"
                required>
            <input id="eUpazila" name="eUpazila" type="text" placeholder="Upazila" class="p-2 border rounded" required>

            <select id="eStatus" name="eStatus" class="p-2 border rounded">
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
            </select>

            <input id="ePhoto" name="ePhoto" type="file" accept="image/*" class="p-2 border rounded">

            <div class="flex justify-end gap-2 mt-2">
                <button type="button" onclick="closeEditModal()"
                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Submit</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Note Modal -->
<div id="addNoteModal" class="modal-bg modal-center fixed inset-0 hidden z-50">
    <div class="bg-white p-6 rounded-xl w-full max-w-md relative shadow-xl">
        <h2 class="text-xl font-bold mb-4 text-gray-800">📝 Add Note</h2>
        <form method="POST" class="grid gap-4">
            <input type="hidden" name="action" value="add_note">
            <input type="hidden" name="school_id" id="note_school_id">
            <textarea name="note_text" id="addNoteText" placeholder="Write note here..."
                class="p-2 border rounded w-full" required></textarea>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeAddNoteModal()"
                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Add
                    Note</button>
            </div>
        </form>
    </div>
</div>


<!-- View Notes Modal -->
<div id="notesModal" class="modal-bg modal-center fixed inset-0 hidden z-50">
    <div class="bg-white p-6 rounded-xl w-full max-w-[95vh] relative shadow-xl max-h-[95vh] overflow-y-auto">
        <h2 id="notesModalTitle" class="text-2xl font-bold mb-4 text-gray-800">
            📓 All Notes
        </h2>
        <div id="notesModalContent" class="space-y-3"></div>


        <div class="flex justify-end mt-4">
            <button onclick="closeNotesModal()" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                Close
            </button>
        </div>
    </div>
</div>