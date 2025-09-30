<?php
require_once 'db.php';

// CRITICAL: Check authentication and session timeout BEFORE any output
requireLogin();

// Your existing collections logic here...
// Add all your current collections management code after this point
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Collections Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/style.css">
  <style>
    .money { text-align:right; }
    .small-muted { font-size:.9rem; color:#6c757d; }
    .summary-card {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
      padding: 0.5rem 0.75rem;
      text-align: center;
      min-width: 100px;
      max-width: 120px;
    }
    .summary-label {
      font-size: 0.75rem;
      color: #6c757d;
      font-weight: 500;
      margin-bottom: 0.25rem;
      line-height: 1;
    }
    .summary-value {
      font-size: 0.875rem;
      font-weight: 600;
      line-height: 1;
    }
    .filter-section {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
      padding: 1rem;
      margin-bottom: 1rem;
    }
  </style>
</head>
<body>

<?php include 'sidebar_navbar.php'; ?>

<div class="main-content">
  <div class="container-fluid mt-4 px-4">
    <h2 class="fw-bold mb-4">Collections Management</h2>

    <!-- Filter Section -->
    <div class="filter-section">
      <h6 class="mb-3 text-muted">Filter Collections</h6>
      <div class="row g-3 align-items-end">
        <!-- Date Range Filters -->
        <div class="col-md-3">
          <label class="form-label small">From Date</label>
          <input type="date" class="form-control" id="dateFrom" placeholder="Start Date">
        </div>
        <div class="col-md-3">
          <label class="form-label small">To Date</label>
          <input type="date" class="form-control" id="dateTo" placeholder="End Date">
        </div>
        
        <!-- Payment Status Filter -->
        <div class="col-md-3">
          <label class="form-label small">Payment Status</label>
          <select class="form-select" id="paymentStatusFilter">
            <option value="">All Status</option>
            <option value="Unpaid">Unpaid</option>
            <option value="Partial">Partial</option>
            <option value="Paid">Paid</option>
          </select>
        </div>
        
        <!-- Filter Actions -->
        <div class="col-md-3">
          <div class="d-flex gap-2">
            <button id="filterBtn" class="btn btn-primary flex-fill">Filter</button>
            <button id="resetFilterBtn" class="btn btn-outline-secondary flex-fill">Reset</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions and Summary Cards -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
      <!-- Left side: Summary Cards -->
      <div class="d-flex gap-2">
        <div class="summary-card">
          <div class="summary-label">Total Collected</div>
          <div id="card_total_collected" class="summary-value text-success">₱0.00</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Pending</div>
          <div id="card_pending" class="summary-value text-warning">₱0.00</div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Overdue</div>
          <div id="card_overdue" class="summary-value text-danger">₱0.00</div>
        </div>
      </div>

      <!-- Right side: Add Collection button -->
      <div class="d-flex align-items-center">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCollectionModal">+ Add Collection</button>
      </div>
    </div>

    <!-- Table -->
    <div class="table-responsive shadow-sm rounded">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Client Name</th>
            <th>Job Order/Invoice</th>
            <th>Billing Date</th>
            <th>Due Date</th>
            <th class="text-end">Amount Due</th>
            <th class="text-end">Amount Paid</th>
            <th class="text-end">Penalty</th>
            <th>Status</th>
            <th>Receipt Type</th>
            <th>Collector</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="collections_tbody">
          <!-- dynamic rows inserted here -->
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <nav aria-label="Collections Pagination" class="mt-4">
      <ul class="pagination justify-content-center">
        <!-- Pagination will be generated by JavaScript -->
      </ul>
    </nav>
  </div>
</div>

<?php include 'financial_collections_modals.php'; ?>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Pagination Script -->
<script src="financial_collections_pagination.js"></script>

<script>
// Helpers
const peso = (n) => '₱' + (Number(n)||0).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2});
const qs = (s, el=document) => el.querySelector(s);
const qsa = (s, el=document) => [...el.querySelectorAll(s)];

