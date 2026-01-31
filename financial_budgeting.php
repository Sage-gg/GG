<?php
require_once 'db.php';
// financial_budgeting.php - UPDATED WITH BI-WEEKLY SUPPORT
// CRITICAL: Check authentication and session timeout BEFORE any output
requireModuleAccess('budgeting');

// Get permissions for this module
$perms = getModulePermission('budgeting');
$canCreate = $perms['can_create'];
$canEdit = $perms['can_edit'];
$canDelete = $perms['can_delete'];
$canApprove = $perms['can_approve'];

// For managers, filter by department
if (isManager()) {
    $userDepartment = getUserDepartment();
}

// Include pagination after authentication
require_once 'financial_budgeting_pagination.php';

// Check if database connection exists
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed. Please check your database configuration in db.php");
}

// --- Pagination settings ---
$recordsPerPage = 5;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// --- Filter handling ---
$filterPeriod = isset($_GET['filter_period']) ? trim($_GET['filter_period']) : '';
$filterStatus = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';

// Function to determine budget status
function getBudgetStatus($allocated, $used) {
    $diff = (float)$allocated - (float)$used;
    $percentage = $allocated > 0 ? ($diff / (float)$allocated) : 1;
    
    if ($diff < 0) {
        return 'overspent';
    } elseif ($percentage < 0.05) {
        return 'tight';
    } else {
        return 'on_track';
    }
}

// Build base query for counting total records with filters
$countSql = "SELECT COUNT(*) as total FROM budgets";
$countParams = [];
$countTypes = '';
$whereConditions = [];

if ($filterPeriod !== '') {
    $whereConditions[] = "period = ?";
    $countParams[] = $filterPeriod;
    $countTypes .= 's';
}

if (!empty($whereConditions)) {
    $countSql .= " WHERE " . implode(" AND ", $whereConditions);
}

// Get total count
try {
    $countStmt = $conn->prepare($countSql);
    if (!$countStmt) {
        throw new Exception("Count prepare failed: " . $conn->error);
    }
    
    if (!empty($countParams)) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
    
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRecordsBeforeStatusFilter = $countResult->fetch_assoc()['total'];
    $countStmt->close();
} catch (Exception $e) {
    die("Count query failed: " . $e->getMessage());
}

// Build main query with pagination
$sql = "SELECT id, period, department, cost_center, amount_allocated, amount_used, approved_by, approval_status, description, created_at
        FROM budgets";
$params = [];
$types  = '';

if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
    $params = $countParams;
    $types = $countTypes;
}

$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $allRows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    die("Database query failed: " . $e->getMessage());
}

// Filter by status if specified
$filteredRows = $allRows;
if ($filterStatus !== '') {
    $filteredRows = array_filter($allRows, function($row) use ($filterStatus) {
        $status = getBudgetStatus($row['amount_allocated'], $row['amount_used']);
        return $status === $filterStatus;
    });
    $filteredRows = array_values($filteredRows);
}

// Calculate pagination based on filtered results
$totalRecords = count($filteredRows);
$pagination = calculatePagination($totalRecords, $recordsPerPage, $currentPage);

// Apply pagination to filtered results
$rows = array_slice($filteredRows, $pagination['offset'], $recordsPerPage);

// Load ALL budget data for AI analysis
$allBudgetSql = "SELECT id, period, department, cost_center, amount_allocated, amount_used, approved_by, approval_status, description, created_at
                 FROM budgets ORDER BY created_at DESC";

try {
    $allBudgetStmt = $conn->prepare($allBudgetSql);
    if (!$allBudgetStmt) {
        throw new Exception("All budget prepare failed: " . $conn->error);
    }
    
    $allBudgetStmt->execute();
    $allBudgetResult = $allBudgetStmt->get_result();
    $allBudgetData = $allBudgetResult->fetch_all(MYSQLI_ASSOC);
    $allBudgetStmt->close();
} catch (Exception $e) {
    error_log("All budget query error: " . $e->getMessage());
    $allBudgetData = $allRows;
}

