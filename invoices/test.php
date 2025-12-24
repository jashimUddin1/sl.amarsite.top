<div class="mb-3">
    <label class="form-label">Invoice Number</label>
    <input type="text" id="checkInvoiceInput" class="form-control" placeholder="Invoice number দিন">
</div>

<button class="btn btn-primary" id="checkInvoiceBtn">
    Check Invoice
</button>


<script>
let ALL_INVOICE_NUMBERS = [];

// 🔹 সব invoice number লোড
fetch('controllers/get_invoice_numbers.php')
  .then(res => res.json())
  .then(data => {
      if (data.ok) {
          ALL_INVOICE_NUMBERS = data.invoiceNumbers;
      }
  });

// 🔹 button click handler
document.getElementById('checkInvoiceBtn').addEventListener('click', () => {
    const input = document.getElementById('checkInvoiceInput').value.trim();

    if (input === "") {
        showToast("Invoice number দিন", "danger");
        return;
    }

    if (ALL_INVOICE_NUMBERS.includes(input)) {
        showToast("এই invoice number আগেই আছে", "danger");
    } else {
        showToast("Invoice নাই, এখন add করতে পারো", "success");
    }
});
</script>