// Penalty calculation function
function calculatePenalty(dueDate, paymentStatus, amountDue, amountPaid) {
  // Don't apply penalty if already paid
  if (paymentStatus === 'Paid') {
    return 0;
  }
  
  const today = new Date();
  const due = new Date(dueDate);
  
  // Don't apply penalty if not past due date
  if (due >= today) {
    return 0;
  }
  
  // Calculate days past due
  const timeDiff = today.getTime() - due.getTime();
  const daysPastDue = Math.ceil(timeDiff / (1000 * 3600 * 24));
  
  // Calculate remaining unpaid amount
  const remainingUnpaid = Math.max(0, amountDue - amountPaid);
  
  // If nothing is unpaid, no penalty
  if (remainingUnpaid <= 0) {
    return 0;
  }
  
  // Apply penalty: Fixed 850 + (remaining unpaid × 0.10% × days past due)
  const basePenalty = 850;
  const dailyPenaltyRate = 0.001; // 0.10%
  const dailyPenalty = remainingUnpaid * dailyPenaltyRate * daysPastDue;
  
  const totalPenalty = basePenalty + dailyPenalty;
  
  return Math.round(totalPenalty * 100) / 100; // Round to 2 decimal places
}

// Load table data (updated to use pagination with filters)
async function loadCollections(filters = {}) {
  // Use the pagination system with filters
  await collectionsPagination.applyFilters(filters);
}

function statusBadge(s) {
  if (s === 'Paid') return `<span class="badge bg-success">Paid</span>`;
  if (s === 'Partial') return `<span class="badge bg-warning text-dark">Partial</span>`;
  return `<span class="badge bg-danger">Unpaid</span>`;
}

