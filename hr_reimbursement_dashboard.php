<?php
// hr_reimbursement_dashboard.php
// HR Department - Reimbursement Request & Tracking System
session_start();
require_once 'db.php';

// Simulated HR user
if (!isset($_SESSION['hr_user'])) {
    $_SESSION['hr_user'] = 'HR Manager';
    $_SESSION['hr_department'] = 'HR';
}

// Fetch HR reimbursement requests
$query = "SELECT * FROM reimbursements 
          WHERE department = 'HR' 
          ORDER BY submission_date DESC";
$result = $conn->query($query);
$hrReimbursements = $result->fetch_all(MYSQLI_ASSOC);

// Calculate summary statistics
$summary = [
    'total_submitted' => 0,
    'total_amount' => 0,
    'pending_count' => 0,
    'pending_amount' => 0,
    'approved_count' => 0,
    'approved_amount' => 0,
    'rejected_count' => 0,
    'rejected_amount' => 0
];

foreach ($hrReimbursements as $reimb) {
    $summary['total_submitted']++;
    $summary['total_amount'] += $reimb['amount'];
    
    if ($reimb['status'] === 'Pending') {
        $summary['pending_count']++;
        $summary['pending_amount'] += $reimb['amount'];
    } elseif ($reimb['status'] === 'Approved') {
        $summary['approved_count']++;
        $summary['approved_amount'] += $reimb['amount'];
    } elseif ($reimb['status'] === 'Rejected') {
        $summary['rejected_count']++;
        $summary['rejected_amount'] += $reimb['amount'];
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
    <title>HR Department - Reimbursement Dashboard</title>
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
        .stat-card.pending { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.approved { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-card.rejected { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        
        .reimb-item {
            border-left: 4px solid #dee2e6;
            transition: all 0.3s;
        }
        .reimb-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .reimb-item.approved { border-left-color: #28a745; }
        .reimb-item.pending { border-left-color: #ffc107; }
        .reimb-item.rejected { border-left-color: #dc3545; }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pending-badge {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-receipt"></i> HR Department - Reimbursement Management
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
                    <h6 class="text-white-50 mb-2">Total Submitted</h6>
                    <h3 class="mb-0"><?= $summary['total_submitted'] ?></h3>
                    <small><?= peso($summary['total_amount']) ?></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card pending">
                    <h6 class="text-white-50 mb-2">Pending Approval</h6>
                    <h3 class="mb-0"><?= $summary['pending_count'] ?></h3>
                    <small><?= peso($summary['pending_amount']) ?></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card approved">
                    <h6 class="text-white-50 mb-2">Approved</h6>
                    <h3 class="mb-0"><?= $summary['approved_count'] ?></h3>
                    <small><?= peso($summary['approved_amount']) ?></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card rejected">
                    <h6 class="text-white-50 mb-2">Rejected</h6>
                    <h3 class="mb-0"><?= $summary['rejected_count'] ?></h3>
                    <small><?= peso($summary['rejected_amount']) ?></small>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-receipt-cutoff"></i> Reimbursement Requests</h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary" onclick="window.open('financial_reimbursement.php', '_blank')">
                            <i class="bi bi-box-arrow-up-right"></i> View Finance System
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newReimbursementModal">
                            <i class="bi bi-plus-circle"></i> New Reimbursement Request
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Alert -->
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i>
            <strong>Workflow:</strong> Submit your reimbursement requests here. They will automatically appear in the Finance Department's reimbursement system for approval. Once approved by Finance, the status will automatically sync back to this dashboard and your reimbursement will be marked as paid.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <!-- Reimbursement Requests List -->
        <div class="row">
            <?php if (empty($hrReimbursements)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No reimbursement requests yet. Submit your first request!
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($hrReimbursements as $reimb): ?>
                    <?php
                    $statusClass = strtolower($reimb['status']);
                    $statusBadge = [
                        'pending' => '<span class="badge bg-warning pending-badge">Pending Finance Approval</span>',
                        'approved' => '<span class="badge bg-success">Approved & Paid</span>',
                        'rejected' => '<span class="badge bg-danger">Rejected</span>'
                    ][$statusClass] ?? '';
                    ?>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card reimb-item <?= $statusClass ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="card-title mb-1"><?= htmlspecialchars($reimb['employee_name']) ?></h6>
                                        <small class="text-muted">
                                            <i class="bi bi-person-badge"></i> <?= htmlspecialchars($reimb['employee_id'] ?: 'N/A') ?> | 
                                            <i class="bi bi-calendar"></i> Submitted: <?= date('M d, Y', strtotime($reimb['submission_date'])) ?>
                                        </small>
                                    </div>
                                    <?= $statusBadge ?>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Reimbursement Type</small>
                                        <strong><?= htmlspecialchars($reimb['reimbursement_type']) ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Amount</small>
                                        <strong class="text-primary"><?= peso($reimb['amount']) ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Cost Center</small>
                                        <strong><?= htmlspecialchars($reimb['cost_center']) ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Expense Date</small>
                                        <strong><?= date('M d, Y', strtotime($reimb['expense_date'])) ?></strong>
                                    </div>
                                </div>

                                <?php if ($reimb['status'] === 'Approved'): ?>
                                    <div class="alert alert-success mb-3 py-2">
                                        <small>
                                            <i class="bi bi-check-circle-fill"></i> <strong>Approved by:</strong> <?= htmlspecialchars($reimb['approved_by']) ?>
                                            <br>
                                            <i class="bi bi-calendar-check"></i> <strong>Approved on:</strong> <?= date('M d, Y', strtotime($reimb['approved_date'])) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <?php if ($reimb['status'] === 'Rejected' && $reimb['remarks']): ?>
                                    <div class="alert alert-danger mb-3 py-2">
                                        <small>
                                            <i class="bi bi-x-circle-fill"></i> <strong>Rejection Reason:</strong><br>
                                            <?= nl2br(htmlspecialchars($reimb['remarks'])) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3">
                                    <small class="text-muted d-block mb-1">Description:</small>
                                    <small><?= nl2br(htmlspecialchars($reimb['description'] ?: 'No description')) ?></small>
                                </div>

                                <?php if ($reimb['receipt_file']): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="bi bi-paperclip"></i> 
                                            <a href="<?= htmlspecialchars($reimb['receipt_folder'] . $reimb['receipt_file']) ?>" 
                                               target="_blank" class="text-primary">View Receipt</a>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3 d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="viewDetails(<?= htmlspecialchars(json_encode($reimb)) ?>)">
                                        <i class="bi bi-eye"></i> View Details
                                    </button>
                                    <?php if ($reimb['status'] === 'Pending'): ?>
                                        <button class="btn btn-sm btn-outline-warning" 
                                                onclick="editReimbursement(<?= htmlspecialchars(json_encode($reimb)) ?>)">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="cancelRequest(<?= $reimb['id'] ?>, '<?= htmlspecialchars($reimb['employee_name']) ?>')">
                                            <i class="bi bi-trash"></i> Cancel
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

    <!-- New Reimbursement Request Modal -->
    <div class="modal fade" id="newReimbursementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="hr_reimbursement_actions.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> New Reimbursement Request</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Employee Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="employee_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Employee ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="employee_id" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="address" rows="2" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="contact_no" required>
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
                            <div class="col-md-6">
                                <label class="form-label">Reimbursement Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="reimbursement_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Training Course">Training Course</option>
                                    <option value="Medical Expenses">Medical Expenses</option>
                                    <option value="Transportation">Transportation</option>
                                    <option value="Meal Allowance">Meal Allowance</option>
                                    <option value="Office Supplies">Office Supplies</option>
                                    <option value="Business Travel">Business Travel</option>
                                    <option value="Communication">Communication</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expense Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="expense_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Receipt/Proof</label>
                                <input type="file" class="form-control" name="receipt_file" accept=".pdf,.jpg,.jpeg,.png">
                                <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description/Purpose <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="description" rows="3" required></textarea>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    <i class="bi bi-info-circle"></i> 
                                    <strong>Note:</strong> Your request will be automatically sent to the Finance Department for approval. 
                                    You'll be notified once your reimbursement is approved and processed.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Submit to Finance
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
                    <h5 class="modal-title"><i class="bi bi-info-circle"></i> Reimbursement Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="hr_reimbursement_actions.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Reimbursement Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Employee Name</label>
                                <input type="text" class="form-control" name="employee_name" id="edit_employee_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Employee ID</label>
                                <input type="text" class="form-control" name="employee_id" id="edit_employee_id" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" id="edit_address" rows="2" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Number</label>
                                <input type="text" class="form-control" name="contact_no" id="edit_contact_no" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cost Center</label>
                                <select class="form-select" name="cost_center" id="edit_cost_center" required>
                                    <option value="Training Budget">Training Budget</option>
                                    <option value="Reimbursement Budget">Reimbursement Budget</option>
                                    <option value="Benefits Budget">Benefits Budget</option>
                                    <option value="Payroll Budget">Payroll Budget</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reimbursement Type</label>
                                <select class="form-select" name="reimbursement_type" id="edit_type" required>
                                    <option value="Training Course">Training Course</option>
                                    <option value="Medical Expenses">Medical Expenses</option>
                                    <option value="Transportation">Transportation</option>
                                    <option value="Meal Allowance">Meal Allowance</option>
                                    <option value="Office Supplies">Office Supplies</option>
                                    <option value="Business Travel">Business Travel</option>
                                    <option value="Communication">Communication</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" name="amount" id="edit_amount" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expense Date</label>
                                <input type="date" class="form-control" name="expense_date" id="edit_expense_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Update Receipt (Optional)</label>
                                <input type="file" class="form-control" name="receipt_file" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewDetails(reimb) {
            const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
            const content = document.getElementById('detailsContent');
            
            const statusBadge = {
                'Pending': '<span class="badge bg-warning">Pending Finance Approval</span>',
                'Approved': '<span class="badge bg-success">Approved & Paid</span>',
                'Rejected': '<span class="badge bg-danger">Rejected</span>'
            }[reimb.status] || '';
            
            content.innerHTML = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Employee Name</label>
                        <p class="fw-bold">${reimb.employee_name}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Employee ID</label>
                        <p class="fw-bold">${reimb.employee_id || 'N/A'}</p>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small">Address</label>
                        <p>${reimb.address || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Contact Number</label>
                        <p>${reimb.contact_no || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Cost Center</label>
                        <p class="fw-bold">${reimb.cost_center}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Reimbursement Type</label>
                        <p class="fw-bold">${reimb.reimbursement_type}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Amount</label>
                        <p class="fw-bold text-primary">₱${parseFloat(reimb.amount).toLocaleString(undefined, {minimumFractionDigits:2})}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Expense Date</label>
                        <p>${new Date(reimb.expense_date).toLocaleDateString('en-US', {year:'numeric', month:'short', day:'numeric'})}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Status</label>
                        <p>${statusBadge}</p>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small">Description</label>
                        <p>${reimb.description || 'No description'}</p>
                    </div>
                    ${reimb.approved_by ? `
                        <div class="col-12">
                            <label class="text-muted small">Approved By</label>
                            <p class="fw-bold">${reimb.approved_by}</p>
                        </div>
                    ` : ''}
                    ${reimb.remarks ? `
                        <div class="col-12">
                            <div class="alert alert-danger">
                                <strong>Rejection Reason:</strong><br>${reimb.remarks}
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
            
            modal.show();
        }
        
        function editReimbursement(reimb) {
            document.getElementById('edit_id').value = reimb.id;
            document.getElementById('edit_employee_name').value = reimb.employee_name;
            document.getElementById('edit_employee_id').value = reimb.employee_id || '';
            document.getElementById('edit_address').value = reimb.address || '';
            document.getElementById('edit_contact_no').value = reimb.contact_no || '';
            document.getElementById('edit_cost_center').value = reimb.cost_center;
            document.getElementById('edit_type').value = reimb.reimbursement_type;
            document.getElementById('edit_amount').value = reimb.amount;
            document.getElementById('edit_expense_date').value = reimb.expense_date;
            document.getElementById('edit_description').value = reimb.description || '';
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function cancelRequest(id, name) {
            if (confirm(`Cancel reimbursement request for ${name}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'hr_reimbursement_actions.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;
                form.appendChild(idInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
