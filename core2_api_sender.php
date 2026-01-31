<?php
/**
 * API Sender - Send Budget Data to Core 2 (NO SECURITY)
 * Security features REMOVED for easy integration
 */

require_once 'db.php';
require_once 'core2_api_config.php';
require_once 'core2_api_logger.php';

// NO SECURITY - Allow all origins and methods
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    $budget_id = null;
    
    if (isset($_GET['budget_id'])) {
        $budget_id = (int)$_GET['budget_id'];
    } elseif (isset($_POST['budget_id'])) {
        $budget_id = (int)$_POST['budget_id'];
    } elseif (isset($_POST['id'])) {
        $budget_id = (int)$_POST['id'];
    }
    
    if (empty($budget_id) || $budget_id <= 0) {
        throw new Exception('Invalid or missing budget ID');
    }
    
    $stmt = $conn->prepare("
        SELECT id, period, department, cost_center, amount_allocated, amount_used, 
               approved_by, approval_status, description, created_at, updated_at
        FROM budgets WHERE id = ?
    ");
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $budget_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $budget = $result->fetch_assoc();
    $stmt->close();
    
    if (!$budget) {
        throw new Exception('Budget record not found with ID: ' . $budget_id);
    }
    
    $payload = transformBudgetToAsset($budget);
    logAPIRequest('outgoing', 'core2_insert_asset', $payload, null);
    
    // NO SECURITY - Send without SSL verification
    $core2_response = sendToCore2NoSecurity($payload);
    
    logAPIRequest('incoming', 'core2_insert_asset_response', null, $core2_response);
    
    if ($core2_response['success']) {
        updateBudgetSyncStatus($budget_id, 'synced', $core2_response['asset_id'] ?? null);
        
        $response['success'] = true;
        $response['message'] = 'Budget data successfully sent to Core 2';
        $response['data'] = [
            'budget_id' => $budget_id,
            'core2_asset_id' => $core2_response['asset_id'] ?? null,
            'core2_response' => $core2_response
        ];
    } else {
        updateBudgetSyncStatus($budget_id, 'failed', null, $core2_response['message'] ?? 'Unknown error');
        throw new Exception('Core 2 API Error: ' . ($core2_response['message'] ?? 'Unknown error'));
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    logAPIRequest('error', 'api_sender_error', null, ['error' => $e->getMessage()]);
    http_response_code(500);
}

echo json_encode($response, JSON_PRETTY_PRINT);

function transformBudgetToAsset($budget) {
    $crane_id = generateCraneId($budget);
    $budget_frequency = mapPeriodToFrequency($budget['period']);
    $operational_hours = calculateOperationalHours($budget);
    $asset_mapping = mapCostCenterToAsset($budget['cost_center'], $budget['department']);
    
    return [
        'crane_id' => $crane_id,
        'asset_type' => $asset_mapping['asset_type'],
        'manufacturer' => 'Financial Budget System',
        'year_manufactured' => (int)date('Y'),
        'status' => mapApprovalToStatus($budget['approval_status']),
        'current_location' => $asset_mapping['location'],
        'assigned_project' => $budget['department'] . ' - ' . $budget['cost_center'],
        'operational_hours' => $operational_hours,
        'load_capacity' => (float)$budget['amount_allocated'],
        'last_inspection' => $budget['created_at'],
        'next_maintenance' => calculateNextMaintenance($budget['period']),
        'safety_certificate_valid_until' => calculateCertificateExpiry($budget['period']),
        'remarks' => buildRemarks($budget),
        'budget_frequency' => $budget_frequency
    ];
}

function generateCraneId($budget) {
    $dept_code = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', $budget['department']), 0, 4));
    $cc_code = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', $budget['cost_center']), 0, 8));
    $period_code = strtoupper(substr($budget['period'], 0, 3));
    return sprintf('FIN-%s-%s-%s-%d', $dept_code, $cc_code, $period_code, $budget['id']);
}

function mapPeriodToFrequency($period) {
    $mapping = ['Daily' => 'Daily', 'Bi-weekly' => 'Bi-weekly', 'Monthly' => 'Monthly', 'Annually' => 'Annually'];
    return $mapping[$period] ?? 'Monthly';
}

function calculateOperationalHours($budget) {
    $allocated = (float)$budget['amount_allocated'];
    $used = (float)$budget['amount_used'];
    if ($allocated == 0) return 0;
    
    $usage_rate = $used / $allocated;
    $hours_per_year = 8760;
    
    switch ($budget['period']) {
        case 'Daily': return round($usage_rate * 24, 2);
        case 'Bi-weekly': return round($usage_rate * 336, 2);
        case 'Monthly': return round($usage_rate * 730, 2);
        case 'Annually': return round($usage_rate * $hours_per_year, 2);
        default: return round($usage_rate * 730, 2);
    }
}

function mapCostCenterToAsset($cost_center, $department) {
    $mapping = [
        'Training Budget' => ['asset_type' => 'Training Asset', 'location' => 'HR Training Center'],
        'Reimbursement Budget' => ['asset_type' => 'Administrative Asset', 'location' => 'HR Office'],
        'Benefits Budget' => ['asset_type' => 'HR Benefits Asset', 'location' => 'HR Department'],
        'Payroll Budget' => ['asset_type' => 'Payroll Asset', 'location' => 'HR Payroll Division'],
        'Log Maintenance Costs' => ['asset_type' => 'Maintenance Crane', 'location' => 'Core Maintenance Yard'],
        'Depreciation Charges' => ['asset_type' => 'Depreciation Asset', 'location' => 'Core Asset Registry'],
        'Insurance Fees' => ['asset_type' => 'Insured Crane', 'location' => 'Core Insurance Division'],
        'Vehicle Operational Budget' => ['asset_type' => 'Operational Crane', 'location' => 'Core Operations']
    ];
    
    return $mapping[$cost_center] ?? ['asset_type' => 'Crane', 'location' => $department . ' Department'];
}

function mapApprovalToStatus($approval_status) {
    $mapping = ['Approved' => 'Active', 'Pending' => 'Under Maintenance', 'Rejected' => 'Inactive'];
    return $mapping[$approval_status] ?? 'Active';
}

function calculateNextMaintenance($period) {
    $now = new DateTime();
    switch ($period) {
        case 'Daily': $now->modify('+1 day'); break;
        case 'Bi-weekly': $now->modify('+14 days'); break;
        case 'Monthly': $now->modify('+1 month'); break;
        case 'Annually': $now->modify('+1 year'); break;
        default: $now->modify('+1 month');
    }
    return $now->format('Y-m-d');
}

function calculateCertificateExpiry($period) {
    $now = new DateTime();
    switch ($period) {
        case 'Daily': $now->modify('+1 month'); break;
        case 'Bi-weekly': $now->modify('+3 months'); break;
        case 'Monthly': $now->modify('+6 months'); break;
        case 'Annually': $now->modify('+1 year'); break;
        default: $now->modify('+6 months');
    }
    return $now->format('Y-m-d');
}

function buildRemarks($budget) {
    $remarks = [
        "Budget Allocation: {$budget['period']}",
        "Department: {$budget['department']}",
        "Cost Center: {$budget['cost_center']}",
        "Allocated: ₱" . number_format($budget['amount_allocated'], 2),
        "Used: ₱" . number_format($budget['amount_used'], 2),
        "Remaining: ₱" . number_format($budget['amount_allocated'] - $budget['amount_used'], 2)
    ];
    
    if (!empty($budget['approved_by'])) $remarks[] = "Approved By: {$budget['approved_by']}";
    $remarks[] = "Status: {$budget['approval_status']}";
    if (!empty($budget['description'])) $remarks[] = "Description: {$budget['description']}";
    
    return implode(' | ', $remarks);
}

/**
 * NO SECURITY - Send to Core 2 without SSL verification
 */
function sendToCore2NoSecurity($payload) {
    $core2_url = CORE2_API_URL;
    $ch = curl_init($core2_url);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_TIMEOUT => API_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,  // NO SECURITY
        CURLOPT_SSL_VERIFYHOST => 0       // NO SECURITY
    ]);
    
    $response_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($response_body === false) {
        return ['success' => false, 'message' => 'cURL Error: ' . $curl_error, 'http_code' => 0];
    }
    
    $response_data = json_decode($response_body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'message' => 'Invalid JSON response from Core 2',
            'http_code' => $http_code,
            'raw_response' => $response_body
        ];
    }
    
    $response_data['http_code'] = $http_code;
    return $response_data;
}

function updateBudgetSyncStatus($budget_id, $status, $core2_asset_id = null, $error_message = null) {
    global $conn;
    ensureSyncTableExists();
    
    $stmt = $conn->prepare("
        INSERT INTO budget_sync_log 
        (budget_id, sync_status, core2_asset_id, sync_timestamp, error_message)
        VALUES (?, ?, ?, NOW(), ?)
        ON DUPLICATE KEY UPDATE
            sync_status = VALUES(sync_status),
            core2_asset_id = VALUES(core2_asset_id),
            sync_timestamp = VALUES(sync_timestamp),
            error_message = VALUES(error_message),
            sync_attempts = sync_attempts + 1
    ");
    
    $stmt->bind_param('isss', $budget_id, $status, $core2_asset_id, $error_message);
    $stmt->execute();
    $stmt->close();
}

function ensureSyncTableExists() {
    global $conn;
    $sql = "CREATE TABLE IF NOT EXISTS budget_sync_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        budget_id INT NOT NULL,
        sync_status ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
        core2_asset_id VARCHAR(100),
        sync_timestamp DATETIME,
        error_message TEXT,
        sync_attempts INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_budget (budget_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
}
