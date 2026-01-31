<?php

header('Content-Type: application/json');
require_once 'expense_functions.php';

// CORS headers (adjust as needed for your environment)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// API Configuration
define('API_KEY', '02012026'); // Change this to a secure key

/**
 * Verify API Key
 */
function verifyApiKey() {
    $headers = getallheaders();
    $apiKey = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? '';
    
    if ($apiKey !== API_KEY) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized: Invalid API Key'
        ]);
        exit();
    }
}

/**
 * Log API requests (optional)
 */
function logApiRequest($action, $data, $response) {
    $logFile = 'logs/api_requests.log';
    $logDir = dirname($logFile);
    
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'action' => $action,
        'data' => $data,
        'response' => $response
    ];
    
    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);
}

/**
 * Send JSON response
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

/**
 * Validate expense data
 */
function validateExpenseData($data, $isUpdate = false) {
    $errors = [];
    
    if (!$isUpdate || isset($data['expense_date'])) {
        if (empty($data['expense_date'])) {
            $errors[] = 'expense_date is required';
        } elseif (!strtotime($data['expense_date'])) {
            $errors[] = 'expense_date must be a valid date';
        }
    }
    
    if (!$isUpdate || isset($data['category'])) {
        if (empty($data['category'])) {
            $errors[] = 'category is required';
        }
    }
    
    if (!$isUpdate || isset($data['vendor'])) {
        if (empty($data['vendor'])) {
            $errors[] = 'vendor is required';
        }
    }
    
    if (!$isUpdate || isset($data['amount'])) {
        if (!isset($data['amount']) || $data['amount'] <= 0) {
            $errors[] = 'amount must be greater than 0';
        }
    }
    
    if (!$isUpdate || isset($data['tax_type'])) {
        if (empty($data['tax_type'])) {
            $errors[] = 'tax_type is required';
        }
    }
    
    if (!$isUpdate || isset($data['payment_method'])) {
        if (empty($data['payment_method'])) {
            $errors[] = 'payment_method is required';
        }
    }
    
    if (!$isUpdate || isset($data['status'])) {
        if (empty($data['status'])) {
            $errors[] = 'status is required';
        }
    }
    
    return $errors;
}