// --- Summary cards ---
try {
    $sumSql = "SELECT 
                COALESCE(SUM(amount_allocated),0) AS total_budget,
                COALESCE(SUM(amount_used),0) AS total_used,
                COALESCE(SUM(amount_allocated - amount_used),0) AS total_remaining
               FROM budgets";
    
    $sumParams = [];
    $sumTypes = '';
    if (!empty($whereConditions)) {
        $sumSql .= " WHERE " . implode(" AND ", $whereConditions);
        $sumParams = $countParams;
        $sumTypes = $countTypes;
    }
    
    $sumStmt = $conn->prepare($sumSql);
    if (!$sumStmt) {
        throw new Exception("Summary prepare failed: " . $conn->error);
    }
    
    if (!empty($sumParams)) {
        $sumStmt->bind_param($sumTypes, ...$sumParams);
    }
    
    $sumStmt->execute();
    $summaryResult = $sumStmt->get_result();
    $summaryData = $summaryResult->fetch_assoc();
    $sumStmt->close();
    
    if ($filterStatus !== '') {
        $summary = [
            'total_budget' => array_sum(array_column($filteredRows, 'amount_allocated')),
            'total_used' => array_sum(array_column($filteredRows, 'amount_used')),
            'total_remaining' => 0
        ];
        $summary['total_remaining'] = $summary['total_budget'] - $summary['total_used'];
    } else {
        $summary = $summaryData;
    }
    
} catch (Exception $e) {
    $summary = [
        'total_budget' => 0,
        'total_used' => 0,
        'total_remaining' => 0
    ];
    error_log("Summary query error: " . $e->getMessage());
}

function peso($n) {
  return '₱' . number_format((float)$n, 2);
}

$paginationInfo = getPaginationInfo($pagination['current_page'], $recordsPerPage, $totalRecords, count($rows));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Budgeting & Cost Allocation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <!-- ADD THIS BLOCK -->
  <script>
    // Pass PHP session configuration to JavaScript
    window.SESSION_TIMEOUT = <?php echo SESSION_TIMEOUT * 1000; ?>; // Convert to milliseconds
  </script>
  <!-- END OF ADDED BLOCK -->
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .table thead th { white-space: nowrap; }
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
    .pagination-info {
      font-size: 0.875rem;
      color: #6c757d;
    }
    .filter-section {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
      padding: 1rem;
      margin-bottom: 1.5rem;
    }
    
    /* Enhanced table styles for expandable rows */
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
  </style>
</head>
<body>

<?php include 'sidebar_navbar.php'; ?>

