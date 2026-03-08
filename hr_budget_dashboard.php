<?php
// hr_budget_dashboard.php
// HR Department Budget Request & Tracking System
session_start();
require_once 'db.php';

// Simulated HR department user
if (!isset($_SESSION['hr_user'])) {
    $_SESSION['hr_user'] = 'HR Manager';
    $_SESSION['hr_department'] = 'HR';
}

// Fetch HR budget requests with their approval status from finance
$query = "SELECT b.*, 
          (SELECT SUM(allocated_amount) FROM budget_allocations WHERE budget_id = b.id) as total_allocated,
          b.amount_allocated - b.amount_used as remaining_budget
          FROM budgets b 
          WHERE b.department = 'HR' 
          ORDER BY b.created_at DESC";
$result = $conn->query($query);
$hrBudgets = $result->fetch_all(MYSQLI_ASSOC);

// Calculate summary statistics
$summary = [
    'total_requested' => 0,
    'total_approved' => 0,
    'total_spent' => 0,
    'total_remaining' => 0,
    'pending_count' => 0,
    'approved_count' => 0,
    'rejected_count' => 0
];

foreach ($hrBudgets as $budget) {
    $summary['total_requested'] += $budget['amount_allocated'];
    if ($budget['approval_status'] === 'Approved') {
        $summary['total_approved'] += $budget['amount_allocated'];
        $summary['total_spent'] += $budget['amount_used'];
        $summary['total_remaining'] += $budget['remaining_budget'];
        $summary['approved_count']++;
    } elseif ($budget['approval_status'] === 'Pending') {
        $summary['pending_count']++;
    } else {
        $summary['rejected_count']++;
    }
}

