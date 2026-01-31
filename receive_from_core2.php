<?php
// receive_from_core2.php - Receive budget request data from Core2 system
require_once 'db.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

// Validate required fields
if (empty($input['asset_id']) || empty($input['budget_frequency']) || empty($input['budget_amount'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'asset_id, budget_frequency, and budget_amount are required']);
    exit;
}

try {
    // Map Core2 data to your budget table
    $period = $input['budget_frequency'] ?? 'Monthly';
    $department = 'Core'; // Since it's coming from Core2 asset management
    $cost_center = $input['current_location'] ?? 'Vehicle Operational Budget';
    $amount_allocated = floatval($input['budget_amount'] ?? 0);
    $amount_used = 0; // New request starts at 0
    $approved_by = '';
    $approval_status = 'Pending';
    $description = 'Budget request from Core2 Asset #' . $input['asset_id'];
    
    if (!empty($input['budget_remarks'])) {
        $description .= ' - ' . $input['budget_remarks'];
    }

    // Insert into budgets table
    $stmt = $conn->prepare("
        INSERT INTO budgets (
            period, department, cost_center, amount_allocated, 
            amount_used, approved_by, approval_status, description
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        'sssddsss',
        $period,
        $department,
        $cost_center,
        $amount_allocated,
        $amount_used,
        $approved_by,
        $approval_status,
        $description
    );

    $stmt->execute();
    $new_budget_id = $conn->insert_id;
    $stmt->close();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Budget request received and created successfully',
        'budget_id' => $new_budget_id,
        'status' => 'Pending approval'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create budget',
        'error' => $e->getMessage()
    ]);
}
