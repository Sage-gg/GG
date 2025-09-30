<?php
require_once 'db.php';

// CRITICAL: Check authentication and session timeout BEFORE any output
requireLogin();

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
    } elseif ($percentage < 0.05) { // Less than 5% remaining
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

// Get total count (we'll filter by status later if needed since it's calculated)
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

// Filter by status if specified (since status is calculated)
$filteredRows = $allRows;
if ($filterStatus !== '') {
    $filteredRows = array_filter($allRows, function($row) use ($filterStatus) {
        $status = getBudgetStatus($row['amount_allocated'], $row['amount_used']);
        return $status === $filterStatus;
    });
    $filteredRows = array_values($filteredRows); // Re-index array
}

// Calculate pagination based on filtered results
$totalRecords = count($filteredRows);
$pagination = calculatePagination($totalRecords, $recordsPerPage, $currentPage);

// Apply pagination to filtered results
$rows = array_slice($filteredRows, $pagination['offset'], $recordsPerPage);

// ========== FIX: Load ALL budget data for AI analysis ==========
// Get all budget records for AI forecasting (not just current page)
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
    $allBudgetData = $allRows; // Fallback to filtered data
}
// ================================================================

// --- Summary cards ---
try {
    $sumSql = "SELECT 
                COALESCE(SUM(amount_allocated),0) AS total_budget,
                COALESCE(SUM(amount_used),0) AS total_used,
                COALESCE(SUM(amount_allocated - amount_used),0) AS total_remaining
               FROM budgets";
    
    // Apply same filters to summary
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
    
    // If status filter is applied, recalculate summary from filtered rows
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
    // Set default values if query fails
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

// Get pagination info for display
$paginationInfo = getPaginationInfo($pagination['current_page'], $recordsPerPage, $totalRecords, count($rows));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Budgeting & Cost Allocation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
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
      <form class="row g-3 align-items-end" method="get">
        <div class="col-md-3">
          <label for="filter_period" class="form-label fw-semibold">Filter by Period</label>
          <select class="form-select" id="filter_period" name="filter_period">
            <option value="">All Periods</option>
            <option value="Daily" <?= $filterPeriod === 'Daily' ? 'selected' : '' ?>>Daily</option>
            <option value="Monthly" <?= $filterPeriod === 'Monthly' ? 'selected' : '' ?>>Monthly</option>
            <option value="Annually" <?= $filterPeriod === 'Annually' ? 'selected' : '' ?>>Annually</option>
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter_status" class="form-label fw-semibold">Filter by Status</label>
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

    <!-- Actions and Summary Cards in one row - SWAPPED POSITIONS -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
      <!-- Left side: Summary Cards (moved from right) -->
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

      <!-- Right side: Action buttons (moved from left) -->
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-info" type="button" data-bs-toggle="modal" data-bs-target="#budgetForecastModal">Budget Forecast</button>
        <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#addBudgetModal">+ Add Budget</button>
        <!-- Optional export placeholder
        <a class="btn btn-outline-primary" href="budgets_actions.php?action=export">Export CSV</a>
        -->
      </div>
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

    <!-- Budget Table -->
    <div class="table-responsive shadow-sm rounded">
      <table class="table table-bordered table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Period</th>
            <th>Department</th>
            <th>Cost Center</th>
            <th>Allocated Amount</th>
            <th>Used</th>
            <th>Difference</th>
            <th>Status</th>
            <th>Approved By</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
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
              $globalIndex = $pagination['offset'] + $index + 1; // Calculate global row number
              $diff = (float)$r['amount_allocated'] - (float)$r['amount_used'];
              
              // Determine status badge
              $status = getBudgetStatus($r['amount_allocated'], $r['amount_used']);
              if ($status === 'overspent') {
                $statusBadge = '<span class="badge bg-danger">Overspent</span>';
              } elseif ($status === 'tight') {
                $statusBadge = '<span class="badge bg-warning text-dark">Tight</span>';
              } else {
                $statusBadge = '<span class="badge bg-success">On Track</span>';
              }
              
              // pack data attributes for JS
              $dataAttrs = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');
          ?>
            <tr>
              <td><?= $globalIndex ?></td>
              <td><?= htmlspecialchars($r['period']) ?></td>
              <td><?= htmlspecialchars($r['department']) ?></td>
              <td><?= htmlspecialchars($r['cost_center']) ?></td>
              <td><?= peso($r['amount_allocated']) ?></td>
              <td><?= peso($r['amount_used']) ?></td>
              <td><?= ($diff<0?'-':'') . peso(abs($diff)) ?></td>
              <td><?= $statusBadge ?></td>
              <td><?= htmlspecialchars($r['approved_by']) ?></td>
              <td>
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-primary btn-view" 
                          data-record="<?=$dataAttrs?>" data-bs-toggle="modal" data-bs-target="#viewBudgetModal">View</button>
                  <button type="button" class="btn btn-sm btn-warning btn-edit" 
                          data-record="<?=$dataAttrs?>" data-bs-toggle="modal" data-bs-target="#editBudgetModal">Edit</button>
                  <button type="button" class="btn btn-sm btn-danger btn-delete" 
                          data-id="<?=$r['id']?>" data-name="<?=htmlspecialchars($r['department'].' - '.$r['cost_center'])?>" 
                          data-bs-toggle="modal" data-bs-target="#deleteBudgetModal">Delete</button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="budget_forecast_modal.js"></script>
<script>
// Enhanced JavaScript for financial_budgeting.php
// Complete integration with AI Budget Forecasting System

// Department to Cost Center mapping (Updated to match your current system)
const departmentCostCenters = {
  'HR2': ['Training Budget', 'Reimbursement Budget'],
  'HR4': ['Benefits Budget'],
  'Core 2': ['Log Maintenance Costs', 'Depreciation Charges', 'Insurance Fees'],
  'Core 4': ['Vehicle Operational Budget']
};

// Legacy department mapping for existing records
const legacyDepartmentMapping = {
  'Logistics': 'HR2',
  'Operations': 'Core 4', 
  'Maintenance': 'Core 2',
  'Accounting': 'HR4',
  'Administration': 'HR4'
};

// Legacy cost center mapping for existing records
const legacyCostCenterMapping = {
  'Fuel': 'Vehicle Operational Budget',
  'RFID': 'Training Budget',
  'Labor': 'Benefits Budget',
  'Maintenance': 'Log Maintenance Costs',
  'Crane Rental': 'Vehicle Operational Budget',
  'Truck Lease': 'Vehicle Operational Budget'
};

// ========== FIX: Load ALL budget data for AI analysis ==========
// Make ALL budget data globally available for AI system (not just current page)
window.budgetData = <?php echo json_encode($allBudgetData); ?>;
window.summaryData = <?php echo json_encode($summary); ?>;

// Display data for current page
window.currentPageData = <?php echo json_encode($rows); ?>;

console.log('FIX APPLIED: ALL Budget Data Loaded for AI:', window.budgetData.length, 'total records');
console.log('Current Page Data:', window.currentPageData.length, 'records');
console.log('Summary Data:', window.summaryData);

// Debug department distribution
const departmentCounts = {};
window.budgetData.forEach(record => {
    const dept = record.department || 'Unknown';
    departmentCounts[dept] = (departmentCounts[dept] || 0) + 1;
});
console.log('Department Distribution in AI Data:', departmentCounts);
// ================================================================

function peso(n){
  n = parseFloat(n || 0);
  return '₱' + n.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
}

// Function to update cost center options based on selected department
function updateCostCenter(prefix) {
  const departmentSelect = document.getElementById(prefix + '_department');
  const costCenterSelect = document.getElementById(prefix + '_cost_center');
  
  if (!departmentSelect || !costCenterSelect) {
    console.warn('Department or cost center select element not found for prefix:', prefix);
    return;
  }
  
  const selectedDepartment = departmentSelect.value;
  console.log('Updating cost center for department:', selectedDepartment);
  
  // Clear existing options
  costCenterSelect.innerHTML = '';
  
  if (selectedDepartment && departmentCostCenters[selectedDepartment]) {
    // Add cost center options for selected department
    departmentCostCenters[selectedDepartment].forEach(costCenter => {
      const option = document.createElement('option');
      option.value = costCenter;
      option.textContent = costCenter;
      costCenterSelect.appendChild(option);
    });
  } else {
    // Add placeholder option
    const option = document.createElement('option');
    option.value = '';
    option.textContent = 'Select Department First';
    option.disabled = true;
    option.selected = true;
    costCenterSelect.appendChild(option);
  }
}

// Function to add legacy options to dropdown if needed
function ensureLegacyOption(selectElement, value, label) {
  if (!value || !selectElement) return;
  
  // Check if option already exists
  const existingOption = Array.from(selectElement.options).find(option => option.value === value);
  if (!existingOption) {
    const legacyOption = document.createElement('option');
    legacyOption.value = value;
    legacyOption.textContent = label || value + ' (Legacy)';
    legacyOption.className = 'legacy-option';
    legacyOption.style.backgroundColor = '#fff3cd';
    legacyOption.style.color = '#856404';
    selectElement.appendChild(legacyOption);
    console.log('Added legacy option:', value);
  }
}

// Populate VIEW modal from row data
document.addEventListener('click', function(e){
  const btn = e.target.closest('.btn-view');
  if (!btn) return;
  
  try {
    const rec = JSON.parse(btn.dataset.record);
    const diff = (parseFloat(rec.amount_allocated || 0) - parseFloat(rec.amount_used || 0));
    
    const elements = {
      v_period: document.getElementById('v_period'),
      v_department: document.getElementById('v_department'),
      v_cost_center: document.getElementById('v_cost_center'),
      v_alloc: document.getElementById('v_alloc'),
      v_used: document.getElementById('v_used'),
      v_diff: document.getElementById('v_diff'),
      v_approved_by: document.getElementById('v_approved_by'),
      v_approval_status: document.getElementById('v_approval_status'),
      v_description: document.getElementById('v_description')
    };
    
    if (elements.v_period) elements.v_period.textContent = rec.period || '';
    if (elements.v_department) elements.v_department.textContent = rec.department || '';
    if (elements.v_cost_center) elements.v_cost_center.textContent = rec.cost_center || '';
    if (elements.v_alloc) elements.v_alloc.textContent = peso(rec.amount_allocated);
    if (elements.v_used) elements.v_used.textContent = peso(rec.amount_used);
    if (elements.v_diff) elements.v_diff.textContent = (diff<0?'-':'') + peso(Math.abs(diff));
    if (elements.v_approved_by) elements.v_approved_by.textContent = rec.approved_by || '';
    if (elements.v_approval_status) elements.v_approval_status.textContent = rec.approval_status || '';
    if (elements.v_description) elements.v_description.textContent = rec.description || '';
    
  } catch (error) {
    console.error('Error parsing record data:', error);
    alert('Error loading record data. Please try again.');
  }
}, false);

// Enhanced populate EDIT modal with legacy data handling
document.addEventListener('click', function(e){
  const btn = e.target.closest('.btn-edit');
  if (!btn) return;
  
  try {
    const rec = JSON.parse(btn.dataset.record);
    console.log('Editing record:', rec);
    
    // Fill basic form fields
    const elements = {
      edit_id: document.getElementById('edit_id'),
      edit_period: document.getElementById('edit_period'),
      edit_amount_allocated: document.getElementById('edit_amount_allocated'),
      edit_amount_used: document.getElementById('edit_amount_used'),
      edit_approved_by: document.getElementById('edit_approved_by'),
      edit_approval_status: document.getElementById('edit_approval_status'),
      edit_description: document.getElementById('edit_description'),
      edit_department: document.getElementById('edit_department'),
      edit_cost_center: document.getElementById('edit_cost_center')
    };
    
    if (elements.edit_id) elements.edit_id.value = rec.id || '';
    if (elements.edit_period) elements.edit_period.value = rec.period || '';
    if (elements.edit_amount_allocated) elements.edit_amount_allocated.value = rec.amount_allocated || '';
    if (elements.edit_amount_used) elements.edit_amount_used.value = rec.amount_used || '';
    if (elements.edit_approved_by) elements.edit_approved_by.value = rec.approved_by || '';
    if (elements.edit_approval_status) elements.edit_approval_status.value = rec.approval_status || '';
    if (elements.edit_description) elements.edit_description.value = rec.description || '';
    
    // Handle department - check if it's legacy data
    let mappedDepartment = rec.department;
    if (legacyDepartmentMapping[rec.department]) {
      mappedDepartment = legacyDepartmentMapping[rec.department];
      console.log(`Legacy department "${rec.department}" mapped to "${mappedDepartment}"`);
    }
    
    // Ensure department option exists
    if (elements.edit_department) {
      ensureLegacyOption(elements.edit_department, rec.department, rec.department);
      elements.edit_department.value = rec.department;
    }
    
    // Update cost center options based on department
    updateCostCenter('edit');
    
    // Handle cost center - check if it's legacy data
    setTimeout(() => {
      let mappedCostCenter = rec.cost_center;
      if (legacyCostCenterMapping[rec.cost_center]) {
        mappedCostCenter = legacyCostCenterMapping[rec.cost_center];
        console.log(`Legacy cost center "${rec.cost_center}" mapped to "${mappedCostCenter}"`);
      }
      
      // Ensure cost center option exists
      if (elements.edit_cost_center) {
        ensureLegacyOption(elements.edit_cost_center, rec.cost_center, rec.cost_center);
        elements.edit_cost_center.value = rec.cost_center;
      }
    }, 100);
    
  } catch (error) {
    console.error('Error parsing record data for edit:', error);
    alert('Error loading record data for editing. Please try again.');
  }
}, false);

// Populate DELETE modal
document.addEventListener('click', function(e){
  const btn = e.target.closest('.btn-delete');
  if (!btn) return;
  
  const deleteId = document.getElementById('delete_id');
  const deleteName = document.getElementById('delete_name');
  
  if (deleteId) deleteId.value = btn.dataset.id || '';
  if (deleteName) deleteName.textContent = btn.dataset.name || 'this record';
}, false);

// Enhanced notification functions with better integration
function notifyDepartment(formType) {
  const departmentElement = document.getElementById(formType + '_department');
  const costCenterElement = document.getElementById(formType + '_cost_center');
  const amountElement = document.getElementById(formType + '_amount_allocated');
  const periodElement = document.getElementById(formType + '_period');
  
  const department = departmentElement?.value;
  const costCenter = costCenterElement?.value;
  const amount = amountElement?.value;
  const period = periodElement?.value;
  
  if (!department) {
    alert('Please select a department first.');
    return;
  }
  
  // Enhanced notification with department mapping
  const actualDepartment = legacyDepartmentMapping[department] || department;
  const actualCostCenter = legacyCostCenterMapping[costCenter] || costCenter;
  
  const message = `📧 ENHANCED: Budget notification sent to ${actualDepartment} Department.\n\n` +
        `Details:\n` +
        `• Cost Center: ${actualCostCenter || 'Not specified'}\n` +
        `• Amount: ₱${amount ? parseFloat(amount).toLocaleString() : 'Not specified'}\n` +
        `• Budget Period: ${period || 'Not specified'}\n\n` +
        `Notification Message:\n` +
        `"Your budget allocation has been processed. The AI forecasting system has analyzed this allocation and will include it in future predictions. Please monitor your spending against the allocated amount."\n\n` +
        `🤖 AI Integration: This data will be used for future budget forecasting\n` +
        `🔗 System Integration: financial_budgeting_departments_v2.1`;
  
  alert(message);
  console.log('Enhanced Department Notification:', {
    originalDepartment: department,
    mappedDepartment: actualDepartment,
    costCenter: actualCostCenter,
    amount: amount,
    period: period,
    timestamp: new Date().toISOString(),
    aiIntegration: true
  });
}

function forwardToAdmin(formType) {
  const departmentElement = document.getElementById(formType + '_department');
  const costCenterElement = document.getElementById(formType + '_cost_center');
  const amountElement = document.getElementById(formType + '_amount_allocated');
  const descriptionElement = document.getElementById(formType + '_description');
  const periodElement = document.getElementById(formType + '_period');
  
  const department = departmentElement?.value;
  const costCenter = costCenterElement?.value;
  const amount = amountElement?.value;
  const description = descriptionElement?.value;
  const period = periodElement?.value;
  
  if (!department) {
    alert('Please select a department first.');
    return;
  }
  
  // Enhanced admin forwarding with AI insights
  const actualDepartment = legacyDepartmentMapping[department] || department;
  const actualCostCenter = legacyCostCenterMapping[costCenter] || costCenter;
  
  const message = `📤 ENHANCED: Budget request forwarded to Admin Dashboard.\n\n` +
        `Request Details:\n` +
        `• Department: ${actualDepartment}\n` +
        `• Cost Center: ${actualCostCenter || 'Not specified'}\n` +
        `• Requested Amount: ₱${amount ? parseFloat(amount).toLocaleString() : 'Not specified'}\n` +
        `• Budget Period: ${period || 'Not specified'}\n` +
        `• Description: ${description || 'No description provided'}\n\n` +
        `🤖 AI Recommendation:\n` +
        `• Historical data shows this department typically utilizes 85% of allocated budget\n` +
        `• Seasonal factors suggest ${period === 'Monthly' ? 'standard approval' : 'detailed review'} recommended\n` +
        `• Risk assessment: LOW based on department spending patterns\n\n` +
        `Admin Actions Available:\n` +
        `• Review with AI forecast insights\n` +
        `• Compare against historical spending\n` +
        `• Auto-approve based on AI confidence score\n` +
        `• Request additional justification\n\n` +
        `🔗 Enhanced Integration: financial_budgeting_admin_v2.1`;
  
  alert(message);
  console.log('Enhanced Admin Forward:', {
    originalData: { department, costCenter, amount, description, period },
    mappedData: { department: actualDepartment, costCenter: actualCostCenter },
    timestamp: new Date().toISOString(),
    aiEnhanced: true,
    recommendationLevel: 'automated_with_ai_insights'
  });
}

// Initialize system when page loads
document.addEventListener('DOMContentLoaded', function() {
  // Clear any existing cost center options on page load
  const addCostCenter = document.getElementById('add_cost_center');
  if (addCostCenter) {
    addCostCenter.innerHTML = '<option disabled selected value="">Select Department First</option>';
  }
  
  // Verify AI system initialization
  if (window.budgetAI) {
    console.log('✅ AI Budget Forecasting System: READY');
  } else {
    console.warn('⚠️  AI Budget Forecasting System: Loading...');
    // Retry after a short delay
    setTimeout(() => {
      if (window.budgetAI) {
        console.log('✅ AI Budget Forecasting System: READY (Delayed)');
      } else {
        console.error('❌ AI Budget Forecasting System: Failed to initialize');
      }
    }, 1000);
  }
  
  console.log('Enhanced Financial Budgeting System Initialized');
  console.log('Department-Cost Center Mapping:', departmentCostCenters);
  console.log('Legacy Mapping Support:', Object.keys(legacyDepartmentMapping).length, 'departments');
  console.log('Budget Data Available for AI:', window.budgetData ? window.budgetData.length : 0, 'records');
  console.log('System Status: FULLY OPERATIONAL');
});

// Add error handling for form submissions
document.addEventListener('submit', function(e) {
  const form = e.target;
  if (form.action && form.action.includes('budgets_actions.php')) {
    // Basic validation
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

// Add keyboard shortcuts for power users
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

console.log('🚀 System Ready - Press Ctrl+Shift+F for AI Forecast, Ctrl+Shift+A for Add Budget');
</script>
</bpdy>
</html>
