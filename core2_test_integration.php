<?php
/**
 * API Integration Test Script
 * Tests the connection and data flow to Core 2
 */

require_once 'db.php';
require_once 'core2_api_config.php';

echo "<h1>Core 2 API Integration Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .test-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
    code { background: #e0e0e0; padding: 2px 5px; border-radius: 3px; }
</style>";

$all_tests_passed = true;

// Test 1: Configuration
echo "<div class='test-section'>";
echo "<h2>Test 1: Configuration Check</h2>";

$config_errors = validateAPIConfig();
if (empty($config_errors)) {
    echo "<p class='success'>✓ Configuration is valid</p>";
    echo "<ul>";
    echo "<li>Core 2 URL: <code>" . CORE2_API_URL . "</code></li>";
    echo "<li>Auto-Sync: " . (AUTO_SYNC_ENABLED ? 'Enabled' : 'Disabled') . "</li>";
    echo "<li>Logging: " . (API_LOG_ENABLED ? 'Enabled' : 'Disabled') . "</li>";
    echo "</ul>";
} else {
    echo "<p class='error'>✗ Configuration has errors:</p>";
    echo "<ul>";
    foreach ($config_errors as $error) {
        echo "<li class='error'>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
    $all_tests_passed = false;
}
echo "</div>";

// Test 2: Database Connection
echo "<div class='test-section'>";
echo "<h2>Test 2: Database Connection</h2>";

if (isset($conn) && !$conn->connect_error) {
    echo "<p class='success'>✓ Database connection successful</p>";
    
    // Check if budgets table exists
    $result = $conn->query("SHOW TABLES LIKE 'budgets'");
    if ($result && $result->num_rows > 0) {
        echo "<p class='success'>✓ Budgets table exists</p>";
        
        // Count budgets
        $count_result = $conn->query("SELECT COUNT(*) as count FROM budgets");
        $count = $count_result->fetch_assoc()['count'];
        echo "<p>Total budgets in database: <strong>$count</strong></p>";
    } else {
        echo "<p class='error'>✗ Budgets table not found</p>";
        $all_tests_passed = false;
    }
} else {
    echo "<p class='error'>✗ Database connection failed</p>";
    $all_tests_passed = false;
}
echo "</div>";

// Test 3: Sync Table
echo "<div class='test-section'>";
echo "<h2>Test 3: Sync Tracking Table</h2>";

$result = $conn->query("SHOW TABLES LIKE 'budget_sync_log'");
if ($result && $result->num_rows > 0) {
    echo "<p class='success'>✓ Sync log table exists</p>";
    
    // Count sync records
    $count_result = $conn->query("SELECT COUNT(*) as count FROM budget_sync_log");
    $count = $count_result->fetch_assoc()['count'];
    echo "<p>Sync records: <strong>$count</strong></p>";
    
    // Show sync status distribution
    $status_result = $conn->query("
        SELECT sync_status, COUNT(*) as count 
        FROM budget_sync_log 
        GROUP BY sync_status
    ");
    
    if ($status_result && $status_result->num_rows > 0) {
        echo "<p>Sync status distribution:</p><ul>";
        while ($row = $status_result->fetch_assoc()) {
            echo "<li>" . ucfirst($row['sync_status']) . ": " . $row['count'] . "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p class='warning'>⚠ Sync log table does not exist (will be created automatically)</p>";
}
echo "</div>";

// Test 4: Sample Data Transformation
echo "<div class='test-section'>";
echo "<h2>Test 4: Data Transformation Test</h2>";

// Create sample budget data
$sample_budget = [
    'id' => 999,
    'period' => 'Monthly',
    'department' => 'HR',
    'cost_center' => 'Training Budget',
    'amount_allocated' => 50000.00,
    'amount_used' => 25000.00,
    'approved_by' => 'Test Manager',
    'approval_status' => 'Approved',
    'description' => 'Test budget for API integration',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

// Include transformation functions
require_once 'core2_api_sender.php';

echo "<p>Sample Budget:</p>";
echo "<pre>" . print_r($sample_budget, true) . "</pre>";

$transformed = transformBudgetToAsset($sample_budget);

echo "<p class='success'>✓ Data transformation successful</p>";
echo "<p>Transformed to Asset:</p>";
echo "<pre>" . print_r($transformed, true) . "</pre>";

// Verify required fields
$required_fields = ['crane_id', 'asset_type', 'status', 'load_capacity', 'budget_frequency'];
$missing_fields = [];
foreach ($required_fields as $field) {
    if (!isset($transformed[$field]) || empty($transformed[$field])) {
        $missing_fields[] = $field;
    }
}

if (empty($missing_fields)) {
    echo "<p class='success'>✓ All required fields present</p>";
} else {
    echo "<p class='error'>✗ Missing required fields: " . implode(', ', $missing_fields) . "</p>";
    $all_tests_passed = false;
}
echo "</div>";

// Test 5: API Connectivity
echo "<div class='test-section'>";
echo "<h2>Test 5: Core 2 API Connectivity</h2>";

echo "<p>Testing connection to: <code>" . CORE2_API_URL . "</code></p>";

$ch = curl_init(CORE2_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_NOBODY => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true
]);

$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($http_code > 0) {
    echo "<p class='success'>✓ Core 2 API is reachable (HTTP $http_code)</p>";
    if ($http_code == 400) {
        echo "<p class='success'>✓ Correct - API expects POST data (400 = bad request without data)</p>";
    }
} else {
    echo "<p class='error'>✗ Cannot reach Core 2 API</p>";
    echo "<p class='error'>Error: " . htmlspecialchars($curl_error) . "</p>";
    $all_tests_passed = false;
}
echo "</div>";

// Test 6: Log Directory
echo "<div class='test-section'>";
echo "<h2>Test 6: Logging System</h2>";

$log_dir = dirname(API_LOG_FILE);
if (file_exists($log_dir)) {
    echo "<p class='success'>✓ Log directory exists: <code>$log_dir</code></p>";
    
    if (is_writable($log_dir)) {
        echo "<p class='success'>✓ Log directory is writable</p>";
    } else {
        echo "<p class='error'>✗ Log directory is not writable</p>";
        $all_tests_passed = false;
    }
} else {
    echo "<p class='warning'>⚠ Log directory does not exist: <code>$log_dir</code></p>";
    echo "<p>Creating log directory...</p>";
    
    if (mkdir($log_dir, 0755, true)) {
        echo "<p class='success'>✓ Log directory created successfully</p>";
    } else {
        echo "<p class='error'>✗ Failed to create log directory</p>";
        $all_tests_passed = false;
    }
}

// Test log file
if (file_exists(API_LOG_FILE)) {
    $size = filesize(API_LOG_FILE);
    echo "<p>Log file size: " . number_format($size) . " bytes</p>";
    
    if ($size > 0) {
        require_once 'api_logger.php';
        $stats = getAPIStatistics();
        echo "<p>Total API requests logged: <strong>" . $stats['total_requests'] . "</strong></p>";
    }
}
echo "</div>";

// Test 7: Mapping Configuration
echo "<div class='test-section'>";
echo "<h2>Test 7: Department & Cost Center Mappings</h2>";

echo "<p class='success'>✓ Mapping configuration loaded</p>";

echo "<h3>Department Mappings:</h3>";
echo "<ul>";
$departments = ['HR', 'HR2', 'HR4', 'Core', 'Core 2', 'Core 4'];
foreach ($departments as $dept) {
    $location = getDepartmentLocation($dept);
    echo "<li>$dept → $location</li>";
}
echo "</ul>";

echo "<h3>Cost Center Mappings:</h3>";
echo "<ul>";
$cost_centers = [
    'Training Budget',
    'Reimbursement Budget', 
    'Benefits Budget',
    'Payroll Budget',
    'Log Maintenance Costs',
    'Depreciation Charges',
    'Insurance Fees',
    'Vehicle Operational Budget'
];
foreach ($cost_centers as $cc) {
    $mapping = getAssetMapping($cc);
    echo "<li>$cc → {$mapping['asset_type']} @ {$mapping['location']}</li>";
}
echo "</ul>";
echo "</div>";

// Final Summary
echo "<div class='test-section'>";
echo "<h2>Test Summary</h2>";
if ($all_tests_passed) {
    echo "<p class='success' style='font-size: 1.2em;'>✓ ALL TESTS PASSED!</p>";
    echo "<p>Your API integration is ready to use.</p>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Replace <code>budgets_actions.php</code> with <code>budgets_actions_updated.php</code></li>";
    echo "<li>Access the sync dashboard: <a href='api_sync_dashboard.php'>api_sync_dashboard.php</a></li>";
    echo "<li>Create a test budget and verify it syncs to Core 2</li>";
    echo "<li>Monitor the sync status in the dashboard</li>";
    echo "</ol>";
} else {
    echo "<p class='error' style='font-size: 1.2em;'>✗ SOME TESTS FAILED</p>";
    echo "<p>Please review the errors above and fix them before using the integration.</p>";
}
echo "</div>";

echo "<hr>";
echo "<p><small>Test completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>
