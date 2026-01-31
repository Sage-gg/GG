<?php
/**
 * Core 2 API Sender
 * Handles sending budget data to Core 2 system
 */

// Only allow execution if included from budgets_actions.php or called directly with budget_id
if (!defined('AUTO_SYNC_ENABLED')) {
    require_once 'core2_api_config.php';
}

require_once 'db.php';

/**
 * Send budget data to Core 2 API
 * Can be called directly via URL: core2_api_sender.php?budget_id=123
 */
if (isset($_GET['budget_id']) && !isset($budget_id)) {
    $budget_id = (int)$_GET['budget_id'];
    $result = sendBudgetToCore2API($budget_id);
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

/**
 * Send budget to Core 2 API
 * 
 * @param int $budget_id Budget ID to sync
 * @return array Result with success status and message
 */
function sendBudgetToCore2API($budget_id) {
    global $conn;
    
    // Fetch budget data
    $stmt = $conn->prepare("SELECT * FROM budgets WHERE id = ?");
    $stmt->bind_param('i', $budget_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $budget = $result->fetch_assoc();
    $stmt->close();
    
    if (!$budget) {
        return [
            'success' => false,
            'message' => 'Budget not found',
            'data' => null
        ];
    }
    
    // Check if auto-sync is enabled
    if (!AUTO_SYNC_ENABLED) {
        return [
            'success' => false,
            'message' => 'Auto-sync is disabled',
            'data' => null
        ];
    }
    
    // Prepare data for Core 2
    $payload = [
        'budget_id' => $budget['id'],
        'period' => $budget['period'],
        'department' => $budget['department'],
        'cost_center' => $budget['cost_center'],
        'amount_allocated' => $budget['amount_allocated'],
        'amount_used' => $budget['amount_used'],
        'approved_by' => $budget['approved_by'],
        'approval_status' => $budget['approval_status'],
        'description' => $budget['description'],
        'sync_timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Log if debug mode enabled
    if (SYNC_DEBUG_MODE) {
        error_log('Core 2 Sync Attempt: ' . json_encode($payload));
    }
    
    // Send to Core 2 API
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => CORE2_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: ' . CORE2_API_KEY,
            'X-API-Secret: ' . CORE2_API_SECRET
        ],
        CURLOPT_TIMEOUT => API_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Handle response
    if ($response === false) {
        if (SYNC_DEBUG_MODE) {
            error_log('Core 2 Sync cURL Error: ' . $curl_error);
        }
        return [
            'success' => false,
            'message' => 'Connection error: ' . $curl_error,
            'data' => null
        ];
    }
    
    $response_data = json_decode($response, true);
    
    if ($http_code >= 200 && $http_code < 300) {
        // Success - update local record with Core 2 asset ID if provided
        if (isset($response_data['asset_id'])) {
            $stmt = $conn->prepare("UPDATE budgets SET asset_id = ? WHERE id = ?");
            $stmt->bind_param('ii', $response_data['asset_id'], $budget_id);
            $stmt->execute();
            $stmt->close();
        }
        
        if (SYNC_DEBUG_MODE) {
            error_log('Core 2 Sync Success: ' . json_encode($response_data));
        }
        
        return [
            'success' => true,
            'message' => 'Successfully synced to Core 2',
            'data' => [
                'core2_asset_id' => $response_data['asset_id'] ?? null,
                'response' => $response_data
            ]
        ];
    } else {
        // Failed
        if (SYNC_DEBUG_MODE) {
            error_log('Core 2 Sync Failed: HTTP ' . $http_code . ' - ' . $response);
        }
        
        return [
            'success' => false,
            'message' => 'API error (HTTP ' . $http_code . '): ' . ($response_data['message'] ?? 'Unknown error'),
            'data' => [
                'http_code' => $http_code,
                'response' => $response_data
            ]
        ];
    }
}
