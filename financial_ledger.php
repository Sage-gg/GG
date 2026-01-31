<?php
require_once 'db.php';

// CRITICAL: Check authentication and session timeout BEFORE any output
requireModuleAccess('ledger');  // ← ADD THIS LINE (replaces requireLogin)

// Get permissions for this module
$perms = getModulePermission('ledger');
$canCreate = $perms['can_create'];
$canEdit = $perms['can_edit'];
$canDelete = $perms['can_delete'];
// Now include your ledger functions after authentication
require_once 'ledger_functions.php';

// Get filter parameters
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';
$accountCode = $_GET['account_code'] ?? '';

// Get ledger entries and summary
$entries = getJournalEntries($fromDate, $toDate, $accountCode);
$entries = calculateRunningBalance($entries);
$summary = getLedgerSummary($fromDate, $toDate);
$accounts = getChartOfAccounts();
$liquidations = getLiquidationRecords();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>General Ledger</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

<?php include 'sidebar_navbar.php'; ?>

<div class="main-content">
  <div class="container-fluid mt-4 px-4">
    <h2 class="fw-bold mb-4">General Ledger</h2>

    <!-- Filters and Summary + Actions -->
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-3">
      <!-- Left: Filters -->
      <form method="GET" class="d-flex flex-column gap-2" style="max-width: 250px;">
        <input type="date" name="from_date" value="<?= htmlspecialchars($fromDate) ?>" class="form-control date-input" placeholder="From Date" />
        <input type="date" name="to_date" value="<?= htmlspecialchars($toDate) ?>" class="form-control date-input" placeholder="To Date" />
        <select name="account_code" class="form-select filter-account">
          <option value="">Filter by Account</option>
          <?php foreach ($accounts as $account): ?>
            <option value="<?= $account['account_code'] ?>" <?= $accountCode === $account['account_code'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($account['account_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline-secondary btn-sm">Search</button>
      </form>

      <!-- Right: Summary + Buttons -->
      <div class="d-flex flex-column align-items-end gap-2 summary-actions">
        <div class="d-flex gap-3 mb-2 ledger-summary-small">
          <div class="text-end">
            <div>Total Debit</div>
            <div class="text-danger fw-bold">₱<?= formatCurrency($summary['total_debit'] ?? 0) ?></div>
          </div>
          <div class="text-end">
            <div>Total Credit</div>
            <div class="text-success fw-bold">₱<?= formatCurrency($summary['total_credit'] ?? 0) ?></div>
          </div>
          <div class="text-end">
            <div>Net Balance</div>
            <div class="text-primary fw-bold">₱<?= formatCurrency($summary['net_balance'] ?? 0) ?></div>
          </div>
        </div>
      </div>
    </div>



    <!-- Three Tables Section -->
    <div class="row mt-5">
      <!-- Liquidation Records Table -->
      <div class="col-12 mb-4">
        <div class="p-3 bg-white border rounded shadow-sm">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Liquidation Records</h5>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addLiquidationModal">
              + New Liquidation Record
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Date</th>
                  <th>Liquidation ID</th>
                  <th>Employee</th>
                  <th>Purpose</th>
                  <th>Total Amount (₱)</th>
                  <th>Receipt</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($liquidations as $liquidation): ?>
                <tr data-liq-id="<?= $liquidation['id'] ?>">
                  <td><?= $liquidation['date'] ?></td>
                  <td><?= htmlspecialchars($liquidation['liquidation_id']) ?></td>
                  <td><?= htmlspecialchars($liquidation['employee']) ?></td>
                  <td><?= htmlspecialchars($liquidation['purpose']) ?></td>
                  <td><?= formatCurrency($liquidation['total_amount']) ?></td>
                  <td>
                    <?php if (!empty($liquidation['receipt_filename'])): ?>
                      <?php 
                        $fileExt = pathinfo($liquidation['receipt_filename'], PATHINFO_EXTENSION);
                        $isPdf = strtolower($fileExt) === 'pdf';
                      ?>
                      <button 
                        class="btn btn-sm btn-outline-primary view-receipt-btn" 
                        data-receipt-path="<?= htmlspecialchars($liquidation['receipt_path']) ?>"
                        data-receipt-filename="<?= htmlspecialchars($liquidation['receipt_filename']) ?>"
                        title="View Receipt">
                        <i class="bi bi-<?= $isPdf ? 'file-pdf' : 'image' ?>"></i> View
                      </button>
                    <?php else: ?>
                      <span class="text-muted">No receipt</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge <?= $liquidation['status'] === 'Approved' ? 'bg-success' : ($liquidation['status'] === 'Rejected' ? 'bg-danger' : 'bg-warning') ?>">
                      <?= $liquidation['status'] ?>
                    </span>
                  </td>
                  <td>
                    <button class="btn btn-sm btn-primary view-liquidation-btn" data-bs-toggle="modal" data-bs-target="#viewLiquidationModal" data-id="<?= $liquidation['id'] ?>">View</button>
                    <button class="btn btn-sm btn-warning edit-liquidation-btn" data-id="<?= $liquidation['id'] ?>">Edit</button>
                    <button class="btn btn-sm btn-danger delete-liquidation-btn" data-id="<?= $liquidation['id'] ?>">Delete</button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Chart of Accounts Table -->
      <div class="col-12 mb-4">
        <div class="p-3 bg-white border rounded shadow-sm">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Chart of Accounts</h5>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addAccountModal">
              + New Account
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Account Code</th>
                  <th>Account Name</th>
                  <th>Type</th>
                  <th>Description</th>
                  <th>Status</th>
                  <th>Actions</th>

                </tr>
              </thead>
              <tbody>
                <?php foreach ($accounts as $account): ?>
                <tr data-account-id="<?= $account['id'] ?? $account['account_code'] ?>">
                  <td><?= $account['account_code'] ?></td>
                  <td><?= htmlspecialchars($account['account_name']) ?></td>
                  <td><?= $account['account_type'] ?></td>
                  <td><?= htmlspecialchars($account['description']) ?></td>
                  <td>
                    <span class="badge <?= $account['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                      <?= $account['status'] ?>
                    </span>
                  </td>
                  <td>
                    <button class="btn btn-sm btn-primary view-account-btn" data-bs-toggle="modal" data-bs-target="#viewAccountModal" data-code="<?= $account['account_code'] ?>">View</button>
                    <button class="btn btn-sm btn-warning edit-account-btn" data-code="<?= $account['account_code'] ?>">Edit</button>
                    <button class="btn btn-sm btn-danger delete-account-btn" data-code="<?= $account['account_code'] ?>">Delete</button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Journal Entries Table -->
      <div class="col-12 mb-4">
        <div class="p-3 bg-white border rounded shadow-sm">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Journal Entries</h5>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addJournalEntryModal">
              + New Journal Entry
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle table-sm mb-0">
              <thead class="table-light">
                <tr>
                  <th>Entry Date</th>
                  <th>Entry ID</th>
                  <th>Reference</th>
                  <th>Description</th>
                  <th>Account Code</th>
                  <th>Account Name</th>
                  <th>Debit (₱)</th>
                  <th>Credit (₱)</th>
                  <th>Source Module</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($entries as $entry): ?>
                <tr data-journal-id="<?= $entry['id'] ?>">
                  <td><?= $entry['date'] ?></td>
                  <td><?= htmlspecialchars($entry['entry_id']) ?></td>
                  <td><?= htmlspecialchars($entry['reference']) ?></td>
                  <td><?= htmlspecialchars($entry['description']) ?></td>
                  <td><?= $entry['account_code'] ?></td>
                  <td><?= htmlspecialchars($entry['account_name']) ?></td>
                  <td><?= $entry['debit'] > 0 ? formatCurrency($entry['debit']) : '-' ?></td>
                  <td><?= $entry['credit'] > 0 ? formatCurrency($entry['credit']) : '-' ?></td>
                  <td><?= htmlspecialchars($entry['source_module']) ?></td>
                  <td>
                    <button class="btn btn-sm btn-primary view-journal-btn" data-bs-toggle="modal" data-bs-target="#viewJournalModal" data-id="<?= $entry['id'] ?>">View</button>
                    <button class="btn btn-sm btn-warning edit-journal-btn" data-id="<?= $entry['id'] ?>">Edit</button>
                    <button class="btn btn-sm btn-danger delete-journal-btn" data-id="<?= $entry['id'] ?>">Delete</button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    <!-- Pagination -->
    <nav class="mt-4" aria-label="Ledger pagination">
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

<?php include 'financial_ledger_modals.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/ledger_scripts.js"></script>

</body>
</html>

