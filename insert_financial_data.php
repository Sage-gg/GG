<?php
// insert_financial_data.php - Insert data into financial database tables

// Prevent any output before JSON
ob_start();

// Set headers immediately
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Include your database connection
    require_once 'db.php';
    
    // Clear any previous output  
    ob_clean();
    
    // Check if connection exists
    if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'Connection not available'));
    }
    
    // Check if this is a file upload request (FormData) or JSON request
    $isFileUpload = !empty($_FILES);
    
    if ($isFileUpload) {
        // Handle FormData request with file upload
        $table = $_POST['table'] ?? null;
        $module = $_POST['module'] ?? 'unknown';
        
        // Get all POST data except table and module
        $data = [];
        foreach ($_POST as $key => $value) {
            if ($key !== 'table' && $key !== 'module' && !empty($value)) {
                $data[$key] = $value;
            }
        }
        
        // Handle file upload if present
        $fileUploaded = false;
        $uploadPath = '';
        
        // Check for either attach_receipt or receipt_attachment
        $fileFieldName = null;
        if (isset($_FILES['attach_receipt']) && $_FILES['attach_receipt']['error'] === UPLOAD_ERR_OK) {
            $fileFieldName = 'attach_receipt';
        } elseif (isset($_FILES['receipt_attachment']) && $_FILES['receipt_attachment']['error'] === UPLOAD_ERR_OK) {
            $fileFieldName = 'receipt_attachment';
        }
        
        if ($fileFieldName) {
            $uploadDir = 'uploads/receipts/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = $_FILES[$fileFieldName]['name'];
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid('receipt_') . '.' . $fileExtension;
            $uploadPath = $uploadDir . $newFileName;
            
            // Validate file type
            $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf', 'gif'];
            if (!in_array(strtolower($fileExtension), $allowedTypes)) {
                throw new Exception('Invalid file type. Only JPG, PNG, PDF allowed.');
            }
            
            // Validate file size (max 5MB)
            if ($_FILES[$fileFieldName]['size'] > 5 * 1024 * 1024) {
                throw new Exception('File size exceeds 5MB limit.');
            }
            
            if (move_uploaded_file($_FILES[$fileFieldName]['tmp_name'], $uploadPath)) {
                $data[$fileFieldName] = $uploadPath;
                $fileUploaded = true;
            } else {
                throw new Exception('Failed to upload file.');
            }
        }
        
    } else {
        // Handle JSON request
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('No data received or invalid JSON');
        }
        
        $table = $input['table'] ?? null;
        $data = $input['data'] ?? [];
        $module = $input['module'] ?? 'unknown';
    }
    
    if (!$table || empty($data)) {
        throw new Exception('Missing required fields: table or data');
    }
    
    // Validate table name (security check) - UPDATED with new tables
    $allowed_tables = [
        'budgets', 
        'collections', 
        'expenses', 
        'journal_entries', 
        'chart_of_accounts', 
        'liquidation_records', 
        'financial_reports'
    ];
    
    if (!in_array($table, $allowed_tables)) {
        throw new Exception('Invalid table name: ' . $table);
    }
    
    // Remove the ledger_table_type field if it exists (it's only for UI selection)
    if (isset($data['ledger_table_type'])) {
        unset($data['ledger_table_type']);
    }
    
    // Add timestamp if not provided
    if (!isset($data['created_at'])) {
        $data['created_at'] = date('Y-m-d H:i:s');
    }
    
    // Build dynamic INSERT query using mysqli
    $columns = array_keys($data);
    $placeholders = array_fill(0, count($columns), '?');
    $values = array_values($data);
    
    // Create the SQL
    $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
    
    // Prepare statement
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    // Build type string for bind_param
    $types = '';
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    // Bind parameters
    $stmt->bind_param($types, ...$values);
    
    // Execute the statement
    if ($stmt->execute()) {
        $insert_id = $conn->insert_id;
        $affected_rows = $stmt->affected_rows;
        
        $stmt->close();
        
        $response = [
            'success' => true,
            'message' => 'Data inserted successfully',
            'insert_id' => $insert_id,
            'affected_rows' => $affected_rows,
            'table' => $table,
            'module' => $module,
            'columns_inserted' => count($columns),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if (isset($fileUploaded) && $fileUploaded) {
            $response['file_uploaded'] = true;
            $response['file_path'] = $uploadPath;
        }
    } else {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception('Failed to insert data: ' . $error);
    }
    
} catch (Exception $e) {
    ob_clean(); // Clear any error output
    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    http_response_code(500);
}

// Clean output buffer and send JSON
ob_clean();
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
ob_end_flush();
exit();
?>
