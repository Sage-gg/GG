<?php
require_once 'db.php';
// financial_budgeting.php - UPDATED WITH BUDGET REJECTION + AUTO-SYNC
// CRITICAL: Check authentication and session timeout BEFORE any output
requireModuleAccess('budgeting');

// Get permissions for this module
$perms = getModulePermission('budgeting');
$canCreate = $perms['can_create'];
$canEdit   = $perms['can_edit'];
$canDelete = $perms['can_delete'];
$canApprove = $perms['can_approve'];

// For managers, filter by department
if (isManager()) {
    $userDepartment = getUserDepartment();
}

// Include pagination helper
require_once 'financial_budgeting_pagination.php';

// --- Database check ---
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed. Please check your database configuration in db.php");
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

function peso($n) {
    return '₱' . number_format((float)$n, 2);
}

/**
 * Returns 'overspent' | 'tight' | 'on_track'
 */
function getBudgetStatus($allocated, $used) {
    $allocated = (float)$allocated;
    $used      = (float)$used;
    $remaining = $allocated - $used;

    if ($remaining < 0)                                          return 'overspent';
    if ($allocated > 0 && ($remaining / $allocated) < 0.05)      return 'tight';
    return 'on_track';
}

// ─── Pagination & Filter params ──────────────────────────────────────────────

$recordsPerPage = 5;
$currentPage    = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$filterPeriod   = isset($_GET['filter_period'])  ? trim($_GET['filter_period'])  : '';
$filterStatus   = isset($_GET['filter_status'])  ? trim($_GET['filter_status'])  : '';
$filterApproval = isset($_GET['filter_approval']) ? trim($_GET['filter_approval']) : '';

// ─── Shared WHERE builder ───────────────────────────────────────────────────

$whereParts = [];
$bindParams = [];
$bindTypes  = '';

if ($filterPeriod !== '') {
    $whereParts[]  = "period = ?";
    $bindParams[]  = $filterPeriod;
    $bindTypes    .= 's';
}

if ($filterApproval !== '') {
    $whereParts[]  = "approval_status = ?";
    $bindParams[]  = $filterApproval;
    $bindTypes    .= 's';
}

$whereClause = !empty($whereParts) ? " WHERE " . implode(" AND ", $whereParts) : '';

// ─── Summary cards ───────────────────────────────────────────────────────────
try {
    $sumSql  = "SELECT COALESCE(SUM(amount_allocated),0) AS total_budget,
                       COALESCE(SUM(amount_used),0)      AS total_used,
                       COALESCE(SUM(amount_allocated - amount_used),0) AS total_remaining
                FROM budgets" . $whereClause;

    $sumStmt = $conn->prepare($sumSql);
    if (!$sumStmt) throw new Exception("Summary prepare failed: " . $conn->error);
    if (!empty($bindParams)) $sumStmt->bind_param($bindTypes, ...$bindParams);
    $sumStmt->execute();
    $summary = $sumStmt->get_result()->fetch_assoc();
    $sumStmt->close();
} catch (Exception $e) {
    $summary = ['total_budget' => 0, 'total_used' => 0, 'total_remaining' => 0];
    error_log("Summary query error: " . $e->getMessage());
}

// ─── Fetch ALL rows ──────────────────────────────────────────────────────────
try {
    $sql  = "SELECT id, period, department, cost_center, amount_allocated, amount_used,
                    approved_by, approval_status, description, created_at
             FROM budgets" . $whereClause . " ORDER BY 
             CASE approval_status 
                 WHEN 'Pending' THEN 1 
                 WHEN 'Approved' THEN 2 
                 WHEN 'Rejected' THEN 3 
             END, created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Main prepare failed: " . $conn->error);
    if (!empty($bindParams)) $stmt->bind_param($bindTypes, ...$bindParams);
    $stmt->execute();
    $allRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    die("Database query failed: " . $e->getMessage());
}

// ─── Apply status filter in PHP ─────────────────────────────────────────────
$filteredRows = $allRows;
if ($filterStatus !== '') {
    $filteredRows = array_values(array_filter($allRows, function ($row) use ($filterStatus) {
        return getBudgetStatus($row['amount_allocated'], $row['amount_used']) === $filterStatus;
    }));
}

// Recalculate summary if status filter is active
if ($filterStatus !== '') {
    $summary = [
        'total_budget'    => array_sum(array_column($filteredRows, 'amount_allocated')),
        'total_used'      => array_sum(array_column($filteredRows, 'amount_used')),
        'total_remaining' => 0,
    ];
    $summary['total_remaining'] = $summary['total_budget'] - $summary['total_used'];
}

// ─── Count pending approvals ─────────────────────────────────────────────────
$pendingCount = count(array_filter($allRows, fn($r) => $r['approval_status'] === 'Pending'));

// ─── Pagination slice ────────────────────────────────────────────────────────
$totalRecords = count($filteredRows);
$pagination   = calculatePagination($totalRecords, $recordsPerPage, $currentPage);
$rows         = array_slice($filteredRows, $pagination['offset'], $recordsPerPage);

// ─── Load allocations ────────────────────────────────────────────────────────
$pageIds       = array_column($rows, 'id');
$allocations   = [];

