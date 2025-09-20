<?php
// =============================================================================
// db.php
// MERGED DATABASE CONNECTION FILE
// Enhanced Database connection with session management and dual compatibility
// FIXED VERSION - Resolves lockout timer issues
// =============================================================================

// Set timezone to match your database
date_default_timezone_set('Asia/Manila'); // or whatever timezone your database uses

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =============================================================================
// DATABASE CONFIGURATION (Unified)
// =============================================================================

// Primary database configuration (using constants for consistency)
define('DB_HOST', 'localhost');
define('DB_USER', 'fina_LhayYhan');
define('DB_PASS', 'H8@r%ml2#0myfd-n');
define('DB_NAME', 'fina_financial_system');

// Legacy variables for backward compatibility
$host = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$database = DB_NAME;

// =============================================================================
// SESSION CONFIGURATION
// =============================================================================

define('SESSION_TIMEOUT', 10 * 60); // 10 minutes in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 15 * 60); // 15 minutes lockout

// =============================================================================
// DATABASE CONNECTION FUNCTIONS
// =============================================================================

// Main connection function (Enhanced style)
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection failed. Please check your database configuration in db.php");
    }
    
    // Set charset to utf8mb4 for proper encoding
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// =============================================================================
// GLOBAL CONNECTION VARIABLES (Dual Compatibility)
// =============================================================================

// Create primary connection (original style - for backward compatibility)
$conn = new mysqli($host, $username, $password, $database);

// Check primary connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8 (original style)
$conn->set_charset("utf8");

// Create secondary connection using function (enhanced style)
$conn2 = getDBConnection();

// =============================================================================
// LEGACY DATABASE HELPER FUNCTIONS
// =============================================================================

// Function to close connection
function closeConnection() {
    global $conn;
    if ($conn) {
        $conn->close();
    }
}

// Function to escape strings
function escape_string($string) {
    global $conn;
    return $conn->real_escape_string($string);
}

// Function to prepare statement
function prepare_statement($query) {
    global $conn;
    return $conn->prepare($query);
}

// =============================================================================
// SESSION MANAGEMENT FUNCTIONS (Enhanced from new version)
// =============================================================================

// Check if user is logged in and session is valid
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role']);
}

// Check if user has admin privileges
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

// Check session timeout and activity (Enhanced version with database cleanup)
function checkSessionTimeout() {
    if (isLoggedIn()) {
        $current_time = time();
        
        // Check if session has timed out due to inactivity
        if (isset($_SESSION['last_activity']) && ($current_time - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            // Session expired - clean up database session (from new version)
            cleanupUserSession($_SESSION['user_id'], session_id());
            session_unset();
            session_destroy();
            header("Location: login.php?timeout=1");
            exit();
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = $current_time;
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = $current_time;
        } else if ($current_time - $_SESSION['last_regeneration'] > 600) { // 10 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = $current_time;
        }
    }
}

// Redirect to login if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Redirect to login if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: index.php?access_denied=1");
        exit();
    }
}

// Clean up user session from database (Enhanced function from new version)
function cleanupUserSession($user_id, $session_id = null) {
    $conn = getDBConnection();
    
    if ($session_id) {
        // Clean up specific session
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ? AND session_id = ?");
        $stmt->bind_param("is", $user_id, $session_id);
    } else {
        // Clean up all sessions for user
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $stmt->close();
}

// =============================================================================
// LOGIN ATTEMPT TRACKING FUNCTIONS - FIXED FOR TINYINT(1) COMPATIBILITY
// =============================================================================

// FIXED: Log login attempt - Updated for tinyint(1) compatibility
function logLoginAttempt($username, $success = false, $force_log = false) {
    $conn = getDBConnection();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Don't log failed attempts during active lockout (unless forced)
    if (!$success && !$force_log && isIPLockedOut()) {
        return false; // Don't log the attempt
    }
    
    // If login was successful, clear ALL failed attempts for this IP FIRST - explicitly check for 0
    if ($success) {
        $clear_stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND success = 0");
        $clear_stmt->bind_param("s", $ip_address);
        $clear_stmt->execute();
        $clear_stmt->close();
    }
    
    // Then log the current attempt - convert boolean to int
    $success_int = $success ? 1 : 0;
    $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $ip_address, $username, $success_int);
    $stmt->execute();
    $stmt->close();
    
    return true; // Attempt was logged
}

