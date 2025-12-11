<?php
require_once 'config.php';
require_login();

// মোট school, approved, pending count
$totalSchools = (int)($pdo->query("SELECT COUNT(*) FROM schools")->fetchColumn() ?? 0);
$approvedSchools = (int)($pdo->query("SELECT COUNT(*) FROM schools WHERE status = 'Approved'")->fetchColumn() ?? 0);
$pendingSchools  = (int)($pdo->query("SELECT COUNT(*) FROM schools WHERE status = 'Pending'")->fetchColumn() ?? 0);

// চাইলে future এ notes/logs countও যোগ করতে পারো

$pageTitle   = 'Dashboard - School List';
$pageHeading = 'Dashboard';
$activeMenu  = 'dashboard';

require 'layout_header.php';
?>

<div class="grid gap-4 md:grid-cols-3 mb-6">
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs text-slate-500 mb-1">Total Schools</div>
        <div class="text-2xl font-bold text-slate-800"><?php echo $totalSchools; ?></div>
    </div>

    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs text-slate-500 mb-1">Approved Schools</div>
        <div class="text-2xl font-bold text-green-600"><?php echo $approvedSchools; ?></div>
    </div>

    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-xs text-slate-500 mb-1">Pending Schools</div>
        <div class="text-2xl font-bold text-orange-500"><?php echo $pendingSchools; ?></div>
    </div>
</div>

<div class="bg-white rounded-xl shadow p-4">
    <h2 class="text-lg font-semibold mb-2 text-slate-800">Quick Links</h2>
    <div class="flex flex-wrap gap-2 text-sm">
        <a href="schools.php"
           class="px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
            Manage Schools
        </a>
        <a href="school_create.php"
           class="px-3 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700">
            + Add New School
        </a>
        <a href="logs.php"
           class="px-3 py-2 rounded bg-slate-800 text-white hover:bg-slate-900">
            View Logs
        </a>
    </div>
</div>

<?php
require 'layout_footer.php';
