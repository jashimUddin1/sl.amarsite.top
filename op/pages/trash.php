<?php
require_once '../auth/config.php';
require_login();

$userId = $_SESSION['user_id'] ?? null;

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
$pageTitle = 'Trash - School List';
$pageHeading = 'Trash';
$activeMenu = 'trash';

require '../layout/layout_header.php';
?>

<?php if (!empty($_SESSION['flash']) && is_array($_SESSION['flash'])): ?>
    <?php
        $flashType = $_SESSION['flash']['type'] ?? 'info';
        $flashMsg  = $_SESSION['flash']['msg']  ?? '';
        unset($_SESSION['flash']);

        $flashClass = 'bg-blue-100 text-blue-800';
        if ($flashType === 'success') $flashClass = 'bg-green-100 text-green-800';
        if ($flashType === 'error')   $flashClass = 'bg-red-100 text-red-800';
        if ($flashType === 'warning') $flashClass = 'bg-yellow-100 text-yellow-800';
    ?>
    <div class="mb-3 p-3 rounded border <?= $flashClass ?>">
        <div class="flex justify-between items-center">
            <div class="text-sm font-semibold">
                <?= htmlspecialchars($flashMsg) ?>
            </div>
            <button onclick="this.parentElement.parentElement.remove()"
                    style="border:0;background:transparent;font-size:14px;cursor:pointer;">
                ✕
            </button>
        </div>
    </div>
<?php endif; ?>


<div class="bg-white rounded-xl shadow p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Search by School Name
            </label>
            <input type="text" name="q" class="w-full p-2 border rounded text-sm" placeholder="Type school name..."
                value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 rounded bg-slate-900 text-white text-sm hover:bg-slate-800">
                Search
            </button>
            <a href="trash.php" class="px-4 py-2 rounded bg-slate-200 text-slate-700 text-sm hover:bg-slate-300">
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
                    <th class="p-2 border">Photo</th>
                    <th class="p-2 border">School ID</th>

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
                    $deletedAt = $r['deleted_at'] ?? '';
                    ?>
                    <tr class="hover:bg-slate-50">
                        <td class="p-2 border align-top text-xs">
                            <div class="flex flex-col items-start gap-1">

                                <?php if (!empty($r['photo_path'])): ?>
                                    <img src="../<?php echo htmlspecialchars($r['photo_path']); ?>"
                                        style="width: 40px; height: 23px; cursor:pointer;" alt="trash"
                                        onclick="openPhotoModal(this.src)">

                                <?php endif; ?>
                            </div>
                        </td>

                        <td class="p-2 border align-top text-xs">
                            <?php echo (int) $r['school_id']; ?>
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
                                <form method="POST" action="core/trash_core.php" onsubmit="return confirm('এই স্কুলটি restore করতে চান?');">
                                    <input type="hidden" name="action" value="restore_trash">
                                    <input type="hidden" name="trash_id" value="<?php echo (int) $r['id']; ?>">
                                    <button type="submit"
                                        class="px-3 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700 w-full">
                                        Restore
                                    </button>
                                </form>

                                <form method="POST" action="core/trash_core.php" class="deleteForm">
                                    <input type="hidden" name="action" value="delete_trash">
                                    <input type="hidden" name="trash_id" value="<?php echo (int) $r['id']; ?>">
                                    <input type="hidden" name="reason" value="">
                                    <button type="submit"
                                        class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700 w-full">
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


<!-- Photo Preview Modal -->
<div id="photoModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.7); z-index:9999;"
    onclick="closePhotoModal()">

    <div style="display:flex; align-items:center; justify-content:center; height:100%;">
        <img id="modalPhoto" src=""
            style="max-width:90%; max-height:90%; background:#fff; padding:8px; border-radius:6px;">
    </div>
</div>

<!-- Delete Reason Modal -->
<div id="deleteModal"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:9999;">
  <div style="max-width:520px; margin:8% auto; background:#fff; border-radius:10px; padding:16px;">
    <h3 style="margin:0 0 10px; font-size:16px;">Permanent Delete Reason</h3>

    <textarea id="deleteReason"
              style="width:100%; height:90px; padding:10px; border:1px solid #ddd; border-radius:8px;"
              placeholder="Reason লিখুন..."></textarea>

    <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:12px;">
      <button type="button"
              onclick="closeDeleteModal()"
              style="padding:8px 12px; border-radius:8px; border:1px solid #ddd; background:#f3f4f6;">
        Cancel
      </button>

      <button type="button"
              onclick="confirmDeleteSubmit()"
              style="padding:8px 12px; border-radius:8px; border:0; background:#dc2626; color:#fff;">
        Delete Now
      </button>
    </div>
  </div>
</div>

<script>
let currentDeleteForm = null;

document.querySelectorAll('form.deleteForm').forEach(form => {
  form.addEventListener('submit', function(e){
    e.preventDefault(); // stop normal submit
    currentDeleteForm = form;
    document.getElementById('deleteReason').value = '';
    document.getElementById('deleteModal').style.display = 'block';
  });
});

function closeDeleteModal(){
  document.getElementById('deleteModal').style.display = 'none';
  currentDeleteForm = null;
}

function confirmDeleteSubmit(){
  if(!currentDeleteForm) return;

  const reason = document.getElementById('deleteReason').value.trim();
  if(reason.length < 3){
    alert('Reason কমপক্ষে 3 অক্ষর লিখুন।');
    return;
  }

  // confirm (extra safety)
  if(!confirm("Permanent delete নিশ্চিত তো? এটা আর ফিরিয়ে আনা যাবে না!")) return;

  // set hidden reason input
  const reasonInput = currentDeleteForm.querySelector('input[name="reason"]');
  if(reasonInput) reasonInput.value = reason;

  currentDeleteForm.submit();
}
</script>


<script>
    function openPhotoModal(src) {
        const modal = document.getElementById('photoModal');
        const img = document.getElementById('modalPhoto');
        img.src = src;
        modal.style.display = 'block';
    }

    function closePhotoModal() {
        document.getElementById('photoModal').style.display = 'none';
    }
</script>


<?php
require '../layout/layout_footer.php';