// FIXED: Check if IP is locked out - Updated for tinyint(1) compatibility
function isIPLockedOut() {
    $conn = getDBConnection();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $lockout_threshold = date('Y-m-d H:i:s', time() - LOCKOUT_DURATION);
    
    // Count recent failed attempts (within lockout duration) - explicitly check for 0
    $stmt = $conn->prepare("
        SELECT COUNT(*) as fail_count 
        FROM login_attempts 
        WHERE ip_address = ? AND success = 0 AND attempted_at > ?
    ");
    $stmt->bind_param("ss", $ip_address, $lockout_threshold);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $fail_count = $result['fail_count'];
    $stmt->close();
    
    // If we have MAX_LOGIN_ATTEMPTS or more failures, check if lockout should still be active
    if ($fail_count >= MAX_LOGIN_ATTEMPTS) {
        // Get the timestamp of the attempt that triggered lockout (5th failure)
        $trigger_stmt = $conn->prepare("
            SELECT attempted_at 
            FROM login_attempts 
            WHERE ip_address = ? AND success = 0 AND attempted_at > ?
            ORDER BY attempted_at ASC 
            LIMIT 1 OFFSET ?
        ");
        $offset = MAX_LOGIN_ATTEMPTS - 1; // 0-indexed, so 5th attempt is offset 4
        $trigger_stmt->bind_param("ssi", $ip_address, $lockout_threshold, $offset);
        $trigger_stmt->execute();
        $trigger_result = $trigger_stmt->get_result();
        
        if ($trigger_result->num_rows > 0) {
            $trigger_time = $trigger_result->fetch_assoc()['attempted_at'];
            $trigger_timestamp = strtotime($trigger_time);
            $lockout_expires = $trigger_timestamp + LOCKOUT_DURATION;
            
            $trigger_stmt->close();
            
            // Return true if lockout is still active
            return time() < $lockout_expires;
        }
        $trigger_stmt->close();
    }
    
    return false;
}

// FIXED: Get lockout time remaining - Updated for tinyint(1) compatibility
function getLockoutTimeRemaining() {
    if (!isIPLockedOut()) {
        return 0;
    }
    
    $conn = getDBConnection();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Get the 5th most recent failed attempt (the one that triggered lockout) - explicitly check for 0
    $stmt = $conn->prepare("
        SELECT attempted_at 
        FROM login_attempts 
        WHERE ip_address = ? AND success = 0 
        ORDER BY attempted_at DESC 
        LIMIT 1 OFFSET 4
    ");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $lockout_trigger = $result->fetch_assoc()['attempted_at'];
        $lockout_trigger_timestamp = strtotime($lockout_trigger);
        $lockout_expires = $lockout_trigger_timestamp + LOCKOUT_DURATION;
        $remaining = $lockout_expires - time();
        
        $stmt->close();
        return max(0, $remaining);
    }
    
    // Fallback: check most recent failed attempt - explicitly check for 0
    $stmt = $conn->prepare("
        SELECT attempted_at 
        FROM login_attempts 
        WHERE ip_address = ? AND success = 0 
        ORDER BY attempted_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $last_attempt = $result['fetch_assoc()']['attempted_at'];
        $last_attempt_timestamp = strtotime($last_attempt);
        $lockout_expires = $last_attempt_timestamp + LOCKOUT_DURATION;
        $remaining = $lockout_expires - time();
        
        $stmt->close();
        return max(0, $remaining);
    }
    
    $stmt->close();
    return 0;
}

// Clean up expired sessions and old login attempts (Enhanced from new version)
function cleanupSessions() {
    $conn = getDBConnection();
    
    // Remove expired sessions
    $stmt = $conn->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
    $stmt->execute();
    $stmt->close();
    
    // Clean old login attempts (older than 24 hours)
    $cleanup_time = date('Y-m-d H:i:s', time() - (24 * 60 * 60));
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE attempted_at < ?");
    $stmt->bind_param("s", $cleanup_time);
    $stmt->execute();
    $stmt->close();
}

// Clean old login attempts (call this occasionally)
function cleanOldLoginAttempts() {
    $conn = getDBConnection();
    $cleanup_time = date('Y-m-d H:i:s', time() - (24 * 60 * 60)); // 24 hours ago
    
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE attempted_at < ?");
    $stmt->bind_param("s", $cleanup_time);
    $stmt->execute();
    $stmt->close();
}

// =============================================================================
// UTILITY FUNCTIONS (With function_exists check to prevent conflicts)
// =============================================================================

// Function to calculate tax amount based on tax type and amount
if (!function_exists('calculateTaxAmount')) {
    function calculateTaxAmount($taxType, $amount) {
        switch($taxType) {
            case 'VAT':
                return $amount * 0.12; // 12% VAT
            case 'Withholding':
                return $amount * 0.02; // 2% Withholding
            case 'Exempted':
            case 'None':
            default:
                return 0.00;
        }
    }
}

// Function to format currency
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return '₱' . number_format($amount, 2);
    }
}

