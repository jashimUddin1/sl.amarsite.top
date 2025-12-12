<?php
// নিচের অংশ আসলে এখানে দরকার নেই, কারণ সব ক্ষেত্রেই উপরে exit হয়ে যাবে,
// তবে আগের কোড থেকে রেখে দিচ্ছি যদি UI page বানাও ভবিষ্যতে।
$pageTitle   = 'Add School - School List';
$pageHeading = 'Add School';
$activeMenu  = 'schools';
require 'layout_header.php';
?>

<div class="max-w-xl mx-auto bg-white rounded-xl shadow p-4">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-bold text-slate-800">New School</h2>
        <a href="schools.php"
            class="text-xs sm:text-sm px-3 py-1.5 rounded border border-slate-300 text-slate-700 hover:bg-slate-100">
            ◀ Back to List
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="mb-3 rounded bg-red-50 border border-red-200 text-red-700 text-sm p-2">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="core/create_core.php" method="POST" enctype="multipart/form-data" class="space-y-3">
        <input type="hidden" name="action" value="create_school">
        <div>
            <label class="block text-xs font-semibold mb-1 text-slate-700">
                District<span class="text-red-500">*</span>
            </label>
            <input type="text" name="district" class="w-full p-2 border rounded text-sm" required>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1 text-slate-700">
                Upazila<span class="text-red-500">*</span>
            </label>
            <input type="text" name="upazila" class="w-full p-2 border rounded text-sm"required>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1 text-slate-700">
                School Name<span class="text-red-500">*</span>
            </label>
            <input type="text" name="school_name" class="w-full p-2 border rounded text-sm" required>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1 text-slate-700">Mobile</label>
            <input type="text" name="mobile" class="w-full p-2 border rounded text-sm">
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1 text-slate-700">Status</label>
            <select name="status" class="w-full p-2 border rounded text-sm">
                <option value="Pending" >Pending</option>
                <option value="Approved">Approved</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1 text-slate-700">Photo (optional)</label>
            <input type="file" name="photo" accept="image/*" class="w-full text-sm">
            <p class="text-[11px] text-slate-500 mt-1">
                বড় ছবি আপলোড করলেও অটো কমপ্রেস হয়ে ছোট সাইজে সেভ হবে (JPG)।
            </p>
        </div>

        <div class="pt-2 flex justify-end gap-2">
            <a href="schools.php"
                class="px-4 py-2 rounded border border-slate-300 text-sm text-slate-700 hover:bg-slate-100">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                Entry
            </button>
        </div>
    </form>
</div>

<?php
require 'layout_footer.php';
