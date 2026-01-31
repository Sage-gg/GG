<?php
/**
 * Webhook Receiver (NO SECURITY)
 * Receives updates from Core 2 - All security removed
 */

require_once 'db.php';
require_once 'core2_api_config.php';
require_once 'core2_api_logger.php';

// NO SECURITY - Allow all origins
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$response = [
    'success' => false,
    'message' => '',
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    // NO SECURITY - No webhook verification
    if (!WEBHOOK_ENABLED) {
        http_response_code(403);
        throw new Exception('Webhook endpoint is disabled');
    }
    
    $raw_input = file_get_contents('php://input');
    $payload = json_decode($raw_input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        throw new Exception('Invalid JSON payload');
    }
    
    logAPIRequest('incoming', 'webhook_received', $payload, null);
    
    $event_type = $payload['event_type'] ?? 'unknown';
    
    switch ($event_type) {
        case 'asset_updated':
            $result = handleAssetUpdated($payload);
            break;
        case 'asset_deleted':
            $result = handleAssetDeleted($payload);
            break;
        case 'asset_status_changed':
            $result = handleAssetStatusChanged($payload);
            break;
        default:
            throw new Exception('Unknown event type: ' . $event_type);
    }
    
    $response['success'] = true;
    $response['message'] = 'Webhook processed successfully';
    $response['data'] = $result;
    
    logAPIRequest('incoming', 'webhook_processed', null, $response);
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    logAPIRequest('error', 'webhook_error', null, ['error' => $e->getMessage()]);
    if (!isset($http_code)) http_response_code(500);
}

echo json_encode($response, JSON_PRETTY_PRINT);

function handleAssetUpdated($payload) {
    global $conn;
    
    $crane_id = $payload['crane_id'] ?? '';
    $asset_id = $payload['asset_id'] ?? '';
    
    if (empty($crane_id)) throw new Exception('Missing crane_id in payload');
    
    if (!preg_match('/FIN-.+-(\d+)$/', $crane_id, $matches)) {
        throw new Exception('Invalid crane_id format');
    }
    
    $budget_id = (int)$matches[1];
    
    $stmt = $conn->prepare("
        UPDATE budget_sync_log 
        SET sync_status = 'synced', core2_asset_id = ?, sync_timestamp = NOW(), last_webhook_update = NOW()
        WHERE budget_id = ?
    ");
    
    $stmt->bind_param('si', $asset_id, $budget_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return ['budget_id' => $budget_id, 'updated' => $affected > 0];
}

function handleAssetDeleted($payload) {
    global $conn;
    
    $crane_id = $payload['crane_id'] ?? '';
    if (empty($crane_id)) throw new Exception('Missing crane_id in payload');
    
    if (!preg_match('/FIN-.+-(\d+)$/', $crane_id, $matches)) {
        throw new Exception('Invalid crane_id format');
    }
    
    $budget_id = (int)$matches[1];
    
    $stmt = $conn->prepare("
        UPDATE budget_sync_log 
        SET sync_status = 'deleted_in_core2', last_webhook_update = NOW()
        WHERE budget_id = ?
    ");
    
    $stmt->bind_param('i', $budget_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return ['budget_id' => $budget_id, 'marked_deleted' => $affected > 0];
}

function handleAssetStatusChanged($payload) {
    global $conn;
    
    $crane_id = $payload['crane_id'] ?? '';
    $new_status = $payload['new_status'] ?? '';
    
    if (empty($crane_id) || empty($new_status)) {
        throw new Exception('Missing required fields in payload');
    }
    
    if (!preg_match('/FIN-.+-(\d+)$/', $crane_id, $matches)) {
        throw new Exception('Invalid crane_id format');
    }
    
    $budget_id = (int)$matches[1];
    
    $status_map = [
        'Active' => 'Approved',
        'Under Maintenance' => 'Pending',
        'Inactive' => 'Rejected'
    ];
    
    $budget_status = $status_map[$new_status] ?? null;
    
    if ($budget_status) {
        $stmt = $conn->prepare("UPDATE budgets SET approval_status = ? WHERE id = ?");
        $stmt->bind_param('si', $budget_status, $budget_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        $stmt = $conn->prepare("UPDATE budget_sync_log SET last_webhook_update = NOW() WHERE budget_id = ?");
        $stmt->bind_param('i', $budget_id);
        $stmt->execute();
        $stmt->close();
        
        return ['budget_id' => $budget_id, 'new_status' => $budget_status, 'updated' => $affected > 0];
    }
    
    return ['budget_id' => $budget_id, 'updated' => false, 'reason' => 'Unknown status mapping'];
}
