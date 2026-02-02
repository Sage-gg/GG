<?php
require_once 'db.php';
requireModuleAccess('collections');  // ← ADD THIS LINE (replaces requireLogin)
// financial_collections.php
// Get permissions for this module
$perms = getModulePermission('collections');
$canCreate = $perms['can_create'];
$canEdit = $perms['can_edit'];
$canDelete = $perms['can_delete'];
$canApprove = $perms['can_approve'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Collections Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <!-- ADD THIS BLOCK -->
  <script>
    // Pass PHP session configuration to JavaScript
    window.SESSION_TIMEOUT = <?php echo SESSION_TIMEOUT * 1000; ?>; // Convert to milliseconds
  </script>
  <!-- END OF ADDED BLOCK -->
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
    .fiscal-year-badge {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 0.35rem 0.75rem;
      border-radius: 1rem;
      font-weight: 600;
      font-size: 0.85rem;
    }
    .main-row {
      cursor: pointer;
      transition: background-color 0.2s;
    }
    .main-row:hover {
      background-color: #f8f9fa;
    }
    .main-row.expanded {
      background-color: #e7f3ff;
    }
    .detail-row {
      display: none;
      background-color: #f8f9fa;
    }
    .detail-row.show {
      display: table-row;
    }
    .detail-content {
      padding: 20px;
      border-left: 4px solid #0d6efd;
    }
    .detail-section {
      margin-bottom: 15px;
    }
    .detail-section h6 {
      color: #0d6efd;
      font-weight: 600;
      margin-bottom: 10px;
      border-bottom: 2px solid #dee2e6;
      padding-bottom: 5px;
    }
    .detail-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 10px;
    }
    .detail-item {
      display: flex;
      padding: 8px;
      background: white;
      border-radius: 4px;
      border: 1px solid #dee2e6;
    }
    .detail-label {
      font-weight: 600;
      color: #495057;
      min-width: 140px;
      margin-right: 10px;
    }
    .detail-value {
      color: #212529;
      flex: 1;
    }
    .expand-icon {
      transition: transform 0.3s;
      display: inline-block;
    }
    .expand-icon.rotated {
      transform: rotate(90deg);
    }
    .attachment-preview {
      max-width: 300px;
      max-height: 200px;
      object-fit: contain;
      border: 1px solid #dee2e6;
      border-radius: 4px;
      cursor: pointer;
    }
    .attachment-placeholder {
      padding: 20px;
      background: white;
      border: 2px dashed #dee2e6;
      border-radius: 4px;
      text-align: center;
      color: #6c757d;
    }
  </style>
</head>
<body>

<?php include 'sidebar_navbar.php'; ?>

<div class="main-content">
  <div class="container-fluid mt-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold mb-0">Collections Management</h2>
      <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#fiscalYearSettingsModal">
        <i class="bi bi-calendar-range"></i> Fiscal Year Settings
      </button>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <h6 class="mb-3 text-muted">Filter Collections</h6>
      <div class="row g-3 align-items-end">
        <!-- Fiscal Year Filter -->
        <div class="col-md-2">
          <label class="form-label small">Fiscal Year</label>
          <select class="form-select" id="fiscalYearFilter">
            <option value="">All Years</option>
            <!-- Dynamic options populated by JS -->
          </select>
        </div>
        
        <!-- Date Range Filters -->
        <div class="col-md-2">
          <label class="form-label small">From Date</label>
          <input type="date" class="form-control" id="dateFrom" placeholder="Start Date">
        </div>
        <div class="col-md-2">
          <label class="form-label small">To Date</label>
          <input type="date" class="form-control" id="dateTo" placeholder="End Date">
        </div>
        
        <!-- Payment Status Filter -->
        <div class="col-md-2">
          <label class="form-label small">Payment Status</label>
          <select class="form-select" id="paymentStatusFilter">
            <option value="">All Status</option>
            <option value="Unpaid">Unpaid</option>
            <option value="Partial">Partial</option>
            <option value="Paid">Paid</option>
          </select>
        </div>
        
        <!-- Filter Actions -->
        <div class="col-md-4">
          <div class="d-flex gap-2">
            <button id="filterBtn" class="btn btn-primary flex-fill">Filter</button>
            <button id="resetFilterBtn" class="btn btn-outline-secondary flex-fill">Reset</button>
            <div class="dropdown flex-fill">
              <button class="btn btn-success dropdown-toggle w-100" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-file-excel"></i> Export
              </button>
              <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                <li><a class="dropdown-item" href="#" onclick="exportData('excel'); return false;">
                  <i class="bi bi-file-excel text-success"></i> Export to Excel
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="exportData('csv'); return false;">
                  <i class="bi bi-filetype-csv text-primary"></i> Export to CSV
                </a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions and Summary Cards -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
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

      <div class="d-flex align-items-center">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCollectionModal">+ Add Collection</button>
      </div>
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <i class="bi bi-info-circle-fill me-2"></i>
      <strong>Click on any row</strong> to expand and view complete collection details including VAT breakdown, payment information, and attachments.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <!-- Enhanced Table with Expandable Rows -->
    <div class="table-responsive shadow-sm rounded">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="width: 30px;"></th>
            <th>#</th>
            <th>Fiscal Year</th>
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