if (!empty($pageIds)) {
    try {
        $placeholders = implode(',', array_fill(0, count($pageIds), '?'));
        $allocSql     = "SELECT * FROM budget_allocations WHERE budget_id IN ($placeholders) ORDER BY created_at ASC";
        $allocStmt    = $conn->prepare($allocSql);
        if ($allocStmt) {
            $allocStmt->bind_param(str_repeat('i', count($pageIds)), ...$pageIds);
            $allocStmt->execute();
            foreach ($allocStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $a) {
                $allocations[$a['budget_id']][] = $a;
            }
            $allocStmt->close();
        }
    } catch (Exception $e) {
        error_log("Allocations query error: " . $e->getMessage());
    }
}

$allBudgetData = $filteredRows ?: $allRows;
$paginationInfo = getPaginationInfo($pagination['current_page'], $recordsPerPage, $totalRecords, count($rows));

// ─── ACTION-REQUIRED ALERTS ──────────────────────────────────────────────────
$actionAlerts = [];
foreach ($filteredRows as $row) {
    $budgetStatus = getBudgetStatus($row['amount_allocated'], $row['amount_used']);
    $label = htmlspecialchars($row['department'] . ' – ' . $row['cost_center']);

    if ($budgetStatus === 'overspent') {
        $over = (float)$row['amount_used'] - (float)$row['amount_allocated'];
        $actionAlerts[] = [
            'type'    => 'overspent',
            'icon'    => 'bi-exclamation-triangle-fill',
            'color'   => 'danger',
            'label'   => $label,
            'message' => $label . ' is <strong>overspent</strong> by ' . peso($over) . '.',
        ];
    } elseif ($budgetStatus === 'tight') {
        $rem = (float)$row['amount_allocated'] - (float)$row['amount_used'];
        $actionAlerts[] = [
            'type'    => 'tight',
            'icon'    => 'bi-clock-warning',
            'color'   => 'warning',
            'label'   => $label,
            'message' => $label . ' has only <strong>' . peso($rem) . '</strong> remaining — budget is <strong>tight</strong>.',
        ];
    }

    if (strtolower($row['approval_status'] ?? '') === 'pending' || $row['approval_status'] === '') {
        $actionAlerts[] = [
            'type'    => 'pending',
            'icon'    => 'bi-clock',
            'color'   => 'info',
            'label'   => $label,
            'message' => $label . ' is still <strong>pending approval</strong>.',
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Budgeting &amp; Cost Allocation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <script>
    window.SESSION_TIMEOUT = <?php echo SESSION_TIMEOUT * 1000; ?>;
  </script>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/style.css" />

  <style>
    /* Summary cards */
    .summary-card {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
      padding: 0.5rem 0.75rem;
      text-align: center;
      min-width: 110px;
    }
    .summary-label  { font-size: .75rem; color: #6c757d; font-weight: 500; margin-bottom: .25rem; line-height: 1; }
    .summary-value  { font-size: .875rem; font-weight: 600; line-height: 1; }

    /* Pending badge pulse */
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }
    .pending-badge {
      animation: pulse 2s infinite;
    }

    /* Filters */
    .filter-section {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
      padding: 1rem;
      margin-bottom: 1.5rem;
    }
    .pagination-info { font-size: .875rem; color: #6c757d; }

    /* Expandable rows */
    .main-row            { cursor: pointer; transition: background-color .2s; }
    .main-row:hover      { background-color: #f8f9fa; }
    .main-row.expanded   { background-color: #e7f3ff; }
    .detail-row          { display: none; background-color: #f8f9fa; }
    .detail-row.show     { display: table-row; }
    .detail-content      { padding: 20px; border-left: 4px solid #0d6efd; }
    .detail-section      { margin-bottom: 15px; }
    .detail-section h6   { color: #0d6efd; font-weight: 600; margin-bottom: 10px; border-bottom: 2px solid #dee2e6; padding-bottom: 5px; }
    .detail-grid         { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; }
    .detail-item         { display: flex; padding: 8px; background: #fff; border-radius: 4px; border: 1px solid #dee2e6; }
    .detail-label        { font-weight: 600; color: #495057; min-width: 140px; margin-right: 10px; }
    .detail-value        { color: #212529; flex: 1; }
    .expand-icon         { transition: transform .3s; display: inline-block; }
    .expand-icon.rotated { transform: rotate(90deg); }

    /* Allocation table */
    .alloc-table         { margin-top: 6px; font-size: .85rem; }
    .alloc-table th      { background: #e9ecef; font-weight: 600; padding: 5px 8px; }
    .alloc-table td      { padding: 5px 8px; border-top: 1px solid #dee2e6; }
    .alloc-empty         { color: #6c757d; font-style: italic; font-size: .82rem; }

    /* Allocation modal */
    .alloc-progress-wrap { margin-top: 12px; }
    .alloc-progress-labels {
      display: flex; justify-content: space-between; font-size: .78rem; color: #6c757d; margin-bottom: 4px;
    }
    .alloc-progress .progress-bar { transition: width .4s ease; }
    .alloc-line          { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
    .alloc-line input[type="text"]   { flex: 1; min-width: 0; }
    .alloc-line input[type="number"] { width: 130px; }
    .alloc-line .btn-remove-line     { flex-shrink: 0; }

    /* Action alerts */
    .action-alert-panel {
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
      margin-bottom: 1.25rem;
      overflow: hidden;
    }
    .action-alert-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #fff3cd;
      border-bottom: 1px solid #ffe69c;
      padding: 0.55rem 0.85rem;
      cursor: pointer;
      user-select: none;
    }
    .action-alert-header:hover { background: #ffe8a3; }
    .action-alert-header-left  { display: flex; align-items: center; gap: 0.55rem; }
    .action-alert-header-left .badge { font-size: .72rem; }
    .action-alert-header-title { font-weight: 600; font-size: .88rem; color: #664d03; }
    .action-alert-chevron      { transition: transform .25s; font-size: .85rem; color: #664d03; }
    .action-alert-chevron.open { transform: rotate(90deg); }
    .action-alert-body         { display: none; max-height: 0; overflow: hidden; transition: max-height .3s ease; }
    .action-alert-body.open    { display: block; max-height: 600px; }
    .action-alert-item {
      display: flex;
      align-items: flex-start;
      gap: 0.6rem;
      padding: 0.5rem 0.85rem;
      border-bottom: 1px solid #f0f0f0;
      font-size: .84rem;
    }
    .action-alert-item:last-child { border-bottom: none; }
    .action-alert-item .ai-icon   { flex-shrink: 0; margin-top: 2px; font-size: 1rem; }
    .action-alert-item.ai-danger  .ai-icon { color: #dc3545; }
    .action-alert-item.ai-warning .ai-icon { color: #ffc107; }
    .action-alert-item.ai-info    .ai-icon { color: #0dcaf0; }
    .action-alert-item .ai-text   { line-height: 1.4; color: #333; }

    .summary-card-btn {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
      padding: 0.5rem 0.75rem;
      text-align: center;
      min-width: 110px;
      cursor: pointer;
      transition: box-shadow .2s, transform .15s;
    }
    .summary-card-btn:hover  { box-shadow: 0 2px 8px rgba(13,110,253,.25); transform: translateY(-2px); }
    .summary-card-btn:active { transform: translateY(0); }
  </style>
</head>
<body>

<?php include 'sidebar_navbar.php'; ?>

<div class="main-content">
  <div class="container-fluid mt-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="fw-bold">Finance - Budgeting &amp; Approval</h2>
      <div class="d-flex gap-2">
        <a href="hr_budget_dashboard.php" class="btn btn-outline-secondary">
          <i class="bi bi-building"></i> HR Dashboard
        </a>
        <a href="compliance_dashboard.php" class="btn btn-outline-dark">
          <i class="bi bi-shield-check"></i> Compliance Dashboard
        </a>
      </div>
    </div>

    <!-- Flash messages -->
    <?php if (isset($_SESSION['flash'])): ?>
      <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show" role="alert">
        <?= $_SESSION['flash']['msg'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <!-- Pending Approvals Alert -->
    <?php if ($pendingCount > 0): ?>
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Pending Approvals:</strong> You have <?= $pendingCount ?> budget request<?= $pendingCount > 1 ? 's' : '' ?> waiting for approval.
        <a href="?filter_approval=Pending" class="alert-link">View pending requests</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Action-Required Alert Panel -->
    <?php if (!empty($actionAlerts)): ?>
    <div class="action-alert-panel" id="actionAlertPanel">
      <div class="action-alert-header" onclick="toggleActionAlerts()">
        <div class="action-alert-header-left">
          <i class="bi bi-bell-fill text-warning"></i>
          <span class="action-alert-header-title">Action Required</span>
          <span class="badge bg-danger text-white"><?= count($actionAlerts) ?></span>
        </div>
        <i class="bi bi-chevron-right action-alert-chevron open" id="actionAlertChevron"></i>
      </div>
      <div class="action-alert-body open" id="actionAlertBody">
        <?php foreach ($actionAlerts as $alert): ?>
        <div class="action-alert-item ai-<?= $alert['color'] ?>">
          <i class="bi <?= $alert['icon'] ?> ai-icon"></i>
          <div class="ai-text"><?= $alert['message'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="filter-section">
      <h6 class="mb-3 text-muted">Filter Budgets</h6>
      <form class="row g-3 align-items-end" method="get">
        <div class="col-md-3">
          <label for="filter_period" class="form-label small">Period</label>
          <select class="form-select" id="filter_period" name="filter_period">
            <option value="">All Periods</option>
            <option value="Daily"      <?= $filterPeriod === 'Daily'      ? 'selected' : '' ?>>Daily</option>
            <option value="Bi-weekly"  <?= $filterPeriod === 'Bi-weekly'  ? 'selected' : '' ?>>Bi-weekly</option>
            <option value="Monthly"    <?= $filterPeriod === 'Monthly'    ? 'selected' : '' ?>>Monthly</option>
            <option value="Annually"   <?= $filterPeriod === 'Annually'   ? 'selected' : '' ?>>Annually</option>
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter_status" class="form-label small">Budget Status</label>
          <select class="form-select" id="filter_status" name="filter_status">
            <option value="">All Status</option>
            <option value="on_track" <?= $filterStatus === 'on_track' ? 'selected' : '' ?>>On Track</option>
            <option value="tight"    <?= $filterStatus === 'tight'    ? 'selected' : '' ?>>Tight</option>
            <option value="overspent" <?= $filterStatus === 'overspent' ? 'selected' : '' ?>>Overspent</option>
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter_approval" class="form-label small">Approval Status</label>
          <select class="form-select" id="filter_approval" name="filter_approval">
            <option value="">All</option>
            <option value="Pending" <?= $filterApproval === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Approved" <?= $filterApproval === 'Approved' ? 'selected' : '' ?>>Approved</option>
            <option value="Rejected" <?= $filterApproval === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
          </select>
        </div>
        <div class="col-md-3">
          <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">Apply</button>
            <?php if ($filterPeriod !== '' || $filterStatus !== '' || $filterApproval !== ''): ?>
              <a class="btn btn-outline-secondary" href="financial_budgeting.php">Clear</a>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>

    <!-- Actions Bar + Summary Cards -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
      <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="summary-card-btn" data-bs-toggle="modal" data-bs-target="#bdModal_budget" title="View Total Budget Breakdown">
          <div class="summary-label">Total Budget</div>
          <div class="summary-value text-primary"><?= peso($summary['total_budget']) ?></div>
        </button>
        <button type="button" class="summary-card-btn" data-bs-toggle="modal" data-bs-target="#bdModal_spent" title="View Total Spent Breakdown">
          <div class="summary-label">Total Spent</div>
          <div class="summary-value text-danger"><?= peso($summary['total_used']) ?></div>
        </button>
        <button type="button" class="summary-card-btn" data-bs-toggle="modal" data-bs-target="#bdModal_remaining" title="View Remaining Budget Breakdown">
          <div class="summary-label">Remaining</div>
          <div class="summary-value <?= $summary['total_remaining'] < 0 ? 'text-danger' : 'text-success' ?>"><?= peso($summary['total_remaining']) ?></div>
        </button>
        <?php if ($pendingCount > 0): ?>
          <div class="summary-card pending-badge">
            <div class="summary-label">Pending Approval</div>
            <div class="summary-value text-warning"><?= $pendingCount ?></div>
          </div>
        <?php endif; ?>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-info"    type="button" data-bs-toggle="modal" data-bs-target="#budgetForecastModal">Budget Forecast</button>
        <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#addBudgetModal">+ Add Budget</button>
      </div>
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <i class="bi bi-info-circle-fill me-2"></i>
      <strong>Workflow:</strong> When you approve a budget request, it will automatically sync back to the requesting department's dashboard with allocated budget details.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <!-- Pagination Info -->
    <?php if ($totalRecords > 0): ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="pagination-info">
        Showing <?= $paginationInfo['start'] ?> to <?= $paginationInfo['end'] ?> of <?= $paginationInfo['total'] ?> entries
        <?php if ($filterPeriod !== '' || $filterStatus !== '' || $filterApproval !== ''): ?>(filtered)<?php endif; ?>
      </div>
      <div class="pagination-info">Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?></div>
    </div>
    <?php endif; ?>

    <!-- Budget Table -->
    <div class="table-responsive shadow-sm rounded">
      <table class="table table-bordered table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:30px;"></th>
            <th>#</th>
            <th>Period</th>
            <th>Department</th>
            <th>Cost Center</th>
            <th class="text-end">Allocated</th>
            <th class="text-end">Used</th>
            <th class="text-end">Difference</th>
            <th>Status</th>
            <th>Approval</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="budgets_tbody">
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="11" class="text-center text-muted py-4">
              <?= ($filterPeriod !== '' || $filterStatus !== '' || $filterApproval !== '')
                  ? 'No budgets match your filter criteria.'
                  : 'No budgets found. Add one to get started.' ?>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $index => $r):
              $globalIndex = $pagination['offset'] + $index + 1;
              $diff        = (float)$r['amount_allocated'] - (float)$r['amount_used'];
              $status      = getBudgetStatus($r['amount_allocated'], $r['amount_used']);

              $statusBadge = match($status) {
                  'overspent' => '<span class="badge bg-danger">Overspent</span>',
                  'tight'     => '<span class="badge bg-warning text-dark">Tight</span>',
                  default     => '<span class="badge bg-success">On Track</span>',
              };

              $approvalBadge = match($r['approval_status']) {
                  'Approved' => '<span class="badge bg-success">Approved</span>',
                  'Rejected' => '<span class="badge bg-danger">Rejected</span>',
                  default    => '<span class="badge bg-warning text-dark pending-badge">Pending</span>',
              };

              $dataAttrs     = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');
              $rowAllocations = $allocations[$r['id']] ?? [];
              $totalAllocated = array_sum(array_column($rowAllocations, 'allocated_amount'));
          ?>
            <tr class="main-row" data-id="<?= $r['id'] ?>">
              <td class="text-center toggle-cell">
                <i class="bi bi-chevron-right expand-icon"></i>
              </td>
              <td class="toggle-cell"><?= $globalIndex ?></td>
              <td class="toggle-cell"><?= htmlspecialchars($r['period']) ?></td>
              <td class="toggle-cell"><?= htmlspecialchars($r['department']) ?></td>
              <td class="toggle-cell"><?= htmlspecialchars($r['cost_center']) ?></td>
              <td class="toggle-cell text-end"><?= peso($r['amount_allocated']) ?></td>
              <td class="toggle-cell text-end"><?= peso($r['amount_used']) ?></td>
              <td class="toggle-cell text-end <?= $diff < 0 ? 'text-danger' : '' ?>"><?= ($diff < 0 ? '-' : '') . peso(abs($diff)) ?></td>
              <td class="toggle-cell"><?= $statusBadge ?></td>
              <td class="toggle-cell"><?= $approvalBadge ?></td>
              <td class="actions-cell">
                <div class="btn-group-vertical btn-group-sm gap-1">
                  <?php if ($r['approval_status'] === 'Pending'): ?>
                    <button type="button" class="btn btn-success btn-sm" 
                            onclick="approveRequest(<?= $r['id'] ?>, '<?= htmlspecialchars($r['department'] . ' - ' . $r['cost_center']) ?>')">
                      <i class="bi bi-check-circle"></i> Approve
                    </button>
                    <!-- NEW: Reject Button -->
                    <button type="button" class="btn btn-danger btn-sm" 
                            onclick="rejectRequest(<?= $r['id'] ?>, '<?= htmlspecialchars($r['department'] . ' - ' . $r['cost_center']) ?>')">
                      <i class="bi bi-x-circle"></i> Reject
                    </button>
                  <?php endif; ?>
                  <button type="button" class="btn btn-sm btn-warning btn-edit"
                          data-record="<?= $dataAttrs ?>"
                          data-bs-toggle="modal" data-bs-target="#editBudgetModal">Edit</button>
                  <?php if ($r['approval_status'] === 'Approved'): ?>
                    <button type="button" class="btn btn-sm btn-primary btn-allocate"
                            data-budget-id="<?= $r['id'] ?>"
                            data-budget-amount="<?= $r['amount_allocated'] ?>"
                            data-budget-label="<?= htmlspecialchars($r['department'] . ' – ' . $r['cost_center']) ?>"
                            data-total-allocated="<?= $totalAllocated ?>"
                            data-bs-toggle="modal" data-bs-target="#allocateBudgetModal">Allocate</button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>

            <!-- Detail Row -->
            <tr class="detail-row" id="detail-<?= $r['id'] ?>">
              <td colspan="11">
                <div class="detail-content">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="detail-section">
                        <h6><i class="bi bi-building"></i> Budget Information</h6>
                        <div class="detail-grid">
                          <div class="detail-item">
                            <div class="detail-label">Period:</div>
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
                            <div class="detail-label"><strong>Allocated:</strong></div>
                            <div class="detail-value"><strong><?= peso($r['amount_allocated']) ?></strong></div>
                          </div>
                          <div class="detail-item">
                            <div class="detail-label">Used:</div>
                            <div class="detail-value text-danger"><?= peso($r['amount_used']) ?></div>
                          </div>
                          <div class="detail-item">
                            <div class="detail-label">Remaining:</div>
                            <div class="detail-value <?= $diff < 0 ? 'text-danger' : 'text-success' ?>"><?= ($diff < 0 ? '-' : '') . peso(abs($diff)) ?></div>
                          </div>
                        </div>
                      </div>
                    </div>

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
                            <div class="detail-value"><?= $approvalBadge ?></div>
                          </div>
                          <div class="detail-item">
                            <div class="detail-label">Budget Status:</div>
                            <div class="detail-value"><?= $statusBadge ?></div>
                          </div>
                        </div>
                      </div>

                      <div class="detail-section">
                        <h6><i class="bi bi-file-text"></i> Description / Justification</h6>
                        <div class="detail-item">
                          <div class="detail-value"><?= nl2br(htmlspecialchars($r['description'] ?: 'No description provided')) ?></div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Cost Allocations -->
                  <?php if (!empty($rowAllocations)): ?>
                  <div class="detail-section mt-3">
                    <h6><i class="bi bi-pie-chart"></i> Cost Allocations
                      <span class="badge bg-secondary ms-2"><?= count($rowAllocations) ?> item<?= count($rowAllocations) !== 1 ? 's' : '' ?></span>
                    </h6>
                    <table class="table table-sm table-bordered alloc-table mb-2">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Allocation Label</th>
                          <th class="text-end">Amount</th>
                          <th>Notes</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($rowAllocations as $ai => $a): ?>
                          <tr>
                            <td><?= $ai + 1 ?></td>
                            <td><?= htmlspecialchars($a['label']) ?></td>
                            <td class="text-end"><?= peso($a['allocated_amount']) ?></td>
                            <td><?= htmlspecialchars($a['notes'] ?? '') ?: '—' ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                  <?php endif; ?>
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
        $qp = [];
        if ($filterPeriod !== '') $qp['filter_period'] = $filterPeriod;
        if ($filterStatus !== '') $qp['filter_status'] = $filterStatus;
        if ($filterApproval !== '') $qp['filter_approval'] = $filterApproval;
        echo generatePagination($pagination['current_page'], $pagination['total_pages'], 'financial_budgeting.php', $qp);
    }
    ?>
  </div>
</div>

<!-- Include Modals -->
<?php include 'financial_budgeting_modals.php'; ?>

<!-- Cost Allocation Modal -->
<div class="modal fade" id="allocateBudgetModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-outline-primary border-primary">
        <h5 class="modal-title fw-bold"><i class="bi bi-pie-chart me-2"></i>Allocate Budget</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="alloc_budget_id">
        <input type="hidden" id="alloc_budget_amount">

        <div class="card card-body bg-light mb-3 p-3">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <div class="text-muted small">Budget</div>
              <div class="fw-semibold" id="alloc_budget_label">–</div>
            </div>
            <div class="text-end">
              <div class="text-muted small">Total Budget</div>
              <div class="fw-bold text-primary" id="alloc_budget_total">₱0.00</div>
            </div>
            <div class="text-end">
              <div class="text-muted small">Already Allocated</div>
              <div class="fw-bold text-warning" id="alloc_already_allocated">₱0.00</div>
            </div>
            <div class="text-end">
              <div class="text-muted small">Available to Allocate</div>
              <div class="fw-bold text-success" id="alloc_available">₱0.00</div>
            </div>
          </div>
        </div>

        <div class="alloc-progress-wrap mb-3">
          <div class="alloc-progress-labels">
            <span>Allocated: <strong id="alloc_pct_label">0%</strong></span>
            <span>Remaining: <strong id="alloc_rem_pct_label">100%</strong></span>
          </div>
          <div class="progress alloc-progress" style="height:18px;">
            <div class="progress-bar bg-primary" id="alloc_progress_bar" role="progressbar" style="width:0%;"></div>
          </div>
        </div>

        <div id="alloc_existing_list" class="mb-3"></div>

        <h6 class="text-muted small fw-semibold mb-2">ADD NEW ALLOCATIONS</h6>
        <div id="alloc_lines_container"></div>

        <button type="button" class="btn btn-sm btn-outline-primary" id="btn_add_alloc_line">
          <i class="bi bi-plus-circle me-1"></i>Add Another Line
        </button>

        <div class="alert alert-danger mt-3 d-none" id="alloc_warning" role="alert"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
        <button class="btn btn-primary" id="btn_save_allocations" type="button"><i class="bi bi-floppy-disk me-1"></i>Save Allocations</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="session_check.js"></script>
<script src="budget_forecast_modal.js"></script>

<script>
// Department/Cost Center mapping
const departmentCostCenters = {
  'HR':   ['Training Budget','Reimbursement Budget','Benefits Budget','Payroll Budget'],
  'Core': ['Log Maintenance Costs','Depreciation Charges','Insurance Fees','Vehicle Operational Budget']
};

window.budgetData      = <?php echo json_encode($allBudgetData); ?>;
window.summaryData     = <?php echo json_encode($summary); ?>;
window.currentPageData = <?php echo json_encode($rows); ?>;
window.allocationsData = <?php echo json_encode($allocations); ?>;

function peso(n) {
  return '₱' + parseFloat(n || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
}

function toggleActionAlerts() {
  const body    = document.getElementById('actionAlertBody');
  const chevron = document.getElementById('actionAlertChevron');
  if (!body || !chevron) return;
  body.classList.toggle('open');
  chevron.classList.toggle('open');
}

// Approve budget request
function approveRequest(budgetId, label) {
  if (!confirm(`Approve budget request for: ${label}?\n\nThis will sync the approved budget back to the requesting department's dashboard.`)) {
    return;
  }
  
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'budgets_actions.php';
  
  const actionInput = document.createElement('input');
  actionInput.type = 'hidden';
  actionInput.name = 'action';
  actionInput.value = 'quick_approve';
  form.appendChild(actionInput);
  
  const idInput = document.createElement('input');
  idInput.type = 'hidden';
  idInput.name = 'id';
  idInput.value = budgetId;
  form.appendChild(idInput);
  
  document.body.appendChild(form);
  form.submit();
}

// Reject budget request function - NEW
function rejectRequest(budgetId, label) {
  document.getElementById('reject_id').value = budgetId;
  document.getElementById('reject_name').textContent = label;
  document.getElementById('rejection_reason').value = '';
  
  const modal = new bootstrap.Modal(document.getElementById('rejectBudgetModal'));
  modal.show();
}

// Optional: Add validation before submit - NEW
document.getElementById('rejectBudgetForm')?.addEventListener('submit', function(e) {
  const reason = document.getElementById('rejection_reason').value.trim();
  if (reason.length < 10) {
    e.preventDefault();
    alert('Please provide a more detailed reason for rejection (at least 10 characters).');
    return false;
  }
  
  return confirm(`Confirm rejection of this budget request?\n\nThis action will notify the department with your rejection reason.`);
});

// Row toggle
document.getElementById('budgets_tbody').addEventListener('click', function (e) {
  if (e.target.closest('.actions-cell')) return;

  const mainRow = e.target.closest('.main-row');
  if (!mainRow) return;

  const id        = mainRow.dataset.id;
  const detailRow = document.getElementById('detail-' + id);
  const icon      = mainRow.querySelector('.expand-icon');
  if (!detailRow) return;

  if (detailRow.classList.contains('show')) {
    detailRow.classList.remove('show');
    mainRow.classList.remove('expanded');
    icon.classList.remove('rotated');
  } else {
    document.querySelectorAll('.detail-row.show').forEach(r => r.classList.remove('show'));
    document.querySelectorAll('.main-row.expanded').forEach(r => r.classList.remove('expanded'));
    document.querySelectorAll('.expand-icon.rotated').forEach(i => i.classList.remove('rotated'));
    detailRow.classList.add('show');
    mainRow.classList.add('expanded');
    icon.classList.add('rotated');
  }
});

// Edit modal
document.getElementById('budgets_tbody').addEventListener('click', function (e) {
  const btn = e.target.closest('.btn-edit');
  if (!btn) return;
  e.stopPropagation();

  const rec = JSON.parse(btn.dataset.record);

  document.getElementById('edit_id').value               = rec.id || '';
  document.getElementById('edit_period').value           = rec.period || '';
  document.getElementById('edit_amount_allocated').value = rec.amount_allocated || '';
  document.getElementById('edit_amount_used').value      = rec.amount_used || '';
  document.getElementById('edit_approved_by').value      = rec.approved_by || '';
  document.getElementById('edit_approval_status').value  = rec.approval_status || '';
  document.getElementById('edit_description').value      = rec.description || '';
  document.getElementById('edit_department').value       = rec.department;
  
  updateCostCenter('edit');

  setTimeout(() => {
    document.getElementById('edit_cost_center').value = rec.cost_center;
  }, 100);
});

function updateCostCenter(prefix) {
  const dept = document.getElementById(prefix + '_department');
  const cc   = document.getElementById(prefix + '_cost_center');
  if (!dept || !cc) return;

  cc.innerHTML = '';
  const options = departmentCostCenters[dept.value];
  if (options) {
    options.forEach(label => {
      const o = document.createElement('option');
      o.value = label;
      o.textContent = label;
      cc.appendChild(o);
    });
  }
}

// Allocation modal logic
let allocBudgetId      = null;
let allocBudgetAmount  = 0;
let allocAlreadyDone   = 0;
let allocExisting      = [];

document.getElementById('budgets_tbody').addEventListener('click', function (e) {
  const btn = e.target.closest('.btn-allocate');
  if (!btn) return;
  e.stopPropagation();

  allocBudgetId     = parseInt(btn.dataset.budgetId);
  allocBudgetAmount = parseFloat(btn.dataset.budgetAmount);
  allocAlreadyDone  = parseFloat(btn.dataset.totalAllocated);
  allocExisting     = window.allocationsData[allocBudgetId] || [];

  document.getElementById('alloc_budget_id').value    = allocBudgetId;
  document.getElementById('alloc_budget_amount').value = allocBudgetAmount;
  document.getElementById('alloc_budget_label').textContent = btn.dataset.budgetLabel;
  document.getElementById('alloc_budget_total').textContent = peso(allocBudgetAmount);
  document.getElementById('alloc_already_allocated').textContent = peso(allocAlreadyDone);
  document.getElementById('alloc_available').textContent = peso(allocBudgetAmount - allocAlreadyDone);

  renderExistingAllocations();
  resetNewLines();
  updateAllocProgress();
  document.getElementById('alloc_warning').classList.add('d-none');
});

function renderExistingAllocations() {
  const container = document.getElementById('alloc_existing_list');
  if (allocExisting.length === 0) {
    container.innerHTML = '<p class="alloc-empty">No existing allocations.</p>';
    return;
  }

  let html = '<h6 class="text-muted small fw-semibold mb-2">EXISTING ALLOCATIONS</h6>';
  html += '<table class="table table-sm table-bordered alloc-table mb-3"><thead><tr>' +
          '<th>#</th><th>Label</th><th class="text-end">Amount</th><th>Notes</th><th></th>' +
          '</tr></thead><tbody>';

  allocExisting.forEach((a, i) => {
    html += `<tr>
      <td>${i+1}</td>
      <td>${escHtml(a.label)}</td>
      <td class="text-end">${peso(a.allocated_amount)}</td>
      <td>${escHtml(a.notes || '—')}</td>
      <td><button type="button" class="btn btn-sm btn-outline-danger btn-del-existing" data-alloc-id="${a.id}"><i class="bi bi-trash"></i></button></td>
    </tr>`;
  });

  html += '</tbody></table>';
  container.innerHTML = html;

  container.querySelectorAll('.btn-del-existing').forEach(btn => {
    btn.addEventListener('click', function () {
      const allocId = parseInt(this.dataset.allocId);
      deleteAllocation(allocId);
    });
  });
}

function resetNewLines() {
  const container = document.getElementById('alloc_lines_container');
  container.innerHTML = '';
  addNewAllocLine();
}

let lineCounter = 0;
function addNewAllocLine() {
  lineCounter++;
  const id = 'alloc_line_' + lineCounter;
  const container = document.getElementById('alloc_lines_container');

  const div = document.createElement('div');
  div.className = 'alloc-line';
  div.id = id;
  div.innerHTML = `
    <input type="text"   class="form-control form-control-sm" placeholder="Allocation label" required>
    <input type="number" class="form-control form-control-sm" placeholder="₱ Amount" step="0.01" min="0" required>
    <input type="text"   class="form-control form-control-sm" placeholder="Notes (optional)" style="flex:0.7;">
    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line" title="Remove"><i class="bi bi-x-lg"></i></button>
  `;
  container.appendChild(div);

  div.querySelector('input[type="number"]').addEventListener('input', updateAllocProgress);

  div.querySelector('.btn-remove-line').addEventListener('click', function () {
    if (document.querySelectorAll('.alloc-line').length > 1) {
      div.remove();
      updateAllocProgress();
    }
  });
}

document.getElementById('btn_add_alloc_line').addEventListener('click', addNewAllocLine);

function updateAllocProgress() {
  const newTotal = getNewLinesTotal();
  const grandTotal = allocAlreadyDone + newTotal;
  const pct = allocBudgetAmount > 0 ? Math.min((grandTotal / allocBudgetAmount) * 100, 100) : 0;
  const remPct = Math.max(100 - pct, 0);

  document.getElementById('alloc_progress_bar').style.width = pct + '%';
  document.getElementById('alloc_progress_bar').setAttribute('aria-valuenow', pct);
  document.getElementById('alloc_progress_bar').className =
    'progress-bar ' + (pct > 100 ? 'bg-danger' : pct > 80 ? 'bg-warning' : 'bg-primary');

  document.getElementById('alloc_pct_label').textContent     = pct.toFixed(1) + '%';
  document.getElementById('alloc_rem_pct_label').textContent = remPct.toFixed(1) + '%';
  document.getElementById('alloc_available').textContent     = peso(allocBudgetAmount - grandTotal);

  const avail = allocBudgetAmount - grandTotal;
  document.getElementById('alloc_available').className = 'fw-bold ' + (avail < 0 ? 'text-danger' : 'text-success');
}

function getNewLinesTotal() {
  let sum = 0;
  document.querySelectorAll('.alloc-line input[type="number"]').forEach(inp => {
    sum += parseFloat(inp.value) || 0;
  });
  return sum;
}

document.getElementById('btn_save_allocations').addEventListener('click', function () {
  const warning = document.getElementById('alloc_warning');
  warning.classList.add('d-none');

  const lines = [];
  document.querySelectorAll('.alloc-line').forEach(div => {
    const inputs = div.querySelectorAll('input');
    const label  = inputs[0].value.trim();
    const amount = parseFloat(inputs[1].value) || 0;
    const notes  = inputs[2].value.trim();
    if (label || amount > 0) lines.push({ label, amount, notes });
  });

  if (lines.length === 0) {
    warning.textContent = 'Please enter at least one allocation line item.';
    warning.classList.remove('d-none');
    return;
  }
  for (const l of lines) {
    if (!l.label) {
      warning.textContent = 'Each allocation must have a label.';
      warning.classList.remove('d-none');
      return;
    }
    if (l.amount <= 0) {
      warning.textContent = 'Each allocation amount must be greater than zero.';
      warning.classList.remove('d-none');
      return;
    }
  }

  const newTotal   = lines.reduce((s, l) => s + l.amount, 0);
  const grandTotal = allocAlreadyDone + newTotal;
  if (grandTotal > allocBudgetAmount) {
    warning.textContent = `Total allocations (${peso(grandTotal)}) exceed the budget amount (${peso(allocBudgetAmount)}).`;
    warning.classList.remove('d-none');
    return;
  }

  const formData = new FormData();
  formData.append('action',    'save_allocations');
  formData.append('budget_id', allocBudgetId);
  lines.forEach((l, i) => {
    formData.append('alloc_label[]',  l.label);
    formData.append('alloc_amount[]', l.amount);
    formData.append('alloc_notes[]',  l.notes);
  });

  fetch('budgets_actions.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .catch(() => ({ success: false, message: 'Network error' }))
    .then(data => {
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('allocateBudgetModal')).hide();
        location.reload();
      } else {
        warning.textContent = data.message || 'Failed to save allocations.';
        warning.classList.remove('d-none');
      }
    });
});

function deleteAllocation(allocId) {
  if (!confirm('Remove this allocation?')) return;

  const formData = new FormData();
  formData.append('action', 'delete_allocation');
  formData.append('allocation_id', allocId);

  fetch('budgets_actions.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .catch(() => ({ success: false, message: 'Network error' }))
    .then(data => {
      if (data.success) {
        const idx = allocExisting.findIndex(a => a.id === allocId);
        if (idx > -1) {
          allocAlreadyDone -= parseFloat(allocExisting[idx].allocated_amount);
          allocExisting.splice(idx, 1);
        }
        document.getElementById('alloc_already_allocated').textContent = peso(allocAlreadyDone);
        document.getElementById('alloc_available').textContent = peso(allocBudgetAmount - allocAlreadyDone);
        renderExistingAllocations();
        updateAllocProgress();
      } else {
        alert('Failed to delete: ' + (data.message || 'Unknown error'));
      }
    });
}

function escHtml(str) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(str));
  return d.innerHTML;
}

console.log('Finance Budgeting System Ready');
</script>
<?php include 'budgets_breakdown.php'; ?>

</body>
</html>
