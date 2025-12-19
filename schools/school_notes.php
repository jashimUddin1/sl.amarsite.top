<?php
// school_notes.php
require_once '../auth/config.php';
require_login();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$pageTitle   = 'All Notes';
$pageHeading = 'All Notes';
$activeMenu  = 'notes';

// ✅ সব note + school info (যদি school delete হয়, তবুও note দেখাবে)
$sql = "
    SELECT
        sn.*,
        s.school_name,
        s.district,
        s.upazila
    FROM school_notes sn
    LEFT JOIN schools s ON s.id = sn.school_id
    ORDER BY sn.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../layout/layout_header.php';
?>

<div class="px-2 py-4">

    <div class="bg-white rounded-xl shadow p-4 mb-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="text-lg font-semibold">School Notes (All)</div>
                <div class="text-xs text-slate-500">
                    Total: <?php echo count($notes); ?> rows
                </div>
            </div>

            <a href="notes.php"
               class="px-3 py-2 text-sm rounded bg-slate-900 text-white hover:bg-slate-700">
                Back to Notes
            </a>
        </div>
    </div>

    <?php if (empty($notes)): ?>
        <div class="bg-white shadow rounded-lg p-4 text-sm text-slate-600">
            school_notes টেবিলে কোনো ডাটা নেই।
        </div>
    <?php else: ?>

        <div class="bg-white shadow rounded-lg p-3">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700">
                            <?php foreach (array_keys($notes[0]) as $col): ?>
                                <th class="text-left px-3 py-2 border-b whitespace-nowrap">
                                    <?php echo htmlspecialchars($col); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($notes as $r): ?>
                            <tr class="hover:bg-slate-50 align-top">
                                <?php foreach ($r as $key => $val): ?>
                                    <?php
                                    // বড় টেক্সট (note_text) সুন্দরভাবে দেখানোর জন্য
                                    $cellClass = "px-3 py-2 border-b";
                                    if ($key === 'note_text') $cellClass .= " min-w-[320px] whitespace-normal";
                                    else $cellClass .= " whitespace-nowrap";
                                    ?>
                                    <td class="<?php echo $cellClass; ?>">
                                        <?php
                                        if ($key === 'note_text') {
                                            echo nl2br(htmlspecialchars((string)$val));
                                        } else {
                                            echo htmlspecialchars((string)$val);
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>

    <?php endif; ?>

</div>

<?php
if (file_exists('../layout/layout_footer.php')) {
    include '../layout/layout_footer.php';
}
?>