function escapeHtml(str='') {
  return (str+'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

// Filter functionality
qs('#filterBtn').addEventListener('click', () => {
  const dateFrom = qs('#dateFrom').value.trim();
  const dateTo = qs('#dateTo').value.trim();
  const paymentStatus = qs('#paymentStatusFilter').value.trim();
  
  // Always pass all filter values, even if empty
  const filters = {
    date_from: dateFrom,
    date_to: dateTo,
    payment_status: paymentStatus
  };
  
  collectionsPagination.applyFilters(filters);
});

qs('#resetFilterBtn').addEventListener('click', () => { 
  qs('#dateFrom').value = '';
  qs('#dateTo').value = '';
  qs('#paymentStatusFilter').value = '';
  collectionsPagination.resetFilters();
});

// Enhanced View function with attachment display
async function openView(id) {
  const res = await fetch('collections_action.php?action=get&id='+id);
  const row = await res.json();
  const detailsContainer = qs('#collection_details');
  const attachmentContainer = qs('#attachment_content');

  // Fill collection details (left side)
  detailsContainer.innerHTML = `
    <p><strong>Client:</strong> ${escapeHtml(row.client_name)}</p>
    <p><strong>Invoice:</strong> ${escapeHtml(row.invoice_no)}</p>
    <p><strong>Billing Date:</strong> ${row.billing_date}</p>
    <p><strong>Due Date:</strong> ${row.due_date}</p>
    <p><strong>Amount (Base):</strong> ${peso(row.amount_base)}</p>
    <p><strong>VAT Applied:</strong> ${row.vat_applied} (${Number(row.vat_rate)}%)</p>
    <p><strong>VAT Amount:</strong> ${peso(row.vat_amount)}</p>
    <p><strong>Amount Due (Total):</strong> <strong>${peso(row.amount_due)}</strong></p>
    <p><strong>Amount Paid:</strong> ${peso(row.amount_paid)}</p>
    <p><strong>Penalty:</strong> ${peso(row.penalty)}</p>
    <p><strong>Status:</strong> ${escapeHtml(row.payment_status)}</p>
    <p><strong>Receipt Type:</strong> ${escapeHtml(row.receipt_type)}</p>
    <p><strong>Collector:</strong> ${escapeHtml(row.collector_name)}</p>
  `;

  // Handle attachment display (right side)
  if (row.receipt_attachment) {
    const fileExtension = row.receipt_attachment.split('.').pop().toLowerCase();
    const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension);
    const isPdf = fileExtension === 'pdf';

    if (isImage) {
      attachmentContainer.innerHTML = `
        <div class="text-center">
          <img src="invoice_uploads/${row.receipt_attachment}" 
               alt="Receipt" 
               class="img-fluid rounded shadow-sm"
               style="max-height: 400px; cursor: pointer;"
               onclick="openImageModal('invoice_uploads/${row.receipt_attachment}')">
          <div class="mt-2">
            <small class="text-muted">${row.receipt_attachment}</small><br>
            <a href="invoice_uploads/${row.receipt_attachment}" 
               target="_blank" 
               class="btn btn-sm btn-outline-primary mt-1">
              <i class="bi bi-download"></i> Download
            </a>
          </div>
        </div>
      `;
    } else if (isPdf) {
      attachmentContainer.innerHTML = `
        <div class="text-center">
          <div class="pdf-placeholder p-4 border rounded bg-white shadow-sm">
            <i class="bi bi-file-earmark-pdf-fill text-danger" style="font-size: 3rem;"></i>
            <h6 class="mt-2">PDF Document</h6>
            <small class="text-muted">${row.receipt_attachment}</small>
          </div>
          <div class="mt-3">
            <a href="invoice_uploads/${row.receipt_attachment}" 
               target="_blank" 
               class="btn btn-sm btn-primary me-2">
              <i class="bi bi-eye"></i> View PDF
            </a>
            <a href="invoice_uploads/${row.receipt_attachment}" 
               download 
               class="btn btn-sm btn-outline-primary">
              <i class="bi bi-download"></i> Download
            </a>
          </div>
        </div>
      `;
    } else {
      attachmentContainer.innerHTML = `
        <div class="text-center">
          <div class="file-placeholder p-4 border rounded bg-light">
            <i class="bi bi-file-earmark text-primary" style="font-size: 3rem;"></i>
            <h6 class="mt-2">File Attachment</h6>
            <small class="text-muted">${row.receipt_attachment}</small>
          </div>
          <div class="mt-3">
            <a href="invoice_uploads/${row.receipt_attachment}" 
               target="_blank" 
               class="btn btn-sm btn-primary">
              <i class="bi bi-download"></i> Download
            </a>
          </div>
        </div>
      `;
    }
  } else {
    attachmentContainer.innerHTML = `
      <div class="text-center p-4">
        <i class="bi bi-file-earmark-x text-muted" style="font-size: 3rem;"></i>
        <p class="text-muted mt-2">No attachment uploaded</p>
      </div>
    `;
  }

  const modal = new bootstrap.Modal(qs('#viewCollectionModal'));
  modal.show();
}

// Enhanced Edit function with attachment handling
async function openEdit(id) {
  const res = await fetch('collections_action.php?action=get&id='+id);
  const r = await res.json();

  qs('#edit_id').value = r.id;
  qs('#edit_client_name').value = r.client_name;
  qs('#edit_invoice_no').value = r.invoice_no;
  qs('#edit_billing_date').value = r.billing_date;
  qs('#edit_due_date').value = r.due_date;

  qs('#edit_amount_base').value = r.amount_base;
  qs('#edit_amount_paid').value = r.amount_paid;
  qs('#edit_penalty').value = r.penalty;
  qs('#edit_mode_of_payment').value = r.mode_of_payment;
  qs('#edit_payment_status').value = r.payment_status;
  qs('#edit_vat_applied').value = r.vat_applied;
  qs('#edit_receipt_type').value = r.receipt_type;
  qs('#edit_collector_name').value = r.collector_name;

  // Handle current attachment display
  const currentAttachmentDiv = qs('#edit_current_attachment');
  const currentFileName = qs('#edit_current_file_name');
  const currentFileLink = qs('#edit_current_file_link');

  if (r.receipt_attachment) {
    currentFileName.textContent = r.receipt_attachment;
    currentFileLink.href = `invoice_uploads/${r.receipt_attachment}`;
    currentAttachmentDiv.style.display = 'block';
  } else {
    currentAttachmentDiv.style.display = 'none';
  }

  // Clear new file preview
  qs('#edit_receipt_attachment').value = '';
  qs('#edit_file_preview').style.display = 'none';

  computeVatPreview('edit');
  updatePenaltyPreview('edit');

  const modal = new bootstrap.Modal(qs('#editCollectionModal'));
  modal.show();
}

// Image modal for full-size viewing
function openImageModal(imageSrc) {
  // Create modal dynamically if it doesn't exist
  let imageModal = qs('#imageModal');
  if (!imageModal) {
    document.body.insertAdjacentHTML('beforeend', `
      <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Receipt Image</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
              <img id="modalImage" src="" class="img-fluid" alt="Receipt">
            </div>
          </div>
        </div>
      </div>
    `);
    imageModal = qs('#imageModal');
  }
  
  qs('#modalImage').src = imageSrc;
  new bootstrap.Modal(imageModal).show();
}

// File validation
function validateFile(file) {
  const maxSize = 5 * 1024 * 1024; // 5MB
  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
  
  if (file.size > maxSize) {
    alert('File size too large. Maximum size is 5MB.');
    return false;
  }
  
  if (!allowedTypes.includes(file.type)) {
    alert('Invalid file type. Only images (JPEG, PNG, GIF, WebP) and PDF files are allowed.');
    return false;
  }
  
  return true;
}

// File preview functions
function clearAddFilePreview() {
  qs('#add_receipt_attachment').value = '';
  qs('#add_file_preview').style.display = 'none';
}

function clearEditFilePreview() {
  qs('#edit_receipt_attachment').value = '';
  qs('#edit_file_preview').style.display = 'none';
}

// Delete (updated to refresh current page)
async function deleteRow(id) {
  if (!confirm('Delete this record?')) return;
  const res = await fetch('collections_action.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'delete', id})
  });
  const ok = await res.text();
  if (ok.trim() === 'ok') {
    collectionsPagination.refresh();
  } else {
    alert('Delete failed: ' + ok);
  }
}

