<?php
require_once 'db.php';

// CRITICAL: Check authentication and session timeout BEFORE any output
requireModuleAccess('ledger');

// Get permissions for this module
$perms = getModulePermission('ledger');
$canCreate = $perms['can_create'];
$canEdit = $perms['can_edit'];
$canDelete = $perms['can_delete'];

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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .ledger-tabs {
      background: white;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      padding: 20px;
      margin-top: 20px;
    }
    
    .nav-tabs {
      border-bottom: 2px solid #dee2e6;
      margin-bottom: 20px;
    }
    
    .nav-tabs .nav-link {
      color: #6c757d;
      font-weight: 500;
      padding: 12px 24px;
      border: none;
      border-bottom: 3px solid transparent;
      transition: all 0.3s ease;
    }
    
    .nav-tabs .nav-link:hover {
      color: #0d6efd;
      border-bottom-color: #0d6efd;
      background: transparent;
    }
    
    .nav-tabs .nav-link.active {
      color: #0d6efd;
      background: transparent;
      border-bottom-color: #0d6efd;
      font-weight: 600;
    }
    
    .tab-content {
      padding: 20px 0;
    }
    
    .tab-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid #e9ecef;
    }
    
    .tab-header h5 {
      margin: 0;
      color: #212529;
      font-weight: 600;
    }
    
    .ledger-summary-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 20px;
    }
    
    .summary-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .summary-card.debit {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    
    .summary-card.credit {
      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    
    .summary-card.balance {
      background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }
    
    .summary-card .label {
      font-size: 14px;
      opacity: 0.9;
      margin-bottom: 5px;
    }
    
    .summary-card .value {
      font-size: 24px;
      font-weight: 700;
    }
    
    .filters-section {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    
    .table-wrapper {
      background: white;
      border-radius: 8px;
      overflow: hidden;
    }
  </style>
</head>
<body>

<?php include 'sidebar_navbar.php'; ?>

<div class="main-content">
  <div class="container-fluid mt-4 px-4">
    <h2 class="fw-bold mb-4"><i class="bi bi-journal-text me-2"></i>General Ledger</h2>

    <!-- Summary Cards -->
    <div class="ledger-summary-cards">
      <div class="summary-card debit">
        <div class="label">Total Debit</div>
        <div class="value"><?= formatCurrency($summary['total_debit'] ?? 0) ?></div>
      </div>
      <div class="summary-card credit">
        <div class="label">Total Credit</div>
        <div class="value"><?= formatCurrency($summary['total_credit'] ?? 0) ?></div>
      </div>
      <div class="summary-card balance">
        <div class="label">Net Balance</div>
        <div class="value"><?= formatCurrency($summary['net_balance'] ?? 0) ?></div>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
      <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label fw-semibold">From Date</label>
          <input type="date" name="from_date" value="<?= htmlspecialchars($fromDate) ?>" class="form-control" />
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">To Date</label>
          <input type="date" name="to_date" value="<?= htmlspecialchars($toDate) ?>" class="form-control" />
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Filter by Account</label>
          <select name="account_code" class="form-select">
            <option value="">All Accounts</option>
            <?php foreach ($accounts as $account): ?>
              <option value="<?= $account['account_code'] ?>" <?= $accountCode === $account['account_code'] ? 'selected' : '' ?>>
                <?= $account['account_code'] ?> - <?= htmlspecialchars($account['account_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-search me-1"></i> Filter
          </button>
        </div>
      </form>
    </div>

    <!-- Tabbed Interface -->
    <div class="ledger-tabs">
      <ul class="nav nav-tabs" id="ledgerTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="coa-tab" data-bs-toggle="tab" data-bs-target="#coa" type="button" role="tab">
            <i class="bi bi-list-ul me-2"></i>COA
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="liquidation-tab" data-bs-toggle="tab" data-bs-target="#liquidation" type="button" role="tab">
            <i class="bi bi-receipt me-2"></i>Liquidation
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="journal-tab" data-bs-toggle="tab" data-bs-target="#journal" type="button" role="tab">
            <i class="bi bi-journal-bookmark me-2"></i>JE
          </button>
        </li>
      </ul>

      <div class="tab-content" id="ledgerTabContent">
        <!-- Chart of Accounts Tab -->
        <div class="tab-pane fade show active" id="coa" role="tabpanel">
          <div class="tab-header">
            <h5><i class="bi bi-list-ul me-2"></i>Chart of Accounts</h5>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addAccountModal">
              <i class="bi bi-plus-circle me-1"></i> New Account
            </button>
          </div>
          <div class="table-wrapper">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($accounts as $account): ?>
                  <tr data-account-id="<?= $account['id'] ?? $account['account_code'] ?>">
                    <td><strong><?= $account['account_code'] ?></strong></td>
                    <td><?= htmlspecialchars($account['account_name']) ?></td>
                    <td>
                      <span class="badge bg-info"><?= $account['account_type'] ?></span>
                    </td>
                    <td><?= htmlspecialchars($account['description']) ?></td>
                    <td>
                      <span class="badge <?= $account['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                        <?= $account['status'] ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary view-account-btn" data-bs-toggle="modal" data-bs-target="#viewAccountModal" data-code="<?= $account['account_code'] ?>">
                          <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-warning edit-account-btn" data-code="<?= $account['account_code'] ?>">
                          <i class="bi bi-pencil"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Liquidation Records Tab -->
        <div class="tab-pane fade" id="liquidation" role="tabpanel">
          <div class="tab-header">
            <h5><i class="bi bi-receipt me-2"></i>Liquidation Records</h5>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addLiquidationModal">
              <i class="bi bi-plus-circle me-1"></i> New Liquidation
            </button>
          </div>
          <div class="table-wrapper">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Date</th>
                    <th>Liquidation ID</th>
                    <th>Employee</th>
                    <th>Purpose</th>
                    <th>Amount</th>
                    <th>Receipt</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($liquidations as $liquidation): ?>
                  <tr data-liq-id="<?= $liquidation['id'] ?>">
                    <td><?= $liquidation['date'] ?></td>
                    <td><strong><?= htmlspecialchars($liquidation['liquidation_id']) ?></strong></td>
                    <td><?= htmlspecialchars($liquidation['employee']) ?></td>
                    <td><?= htmlspecialchars($liquidation['purpose']) ?></td>
                    <td><strong>₱<?= formatCurrency($liquidation['total_amount']) ?></strong></td>
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
                          <i class="bi bi-<?= $isPdf ? 'file-pdf' : 'image' ?>"></i>
                        </button>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge <?= $liquidation['status'] === 'Approved' ? 'bg-success' : ($liquidation['status'] === 'Rejected' ? 'bg-danger' : 'bg-warning') ?>">
                        <?= $liquidation['status'] ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary view-liquidation-btn" data-bs-toggle="modal" data-bs-target="#viewLiquidationModal" data-id="<?= $liquidation['id'] ?>">
                          <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-warning edit-liquidation-btn" data-id="<?= $liquidation['id'] ?>">
                          <i class="bi bi-pencil"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Journal Entries Tab -->
        <div class="tab-pane fade" id="journal" role="tabpanel">
          <div class="tab-header">
            <h5><i class="bi bi-journal-bookmark me-2"></i>Journal Entries</h5>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addJournalEntryModal">
              <i class="bi bi-plus-circle me-1"></i> New Entry
            </button>
          </div>
          <div class="table-wrapper">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Date</th>
                    <th>Entry ID</th>
                    <th>Reference</th>
                    <th>Description</th>
                    <th>Account</th>
                    <th>Debit</th>
                    <th>Credit</th>
                    <th>Source</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($entries as $entry): ?>
                  <tr data-journal-id="<?= $entry['id'] ?>">
                    <td><?= $entry['date'] ?></td>
                    <td><strong><?= htmlspecialchars($entry['entry_id']) ?></strong></td>
                    <td><?= htmlspecialchars($entry['reference']) ?></td>
                    <td><?= htmlspecialchars($entry['description']) ?></td>
                    <td>
                      <div class="text-muted small"><?= $entry['account_code'] ?></div>
                      <div><?= htmlspecialchars($entry['account_name']) ?></div>
                    </td>
                    <td class="text-danger fw-semibold"><?= $entry['debit'] > 0 ? '₱' . formatCurrency($entry['debit']) : '—' ?></td>
                    <td class="text-success fw-semibold"><?= $entry['credit'] > 0 ? '₱' . formatCurrency($entry['credit']) : '—' ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($entry['source_module']) ?></span></td>
                    <td class="text-end">
                      <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary view-journal-btn" data-bs-toggle="modal" data-bs-target="#viewJournalModal" data-id="<?= $entry['id'] ?>">
                          <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-warning edit-journal-btn" data-id="<?= $entry['id'] ?>">
                          <i class="bi bi-pencil"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Export and Pagination -->
    <div class="d-flex justify-content-between align-items-center mt-4">
      <button class="btn btn-outline-primary">
        <i class="bi bi-download me-2"></i>Export Ledger
      </button>
      
      <nav aria-label="Ledger pagination">
        <ul class="pagination mb-0">
          <li class="page-item disabled"><a class="page-link">Previous</a></li>
          <li class="page-item active"><a class="page-link">1</a></li>
          <li class="page-item"><a class="page-link">2</a></li>
          <li class="page-item"><a class="page-link">3</a></li>
          <li class="page-item"><a class="page-link">Next</a></li>
        </ul>
      </nav>
    </div>
  </div>
</div>

<?php include 'financial_ledger_modals.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/ledger_scripts.js"></script>

</body>
</html>
