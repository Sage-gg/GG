<?php
require_once 'db.php';
requireModuleAccess('expenses');

// Get permissions for this module
$perms = getModulePermission('expenses');
$canCreate = $perms['can_create'];
$canEdit = $perms['can_edit'];
$canDelete = $perms['can_delete'];
$canApprove = $perms['can_approve'];

// For staff, filter by user ID
if (isStaff()) {
    $currentUserId = $_SESSION['user_id'];
}
?>
    
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Expense Tracking & Tax Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="css/style.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    .analytics-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 10px;
      padding: 15px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      margin-bottom: 12px;
    }
    
    .analytics-card h5 {
      font-size: 0.75rem;
      opacity: 0.9;
      margin-bottom: 5px;
    }
    
    .analytics-card .value {
      font-size: 1.3rem;
      font-weight: 700;
    }
    
    .chart-container {
      background: white;
      border-radius: 10px;
      padding: 15px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.08);
      margin-bottom: 12px;
    }
    
    .search-section {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
    }
    
    .receipt-modal-content {
      max-height: 80vh;
      overflow: auto;
    }
    
    .receipt-modal-content img {
      max-width: 100%;
      height: auto;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .receipt-modal-content embed {
      width: 100%;
      min-height: 600px;
      border-radius: 8px;
    }
  </style>
</head>
<body>

<?php include 'sidebar_navbar.php'; ?>

<div class="main-content">
  <div class="container-fluid mt-4 px-4">
    <h2 class="fw-bold mb-4"><i class="bi bi-receipt-cutoff me-2"></i>Expense Tracking & Tax Management</h2>

    <!-- Analytics Section -->
    <div class="row mb-4">
      <!-- Chart -->
      <div class="col-lg-6">
        <div class="chart-container">
          <h5 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill me-2"></i>Expense Analytics</h5>
          <canvas id="expenseChart" style="max-height: 220px;"></canvas>
        </div>
      </div>
      
      <!-- Summary Cards -->
      <div class="col-lg-6">
        <div class="row g-2">
          <div class="col-12">
            <div class="analytics-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
              <h5><i class="bi bi-cash-stack me-2"></i>Total Expenses</h5>
              <div class="value" id="totalExpenses">₱0.00</div>
            </div>
          </div>
          <div class="col-12">
            <div class="analytics-card" style="background: linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%);">
              <h5><i class="bi bi-percent me-2"></i>Total Tax (VAT, Withholding)</h5>
              <div class="value" id="totalTax">₱0.00</div>
            </div>
          </div>
          <div class="col-12">
            <div class="analytics-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
              <h5><i class="bi bi-calculator me-2"></i>Net After Tax</h5>
              <div class="value" id="netAfterTax">₱0.00</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Search and Action Buttons -->
    <div class="search-section">
      <div class="row align-items-end g-3">
        <div class="col-md-8">
          <label class="form-label fw-semibold"><i class="bi bi-search me-1"></i>Search Expenses</label>
          <div class="input-group">
            <input type="text" id="searchInput" class="form-control" placeholder="Search by category, vendor, remarks..." />
            <button class="btn btn-primary" id="searchBtn">
              <i class="bi bi-search"></i> Search
            </button>
          </div>
        </div>
        <div class="col-md-4 text-end">
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
            <i class="bi bi-plus-circle me-1"></i> Add Expense
          </button>
        </div>
      </div>
    </div>

    <!-- Expense Table -->
    <div class="table-responsive shadow-sm rounded" style="background: white;">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Category</th>
            <th>Vendor</th>
            <th>Description</th>
            <th>Amount</th>
            <th>Tax Type</th>
            <th>Tax Amount</th>
            <th>Receipt</th>
            <th>Approved By</th>
            <th>Status</th>
            <th>Payment Method</th>
            <th>Vehicle/Crane</th>
            <th>Job Linked</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="expenseTableBody">
          <tr><td colspan="15" class="text-center py-4">Loading expenses...</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <nav class="mt-4" aria-label="Expense Pagination">
      <ul class="pagination justify-content-center" id="pagination">
        <!-- Pagination buttons to be generated dynamically -->
      </ul>
    </nav>
  </div>
</div>

<!-- Receipt Viewer Modal -->
<div class="modal fade" id="receiptViewerModal" tabindex="-1" aria-labelledby="receiptViewerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-text me-2"></i>Receipt Viewer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body receipt-modal-content" id="receiptModalContent">
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a id="receiptDownloadLink" href="#" class="btn btn-primary" download>
          <i class="bi bi-download me-1"></i> Download
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php include 'financial_expense_modals.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="expense_script.js"></script>
</body>

</html>