<!-- Fiscal Year Settings Modal -->
<div class="modal fade" id="fiscalYearSettingsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-calendar-range"></i> Fiscal Year Settings</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info">
          <strong>Philippine Fiscal Year:</strong> Configure when your fiscal year starts. Standard is January 1 (Calendar Year).
        </div>
        <form id="fiscalYearSettingsForm">
          <div class="mb-3">
            <label class="form-label">Fiscal Year Start Month</label>
            <select class="form-select" name="start_month" id="fy_start_month" required>
              <option value="1" selected>January (Calendar Year)</option>
              <option value="7">July (Mid-Year)</option>
              <option value="4">April</option>
              <option value="10">October</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Start Day</label>
            <input type="number" class="form-control" name="start_day" id="fy_start_day" min="1" max="31" value="1" required>
          </div>
          <div class="alert alert-warning">
            <small><strong>Note:</strong> Changing fiscal year settings will recalculate fiscal years for all existing records based on their billing dates.</small>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="saveFiscalYearSettings()">Save Settings</button>
      </div>
    </div>
  </div>
</div>

<?php include 'financial_collections_modals.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ADD THIS LINE -->
<script src="session_check.js"></script>
<!-- END OF ADDED LINE -->

<script src="financial_collections_pagination.js"></script>

<script>
const peso = (n) => '₱' + (Number(n)||0).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2});
const qs = (s, el=document) => el.querySelector(s);
const qsa = (s, el=document) => [...el.querySelectorAll(s)];

// Calculate fiscal year based on settings
let currentFYSettings = { start_month: 1, start_day: 1 };

function calculateFiscalYear(date, startMonth = 1, startDay = 1) {
  const d = new Date(date);
  const year = d.getFullYear();
  const month = d.getMonth() + 1;
  const day = d.getDate();
  
  const fyStart = new Date(year, startMonth - 1, startDay);
  
  if (d >= fyStart) {
    return `FY${year}`;
  } else {
    return `FY${year - 1}`;
  }
}

// Update fiscal year preview when date changes
function updateFiscalYearPreview(prefix) {
  const dateInput = qs(`#${prefix}_billing_date`);
  const previewDiv = qs(`#${prefix}_fiscal_year_preview`);
  
  if (dateInput && previewDiv && dateInput.value) {
    const fiscalYear = calculateFiscalYear(
      dateInput.value, 
      currentFYSettings.start_month, 
      currentFYSettings.start_day
    );
    previewDiv.innerHTML = `<i class="bi bi-calendar-check text-success"></i> Fiscal Year: <strong class="text-primary">${fiscalYear}</strong>`;
  } else if (previewDiv) {
    previewDiv.innerHTML = `<i class="bi bi-calendar-check"></i> Fiscal Year: <strong>-</strong>`;
  }
}

// Load fiscal year options
async function loadFiscalYearOptions() {
  const res = await fetch('collections_action.php?action=get_fiscal_years');
  const years = await res.json();
  const select = qs('#fiscalYearFilter');
  select.innerHTML = '<option value="">All Years</option>';
  years.forEach(fy => {
    select.innerHTML += `<option value="${fy}">${fy}</option>`;
  });
}