<div class="main-content">
  <div class="container-fluid mt-4 px-4">
    <h2 class="fw-bold mb-3">Budgeting & Cost Allocation</h2>

    <!-- Flash messages -->
    <?php if(isset($_SESSION['flash'])): ?>
      <div class="alert alert-<?=$_SESSION['flash']['type']?> alert-dismissible fade show" role="alert">
        <?=$_SESSION['flash']['msg']?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="filter-section">
      <h6 class="mb-3 text-muted">Filter Budgets</h6>
      <form class="row g-3 align-items-end" method="get">
        <div class="col-md-3">
          <label for="filter_period" class="form-label small">Filter by Period</label>
          <select class="form-select" id="filter_period" name="filter_period">
            <option value="">All Periods</option>
            <option value="Daily" <?= $filterPeriod === 'Daily' ? 'selected' : '' ?>>Daily</option>
            <option value="Bi-weekly" <?= $filterPeriod === 'Bi-weekly' ? 'selected' : '' ?>>Bi-weekly</option>
            <option value="Monthly" <?= $filterPeriod === 'Monthly' ? 'selected' : '' ?>>Monthly</option>
            <option value="Annually" <?= $filterPeriod === 'Annually' ? 'selected' : '' ?>>Annually</option>
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter_status" class="form-label small">Filter by Status</label>
          <select class="form-select" id="filter_status" name="filter_status">
            <option value="">All Status</option>
            <option value="on_track" <?= $filterStatus === 'on_track' ? 'selected' : '' ?>>On Track</option>
            <option value="tight" <?= $filterStatus === 'tight' ? 'selected' : '' ?>>Tight</option>
            <option value="overspent" <?= $filterStatus === 'overspent' ? 'selected' : '' ?>>Overspent</option>
          </select>
        </div>
        <div class="col-md-6">
          <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">Apply Filters</button>
            <?php if($filterPeriod !== '' || $filterStatus !== ''): ?>
              <a class="btn btn-outline-secondary" href="financial_budgeting.php">Clear Filters</a>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>

    <!-- Actions and Summary Cards -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
      <div class="d-flex gap-2">
        <div class="summary-card">
          <div class="summary-label">Total Budget</div>
          <div class="summary-value text-primary"><?= peso($summary['total_budget']) ?></div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Total Spent</div>
          <div class="summary-value text-danger"><?= peso($summary['total_used']) ?></div>
        </div>
        <div class="summary-card">
          <div class="summary-label">Remaining</div>
          <div class="summary-value text-success"><?= peso($summary['total_remaining']) ?></div>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#budgetForecastModal">Budget Forecast</button>
        <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#addBudgetModal">+ Add Budget</button>
      </div>
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <i class="bi bi-info-circle-fill me-2"></i>
      <strong>Click on any row</strong> to expand and view complete budget details including financial breakdown and approval information.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <!-- Pagination Info -->
    <?php if ($totalRecords > 0): ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="pagination-info">
        Showing <?= $paginationInfo['start'] ?> to <?= $paginationInfo['end'] ?> of <?= $paginationInfo['total'] ?> entries
        <?php if ($filterPeriod !== '' || $filterStatus !== ''): ?>
          (filtered from total entries)
        <?php endif; ?>
      </div>
      <div class="pagination-info">
        Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Enhanced Budget Table with Expandable Rows -->
    <div class="table-responsive shadow-sm rounded">
      <table class="table table-bordered table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 30px;"></th>
            <th>#</th>
            <th>Period</th>
            <th>Department</th>
            <th>Cost Center</th>
            <th class="text-end">Allocated</th>
            <th class="text-end">Used</th>
            <th class="text-end">Difference</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="budgets_tbody">
        <?php if(empty($rows)): ?>
          <tr><td colspan="10" class="text-center text-muted py-4">
            <?php if ($filterPeriod !== '' || $filterStatus !== ''): ?>
              No budgets found matching your filter criteria.
            <?php else: ?>
              No budgets found. Add one to get started.
            <?php endif; ?>
          </td></tr>
        <?php else: ?>
          <?php foreach($rows as $index => $r):
              $globalIndex = $pagination['offset'] + $index + 1;
              $diff = (float)$r['amount_allocated'] - (float)$r['amount_used'];
              
              $status = getBudgetStatus($r['amount_allocated'], $r['amount_used']);
              if ($status === 'overspent') {
                $statusBadge = '<span class="badge bg-danger">Overspent</span>';
              } elseif ($status === 'tight') {
                $statusBadge = '<span class="badge bg-warning text-dark">Tight</span>';
              } else {
                $statusBadge = '<span class="badge bg-success">On Track</span>';
              }
              
              $dataAttrs = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');
          ?>
            <!-- Main Row -->
            <tr class="main-row" data-id="<?=$r['id']?>">
              <td class="text-center" onclick="toggleBudgetRow(<?=$r['id']?>)" style="cursor: pointer;">
                <i class="bi bi-chevron-right expand-icon"></i>
              </td>
              <td onclick="toggleBudgetRow(<?=$r['id']?>)" style="cursor: pointer;"><?= $globalIndex ?></td>
              <td onclick="toggleBudgetRow(<?=$r['id']?>)" style="cursor: pointer;"><?= htmlspecialchars($r['period']) ?></td>
              <td onclick="toggleBudgetRow(<?=$r['id']?>)" style="cursor: pointer;"><?= htmlspecialchars($r['department']) ?></td>
              <td onclick="toggleBudgetRow(<?=$r['id']?>)" style="cursor: pointer;"><?= htmlspecialchars($r['cost_center']) ?></td>
              <td onclick="toggleBudgetRow(<?=$r['id']?>)" style="cursor: pointer;" class="text-end"><?= peso($r['amount_allocated']) ?></td>
              <td onclick="toggleBudgetRow(<?=$r['id']?>)" style="cursor: pointer;" class="text-end"><?= peso($r['amount_used']) ?></td>
              <td onclick="toggleBudgetRow(<?=$r['id']?>)" style="cursor: pointer;" class="text-end"><?= ($diff<0?'-':'') . peso(abs($diff)) ?></td>
              <td onclick="toggleBudgetRow(<?=$r['id']?>)" style="cursor: pointer;"><?= $statusBadge ?></td>
              <td>
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-primary btn-view" 
                          data-record='<?=$dataAttrs?>' 
                          data-bs-toggle="modal" 
                          data-bs-target="#viewBudgetModal"
                          onclick="viewBudget(this); event.stopPropagation();">View</button>
                  <button type="button" class="btn btn-sm btn-warning btn-edit" 
                          data-record='<?=$dataAttrs?>' 
                          data-bs-toggle="modal" 
                          data-bs-target="#editBudgetModal"
                          onclick="editBudget(this); event.stopPropagation();">Edit</button>
                  <button type="button" class="btn btn-sm btn-danger btn-delete" 
                          data-id="<?=$r['id']?>" 
                          data-name="<?=htmlspecialchars($r['department'].' - '.$r['cost_center'])?>" 
                          data-bs-toggle="modal" 
                          data-bs-target="#deleteBudgetModal"
                          onclick="deleteBudget(this); event.stopPropagation();">Delete</button>
                </div>
              </td>
            </tr>
            
            <!-- Detail Row -->
            <tr class="detail-row" id="detail-<?=$r['id']?>">
              <td colspan="10">
                <div class="detail-content">
                  <div class="row">
                    <!-- Left Column: Basic & Financial Info -->
                    <div class="col-md-6">
                      <div class="detail-section">
                        <h6><i class="bi bi-building"></i> Budget Information</h6>
                        <div class="detail-grid">
                          <div class="detail-item">
                            <div class="detail-label">Budget Period:</div>
                            <div class="detail-value"><?= htmlspecialchars($r['period']) ?></div>
                          </div>
                          <div class="detail-item">
                            <div class="detail-label">Department:</div>
                            <div class="detail-value"><?= htmlspecialchars($r['department']) ?></div>
                          </div>
                          <div class="detail-item">
                            <div class="detail-label">Cost Center:</div>
                            <div class="detail-value"><?= htmlspecialchars($r['cost_center']) ?></div>
                          </div>
                        </div>
                      </div>
                      
                      <div class="detail-section">
                        <h6><i class="bi bi-calculator"></i> Financial Breakdown</h6>
                        <div class="detail-grid">
                          <div class="detail-item">
                            <div class="detail-label"><strong>Allocated Amount:</strong></div>
                            <div class="detail-value"><strong><?= peso($r['amount_allocated']) ?></strong></div>
                          </div>
                          <div class="detail-item">
                            <div class="detail-label">Amount Used:</div>
                            <div class="detail-value text-danger"><?= peso($r['amount_used']) ?></div>
                          </div>
                          <div class="detail-item">
                            <div class="detail-label">Remaining Balance:</div>
                            <div class="detail-value <?= $diff < 0 ? 'text-danger' : 'text-success' ?>">
                              <?= ($diff<0?'-':'') . peso(abs($diff)) ?>
                            </div>
                          </div>
                          <div class="detail-item">
                            <div class="detail-label">Utilization Rate:</div>
                            <div class="detail-value">
                              <?php 
                                $utilization = $r['amount_allocated'] > 0 
                                  ? ($r['amount_used'] / $r['amount_allocated'] * 100) 
                                  : 0;
                                $utilClass = $utilization > 100 ? 'text-danger' : ($utilization > 95 ? 'text-warning' : 'text-success');
                              ?>
                              <span class="<?= $utilClass ?>"><?= number_format($utilization, 1) ?>%</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Right Column: Approval & Additional Info -->
                    <div class="col-md-6">
                      <div class="detail-section">
                        <h6><i class="bi bi-check-circle"></i> Approval Information</h6>
                        <div class="detail-grid">
                          <div class="detail-item">
                            <div class="detail-label">Approved By:</div>
                            <div class="detail-value"><?= htmlspecialchars($r['approved_by'] ?: 'N/A') ?></div>
                          </div>
                          <div class="detail-item">
                            <div class="detail-label">Approval Status:</div>
                            <div class="detail-value">
                              <?php
                                $approvalBadge = match($r['approval_status']) {
                                  'Approved' => '<span class="badge bg-success">Approved</span>',
                                  'Rejected' => '<span class="badge bg-danger">Rejected</span>',
                                  default => '<span class="badge bg-warning text-dark">Pending</span>'
                                };
                                echo $approvalBadge;
                              ?>
                            </div>
                          </div>
                          <div class="detail-item">
                            <div class="detail-label">Budget Status:</div>
                            <div class="detail-value"><?= $statusBadge ?></div>
                          </div>
                          <div class="detail-item">
                            <div class="detail-label">Created Date:</div>
                            <div class="detail-value"><?= date('M d, Y', strtotime($r['created_at'])) ?></div>
                          </div>
                        </div>
                      </div>
                      
                      <div class="detail-section">
                        <h6><i class="bi bi-file-text"></i> Description / Justification</h6>
                        <div class="detail-item">
                          <div class="detail-value">
                            <?= nl2br(htmlspecialchars($r['description'] ?: 'No description provided')) ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php 
    if ($totalRecords > 0) {
        $queryParams = [];
        if ($filterPeriod !== '') {
            $queryParams['filter_period'] = $filterPeriod;
        }
        if ($filterStatus !== '') {
            $queryParams['filter_status'] = $filterStatus;
        }
        echo generatePagination($pagination['current_page'], $pagination['total_pages'], 'financial_budgeting.php', $queryParams);
    }
    ?>
  </div>
