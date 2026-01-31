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
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

<?php include 'sidebar_navbar.php'; ?>

<div class="main-content">
  <div class="container-fluid mt-4 px-4">
    <h2 class="fw-bold mb-4">Expense Tracking & Tax Management</h2>

    <!-- Filters and Summary and Actions -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">

      <!-- Search input (left side) -->
      <div class="input-group w-auto">
        <input type="text" id="searchInput" class="form-control" placeholder="Search by expense type, vendor, remarks..." />
        <button class="btn btn-outline-secondary" id="searchBtn">Search</button>
      </div>

      <!-- Summary + Action Buttons container (right side) -->
      <div class="d-flex flex-column align-items-end gap-2">

   <!-- Smaller Summary (top right) -->
<div class="d-flex gap-2 flex-wrap justify-content-end" style="min-width: 280px;">
  <div class="p-2 bg-light border rounded text-center" style="min-width: 100px;">
    <h6 class="mb-1" style="font-size: 0.75rem;">Total Expenses</h6>
    <h5 class="text-danger mb-0" id="totalExpenses" style="font-size: 0.9rem;">₱0.00</h5>
  </div>
  <div class="p-2 bg-light border rounded text-center" style="min-width: 130px;">
    <h6 class="mb-1" style="font-size: 0.75rem;">Total Tax (VAT, Withholding)</h6>
    <h5 class="text-warning mb-0" id="totalTax" style="font-size: 0.9rem;">₱0.00</h5>
  </div>
  <div class="p-2 bg-light border rounded text-center" style="min-width: 100px;">
    <h6 class="mb-1" style="font-size: 0.75rem;">Net After Tax</h6>
    <h5 class="text-success mb-0" id="netAfterTax" style="font-size: 0.9rem;">₱0.00</h5>
  </div>
</div>

   <!-- Action Buttons (below summary) -->
        <div class="d-flex gap-2">
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addExpenseModal">+ Add Expense</button>
          <button class="btn btn-outline-primary" id="generateTaxReportBtn">Generate Tax Report</button>
        </div>

      </div>
    </div>

    <!-- Expense Table -->
    <div class="table-responsive shadow-sm rounded">
      <table class="table table-bordered table-hover align-middle">
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
          <!-- Rows to be dynamically loaded from database -->
          <tr><td colspan="16" class="text-center">Loading expenses...</td></tr>
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

<?php include 'financial_expense_modals.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="expense_script.js"></script>
</body>

</html>



