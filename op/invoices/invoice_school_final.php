<?php // invoices/invoice_school_final.php
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

// ✅ Get next invoice number from invoices.in_no (global)
$sql = "
SELECT COALESCE(MAX(in_no), 0) + 1 AS next_in_no
FROM invoices
";
$stmt = $pdo->query($sql);
$nextInvoiceNumber = (int)$stmt->fetchColumn();

require '../layout/single_invoice_header_final.php';
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
                                value="<?= htmlspecialchars($school['school_name']) ?>"
                                placeholder="Institution Name"
                                >
                        </div>

                        <div class="mb-3">
                            <input type="text" class="form-control" id="bill-name"
                                value="<?= htmlspecialchars($school['client_name']) ?>"
                                placeholder="Client Name"
                                >
                        </div>

                        <div>
                            <input type="text" class="form-control" id="bill-phone"
                                value="<?= htmlspecialchars($school['mobile'] ?? '') ?>"
                                placeholder="Phone Number"
                                >
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
                                    <input type="text" class="form-control item-qty" value="1" required>
                                </td>
                                <td>
                                    <input type="number" min="0" step="0.01" class="form-control item-rate" value="0"
                                        required>
                                </td>
                                <td>
                                    <input type="number" class="form-control item-amount" value="0.00" required
                                        >
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

                <!-- ✅ Action Row -->
                <div
                    class="d-flex flex-column flex-md-row align-items-stretch align-items-md-end justify-content-between gap-3 mb-4">
                    <div class="d-flex justify-content-between gap-2">
                        <button type="button" class="btn btn-add-item btn-sm" id="add-item-btn">
                            <i class="fa-solid fa-plus me-1"></i> Add Item
                        </button>

                        <button type="button" class="btn btn-outline-secondary btn-sm" id="view-btn">
                            <i class="fa-regular fa-eye me-1"></i> View
                        </button>
                    </div>

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
                        <i class="fa-solid fa-circle-plus me-2"></i> Save Invoice
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

