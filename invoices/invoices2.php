<?php
require_once '../auth/config.php';
require_login();

/**
 * Saved Invoices List Page
 * JSON structure based on:
 * {
 *   invoiceNumber,
 *   invoiceDate,
 *   billTo.school,
 *   totals.total,
 *   totals.status
 * }
 */

try {
    $stmt = $pdo->query("
        SELECT
            id,
            school_id,

            CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.invoiceNumber')) AS UNSIGNED) AS invoice_number,
            JSON_UNQUOTE(JSON_EXTRACT(data, '$.invoiceDate')) AS invoice_date,
            JSON_UNQUOTE(JSON_EXTRACT(data, '$.billTo.school')) AS school_name,
            CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.totals.total')) AS DECIMAL(10,2)) AS total_amount,
            JSON_UNQUOTE(JSON_EXTRACT(data, '$.totals.status')) AS payment_status,

            created_at
        FROM invoices
        ORDER BY id DESC
    ");

    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>Saved Invoices</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">📄 Saved Invoices</h4>
        <a href="invoice_create.php" class="btn btn-success btn-sm">
            + New Invoice
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">

            <table class="table table-bordered table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr class="text-center">
                        <th>#</th>
                        <th>Invoice No</th>
                        <th>Date</th>
                        <th>School / Client</th>
                        <th>Total (৳)</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">
                            কোনো Invoice পাওয়া যায়নি
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($invoices as $i => $row): ?>
                        <tr>
                            <td class="text-center"><?= $i + 1 ?></td>

                            <td class="text-center fw-semibold">
                                <?= htmlspecialchars($row['invoice_number']) ?>
                            </td>

                            <td class="text-center">
                                <?= htmlspecialchars($row['invoice_date']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['school_name'] ?: '-') ?>
                            </td>

                            <td class="text-end">
                                <?= number_format((float)$row['total_amount'], 2) ?>
                            </td>

                            <td class="text-center">
                                <?php
                                $status = $row['payment_status'] ?? 'UNPAID';
                                $badge = match ($status) {
                                    'PAID' => 'success',
                                    'PARTIAL' => 'warning',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $badge ?>">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </td>

                            <td class="text-center">
                                <a href="invoice_view.php?id=<?= $row['id'] ?>"
                                   class="btn btn-primary btn-sm">
                                    View
                                </a>

                                <a href="invoice_print.php?id=<?= $row['id'] ?>"
                                   class="btn btn-outline-secondary btn-sm">
                                    Print
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>

</div>

</body>
</html>
