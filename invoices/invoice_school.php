<?php // invoices/invoice_school.php 
require_once '../auth/config.php';
require_login();
require_once '../controllers/is_controller.php';

// 🔹 ID validate
$schoolId = isset($_GET['school_id']) ? (int) $_GET['school_id'] : 0;

if ($schoolId <= 0) {
    die('Invalid school ID');
}

// 🔹 Fetch school data
$school = getSchoolById($pdo, $schoolId);

if (!$school) {
    die('School not found');
}


// ✅ Get next invoice number from invoices.data JSON
$sql = "
SELECT COALESCE(
    MAX(CAST(JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.invoiceNumber')) AS UNSIGNED)),
    0
) AS max_inv
FROM `invoices`
";
$stmt = $pdo->query($sql);
$maxInv = (int) $stmt->fetchColumn();
$nextInvoiceNumber = $maxInv + 1;



require '../layout/single_invoice_header.php';
?>

<div class="invoice-wrapper">
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4 p-md-5">

            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill order-1 order-md-1"
                    id="back-btn">
                    <i class="fa-solid fa-arrow-left me-1"></i> <span class="d-none d-sm-inline">Back</span>
                </button>

                <h2 class="m-0 fw-bold text-center flex-grow-1 order-2 order-md-2" style="line-height: 1.1;">
                    Create Your Invoice
                </h2>

                <button type="button" class="btn btn-reset btn-sm rounded-pill order-3 order-md-3" id="reset-btn">
                    <i class="fa-solid fa-rotate-left me-1"></i> <span class="d-none d-sm-inline">Reset</span>
                </button>
            </div>

            <p class="text-muted text-center mb-4">
                Fill in the details below to generate a professional invoice.
            </p>

            <form id="invoice-form">

                <!-- Bill To + Invoice Details -->
                <div class="row g-4 mb-4">

                    <!-- hidden input for school ID -->
                    <input type="hidden" id="school-id" value="<?= $school['id'] ?>">

                    <!-- Bill To -->
                    <div class="col-md-6">
                        <div class="mb-2 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-user"></i>
                            <span class="form-section-title">Bill To</span>
                        </div>

                        <div class="mb-3">
                            <input type="text" class="form-control" id="bill-school"
                                value="<?= htmlspecialchars($school['school_name']) ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <input type="text" class="form-control" id="bill-name"
                                value="<?= htmlspecialchars($school['client_name']) ?>">
                        </div>

                        <div>
                            <input type="text" class="form-control" id="bill-phone"
                                value="<?= htmlspecialchars($school['mobile'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Invoice Details -->
                    <div class="col-md-6">
                        <div class="mb-2 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-file-invoice"></i>
                            <span class="form-section-title">Invoice Details</span>
                        </div>

                        <div class="mb-3">
                            <input type="number" min="1" class="form-control" id="invoice-number"
                                placeholder="Invoice #" value="<?= htmlspecialchars($nextInvoiceNumber) ?>">
                        </div>

                        <div class="mb-3">
                            <input type="date" class="form-control" id="invoice-date">
                        </div>

                        <div class="mb-3">
                            <select class="form-select" id="invoice-style">
                                <option value="classic" selected>Classic Style</option>
                                <option value="modern">Modern Style</option>
                                <option value="minimal">Minimal Style</option>
                            </select>
                        </div>
                    </div>

                </div>

                <!-- Items -->
                <hr class="my-4">

                <div class="table-responsive mb-3">
                    <table class="table align-middle" id="items-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40%;">Description</th>
                                <th style="width: 15%;">Qty</th>
                                <th style="width: 20%;">Rate</th>
                                <th style="width: 20%;">Amount</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            <tr class="item-row">
                                <td>
                                    <input type="text" class="form-control item-desc"
                                        placeholder="Item description / comment" value="" required>
                                </td>
                                <td>
                                    <input type="text" min="0" step="1" class="form-control item-qty" value="1"
                                        required>
                                </td>
                                <td>
                                    <input type="number" min="0" step="0.01" class="form-control item-rate" value="0"
                                        required>
                                </td>
                                <td>
                                    <input type="number" class="form-control item-amount" value="0.00" required>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-link text-danger p-0 btn-sm btn-delete-row"
                                        title="Remove item">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- ✅ Totals Summary (Form) -->
                    <div class="d-flex justify-content-end">
                        <div class="p-3 rounded-3 border bg-light" style="min-width: 320px;">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">Total Amount</span>
                                <span class="fw-bold">Tk <span id="form-total">0.00</span></span>
                            </div>

                            <div id="pay-wrapper" class="mt-2 d-none">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <label class="mb-0 fw-semibold" for="pay-amount">Pay Amount</label>
                                    <input type="number" min="0" step="0.01"
                                        class="form-control form-control-sm text-end" id="pay-amount" value="0"
                                        style="max-width: 140px;">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                                <span class="fw-semibold">Due</span>
                                <span class="fw-bold text-danger">Tk <span id="form-due">0.00</span></span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ✅ Action Row: Add Item | Payment Status | View -->
                <div
                    class="d-flex flex-column flex-md-row align-items-stretch align-items-md-end justify-content-between gap-3 mb-4">

                    <!-- ✅ Left: Add Item + View -->
                    <div class="d-flex justify-content-between gap-2">
                        <button type="button" class="btn btn-add-item btn-sm" id="add-item-btn">
                            <i class="fa-solid fa-plus me-1"></i> Add Item
                        </button>

                        <button type="button" class="btn btn-outline-secondary btn-sm" id="view-btn">
                            <i class="fa-regular fa-eye me-1"></i> View
                        </button>
                    </div>

                    <!-- ✅ Right: Payment Status -->
                    <div class="d-flex align-items-center justify-content-between" style="min-width: 260px;">

                        <label for="payment-status" class="mb-0 small fw-semibold text-muted me-2">
                            <i class="fa-solid fa-credit-card me-1"></i>
                            Payment Status
                        </label>

                        <select class="form-select form-select-sm w-auto" id="payment-status">
                            <option value="UNPAID" selected>UNPAID</option>
                            <option value="PAID">PAID</option>
                            <option value="PARTIAL">PARTIALLY PAID</option>
                        </select>
                    </div>

                </div>

                <!-- Note -->
                <div class="mb-3 d-flex align-items-center gap-2">
                    <i class="fa-regular fa-note-sticky"></i>
                    <span class="form-section-title">Note</span>
                    <i class="fas fa-calculator calculator-style" id="btn-total-to-note"></i>
                </div>

                <div class="mb-4">
                    <textarea class="form-control" id="invoice-note" rows="3"
                        placeholder="Write any note here..."></textarea>
                </div>

                <!-- Footer buttons -->
                <div class="d-flex flex-column flex-md-row justify-content-center gap-3 pt-3 border-top">
                    <button type="button" class="btn btn-footer-add px-5 rounded-pill" id="add-invoice-btn">
                        <i class="fa-solid fa-circle-plus me-2"></i> Add Invoice
                    </button>

                    <button type="button" class="btn btn-footer-print px-5 rounded-pill" id="print-btn">
                        <i class="fa-solid fa-print me-2"></i> Print
                    </button>

                    <button type="button" class="btn btn-footer-pdf px-5 rounded-pill" id="download-pdf-btn">
                        <i class="fa-solid fa-file-pdf me-2"></i> Download PDF
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Invoice Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body d-flex justify-content-center">
                <div id="preview-body" class="w-100"></div>
            </div>

            <div class="modal-footer flex-column flex-md-row justify-content-between gap-2">
                <button type="button" class="btn btn-download-preview w-100 w-md-auto" id="download-preview-btn">
                    <i class="fa-solid fa-download me-2"></i> Download as Image
                </button>

                <button type="button" class="btn btn-secondary w-100 w-md-auto" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const itemsBody = document.getElementById("items-body");
        const invoiceForm = document.getElementById("invoice-form");
        const previewBody = document.getElementById("preview-body");

        const noteTextarea = document.getElementById("invoice-note");
        const calcBtn = document.getElementById("btn-total-to-note");

        // ✅ Form totals elements
        const paymentStatusEl = document.getElementById("payment-status");
        const formTotalEl = document.getElementById("form-total");
        const formDueEl = document.getElementById("form-due");
        const payWrapper = document.getElementById("pay-wrapper");
        const payAmountEl = document.getElementById("pay-amount");


        function extractNumber(value) {
            const match = value.match(/[\d.]+/);
            return match ? parseFloat(match[0]) : 0;
        }

        function updateNoteFromTotal() {
            const total = getTotalAmount();
            const words = convert_number_to_words(total);
            noteTextarea.value = `${words} Taka Only.`;
        }


        // ✅ Auto set date
        const dateInput = document.getElementById("invoice-date");
        if (dateInput && !dateInput.value) {
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, "0");
            const dd = String(today.getDate()).padStart(2, "0");
            dateInput.value = `${yyyy}-${mm}-${dd}`;
        }

        function getTotalAmount() {
            let total = 0;
            itemsBody.querySelectorAll("tr.item-row").forEach(row => {
                const qty = extractNumber(row.querySelector(".item-qty")?.value || "");
                const rate = parseFloat(row.querySelector(".item-rate")?.value) || 0;
                total += qty * rate;
            });
            return Math.round(total * 100) / 100;
        }


        // ✅ compute totals with status
        function computeInvoiceTotals() {
            const total = getTotalAmount();
            const status = paymentStatusEl.value || "UNPAID";
            let pay = 0;

            if (status === "PARTIAL") {
                pay = parseFloat(payAmountEl.value) || 0;
                if (pay < 0) pay = 0;

                if (pay > total) {
                    alert("Total amount ar theke Pay Amount beshi likhso");
                    pay = total;
                    payAmountEl.value = total.toFixed(2);
                }

                if (total > 0 && pay === total) {
                    alert("somoporiman tk paid hole just paid select koro. jodi unpaid select kora hoy tobe pay amount and tar sather input thakbe na");
                }
            } else if (status === "PAID") {
                pay = total;
            } else {
                pay = 0; // UNPAID
            }

            const due = Math.max(0, total - pay);
            return { total, pay, due, status };
        }

        // ✅ update UI (form totals + pay field visibility)
        function updatePaymentUI() {
            const status = paymentStatusEl.value || "UNPAID";

            if (status === "PARTIAL") {
                payWrapper.classList.remove("d-none");
            } else {
                payWrapper.classList.add("d-none");
                if (payAmountEl) payAmountEl.value = "0";
            }

            const { total, due } = computeInvoiceTotals();
            formTotalEl.textContent = total.toFixed(2);
            formDueEl.textContent = due.toFixed(2);

            if (isApplied) {
                setNoteAsWordsFromTotal();
            }
        }

        // ✅ listeners
        paymentStatusEl.addEventListener("change", updatePaymentUI);
        if (payAmountEl) payAmountEl.addEventListener("input", updatePaymentUI);

        // ✅ Convert number to words (English) for taka.

        function convert_number_to_words(amount) {
            if (amount === null || amount === undefined || isNaN(amount)) return "Zero";

            const ones = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine",
                "Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen",
                "Seventeen", "Eighteen", "Nineteen"];
            const tens = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];

            function twoDigits(n) {
                if (n === 0) return "";
                if (n < 20) return ones[n];
                const t = Math.floor(n / 10);
                const r = n % 10;
                return tens[t] + (r ? " " + ones[r] : "");
            }

            function threeDigits(n) {
                const h = Math.floor(n / 100);
                const r = n % 100;
                let s = "";
                if (h) s += ones[h] + " Hundred";
                const td = twoDigits(r);
                if (td) s += (s ? " " : "") + td;
                return s;
            }

            function chunkToWords(n) {
                if (n === 0) return "Zero";

                const billion = Math.floor(n / 1000000000);
                n %= 1000000000;
                const million = Math.floor(n / 1000000);
                n %= 1000000;
                const thousand = Math.floor(n / 1000);
                const rest = n % 1000;

                const parts = [];
                if (billion) parts.push(threeDigits(billion) + " Billion");
                if (million) parts.push(threeDigits(million) + " Million");
                if (thousand) parts.push(threeDigits(thousand) + " Thousand");
                if (rest) parts.push(threeDigits(rest));

                return parts.join(" ").trim();
            }

            const taka = Math.floor(amount);
            return chunkToWords(taka).trim();
        }

        // auto word set in note
        function setNoteAsWordsFromTotal() {
            const total = getTotalAmount();
            const words = convert_number_to_words(total);
            noteTextarea.value = `${words} Taka Only.`;
        }


        // Toggle behavior for Note (words)
        let isApplied = true; // default words mode ON

        setNoteAsWordsFromTotal();
        calcBtn.classList.add("active");

        calcBtn.addEventListener("click", function () {
            isApplied = !isApplied;

            if (isApplied) {
                setNoteAsWordsFromTotal();
                calcBtn.classList.add("active");
            } else {
                noteTextarea.value = "Zero Taka Only.";
                calcBtn.classList.remove("active");
            }
        });


        // replace row
        function recalcRow(row) {
            const qtyInput = row.querySelector(".item-qty");
            const rateInput = row.querySelector(".item-rate");
            const amountInput = row.querySelector(".item-amount");

            const qty = extractNumber(qtyInput.value);
            const rate = parseFloat(rateInput.value) || 0;

            const amount = qty * rate;
            amountInput.value = amount.toFixed(2);
        }


        function attachRowEvents(row) {
            const qtyInput = row.querySelector(".item-qty");
            const rateInput = row.querySelector(".item-rate");
            const deleteBtn = row.querySelector(".btn-delete-row");

            qtyInput.addEventListener("input", () => { recalcRow(row); updatePaymentUI(); });
            rateInput.addEventListener("input", () => { recalcRow(row); updatePaymentUI(); });

            deleteBtn.addEventListener("click", function () {
                if (itemsBody.rows.length > 1) row.remove();
                updatePaymentUI();
            });
        }

        // init rows
        Array.from(itemsBody.rows).forEach(row => {
            attachRowEvents(row);
            recalcRow(row);
        });

        // ✅ initial totals
        updatePaymentUI();

        // Add item
        document.getElementById("add-item-btn").addEventListener("click", function () {
            const newRow = itemsBody.rows[0].cloneNode(true);

            newRow.querySelector(".item-desc").value = "";
            newRow.querySelector(".item-qty").value = 1;
            newRow.querySelector(".item-rate").value = 0;
            newRow.querySelector(".item-amount").value = "0.00";

            attachRowEvents(newRow);
            itemsBody.appendChild(newRow);

            recalcRow(newRow);
            updatePaymentUI();

        });

        updateNoteFromTotal();

        // Back
        document.getElementById("back-btn").addEventListener("click", function () {
            window.history.back();
        });

        // Reset
        document.getElementById("reset-btn").addEventListener("click", function () {
            if (!confirm("Reset all invoice fields?")) return;

            invoiceForm.reset();

            // date again
            if (dateInput) {
                const today = new Date();
                const yyyy = today.getFullYear();
                const mm = String(today.getMonth() + 1).padStart(2, "0");
                const dd = String(today.getDate()).padStart(2, "0");
                dateInput.value = `${yyyy}-${mm}-${dd}`;
            }

            while (itemsBody.rows.length > 1) itemsBody.deleteRow(1);

            const firstRow = itemsBody.rows[0];
            firstRow.querySelector(".item-desc").value = "";
            firstRow.querySelector(".item-qty").value = 1;
            firstRow.querySelector(".item-rate").value = 0;
            firstRow.querySelector(".item-amount").value = "0.00";

            recalcRow(firstRow);
            updatePaymentUI();
        });

        // View / Preview
        const MIN_ROWS = 7;

        function makeEmptyPreviewRow() {
            return `
        <tr class="empty-row">
            <td></td>
            <td>&nbsp;</td>
            <td class="text-center">&nbsp;</td>
            <td class="text-center">&nbsp;</td>
            <td class="text-center">&nbsp;</td>
        </tr>`;
        }

        function getNonEmptyItemRows() {
            return Array.from(itemsBody.querySelectorAll("tr.item-row")).filter(row => {
                const desc = row.querySelector(".item-desc")?.value?.trim() || "";
                const qty = parseFloat(row.querySelector(".item-qty")?.value) || 0;
                const rate = parseFloat(row.querySelector(".item-rate")?.value) || 0;
                return desc !== "" || qty > 0 || rate > 0;
            });
        }

        document.getElementById("view-btn").addEventListener("click", function () {
            const billName = document.getElementById("bill-name").value || "Customer";
            const invoiceNumber = document.getElementById("invoice-number").value || "--";
            const invoiceDate = document.getElementById("invoice-date").value || "-";
            const status = paymentStatusEl.value || "UNPAID";
            const note = noteTextarea.value || "";

            const totals = computeInvoiceTotals();

            let rowsHtml = "";
            let total = 0;

            const filledRows = getNonEmptyItemRows();

            filledRows.forEach((row, idx) => {
                const desc = row.querySelector(".item-desc").value;
                const qty = parseFloat(row.querySelector(".item-qty").value) || 0;
                const rate = parseFloat(row.querySelector(".item-rate").value) || 0;
                const amount = qty * rate;
                total += amount;

                rowsHtml += `
            <tr>
                <td>#${idx + 1}</td>
                <td>${desc || "-"}</td>
                <td class="text-center">${qty}</td>
                <td class="text-center">Tk ${rate.toFixed(2)}</td>
                <td class="text-center">Tk ${amount.toFixed(2)}</td>
            </tr>`;
            });

            const emptyRowsNeeded = Math.max(0, MIN_ROWS - filledRows.length);
            for (let i = 0; i < emptyRowsNeeded; i++) rowsHtml += makeEmptyPreviewRow();

            const unpaidBadge = status === "UNPAID"
                ? `<span class="badge-status-unpaid ms-2">UNPAID</span>`
                : `<span class="badge base-bg base-p ms-2">${status}</span>`;

            previewBody.innerHTML = `
        <div class="invoice-preview-card" id="invoice-preview-card">
           
            <div class="d-flex justify-content-between align-items-start">
                <div class="invoice_left_heading">
                    <img src="../assets/logo.png" alt="Logo" style="width: 180px; margin-bottom: 5px;">
                    <div><strong>Client Name</strong>: ${billName}</div>
                    <div><strong>Institution Name</strong>: ${document.getElementById("bill-school").value || ""}</div>
                    <div><strong>Phone Number</strong>: ${document.getElementById("bill-phone").value || ""}</div>
                </div>
                <div class="text-end small">
                    <h4 class="fw-bold mb-1 fs-3" style="color: #1FBD59;">INVOICE</h4>
                    <div class="fs-7 text-muted">Date: ${invoiceDate}</div>
                    <div class="text-muted">Invoice: ${invoiceNumber}</div>
                </div>
            </div>

            <div class="invoice-preview-header-line"></div>

            <div class="table-responsive my-3 ">
                <table class="table invoice-preview-table mb-0">
                    <thead>
                        <tr>
                            <th class="text-white" style="width: 10%; background-color: #1FBD59;">Item #</th>
                            <th class="text-white" style="background-color: #1FBD59;">Description</th>
                            <th class="text-white text-center" style="width: 15%; background-color: #1FBD59;">Quantity</th>
                            <th class="text-white text-center" style="width: 20%; background-color: #1FBD59;">Amount</th>
                            <th class="text-white text-center" style="width: 20%; background-color: #1FBD59;">Total</th>
                        </tr>
                    </thead>
                    <tbody id="bg-img-logo">${rowsHtml || `
                        <tr><td colspan="5" class="text-center text-muted py-3">No items added.</td></tr>
                    `}</tbody>
                </table>
            </div>

            <div class="row mt-3 small">
                <div class="col-md-7">
                    ${note ? `<div><strong>Note:</strong> ${note}</div>` : ""}
                </div>
                <div class="col-md-5">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th class="text-end">Subtotal</th>
                            <td class="text-end">Tk ${total.toFixed(2)}</td>
                        </tr>
                        <tr style="background-color: #DCFCE7;">
                            <th class="text-end">Due</th>
                            <td class="text-end">Tk ${totals.due.toFixed(2)}</td>
                        </tr>
                        <tr style="background-color: #1FBD59">
                            <th class="text-end">TOTAL</th>
                            <td class="text-end fw-bold">Tk ${total.toFixed(2)}</td>
                        </tr>
                    </table>
                    <div class="mt-2 text-end">${unpaidBadge}</div>
                </div>
            </div>

            <div class="mt-4 small text-center text-muted">
                We believe education is powerful. Thank you for being with us.
            </div>
        </div>`;

            const modalEl = document.getElementById("previewModal");
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        });

        // Download Preview as image
        document.getElementById("download-preview-btn").addEventListener("click", function () {
            const card = document.getElementById("invoice-preview-card");
            if (!card) {
                alert("Please click View to generate the preview first.");
                return;
            }

            const invNo = document.getElementById("invoice-number").value || "invoice";

            html2canvas(card, {
                scale: 2,
                useCORS: true,
                backgroundColor: "#ffffff"
            }).then(canvas => {
                const link = document.createElement("a");
                link.download = `${invNo}_invoice.png`;
                link.href = canvas.toDataURL("image/png");
                link.click();
            }).catch(err => {
                console.error(err);
                alert("Image download failed.");
            });
        });

        document.getElementById("add-invoice-btn").addEventListener("click", function () {
            alert("Add Invoice clicked (এখানে তুমি নিজের PHP / AJAX কোড কল করবে)।");
        });

        document.getElementById("print-btn").addEventListener("click", function () {
            window.print();
        });

        document.getElementById("download-pdf-btn").addEventListener("click", function () {
            alert("Download PDF clicked (এখানে তুমি HTML2PDF বা server-side PDF জেনারেশন করবে)।");
        });

    });
</script>


</body>

</html>