<?php
// layout_header.php
if (!isset($pageTitle))
    $pageTitle = 'Admin Panel';
if (!isset($pageHeading))
    $pageHeading = '';
if (!isset($activeMenu))
    $activeMenu = 'home';
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');


$notifyCount = 0;
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {
    $stmtNotify = $pdo->prepare("
        SELECT COUNT(*) AS cnt
        FROM notifications
        WHERE user_id = :user_id
          AND status = 'unread'
    ");
    $stmtNotify->execute([':user_id' => $user_id]);
    $rowNotify   = $stmtNotify->fetch(PDO::FETCH_ASSOC);
    $notifyCount = (int)($rowNotify['cnt'] ?? 0);
}


?>
<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100">

    <div class="min-h-screen flex">

        <!-- ✅ Desktop Sidebar -->
        <aside id="sidebarDesktop"
            class="hidden lg:flex lg:flex-col lg:fixed lg:inset-y-0 lg:left-0 lg:w-64 bg-slate-900 text-slate-100 shadow-xl z-30">
            <div class="h-16 flex items-center px-4 border-b border-slate-800">
                <img src="edur.png" style="width: 160px; " alt="logo">
                <!-- <span class="text-lg font-bold tracking-wide">
               
            </span> -->
            </div>

            <nav class="flex-1 overflow-y-auto py-4">
                <a href="index.php"
                    class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'home' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>">
                    <span class="mr-2">🏠</span> Home
                </a>

                <a href="index2.php"
                    class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'dashboard' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>">
                    <span class="mr-2">📊</span> Dashboard
                </a>

                <a href="schools.php"
                    class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'schools' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>">
                    <span class="mr-2">🏫</span> Schools
                </a>

                <a href="notifications.php"
                    class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'notifications' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>">
                    <span class="mr-2">🔔</span> Notifications
                </a>

                <a href="logs.php"
                    class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'logs' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>">
                    <span class="mr-2">🧾</span> Logs
                </a>
                <a href="trash.php"
                    class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'trash' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>">
                    <span class="mr-2">🗑️</span> Trash
                </a>
                <a href="user_reports.php"
                    class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'reports' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>">
                    <span class="mr-2">👤</span> User Reports
                </a>
                <a href="#"
                    class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'settings' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>">
                    <span class="mr-2">⚙️</span> Settings
                </a>
            </nav>

            <div class="border-t border-slate-800 px-4 py-3 text-xs">
                <div class="font-semibold">
                    <?php echo $userName; ?>
                </div>
                <a href="logout.php" class="text-slate-300 hover:text-white">
                    Logout
                </a>
            </div>
        </aside>

        <!-- ✅ Mobile Sidebar (drawer) -->
        <div id="sidebarMobileWrapper" class="fixed inset-0 z-40 hidden lg:hidden" aria-hidden="true">
            <div class="absolute inset-0 bg-black/50" onclick="toggleSidebar()"></div>

            <aside class="absolute inset-y-0 left-0 w-64 bg-slate-900 text-slate-100 shadow-xl flex flex-col">
                <div class="h-16 flex items-center px-4 border-b border-slate-800 justify-between">
                    <img src="edur.png" style="width: 160px; " alt="logo">
                    <!-- <span class="text-lg font-bold tracking-wide">
                    School List
                </span> -->
                    <button type="button" class="text-slate-200 hover:text-white" onclick="toggleSidebar()">
                        ✕
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto py-4">
                    <a href="index.php"
                        class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'home' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>"
                        onclick="toggleSidebar()">
                        <span class="mr-2">🏠</span> Home
                    </a>
                    <a href="index2.php"
                        class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'dashboard' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>"
                        onclick="toggleSidebar()">
                        <span class="mr-2">📊</span> Dashboard
                    </a>
                    <a href="schools.php"
                        class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'schools' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>"
                        onclick="toggleSidebar()">
                        <span class="mr-2">🏫</span> Schools
                    </a>
                    <a href="notifications.php"
                        class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'notifications' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>"
                        onclick="toggleSidebar()">
                        <span class="mr-2">🔔</span> Notifications
                    </a>
                    <a href="logs.php"
                        class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'logs' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>"
                        onclick="toggleSidebar()">
                        <span class="mr-2">🧾</span> Logs
                    </a>
                    <a href="trash.php"
                        class="flex items-center px-4 py-2 text-sm <?php echo $activeMenu === 'trash' ? 'bg-slate-800' : 'hover:bg-slate-800'; ?>"
                        onclick="toggleSidebar()">
                        <span class="mr-2">🗑️</span> Trash
                    </a>
                    <a href="user_reports.php"
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
                    <div class="font-semibold">
                        <?php echo $userName; ?>
                    </div>
                    <a href="logout.php" class="text-slate-300 hover:text-white">
                        Logout
                    </a>
                </div>
            </aside>
        </div>

        <!-- ✅ Main area -->
        <div class="flex-1 flex flex-col lg:ml-64">

            <!-- Navbar -->
            <header
                class="fixed top-0 left-0 lg:left-64 right-0 h-16 bg-white shadow flex items-center justify-between px-4 z-20">
                <div class="flex items-center gap-3">
                    <button type="button"
                        class="lg:hidden inline-flex items-center justify-center p-2 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-100"
                        onclick="toggleSidebar()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
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
                        <a href="notifications.php" class="text-sm sm:text-sm px-2 py-1 rounded text-white
                            <?php echo ($notifyCount > 0) ? 'bg-success' : 'bg-secondary'; ?>
                            hover:bg-secondary">

                            &#128276;
                        </a>

                        <!-- Notification Count Badge -->
                        <?php if ($notifyCount > 0): ?>
                            <span style="
                                position:absolute;
                                top:-6px;
                                right:-6px;
                                background:red;
                                color:white;
                                padding:1px 6px;
                                font-size:10px;
                                border-radius:50%;
                            ">
                                <?php echo $notifyCount; ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <span class="hidden sm:inline-block text-sm text-slate-600">
                        <?php echo $userName; ?>
                    </span>

                    <a href="logout.php"
                        class="text-xs sm:text-sm px-3 py-1.5 rounded bg-slate-900 text-white hover:bg-slate-800">
                        Logout
                    </a>

                </div>

            </header>

            <!-- Page content wrapper -->
            <main class="mt-16  overflow-y-auto">