// Load fiscal year settings
async function loadFiscalYearSettings() {
  const res = await fetch('collections_action.php?action=get_fy_settings');
  const settings = await res.json();
  if (settings) {
    currentFYSettings = settings;
    qs('#fy_start_month').value = settings.start_month;
    qs('#fy_start_day').value = settings.start_day;
  }
}

// Save fiscal year settings
async function saveFiscalYearSettings() {
  const formData = new FormData(qs('#fiscalYearSettingsForm'));
  formData.append('action', 'save_fy_settings');
  
  const res = await fetch('collections_action.php', {
    method: 'POST',
    body: formData
  });
  
  const result = await res.text();
  if (result.trim() === 'ok') {
    bootstrap.Modal.getInstance(qs('#fiscalYearSettingsModal')).hide();
    alert('Fiscal year settings saved successfully. All records have been updated.');
    collectionsPagination.refresh();
    loadFiscalYearOptions();
  } else {
    alert('Failed to save settings: ' + result);
  }
}

function generateDetailRow(row) {
  const remainingBalance = row.amount_due - row.amount_paid;
  
  let attachmentHtml = '';
  if (row.receipt_attachment) {
    const fileExtension = row.receipt_attachment.split('.').pop().toLowerCase();
    const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension);
    const isPdf = fileExtension === 'pdf';
    
    if (isImage) {
      attachmentHtml = `
        <img src="invoice_uploads/${row.receipt_attachment}" 
             alt="Receipt" 
             class="attachment-preview"
             onclick="openImageModal('invoice_uploads/${row.receipt_attachment}')">
        <div class="mt-2">
          <small class="text-muted">${row.receipt_attachment}</small><br>
          <a href="invoice_uploads/${row.receipt_attachment}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
            <i class="bi bi-download"></i> Download
          </a>
        </div>
      `;
    } else if (isPdf) {
      attachmentHtml = `
        <div class="attachment-placeholder">
          <i class="bi bi-file-earmark-pdf-fill text-danger" style="font-size: 3rem;"></i>
          <h6 class="mt-2">PDF Document</h6>
          <small class="text-muted">${row.receipt_attachment}</small>
          <div class="mt-2">
            <a href="invoice_uploads/${row.receipt_attachment}" target="_blank" class="btn btn-sm btn-primary me-2">
              <i class="bi bi-eye"></i> View PDF
            </a>
            <a href="invoice_uploads/${row.receipt_attachment}" download class="btn btn-sm btn-outline-primary">
              <i class="bi bi-download"></i> Download
            </a>
          </div>
        </div>
      `;
    }
  } else {
    attachmentHtml = `
      <div class="attachment-placeholder">
        <i class="bi bi-file-earmark-x text-muted" style="font-size: 2rem;"></i>
        <p class="mb-0 mt-2">No attachment uploaded</p>
      </div>
    `;
  }
  
  return `
    <tr class="detail-row" id="detail-${row.id}">
      <td colspan="14">
        <div class="detail-content">
          <div class="row">
            <div class="col-md-6">
              <div class="detail-section">
                <h6><i class="bi bi-person-badge"></i> Client Information</h6>
                <div class="detail-grid">
                  <div class="detail-item">
                    <div class="detail-label">Client Name:</div>
                    <div class="detail-value">${escapeHtml(row.client_name)}</div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Invoice Number:</div>
                    <div class="detail-value">${escapeHtml(row.invoice_no)}</div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Fiscal Year:</div>
                    <div class="detail-value"><span class="fiscal-year-badge">${row.fiscal_year}</span></div>
                  </div>
                </div>
              </div>
              
              <div class="detail-section">
                <h6><i class="bi bi-calendar-event"></i> Dates</h6>
                <div class="detail-grid">
                  <div class="detail-item">
                    <div class="detail-label">Billing Date:</div>
                    <div class="detail-value">${row.billing_date}</div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Due Date:</div>
                    <div class="detail-value">${row.due_date}</div>
                  </div>
                </div>
              </div>
              
              <div class="detail-section">
                <h6><i class="bi bi-calculator"></i> Financial Breakdown</h6>
                <div class="detail-grid">
                  <div class="detail-item">
                    <div class="detail-label">Base Amount:</div>
                    <div class="detail-value">${peso(row.amount_base)}</div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">VAT Applied:</div>
                    <div class="detail-value">
                      ${row.vat_applied} 
                      ${row.vat_applied === 'Yes' ? `(${row.vat_rate}%)` : ''}
                    </div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">VAT Amount:</div>
                    <div class="detail-value">${peso(row.vat_amount)}</div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label"><strong>Total Amount Due:</strong></div>
                    <div class="detail-value"><strong>${peso(row.amount_due)}</strong></div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Amount Paid:</div>
                    <div class="detail-value text-success">${peso(row.amount_paid)}</div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Remaining Balance:</div>
                    <div class="detail-value ${remainingBalance > 0 ? 'text-danger' : 'text-success'}">
                      ${peso(remainingBalance)}
                    </div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Penalty:</div>
                    <div class="detail-value ${row.penalty > 0 ? 'text-danger' : ''}">
                      ${peso(row.penalty)}
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="detail-section">
                <h6><i class="bi bi-credit-card"></i> Payment Information</h6>
                <div class="detail-grid">
                  <div class="detail-item">
                    <div class="detail-label">Mode of Payment:</div>
                    <div class="detail-value">${escapeHtml(row.mode_of_payment)}</div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Payment Status:</div>
                    <div class="detail-value">${statusBadge(row.payment_status)}</div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Receipt Type:</div>
                    <div class="detail-value">${escapeHtml(row.receipt_type)}</div>
                  </div>
                  <div class="detail-item">
                    <div class="detail-label">Collector:</div>
                    <div class="detail-value">${escapeHtml(row.collector_name)}</div>
                  </div>
                </div>
              </div>
              
              <div class="detail-section">
                <h6><i class="bi bi-paperclip"></i> Receipt Attachment</h6>
                <div class="text-center">
                  ${attachmentHtml}
                </div>
              </div>
            </div>
          </div>
        </div>
      </td>
    </tr>
  `;
}