</div>

<!-- Modals -->
<?php include 'financial_budgeting_modals.php'; ?>

<?php include 'send_to_core2_modal.php'; ?>
    
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ADD THIS LINE -->
<script src="session_check.js"></script>
<!-- END OF ADDED LINE -->

<script src="budget_forecast_modal.js"></script>
<script>
// Department to Cost Center mapping - UPDATED WITH PAYROLL BUDGET
const departmentCostCenters = {
  'HR': ['Training Budget', 'Reimbursement Budget', 'Benefits Budget', 'Payroll Budget'],
  'Core': ['Log Maintenance Costs', 'Depreciation Charges', 'Insurance Fees', 'Vehicle Operational Budget']
};

const legacyDepartmentMapping = {
  'HR2': 'HR', 'HR4': 'HR', 'Core 2': 'Core', 'Core 4': 'Core',
  'Logistics': 'Core', 'Operations': 'Core', 'Maintenance': 'Core',
  'Accounting': 'HR', 'Administration': 'HR'
};

const legacyCostCenterMapping = {
  'Fuel': 'Vehicle Operational Budget',
  'RFID': 'Training Budget',
  'Labor': 'Benefits Budget',
  'Maintenance': 'Log Maintenance Costs',
  'Crane Rental': 'Vehicle Operational Budget',
  'Truck Lease': 'Vehicle Operational Budget'
};

