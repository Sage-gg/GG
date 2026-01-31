<?php
// financial_reimbursement.php
require_once 'db.php';
requireModuleAccess('reimbursement');

// Get permissions for this module
$perms = getModulePermission('reimbursement');
$canCreate = $perms['can_create'];
$canEdit = $perms['can_edit'];
$canDelete = $perms['can_delete'];
$canApprove = $perms['can_approve'];

// For staff, filter by user ID
if (isStaff()) {
    $currentUserId = $_SESSION['user_id'];
}

// For managers, filter by department
if (isManager()) {
    $userDepartment = getUserDepartment();
}

// Pagination settings
$recordsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Filter handling
$filterStatus = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';
$filterDepartment = isset($_GET['filter_department']) ? trim($_GET['filter_department']) : '';
$filterEmployee = isset($_GET['filter_employee']) ? trim($_GET['filter_employee']) : '';

// Build WHERE clause
$whereConditions = [];
$params = [];
$types = '';

if ($filterStatus !== '') {
    $whereConditions[] = "r.status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

if ($filterDepartment !== '') {
    $whereConditions[] = "r.department = ?";
    $params[] = $filterDepartment;
    $types .= 's';
}

if ($filterEmployee !== '') {
    $whereConditions[] = "r.employee_name LIKE ?";
    $params[] = "%$filterEmployee%";
    $types .= 's';
}

// Count total records
$countSql = "SELECT COUNT(*) as total FROM reimbursements r";
if (!empty($whereConditions)) {
    $countSql .= " WHERE " . implode(" AND ", $whereConditions);
}

$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

// Calculate pagination
$totalPages = ceil($totalRecords / $recordsPerPage);
$currentPage = max(1, min($currentPage, max(1, $totalPages)));
$offset = ($currentPage - 1) * $recordsPerPage;

// Fetch reimbursements
$sql = "SELECT r.*, b.amount_allocated, b.amount_used 
        FROM reimbursements r
        LEFT JOIN budgets b ON r.budget_id = b.id";

if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " ORDER BY r.submission_date DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$params[] = $recordsPerPage;
$params[] = $offset;
$types .= 'ii';

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$reimbursements = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate summary statistics (removed Paid status)
// We need to rebuild the WHERE conditions without the LIMIT/OFFSET params
$summaryWhereConditions = [];
$summaryParams = [];
$summaryTypes = '';

if ($filterStatus !== '') {
    $summaryWhereConditions[] = "r.status = ?";
    $summaryParams[] = $filterStatus;
    $summaryTypes .= 's';
}

if ($filterDepartment !== '') {
    $summaryWhereConditions[] = "r.department = ?";
    $summaryParams[] = $filterDepartment;
    $summaryTypes .= 's';
}

if ($filterEmployee !== '') {
    $summaryWhereConditions[] = "r.employee_name LIKE ?";
    $summaryParams[] = "%$filterEmployee%";
    $summaryTypes .= 's';
}

$summarySql = "SELECT 
    COUNT(*) as total_count,
    COALESCE(SUM(r.amount), 0) as total_amount,
    COALESCE(SUM(CASE WHEN r.status = 'Pending' THEN r.amount ELSE 0 END), 0) as pending_amount,
    COALESCE(SUM(CASE WHEN r.status = 'Approved' THEN r.amount ELSE 0 END), 0) as approved_amount,
    COALESCE(SUM(CASE WHEN r.status = 'Rejected' THEN r.amount ELSE 0 END), 0) as rejected_amount
    FROM reimbursements r";

if (!empty($summaryWhereConditions)) {
    $summarySql .= " WHERE " . implode(" AND ", $summaryWhereConditions);
}

$summaryStmt = $conn->prepare($summarySql);
if (!empty($summaryParams)) {
    $summaryStmt->bind_param($summaryTypes, ...$summaryParams);
}

$summaryStmt->execute();
$summary = $summaryStmt->get_result()->fetch_assoc();
$summaryStmt->close();

function peso($n) {
    return '₱' . number_format((float)$n, 2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reimbursement Management</title>
    
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
        .summary-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.75rem;
            text-align: center;
        }
        .summary-label {
            font-size: 0.75rem;
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        .summary-value {
            font-size: 1rem;
            font-weight: 600;
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>

<?php include 'sidebar_navbar.php'; ?>

<div class="main-content">
    <div class="container-fluid mt-4 px-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="fw-bold">Reimbursement Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReimbursementModal">
                <i class="bi bi-plus-circle"></i> Submit Reimbursement
            </button>
        </div>

        <?php if(isset($_SESSION['flash'])): ?>
            <div class="alert alert-<?=$_SESSION['flash']['type']?> alert-dismissible fade show">
                <?=$_SESSION['flash']['msg']?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <!-- Summary Cards (removed Paid status) -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="summary-card border-primary">
                    <div class="summary-label">Total Requests</div>
                    <div class="summary-value text-primary"><?= $summary['total_count'] ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card border-warning">
                    <div class="summary-label">Pending Amount</div>
                    <div class="summary-value text-warning"><?= peso($summary['pending_amount']) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card border-success">
                    <div class="summary-label">Approved Amount</div>
                    <div class="summary-value text-success"><?= peso($summary['approved_amount']) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card border-danger">
                    <div class="summary-label">Rejected Amount</div>
                    <div class="summary-value text-danger"><?= peso($summary['rejected_amount']) ?></div>
                </div>
            </div>
        </div>

        <!-- Filters (removed Paid option) -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Status</label>
                        <select class="form-select form-select-sm" name="filter_status">
                            <option value="">All Status</option>
                            <option value="Pending" <?= $filterStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Approved" <?= $filterStatus === 'Approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="Rejected" <?= $filterStatus === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Department</label>
                        <select class="form-select form-select-sm" name="filter_department">
                            <option value="">All Departments</option>
                            <option value="HR" <?= $filterDepartment === 'HR' ? 'selected' : '' ?>>HR</option>
                            <option value="Core" <?= $filterDepartment === 'Core' ? 'selected' : '' ?>>Core</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Employee Name</label>
                        <input type="text" class="form-control form-control-sm" name="filter_employee" 
                               value="<?= htmlspecialchars($filterEmployee) ?>" placeholder="Search employee...">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Apply</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reimbursements Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Expense Date</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($reimbursements)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">No reimbursements found</td></tr>
                    <?php else: ?>
                        <?php foreach($reimbursements as $index => $r): ?>
                            <?php
                            $globalIndex = $offset + $index + 1;
                            $statusBadge = match($r['status']) {
                                'Pending' => '<span class="badge bg-warning status-badge">Pending</span>',
                                'Approved' => '<span class="badge bg-success status-badge">Approved</span>',
                                'Rejected' => '<span class="badge bg-danger status-badge">Rejected</span>',
                                default => '<span class="badge bg-secondary status-badge">Unknown</span>'
                            };
                            $dataAttrs = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr>
                                <td><?= $globalIndex ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($r['employee_name']) ?></strong>
                                    <?php if($r['employee_id']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($r['employee_id']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($r['department']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($r['cost_center']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($r['reimbursement_type']) ?></td>
                                <td class="text-end"><?= peso($r['amount']) ?></td>
                                <td><?= date('M d, Y', strtotime($r['expense_date'])) ?></td>
                                <td><?= date('M d, Y', strtotime($r['submission_date'])) ?></td>
                                <td><?= $statusBadge ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-primary btn-sm" data-record='<?=$dataAttrs?>' 
                                                onclick="viewReimbursement(this)" data-bs-toggle="modal" 
                                                data-bs-target="#viewReimbursementModal">View</button>
                                        <?php if($r['status'] === 'Pending'): ?>
                                            <button class="btn btn-warning btn-sm" data-record='<?=$dataAttrs?>' 
                                                    onclick="editReimbursement(this)" data-bs-toggle="modal" 
                                                    data-bs-target="#editReimbursementModal">Edit</button>
                                            <button class="btn btn-danger btn-sm" data-id="<?=$r['id']?>" 
                                                    data-name="<?=htmlspecialchars($r['employee_name'])?>" 
                                                    onclick="deleteReimbursement(this)" data-bs-toggle="modal" 
                                                    data-bs-target="#deleteReimbursementModal">Delete</button>
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
        <?php if($totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php
                    $queryParams = [];
                    if($filterStatus) $queryParams[] = "filter_status=$filterStatus";
                    if($filterDepartment) $queryParams[] = "filter_department=$filterDepartment";
                    if($filterEmployee) $queryParams[] = "filter_employee=" . urlencode($filterEmployee);
                    $queryString = !empty($queryParams) ? '&' . implode('&', $queryParams) : '';
                    
                    for($i = 1; $i <= $totalPages; $i++):
                    ?>
                        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?=$i?><?=$queryString?>"><?=$i?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Modals -->
<?php include 'reimbursement_modals.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ADD THIS LINE -->
<script src="session_check.js"></script>
<!-- END OF ADDED LINE -->

<script src="reimbursement_scripts.js"></script>
</body>
</html>
