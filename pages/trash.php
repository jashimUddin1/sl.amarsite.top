<?php
require_once '../auth/config.php';
require_login();

$userId = $_SESSION['user_id'] ?? null;

// ====== Restore / Delete Handler ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $trashId = isset($_POST['trash_id']) ? (int) $_POST['trash_id'] : 0;

    if ($trashId > 0) {
        // ---------- RESTORE ----------
        if ($action === 'restore_trash') {
            try {
                $pdo->beginTransaction();

                // 1) trash row আনো
                $stmt = $pdo->prepare("SELECT * FROM school_trash WHERE id = :id");
                $stmt->execute([':id' => $trashId]);
                $trash = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($trash) {
                    $photoPath    = $trash['photo_path'] ?? null;
                    $newPhotoPath = $photoPath;
                    $schoolId     = (int)($trash['school_id'] ?? 0); // মূল school_id

                    // 2) যদি trash_schools এ থাকে, তাহলে আবার uploads/schools এ move করব
                    if (!empty($photoPath) && strpos($photoPath, 'uploads/trash_schools/') === 0) {
                        $oldFull = __DIR__ . '/' . $photoPath;
                        if (is_file($oldFull)) {
                            $mainDir = __DIR__ . '/uploads/schools';
                            if (!is_dir($mainDir)) {
                                mkdir($mainDir, 0777, true);
                            }

                            $baseName   = basename($photoPath);
                            $targetFull = $mainDir . '/' . $baseName;

                            // নাম conflict হলে suffix যোগ
                            if (is_file($targetFull)) {
                                $nameNoExt = pathinfo($baseName, PATHINFO_FILENAME);
                                $ext       = pathinfo($baseName, PATHINFO_EXTENSION);
                                $baseName  = $nameNoExt . '_' . time() . '.' . $ext;
                                $targetFull = $mainDir . '/' . $baseName;
                            }

                            if (@rename($oldFull, $targetFull)) {
                                $newPhotoPath = 'uploads/schools/' . $baseName;
                            } else {
                                // move fail হলে পুরানো path রেখে দিচ্ছি
                                $newPhotoPath = $photoPath;
                            }
                        }
                    }

                    // 3) schools এ আবার insert
                    // এখানে পুরোনো school_id দিয়েই id সেট করছি,
                    // যেন school_notes / note_trash এর school_id এর সাথে ম্যাচ করে
                    $stmtIns = $pdo->prepare("
                        INSERT INTO schools (
                            id,
                            district, upazila, school_name, mobile, status,
                            photo_path, created_by, updated_by
                        )
                        VALUES (
                            :id,
                            :district, :upazila, :school_name, :mobile, :status,
                            :photo_path, :created_by, :updated_by
                        )
                    ");

                    $stmtIns->execute([
                        ':id'          => $schoolId,
                        ':district'    => $trash['district'],
                        ':upazila'     => $trash['upazila'],
                        ':school_name' => $trash['school_name'],
                        ':mobile'      => $trash['mobile'],
                        ':status'      => $trash['status'],
                        ':photo_path'  => $newPhotoPath,
                        ':created_by'  => $trash['created_by'] ?? null,
                        // restore করল যে user, তাকে updated_by দিলে future ট্র্যাক সহজ হবে
                        ':updated_by'  => $userId,
                    ]);

                    // 4) note_trash থেকে এই school এর সব নোট আবার school_notes এ ফেরত আনা
                    // (আগে schools.php তে delete করার সময় note_trash এ কপি করা হয়েছিল)
                    $stmtRestoreNotes = $pdo->prepare("
                        INSERT INTO school_notes (
                            school_id,
                            note_text,
                            note_date,
                            updated_by,
                            created_at
                        )
                        SELECT
                            school_id,
                            note_text,
                            note_date,
                            updated_by,
                            created_at
                        FROM note_trash
                        WHERE school_id = :school_id
                    ");
                    $stmtRestoreNotes->execute([
                        ':school_id' => $schoolId,
                    ]);

                    // 5) note_trash থেকে নোটগুলো মুছে দাও (already restored)
                    $stmtDelNotesTrash = $pdo->prepare("
                        DELETE FROM note_trash
                        WHERE school_id = :school_id
                    ");
                    $stmtDelNotesTrash->execute([
                        ':school_id' => $schoolId,
                    ]);

                    // 6) school_trash থেকে এই row মুছে দাও
                    $stmtDelTrash = $pdo->prepare("DELETE FROM school_trash WHERE id = :id");
                    $stmtDelTrash->execute([':id' => $trashId]);
                }

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                // চাইলে এখানে error log করতে পারো
            }
        }

        // ---------- PERMANENT DELETE ----------
        if ($action === 'delete_trash') {
            try {
                $pdo->beginTransaction();

                // আগে school_id + photo_path নিয়ে আসি
                $stmt = $pdo->prepare("SELECT school_id, photo_path FROM school_trash WHERE id = :id");
                $stmt->execute([':id' => $trashId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    $schoolId  = (int)($row['school_id'] ?? 0);
                    $photoPath = $row['photo_path'] ?? null;

                    // ছবিটা ফাইল সিস্টেম থেকে ডিলিট
                    if (!empty($photoPath) && strpos($photoPath, 'uploads/trash_schools/') === 0) {
                        $fileFull = __DIR__ . '/' . $photoPath;
                        if (is_file($fileFull)) {
                            @unlink($fileFull);
                        }
                    }

                    // এই school_id এর note_trash থেকেও সব নোট permanently delete
                    if ($schoolId > 0) {
                        $stmtDelNotes = $pdo->prepare("
                            DELETE FROM note_trash
                            WHERE school_id = :school_id
                        ");
                        $stmtDelNotes->execute([
                            ':school_id' => $schoolId,
                        ]);
                    }

                    // সর্বশেষ school_trash থেকে রেকর্ড ডিলিট
                    $stmtDel = $pdo->prepare("DELETE FROM school_trash WHERE id = :id");
                    $stmtDel->execute([':id' => $trashId]);
                }

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                // চাইলে error log করতে পারো
            }
        }
    }

    header("Location: trash.php");
    exit;
}

// ====== Filter/Search ======
$search = trim($_GET['q'] ?? '');

// Trash list আনব
$sql = "
    SELECT st.*,
           u1.name AS created_name,
           u2.name AS updated_name,
           u3.name AS deleted_name
    FROM school_trash st
    LEFT JOIN users u1 ON st.created_by = u1.id
    LEFT JOIN users u2 ON st.updated_by = u2.id
    LEFT JOIN users u3 ON st.deleted_by = u3.id
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= " AND st.school_name LIKE :q";
    $params[':q'] = '%' . $search . '%';
}

$sql .= " ORDER BY st.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// layout
$pageTitle   = 'Trash - School List';
$pageHeading = 'Trash';
$activeMenu  = 'trash';

require '../layout/layout_header.php';
?>

<div class="bg-white rounded-xl shadow p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Search by School Name
            </label>
            <input
                type="text"
                name="q"
                class="w-full p-2 border rounded text-sm"
                placeholder="Type school name..."
                value="<?php echo htmlspecialchars($search); ?>"
            >
        </div>
        <div class="flex gap-2">
            <button
                type="submit"
                class="px-4 py-2 rounded bg-slate-900 text-white text-sm hover:bg-slate-800"
            >
                Search
            </button>
            <a
                href="trash.php"
                class="px-4 py-2 rounded bg-slate-200 text-slate-700 text-sm hover:bg-slate-300"
            >
                Reset
            </a>
        </div>
    </form>
    <p class="mt-2 text-[11px] text-slate-500">
        এখানে শুধুমাত্র ডিলিট হওয়া স্কুলগুলো দেখা যাবে। চাইলে Restore করে আবার active list এ ফেরত নিতে পারবে।
    </p>
</div>

<div class="bg-white rounded-xl shadow p-3 overflow-x-auto">
    <?php if (!$rows): ?>
        <p class="text-center text-gray-500 text-sm py-4">কোনো ট্র্যাশ পাওয়া যায়নি।</p>
    <?php else: ?>
        <table class="min-w-full text-sm border-collapse">
            <thead>
            <tr class="bg-slate-100 text-left">
                <th class="p-2 border">Trash ID</th>
                <th class="p-2 border">School ID / Photo</th>
                <th class="p-2 border" style="min-width: 150px;">School Name</th>
                <th class="p-2 border" style="min-width: 160px;">Address</th>
                <th class="p-2 border">Status</th>
                <th class="p-2 border">Deleted By</th>
                <th class="p-2 border">Deleted At</th>
                <th class="p-2 border">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <?php
                $address = trim(
                    ($r['district'] ?? '') .
                    (($r['district'] ?? '') && ($r['upazila'] ?? '') ? ', ' : '') .
                    ($r['upazila'] ?? '')
                );
                if ($address === '') {
                    $address = 'N/A';
                }

                $statusClass = ($r['status'] === 'Approved')
                    ? 'text-green-600'
                    : 'text-orange-600';

                $deletedByName = $r['deleted_name'] ?? 'Unknown';
                $deletedAt     = $r['deleted_at'] ?? '';
                ?>
                <tr class="hover:bg-slate-50">
                    <td class="p-2 border align-top text-xs">
                        <?php echo (int)$r['id']; ?>
                    </td>
                    <td class="p-2 border align-top text-xs">
                        <div class="flex flex-col items-start gap-1">
                            <div>SID: <?php echo (int)$r['school_id']; ?></div>
                            <?php if (!empty($r['photo_path'])): ?>
                                <img
                                    src="../<?php echo htmlspecialchars($r['photo_path']); ?>"
                                    style="width: 80px;"
                                    alt="trash"
                                >
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="p-2 border align-top font-semibold">
                        <?php echo htmlspecialchars($r['school_name'] ?? '(No name)'); ?>
                    </td>
                    <td class="p-2 border align-top text-xs text-slate-700">
                        <?php echo htmlspecialchars($address); ?>
                    </td>
                    <td class="p-2 border align-top">
                        <span class="text-xs font-semibold <?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars($r['status'] ?? ''); ?>
                        </span>
                    </td>
                    <td class="p-2 border align-top text-xs">
                        <?php echo htmlspecialchars($deletedByName); ?>
                    </td>
                    <td class="p-2 border align-top text-xs">
                        <?php
                        if (!empty($deletedAt)) {
                            echo date("d M Y h:i A", strtotime($deletedAt));
                        } else {
                            echo "";
                        }
                        ?>
                    </td>

                    <td class="p-2 border align-top">
                        <div class="flex flex-col sm:flex-row gap-1 text-xs">
                            <form method="POST" onsubmit="return confirm('এই স্কুলটি restore করতে চান?');">
                                <input type="hidden" name="action" value="restore_trash">
                                <input type="hidden" name="trash_id" value="<?php echo (int)$r['id']; ?>">
                                <button
                                    type="submit"
                                    class="px-3 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700 w-full"
                                >
                                    Restore
                                </button>
                            </form>

                            <form method="POST"
                                  onsubmit="return confirm('Permanent delete? এই school_trash রেকর্ড, নোট এবং ছবিটা আর ফিরিয়ে আনা যাবে না।');">
                                <input type="hidden" name="action" value="delete_trash">
                                <input type="hidden" name="trash_id" value="<?php echo (int)$r['id']; ?>">
                                <button
                                    type="submit"
                                    class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700 w-full"
                                >
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
require '../layout/layout_footer.php';