// Make budget data globally available
window.budgetData = <?php echo json_encode($allBudgetData); ?>;
window.summaryData = <?php echo json_encode($summary); ?>;
window.currentPageData = <?php echo json_encode($rows); ?>;

function peso(n){
  n = parseFloat(n || 0);
  return '₱' + n.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
}

// Toggle row expansion
function toggleBudgetRow(id) {
  const mainRow = document.querySelector(`tr.main-row[data-id="${id}"]`);
  const detailRow = document.querySelector(`#detail-${id}`);
  const icon = mainRow.querySelector('.expand-icon');
  
  if (detailRow.classList.contains('show')) {
    detailRow.classList.remove('show');
    mainRow.classList.remove('expanded');
    icon.classList.remove('rotated');
  } else {
    // Close all other expanded rows
    document.querySelectorAll('.detail-row.show').forEach(row => row.classList.remove('show'));
    document.querySelectorAll('.main-row.expanded').forEach(row => row.classList.remove('expanded'));
    document.querySelectorAll('.expand-icon.rotated').forEach(ic => ic.classList.remove('rotated'));
    
    // Open clicked row
    detailRow.classList.add('show');
    mainRow.classList.add('expanded');
    icon.classList.add('rotated');
  }
}

function updateCostCenter(prefix) {
  const departmentSelect = document.getElementById(prefix + '_department');
  const costCenterSelect = document.getElementById(prefix + '_cost_center');
  
  if (!departmentSelect || !costCenterSelect) return;
  
  const selectedDepartment = departmentSelect.value;
  costCenterSelect.innerHTML = '';
  
  if (selectedDepartment && departmentCostCenters[selectedDepartment]) {
    departmentCostCenters[selectedDepartment].forEach(costCenter => {
      const option = document.createElement('option');
      option.value = costCenter;
      option.textContent = costCenter;
      costCenterSelect.appendChild(option);
    });
  } else {
    const option = document.createElement('option');
    option.value = '';
    option.textContent = 'Select Department First';
    option.disabled = true;
    option.selected = true;
    costCenterSelect.appendChild(option);
  }
}

