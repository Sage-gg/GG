<?php
/**
 * API Sync Dashboard
 * View and manage budget sync status with Core 2
 */

require_once 'db.php';
require_once 'core2_api_config.php';
require_once 'core2_api_logger.php';

// Get sync statistics
$sync_stats = getSyncStatistics();
$api_stats = getAPIStatistics();
$recent_logs = getRecentAPILogs(20);

/**
 * Get sync statistics from database
 */
function getSyncStatistics() {
    global $conn;
    
    // Ensure sync table exists
    ensureSyncTableExists();
    
    $stats = [
        'total_budgets' => 0,
        'synced' => 0,
        'failed' => 0,
        'pending' => 0,
        'sync_rate' => 0
    ];
    
    // Get total budgets
    $result = $conn->query("SELECT COUNT(*) as total FROM budgets");
    if ($row = $result->fetch_assoc()) {
        $stats['total_budgets'] = $row['total'];
    }
    
    // Get sync status counts
    $result = $conn->query("
        SELECT sync_status, COUNT(*) as count 
        FROM budget_sync_log 
        GROUP BY sync_status
    ");
    
    while ($row = $result->fetch_assoc()) {
        $status = $row['sync_status'];
        $count = $row['count'];
        
        if ($status === 'synced') {
            $stats['synced'] = $count;
        } elseif ($status === 'failed') {
            $stats['failed'] = $count;
        } elseif ($status === 'pending') {
            $stats['pending'] = $count;
        }
    }
    
    // Calculate sync rate
    if ($stats['total_budgets'] > 0) {
        $stats['sync_rate'] = round(($stats['synced'] / $stats['total_budgets']) * 100, 1);
    }
    
    return $stats;
}

/**
 * Ensure sync tracking table exists
 */
function ensureSyncTableExists() {
    global $conn;
    
    $sql = "CREATE TABLE IF NOT EXISTS budget_sync_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        budget_id INT NOT NULL,
        sync_status ENUM('pending', 'synced', 'failed', 'deleted_in_core2') DEFAULT 'pending',
        core2_asset_id VARCHAR(100),
        sync_timestamp DATETIME,
        last_webhook_update DATETIME,
        error_message TEXT,
        sync_attempts INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_budget (budget_id),
        INDEX idx_sync_status (sync_status),
        INDEX idx_sync_timestamp (sync_timestamp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->query($sql);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Sync Dashboard - Core 2 Integration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .log-entry {
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
        }
        .status-badge {
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold">
                    <i class="bi bi-cloud-arrow-up"></i> API Sync Dashboard
                </h2>
                <p class="text-muted">Monitor budget synchronization with Core 2 Asset Management System</p>
            </div>
        </div>

        <!-- Sync Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card border-primary" style="border-left-color: #0d6efd !important;">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total Budgets</h6>
                        <h2 class="mb-0"><?= $sync_stats['total_budgets'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-success" style="border-left-color: #198754 !important;">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Successfully Synced</h6>
                        <h2 class="mb-0 text-success"><?= $sync_stats['synced'] ?></h2>
                        <small class="text-muted"><?= $sync_stats['sync_rate'] ?>% sync rate</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-warning" style="border-left-color: #ffc107 !important;">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Pending Sync</h6>
                        <h2 class="mb-0 text-warning"><?= $sync_stats['pending'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card border-danger" style="border-left-color: #dc3545 !important;">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Failed Syncs</h6>
                        <h2 class="mb-0 text-danger"><?= $sync_stats['failed'] ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Statistics -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">API Request Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <h4><?= $api_stats['total_requests'] ?></h4>
                                <small class="text-muted">Total Requests</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h4 class="text-success"><?= $api_stats['successful'] ?></h4>
                                <small class="text-muted">Successful</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h4 class="text-danger"><?= $api_stats['failed'] ?></h4>
                                <small class="text-muted">Failed</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h4 class="text-warning"><?= $api_stats['error_rate'] ?>%</h4>
                                <small class="text-muted">Error Rate</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuration Status -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Configuration Status</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Core 2 API URL:</strong></td>
                                <td><code><?= CORE2_API_URL ?></code></td>
                            </tr>
                            <tr>
                                <td><strong>Auto-Sync Enabled:</strong></td>
                                <td>
                                    <?php if (AUTO_SYNC_ENABLED): ?>
                                        <span class="badge bg-success">Yes</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Sync on Approval:</strong></td>
                                <td>
                                    <?php if (SYNC_ON_APPROVAL): ?>
                                        <span class="badge bg-success">Yes</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Retry Attempts:</strong></td>
                                <td><?= SYNC_RETRY_ATTEMPTS ?></td>
                            </tr>
                            <tr>
                                <td><strong>Webhook Enabled:</strong></td>
                                <td>
                                    <?php if (WEBHOOK_ENABLED): ?>
                                        <span class="badge bg-success">Yes</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="budgets_actions.php?action=bulk_sync" class="btn btn-primary" 
                               onclick="return confirm('Sync all approved budgets to Core 2?')">
                                <i class="bi bi-cloud-upload"></i> Bulk Sync All Approved Budgets
                            </a>
                            <button class="btn btn-info" onclick="refreshStats()">
                                <i class="bi bi-arrow-clockwise"></i> Refresh Statistics
                            </button>
                            <a href="?clear_logs=1" class="btn btn-warning" 
                               onclick="return confirm('Clear all API logs? This will create a backup.')">
                                <i class="bi bi-trash"></i> Clear API Logs
                            </a>
                            <a href="api_export_logs.php" class="btn btn-secondary">
                                <i class="bi bi-download"></i> Export Logs to CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent API Logs -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent API Activity</h5>
                        <small class="text-muted">Last 20 entries</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Direction</th>
                                        <th>Action</th>
                                        <th>Status</th>
                                        <th>Message</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_logs)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No API logs found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_logs as $log): ?>
                                            <tr>
                                                <td class="log-entry"><?= htmlspecialchars($log['timestamp'] ?? '') ?></td>
                                                <td>
                                                    <?php
                                                    $direction = $log['direction'] ?? 'unknown';
                                                    $badge_class = match($direction) {
                                                        'outgoing' => 'bg-primary',
                                                        'incoming' => 'bg-success',
                                                        'error' => 'bg-danger',
                                                        default => 'bg-secondary'
                                                    };
                                                    ?>
                                                    <span class="badge <?= $badge_class ?> status-badge">
                                                        <?= strtoupper($direction) ?>
                                                    </span>
                                                </td>
                                                <td class="log-entry"><?= htmlspecialchars($log['action'] ?? '') ?></td>
                                                <td>
                                                    <?php
                                                    $success = $log['response_data']['success'] ?? null;
                                                    if ($success === true): ?>
                                                        <span class="badge bg-success status-badge">SUCCESS</span>
                                                    <?php elseif ($success === false): ?>
                                                        <span class="badge bg-danger status-badge">FAILED</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary status-badge">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="log-entry">
                                                    <?= htmlspecialchars(substr($log['response_data']['message'] ?? '', 0, 50)) ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-info" 
                                                            onclick="showLogDetails(<?= htmlspecialchars(json_encode($log)) ?>)">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Log Details Modal -->
    <div class="modal fade" id="logDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">API Log Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre id="logDetailsContent" style="max-height: 500px; overflow-y: auto;"></pre>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showLogDetails(log) {
            const content = document.getElementById('logDetailsContent');
            content.textContent = JSON.stringify(log, null, 2);
            
            const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
            modal.show();
        }
        
        function refreshStats() {
            window.location.reload();
        }
    </script>
</body>
</html>

<?php
// Handle clear logs action
if (isset($_GET['clear_logs']) && $_GET['clear_logs'] == '1') {
    clearAPILogs(true);
    header('Location: api_sync_dashboard.php');
    exit;
}
?>