function toggleRow(id) {
  const mainRow = document.querySelector(`tr.main-row[data-id="${id}"]`);
  const detailRow = document.querySelector(`#detail-${id}`);
  const icon = mainRow.querySelector('.expand-icon');
  
  if (detailRow.classList.contains('show')) {
    detailRow.classList.remove('show');
    mainRow.classList.remove('expanded');
    icon.classList.remove('rotated');
  } else {
    document.querySelectorAll('.detail-row.show').forEach(row => row.classList.remove('show'));
    document.querySelectorAll('.main-row.expanded').forEach(row => row.classList.remove('expanded'));
    document.querySelectorAll('.expand-icon.rotated').forEach(ic => ic.classList.remove('rotated'));
    
    detailRow.classList.add('show');
    mainRow.classList.add('expanded');
    icon.classList.add('rotated');
  }
}

const originalRenderTableData = window.collectionsPagination.renderTableData.bind(window.collectionsPagination);
window.collectionsPagination.renderTableData = function(data) {
  const tbody = document.querySelector('#collections_tbody');
  tbody.innerHTML = '';

  if (!data.rows || data.rows.length === 0) {
    tbody.innerHTML = `<tr><td colspan="14" class="text-center text-muted">No records found</td></tr>`;
  } else {
    data.rows.forEach(row => {
      tbody.insertAdjacentHTML('beforeend', `
        <tr class="main-row" data-id="${row.id}" onclick="toggleRow(${row.id})">
          <td class="text-center">
            <i class="bi bi-chevron-right expand-icon"></i>
          </td>
          <td>${row.id}</td>
          <td><span class="fiscal-year-badge">${row.fiscal_year}</span></td>
          <td>${this.escapeHtml(row.client_name)}</td>
          <td>${this.escapeHtml(row.invoice_no)}</td>
          <td>${row.billing_date}</td>
          <td>${row.due_date}</td>
          <td class="text-end">${this.peso(row.amount_due)}</td>
          <td class="text-end">${this.peso(row.amount_paid)}</td>
          <td class="text-end">${this.peso(row.penalty)}</td>
          <td>${this.statusBadge(row.payment_status)}</td>
          <td>${this.escapeHtml(row.receipt_type)}</td>
          <td>${this.escapeHtml(row.collector_name)}</td>
          <td onclick="event.stopPropagation()">
            <div class="btn-group">
              <button class="btn btn-sm btn-primary" onclick="openView(${row.id})">View</button>
              <button class="btn btn-sm btn-warning" onclick="openEdit(${row.id})">Edit</button>
            </div>
          </td>
        </tr>
      `);
      
      tbody.insertAdjacentHTML('beforeend', generateDetailRow(row));
    });
  }

  document.querySelector('#card_total_collected').textContent = this.peso(data.totals.total_collected || 0);
  document.querySelector('#card_pending').textContent = this.peso(data.totals.total_pending || 0);
  document.querySelector('#card_overdue').textContent = this.peso(data.totals.total_overdue || 0);
};