// Penalty live computation
function updatePenaltyPreview(prefix) {
  const dueDate = qs(`#${prefix}_due_date`).value;
  const paymentStatus = qs(`#${prefix}_payment_status`).value;
  
  // Get amount due and paid for penalty calculation
  const amountDue = getAmountDue(prefix);
  const amountPaid = Number(qs(`#${prefix}_amount_paid`).value) || 0;
  
  if (dueDate && paymentStatus) {
    const penalty = calculatePenalty(dueDate, paymentStatus, amountDue, amountPaid);
    qs(`#${prefix}_penalty`).value = penalty.toFixed(2);
    
    // Update penalty info display
    const penaltyInfo = qs(`#${prefix}_penalty_info`);
    if (penaltyInfo) {
      if (paymentStatus === 'Paid') {
        penaltyInfo.textContent = 'No penalty (Paid status)';
        penaltyInfo.className = 'small-muted mt-1 text-success';
      } else if (penalty > 0) {
        const today = new Date();
        const due = new Date(dueDate);
        const daysPastDue = Math.ceil((today.getTime() - due.getTime()) / (1000 * 3600 * 24));
        const remainingUnpaid = Math.max(0, amountDue - amountPaid);
        const dailyPenalty = remainingUnpaid * 0.001 * daysPastDue;
        penaltyInfo.textContent = `${daysPastDue} days × (₱850 + ${peso(remainingUnpaid)} × 0.10%) = ${peso(penalty)}`;
        penaltyInfo.className = 'small-muted mt-1 text-danger';
      } else {
        penaltyInfo.textContent = 'No penalty (not past due or fully paid)';
        penaltyInfo.className = 'small-muted mt-1 text-muted';
      }
    }
  }
}

// Helper function to get current amount due (including VAT)
function getAmountDue(prefix) {
  const base = Number(qs(`#${prefix}_amount_base`).value) || 0;
  const vatApplied = qs(`#${prefix}_vat_applied`).value;
  
  let vatAmt = 0;
  if (vatApplied === 'Yes') {
    vatAmt = base * (12.0 / 100);
  }
  
  return base + vatAmt;
}

