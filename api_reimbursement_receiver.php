<?php
/**
 * API Receiver for Reimbursement Management System
 * 
 * This endpoint receives reimbursement data from external systems
 * and inserts it into the financial_reimbursement system
 * 
 * Endpoint: api_reimbursement_receiver.php
 * Method: POST
 * Content-Type: application/json
 * 
 * Authentication: API Key in header (X-API-Key)
 */

// Set headers for API response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Modify this in production
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Only POST requests are accepted.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Include database connection
require_once 'db.php';

// Configuration
define('MAX_AMOUNT', 1000000); // Maximum reimbursement amount
define('ALLOWED_DEPARTMENTS', ['HR', 'Core']);
define('ALLOWED_STATUSES', ['Pending', 'Approved', 'Rejected']);

/**
 * Authenticate API request
 * Note: Authentication is disabled. Add IP whitelist or other auth as needed.
 */
function authenticateRequest() {
    // Optional: Add IP whitelist here
    // $allowed_ips = ['192.168.1.100', '10.0.0.50'];
    // if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
    //     return ['success' => false, 'error' => 'Access denied from IP: ' . $_SERVER['REMOTE_ADDR']];
    // }
    
    return ['success' => true];
}

/**
 * Validate reimbursement data
 */
function validateReimbursementData($data) {
    $errors = [];
    
    // Required fields
    $requiredFields = [
        'employee_name',
        'department',
        'cost_center',
        'reimbursement_type',
        'amount',
        'expense_date'
    ];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $errors[] = "Missing required field: $field";
        }
    }
    
    // Validate employee_name
    if (isset($data['employee_name']) && strlen($data['employee_name']) > 255) {
        $errors[] = "employee_name exceeds maximum length of 255 characters";
    }
    
    // Validate employee_id
    if (isset($data['employee_id']) && strlen($data['employee_id']) > 100) {
        $errors[] = "employee_id exceeds maximum length of 100 characters";
    }
    
    // Validate contact_no
    if (isset($data['contact_no']) && strlen($data['contact_no']) > 50) {
        $errors[] = "contact_no exceeds maximum length of 50 characters";
    }
    
    // Validate department
    if (isset($data['department']) && !in_array($data['department'], ALLOWED_DEPARTMENTS)) {
        $errors[] = "Invalid department. Allowed values: " . implode(', ', ALLOWED_DEPARTMENTS);
    }
    
    // Validate cost_center
    if (isset($data['cost_center']) && strlen($data['cost_center']) > 120) {
        $errors[] = "cost_center exceeds maximum length of 120 characters";
    }
    
    // Validate reimbursement_type
    if (isset($data['reimbursement_type']) && strlen($data['reimbursement_type']) > 100) {
        $errors[] = "reimbursement_type exceeds maximum length of 100 characters";
    }
    
    // Validate amount
    if (isset($data['amount'])) {
        if (!is_numeric($data['amount'])) {
            $errors[] = "amount must be a numeric value";
        } elseif ($data['amount'] <= 0) {
            $errors[] = "amount must be greater than 0";
        } elseif ($data['amount'] > MAX_AMOUNT) {
            $errors[] = "amount exceeds maximum allowed value of " . MAX_AMOUNT;
        }
    }
    
    // Validate expense_date
    if (isset($data['expense_date'])) {
        $date = DateTime::createFromFormat('Y-m-d', $data['expense_date']);
        if (!$date || $date->format('Y-m-d') !== $data['expense_date']) {
            $errors[] = "expense_date must be in YYYY-MM-DD format";
        }
    }
    
    // Validate status if provided
    if (isset($data['status']) && !in_array($data['status'], ALLOWED_STATUSES)) {
        $errors[] = "Invalid status. Allowed values: " . implode(', ', ALLOWED_STATUSES);
    }
    
    // Validate payment_method length
    if (isset($data['payment_method']) && strlen($data['payment_method']) > 100) {
        $errors[] = "payment_method exceeds maximum length of 100 characters";
    }
    
    // Validate payment_reference length
    if (isset($data['payment_reference']) && strlen($data['payment_reference']) > 255) {
        $errors[] = "payment_reference exceeds maximum length of 255 characters";
    }
    
    // Validate approved_by length
    if (isset($data['approved_by']) && strlen($data['approved_by']) > 120) {
        $errors[] = "approved_by exceeds maximum length of 120 characters";
    }
    
    return $errors;
}

/**
 * Find or create budget link
 */
function findBudgetId($conn, $department, $cost_center) {
    $stmt = $conn->prepare("SELECT id FROM budgets WHERE department = ? AND cost_center = ? LIMIT 1");
    $stmt->bind_param('ss', $department, $cost_center);
    $stmt->execute();
    $result = $stmt->get_result();
    $budget = $result->fetch_assoc();
    $stmt->close();
    
    return $budget ? $budget['id'] : null;
}