// =============================================================================
// DEBUG FUNCTIONS (Enhanced from new version)
// =============================================================================

// Debug function to check current lockout status (Enhanced with session info)
function debugLockoutStatus() {
    if (isset($_GET['debug_lockout']) && $_GET['debug_lockout'] == 1) {
        $conn = getDBConnection();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $lockout_time = date('Y-m-d H:i:s', time() - LOCKOUT_DURATION);
        
        echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;'>";
        echo "<h3>Debug: Lockout Status</h3>";
        echo "<p><strong>Current IP:</strong> " . htmlspecialchars($ip_address) . "</p>";
        echo "<p><strong>Lockout Time Threshold:</strong> " . $lockout_time . "</p>";
        echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
        echo "<p><strong>Max Login Attempts:</strong> " . MAX_LOGIN_ATTEMPTS . "</p>";
        echo "<p><strong>Lockout Duration:</strong> " . (LOCKOUT_DURATION / 60) . " minutes</p>";
        
        // Show recent attempts
        $stmt = $conn->prepare("SELECT * FROM login_attempts WHERE ip_address = ? ORDER BY attempted_at DESC LIMIT 10");
        $stmt->bind_param("s", $ip_address);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "<h4>Recent Login Attempts (Last 10):</h4>";
        echo "<table border='1' style='width:100%; border-collapse: collapse;'>";
        echo "<tr><th>Username</th><th>Success</th><th>Time</th><th>Within Lockout Period</th></tr>";
        
        $recent_fails = 0;
        while ($row = $result->fetch_assoc()) {
            $within_period = $row['attempted_at'] > $lockout_time ? 'YES' : 'NO';
            $success_text = $row['success'] ? 'SUCCESS' : 'FAILED';
            
            if (!$row['success'] && $within_period == 'YES') {
                $recent_fails++;
            }
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td style='color: " . ($row['success'] ? 'green' : 'red') . "'>" . $success_text . "</td>";
            echo "<td>" . $row['attempted_at'] . "</td>";
            echo "<td>" . $within_period . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $stmt->close();
        
        echo "<p><strong>Recent Failed Attempts Count:</strong> " . $recent_fails . "</p>";
        echo "<p><strong>Is Locked Out:</strong> " . (isIPLockedOut() ? 'YES' : 'NO') . "</p>";
        echo "<p><strong>Time Remaining:</strong> " . getLockoutTimeRemaining() . " seconds</p>";
        
        // Show active sessions (enhanced feature)
        $session_stmt = $conn->prepare("SELECT * FROM user_sessions WHERE expires_at > NOW() ORDER BY created_at DESC LIMIT 5");
        $session_stmt->execute();
        $session_result = $session_stmt->get_result();
        
        echo "<h4>Active Sessions (Last 5):</h4>";
        echo "<table border='1' style='width:100%; border-collapse: collapse;'>";
        echo "<tr><th>User ID</th><th>Session ID</th><th>IP</th><th>Created</th><th>Expires</th></tr>";
        
        while ($session_row = $session_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $session_row['user_id'] . "</td>";
            echo "<td>" . substr($session_row['session_id'], 0, 10) . "...</td>";
            echo "<td>" . htmlspecialchars($session_row['ip_address']) . "</td>";
            echo "<td>" . $session_row['created_at'] . "</td>";
            echo "<td>" . $session_row['expires_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        $session_stmt->close();
        
        echo "</div>";
    }
}

// =============================================================================
// AUTO-EXECUTION (Runs on every page load) - Enhanced cleanup
// =============================================================================

// Auto-check session timeout on every page load
checkSessionTimeout();

// Clean old login attempts and sessions occasionally (5% chance per request - enhanced)
if (rand(1, 20) === 1) {
    cleanOldLoginAttempts();
    cleanupSessions();
}

// Show debug info if requested (remove in production)
debugLockoutStatus();

?>