// Continue with remaining functions from original file...
// (Include all the original helper functions, event listeners, etc.)

function calculatePenalty(dueDate, paymentStatus, amountDue, amountPaid) {
  if (paymentStatus === 'Paid') {
    return 0;
  }
  
  const today = new Date();
  const due = new Date(dueDate);
  
  if (due >= today) {
    return 0;
  }
  
  const timeDiff = today.getTime() - due.getTime();
  const daysPastDue = Math.ceil(timeDiff / (1000 * 3600 * 24));
  
  const remainingUnpaid = Math.max(0, amountDue - amountPaid);
  
  if (remainingUnpaid <= 0) {
    return 0;
  }
  
  const basePenalty = 850;
  const dailyPenaltyRate = 0.001;
  const dailyPenalty = remainingUnpaid * dailyPenaltyRate * daysPastDue;
  
  const totalPenalty = basePenalty + dailyPenalty;
  
  return Math.round(totalPenalty * 100) / 100;
}

async function loadCollections(filters = {}) {
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
  const fiscalYear = qs('#fiscalYearFilter').value.trim();
  
  const filters = {
    date_from: dateFrom,
    date_to: dateTo,
    payment_status: paymentStatus,
    fiscal_year: fiscalYear
  };
  
  collectionsPagination.applyFilters(filters);
});

qs('#resetFilterBtn').addEventListener('click', () => { 
  qs('#dateFrom').value = '';
  qs('#dateTo').value = '';
  qs('#paymentStatusFilter').value = '';
  qs('#fiscalYearFilter').value = '';
  collectionsPagination.resetFilters();
});

// Export functionality
function exportData(format) {
  const dateFrom = qs('#dateFrom').value.trim();
  const dateTo = qs('#dateTo').value.trim();
  const paymentStatus = qs('#paymentStatusFilter').value.trim();
  const fiscalYear = qs('#fiscalYearFilter').value.trim();
  
  const params = new URLSearchParams();
  params.append('format', format);
  
  if (fiscalYear !== '') params.append('fiscal_year', fiscalYear);
  if (dateFrom !== '') params.append('date_from', dateFrom);
  if (dateTo !== '') params.append('date_to', dateTo);
  if (paymentStatus !== '') params.append('payment_status', paymentStatus);
  
  const exportUrl = `collections_export.php?${params.toString()}`;
  window.location.href = exportUrl;
}

