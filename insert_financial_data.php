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
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('No data received or invalid JSON');
    }
    
    if (!isset($input['table']) || !isset($input['data'])) {
        throw new Exception('Missing required fields: table or data');
    }
    
    $table = $input['table'];
    $data = $input['data'];
    $module = $input['module'] ?? 'unknown';
    
    if (empty($data)) {
        throw new Exception('No data provided for insertion');
    }
    
    // Validate table name (security check)
    $allowed_tables = ['budgets', 'collections', 'expenses', 'journal_entries', 'financial_reports'];
    if (!in_array($table, $allowed_tables)) {
        throw new Exception('Invalid table name: ' . $table);
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