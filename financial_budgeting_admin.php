<?php
// financial_budgeting_admin.php
// Placeholder file for admin budget approval system
session_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Budget Management - Placeholder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">⚖️ Admin Budget Management - PLACEHOLDER</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <h5>🏛️ Legal Management / Admin Overview</h5>
                            <p>This is a placeholder for the admin system that will handle budget request approvals and rejections.</p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6>📋 Planned Admin Functions:</h6>
                                <ul class="list-group list-group-flush mb-4">
                                    <li class="list-group-item">
                                        <i class="fas fa-inbox text-primary"></i>
                                        <strong>Receive Budget Requests</strong>
                                        <small class="d-block text-muted">View forwarded requests from departments</small>
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-check-circle text-success"></i>
                                        <strong>Approve/Reject Requests</strong>
                                        <small class="d-block text-muted">Review and make approval decisions</small>
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-comment text-info"></i>
                                        <strong>Add Comments/Notes</strong>
                                        <small class="d-block text-muted">Provide feedback or requirements</small>
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-paper-plane text-warning"></i>
                                        <strong>Send Notifications</strong>
                                        <small class="d-block text-muted">Notify departments of decisions</small>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>🏢 Department Budget Categories:</h6>
                                <div class="accordion" id="departmentAccordion">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="hr2Header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#hr2Collapse">
                                                HR2 Department
                                            </button>
                                        </h2>
                                        <div id="hr2Collapse" class="accordion-collapse collapse" data-bs-parent="#departmentAccordion">
                                            <div class="accordion-body">
                                                <ul class="list-unstyled">
                                                    <li>• Training Budget</li>
                                                    <li>• Reimbursement Budget</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="hr4Header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#hr4Collapse">
                                                HR4 Department
                                            </button>
                                        </h2>
                                        <div id="hr4Collapse" class="accordion-collapse collapse" data-bs-parent="#departmentAccordion">
                                            <div class="accordion-body">
                                                <ul class="list-unstyled">
                                                    <li>• Benefits Budget</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="core2Header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#core2Collapse">
                                                Core 2 Department
                                            </button>
                                        </h2>
                                        <div id="core2Collapse" class="accordion-collapse collapse" data-bs-parent="#departmentAccordion">
                                            <div class="accordion-body">
                                                <ul class="list-unstyled">
                                                    <li>• Log Maintenance Costs</li>
                                                    <li>• Depreciation Charges</li>
                                                    <li>• Insurance Fees</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="core4Header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#core4Collapse">
                                                Core 4 Department
                                            </button>
                                        </h2>
                                        <div id="core4Collapse" class="accordion-collapse collapse" data-bs-parent="#departmentAccordion">
                                            <div class="accordion-body">
                                                <ul class="list-unstyled">
                                                    <li>• Vehicle Operational Budget</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-4">
                            <h6>🔗 Integration Requirements:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="mb-0">
                                        <li>Admin authentication system</li>
                                        <li>Budget request queue management</li>
                                        <li>Approval workflow engine</li>
                                        <li>Document attachment system</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="mb-0">
                                        <li>Email notification service</li>
                                        <li>Audit trail logging</li>
                                        <li>Department contact management</li>
                                        <li>Report generation system</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-light mt-4">
                            <div class="card-body">
                                <h6>📊 Sample Admin Workflow:</h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-center">
                                        <i class="fas fa-inbox fa-2x text-primary"></i>
                                        <div class="small mt-1">Receive Request</div>
                                    </div>
                                    <i class="fas fa-arrow-right text-muted"></i>
                                    <div class="text-center">
                                        <i class="fas fa-eye fa-2x text-info"></i>
                                        <div class="small mt-1">Review Details</div>
                                    </div>
                                    <i class="fas fa-arrow-right text-muted"></i>
                                    <div class="text-center">
                                        <i class="fas fa-balance-scale fa-2x text-warning"></i>
                                        <div class="small mt-1">Make Decision</div>
                                    </div>
                                    <i class="fas fa-arrow-right text-muted"></i>
                                    <div class="text-center">
                                        <i class="fas fa-bell fa-2x text-success"></i>
                                        <div class="small mt-1">Notify Department</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <a href="financial_budgeting.php" class="btn btn-primary">
                                ← Back to Budget Management
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>