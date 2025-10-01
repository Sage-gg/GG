<?php
// check_db_connection.php - Database connection checker for AJAX calls

// Prevent any output before JSON
ob_start();

// Set headers immediately
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
    
    // Check if connection exists and is valid
    if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'Connection not available'));
    }
    
    // Test the connection with a simple query
    $result = $conn->query("SELECT 1 as test");
    
    if (!$result) {
        throw new Exception('Database query test failed: ' . $conn->error);
    }
    
    $test_row = $result->fetch_assoc();
    
    if ($test_row['test'] == 1) {
        $response = [
            'success' => true,
            'message' => 'Connected to financial_system database successfully',
            'database' => defined('DB_NAME') ? DB_NAME : 'financial_system',
            'host' => defined('DB_HOST') ? DB_HOST : 'localhost',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } else {
        throw new Exception('Connection test validation failed');
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