<!-- ✅ Toast (Bangla) -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
    <div id="paymentToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive"
        aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="paymentToastMsg">বার্তা</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                aria-label="Close"></button>
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

        const paymentStatusEl = document.getElementById("payment-status");
        const formTotalEl = document.getElementById("form-total");
        const formDueEl = document.getElementById("form-due");
        const payWrapper = document.getElementById("pay-wrapper");
        const payAmountEl = document.getElementById("pay-amount");

        let isApplied = true;

        // ✅ toast helpers
        let toastShown = false;

        function showToast(message, type = "success") {
            const toastEl = document.getElementById("paymentToast");
            const msgEl = document.getElementById("paymentToastMsg");
            if (!toastEl || !msgEl) return;

            msgEl.textContent = message;

            toastEl.classList.remove("text-bg-success", "text-bg-warning", "text-bg-info", "text-bg-danger");
            toastEl.classList.add(`text-bg-${type}`);

            const toast = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 2600, autohide: true });
            toast.show();
        }

        function extractNumber(value, fallback = 1) {
            const str = String(value ?? "").trim();
            if (!str) return fallback;

            const match = str.match(/[\d.]+/);
            const num = match ? parseFloat(match[0]) : NaN;

            return Number.isFinite(num) ? num : fallback;
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

        // ✅ total calc
        function getTotalAmount() {
            let total = 0;
            itemsBody.querySelectorAll("tr.item-row").forEach(row => {
                const qty = extractNumber(row.querySelector(".item-qty")?.value || "", 1);
                const rate = parseFloat(row.querySelector(".item-rate")?.value) || 0;
                total += qty * rate;
            });
            return Math.round(total * 100) / 100;
        }

        // ✅ number to words (English) for note
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

        function setNoteAsWordsFromTotal() {
            const total = getTotalAmount();
            const words = convert_number_to_words(total);
            noteTextarea.value = `${words} Taka Only.`;
        }

        // ✅ compute totals + clean UX + Bangla toast
        function computeInvoiceTotals() {
            const total = getTotalAmount();
            let status = paymentStatusEl.value || "UNPAID";
            let pay = 0;

            if (status === "PARTIAL") {
                pay = parseFloat(payAmountEl.value);
                pay = Number.isFinite(pay) ? pay : 0;

                if (pay < 0) pay = 0;

                if (pay > total) {
                    pay = total;
                    payAmountEl.value = total.toFixed(2);

                    if (!toastShown && total > 0) {
                        showToast("পে এমাউন্ট টোটালের বেশি ছিল—টোটাল অনুযায়ী ঠিক করা হয়েছে।", "warning");
                        toastShown = true;
                    }
                } else {
                    payAmountEl.value = pay.toFixed(2);
                }

                if (total > 0 && Math.abs(total - pay) < 0.0001) {
                    status = "PAID";
                    paymentStatusEl.value = "PAID";
                    payWrapper.classList.add("d-none");

                    if (!toastShown) {
                        showToast("সম্পূর্ণ টাকা পরিশোধ হয়েছে—স্ট্যাটাস PAID করা হলো।", "success");
                        toastShown = true;
                    }
                }
            } else if (status === "PAID") {
                pay = total;

                if (total > 0 && !toastShown) {
                    showToast("স্ট্যাটাস PAID সিলেক্ট করা হয়েছে।", "success");
                    toastShown = true;
                }
            } else {
                pay = 0;
            }

            const due = Math.max(0, total - pay);
            return { total, pay, due, status };
        }

        function updatePaymentUI() {
            // UNPAID এ ফিরলে আবার toast দেখানোর সুযোগ
            if ((paymentStatusEl.value || "UNPAID") === "UNPAID") toastShown = false;

            const totals = computeInvoiceTotals();

            if (totals.status === "PARTIAL") {
                payWrapper.classList.remove("d-none");
            } else {
                payWrapper.classList.add("d-none");
                if (payAmountEl) payAmountEl.value = "0";
            }

            formTotalEl.textContent = totals.total.toFixed(2);
            formDueEl.textContent = totals.due.toFixed(2);

            if (isApplied) setNoteAsWordsFromTotal();
        }

        paymentStatusEl.addEventListener("change", updatePaymentUI);
        if (payAmountEl) payAmountEl.addEventListener("input", updatePaymentUI);

        // Default ON
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

        function recalcRow(row) {
            const qtyInput = row.querySelector(".item-qty");
            const rateInput = row.querySelector(".item-rate");
            const amountInput = row.querySelector(".item-amount");

            const qty = extractNumber(qtyInput.value, 1);
            const rate = parseFloat(rateInput.value) || 0;

            amountInput.value = (qty * rate).toFixed(2);
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

        // ✅ Enter navigation
        itemsBody.addEventListener("keydown", function (e) {
            if (e.key !== "Enter") return;

            const el = e.target;
            if (!el.matches(".item-desc, .item-qty, .item-rate")) return;

            e.preventDefault();

            const row = el.closest("tr.item-row");
            if (!row) return;

            const inputs = Array.from(row.querySelectorAll(".item-desc, .item-qty, .item-rate"));
            const idx = inputs.indexOf(el);

            if (idx >= 0 && idx < inputs.length - 1) {
                inputs[idx + 1].focus();
                inputs[idx + 1].select?.();
                return;
            }

            const allRows = Array.from(itemsBody.querySelectorAll("tr.item-row"));
            const rowIndex = allRows.indexOf(row);

            if (rowIndex >= 0 && rowIndex < allRows.length - 1) {
                const nextDesc = allRows[rowIndex + 1].querySelector(".item-desc");
                nextDesc?.focus();
                nextDesc?.select?.();
                return;
            }

            document.getElementById("add-item-btn").click();
            const newLastRow = itemsBody.querySelector("tr.item-row:last-child .item-desc");
            newLastRow?.focus();
        });

        // init rows
        Array.from(itemsBody.rows).forEach(row => {
            attachRowEvents(row);
            recalcRow(row);
        });

        updatePaymentUI();

        // Add item
        document.getElementById("add-item-btn").addEventListener("click", function () {
            const newRow = itemsBody.rows[0].cloneNode(true);

            newRow.querySelector(".item-desc").value = "";
            newRow.querySelector(".item-qty").value = "1";
            newRow.querySelector(".item-rate").value = 0;
            newRow.querySelector(".item-amount").value = "0.00";

            attachRowEvents(newRow);
            itemsBody.appendChild(newRow);

            recalcRow(newRow);
            updatePaymentUI();
        });

        // Back
        document.getElementById("back-btn").addEventListener("click", function () {
            window.history.back();
        });

        // Reset
        document.getElementById("reset-btn").addEventListener("click", function () {
            if (!confirm("Reset all invoice fields?")) return;

            invoiceForm.reset();
            toastShown = false;

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
            firstRow.querySelector(".item-qty").value = "1";
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
                const qtyRaw = row.querySelector(".item-qty")?.value || "";
                const qtyVal = extractNumber(qtyRaw, 0);
                const rate = parseFloat(row.querySelector(".item-rate")?.value) || 0;
                return desc !== "" || qtyVal > 0 || rate > 0;
            });
        }

        document.getElementById("view-btn").addEventListener("click", function () {
            const billName = document.getElementById("bill-name").value || "....................";
            const invoiceNumber = document.getElementById("invoice-number").value || "--";
            const invoiceDate = document.getElementById("invoice-date").value || "-";
            const note = noteTextarea.value || "";

            const totals = computeInvoiceTotals();
            const filledRows = getNonEmptyItemRows();

            let rowsHtml = "";

            filledRows.forEach((row, idx) => {
                const desc = row.querySelector(".item-desc").value || "-";
                const qtyRaw = row.querySelector(".item-qty").value || "";
                const qty = extractNumber(qtyRaw, 1);
                const rate = parseFloat(row.querySelector(".item-rate").value) || 0;
                const amount = qty * rate;

                rowsHtml += `
            <tr>
                <td>#${idx + 1}</td>
                <td>${desc}</td>
                <td class="text-center">${qtyRaw || "1"}</td>
                <td class="text-center">${rate.toFixed(2)}</td>
                <td class="text-center">${amount.toFixed(2)}</td>
            </tr>`;
            });

            const emptyRowsNeeded = Math.max(0, MIN_ROWS - filledRows.length);
            for (let i = 0; i < emptyRowsNeeded; i++) rowsHtml += makeEmptyPreviewRow();

            const statusForBadge = totals.status;
            const unpaidBadge = statusForBadge === "UNPAID"
                ? `<span class="badge-status-unpaid ms-2">UNPAID</span>`
                : `<span class="badge base-bg base-p7d ms-2">${statusForBadge}</span>`;

            previewBody.innerHTML = `
        <div class="invoice-preview-card" id="invoice-preview-card">

            <div class="d-flex justify-content-between align-items-start">
                <div class="invoice_left_heading">
                    <img src="../assets/logo.png" alt="Logo" style="width: 140px; margin-bottom: 5px;">

                    <div><strong>Client Name</strong>: ${billName}</div>
                    <div><strong>Phone Number</strong>: ${document.getElementById("bill-phone").value || ""}</div>
                    <div><strong>Institution Name</strong>: ${document.getElementById("bill-school").value || ""}</div>
                </div>
                <div class="text-end invoice_right_heading">
                    <h4 class="fw-bold mb-1 fs-6" style="color: #1FBD59;">INVOICE</h4>
                    <div class="text-muted">Date: ${invoiceDate}</div>
                    <div class="text-muted">Invoice: ${invoiceNumber}</div>
                </div>
            </div>

            <div class="invoice-preview-header-line"></div>

            <div class="table-responsive mt-3">
                <table class="table invoice-preview-table mb-0">
                    <thead>
                        <tr>
                            <th class="text-white" style="width: 10%; background-color: #1FBD59;">Item</th>
                            <th class="text-white" style="background-color: #1FBD59;">Description</th>
                            <th class="text-white text-center" style="width: 15%; background-color: #1FBD59;">Quantity</th>
                            <th class="text-white text-center" style="width: 15%; background-color: #1FBD59;">Rate</th>
                            <th class="text-white text-center" style="width: 18%; background-color: #1FBD59;">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="bg-img-logo">${rowsHtml || `
                        <tr><td colspan="5" class="text-center text-muted py-3">No items added.</td></tr>
                    `}</tbody>
                </table>
            </div>

            <div class="row small">
                <div class="col-md-7 mt-1">
                    ${note ? `<div style="font-size:0.5rem"><strong>Note:</strong> ${note}</div>` : ""}
                </div>
                <div class="col-md-5">
                    <table class="table table-sm subtotal_cal">
                        <tr>
                            <th class="text-end">Subtotal</th>
                            <td class="text-end">Tk ${totals.total.toFixed(2)}</td>
                        </tr>
                        <tr style="background: #DCFCE7;">
                            <th class="text-end">Due</th>
                            <td class="text-end">Tk ${totals.due.toFixed(2)}</td>
                        </tr>
                        <tr style="background: #1FBD59">
                            <th class="text-end">TOTAL</th>
                            <td class="text-end fw-bold">Tk ${totals.total.toFixed(2)}</td>
                        </tr>
                    </table>
                    <div class="mt-2 text-end">${unpaidBadge}</div>
                </div>
            </div>

            <footer class="mt-5">
                <div class="footer_top d-flex justify-content-between align-items-end w-100">
                    <div class="footer_top_left text-center rem6">
                        <img src="../assets/signature.png" alt="Signature" class="mb-1" style="width:112px; height:auto;">
                        <p class="mb-0" style="border-top:1px solid #000;">Easin Khan Santo (Co-founder)</p>
                    </div>

                    <div class="footer_top_right text-end ms-auto rem7">
                        <p class="mb-0">bkash & Nagad 01805-123649</p>
                        <a href="https://www.edurlab.com" style="font-size: 1.2rem" class="text-decoration-none fw-bold">www.edurlab.com</a>
                    </div>
                </div>

                <div class="footer_bottom_txt text-center mt-2">
                    <p class="mb-0 rem5">
                        We believe education is the key to progress, and EduRLab is always here to support that journey.
                    </p>
                </div>
            </footer>

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

        document.getElementById("add-invoice-btn").addEventListener("click", async function () {

            const filledRows = getNonEmptyItemRows();
            if (filledRows.length === 0) {
                showToast("কমপক্ষে ১টা item দিন।", "danger");
                return;
            }

            const items = filledRows.map(row => {
                const desc = row.querySelector(".item-desc").value || "";
                const qtyRaw = row.querySelector(".item-qty").value || "1";
                const qty = extractNumber(qtyRaw, 1);
                const rate = parseFloat(row.querySelector(".item-rate").value) || 0;
                return {
                    desc,
                    qty_raw: qtyRaw,
                    qty,
                    rate,
                    amount: Math.round(qty * rate * 100) / 100
                };
            });

            const totals = computeInvoiceTotals();

            const invNo = parseInt(document.getElementById("invoice-number").value, 10) || 0;

            const payload = {
                school_id: parseInt(document.getElementById("school-id").value, 10),
                in_no: invNo,
                data: {
                    invoiceNumber: invNo,
                    invoiceDate: document.getElementById("invoice-date").value || "",
                    invoiceStyle: document.getElementById("invoice-style").value || "classic",

                    billTo: {
                        school: document.getElementById("bill-school").value || "",
                        name: document.getElementById("bill-name").value || "",
                        phone: document.getElementById("bill-phone").value || ""
                    },

                    items,
                    totals: {
                        total: totals.total,
                        pay: totals.pay,
                        due: totals.due,
                        status: totals.status
                    },

                    note: (document.getElementById("invoice-note").value || "").trim()
                }
            };

            // Basic validation
            if (!payload.in_no || payload.in_no <= 0) {
                showToast("Invoice Number ঠিক দিন।", "danger");
                return;
            }

            try {
                const res = await fetch("controllers/invoice_save_school.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload)
                });

                const out = await res.json().catch(() => ({}));

                if (!res.ok) {
                    showToast(out.msg || "Save failed", "danger");
                    return;
                }

                showToast("Invoice Save Successfully", "success");

                //  save hole invoice-number auto next kore dite pore line ta comment sorai daw
                // document.getElementById("invoice-number").value = payload.in_no + 1;

            } catch (err) {
                console.error(err);
                showToast("Network/Server error", "danger");
            }
        });

        document.getElementById("download-pdf-btn").addEventListener("click", async function () {
            const card = document.getElementById("invoice-preview-card");
            if (!card) {
                showToast("PDF ডাউনলোডের আগে View দিয়ে Preview বানাও।", "warning");
                return;
            }

            const invNo = document.getElementById("invoice-number").value || "invoice";

            // ✅ make a visible clone in body (fixes blank canvas)
            const tempWrap = document.createElement("div");
            tempWrap.style.position = "fixed";
            tempWrap.style.left = "0";
            tempWrap.style.top = "0";
            tempWrap.style.width = "100%";
            tempWrap.style.height = "100%";
            tempWrap.style.background = "#fff";
            tempWrap.style.zIndex = "999999";
            tempWrap.style.overflow = "auto";
            tempWrap.style.padding = "20px";

            const clone = card.cloneNode(true);
            clone.style.maxWidth = "210mm";
            clone.style.width = "210mm";
            clone.style.margin = "0 auto";
            clone.style.boxShadow = "none";

            tempWrap.appendChild(clone);
            document.body.appendChild(tempWrap);

            try {
                // images load wait (logo/signature)
                const imgs = Array.from(clone.querySelectorAll("img"));
                await Promise.all(imgs.map(img => {
                    if (img.complete) return Promise.resolve();
                    return new Promise(res => { img.onload = img.onerror = () => res(); });
                }));

                const canvas = await html2canvas(clone, {
                    scale: 3,
                    useCORS: true,
                    backgroundColor: "#ffffff",
                    scrollX: 0,
                    scrollY: -window.scrollY
                });

                const imgData = canvas.toDataURL("image/png");

                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF("p", "mm", "a4");

                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();

                const imgWidth = pageWidth;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;

                if (imgHeight <= pageHeight) {
                    pdf.addImage(imgData, "PNG", 0, 0, imgWidth, imgHeight);
                } else {
                    // multipage
                    let heightLeft = imgHeight;
                    let position = 0;
                    while (heightLeft > 0) {
                        pdf.addImage(imgData, "PNG", 0, position, imgWidth, imgHeight);
                        heightLeft -= pageHeight;
                        position -= pageHeight;
                        if (heightLeft > 0) pdf.addPage();
                    }
                }

                pdf.save(`${invNo}_invoice.pdf`);
                showToast("PDF ডাউনলোড হয়েছে ✅", "success");
            } catch (err) {
                console.error(err);
                showToast("PDF তৈরি করা যায়নি। (Console দেখো)", "danger");
            } finally {
                document.body.removeChild(tempWrap);
            }
        });



    });
</script>

</body>

</html>