async function openView(id) {
  const res = await fetch('collections_action.php?action=get&id='+id);
  const row = await res.json();
  const detailsContainer = qs('#collection_details');
  const attachmentContainer = qs('#attachment_content');

  detailsContainer.innerHTML = `
    <p><strong>Fiscal Year:</strong> <span class="fiscal-year-badge">${row.fiscal_year}</span></p>
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

  qs('#edit_receipt_attachment').value = '';
  qs('#edit_file_preview').style.display = 'none';

  computeVatPreview('edit');
  updatePenaltyPreview('edit');
  updateFiscalYearPreview('edit');

  const modal = new bootstrap.Modal(qs('#editCollectionModal'));
  modal.show();
}

function openImageModal(imageSrc) {
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

function validateFile(file) {
  const maxSize = 5 * 1024 * 1024;
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

function clearAddFilePreview() {
  qs('#add_receipt_attachment').value = '';
  qs('#add_file_preview').style.display = 'none';
}

function clearEditFilePreview() {
  qs('#edit_receipt_attachment').value = '';
  qs('#edit_file_preview').style.display = 'none';
}

function updatePenaltyPreview(prefix) {
  const dueDate = qs(`#${prefix}_due_date`).value;
  const paymentStatus = qs(`#${prefix}_payment_status`).value;
  
  const amountDue = getAmountDue(prefix);
  const amountPaid = Number(qs(`#${prefix}_amount_paid`).value) || 0;
  
  if (dueDate && paymentStatus) {
    const penalty = calculatePenalty(dueDate, paymentStatus, amountDue, amountPaid);
    qs(`#${prefix}_penalty`).value = penalty.toFixed(2);
    
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
        penaltyInfo.textContent = `${daysPastDue} days × (₱850 + ${peso(remainingUnpaid)} × 0.10%) = ${peso(penalty)}`;
        penaltyInfo.className = 'small-muted mt-1 text-danger';
      } else {
        penaltyInfo.textContent = 'No penalty (not past due or fully paid)';
        penaltyInfo.className = 'small-muted mt-1 text-muted';
      }
    }
  }
}

function getAmountDue(prefix) {
  const base = Number(qs(`#${prefix}_amount_base`).value) || 0;
  const vatApplied = qs(`#${prefix}_vat_applied`).value;
  
  let vatAmt = 0;
  if (vatApplied === 'Yes') {
    vatAmt = base * (12.0 / 100);
  }
  
  return base + vatAmt;
}

function computeVatPreview(prefix) {
  const base = Number(qs(`#${prefix}_amount_base`).value) || 0;
  const vatApplied = qs(`#${prefix}_vat_applied`).value;
  const vatRate = 12.0;

  let vatAmt = 0, total = base;
  if (vatApplied === 'Yes') {
    vatAmt = +(base * (vatRate / 100)).toFixed(2);
    total = +(base + vatAmt).toFixed(2);
  }
  qs(`#${prefix}_vat_info`).textContent =
    `VAT: ${vatApplied==='Yes' ? vatRate+'% = '+peso(vatAmt) : 'No VAT'} | Total Due: ${peso(total)}`;

  if (qs(`#${prefix}_vat_amount`)) qs(`#${prefix}_vat_amount`).value = vatAmt;
  if (qs(`#${prefix}_amount_due`)) qs(`#${prefix}_amount_due`).value = total;
}

['add','edit'].forEach(p=>{
  ['amount_base', 'vat_applied'].forEach(id=>{
    const el = qs(`#${p}_${id}`);
    if (el) el.addEventListener('input', ()=>{computeVatPreview(p); updatePenaltyPreview(p);});
    if (el) el.addEventListener('change', ()=>{computeVatPreview(p); updatePenaltyPreview(p);});
  });
  
  ['due_date', 'payment_status', 'amount_paid'].forEach(id=>{
    const el = qs(`#${p}_${id}`);
    if (el) el.addEventListener('input', ()=>updatePenaltyPreview(p));
    if (el) el.addEventListener('change', ()=>updatePenaltyPreview(p));
  });
  
  // Add fiscal year preview on billing date change
  const billingDateEl = qs(`#${p}_billing_date`);
  if (billingDateEl) {
    billingDateEl.addEventListener('input', ()=>updateFiscalYearPreview(p));
    billingDateEl.addEventListener('change', ()=>updateFiscalYearPreview(p));
  }
});

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
    updateFiscalYearPreview('add');
    bootstrap.Modal.getInstance(qs('#addCollectionModal')).hide();
    collectionsPagination.refresh();
    loadFiscalYearOptions();
  } else {
    alert('Save failed: ' + txt);
  }
});

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
    loadFiscalYearOptions();
  } else {
    alert('Update failed: ' + txt);
  }
});

document.addEventListener('DOMContentLoaded', async ()=>{
  computeVatPreview('add');
  updatePenaltyPreview('add');
  
  await loadFiscalYearSettings();
  await loadFiscalYearOptions();
  collectionsPagination.loadCollectionsWithPagination({}, 1);

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
