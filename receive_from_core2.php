<?php
// receive_from_core2.php - Receive budget request data from Core2 system
// Location: financials.cranecali-ms.com/receive_from_core2.php

require_once 'db.php';

// Set JSON response header
header('Content-Type: application/json');

// Enable error logging
error_log("=== Budget Request Received ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));

// Get JSON input
$rawInput = file_get_contents("php://input");
error_log("Raw Input: " . $rawInput);

$input = json_decode($rawInput, true);

// Check for JSON decode errors
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $error = [
        'success' => false, 
        'message' => 'Invalid JSON payload',
        'json_error' => json_last_error_msg(),
        'raw_input' => substr($rawInput, 0, 200) // First 200 chars for debugging
    ];
    error_log("JSON Error: " . json_encode($error));
    echo json_encode($error);
    exit;
}

// Log received data
error_log("Decoded Input: " . json_encode($input));

// Validate required fields
if (empty($input['asset_id'])) {
    http_response_code(400);
    $error = ['success' => false, 'message' => 'asset_id is required'];
    error_log("Validation Error: " . json_encode($error));
    echo json_encode($error);
    exit;
}

if (empty($input['budget_frequency'])) {
    http_response_code(400);
    $error = ['success' => false, 'message' => 'budget_frequency is required'];
    error_log("Validation Error: " . json_encode($error));
    echo json_encode($error);
    exit;
}

if (empty($input['budget_amount']) || floatval($input['budget_amount']) <= 0) {
    http_response_code(400);
    $error = ['success' => false, 'message' => 'budget_amount must be greater than 0'];
    error_log("Validation Error: " . json_encode($error));
    echo json_encode($error);
    exit;
}

try {
    // Map Core2 data to budget table
    $period = $input['budget_frequency'] ?? 'Monthly';
    $department = 'Core'; // Since it's coming from Core2 asset management
    
    // Build cost center from location and asset type
    $cost_center = 'Asset Management';
    if (!empty($input['current_location'])) {
        $cost_center = $input['current_location'];
    }
    if (!empty($input['asset_type'])) {
        $cost_center .= ' - ' . $input['asset_type'];
    }
    
    $amount_allocated = floatval($input['budget_amount'] ?? 0);
    $amount_used = 0; // New request starts at 0
    $approved_by = ''; // Will be filled by finance team
    $approval_status = 'Pending';
    
    // Build description with all available info
    $description = 'Budget request from Core2 Asset #' . $input['asset_id'];
    
    if (!empty($input['crane_name'])) {
        $description .= ' (' . $input['crane_name'] . ')';
    }
    
    if (!empty($input['asset_type'])) {
        $description .= ' - Type: ' . $input['asset_type'];
    }
    
    if (!empty($input['manufacturer'])) {
        $description .= ', Manufacturer: ' . $input['manufacturer'];
    }
    
    if (!empty($input['assigned_project'])) {
        $description .= ', Project: ' . $input['assigned_project'];
    }
    
    if (!empty($input['budget_remarks'])) {
        $description .= ' | Remarks: ' . $input['budget_remarks'];
    }

    error_log("Attempting database insert with data: " . json_encode([
        'period' => $period,
        'department' => $department,
        'cost_center' => $cost_center,
        'amount_allocated' => $amount_allocated,
        'amount_used' => $amount_used,
        'approved_by' => $approved_by,
        'approval_status' => $approval_status,
        'description' => $description
    ]));

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

    $executeResult = $stmt->execute();
    
    if (!$executeResult) {
        throw new Exception("Database insert failed: " . $stmt->error);
    }
    
    $new_budget_id = $conn->insert_id;
    $stmt->close();

    error_log("Budget created successfully with ID: " . $new_budget_id);

    // Return success response
    $response = [
        'success' => true,
        'message' => 'Budget request received and created successfully',
        'budget_id' => $new_budget_id,
        'status' => 'Pending approval',
        'details' => [
            'period' => $period,
            'department' => $department,
            'cost_center' => $cost_center,
            'amount' => $amount_allocated,
            'description' => $description
        ]
    ];
    
    error_log("Success Response: " . json_encode($response));
    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Exception caught: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create budget',
        'error' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}
?>
