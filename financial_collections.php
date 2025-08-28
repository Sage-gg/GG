<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Collections Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
  </style>
</head>
<body>

<?php include 'sidebar_navbar.php'; ?>

<div class="main-content">
  <div class="container-fluid mt-4 px-4">
    <h2 class="fw-bold mb-4">Collections Management</h2>

    <!-- Filters, Actions, and Summary Cards in one row -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
      <!-- Left side: Search functionality -->
      <div class="d-flex align-items-center">
        <div class="input-group">
          <input id="searchInput" type="text" class="form-control" placeholder="Search by Client, Ref #, or Invoice">
          <button id="searchBtn" class="btn btn-outline-secondary">Search</button>
          <button id="resetBtn" class="btn btn-outline-secondary">Reset</button>
        </div>
      </div>

      <!-- Center: Summary Cards -->
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

    <!-- (Static) Pagination placeholder removed since data is AJAX-driven -->
     <nav aria-label="Budget Pagination" class="mt-4">
      <ul class="pagination justify-content-center">
        <li class="page-item disabled"><a class="page-link">Previous</a></li>
        <li class="page-item active"><a class="page-link">1</a></li>
        <li class="page-item"><a class="page-link">2</a></li>
        <li class="page-item"><a class="page-link">3</a></li>
        <li class="page-item"><a class="page-link">Next</a></li>
      </ul>
    </nav>
  </div>
</div>

<?php include 'financial_collections_modals.php'; ?>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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

// Load table data
async function loadCollections(query='') {
  const res = await fetch('collections_action.php?action=list&query=' + encodeURIComponent(query));
  const data = await res.json(); // {rows:[], totals:{}}
  const tbody = qs('#collections_tbody');
  tbody.innerHTML = '';

  if (!data.rows || data.rows.length === 0) {
    tbody.innerHTML = `<tr><td colspan="12" class="text-center text-muted">No records found</td></tr>`;
  } else {
    data.rows.forEach(row => {
      tbody.insertAdjacentHTML('beforeend', `
        <tr>
          <td>${row.id}</td>
          <td>${escapeHtml(row.client_name)}</td>
          <td>${escapeHtml(row.invoice_no)}</td>
          <td>${row.billing_date}</td>
          <td>${row.due_date}</td>
          <td class="text-end">${peso(row.amount_due)}</td>
          <td class="text-end">${peso(row.amount_paid)}</td>
          <td class="text-end">${peso(row.penalty)}</td>
          <td>${statusBadge(row.payment_status)}</td>
          <td>${escapeHtml(row.receipt_type)}</td>
          <td>${escapeHtml(row.collector_name)}</td>
          <td>
            <div class="btn-group">
              <button class="btn btn-sm btn-primary" onclick="openView(${row.id})">View</button>
              <button class="btn btn-sm btn-warning" onclick="openEdit(${row.id})">Edit</button>
              <button class="btn btn-sm btn-danger" onclick="deleteRow(${row.id})">Delete</button>
            </div>
          </td>
        </tr>
      `);
    });
  }

  // Update summary cards
  qs('#card_total_collected').textContent = peso(data.totals.total_collected || 0);
  qs('#card_pending').textContent = peso(data.totals.total_pending || 0);
  qs('#card_overdue').textContent = peso(data.totals.total_overdue || 0);
}

function statusBadge(s) {
  if (s === 'Paid') return `<span class="badge bg-success">Paid</span>`;
  if (s === 'Partial') return `<span class="badge bg-warning text-dark">Partial</span>`;
  return `<span class="badge bg-danger">Unpaid</span>`;
}

function escapeHtml(str='') {
  return (str+'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

// Search
qs('#searchBtn').addEventListener('click', () => loadCollections(qs('#searchInput').value.trim()));
qs('#resetBtn').addEventListener('click', () => { qs('#searchInput').value=''; loadCollections(); });
qs('#searchInput').addEventListener('keydown', (e)=>{ if(e.key==='Enter'){ e.preventDefault(); qs('#searchBtn').click(); }});

// View
async function openView(id) {
  const res = await fetch('collections_action.php?action=get&id='+id);
  const row = await res.json();
  const body = qs('#viewCollectionModal .modal-body');

  body.innerHTML = `
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

  const modal = new bootstrap.Modal(qs('#viewCollectionModal'));
  modal.show();
}

// Edit (prefill)
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

  computeVatPreview('edit'); // show totals
  updatePenaltyPreview('edit'); // show penalty

  const modal = new bootstrap.Modal(qs('#editCollectionModal'));
  modal.show();
}

// Delete
async function deleteRow(id) {
  if (!confirm('Delete this record?')) return;
  const res = await fetch('collections_action.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'delete', id})
  });
  const ok = await res.text();
  if (ok.trim() === 'ok') {
    loadCollections(qs('#searchInput').value.trim());
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

// Submit Add
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
    computeVatPreview('add');
    updatePenaltyPreview('add');
    bootstrap.Modal.getInstance(qs('#addCollectionModal')).hide();
    loadCollections(qs('#searchInput').value.trim());
  } else {
    alert('Save failed: ' + txt);
  }
});

// Submit Edit
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
    loadCollections(qs('#searchInput').value.trim());
  } else {
    alert('Update failed: ' + txt);
  }
});

// Initial
document.addEventListener('DOMContentLoaded', ()=>{
  computeVatPreview('add');
  updatePenaltyPreview('add');
  loadCollections();
});
</script>
</body>
</html>