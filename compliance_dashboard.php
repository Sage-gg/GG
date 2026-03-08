<?php
// compliance_dashboard.php
// Compliance Department - Budget Alert & Action Tracking System
session_start();
require_once 'db.php';

// Simulated Compliance user
if (!isset($_SESSION['compliance_user'])) {
    $_SESSION['compliance_user'] = 'Compliance Officer';
}

// Create compliance_alerts table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS compliance_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_id INT NOT NULL,
    alert_type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('Open', 'In Progress', 'Resolved') DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    resolution_notes TEXT,
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE
)");

// ═══════════════════════════════════════════════════════════════════════════
// CRITICAL FIX: Auto-detect overspent budgets and create/update alerts
// This ensures ALL overspent budgets are reflected in the compliance dashboard
// ═══════════════════════════════════════════════════════════════════════════

$autoDetectSql = "SELECT id, department, cost_center, amount_allocated, amount_used 
                  FROM budgets 
                  WHERE amount_used > amount_allocated 
                  AND approval_status = 'Approved'";
$autoDetectResult = $conn->query($autoDetectSql);

if ($autoDetectResult) {
    while ($overspent = $autoDetectResult->fetch_assoc()) {
        $budgetId = $overspent['id'];
        $overAmount = $overspent['amount_used'] - $overspent['amount_allocated'];
        
        // Check if alert already exists for this budget
        $checkSql = "SELECT id FROM compliance_alerts 
                     WHERE budget_id = ? AND alert_type = 'overspent' AND status != 'Resolved'";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param('i', $budgetId);
        $checkStmt->execute();
        $existingAlert = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();
        
        if (!$existingAlert) {
            // Create new alert for overspent budget
            $message = "{$overspent['department']} - {$overspent['cost_center']} is overspent by ₱" . number_format($overAmount, 2);
            $insertSql = "INSERT INTO compliance_alerts (budget_id, alert_type, message, status) 
                         VALUES (?, 'overspent', ?, 'Open')";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param('is', $budgetId, $message);
            $insertStmt->execute();
            $insertStmt->close();
        } else {
            // Update existing alert message with current overspent amount
            $message = "{$overspent['department']} - {$overspent['cost_center']} is overspent by ₱" . number_format($overAmount, 2);
            $updateSql = "UPDATE compliance_alerts SET message = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param('si', $message, $existingAlert['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }
}

// Fetch all compliance alerts with budget details
$query = "SELECT ca.*, 
          b.department, b.cost_center, b.period, 
          b.amount_allocated, b.amount_used,
          (b.amount_allocated - b.amount_used) as remaining,
          b.approval_status
          FROM compliance_alerts ca
          JOIN budgets b ON ca.budget_id = b.id
          ORDER BY 
            CASE ca.status 
                WHEN 'Open' THEN 1 
                WHEN 'In Progress' THEN 2 
                WHEN 'Resolved' THEN 3 
            END,
            ca.created_at DESC";

$result = $conn->query($query);
$alerts = $result->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$stats = [
    'total_alerts' => count($alerts),
    'open_alerts' => 0,
    'in_progress' => 0,
    'resolved_today' => 0,
    'critical_alerts' => 0
];

foreach ($alerts as $alert) {
    if ($alert['status'] === 'Open') {
        $stats['open_alerts']++;
        if ($alert['alert_type'] === 'overspent') {
            $stats['critical_alerts']++;
        }
    } elseif ($alert['status'] === 'In Progress') {
        $stats['in_progress']++;
    }
    
    if ($alert['resolved_at'] && date('Y-m-d', strtotime($alert['resolved_at'])) === date('Y-m-d')) {
        $stats['resolved_today']++;
    }
}

function peso($n) {
    return '₱' . number_format((float)$n, 2);
}

function getAlertIcon($type) {
    return match($type) {
        'overspent' => '<i class="bi bi-exclamation-triangle-fill text-danger"></i>',
        'low_budget' => '<i class="bi bi-exclamation-circle-fill text-warning"></i>',
        'pending_approval' => '<i class="bi bi-clock-fill text-info"></i>',
        default => '<i class="bi bi-info-circle-fill text-secondary"></i>'
    };
}

function getAlertBadge($type) {
    return match($type) {
        'overspent' => '<span class="badge bg-danger">Overspent</span>',
        'low_budget' => '<span class="badge bg-warning">Low Budget</span>',
        'pending_approval' => '<span class="badge bg-info">Pending Approval</span>',
        default => '<span class="badge bg-secondary">Other</span>'
    };
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Compliance Dashboard - Budget Alerts</title>
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
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stat-card.danger { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.warning { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333; }
        .stat-card.success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-card.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        
        .alert-item {
            border-left: 4px solid #dee2e6;
            transition: all 0.3s;
            margin-bottom: 15px;
        }
        .alert-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .alert-item.critical { border-left-color: #dc3545; background: #fff5f5; }
        .alert-item.warning { border-left-color: #ffc107; background: #fffef5; }
        .alert-item.info { border-left-color: #0dcaf0; background: #f5fcff; }
        .alert-item.resolved { border-left-color: #28a745; background: #f5fef5; opacity: 0.7; }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #0d6efd;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #dee2e6;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-shield-check"></i> Compliance Dashboard - Budget Alerts
            </span>
            <span class="text-white">
                <i class="bi bi-person-circle"></i> <?= $_SESSION['compliance_user'] ?>
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

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <h6 class="text-white-50 mb-2">Total Alerts</h6>
                    <h3 class="mb-0"><?= $stats['total_alerts'] ?></h3>
                    <small>All time</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card danger">
                    <h6 class="text-white-50 mb-2">Open Alerts</h6>
                    <h3 class="mb-0"><?= $stats['open_alerts'] ?></h3>
                    <small><?= $stats['critical_alerts'] ?> critical</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <h6 class="mb-2" style="opacity: 0.7;">In Progress</h6>
                    <h3 class="mb-0"><?= $stats['in_progress'] ?></h3>
                    <small>Being addressed</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <h6 class="text-white-50 mb-2">Resolved Today</h6>
                    <h3 class="mb-0"><?= $stats['resolved_today'] ?></h3>
                    <small>Good work!</small>
                </div>
            </div>
        </div>

        <!-- Info Alert -->
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i>
            <strong>Auto-Detection Active:</strong> This dashboard automatically detects and displays all overspent budgets from the financial budgeting system in real-time.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <!-- Filter Tabs -->
        <ul class="nav nav-tabs mb-4" id="alertTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button">
                    All Alerts <span class="badge bg-secondary ms-2"><?= count($alerts) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="open-tab" data-bs-toggle="tab" data-bs-target="#open" type="button">
                    Open <span class="badge bg-danger ms-2"><?= $stats['open_alerts'] ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="progress-tab" data-bs-toggle="tab" data-bs-target="#progress" type="button">
                    In Progress <span class="badge bg-warning ms-2"><?= $stats['in_progress'] ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="resolved-tab" data-bs-toggle="tab" data-bs-target="#resolved" type="button">
                    Resolved
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="alertTabsContent">
            <!-- All Alerts -->
            <div class="tab-pane fade show active" id="all" role="tabpanel">
                <?php if (empty($alerts)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> No alerts at this time. All budgets are in compliance!
                    </div>
                <?php else: ?>
                    <?php foreach ($alerts as $alert): ?>
                        <?= renderAlertCard($alert) ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Open Alerts -->
            <div class="tab-pane fade" id="open" role="tabpanel">
                <?php
                $openAlerts = array_filter($alerts, fn($a) => $a['status'] === 'Open');
                if (empty($openAlerts)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> No open alerts!
                    </div>
                <?php else: ?>
                    <?php foreach ($openAlerts as $alert): ?>
                        <?= renderAlertCard($alert) ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- In Progress -->
            <div class="tab-pane fade" id="progress" role="tabpanel">
                <?php
                $progressAlerts = array_filter($alerts, fn($a) => $a['status'] === 'In Progress');
                if (empty($progressAlerts)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No alerts in progress.
                    </div>
                <?php else: ?>
                    <?php foreach ($progressAlerts as $alert): ?>
                        <?= renderAlertCard($alert) ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Resolved -->
            <div class="tab-pane fade" id="resolved" role="tabpanel">
                <?php
                $resolvedAlerts = array_filter($alerts, fn($a) => $a['status'] === 'Resolved');
                if (empty($resolvedAlerts)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No resolved alerts yet.
                    </div>
                <?php else: ?>
                    <?php foreach ($resolvedAlerts as $alert): ?>
                        <?= renderAlertCard($alert) ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Action Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="compliance_actions.php" method="POST">
                    <input type="hidden" name="alert_id" id="action_alert_id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-clipboard-check"></i> Take Action</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="alertDetailsDisplay" class="mb-4"></div>
                        
                        <div class="mb-3">
                            <label class="form-label">Change Status</label>
                            <select class="form-select" name="new_status" id="action_status">
                                <option value="Open">Open</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Resolved">Resolved</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Resolution Notes</label>
                            <textarea class="form-control" name="resolution_notes" rows="4" 
                                      placeholder="Document the action taken or resolution..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="action" value="update_status" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Alert
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function takeAction(alertData) {
            document.getElementById('action_alert_id').value = alertData.id;
            document.getElementById('action_status').value = alertData.status;
            
            const detailsHtml = `
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">${getAlertIcon(alertData.alert_type)} ${alertData.message}</h6>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">Department:</small>
                                <p class="mb-2"><strong>${alertData.department}</strong></p>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Cost Center:</small>
                                <p class="mb-2"><strong>${alertData.cost_center}</strong></p>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Budget Allocated:</small>
                                <p class="mb-2"><strong>₱${parseFloat(alertData.amount_allocated).toLocaleString(undefined, {minimumFractionDigits:2})}</strong></p>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Amount Used:</small>
                                <p class="mb-2"><strong>₱${parseFloat(alertData.amount_used).toLocaleString(undefined, {minimumFractionDigits:2})}</strong></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('alertDetailsDisplay').innerHTML = detailsHtml;
            new bootstrap.Modal(document.getElementById('actionModal')).show();
        }
        
        function getAlertIcon(type) {
            const icons = {
                'overspent': '<i class="bi bi-exclamation-triangle-fill text-danger"></i>',
                'low_budget': '<i class="bi bi-exclamation-circle-fill text-warning"></i>',
                'pending_approval': '<i class="bi bi-clock-fill text-info"></i>'
            };
            return icons[type] || '<i class="bi bi-info-circle-fill text-secondary"></i>';
        }
    </script>
</body>
</html>

<?php
function renderAlertCard($alert) {
    $alertClass = match($alert['status']) {
        'Resolved' => 'resolved',
        default => match($alert['alert_type']) {
            'overspent' => 'critical',
            'low_budget' => 'warning',
            default => 'info'
        }
    };
    
    $statusBadge = match($alert['status']) {
        'Open' => '<span class="badge bg-danger status-badge">Open</span>',
        'In Progress' => '<span class="badge bg-warning status-badge">In Progress</span>',
        'Resolved' => '<span class="badge bg-success status-badge">Resolved</span>',
        default => '<span class="badge bg-secondary status-badge">' . $alert['status'] . '</span>'
    };
    
    $alertData = htmlspecialchars(json_encode($alert), ENT_QUOTES, 'UTF-8');
    
    ob_start();
    ?>
    <div class="card alert-item <?= $alertClass ?>">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h6 class="mb-1">
                        <?= getAlertIcon($alert['alert_type']) ?>
                        <?= htmlspecialchars($alert['message']) ?>
                    </h6>
                    <small class="text-muted">
                        <i class="bi bi-building"></i> <?= htmlspecialchars($alert['department']) ?> - 
                        <?= htmlspecialchars($alert['cost_center']) ?> | 
                        <i class="bi bi-calendar"></i> <?= date('M d, Y g:i A', strtotime($alert['created_at'])) ?>
                    </small>
                </div>
                <div class="text-end">
                    <?= getAlertBadge($alert['alert_type']) ?>
                    <?= $statusBadge ?>
                </div>
            </div>
            
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <small class="text-muted d-block">Budget Allocated</small>
                    <strong><?= peso($alert['amount_allocated']) ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Amount Used</small>
                    <strong class="text-danger"><?= peso($alert['amount_used']) ?></strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Remaining</small>
                    <strong class="<?= $alert['remaining'] < 0 ? 'text-danger' : 'text-success' ?>">
                        <?= peso($alert['remaining']) ?>
                    </strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Period</small>
                    <strong><?= htmlspecialchars($alert['period']) ?></strong>
                </div>
            </div>
            
            <?php if ($alert['resolution_notes']): ?>
                <div class="alert alert-info mb-3">
                    <strong><i class="bi bi-journal-text"></i> Resolution Notes:</strong><br>
                    <?= nl2br(htmlspecialchars($alert['resolution_notes'])) ?>
                    <?php if ($alert['resolved_at']): ?>
                        <br><small class="text-muted">Resolved on: <?= date('M d, Y g:i A', strtotime($alert['resolved_at'])) ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-primary" onclick='takeAction(<?= $alertData ?>)'>
                    <i class="bi bi-clipboard-check"></i> Take Action
                </button>
                <a href="financial_budgeting.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-box-arrow-up-right"></i> View in Finance System
                </a>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