// VAT live computation (Add/Edit)
function computeVatPreview(prefix) {
  const base = Number(qs(`#${prefix}_amount_base`).value) || 0;
  const vatApplied = qs(`#${prefix}_vat_applied`).value;
  const vatRate = 12.0; // constant, also validated server-side

  let vatAmt = 0, total = base;
  if (vatApplied === 'Yes') {
    vatAmt = +(base * (vatRate / 100)).toFixed(2);
    total = +(base + vatAmt).toFixed(2);
  }
  qs(`#${prefix}_vat_info`).textContent =
    `VAT: ${vatApplied==='Yes' ? vatRate+'% = '+peso(vatAmt) : 'No VAT'} | Total Due: ${peso(total)}`;

  // set hidden fields that server expects (but server will recompute anyway)
  if (qs(`#${prefix}_vat_amount`)) qs(`#${prefix}_vat_amount`).value = vatAmt;
  if (qs(`#${prefix}_amount_due`)) qs(`#${prefix}_amount_due`).value = total;
}

['add','edit'].forEach(p=>{
  ['amount_base', 'vat_applied'].forEach(id=>{
    const el = qs(`#${p}_${id}`);
    if (el) el.addEventListener('input', ()=>{computeVatPreview(p); updatePenaltyPreview(p);});
    if (el) el.addEventListener('change', ()=>{computeVatPreview(p); updatePenaltyPreview(p);});
  });
  
  // Add penalty calculation listeners
  ['due_date', 'payment_status', 'amount_paid'].forEach(id=>{
    const el = qs(`#${p}_${id}`);
    if (el) el.addEventListener('input', ()=>updatePenaltyPreview(p));
    if (el) el.addEventListener('change', ()=>updatePenaltyPreview(p));
  });
});

// Submit Add (updated to refresh current page)
qs('#addCollectionForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  computeVatPreview('add');
  updatePenaltyPreview('add');
  
  const formData = new FormData(e.target);
  formData.append('action','add');

  const res = await fetch('collections_action.php', { method:'POST', body: formData });
  const txt = await res.text();
  
  if (txt.trim()==='ok') {
    e.target.reset();
    clearAddFilePreview();
    computeVatPreview('add');
    updatePenaltyPreview('add');
    bootstrap.Modal.getInstance(qs('#addCollectionModal')).hide();
    collectionsPagination.refresh();
  } else {
    alert('Save failed: ' + txt);
  }
});

// Submit Edit (updated to refresh current page)
qs('#editCollectionForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  computeVatPreview('edit');
  updatePenaltyPreview('edit');
  
  const formData = new FormData(e.target);
  formData.append('action','update');

  const res = await fetch('collections_action.php', { method:'POST', body: formData });
  const txt = await res.text();
  
  if (txt.trim()==='ok') {
    bootstrap.Modal.getInstance(qs('#editCollectionModal')).hide();
    collectionsPagination.refresh();
  } else {
    alert('Update failed: ' + txt);
  }
});

// Initial load and file input handlers
document.addEventListener('DOMContentLoaded', ()=>{
  computeVatPreview('add');
  updatePenaltyPreview('add');
  
  // Load first page of collections without any filters
  collectionsPagination.loadCollectionsWithPagination({}, 1);

  // Add file input handlers
  const addFileInput = qs('#add_receipt_attachment');
  if (addFileInput) {
    addFileInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      const preview = qs('#add_file_preview');
      const fileName = qs('#add_file_name');
      
      if (file) {
        if (validateFile(file)) {
          fileName.textContent = file.name;
          preview.style.display = 'block';
        } else {
          e.target.value = '';
          preview.style.display = 'none';
        }
      } else {
        preview.style.display = 'none';
      }
    });
  }

  const editFileInput = qs('#edit_receipt_attachment');
  if (editFileInput) {
    editFileInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      const preview = qs('#edit_file_preview');
      const fileName = qs('#edit_file_name');
      
      if (file) {
        if (validateFile(file)) {
          fileName.textContent = file.name;
          preview.style.display = 'block';
        } else {
          e.target.value = '';
          preview.style.display = 'none';
        }
      } else {
        preview.style.display = 'none';
      }
    });
  }
});
</script>
</body>
</html>
