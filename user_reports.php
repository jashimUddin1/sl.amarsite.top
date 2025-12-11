<?php
require_once 'config.php';
require_login();

// user-wise aggregate
$sql = "
    SELECT
        u.id,
        u.name,
        u.username,

        COALESCE(sc_cre.cnt, 0) + COALESCE(st_cre.cnt, 0) AS total_created,
        COALESCE(sc_upd.cnt, 0) + COALESCE(st_upd.cnt, 0) AS total_updated,
        COALESCE(st_del.cnt, 0) AS total_deleted

    FROM users u

    LEFT JOIN (
        SELECT created_by AS uid, COUNT(*) AS cnt
        FROM schools
        WHERE created_by IS NOT NULL
        GROUP BY created_by
    ) sc_cre ON sc_cre.uid = u.id

    LEFT JOIN (
        SELECT created_by AS uid, COUNT(*) AS cnt
        FROM school_trash
        WHERE created_by IS NOT NULL
        GROUP BY created_by
    ) st_cre ON st_cre.uid = u.id

    LEFT JOIN (
        SELECT updated_by AS uid, COUNT(*) AS cnt
        FROM schools
        WHERE updated_by IS NOT NULL
        GROUP BY updated_by
    ) sc_upd ON sc_upd.uid = u.id

    LEFT JOIN (
        SELECT updated_by AS uid, COUNT(*) AS cnt
        FROM school_trash
        WHERE updated_by IS NOT NULL
        GROUP BY updated_by
    ) st_upd ON st_upd.uid = u.id

    LEFT JOIN (
        SELECT deleted_by AS uid, COUNT(*) AS cnt
        FROM school_trash
        WHERE deleted_by IS NOT NULL
        GROUP BY deleted_by
    ) st_del ON st_del.uid = u.id

    WHERE
        sc_cre.uid IS NOT NULL
        OR st_cre.uid IS NOT NULL
        OR sc_upd.uid IS NOT NULL
        OR st_upd.uid IS NOT NULL
        OR st_del.uid IS NOT NULL

    ORDER BY u.name ASC
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// layout
$pageTitle   = 'User Reports - School List';
$pageHeading = 'User Reports';
$activeMenu  = 'reports';

require 'layout_header.php';
?>

<div class="bg-white rounded-xl shadow p-4 overflow-x-auto">
    <?php if (!$rows): ?>
        <p class="text-center text-gray-500 text-sm py-4">
            এখনও কোনো user-এর activity পাওয়া যায়নি।
        </p>
    <?php else: ?>
        <table class="min-w-full text-sm border-collapse">
            <thead>
                <tr class="bg-slate-100 text-left">
                    <th class="p-2 border">User ID</th>
                    <th class="p-2 border" style="min-width: 160px;">Name</th>
                    <th class="p-2 border" style="min-width: 180px;">UserName</th>
                    <th class="p-2 border">Created Schools</th>
                    <th class="p-2 border">Updated Schools</th>
                    <th class="p-2 border">Deleted Schools</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="p-2 border align-top text-xs">
                            <?php echo (int)$r['id']; ?>
                        </td>
                        <td class="p-2 border align-top font-semibold">
                            <?php echo htmlspecialchars($r['name'] ?? ''); ?>
                        </td>
                        <td class="p-2 border align-top text-xs text-slate-700">
                            <?php echo htmlspecialchars($r['username'] ?? ''); ?>
                        </td>
                        <td class="p-2 border align-top text-center">
                            <span class="inline-block px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 text-xs font-semibold">
                                <?php echo (int)$r['total_created']; ?>
                            </span>
                        </td>
                        <td class="p-2 border align-top text-center">
                            <span class="inline-block px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-xs font-semibold">
                                <?php echo (int)$r['total_updated']; ?>
                            </span>
                        </td>
                        <td class="p-2 border align-top text-center">
                            <span class="inline-block px-2 py-0.5 rounded bg-red-50 text-red-700 text-xs font-semibold">
                                <?php echo (int)$r['total_deleted']; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
require 'layout_footer.php';
