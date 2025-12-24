<?php
include "../auth/config.php";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// 2. UPDATE LOGIC
if (isset($_POST['action']) && $_POST['action'] == 'update_invoice') {
    $id = $_POST['id'];
    $newNumber = $_POST['invoiceNumber'];
    $newSchool = $_POST['school'];
    $newStatus = $_POST['status'];
    $newAmount = (float)$_POST['amount'];

    $res = $conn->query("SELECT data FROM invoices WHERE id = $id");
    $row = $res->fetch_assoc();
    $jsonData = json_decode($row['data'], true);

    if (isset($jsonData['billTo'])) {
        $jsonData['invoiceNumber'] = $newNumber;
        $jsonData['billTo']['school'] = $newSchool;
        $jsonData['totals']['status'] = $newStatus;
        $jsonData['totals']['total'] = $newAmount;
    } else {
        $jsonData['invoiceNumber'] = $newNumber;
        $jsonData['clientInstitution'] = $newSchool;
        $jsonData['paymentStatus'] = $newStatus;
        $items = is_string($jsonData['items']) ? json_decode($jsonData['items'], true) : $jsonData['items'];
        if(is_array($items) && count($items) > 0) {
            $items[0]['total'] = $newAmount;
            $jsonData['items'] = is_string($jsonData['items']) ? json_encode($items) : $items;
        }
    }

    $updatedJson = json_encode($jsonData, JSON_UNESCAPED_UNICODE);
    $stmt = $conn->prepare("UPDATE invoices SET data = ? WHERE id = ?");
    $stmt->bind_param("si", $updatedJson, $id);
    echo ($stmt->execute()) ? "success" : "error";
    exit;
}

// 3. FETCH SINGLE DATA
if (isset($_GET['get_id'])) {
    $id = $_GET['get_id'];
    $res = $conn->query("SELECT * FROM invoices WHERE id = $id");
    echo json_encode($res->fetch_assoc());
    exit;
}

// 4. DELETE LOGIC
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM invoices WHERE id = $id");
    header("Location: invoice_ge.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices Responsive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .invoice-card { background: #fff; border-radius: 12px; padding: 15px; margin-bottom: 12px; border: 1px solid #eee; }
        
        .school-info { width: 70%; } /* Mobile e 70% */
        .invoice-title { font-weight: 700; color: #333; font-size: 15px; display: block; }
        .invoice-sub { font-size: 12px; color: #888; }
        
        .status-badge { font-size: 10px; font-weight: 800; padding: 3px 10px; border-radius: 50px; text-transform: uppercase; display: inline-block;}
        .status-unpaid { background-color: #ffe5e5; color: #ff5c5c; }
        .status-paid { background-color: #e5f9f0; color: #2ecc71; }
        
        .text-amount { font-size: 14px; font-weight: 700; color: #222; }
        .action-link { font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: none; background: none; display: block; padding: 2px 0; }

        /* Desktop view adjustment */
        @media (min-width: 768px) {
            .school-info { width: auto; }
            .action-container { flex-direction: row !important; }
            .action-link { display: inline-block; margin-left: 15px; }
            .status-container { margin-right: 20px; text-align: right; }
            .text-amount { font-size: 16px; }
        }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold"><button class="btn btn-secondary me-2">Back</button><span class="mt-2">Saved Invoices</span></h4>
        <h4> 
            <a href="invoices.php" class="btn btn-outline-success btn-sm "> Option 1</a> 
            <a href="#" class="btn btn-outline-primary btn-sm">Old Style</a> 
        </h4>
    </div>

    <?php
    $result = $conn->query("SELECT * FROM invoices ORDER BY id DESC");
    while($row = $result->fetch_assoc()):
        $inv = json_decode($row['data'], true);
        $invNo = $inv['invoiceNumber'] ?? 'N/A';
        $school = $inv['billTo']['school'] ?? $inv['clientInstitution'] ?? 'Unknown School';
        $date = $inv['invoiceDate'] ?? '';
        
        // Amount
        if (isset($inv['totals']['total'])) { $amount = (float)$inv['totals']['total']; }
        else {
            $items = is_string($inv['items'] ?? '') ? json_decode($inv['items'], true) : ($inv['items'] ?? []);
            $amount = 0; if(is_array($items)) foreach($items as $i) $amount += (float)($i['total'] ?? 0);
        }
        
        $rawStatus = $inv['totals']['status'] ?? $inv['paymentStatus'] ?? 'UNPAID';
        $status = strtoupper($rawStatus);
    ?>
    <div class="invoice-card d-flex align-items-center justify-content-between overflow-auto">
        <div class="school-info">
            <span class="invoice-title text-truncate">#<?php echo $invNo; ?> - <?php echo $school; ?></span>
            <span class="invoice-sub"><?php echo $date; ?></span>
        </div>
        
        <div class="d-flex align-items-center">
            <div class="d-flex status-container text-end me-2">
                <div class="text-amount me-2">৳<?php echo number_format($amount, 0); ?></div>
                <span class="status-badge <?php echo ($status == 'PAID') ? 'status-paid' : 'status-unpaid'; ?>">
                    <?php echo $status; ?>
                </span>
            </div>

            <div class="action-container d-flex flex-column align-items-end border-start ps-3">
                <button class="action-link text-primary" onclick="openEditModal(<?php echo $row['id']; ?>)">Edit</button>
                <a href="?delete_id=<?php echo $row['id']; ?>" class="action-link text-danger" onclick="return confirm('Delete?')">Delete</a>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="editForm" class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="m_id"><input type="hidden" name="action" value="update_invoice">
                <div class="mb-3"><label class="small fw-bold">Invoice #</label><input type="text" name="invoiceNumber" id="m_num" class="form-control"></div>
                <div class="mb-3"><label class="small fw-bold">Amount (৳)</label><input type="number" name="amount" id="m_amount" class="form-control"></div>
                <div class="mb-3"><label class="small fw-bold">Institution</label><input type="text" name="school" id="m_school" class="form-control"></div>
                <div class="mb-3"><label class="small fw-bold">Status</label>
                    <select name="status" id="m_status" class="form-select"><option value="UNPAID">UNPAID</option><option value="PAID">PAID</option></select>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary w-100 rounded-pill">Update</button></div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const bModal = new bootstrap.Modal(document.getElementById('editModal'));
function openEditModal(id) {
    fetch(`invoice_ge.php?get_id=${id}`).then(r => r.json()).then(res => {
        const d = JSON.parse(res.data);
        document.getElementById('m_id').value = res.id;
        document.getElementById('m_num').value = d.invoiceNumber;
        document.getElementById('m_school').value = d.billTo ? d.billTo.school : d.clientInstitution;
        document.getElementById('m_status').value = d.totals ? d.totals.status : d.paymentStatus;
        let amt = 0;
        if(d.totals && d.totals.total) amt = d.totals.total;
        else { 
            let items = (typeof d.items === 'string') ? JSON.parse(d.items) : (d.items || []);
            items.forEach(i => amt += parseFloat(i.total || 0));
        }
        document.getElementById('m_amount').value = amt;
        bModal.show();
    });
}
document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('invoice_ge.php', { method: 'POST', body: new FormData(this) })
    .then(r => r.text()).then(res => { if(res.trim() === 'success') location.reload(); });
});
</script>
</body>
</html>