// Verify API Key
verifyApiKey();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Only accept POST and GET
if (!in_array($method, ['POST', 'GET'])) {
    sendResponse([
        'success' => false,
        'error' => 'Method not allowed'
    ], 405);
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        
        // ==================== ADD EXPENSE ====================
        case 'add':
        case 'create':
            if ($method !== 'POST') {
                sendResponse(['success' => false, 'error' => 'POST method required'], 405);
            }
            
            // Validate data
            $errors = validateExpenseData($input);
            if (!empty($errors)) {
                sendResponse([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $errors
                ], 400);
            }
            
            // Prepare data
            $data = [
                'expense_date' => $input['expense_date'],
                'category' => $input['category'],
                'vendor' => $input['vendor'],
                'amount' => floatval($input['amount']),
                'remarks' => $input['remarks'] ?? '',
                'tax_type' => $input['tax_type'],
                'payment_method' => $input['payment_method'],
                'vehicle' => $input['vehicle'] ?? '',
                'job_linked' => $input['job_linked'] ?? '',
                'approved_by' => $input['approved_by'] ?? '',
                'status' => $input['status']
            ];
            
            // Handle receipt file if base64 encoded
            $receiptFile = null;
            if (isset($input['receipt_file_base64']) && !empty($input['receipt_file_base64'])) {
                $uploadDir = 'uploads/receipts/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileData = base64_decode($input['receipt_file_base64']);
                $extension = $input['receipt_file_extension'] ?? 'jpg';
                $fileName = 'receipt_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
                $filePath = $uploadDir . $fileName;
                
                if (file_put_contents($filePath, $fileData)) {
                    $data['receipt_file'] = $fileName;
                }
            }
            
            $result = addExpense($data);
            
            $response = [
                'success' => $result,
                'message' => $result ? 'Expense added successfully' : 'Failed to add expense',
                'data' => $result ? $data : null
            ];
            
            logApiRequest('add', $input, $response);
            sendResponse($response, $result ? 201 : 500);
            break;
        
        // ==================== UPDATE EXPENSE ====================
        case 'update':
            if ($method !== 'POST') {
                sendResponse(['success' => false, 'error' => 'POST method required'], 405);
            }
            
            if (empty($input['id'])) {
                sendResponse(['success' => false, 'error' => 'ID is required'], 400);
            }
            
            $id = intval($input['id']);
            
            // Validate data (partial validation for update)
            $errors = validateExpenseData($input, true);
            if (!empty($errors)) {
                sendResponse([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $errors
                ], 400);
            }
            
            // Prepare data
            $data = [
                'expense_date' => $input['expense_date'] ?? null,
                'category' => $input['category'] ?? null,
                'vendor' => $input['vendor'] ?? null,
                'amount' => isset($input['amount']) ? floatval($input['amount']) : null,
                'remarks' => $input['remarks'] ?? null,
                'tax_type' => $input['tax_type'] ?? null,
                'payment_method' => $input['payment_method'] ?? null,
                'vehicle' => $input['vehicle'] ?? null,
                'job_linked' => $input['job_linked'] ?? null,
                'approved_by' => $input['approved_by'] ?? null,
                'status' => $input['status'] ?? null
            ];
            
            // Remove null values
            $data = array_filter($data, function($value) {
                return $value !== null;
            });
            
            // Get existing expense data and merge
            $existingExpense = getExpenseById($id);
            if (!$existingExpense) {
                sendResponse(['success' => false, 'error' => 'Expense not found'], 404);
            }
            
            $data = array_merge($existingExpense, $data);
            
            // Handle receipt file if base64 encoded
            if (isset($input['receipt_file_base64']) && !empty($input['receipt_file_base64'])) {
                $uploadDir = 'uploads/receipts/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileData = base64_decode($input['receipt_file_base64']);
                $extension = $input['receipt_file_extension'] ?? 'jpg';
                $fileName = 'receipt_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
                $filePath = $uploadDir . $fileName;
                
                if (file_put_contents($filePath, $fileData)) {
                    $data['receipt_file'] = $fileName;
                }
            }
            
            $result = updateExpense($id, $data);
            
            $response = [
                'success' => $result,
                'message' => $result ? 'Expense updated successfully' : 'Failed to update expense',
                'data' => $result ? $data : null
            ];
            
            logApiRequest('update', $input, $response);
            sendResponse($response, $result ? 200 : 500);
            break;
        
        // ==================== DELETE EXPENSE ====================
        case 'delete':
            if ($method !== 'POST') {
                sendResponse(['success' => false, 'error' => 'POST method required'], 405);
            }
            
            if (empty($input['id'])) {
                sendResponse(['success' => false, 'error' => 'ID is required'], 400);
            }
            
            $id = intval($input['id']);
            $result = deleteExpense($id);
            
            $response = [
                'success' => $result,
                'message' => $result ? 'Expense deleted successfully' : 'Failed to delete expense'
            ];
            
            logApiRequest('delete', $input, $response);
            sendResponse($response, $result ? 200 : 500);
            break;
        
        // ==================== GET SINGLE EXPENSE ====================
        case 'get':
            if (empty($input['id']) && empty($_GET['id'])) {
                sendResponse(['success' => false, 'error' => 'ID is required'], 400);
            }
            
            $id = intval($input['id'] ?? $_GET['id']);
            $expense = getExpenseById($id);
            
            if (!$expense) {
                sendResponse(['success' => false, 'error' => 'Expense not found'], 404);
            }
            
            $response = [
                'success' => true,
                'data' => $expense
            ];
            
            sendResponse($response);
            break;
        
        // ==================== LIST EXPENSES ====================
        case 'list':
            $page = intval($input['page'] ?? $_GET['page'] ?? 1);
            $limit = intval($input['limit'] ?? $_GET['limit'] ?? 10);
            $search = $input['search'] ?? $_GET['search'] ?? '';
            
            $expenses = getExpenses($page, $limit, $search);
            $total = getTotalExpenses($search);
            $summary = getExpenseSummary($search);
            
            $response = [
                'success' => true,
                'data' => [
                    'expenses' => $expenses,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'total_pages' => ceil($total / $limit)
                    ],
                    'summary' => $summary
                ]
            ];
            
            sendResponse($response);
            break;
        
        // ==================== GET SUMMARY ====================
        case 'summary':
            $search = $input['search'] ?? $_GET['search'] ?? '';
            $summary = getExpenseSummary($search);
            
            $response = [
                'success' => true,
                'data' => $summary
            ];
            
            sendResponse($response);
            break;
        
        // ==================== INVALID ACTION ====================
        default:
            sendResponse([
                'success' => false,
                'error' => 'Invalid action',
                'available_actions' => ['add', 'update', 'delete', 'get', 'list', 'summary']
            ], 400);
            break;
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ];
    
    logApiRequest($action, $input, $response);
    sendResponse($response, 500);
}
?>
