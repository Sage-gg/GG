<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Settings</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

  <?php include 'sidebar_navbar.php'; ?>

  <div class="main-content">
    <div class="container-fluid mt-4 px-4">
      <h2 class="fw-bold mb-4">Financial Settings</h2>

      <div class="row g-4">


        <!-- Collections Management - Updated Settings -->
<div class="col-md-6">
  <div class="card p-4">
    <h5 class="fw-semibold">Collections Management</h5>

    <div class="mb-3">
      <label class="form-label">Default Collection Grace Period (Days)</label>
      <input type="number" class="form-control" placeholder="e.g., 7" />
      <small class="text-muted">Number of days allowed before marking a collection as late.</small>
    </div>

    <div class="mb-3">
      <label class="form-label">Accepted Payment Methods</label>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="paymentCash" checked />
        <label class="form-check-label" for="paymentCash">Cash</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="paymentBank" checked />
        <label class="form-check-label" for="paymentBank">Bank Transfer</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="paymentCheque" />
        <label class="form-check-label" for="paymentCheque">Cheque</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" id="paymentOnline" />
        <label class="form-check-label" for="paymentOnline">Online Gateway</label>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Default Status for New Collections</label>
      <select class="form-select">
        <option selected>Pending</option>
        <option>Under Review</option>
        <option>Received</option>
      </select>
    </div>

    <div class="form-check form-switch mt-3">
      <input class="form-check-input" type="checkbox" id="autoEmailReminders" checked />
      <label class="form-check-label" for="autoEmailReminders">Enable Email Reminders for Pending Collections</label>
    </div>
  </div>
</div>


        <!-- Budgeting and Cost Allocation - Adjusted -->
<div class="col-md-6">
  <div class="card p-4">
    <h5 class="fw-semibold">Budgeting & Cost Allocation</h5>

    <div class="mb-3">
      <label class="form-label">Default Annual Budget Cap (₱)</label>
      <input type="number" class="form-control" placeholder="e.g., 5000000" />
    </div>

    <div class="mb-3">
      <label class="form-label">Overspending Threshold (%)</label>
      <input type="number" class="form-control" placeholder="e.g., 10" />
      <small class="text-muted">Trigger alert when budget exceeds this threshold.</small>
    </div>

    <div class="mb-3">
      <label class="form-label">Default Cost Allocation Method</label>
      <select class="form-select">
        <option selected>Proportional by Department</option>
        <option>Fixed per Quarter</option>
        <option>Manual Assignment</option>
      </select>
    </div>

    <div class="form-check form-switch mt-3">
      <input class="form-check-input" type="checkbox" id="autoReallocation" checked />
      <label class="form-check-label" for="autoReallocation">Enable Auto Reallocation for Unused Budgets</label>
    </div>
  </div>
</div>


        <!-- Expense Tracking & Tax Management Settings -->
<div class="col-md-6">
  <div class="card p-4">
    <h5 class="fw-semibold">Expense Tracking & Tax Management</h5>

    <div class="mb-3">
      <label class="form-label">Default Tax Type</label>
      <select class="form-select">
        <option selected>VAT (12%)</option>
        <option>Withholding (5%)</option>
        <option>No Tax</option>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Enable Custom Tax Rates</label>
      <select class="form-select">
        <option selected>Yes</option>
        <option>No</option>
      </select>
      <small class="text-muted">Allow users to define tax rates per transaction.</small>
    </div>

    <div class="mb-3">
      <label class="form-label">Expense Approval Limit (₱)</label>
      <input type="number" class="form-control" placeholder="e.g., 5000" />
      <small class="text-muted">Expenses above this amount require admin approval.</small>
    </div>

    <div class="form-check form-switch mt-3">
      <input class="form-check-input" type="checkbox" id="autoTaxCalc" checked />
      <label class="form-check-label" for="autoTaxCalc">Enable Automatic Tax Calculation</label>
    </div>

    <div class="form-check form-switch mt-2">
      <input class="form-check-input" type="checkbox" id="vendorTracking" checked />
      <label class="form-check-label" for="vendorTracking">Enable Vendor-Based Expense Tracking</label>
    </div>
  </div>
</div>


        <!-- General Ledger Settings -->
<div class="col-md-6">
  <div class="card p-4">
    <h5 class="fw-semibold">General Ledger Settings</h5>

    <div class="mb-3">
      <label class="form-label">Default Entry Reference Format</label>
      <input type="text" class="form-control" placeholder="e.g., GL-{number}" value="GL-{number}" />
      <small class="text-muted">Used when auto-generating journal entry reference numbers.</small>
    </div>

    <div class="mb-3">
      <label class="form-label">Auto-Balance Entries</label>
      <select class="form-select">
        <option selected>Enabled</option>
        <option>Disabled</option>
      </select>
      <small class="text-muted">Ensure every journal entry is balanced (debits = credits).</small>
    </div>

    <div class="mb-3">
      <label class="form-label">Default Account Filter</label>
      <select class="form-select">
        <option>All Accounts</option>
        <option>Cash</option>
        <option>Revenue</option>
        <option>Expenses</option>
        <option>Accounts Payable</option>
      </select>
    </div>

    <div class="form-check form-switch mt-3">
      <input class="form-check-input" type="checkbox" id="enableLedgerLock" />
      <label class="form-check-label" for="enableLedgerLock">Enable Ledger Locking (Post-Financial Close)</label>
    </div>

    <div class="form-check form-switch mt-2">
      <input class="form-check-input" type="checkbox" id="showRunningBalance" checked />
      <label class="form-check-label" for="showRunningBalance">Display Running Balance in Table</label>
    </div>
  </div>
</div>


        <!-- Financial Reporting -->
        <div class="col-md-6">
          <div class="card p-4">
            <h5 class="fw-semibold">Financial Reporting Module</h5>
            <div class="mb-3">
              <label class="form-label">Default Report Format</label>
              <select class="form-select">
                <option>PDF</option>
                <option>Excel</option>
                <option>CSV</option>
              </select>
            </div>
            <div>
              <label class="form-label">Auto Email Reports</label>
              <select class="form-select">
                <option>Yes</option>
                <option>No</option>
              </select>
            </div>
          </div>
        </div>
      </div>

    <!-- Spacer for alignment -->
  <div class="col-md-6"></div>

  <!-- Save Settings Card with top and bottom spacing -->
  <div class="col-md-6 offset-md-3 mt-4 mb-5">
    <div class="card p-4">
      <div class="d-flex justify-content-center">
        <button class="btn btn-success fw-semibold px-4 py-2">
          💾 Save Settings
        </button>
      </div>
    </div>
  </div>
</div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