/**
 * Insert reimbursement into database
 */
function insertReimbursement($conn, $data) {
    // Find matching budget
    $budget_id = findBudgetId($conn, $data['department'], $data['cost_center']);
    
    // Prepare data with defaults
    $employee_name = $data['employee_name'];
    $employee_id = $data['employee_id'] ?? null;
    $address = $data['address'] ?? null;
    $contact_no = $data['contact_no'] ?? null;
    $department = $data['department'];
    $cost_center = $data['cost_center'];
    $reimbursement_type = $data['reimbursement_type'];
    $amount = (float)$data['amount'];
    $expense_date = $data['expense_date'];
    $description = $data['description'] ?? null;
    $status = $data['status'] ?? 'Pending';
    $approved_by = $data['approved_by'] ?? null;
    $payment_method = $data['payment_method'] ?? null;
    $payment_reference = $data['payment_reference'] ?? null;
    $remarks = $data['remarks'] ?? null;
    
    // Parse dates if provided
    $submission_date = isset($data['submission_date']) ? $data['submission_date'] : date('Y-m-d H:i:s');
    $approved_date = $data['approved_date'] ?? null;
    $payment_date = $data['payment_date'] ?? null;
    
    // Note: receipt_file and receipt_folder are not handled via API (would need file upload)
    $receipt_file = null;
    $receipt_folder = null;
    
    // Prepare SQL statement
    $sql = "INSERT INTO reimbursements (
        budget_id, employee_name, employee_id, address, contact_no,
        department, cost_center, reimbursement_type, amount, expense_date,
        submission_date, description, receipt_file, receipt_folder, status,
        approved_by, approved_date, payment_date, payment_method, 
        payment_reference, remarks
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param(
        'isssssssdssssssssssss',
        $budget_id,
        $employee_name,
        $employee_id,
        $address,
        $contact_no,
        $department,
        $cost_center,
        $reimbursement_type,
        $amount,
        $expense_date,
        $submission_date,
        $description,
        $receipt_file,
        $receipt_folder,
        $status,
        $approved_by,
        $approved_date,
        $payment_date,
        $payment_method,
        $payment_reference,
        $remarks
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Database execute failed: " . $stmt->error);
    }
    
    $insert_id = $stmt->insert_id;
    $stmt->close();
    
    // Update budget if approved and budget exists
    if ($status === 'Approved' && $budget_id) {
        $update_sql = "UPDATE budgets SET amount_used = amount_used + ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('di', $amount, $budget_id);
        $update_stmt->execute();
        $update_stmt->close();
    }
    
    return $insert_id;
}

/**
 * Log API request (optional - for audit trail)
 */
function logApiRequest($data, $response, $success) {
    $log_file = 'logs/api_reimbursement_' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'success' => $success,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'data' => $data,
        'response' => $response
    ];
    
    file_put_contents($log_file, json_encode($log_entry) . PHP_EOL, FILE_APPEND);
}

// Main execution
try {
    // Authenticate request
    $auth = authenticateRequest();
    if (!$auth['success']) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => $auth['error'],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON format: ' . json_last_error_msg(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Check if batch or single record
    $isBatch = isset($data['reimbursements']) && is_array($data['reimbursements']);
    $records = $isBatch ? $data['reimbursements'] : [$data];
    
    $results = [];
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($records as $index => $record) {
        // Validate data
        $validationErrors = validateReimbursementData($record);
        
        if (!empty($validationErrors)) {
            $errorCount++;
            $results[] = [
                'index' => $index,
                'success' => false,
                'errors' => $validationErrors
            ];
            continue;
        }
        
        // Insert into database
        try {
            $reimbursement_id = insertReimbursement($conn, $record);
            $successCount++;
            $results[] = [
                'index' => $index,
                'success' => true,
                'reimbursement_id' => $reimbursement_id,
                'employee_name' => $record['employee_name'],
                'amount' => $record['amount']
            ];
        } catch (Exception $e) {
            $errorCount++;
            $results[] = [
                'index' => $index,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Prepare response
    $response = [
        'success' => $errorCount === 0,
        'total_records' => count($records),
        'successful' => $successCount,
        'failed' => $errorCount,
        'results' => $results,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Log the request (optional)
    logApiRequest($data, $response, $errorCount === 0);
    
    // Set appropriate HTTP status code
    http_response_code($errorCount === 0 ? 201 : 207); // 201 Created or 207 Multi-Status
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    $error_response = [
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    logApiRequest($data ?? [], $error_response, false);
    
    echo json_encode($error_response);
}

$conn->close();
