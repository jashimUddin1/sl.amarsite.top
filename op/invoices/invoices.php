<?php // invoices/invoices.php
require_once '../auth/config.php';
require_login();

$pageTitle = 'Invoices - School Note Manager';
$pageHeading = 'Invoices';
$activeMenu = 'invoices';

$user_id = $_SESSION['user_id'] ?? null;

$flash = $_SESSION['flash'] ?? ['type' => '', 'msg' => ''];
unset($_SESSION['flash']);

// ✅ Fetch invoices (latest first)
$stmt = $pdo->query("
    SELECT id, in_no, school_id, data, created_at, updated_at
    FROM invoices
    ORDER BY in_no DESC
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function safe_json($s)
{
    $d = json_decode($s ?? '', true);
    return is_array($d) ? $d : [];
}
function h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

// ✅ Approved schools list
$approvedStmt = $pdo->prepare("SELECT id, school_name, m_fee FROM schools WHERE status='Approved' ");
$approvedStmt->execute();
$approvedSchools = $approvedStmt->fetchAll(PDO::FETCH_ASSOC);

$monthStart = date('Y-m-01 00:00:00');
$monthEnd = date('Y-m-t 23:59:59');

$remaining = 0;

if ($approvedSchools) {
    $invCheck = $pdo->prepare("
        SELECT id, data, created_at
        FROM invoices
        WHERE school_id = :sid
          AND created_at BETWEEN :ms AND :me
        ORDER BY id DESC
        LIMIT 30
    ");

    foreach ($approvedSchools as $s) {
        $sid = (int) $s['id'];

        // এই মাসে ঐ স্কুলের invoices (created_at ভিত্তিতে) তুলে আনা
        $invCheck->execute([':sid' => $sid, ':ms' => $monthStart, ':me' => $monthEnd]);
        $list = $invCheck->fetchAll(PDO::FETCH_ASSOC);

        // ✅ এই মাসে invoice আছে কিনা চেক (invoiceDate থাকলে সেটাও মিলিয়ে দেখবে)
        $hasThisMonth = false;
        foreach ($list as $inv) {
            $data = json_decode($inv['data'] ?? '', true);
            $invDate = $data['invoiceDate'] ?? null;

            if ($invDate) {
                $ts = strtotime($invDate);
                if ($ts && date('Y-m', $ts) === date('Y-m')) {
                    $hasThisMonth = true;
                    break;
                }
            } else {
                // invoiceDate না থাকলে created_at মাস ধরবো
                $ts = strtotime($inv['created_at'] ?? '');
                if ($ts && date('Y-m', $ts) === date('Y-m')) {
                    $hasThisMonth = true;
                    break;
                }
            }
        }

        if (!$hasThisMonth)
            $remaining++;
    }
}

$btnClass = ($remaining > 0) ? 'btn-outline-success' : 'btn-outline-secondary';
$btnDisabled = ($remaining > 0) ? '' : 'disabled';


require '../layout/layout_header.php';
?>

<div class="container-fluid">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2">
            <a href="invoices.php" class="btn btn-light border d-none d-md-inline" title="Refresh">
                ↩
            </a>
            <h5 class="mb-0 fw-semibold text-secondary d-none d-md-inline" style="min-width: 110px;">Saved Invoices</h5>

            <!-- 🔍 Search (schools.php-এর মতো live filter) -->
            <div class="input-group input-group-sm ms-md-2">
                <input type="text" name="search" id="invoiceSearchInput" placeholder="Search Invoice..."
                    class="form-control" onkeyup="searchInvoiceList()">
            </div>


        </div>

        <div class="d-flex">
            <div class="me-2">
                <button class="btn btn-sm btn-outline-success py-1 d-inline d-md-none">
                    <a href="invoice_simple.php">
                        <i class="bi bi-plus "></i>
                    </a>
                </button>
                <button class="btn btn-sm btn-outline-success d-none d-md-inline">
                    <a href="invoice_simple.php">
                        Simple Invoice
                    </a>
                </button>
            </div>
            <form method="POST" action="controllers/invoice_auto_generate.php" class="m-0">
                <button type="submit" title="Invoice Auto create This Month" class="btn btn-sm <?php echo $btnClass; ?>"
                    <?php echo $btnDisabled; ?>>
                    <span class="d-none d-md-inline">Auto create</span>
                    <i class="bi bi-magic d-inline d-md-none"></i>
                </button>
                <?php if ($remaining > 0): ?>
                    <span class="btn btn-sm <?php echo $btnClass; ?>"><?php echo (int) $remaining; ?></span>
                <?php endif; ?>
            </form>
        </div>

    </div>

    <?php if (!empty($flash['msg'])): ?>
        <div class="alert alert-<?php echo h($flash['type']); ?> alert-dismissible fade show" role="alert">
            <?php echo h($flash['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!$rows): ?>
        <div class="alert alert-info">No invoices found.</div>
    <?php else: ?>

        <div class="card shadow-sm border-0 rounded-2">
            <div class="card-body p-0 ">
                <div class="list-group list-group-flush">

                    <?php foreach ($rows as $r): ?>
                        <?php
                        $data = safe_json($r['data'] ?? '');
                        $invNo = $r['in_no'] ?? $r['id'];
                        $schoolName = $data['billTo']['school'] ?? ('School ID: ' . ($r['school_id'] ?? ''));
                        $invoiceDate = $data['invoiceDate'] ?? '';
                        $total = $data['totals']['total'] ?? 0;
                        $status = strtoupper($data['totals']['status'] ?? 'UNPAID');

                        // date display (জাস্ট সুন্দর করে)
                        $dateShow = $invoiceDate;
                        if ($invoiceDate) {
                            $ts = strtotime($invoiceDate);
                            if ($ts)
                                $dateShow = date('j/n/Y', $ts);
                        }

                        $badgeClass = ($status === 'PAID') ? 'success' : 'danger';

                        // View modal এর জন্য
                        $payload = [
                            'id' => (int) $r['id'],
                            'invoiceNumber' => $invNo,
                            'invoiceDate' => $invoiceDate,
                            'dateShow' => $dateShow,
                            'school' => $schoolName,
                            'items' => $data['items'] ?? [],
                            'totals' => $data['totals'] ?? ['total' => 0, 'pay' => 0, 'due' => 0, 'status' => $status],
                            'note' => $data['note'] ?? ''
                        ];
                        $payloadAttr = h(json_encode($payload, JSON_UNESCAPED_UNICODE));
                        ?>

                        <div class="list-group-item py-3 invoice-row">
                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">

                                <!-- ✅ Left -->
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-dark fs-6">
                                        #<?php echo h($invNo); ?> - <?php echo h($schoolName); ?>
                                    </div>

                                    <!-- ✅ Style বাদ -> Date, Date বাদ -> Total -->
                                    <div class="text-secondary small mt-1">
                                        <span>Date: <?php echo h($dateShow ?: '—'); ?></span>
                                        <span class="mx-2">|</span>
                                        <span>Total: ৳<?php echo h(number_format((float) $total, 2)); ?></span>
                                    </div>
                                </div>

                                <!-- ✅ Right -->
                                <div class="d-flex align-items-center gap-3 flex-shrink-0">
                                    <span
                                        class="badge rounded-pill bg-<?php echo h($badgeClass); ?> bg-opacity-10 text-<?php echo h($badgeClass); ?> px-3 py-2">
                                        <?php echo h($status); ?>
                                    </span>

                                    <?php
                                    $editUrl = (empty($r['school_id']))
                                        ? "invoice_edit_simple.php?invoice_id=" . (int) $r['id']
                                        : "invoice_edit.php?invoice_id=" . (int) $r['id'];
                                    ?>
                                    <a class="btn btn-outline-primary btn-sm fw-semibold" href="<?= $editUrl ?>">
                                        Edit
                                    </a>


                                    <button type="button" class="btn btn-outline-danger fw-semibold btn-sm"
                                        onclick="openDeleteModal(<?= (int) $r['id'] ?>)">
                                        Delete
                                    </button>


                                </div>

                            </div>
                        </div>

                    <?php endforeach; ?>

                </div>
            </div>
        </div>

    <?php endif; ?>
</div>


<!--  View Modal -->
<div class="modal fade" id="viewInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold">Invoice Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="fw-semibold" id="m_inv_title">—</div>
                        <div class="text-secondary small" id="m_inv_sub">—</div>
                    </div>
                    <div class="text-end">
                        <span class="badge rounded-pill" id="m_inv_status">—</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width:260px;">Description</th>
                                <th class="text-end" style="width:90px;">Qty</th>
                                <th class="text-end" style="width:120px;">Rate</th>
                                <th class="text-end" style="width:140px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="m_items">
                            <tr>
                                <td colspan="4" class="text-secondary">—</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-7">
                        <div class="border rounded p-3 bg-light">
                            <div class="fw-semibold mb-2">Note</div>
                            <div class="text-secondary" id="m_note">—</div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="border rounded p-3">
                            <div class="d-flex justify-content-between">
                                <span>Total</span>
                                <strong id="m_total">৳0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <span>Pay</span>
                                <strong id="m_pay">৳0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <span>Due</span>
                                <strong id="m_due">৳0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Reason Modal -->
<div class="modal fade" id="deleteReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="controllers/invoice_delete.php" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="delete_id" id="del_invoice_id">

                <!-- CSRF থাকলে -->
                <?php if (!empty($_SESSION['csrf'])): ?>
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Reason for delete <span class="text-danger">*</span>
                    </label>
                    <textarea name="reason" id="delete_reason" class="form-control" rows="3" required
                        placeholder="Write the reason for deleting this invoice..."></textarea>
                </div>

                <div class="alert alert-warning small mb-0">
                    This action cannot be undone.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="submit" class="btn btn-danger">
                    Delete Invoice
                </button>
            </div>
        </form>
    </div>
</div>


<script>
    function searchInvoiceList() {
        const input = document.getElementById('invoiceSearchInput');
        if (!input) return;

        const filter = (input.value || '').toLowerCase();
        const rows = document.querySelectorAll('.invoice-row');

        rows.forEach(row => {
            const text = (row.innerText || '').toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    }

    function openDeleteModal(id) {
        document.getElementById('del_invoice_id').value = id;
        document.getElementById('delete_reason').value = '';

        const modal = new bootstrap.Modal(
            document.getElementById('deleteReasonModal')
        );
        modal.show();
    }
</script>


<?php require '../layout/layout_footer.php'; ?>