function ensureLegacyOption(selectElement, value, label) {
  if (!value || !selectElement) return;
  
  const existingOption = Array.from(selectElement.options).find(option => option.value === value);
  if (!existingOption) {
    const legacyOption = document.createElement('option');
    legacyOption.value = value;
    legacyOption.textContent = label || value + ' (Legacy)';
    legacyOption.className = 'legacy-option';
    legacyOption.style.backgroundColor = '#fff3cd';
    legacyOption.style.color = '#856404';
    selectElement.appendChild(legacyOption);
  }
}

// View modal - Direct function
function viewBudget(btn) {
  try {
    const rec = JSON.parse(btn.dataset.record);
    console.log('View budget:', rec);
    
    const diff = (parseFloat(rec.amount_allocated || 0) - parseFloat(rec.amount_used || 0));
    const displayDepartment = legacyDepartmentMapping[rec.department] || rec.department;
    
    document.getElementById('v_period').textContent = rec.period || '';
    document.getElementById('v_department').textContent = displayDepartment;
    document.getElementById('v_cost_center').textContent = rec.cost_center || '';
    document.getElementById('v_alloc').textContent = peso(rec.amount_allocated);
    document.getElementById('v_used').textContent = peso(rec.amount_used);
    document.getElementById('v_diff').textContent = (diff<0?'-':'') + peso(Math.abs(diff));
    document.getElementById('v_approved_by').textContent = rec.approved_by || 'N/A';
    document.getElementById('v_approval_status').textContent = rec.approval_status || 'Pending';
    document.getElementById('v_description').textContent = rec.description || 'No description provided';
    
  } catch (error) {
    console.error('Error in viewBudget:', error);
    alert('Error loading record data: ' + error.message);
  }
}

// Edit modal - Direct function
function editBudget(btn) {
  try {
    const rec = JSON.parse(btn.dataset.record);
    console.log('Edit budget:', rec);
    
    document.getElementById('edit_id').value = rec.id || '';
    document.getElementById('edit_period').value = rec.period || '';
    document.getElementById('edit_amount_allocated').value = rec.amount_allocated || '';
    document.getElementById('edit_amount_used').value = rec.amount_used || '';
    document.getElementById('edit_approved_by').value = rec.approved_by || '';
    document.getElementById('edit_approval_status').value = rec.approval_status || '';
    document.getElementById('edit_description').value = rec.description || '';
    
    let mappedDepartment = legacyDepartmentMapping[rec.department] || rec.department;
    document.getElementById('edit_department').value = mappedDepartment;
    
    updateCostCenter('edit');
    
    setTimeout(() => {
      let mappedCostCenter = legacyCostCenterMapping[rec.cost_center] || rec.cost_center;
      const costCenterSelect = document.getElementById('edit_cost_center');
      
      const costCenterExists = Array.from(costCenterSelect.options).some(
        option => option.value === mappedCostCenter
      );
      
      if (costCenterExists) {
        costCenterSelect.value = mappedCostCenter;
      } else {
        ensureLegacyOption(costCenterSelect, rec.cost_center, rec.cost_center + ' (Legacy - Please Update)');
        costCenterSelect.value = rec.cost_center;
      }
    }, 100);
    
  } catch (error) {
    console.error('Error in editBudget:', error);
    alert('Error loading record data: ' + error.message);
  }
}

