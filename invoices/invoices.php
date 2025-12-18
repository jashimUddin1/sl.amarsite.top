<?php // chatpgt => Index file fix steps
require_once '../auth/config.php';
require_login();


$pageTitle = 'invoices - EduRLab School List';
$pageHeading = 'invoices';
$activeMenu = 'invoices';


require '../layout/layout_header_invoices.php';

//for testing purpose
// echo "<pre>";
// print_r($_SESSION);
// echo "</pre>";
?>

<style>
  @media screen and (max-width: 768px) {
    .mt-8 {
      margin-top: 3rem !important;
    }
  }
</style>

<body class="bg-gray-100">

  <!-- Form Section -->
  <div class="no-print container mx-auto sm:p-6 ">
    <!-- Increased max-w-5xl to max-w-6xl for a slightly wider form -->


    <!-- Saved Invoices Section -->
    <div class="bg-white rounded-2xl shadow-lg p-6  max-w-6xl mx-auto mt-8">

      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center"><i
            class="fas fa-history mr-2 text-gray-400"></i> Saved Invoices</h2>

        <button id="openCreateModalBtn"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
          Add New
        </button>
      </div>

      <div id="saved-invoices-list" class="space-y-4">
        <p class="text-gray-500 text-center" id="saved-invoices-status">No saved invoices yet.</p>
      </div>
      <div class="text-center mt-6">
        <button type="button" id="clear-form-btn"
          class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
          <i class="fas fa-eraser mr-2"></i> <span>Create New Invoice</span>
        </button>
      </div>
    </div>

  </div>


  <!-- create new invoice modal -->
  <!-- CREATE / EDIT INVOICE MODAL -->
  <div id="invoiceModal"
    class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center p-6 z-[9999] overflow-y-auto">

    <div class="bg-white rounded-xl shadow-xl max-w-3xl w-full p-6 relative my-auto">

      <!-- Close button -->
      <button id="closeInvoiceModal"
        class="absolute top-3 right-3 text-3xl text-gray-500 hover:text-gray-800">&times;</button>

      <h2 id="modalTitle" class="text-2xl font-bold mb-4">Create New Invoice</h2>

      <form id="invoice-form" action="core/invoice_form.php" method="POST" class="space-y-8">
        <!-- Client & Invoice Details -->
        <div class="form-section grid grid-cols-1 md:grid-cols-2 gap-8 border-b pb-8">
          <div>
            <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i
                class="fas fa-user-tie mr-2 text-gray-400"></i> Bill To</h3>
            <div class="space-y-4">
              <input type="text" id="client-institution" placeholder="Institution Name" required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
              <input type="text" id="client-name" placeholder="Client Name" required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
              <input type="text" id="client-phone" placeholder="Client Phone" required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
          </div>
          <div>
            <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i
                class="fas fa-file-invoice mr-2 text-gray-400"></i> Invoice Details</h3>
            <div class="space-y-4">
              <input type="text" id="invoice-number" placeholder="Invoice #" required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
              <input type="date" id="invoice-date" required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
              <select id="invoice-style"
                class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <option value="classic" selected>Classic Style</option>
                <option value="minimalist">Minimalist Style</option>
              </select>
            </div>
            <h3 class="text-xl font-semibold text-gray-700 mt-6 mb-4 flex items-center"><i
                class="fas fa-credit-card mr-2 text-gray-400"></i> Payment Status</h3>
            <select id="payment-status"
              class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
              <option value="UNPAID" selected>UNPAID</option>
              <option value="PAID">PAID</option>
            </select>
          </div>
        </div>

        <!-- Items Section -->
        <div class="form-section">
          <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i
              class="fas fa-list-ul mr-2 text-gray-400"></i> Items</h3>
          <div id="items-container" class="space-y-4"></div>
          <div class="mt-4 flex items-center gap-4">
            <button type="button" id="add-item-btn"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
              <i class="fas fa-plus mr-2"></i> <span>Add Item</span>
            </button>
            <button type="button" id="view-invoice-btn"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
              <i class="fas fa-eye mr-2"></i> <span>View</span>
            </button>
          </div>
        </div>

        <!-- Note Section -->
        <div class="form-section">
          <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i
              class="fas fa-sticky-note mr-2 text-gray-400"></i> Note</h3>
          <textarea id="invoice-note" rows="3"
            class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="Amount in words will be auto-generated..."></textarea>
        </div>

        <!-- Action Buttons -->
        <div class="text-center pt-6 flex flex-wrap items-center justify-center gap-4">
          <button type="submit" id="save-btn"
            class="w-full sm:w-auto inline-flex items-center justify-center py-3 px-6 border border-transparent shadow-lg text-base font-medium rounded-full text-white bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-transform transform hover:scale-105">
            <i class="fas fa-save mr-2" id="save-icon"></i> <span id="save-btn-text">Add Invoice</span>
          </button>
          <button type="submit" id="print-btn"
            class="w-full sm:w-auto inline-flex justify-center py-3 px-6 border border-transparent shadow-lg text-base font-medium rounded-full text-white bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-transform transform hover:scale-105">
            <i class="fas fa-print mr-2" id="print-icon"></i> <span id="print-btn-text">Print</span>
          </button>
          <button type="button" id="download-pdf-btn"
            class="w-full sm:w-auto inline-flex justify-center py-3 px-6 border border-transparent shadow-lg text-base font-medium rounded-full text-white bg-gradient-to-r from-red-500 to-orange-600 hover:from-red-600 hover:to-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-transform transform hover:scale-105">
            <i class="fas fa-file-pdf mr-2" id="pdf-icon"></i> <span id="pdf-btn-text">Download PDF</span>
          </button>
        </div>
      </form>

    </div>
  </div>


  <!-- View Modal -->
  <div id="view-modal"
    class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center p-4 z-50 overflow-hidden">
    <div id="view-container" class="w-full h-full relative flex items-center justify-center">
      <button id="close-view-btn"
        class="absolute top-0 right-0 m-2 text-4xl text-white hover:text-gray-300 leading-none z-20">&times;</button>
      <div id="view-scaler">
        <!-- Scaled Invoice will be injected here -->
      </div>
      <div id="modal-footer" class="no-print absolute bottom-4">
        <button id="modal-download-img-btn"
          class="inline-flex justify-center py-2 px-6 border border-transparent shadow-lg text-base font-medium rounded-full text-white bg-gradient-to-r from-green-500 to-teal-600 hover:from-green-600 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition-transform transform hover:scale-105">
          <i class="fas fa-download mr-2" id="img-icon"></i> <span id="img-btn-text">Download as Image</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Rename Modal -->
  <div id="rename-modal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm">
      <h3 class="text-xl font-semibold mb-4 text-center">Rename Invoice</h3>
      <input type="text" id="rename-input" placeholder="Enter new name"
        class="w-full p-2 border rounded-md mb-4 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
      <div class="flex justify-center gap-4">
        <button id="rename-cancel-btn"
          class="px-6 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">Cancel</button>
        <button id="rename-save-btn"
          class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Save</button>
      </div>
    </div>
  </div>

  <!-- Edit Modal -->
  <div id="edit-modal"
    class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center p-4 z-50 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8 max-w-2xl w-full relative my-auto">
      <button id="close-edit-btn"
        class="absolute top-4 right-4 text-4xl text-gray-500 hover:text-gray-800 leading-none z-20">&times;</button>
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Edit Invoice</h1>
        <p class="text-gray-500 mt-2">Update the invoice details below.</p>
      </div>

      <form id="edit-invoice-form" class="space-y-8">
        <!-- Client & Invoice Details -->
        <div class="form-section grid grid-cols-1 md:grid-cols-2 gap-8 border-b pb-8">
          <div>
            <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i
                class="fas fa-user-tie mr-2 text-gray-400"></i> Bill To</h3>
            <div class="space-y-4">
              <input type="text" id="edit-client-institution" placeholder="Institution Name" required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
              <input type="text" id="edit-client-name" placeholder="Client Name" required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
              <input type="text" id="edit-client-phone" placeholder="Client Phone" required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
          </div>
          <div>
            <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i
                class="fas fa-file-invoice mr-2 text-gray-400"></i> Invoice Details</h3>
            <div class="space-y-4">
              <input type="text" id="edit-invoice-number" placeholder="Invoice #" required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
              <input type="date" id="edit-invoice-date" required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
              <select id="edit-invoice-style"
                class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <option value="classic">Classic Style</option>
                <option value="minimalist">Minimalist Style</option>
              </select>
            </div>
            <h3 class="text-xl font-semibold text-gray-700 mt-6 mb-4 flex items-center"><i
                class="fas fa-credit-card mr-2 text-gray-400"></i> Payment Status</h3>
            <select id="edit-payment-status"
              class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
              <option value="UNPAID" selected>UNPAID</option>
              <option value="PAID">PAID</option>
            </select>
          </div>
        </div>

        <!-- Items Section -->
        <div class="form-section">
          <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i
              class="fas fa-list-ul mr-2 text-gray-400"></i> Items</h3>
          <div id="edit-items-container" class="space-y-4"></div>
          <div class="mt-4 flex items-center gap-4">
            <button type="button" id="edit-add-item-btn"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
              <i class="fas fa-plus mr-2"></i> <span>Add Item</span>
            </button>
          </div>
        </div>

        <!-- Note Section -->
        <div class="form-section">
          <h3 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i
              class="fas fa-sticky-note mr-2 text-gray-400"></i> Note</h3>
          <textarea id="edit-invoice-note" rows="3"
            class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="Amount in words will be auto-generated..."></textarea>
        </div>

        <div class="text-center pt-6">
          <button type="button" id="save-edit-btn" data-id="${invoice.dbId || invoice.id}"
            class="inline-flex items-center justify-center py-3 px-6 border border-transparent shadow-lg text-base font-medium rounded-full text-white bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-transform transform hover:scale-105">
            <i class="fas fa-save mr-2"></i> <span>Save Changes</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Confirmation Modal -->
  <div id="confirm-modal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm text-center">
      <p id="confirm-msg" class="mb-4 text-lg">Are you sure you want to delete this item?</p>
      <div class="flex justify-center gap-4">
        <button id="confirm-yes-btn" class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Yes</button>
        <button id="confirm-no-btn" class="px-6 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">No</button>
      </div>
    </div>
  </div>

  <!-- Message Modal -->
  <div id="message-modal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm text-center">
      <p id="message-text" class="mb-4 text-lg"></p>
      <button id="message-ok-btn" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">OK</button>
    </div>
  </div>

  <!-- Printable/Renderable Invoice Area -->
  <div id="print-area"></div>

  <script type="module">
    document.addEventListener('DOMContentLoaded', function () {
      const STORAGE_KEY = 'invoices';
      const companyInfo = {
        name: "EduRLab",
        website: "www.edurlab.com",
        slogan: "We believe education is the key to progress, and EduRLab is always here to support that journey.",
        payment: "bkash & Nagat 01805-123649"
      };

      let rowToDelete = null;
      let currentInvoiceId = null;
      let currentDbId = null;

      const form = document.getElementById('invoice-form');
      const itemsContainer = document.getElementById('items-container');
      const addItemBtn = document.getElementById('add-item-btn');
      const printArea = document.getElementById('print-area');
      const noteTextarea = document.getElementById('invoice-note');
      const viewModal = document.getElementById('view-modal');
      const viewContainer = document.getElementById('view-container');
      const viewScaler = document.getElementById('view-scaler');
      const closeViewBtn = document.getElementById('close-view-btn');
      const modalDownloadImgBtn = document.getElementById('modal-download-img-btn');
      const imgBtnText = document.getElementById('img-btn-text');
      const imgIcon = document.getElementById('img-icon');
      const renameModal = document.getElementById('rename-modal');
      const renameInput = document.getElementById('rename-input');
      const renameSaveBtn = document.getElementById('rename-save-btn');
      const renameCancelBtn = document.getElementById('rename-cancel-btn');
      const editModal = document.getElementById('edit-modal');
      const editItemsContainer = document.getElementById('edit-items-container');
      const editAddItemBtn = document.getElementById('edit-add-item-btn');
      const saveEditBtn = document.getElementById('save-edit-btn');
      const closeEditBtn = document.getElementById('close-edit-btn');
      const confirmModal = document.getElementById('confirm-modal');
      const confirmMsg = document.getElementById('confirm-msg');
      const confirmYesBtn = document.getElementById('confirm-yes-btn');
      const confirmNoBtn = document.getElementById('confirm-no-btn');
      const messageModal = document.getElementById('message-modal');
      const messageText = document.getElementById('message-text');
      const messageOkBtn = document.getElementById('message-ok-btn');
      const viewInvoiceBtn = document.getElementById('view-invoice-btn');
      const downloadPdfBtn = document.getElementById('download-pdf-btn');
      const pdfBtnText = document.getElementById('pdf-btn-text');
      const pdfIcon = document.getElementById('pdf-icon');
      const printBtn = document.getElementById('print-btn');
      const printBtnText = document.getElementById('print-btn-text');
      const printIcon = document.getElementById('fa-print');
      const savedInvoicesList = document.getElementById('saved-invoices-list');
      const savedInvoicesStatus = document.getElementById('saved-invoices-status');
      const saveBtn = document.getElementById('save-btn');
      const saveBtnText = document.getElementById('save-btn-text');
      const saveIcon = document.getElementById('save-icon');
      const clearFormBtn = document.getElementById('clear-form-btn');

      //for modal start 
      const invoiceModal = document.getElementById('invoiceModal');
      const openCreateModalBtn = document.getElementById('openCreateModalBtn');
      const closeInvoiceModal = document.getElementById('closeInvoiceModal');
      const modalTitle = document.getElementById('modalTitle');

      openCreateModalBtn.addEventListener('click', () => {
        clearForm();                // old form clearing logic (তোমারটা আগেই আছে)
        currentInvoiceId = null;    // new invoice mode
        modalTitle.textContent = "Create New Invoice";
        setSaveButtonMode("new");   // button text = Add Invoice
        loadNextInvoiceNumber();    // DB থেকে next invoice number
        invoiceModal.classList.remove("hidden");
      });

      closeInvoiceModal.addEventListener('click', () => {
        invoiceModal.classList.add("hidden");
      });
      //for modal end

      function setSaveButtonMode(mode) {
        if (mode === 'edit') {
          saveBtnText.textContent = 'Save Changes';
        } else {
          saveBtnText.textContent = 'Add Invoice';
        }
      }


      // Signature and logo data for different styles
      const styles = {
        classic: {
          logoUrl: "assets/logo.png",
          logoAlt: "EduRLab Logo",
          headerBg: "bg-gradient-to-r from-green-600 to-green-400 rounded-t-lg",
          tableHeadBg: "bg-gradient-to-r from-green-600 to-green-500 text-white",
          totalBg: "background: linear-gradient(to right, #16a34a, #22c55e);",
          dueBg: "bg-green-100",
          dueText: "text-gray-900",
          statusPaid: "inline-block bg-green-100 text-green-700 font-bold px-4 py-1 rounded-full border border-green-300 text-lg",
          statusUnpaid: "inline-block bg-red-100 text-red-700 font-bold px-4 py-1 rounded-full border border-red-300 text-lg",
          sigBlock: `
                        <div class="flex flex-col items-center w-64 text-center ">
                            <img src="assets/signature.png" alt="Signature" class="mb-2" style="width:180px; height: 35px;">
                            <p class="w-full text-center font-semibold text-lg whitespace-nowrap pt-1 border-t border-black">Easin Khan Santo (Co-founder)</p>
                        </div>
                    `,
        },
        minimalist: {
          logoUrl: "assets/logo.png",
          logoAlt: "EduRLab Logo",
          headerBg: "bg-gray-800 text-white rounded-t-lg",
          tableHeadBg: "bg-gray-700 text-white",
          totalBg: "background-color: #1f2937;",
          dueBg: "bg-gray-100",
          dueText: "text-gray-900",
          statusPaid: "bg-gray-200 text-green-700 font-bold px-4 py-1 rounded-full text-lg",
          statusUnpaid: "bg-gray-200 text-red-700 font-bold px-4 py-1 rounded-full text-lg",
          sigBlock: `
                        <div class="flex flex-col items-center w-64 text-center ">
                            <img src="/assets/signature.png" alt="Signature" class="mb-2" style="width:180px; height: 35px;">
                            <p class="w-full text-center font-semibold text-lg whitespace-nowrap pt-1 border-t border-black">Easin Khan Khan (Co-founder)</p>
                        </div>
                    `,
        }
      };

      document.getElementById('invoice-date').valueAsDate = new Date();

      // --- UI Functions ---
      function showMessage(msg) {
        messageText.textContent = msg;
        messageModal.classList.remove('hidden');
      }

      function showConfirmation(msg, onConfirm) {
        confirmMsg.textContent = msg;
        confirmModal.classList.remove('hidden');
        confirmYesBtn.onclick = () => {
          confirmModal.classList.add('hidden');
          onConfirm();
        };
      }

      function openRenameModal(id, currentName) {
        currentInvoiceId = id;
        renameInput.value = currentName;
        renameModal.classList.remove('hidden');
      }

      function closeRenameModal() {
        renameModal.classList.add('hidden');
        renameInput.value = '';
        currentInvoiceId = null;
      }

      function closeEditModal() {
        editModal.classList.add('hidden');
        editItemsContainer.innerHTML = '';
      }

      function clearForm() {
        form.reset();
        itemsContainer.innerHTML = '';
        addInvoiceItem(itemsContainer);
        document.getElementById('invoice-date').valueAsDate = new Date();
        updateTotalsAndNote();

        currentInvoiceId = null;
        document.getElementById('invoice-style').value = 'classic';

        // যদি setSaveButtonMode ব্যবহার করো:
        if (typeof setSaveButtonMode === 'function') {
          setSaveButtonMode('new');
        }

        // এখন auto next invoice number নেব
        loadNextInvoiceNumber();
      }


      function updateTotalsAndNote() {
        let subtotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
          subtotal += parseFloat(row.querySelector('[name="total"]').value) || 0;
        });
        noteTextarea.value = numberToWords(subtotal) + " Taka Only.";
      }

      // --- Invoice Item Handling ---
      const addInvoiceItem = (container, item = {}) => {
        const itemDiv = document.createElement('div');
        itemDiv.dataset.calculated = item.calculated || 'false';
        itemDiv.classList.add('item-row', 'grid', 'grid-cols-1', 'sm:grid-cols-12', 'gap-2', 'items-center', 'border-b', 'sm:border-none', 'pb-4', 'sm:pb-0');
        itemDiv.innerHTML = `
                    <div class="sm:col-span-4">
                        <input type="text" name="description" placeholder="Description" required value="${item.description || ''}" class="w-full p-2 border rounded-md">
                    </div>
                    <div class="sm:col-span-2">
                        <input type="text" name="quantity" value="${item.quantity || '1'}" placeholder="Quantity" required class="w-full p-2 border rounded-md">
                    </div>
                    <div class="sm:col-span-3 flex items-center gap-1">
                        <input type="number" name="amount" placeholder="Amount" step="0.01" required value="${item.amount || ''}" class="w-full p-2 border rounded-md">
                        <button type="button" class="calc-item-btn bg-indigo-500 text-white p-2 rounded-md hover:bg-indigo-600 flex-shrink-0 transition-colors ${item.calculated === 'true' ? 'bg-green-500 hover:bg-green-600' : ''}">
                            <i class="fas fa-calculator"></i>
                        </button>
                    </div>
                    <div class="sm:col-span-2">
                        <input type="number" name="total" placeholder="0.00" step="0.01" value="${item.total || ''}" class="w-full p-2 border rounded-md bg-gray-50">
                    </div>
                    <div class="sm:col-span-1 text-right">
                        <button type="button" class="remove-item-btn text-red-500 hover:text-red-700 p-2 rounded-full hover:bg-red-100 transition-all">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                `;
        container.appendChild(itemDiv);
      };

      // --- Local Storage Functions ---
      let serverInvoices = [];

      function getInvoices() {
        return serverInvoices;
      }

      function saveInvoices(invoices) {
        serverInvoices = invoices;
        renderInvoices();
      }

      function renderInvoices() {
        const invoices = getInvoices();
        savedInvoicesList.innerHTML = '';
        if (invoices.length === 0) {
          savedInvoicesStatus.classList.remove('hidden');
          return;
        }
        savedInvoicesStatus.classList.add('hidden');

        invoices.forEach(invoice => {
          const listItem = document.createElement('div');
          listItem.classList.add('flex', 'flex-col', 'sm:flex-row', 'justify-between', 'items-center', 'p-4', 'bg-gray-50', 'rounded-lg', 'shadow-sm', 'hover:bg-gray-100', 'transition-colors');
          const styleText = (invoice.invoiceStyle === 'minimalist') ? 'Minimalist' : 'Classic';
          listItem.innerHTML = `
                        <div class="flex-1 w-full sm:w-auto mb-2 sm:mb-0">
                            <p class="font-semibold text-gray-800">#${invoice.invoiceNumber || 'N/A'} - ${invoice.invoiceName || invoice.clientName || 'No Name'}</p>
                            <p class="text-sm text-gray-500">Style: ${styleText} | Date: ${new Date(invoice.invoiceDate).toLocaleDateString()}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-sm font-semibold px-3 py-1 rounded-full ${invoice.paymentStatus === 'PAID' ? 'bg-green-200 text-green-700' : 'bg-red-200 text-red-700'}">
                                ${invoice.paymentStatus}
                            </span>
                            <button type="button" data-id="${invoice.id}" class="view-btn text-green-500 hover:text-green-700 text-sm font-medium">View</button>
                            <button type="button" data-id="${invoice.id}" class="rename-btn text-purple-500 hover:text-purple-700 text-sm font-medium">Rename</button>
                            <button type="button" data-id="${invoice.id}" class="edit-btn text-blue-500 hover:text-blue-700 text-sm font-medium">Edit</button>
                            <button type="button" data-id="${invoice.dbId || invoice.id}" class="delete-btn text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                        </div>
                    `;
          savedInvoicesList.appendChild(listItem);
        });
      }

      async function loadInvoicesFromServer() {
        try {
          const response = await fetch('/school_list/core/list_invoices.php', {
            method: 'GET',
            headers: {
              'Accept': 'application/json'
            }
          });

          const text = await response.text();
          let result;

          // ⬇⬇⬇ এই অংশটা আপডেট
          try {
            result = JSON.parse(text);
          } catch (e) {
            console.error('Invalid JSON from server:', text);
            // raw output-এর প্রথম 200 character দেখাই
            showMessage('Server থেকে ইনভয়েস লোড করা যায়নি (raw): ' + text.slice(0, 200), 'error');
            return;
          }

          if (!response.ok || !result.success) {
            console.error('Server error:', result);
            // এখানে result.message থাকলে সেটা দেখাই
            showMessage('Server error: ' + (result.message || 'Server থেকে ইনভয়েস লোড করা যায়নি।'), 'error');
            return;
          }

          serverInvoices = result.invoices || [];
          renderInvoices();

        } catch (err) {
          console.error(err);
          showMessage('Server এর সাথে কানেকশন সমস্যা (ইনভয়েস লোড হয়নি): ' + err.message, 'error');
        }
      }

      async function deleteInvoiceFromServer(dbId) {
        try {
          const response = await fetch('core/delete_invoice.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              id: dbId
              // চাইলে এখানে reason ও পাঠাতে পারো
              // reason: 'User requested delete'
            })
          });

          const text = await response.text();
          let result;
          try {
            result = JSON.parse(text);
          } catch (e) {
            console.error('Invalid JSON from delete_invoice:', text);
            showMessage('Server থেকে ঠিকমতো response পাইনি (delete).', 'error');
            return;
          }

          if (!response.ok || !result.success) {
            console.error('Delete error:', result);
            showMessage(result.message || 'Invoice delete failed.', 'error');
            return;
          }

          // সফল হলে, আবার server থেকে fresh লিস্ট আনবো
          if (typeof loadInvoicesFromServer === 'function') {
            await loadInvoicesFromServer();
          }

          showMessage('Invoice deleted successfully!');

        } catch (err) {
          console.error(err);
          showMessage('Server এর সাথে সমস্যা, delete হয়নি: ' + err.message, 'error');
        }
      }

      async function saveInvoice() {
        const invoiceData = collectInvoiceData(form);
        if (!invoiceData) return;

        // Button loading UI
        saveBtnText.textContent = 'Saving...';
        saveBtn.disabled = true;
        saveIcon.classList.remove('fa-save');
        saveIcon.classList.add('fa-spinner', 'fa-spin');

        // আগের মতোই localStorage data নিয়ে নিলাম
        const invoices = getInvoices();

        try {
          // === ১) প্রথমে server (MySQL) এ পাঠাই ===
          const response = await fetch('core/save_invoice.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              id: currentInvoiceId,
              invoice: invoiceData
            })
          });

          // আগে text হিসেবে নিই
          const text = await response.text();

          let result;
          try {
            result = JSON.parse(text);
          } catch (e) {
            // JSON না হলে, raw response টাকেই error হিসেবে দেখাবো
            console.error('Raw server response:', text);
            throw new Error(text);
          }

          if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to save invoice on server');
          }


          const dbId = result.id; // invoices টেবিলের primary key

          // === ২) localStorage আপডেট ===
          if (currentInvoiceId) {
            const index = invoices.findIndex(inv => inv.id === currentInvoiceId);
            if (index > -1) {
              invoices[index] = { ...invoices[index], ...invoiceData, dbId };
            }
            showMessage("Invoice updated & synced with server!");
          } else {
            const newInvoice = {
              ...invoiceData,
              id: crypto.randomUUID(),
              dbId: dbId,
              createdAt: new Date().toISOString()
            };
            invoices.push(newInvoice);
            showMessage("Invoice saved & synced with server!");
          }

          // list + localStorage রিফ্রেশ
          saveInvoices(invoices);

          // চাইলে ফর্ম ক্লিয়ার করো
          // form.reset();
          // currentInvoiceId = null;
          setSaveButtonMode('new');

        } catch (error) {
          console.error(error);
          // Server fail করলেও অন্তত localStorage এ সেভ থাকবে
          showMessage("Server এ save হয়নি: " + error.message);


          if (currentInvoiceId) {
            const index = invoices.findIndex(inv => inv.id === currentInvoiceId);
            if (index > -1) {
              invoices[index] = { ...invoices[index], ...invoiceData };
            }
          } else {
            const newInvoice = {
              ...invoiceData,
              id: crypto.randomUUID(),
              createdAt: new Date().toISOString()
            };
            invoices.push(newInvoice);
          }

          saveInvoices(invoices);
        } finally {
          // Button UI reset
          saveBtnText.textContent = 'Save Invoice';
          saveBtn.disabled = false;
          saveIcon.classList.remove('fa-spinner', 'fa-spin');
          saveIcon.classList.add('fa-save');
        }
      }

      function collectInvoiceData(formElement) {
        const clientInstitution = formElement.querySelector('[id$="client-institution"]').value.trim();
        const clientName = formElement.querySelector('[id$="client-name"]').value.trim();
        const clientPhone = formElement.querySelector('[id$="client-phone"]').value.trim();
        const invoiceNumber = formElement.querySelector('[id$="invoice-number"]').value.trim();
        const invoiceDate = formElement.querySelector('[id$="invoice-date"]').value;
        const paymentStatus = formElement.querySelector('[id$="payment-status"]').value;
        const invoiceNote = formElement.querySelector('[id$="invoice-note"]').value;
        const invoiceStyle = formElement.querySelector('[id$="invoice-style"]').value;

        if (!clientInstitution || !clientName || !clientPhone || !invoiceNumber || !invoiceDate) {
          showMessage("Please fill in all the required fields.");
          return null;
        }

        const items = [];
        const itemsContainer = formElement.id === 'invoice-form' ? document.getElementById('items-container') : document.getElementById('edit-items-container');
        itemsContainer.querySelectorAll('.item-row').forEach(row => {
          const description = row.querySelector('[name="description"]').value;
          const quantity = row.querySelector('[name="quantity"]').value;
          const amount = parseFloat(row.querySelector('[name="amount"]').value) || 0;
          const total = parseFloat(row.querySelector('[name="total"]').value) || 0;
          const calculated = row.dataset.calculated;
          items.push({ description, quantity, amount, total, calculated });
        });

        const invoiceName = clientInstitution;

        return {
          clientInstitution,
          clientName,
          clientPhone,
          invoiceNumber,
          invoiceDate,
          paymentStatus,
          invoiceNote,
          invoiceName,
          invoiceStyle,
          items: JSON.stringify(items),
          updatedAt: new Date().toISOString()
        };
      }

      // --- Event Listeners ---
      addItemBtn.addEventListener('click', () => addInvoiceItem(itemsContainer));
      editAddItemBtn.addEventListener('click', () => addInvoiceItem(editItemsContainer));

      clearFormBtn.addEventListener('click', clearForm);
      saveBtn.addEventListener('click', saveInvoice);

      savedInvoicesList.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.edit-btn');
        const deleteBtn = e.target.closest('.delete-btn');
        const renameBtn = e.target.closest('.rename-btn');
        const viewBtn = e.target.closest('.view-btn');

        const invoices = getInvoices();

        if (editBtn) {
          const invoiceId = editBtn.dataset.id;
          const invoice = invoices.find(inv => inv.id === invoiceId);
          if (invoice) {
            currentInvoiceId = invoiceId;
            currentDbId = invoice.dbId || invoiceId;
            setSaveButtonMode('edit');
            document.getElementById('edit-client-institution').value = invoice.clientInstitution;
            document.getElementById('edit-client-name').value = invoice.clientName;
            document.getElementById('edit-client-phone').value = invoice.clientPhone;
            document.getElementById('edit-invoice-number').value = invoice.invoiceNumber;
            document.getElementById('edit-invoice-date').value = invoice.invoiceDate;
            document.getElementById('edit-payment-status').value = invoice.paymentStatus;
            document.getElementById('edit-invoice-note').value = invoice.invoiceNote;
            document.getElementById('edit-invoice-style').value = invoice.invoiceStyle;

            editItemsContainer.innerHTML = '';
            const items = JSON.parse(invoice.items);
            items.forEach(item => addInvoiceItem(editItemsContainer, item));

            editModal.classList.remove('hidden');
          }
        } else if (deleteBtn) {
          const invoiceId = deleteBtn.dataset.id;

          showConfirmation("Are you sure you want to permanently delete this invoice?", () => {
            // delete request form server
            deleteInvoiceFromServer(invoiceId);
            showMessage("Invoice deleted successfully!");
          });
        } else if (renameBtn) {
          const invoiceId = renameBtn.dataset.id;
          const invoice = invoices.find(inv => inv.id === invoiceId);
          if (invoice) {
            openRenameModal(invoice.id, invoice.invoiceName || `${invoice.invoiceNumber} - ${invoice.clientName}`);
          }
        } else if (viewBtn) {
          const invoiceId = viewBtn.dataset.id;
          const invoice = invoices.find(inv => inv.id === invoiceId);
          if (invoice) {
            // Populate main form with invoice data
            document.getElementById('client-institution').value = invoice.clientInstitution;
            document.getElementById('client-name').value = invoice.clientName;
            document.getElementById('client-phone').value = invoice.clientPhone;
            document.getElementById('invoice-number').value = invoice.invoiceNumber;
            document.getElementById('invoice-date').value = invoice.invoiceDate;
            document.getElementById('payment-status').value = invoice.paymentStatus;
            document.getElementById('invoice-note').value = invoice.invoiceNote;
            document.getElementById('invoice-style').value = invoice.invoiceStyle;

            itemsContainer.innerHTML = '';
            const items = JSON.parse(invoice.items);
            items.forEach(item => addInvoiceItem(itemsContainer, item));

            // Open the view modal
            openViewModal(invoice.invoiceStyle);
          }
        }
      });

      saveEditBtn.addEventListener('click', async () => {
        const editForm = document.getElementById('edit-invoice-form');
        const invoiceData = collectInvoiceData(editForm);
        if (!invoiceData) return;

        // যদি currentDbId ব্যবহার করো
        const dbId = typeof currentDbId !== 'undefined' && currentDbId ? currentDbId : currentInvoiceId;

        if (!dbId) {
          showMessage('Error: Invoice ID missing for update.', 'error');
          return;
        }

        // বাটন লোডিং ইচ্ছা করলে করতে পারো
        saveEditBtn.disabled = true;

        try {
          const response = await fetch('core/update_invoice.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              id: dbId,          // DB এর invoices.id
              invoice: invoiceData
            })
          });

          const text = await response.text();
          let result;
          try {
            result = JSON.parse(text);
          } catch (e) {
            console.error('Invalid JSON from update_invoice:', text);
            showMessage('Server থেকে ঠিকমতো response পাইনি (update).', 'error');
            return;
          }

          if (!response.ok || !result.success) {
            console.error('Update error:', result);
            showMessage(result.message || 'Invoice update failed.', 'error');
            return;
          }

          // সফল হলে: চাইলে লোকাল array update করে আবার list রিফ্রেশ করতে পারো
          if (typeof loadInvoicesFromServer === 'function') {
            await loadInvoicesFromServer();   // DB থেকে ফ্রেশ লিস্ট আনবে
          } else {
            // fallback: শুধু লোকাল array update
            const invoices = getInvoices();
            const index = invoices.findIndex(inv => (inv.dbId || inv.id) == dbId);
            if (index > -1) {
              invoices[index] = { ...invoices[index], ...invoiceData };
              saveInvoices(invoices);
            }
          }

          showMessage('Invoice updated successfully!');
          closeEditModal();

        } catch (err) {
          console.error(err);
          showMessage('Server এর সাথে সমস্যা, update হয়নি: ' + err.message, 'error');
        } finally {
          saveEditBtn.disabled = false;
        }
      });

      closeEditBtn.addEventListener('click', closeEditModal);

      renameSaveBtn.addEventListener('click', () => {
        const newName = renameInput.value.trim();
        if (newName) {
          const invoices = getInvoices();
          const index = invoices.findIndex(inv => inv.id === currentInvoiceId);
          if (index > -1) {
            invoices[index].invoiceName = newName;
            saveInvoices(invoices);
            showMessage("Invoice renamed successfully!");
          }
        } else {
          showMessage("Please enter a new name.");
        }
        closeRenameModal();
      });

      renameCancelBtn.addEventListener('click', closeRenameModal);

      itemsContainer.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.remove-item-btn');
        if (removeBtn) {
          if (itemsContainer.children.length > 1) {
            rowToDelete = removeBtn.closest('.item-row');
            showConfirmation("Are you sure you want to delete this item?", () => {
              if (rowToDelete) {
                rowToDelete.remove();
                updateTotalsAndNote();
                rowToDelete = null;
              }
            });
          } else {
            showMessage("At least one item is required.");
          }
        }

        const calcBtn = e.target.closest('.calc-item-btn');
        if (calcBtn) {
          const row = calcBtn.closest('.item-row');
          const totalInput = row.querySelector('[name="total"]');
          if (row.dataset.calculated === 'true') {
            totalInput.value = '';
            row.dataset.calculated = 'false';
            calcBtn.classList.remove('bg-green-500', 'hover:bg-green-600');
            calcBtn.classList.add('bg-indigo-500', 'hover:bg-indigo-600');
          } else {
            const quantityString = row.querySelector('[name="quantity"]').value;
            const quantityMatch = quantityString.match(/[\d\.]+/);
            const quantity = quantityMatch ? parseFloat(quantityMatch[0]) : 0;
            const amount = parseFloat(row.querySelector('[name="amount"]').value) || 0;
            totalInput.value = (quantity === 0 ? amount : quantity * amount).toFixed(2);
            row.dataset.calculated = 'true';
            calcBtn.classList.remove('bg-indigo-500', 'hover:bg-indigo-600');
            calcBtn.classList.add('bg-green-500', 'hover:bg-green-600');
          }
          updateTotalsAndNote();
        }
      });

      editItemsContainer.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.remove-item-btn');
        if (removeBtn) {
          if (editItemsContainer.children.length > 1) {
            rowToDelete = removeBtn.closest('.item-row');
            showConfirmation("Are you sure you want to delete this item?", () => {
              if (rowToDelete) {
                rowToDelete.remove();
                // updateTotalsAndNote(); // This function is for the main form, need to create one for the edit form
                rowToDelete = null;
              }
            });
          } else {
            showMessage("At least one item is required.");
          }
        }

        const calcBtn = e.target.closest('.calc-item-btn');
        if (calcBtn) {
          const row = calcBtn.closest('.item-row');
          const totalInput = row.querySelector('[name="total"]');
          if (row.dataset.calculated === 'true') {
            totalInput.value = '';
            row.dataset.calculated = 'false';
            calcBtn.classList.remove('bg-green-500', 'hover:bg-green-600');
            calcBtn.classList.add('bg-indigo-500', 'hover:bg-indigo-600');
          } else {
            const quantityString = row.querySelector('[name="quantity"]').value;
            const quantityMatch = quantityString.match(/[\d\.]+/);
            const quantity = quantityMatch ? parseFloat(quantityMatch[0]) : 0;
            const amount = parseFloat(row.querySelector('[name="amount"]').value) || 0;
            totalInput.value = (quantity === 0 ? amount : quantity * amount).toFixed(2);
            row.dataset.calculated = 'true';
            calcBtn.classList.remove('bg-indigo-500', 'hover:bg-indigo-600');
            calcBtn.classList.add('bg-green-500', 'hover:bg-green-600');
          }
          // updateTotalsAndNote(); // This function is for the main form, need to create one for the edit form
        }
      });

      editItemsContainer.addEventListener('keyup', function (e) {
        if (e.target.name === 'total') {
          const row = e.target.closest('.item-row');
          row.dataset.calculated = 'false';
          const calcBtn = row.querySelector('.calc-item-btn');
          calcBtn.classList.remove('bg-green-500', 'hover:bg-green-600');
          calcBtn.classList.add('bg-indigo-500', 'hover:bg-indigo-600');
          // updateTotalsAndNote(); // This function is for the main form, need to create one for the edit form
        }
      });

      function numberToWords(num) {
        if (num < 0) return "Invalid Amount";
        if (num === 0) return 'Zero';

        const integerPart = Math.floor(num);
        const fractionalPart = Math.round((num - integerPart) * 100);

        let words = '';
        const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
        const teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        const scales = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];

        function convertLessThanOneThousand(n) {
          let currentWords = '';
          if (n >= 100) {
            currentWords += ones[Math.floor(n / 100)] + ' Hundred ';
            n %= 100;
          }
          if (n >= 20) {
            currentWords += tens[Math.floor(n / 10)] + ' ';
            n %= 10;
          }
          if (n >= 10) {
            currentWords += teens[n - 10] + ' ';
            n = 0;
          }
          if (n > 0) {
            currentWords += ones[n] + ' ';
          }
          return currentWords;
        }

        if (integerPart > 0) {
          let tempNum = integerPart;
          let scaleIndex = 0;
          let tempWords = '';
          while (tempNum > 0) {
            if (tempNum % 1000 !== 0) {
              tempWords = convertLessThanOneThousand(tempNum % 1000) + scales[scaleIndex] + ' ' + tempWords;
            }
            tempNum = Math.floor(tempNum / 1000);
            scaleIndex++;
          }
          words = tempWords.trim();
        }

        if (fractionalPart > 0) {
          if (integerPart > 0) words += ' and ';
          words += convertLessThanOneThousand(fractionalPart).trim() + ' Paisa';
        }

        return words.trim() + ' Taka Only.';
      }


      function generateInvoiceHTML(style) {
        const activeStyle = styles[style] || styles.classic;

        const clientInstitution = document.getElementById('client-institution').value;
        const clientName = document.getElementById('client-name').value;
        const clientPhone = document.getElementById('client-phone').value;
        const invoiceNote = document.getElementById('invoice-note').value;
        const invoiceNumber = document.getElementById('invoice-number').value;
        const invoiceDate = new Date(document.getElementById('invoice-date').value).toLocaleDateString('en-GB');
        const paymentStatus = document.getElementById('payment-status').value;

        let signatureHtml = '';
        if (paymentStatus === 'PAID') {
          signatureHtml = activeStyle.sigBlock;
        }

        let itemsHTML = '';
        let subtotal = 0;
        document.querySelectorAll('.item-row').forEach((row, index) => {
          const description = row.querySelector('[name="description"]').value;
          const quantity = row.querySelector('[name="quantity"]').value;
          const amount = parseFloat(row.querySelector('[name="amount"]').value) || 0;
          const total = parseFloat(row.querySelector('[name="total"]').value) || 0;
          subtotal += total;
          itemsHTML += `
                        <tr class="text-center bg-white text-base">
                            <td class="border border-black px-3 py-2 font-bold align-top text-center">#${index + 1}</td>
                            <td class="border border-black px-3 py-2 text-center align-top">${description}</td>
                            <td class="border border-black px-3 py-2 align-top text-center">${quantity}</td>
                            <td class="border border-black px-3 py-2 align-top text-center">Tk ${amount.toFixed(2)}</td>
                            <td class="border border-black px-3 py-2 align-top text-center">Tk ${total.toFixed(2)}</td>
                        </tr>
                    `;
        });

        // Ensure at least 8 rows are displayed for visual consistency
        const emptyRowsNeeded = 8 - document.querySelectorAll('.item-row').length;
        if (emptyRowsNeeded > 0) {
          for (let i = 0; i < emptyRowsNeeded; i++) {
            // Changed py-6 to py-5 for slightly less height in filler rows
            itemsHTML += `<tr class="text-center bg-gray-50 text-base"><td class="border border-black px-3 py-5"></td><td class="border border-black px-3 py-5"></td><td class="border border-black px-3 py-5"></td><td class="border border-black px-3 py-5"></td><td class="border border-black px-3 py-5"></td></tr>`;
          }
        }

        const statusBadge = paymentStatus === 'PAID'
          ? `<span class="${activeStyle.statusPaid}">PAID</span>`
          : `<span class="${activeStyle.statusUnpaid}">UNPAID</span>`;

        const dueAmount = paymentStatus === 'UNPAID' ? 'Tk ' + subtotal.toFixed(2) : '------';

        // New Note/Total Layout structure: Flex container with Note on left (w-3/5) and Totals on right (w-2/5)
        // Note text alignment changed from text-center to text-left
        const noteAndTotalLayout = `
                    <div class="flex mt-6 justify-between items-start">
                        <!-- Note Section (Left Side, text is now left-aligned within its container) -->
                        <div class="w-3/5 pr-4">
                            <p class="text-base font-medium text-gray-700 font-bold text-left border-t border-black pt-2">Note: ${invoiceNote}</p>
                        </div>
                        
                        <!-- Totals Section (Right Side) -->
                        <div class="w-2/5">
                            <div class="flex justify-between border border-black px-3 py-2">
                                <span class="font-semibold">Subtotal</span>
                                <span>Tk ${subtotal.toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between border border-black px-3 py-2 ${activeStyle.dueBg}">
                                <span class="font-semibold">Due</span>
                                <span>${dueAmount}</span>
                            </div>
                            <div class="flex justify-between border border-black px-3 py-2 text-white font-bold" style="${activeStyle.totalBg}">
                                <span>TOTAL</span>
                                <span>Tk ${subtotal.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                `;


        if (style === 'minimalist') {
          return `
                        <div class="invoice-container bg-white shadow-xl rounded-lg border border-gray-300 text-gray-800" style="width: 210mm; height: 297mm; display: flex; flex-direction: column; box-sizing: border-box;">
                            <div class="${activeStyle.headerBg} p-6">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-3">
                                        <img src="${activeStyle.logoUrl}" alt="${activeStyle.logoAlt}" style="height: 70px; width: auto;">
                                        <div>
                                            <h1 class="text-xl font-bold">EduRLab</h1>
                                            <p class="text-sm text-gray-400">${companyInfo.slogan}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <h2 class="text-3xl font-bold text-gray-200">INVOICE</h2>
                                        <p class="text-base text-gray-400">Date: ${invoiceDate}</p>
                                        <p class="text-base text-gray-400">Invoice: ${invoiceNumber}</p>
                                    </div>
                                </div>
                            </div>
                            <!-- Adjusted padding from p-6 to p-5 for slightly more vertical space -->
                            <div class="p-5" style="flex-grow: 1;">
                                <div class="mt-4 pb-6 border-b border-gray-300">
                                    <h3 class="text-lg font-semibold text-gray-700">Bill To</h3>
                                    <!-- Increased text-sm to text-base for client details -->
                                    <div class="mt-3 text-base space-y-1 text-gray-600">
                                        <p><span class="font-semibold text-gray-800">Client Name:</span> ${clientName}</p>
                                        <p><span class="font-semibold text-gray-800">Institution Name:</span> ${clientInstitution}</p>
                                        <p><span class="font-semibold text-gray-800">Phone Number:</span> ${clientPhone}</p>
                                    </div>
                                </div>
                                <div class="mt-6">
                                    <!-- Increased table text size to text-base -->
                                    <table class="w-full border-collapse text-base">
                                        <thead class="${activeStyle.tableHeadBg}">
                                            <tr>
                                                <th class="border border-gray-500 px-3 py-2 text-center">Item #</th>
                                                <th class="border border-gray-500 px-3 py-2 text-center">Description</th>
                                                <th class="border border-gray-500 px-3 py-2 text-center">Quantity</th>
                                                <th class="border border-gray-500 px-3 py-2 text-center">Amount</th>
                                                <th class="border border-gray-500 px-3 py-2 text-center">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>${itemsHTML}</tbody>
                                    </table>
                                </div>

                                <!-- Note and Total Section using the new layout -->
                                ${noteAndTotalLayout.replace(/border-black/g, 'border-gray-300').replace(/text-center/g, 'text-left')} 
                                
                                <div class="mt-6 flex justify-end">
                                    ${statusBadge}
                                </div>
                            </div>
                            <!-- Adjusted footer padding from p-6 to p-5 -->
                            <footer class="mt-auto p-5" style="width: 100%;">
                            <div>
                                        ${signatureHtml}
                                    </div>
                                <div class="">
                                    
                                    <div class="text-right text-base text-gray-600">
                                        <p class="font-bold text-lg">bkash & Nagat 01805-123649</p>
                                        <a href="https://www.edurlab.com" class="text-gray-800 font-bold text-lg">www.edurlab.com</a>
                                    </div>
                                </div>
                                <p class="mt-4 text-base text-gray-600 text-center">
                                    ${companyInfo.slogan}
                                </p>
                            </footer>
                        </div>
                    `;
        } else { // Classic style is the default
          return `
                        <div class="invoice-container bg-white shadow-xl rounded-lg border border-black" style="width: 210mm; height: 297mm; display: flex; flex-direction: column; box-sizing: border-box;">
                            <div class="h-2 ${activeStyle.headerBg}"></div>
                            <!-- Adjusted padding from p-6 to p-5 for slightly more vertical space -->
                            <div class="p-5" style="flex-grow: 1;">
                                <div class="flex justify-between items-start pb-6 border-b border-black">
                                    <!-- Company Info & Client Info -->
                                    <div>
                                        <div class="flex items-center gap-3">
                                            <img src="${activeStyle.logoUrl}" alt="${activeStyle.logoAlt}" style="height: 70px; width: auto;">
                                        </div>
                                        <!-- Client Details already text-base -->
                                        <div class="mt-4 text-base space-y-1 text-gray-800">
                                            <p><span class="font-bold">Client Name:</span> ${clientName}</p>
                                            <p><span class="font-bold">Institution Name:</span> ${clientInstitution}</p>
                                            <p><span class="font-bold">Phone Number:</span> ${clientPhone}</p>
                                        </div>
                                    </div>
                                    <!-- Invoice Details -->
                                    <div class="text-right">
                                        <h2 class="text-3xl font-bold text-green-600">INVOICE</h2>
                                        <p class="text-base text-gray-600">Date: ${invoiceDate}</p>
                                        <p class="text-base text-gray-600">Invoice: ${invoiceNumber}</p>
                                    </div>
                                </div>
                                <div class="mt-6">
                                    <!-- Increased table text size to text-base -->
                                    <table class="w-full border-collapse text-base">
                                        <thead class="${activeStyle.tableHeadBg}">
                                            <tr>
                                                <th class="border border-black px-3 py-2 text-center">Item #</th>
                                                <th class="border border-black px-3 py-2 text-center">Description</th>
                                                <th class="border border-black px-3 py-2 text-center">Quantity</th>
                                                <th class="border border-black px-3 py-2 text-center">Amount</th>
                                                <th class="border border-black px-3 py-2 text-center">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>${itemsHTML}</tbody>
                                    </table>
                                </div>
                                
                                <!-- Note and Total Section using the new layout -->
                                ${noteAndTotalLayout.replace(/text-center/g, 'text-left')}
                                
                                <div class="mt-6 flex justify-end">
                                    ${statusBadge}
                                </div>
                            </div>
                            <!-- Adjusted footer padding from p-6 to p-5 -->
                            <footer class="mt-auto p-5" style="width: 100%;">
                               <div class="flex justify-between">
                                    <div>
                                        ${signatureHtml}
                                    </div>
                                    <div class="text-right text-base mt-5">
                                        <p class="font-bold text-lg">bkash & Nagad 01805-123649</p>
                                        <a href="https://www.edurlab.com" class="text-green-600 font-bold text-lg">www.edurlab.com</a>
                                    </div>
                                </div>
                               <p class="mt-4 text-base text-gray-600 text-center">
                                    ${companyInfo.slogan}
                               </p>
                            </footer>
                        </div>
                    `;
        }
      }

      function printAsImage() {
        const selectedStyle = document.getElementById('invoice-style').value;
        const invoiceHTML = generateInvoiceHTML(selectedStyle);
        const tempContainer = document.createElement('div');
        tempContainer.classList.add('render-area');
        tempContainer.innerHTML = invoiceHTML;
        document.body.appendChild(tempContainer);

        const invoiceElement = tempContainer.querySelector('.invoice-container');

        printBtnText.textContent = 'Preparing...';
        printBtn.disabled = true;
        printIcon.classList.remove('fa-print');
        printIcon.classList.add('fa-spinner', 'fa-spin');

        html2canvas(invoiceElement, { scale: 3, useCORS: true, logging: true }).then(canvas => {
          const imgData = canvas.toDataURL('image/png');

          const printImage = new Image();
          printImage.src = imgData;

          printImage.onload = function () {
            printArea.innerHTML = '';
            printArea.appendChild(printImage);
            window.print();
            printArea.innerHTML = '';
            document.body.removeChild(tempContainer);

            printBtnText.textContent = 'Print';
            printBtn.disabled = false;
            printIcon.classList.remove('fa-spinner', 'fa-spin');
            printIcon.classList.add('fa-print');
          };
        });
      }


      async function loadNextInvoiceNumber() {
        // Edit মোডে থাকলে auto-override করব না
        if (currentInvoiceId) return;

        const input = document.getElementById('invoice-number');
        if (!input) return;

        // ইউজার যদি আগে থেকেই কিছু লিখে ফেলে, সেটা ওভাররাইট করব না
        if (input.value && input.value.trim() !== '') return;

        try {
          const response = await fetch('core/next_invoice_number.php', {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
          });

          const text = await response.text();
          let result;

          try {
            result = JSON.parse(text);
          } catch (e) {
            console.error('Invalid JSON from next_invoice_number:', text);
            return;
          }

          if (!response.ok || !result.success) {
            console.error('Next invoice error:', result);
            return;
          }

          input.value = result.next_invoice;

        } catch (err) {
          console.error('Next invoice fetch error:', err);
        }
      }


      function openViewModal(style) {
        const invoiceData = collectInvoiceData(form);
        if (!invoiceData) {
          return; // Don't open modal if form is incomplete
        }
        viewScaler.innerHTML = generateInvoiceHTML(style);
        const invoiceElement = viewScaler.querySelector('.invoice-container');
        viewModal.classList.remove('hidden');
        requestAnimationFrame(() => {
          const container = viewContainer;
          const el = invoiceElement;
          const scale = Math.min(
            (container.clientWidth / el.offsetWidth),
            (container.clientHeight / el.offsetHeight)
          ) * 0.95;
          el.style.transform = `scale(${scale})`;
        });
      }

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        const invoiceData = collectInvoiceData(form);
        if (!invoiceData) return;

        printBtnText.textContent = 'Generating...';
        printBtn.disabled = true;
        printIcon.classList.remove('fa-print');
        printIcon.classList.add('fa-spinner', 'fa-spin');

        // Using a delay to simulate a complex generation process
        setTimeout(() => {
          printAsImage();
        }, 100); // Reduced delay for better UX
      });

      closeViewBtn.addEventListener('click', function () {
        viewModal.classList.add('hidden');
        viewScaler.innerHTML = '';
      });

      confirmNoBtn.addEventListener('click', () => {
        confirmModal.classList.add('hidden');
        rowToDelete = null;
      });

      confirmYesBtn.addEventListener('click', () => {
        if (rowToDelete) {
          rowToDelete.remove();
          // updateTotalsAndNote(); // This function is for the main form, need to create one for the edit form
          rowToDelete = null;
        }
        confirmModal.classList.add('hidden');
        rowToDelete = null;
      });

      messageOkBtn.addEventListener('click', () => {
        messageModal.classList.add('hidden');
      });

      viewInvoiceBtn.addEventListener('click', () => {
        const selectedStyle = document.getElementById('invoice-style').value;
        openViewModal(selectedStyle);
      });

      modalDownloadImgBtn.addEventListener('click', function () {
        imgBtnText.textContent = 'Generating...';
        modalDownloadImgBtn.disabled = true;
        imgIcon.classList.remove('fa-download');
        imgIcon.classList.add('fa-spinner', 'fa-spin');

        // Using a delay to simulate a complex generation process
        setTimeout(() => {
          const invoiceToCapture = viewScaler.querySelector('.invoice-container');
          if (invoiceToCapture) {
            html2canvas(invoiceToCapture, { backgroundColor: '#ffffff', scale: 2, useCORS: true }).then(canvas => {
              const link = document.createElement('a');
              link.href = canvas.toDataURL('image/png');
              link.download = `invoice-${document.getElementById('invoice-number').value || 'download'}.png`;
              link.click();
            }).finally(() => {
              imgBtnText.textContent = 'Download as Image';
              modalDownloadImgBtn.disabled = false;
              imgIcon.classList.remove('fa-spinner', 'fa-spin');
              imgIcon.classList.add('fa-download');
            });
          }
        }, 100); // Reduced delay for better UX
      });

      downloadPdfBtn.addEventListener('click', function () {
        // Collect and validate form data
        const invoiceData = collectInvoiceData(form);
        if (!invoiceData) return;

        // Set loading state
        pdfBtnText.textContent = 'Generating...';
        downloadPdfBtn.disabled = true;
        pdfIcon.classList.remove('fa-file-pdf');
        pdfIcon.classList.add('fa-spinner', 'fa-spin');

        // Using a delay to simulate a complex generation process
        setTimeout(() => {
          const selectedStyle = document.getElementById('invoice-style').value;
          const invoiceElement = document.createElement('div');
          invoiceElement.innerHTML = generateInvoiceHTML(selectedStyle);

          // Use html2pdf
          html2pdf().set({
            margin: 0,
            filename: `invoice-${document.getElementById('invoice-number').value || 'download'}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, logging: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
          }).from(invoiceElement).save().finally(() => {
            pdfBtnText.textContent = 'Download PDF';
            downloadPdfBtn.disabled = false;
            pdfIcon.classList.remove('fa-spinner', 'fa-spin');
            pdfIcon.classList.add('fa-file-pdf');
          });
        }, 100); // Reduced delay for better UX
      });

      document.getElementById('payment-status').addEventListener('change', () => {
        const isModalOpen = !viewModal.classList.contains('hidden');
        if (isModalOpen) {
          const selectedStyle = document.getElementById('invoice-style').value;
          openViewModal(selectedStyle);
        }
      });

      setSaveButtonMode('new');
      // Initial setup
      clearForm();
      // renderInvoices(); localStorage
      loadInvoicesFromServer(); //server

      loadNextInvoiceNumber();
    });
  </script>

  <?php include '../layout/layout_footer.php'; ?>