function peso($n) {
    return '₱' . number_format((float)$n, 2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HR Department - Budget Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-card.success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-card.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        
        .budget-item {
            border-left: 4px solid #dee2e6;
            transition: all 0.3s;
        }
        .budget-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .budget-item.approved { border-left-color: #28a745; }
        .budget-item.pending { border-left-color: #ffc107; }
        .budget-item.rejected { border-left-color: #dc3545; }
        
        .allocation-detail {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-building"></i> HR Department - Budget Management
            </span>
            <span class="text-white">
                <i class="bi bi-person-circle"></i> <?= $_SESSION['hr_user'] ?>
            </span>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show">
                <?= $_SESSION['flash']['msg'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <h6 class="text-white-50 mb-2">Total Requested</h6>
                    <h3 class="mb-0"><?= peso($summary['total_requested']) ?></h3>
                    <small><?= count($hrBudgets) ?> requests</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <h6 class="text-white-50 mb-2">Approved Budget</h6>
                    <h3 class="mb-0"><?= peso($summary['total_approved']) ?></h3>
                    <small><?= $summary['approved_count'] ?> approved</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <h6 class="text-white-50 mb-2">Total Spent</h6>
                    <h3 class="mb-0"><?= peso($summary['total_spent']) ?></h3>
                    <small>Remaining: <?= peso($summary['total_remaining']) ?></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card info">
                    <h6 class="text-white-50 mb-2">Pending Approval</h6>
                    <h3 class="mb-0"><?= $summary['pending_count'] ?></h3>
                    <small><?= $summary['rejected_count'] ?> rejected</small>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Budget Requests</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                        <i class="bi bi-plus-circle"></i> New Budget Request
                    </button>
                </div>
            </div>
        </div>

        <!-- Budget Requests List -->
        <div class="row">
            <?php if (empty($hrBudgets)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No budget requests yet. Create your first request!
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($hrBudgets as $budget): ?>
                    <?php
                    $statusClass = strtolower($budget['approval_status']);
                    $statusBadge = [
                        'pending' => '<span class="badge bg-warning">Pending</span>',
                        'approved' => '<span class="badge bg-success">Approved</span>',
                        'rejected' => '<span class="badge bg-danger">Rejected</span>'
                    ][$statusClass] ?? '';
                    
                    // Get allocations for this budget
                    $allocQuery = "SELECT * FROM budget_allocations WHERE budget_id = " . $budget['id'];
                    $allocResult = $conn->query($allocQuery);
                    $allocations = $allocResult->fetch_all(MYSQLI_ASSOC);
                    ?>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card budget-item <?= $statusClass ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="card-title mb-1"><?= htmlspecialchars($budget['cost_center']) ?></h6>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> <?= $budget['period'] ?> | 
                                            Created: <?= date('M d, Y', strtotime($budget['created_at'])) ?>
                                        </small>
                                    </div>
                                    <?= $statusBadge ?>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Requested Amount</small>
                                        <strong><?= peso($budget['amount_allocated']) ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Amount Spent</small>
                                        <strong class="text-danger"><?= peso($budget['amount_used']) ?></strong>
                                    </div>
                                </div>

                                <?php if ($budget['approval_status'] === 'Approved'): ?>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Remaining Budget</small>
                                            <strong class="<?= $budget['remaining_budget'] > 0 ? 'text-success' : 'text-danger' ?>">
                                                <?= peso($budget['remaining_budget']) ?>
                                            </strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Utilization</small>
                                            <?php
                                            $util = $budget['amount_allocated'] > 0 
                                                ? ($budget['amount_used'] / $budget['amount_allocated'] * 100) 
                                                : 0;
                                            $utilColor = $util > 90 ? 'danger' : ($util > 70 ? 'warning' : 'success');
                                            ?>
                                            <strong class="text-<?= $utilColor ?>"><?= number_format($util, 1) ?>%</strong>
                                        </div>
                                    </div>

                                    <!-- Budget Allocations -->
                                    <?php if (!empty($allocations)): ?>
                                        <div class="allocation-detail">
                                            <small class="text-muted fw-bold d-block mb-2">
                                                <i class="bi bi-pie-chart"></i> Budget Breakdown (<?= count($allocations) ?> items)
                                            </small>
                                            <?php foreach ($allocations as $alloc): ?>
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <small><?= htmlspecialchars($alloc['label']) ?></small>
                                                    <small class="fw-bold"><?= peso($alloc['allocated_amount']) ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                            <hr class="my-2">
                                            <div class="d-flex justify-content-between">
                                                <small class="fw-bold">Total Allocated:</small>
                                                <small class="fw-bold"><?= peso($budget['total_allocated'] ?? 0) ?></small>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <div class="mt-3">
                                    <small class="text-muted d-block mb-1">Description:</small>
                                    <small><?= nl2br(htmlspecialchars($budget['description'] ?: 'No description')) ?></small>
                                </div>

                                <?php if ($budget['approved_by']): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="bi bi-person-check"></i> Approved by: <strong><?= htmlspecialchars($budget['approved_by']) ?></strong>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3 d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="viewDetails(<?= htmlspecialchars(json_encode($budget)) ?>)">
                                        <i class="bi bi-eye"></i> Details
                                    </button>
                                    <?php if ($budget['approval_status'] === 'Approved'): ?>
                                        <button class="btn btn-sm btn-success" 
                                                onclick="recordExpense(<?= $budget['id'] ?>, '<?= htmlspecialchars($budget['cost_center']) ?>')">
                                            <i class="bi bi-receipt"></i> Record Expense
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- New Budget Request Modal -->
    <div class="modal fade" id="newRequestModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="hr_budget_actions.php" method="POST">
                    <input type="hidden" name="action" value="create_request">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> New Budget Request</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Budget Period <span class="text-danger">*</span></label>
                                <select class="form-select" name="period" required>
                                    <option value="">Select Period</option>
                                    <option value="Daily">Daily</option>
                                    <option value="Bi-weekly">Bi-weekly</option>
                                    <option value="Monthly">Monthly</option>
                                    <option value="Annually">Annually</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cost Center <span class="text-danger">*</span></label>
                                <select class="form-select" name="cost_center" required>
                                    <option value="">Select Cost Center</option>
                                    <option value="Training Budget">Training Budget</option>
                                    <option value="Reimbursement Budget">Reimbursement Budget</option>
                                    <option value="Benefits Budget">Benefits Budget</option>
                                    <option value="Payroll Budget">Payroll Budget</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Requested Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" name="amount_allocated" 
                                           step="0.01" min="0.01" required placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Justification / Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="description" rows="4" required 
                                          placeholder="Explain why this budget is needed..."></textarea>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    <i class="bi bi-info-circle"></i> 
                                    <strong>Note:</strong> Your request will be sent to the Finance Department for approval. 
                                    Once approved, you'll be able to see the allocated budget and track expenses here.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-info-circle"></i> Budget Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- Record Expense Modal -->
    <div class="modal fade" id="expenseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="hr_budget_actions.php" method="POST">
                    <input type="hidden" name="action" value="record_expense">
                    <input type="hidden" name="budget_id" id="expense_budget_id">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="bi bi-receipt"></i> Record Expense</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Cost Center</label>
                            <input type="text" class="form-control" id="expense_cost_center" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expense Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" name="expense_amount" 
                                       step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="expense_description" rows="2" 
                                      placeholder="What was this expense for?"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Record Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDetails(budget) {
            const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
            const content = document.getElementById('detailsContent');
            
            const statusBadge = {
                'Pending': '<span class="badge bg-warning">Pending</span>',
                'Approved': '<span class="badge bg-success">Approved</span>',
                'Rejected': '<span class="badge bg-danger">Rejected</span>'
            }[budget.approval_status] || '';
            
            content.innerHTML = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Cost Center</label>
                        <p class="fw-bold">${budget.cost_center}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Period</label>
                        <p class="fw-bold">${budget.period}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Requested Amount</label>
                        <p class="fw-bold text-primary">₱${parseFloat(budget.amount_allocated).toLocaleString(undefined, {minimumFractionDigits:2})}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Status</label>
                        <p>${statusBadge}</p>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small">Description</label>
                        <p>${budget.description || 'No description'}</p>
                    </div>
                    ${budget.approved_by ? `
                        <div class="col-12">
                            <label class="text-muted small">Approved By</label>
                            <p class="fw-bold">${budget.approved_by}</p>
                        </div>
                    ` : ''}
                </div>
            `;
            
            modal.show();
        }
        
        function recordExpense(budgetId, costCenter) {
            document.getElementById('expense_budget_id').value = budgetId;
            document.getElementById('expense_cost_center').value = costCenter;
            new bootstrap.Modal(document.getElementById('expenseModal')).show();
        }
    </script>
</body>
</html>
