<?php
require_once 'includes/core.php';
?>


<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Note Manager - Final</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .modal-bg {
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-center {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-100 to-indigo-100 min-h-screen">

    <div class="max-w-6xl mx-auto p-6">
        <h1 class="text-3xl font-bold text-center mb-6 text-indigo-600">📘 School Note Manager</h1>

        <!-- Upload Button -->
        <div class="text-center mb-6">
            <button type="button" onclick="openUploadModal()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-bold shadow">
                Upload School
            </button>
        </div>

        <!-- Filter Section -->
        <form method="GET" class="bg-white shadow p-6 rounded-xl mb-8">
            <h2 class="text-xl font-bold mb-4">🔍 Filter School</h2>
            <div class="grid md:grid-cols-3 gap-4">
                <select name="district" id="filterDistrict" onchange="this.form.submit()" class="p-2 border rounded">
                    <option value="">District</option>
                    <?php foreach ($districts as $d): ?>
                        <option value="<?php echo htmlspecialchars($d); ?>" <?php if ($d === $filterDistrict)
                               echo 'selected'; ?>>
                            <?php echo htmlspecialchars($d); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="upazila" id="filterUpazila" onchange="this.form.submit()" class="p-2 border rounded">
                    <option value="">Upazila</option>
                    <?php foreach ($upazilas as $u): ?>
                        <option value="<?php echo htmlspecialchars($u); ?>" <?php if ($u === $filterUpazila)
                               echo 'selected'; ?>>
                            <?php echo htmlspecialchars($u); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="status" id="filterStatus" onchange="this.form.submit()" class="p-2 border rounded">
                    <option value="">All Status</option>
                    <option value="Pending" <?php if ($filterStatus === 'Pending')
                        echo 'selected'; ?>>Pending</option>
                    <option value="Approved" <?php if ($filterStatus === 'Approved')
                        echo 'selected'; ?>>Approved</option>
                </select>
            </div>
            <button type="submit" class="mt-4 w-full bg-gray-800 text-white p-3 rounded-xl font-bold hover:bg-gray-900">
                Apply Filter
            </button>
        </form>

        <!-- School List -->
        <?php include 'includes/school_list.php' ?>

    </div>

    <?php include 'includes/modal.php' ?>

    <script>
        // =============== Upload Modal ===============
        function openUploadModal() {
            document.getElementById("uploadModal").classList.remove("hidden");
        }

        function closeUploadModal() {
            document.getElementById("uploadModal").classList.add("hidden");
        }

        // =============== Edit Modal ===============
        function openEditModal(btn) {
            // data-* attribute থেকে value নেওয়া
            document.getElementById("eId").value = btn.dataset.id;
            document.getElementById("eSchoolName").value = btn.dataset.name;
            document.getElementById("eMobile").value = btn.dataset.mobile;
            document.getElementById("eDistrict").value = btn.dataset.district;
            document.getElementById("eUpazila").value = btn.dataset.upazila;
            document.getElementById("eStatus").value = btn.dataset.status;

            // নতুন ছবি সিলেক্ট করার জন্য input clear
            document.getElementById("ePhoto").value = "";

            document.getElementById("editModal").classList.remove("hidden");
        }

        function closeEditModal() {
            document.getElementById("editModal").classList.add("hidden");
        }

        // =============== Add Note Modal ===============
        function openAddNoteModal(id) {
            // hidden input এ school_id সেট
            document.getElementById("note_school_id").value = id;
            // পুরনো টেক্সট থাকলে clear
            document.getElementById("addNoteText").value = "";
            document.getElementById("addNoteModal").classList.remove("hidden");
        }

        function closeAddNoteModal() {
            document.getElementById("addNoteModal").classList.add("hidden");
        }

        // =============== View All Notes Modal ===============
        function openNotesModal(btn) {
            const id = btn.dataset.id;
            const name = btn.dataset.name;

            // Title এ school name বসানো
            const titleEl = document.getElementById("notesModalTitle");
            if (titleEl) {
                titleEl.textContent = "📓 All Notes - " + name;
            }

            const modal = document.getElementById("notesModal");
            const content = document.getElementById("notesModalContent");
            content.innerHTML = "<p class='text-gray-500'>Loading...</p>";

            // notes.php থেকে এই স্কুলের সব note load করা
            fetch("notes.php?id=" + encodeURIComponent(id))
                .then(function (res) {
                    return res.text();
                })
                .then(function (html) {
                    content.innerHTML = html;
                })
                .catch(function () {
                    content.innerHTML = "<p class='text-red-600'>Could not load notes.</p>";
                });

            modal.classList.remove("hidden");
        }

        function closeNotesModal() {
            document.getElementById("notesModal").classList.add("hidden");
        }
    </script>

</body>

</html>