<?php
// layout/single_invoice_header.php

if (!isset($pageTitle))
    $pageTitle = 'Admin Panel';
if (!isset($pageHeading))
    $pageHeading = '';
if (!isset($activeMenu))
    $activeMenu = 'home';

$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');

// Unread notification count per-user
$notifyCount = 0;
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id && isset($pdo) && ($pdo instanceof PDO)) {
    try {
        $stmtNotify = $pdo->prepare("
            SELECT COUNT(*) AS cnt
            FROM notifications
            WHERE user_id = :user_id
              AND status = 'unread'
        ");
        $stmtNotify->execute([':user_id' => $user_id]);
        $rowNotify = $stmtNotify->fetch(PDO::FETCH_ASSOC);
        $notifyCount = (int) ($rowNotify['cnt'] ?? 0);
    } catch (Exception $e) {
        $notifyCount = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Font (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- html2canvas (invoice preview image download) -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

    <style>
        body {
            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        /* ===== Invoice page CSS merged (safe, only classes used in invoice page) ===== */
        .invoice-wrapper {
            max-width: 900px;
            margin: 5px auto;
        }

        .btn-reset {
            background-color: #f97373;
            color: #fff;
        }

        .btn-reset:hover {
            background-color: #f05252;
            color: #fff;
        }

        .btn-add-item {
            background-color: #4f46e5;
            color: #fff;
        }

        .btn-add-item:hover {
            background-color: #4338ca;
            color: #fff;
        }

        .btn-footer-add {
            background-color: #16a34a;
            color: #fff;
        }

        .btn-footer-add:hover {
            background-color: #15803d;
            color: #fff;
        }

        .btn-footer-print {
            background-color: #2563eb;
            color: #fff;
        }

        .btn-footer-print:hover {
            background-color: #1d4ed8;
            color: #fff;
        }

        .btn-footer-pdf {
            background-color: #ea580c;
            color: #fff;
        }

        .btn-footer-pdf:hover {
            background-color: #c2410c;
            color: #fff;
        }

        .form-section-title {
            font-weight: 600;
            font-size: 1.05rem;
        }

        .invoice-preview-card {
            max-width: 700px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 18px 35px rgba(15, 23, 42, 0.18);
            padding: 20px 24px 32px;
        }

        .invoice-preview-header-line {
            border-top: 3px solid #e5e7eb;
            margin: 8px 0 12px;
        }

        .invoice-preview-table thead {
            background-color: #22c55e;
            color: #fff;
        }

        .invoice-preview-table,
        .invoice-preview-table th,
        .invoice-preview-table td {
            border: 1px solid #cbd5e1;
        }

        .invoice-preview-table th,
        .invoice-preview-table td {
            padding: 6px 8px;
            font-size: 0.85rem;
        }

        .badge-status-unpaid {
            background-color: #fee2e2;
            color: #b91c1c;
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 0.75rem;
        }

        .btn-download-preview {
            background-color: #16a34a;
            color: #fff;
            border-radius: 999px;
            padding-left: 1.7rem;
            padding-right: 1.7rem;
        }

        .btn-download-preview:hover {
            background-color: #15803d;
            color: #fff;
        }

        .calculator-style {
            background-color: #4F46E5;
            padding: 6px;
            color: white;
            border-radius: 5px;
        }

        .calculator-style.active {
            background-color: #16a34a;
        }
    </style>
</head>

<body class="bg-slate-100">
    <div class="h-screen flex">

        <!-- ✅ Desktop Sidebar -->
        <aside id="sidebarDesktop"
            class="hidden lg:flex lg:flex-col lg:fixed lg:inset-y-0 lg:left-0 lg:w-64 bg-slate-900 text-slate-100 shadow-xl z-30">
            <div class="h-16 flex items-center px-4 border-b border-slate-800">
                <img src="/school_list/assets/edur.png" style="width: 160px;" alt="logo">
            </div>

            <nav class="flex-1 overflow-y-auto py-4">
                <a href="/school_list/index.php"
                    class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'home' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>">
                    <span class="mr-2">🏠</span> Home
                </a>

                <a href="/school_list/pages/dashboard.php"
                    class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'dashboard' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>">
                    <span class="mr-2">📊</span> Dashboard
                </a>

                <a href="/school_list/schools/schools.php"
                    class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'schools' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>">
                    <span class="mr-2">🏫</span> Schools
                </a>

                <a href="/school_list/invoices/invoices.php"
                    class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'invoices' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>">
                    <span class="mr-2">🧾</span> Invoices
                </a>

                <a href="/school_list/pages/notifications.php"
                    class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'notifications' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>">
                    <span class="mr-2">🔔</span> Notifications
                </a>

                <a href="/school_list/logs/logs.php"
                    class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'logs' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>">
                    <span class="mr-2">🧾</span> Logs
                </a>

                <a href="/school_list/pages/trash.php"
                    class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'trash' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>">
                    <span class="mr-2">🗑️</span> Trash
                </a>

                <a href="/school_list/pages/user_reports.php"
                    class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'reports' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>">
                    <span class="mr-2">👤</span> User Reports
                </a>

                <a href="#"
                    class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'settings' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>">
                    <span class="mr-2">⚙️</span> Settings
                </a>
            </nav>

            <div class="border-t border-slate-800 px-4 py-3 text-xs">
                <div class="font-semibold"><?php echo $userName; ?></div>
                <a href="/school_list/auth/logout.php" class="text-slate-300 hover:text-white">Logout</a>
            </div>
        </aside>

        <!-- ✅ Mobile Sidebar (drawer) -->
        <div id="sidebarMobileWrapper" class="fixed inset-0 z-40 hidden lg:hidden" aria-hidden="true">
            <div class="absolute inset-0 bg-black/50" onclick="toggleSidebar()"></div>

            <aside class="absolute inset-y-0 left-0 w-64 bg-slate-900 text-slate-100 shadow-xl flex flex-col">
                <div class="h-16 flex items-center px-4 border-b border-slate-800 justify-between">
                    <img src="/school_list/assets/edur.png" style="width: 160px;" alt="logo">
                    <button type="button" class="text-slate-200 hover:text-white" onclick="toggleSidebar()">✕</button>
                </div>

                <nav class="flex-1 overflow-y-auto py-4">
                    <a href="/school_list/index.php"
                        class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'home' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>"
                        onclick="toggleSidebar()">
                        <span class="mr-2">🏠</span> Home
                    </a>

                    <a href="/school_list/pages/dashboard.php"
                        class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'dashboard' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>"
                        onclick="toggleSidebar()">
                        <span class="mr-2">📊</span> Dashboard
                    </a>

                    <a href="/school_list/schools/schools.php"
                        class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'schools' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>"
                        onclick="toggleSidebar()">
                        <span class="mr-2">🏫</span> Schools
                    </a>

                    <a href="/school_list/invoices/invoices.php"
                        class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'invoices' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>"
                        onclick="toggleSidebar()">
                        <span class="mr-2">🧾</span> Invoices
                    </a>

                    <a href="/school_list/pages/notifications.php"
                        class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'notifications' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>"
                        onclick="toggleSidebar()">
                        <span class="mr-2">🔔</span> Notifications
                    </a>

                    <a href="/school_list/logs/logs.php"
                        class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'logs' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>"
                        onclick="toggleSidebar()">
                        <span class="mr-2">🧾</span> Logs
                    </a>

                    <a href="/school_list/pages/trash.php"
                        class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'trash' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>"
                        onclick="toggleSidebar()">
                        <span class="mr-2">🗑️</span> Trash
                    </a>

                    <a href="/school_list/pages/user_reports.php"
                        class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'reports' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>"
                        onclick="toggleSidebar()">
                        <span class="mr-2">👤</span> User Reports
                    </a>

                    <a href="#"
                        class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'settings' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>"
                        onclick="toggleSidebar()">
                        <span class="mr-2">⚙️</span> Settings
                    </a>
                </nav>

                <div class="border-t border-slate-800 px-4 py-3 text-xs">
                    <div class="font-semibold"><?php echo $userName; ?></div>
                    <a href="/school_list/auth/logout.php" class="text-slate-300 hover:text-white">Logout</a>
                </div>
            </aside>
        </div>

        <!-- ✅ Main area -->
        <div class="flex-1 flex flex-col lg:ml-64">

            <!-- Navbar -->
            <!-- <header class="fixed top-0 left-0 lg:left-64 right-0 h-16 bg-white shadow flex items-center justify-between px-4 z-20">
            <div class="flex items-center gap-3">
                <button type="button"
                        class="lg:hidden inline-flex items-center justify-center p-2 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-100"
                        onclick="toggleSidebar()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <h1 class="text-lg font-semibold text-slate-700">
                    <?php echo htmlspecialchars($pageHeading); ?>
                </h1>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <a href="/school_list/pages/notifications.php"
                       class="text-sm px-2 py-1 rounded text-white <?php echo ($notifyCount > 0) ? 'bg-success' : 'bg-secondary'; ?> hover:bg-secondary">
                        &#128276;
                    </a>

                    <?php if ($notifyCount > 0): ?>
                        <span style="position:absolute; top:-6px; right:-6px; background:red; color:white; padding:1px 6px; font-size:10px; border-radius:50%;">
                            <?php echo $notifyCount; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <span class="hidden sm:inline-block text-sm text-slate-600">
                    <?php echo $userName; ?>
                </span>

                <a href="/school_list/auth/logout.php"
                   class="text-xs sm:text-sm px-3 py-1.5 rounded bg-slate-900 text-white hover:bg-slate-800">
                    Logout
                </a>
            </div>
        </header> -->

            <!-- Page content wrapper -->
            <main class="">