// Delete modal - Direct function
function deleteBudget(btn) {
  try {
    console.log('Delete budget, id:', btn.dataset.id);
    
    document.getElementById('delete_id').value = btn.dataset.id || '';
    document.getElementById('delete_name').textContent = btn.dataset.name || 'this record';
    
  } catch (error) {
    console.error('Error in deleteBudget:', error);
    alert('Error: ' + error.message);
  }
}

function notifyDepartment(formType) {
  const department = document.getElementById(formType + '_department')?.value;
  const costCenter = document.getElementById(formType + '_cost_center')?.value;
  const amount = document.getElementById(formType + '_amount_allocated')?.value;
  const period = document.getElementById(formType + '_period')?.value;
  
  if (!department) {
    alert('Please select a department first.');
    return;
  }
  
  const message = `Budget notification sent to ${department} Department.\n\n` +
        `Details:\n` +
        `• Cost Center: ${costCenter || 'Not specified'}\n` +
        `• Amount: ₱${amount ? parseFloat(amount).toLocaleString() : 'Not specified'}\n` +
        `• Budget Period: ${period || 'Not specified'}\n\n` +
        `Notification Message:\n` +
        `"Your budget allocation has been processed. Please monitor your spending against the allocated amount."`;
  
  alert(message);
}

function forwardToAdmin(formType) {
  const department = document.getElementById(formType + '_department')?.value;
  const costCenter = document.getElementById(formType + '_cost_center')?.value;
  const amount = document.getElementById(formType + '_amount_allocated')?.value;
  const description = document.getElementById(formType + '_description')?.value;
  const period = document.getElementById(formType + '_period')?.value;
  
  if (!department) {
    alert('Please select a department first.');
    return;
  }
  
  const message = `Budget request forwarded to Admin Dashboard.\n\n` +
        `Request Details:\n` +
        `• Department: ${department}\n` +
        `• Cost Center: ${costCenter || 'Not specified'}\n` +
        `• Requested Amount: ₱${amount ? parseFloat(amount).toLocaleString() : 'Not specified'}\n` +
        `• Budget Period: ${period || 'Not specified'}\n` +
        `• Description: ${description || 'No description provided'}`;
  
  alert(message);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
  const addCostCenter = document.getElementById('add_cost_center');
  if (addCostCenter) {
    addCostCenter.innerHTML = '<option disabled selected value="">Select Department First</option>';
  }
  
  console.log('Enhanced Financial Budgeting System Initialized - with Bi-weekly Support and Payroll Budget');
  console.log('Budget Data Available:', window.budgetData ? window.budgetData.length : 0, 'records');
});

// Form validation
document.addEventListener('submit', function(e) {
  const form = e.target;
  if (form.action && form.action.includes('budgets_actions.php')) {
    const department = form.querySelector('[name="department"]');
    const costCenter = form.querySelector('[name="cost_center"]');
    
    if (department && !department.value) {
      e.preventDefault();
      alert('Please select a department before submitting.');
      return false;
    }
    
    if (costCenter && !costCenter.value) {
      e.preventDefault();
      alert('Please select a cost center before submitting.');
      return false;
    }
  }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
  // Ctrl+Shift+F for forecast modal
  if (e.ctrlKey && e.shiftKey && e.key === 'F') {
    e.preventDefault();
    const forecastModal = new bootstrap.Modal(document.getElementById('budgetForecastModal'));
    forecastModal.show();
  }
  
  // Ctrl+Shift+A for add budget modal
  if (e.ctrlKey && e.shiftKey && e.key === 'A') {
    e.preventDefault();
    const addModal = new bootstrap.Modal(document.getElementById('addBudgetModal'));
    addModal.show();
  }
});

console.log('System Ready - Press Ctrl+Shift+F for AI Forecast, Ctrl+Shift+A for Add Budget');
</script>
</body>
</html>

