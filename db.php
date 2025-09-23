<?php
// =============================================================================
// db.php - ENHANCED WITH EMAIL 2FA SYSTEM - FIXED VERSION
// Enhanced Database connection with session management, dual compatibility, and email verification
// =============================================================================

// Set timezone to match your database
date_default_timezone_set('Asia/Manila');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include email configuration
require_once 'email_config.php';

// =============================================================================
// EMAIL VERIFICATION CONSTANTS (Add missing constants)
// =============================================================================
if (!defined('VERIFICATION_CODE_EXPIRY')) {
    define('VERIFICATION_CODE_EXPIRY', 10 * 60); // 10 minutes
}
if (!defined('MAX_VERIFICATION_ATTEMPTS')) {
    define('MAX_VERIFICATION_ATTEMPTS', 3);
}
if (!defined('ACCESS_TOKEN_VALIDITY')) {
    define('ACCESS_TOKEN_VALIDITY', 10 * 24 * 60 * 60); // 10 days
}

// =============================================================================
// PHPMAILER SETUP - FIXED VERSION
// =============================================================================
$phpmailer_available = false;

// Include PHPMailer - with proper error handling
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    $phpmailer_available = true;
} 
elseif (file_exists('PHPMailer/src/PHPMailer.php')) {
    require_once 'PHPMailer/src/PHPMailer.php';
    require_once 'PHPMailer/src/SMTP.php';
    require_once 'PHPMailer/src/Exception.php';
    $phpmailer_available = true;
}
elseif (file_exists('lib/phpmailer/src/PHPMailer.php')) {
    require_once 'lib/phpmailer/src/PHPMailer.php';
    require_once 'lib/phpmailer/src/SMTP.php';
    require_once 'lib/phpmailer/src/Exception.php';
    $phpmailer_available = true;
}
elseif (file_exists('includes/PHPMailer/src/PHPMailer.php')) {
    require_once 'includes/PHPMailer/src/PHPMailer.php';
    require_once 'includes/PHPMailer/src/SMTP.php';
    require_once 'includes/PHPMailer/src/Exception.php';
    $phpmailer_available = true;
}

// Log warning if PHPMailer not found
if (!$phpmailer_available) {
    error_log("WARNING: PHPMailer not found. Email verification will be disabled.");
}

// Use statements must be at top level - always include them
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// =============================================================================
// DATABASE CONFIGURATION (Unified)
// =============================================================================

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

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection failed. Please check your database configuration in db.php");
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// =============================================================================
// GLOBAL CONNECTION VARIABLES (Dual Compatibility)
// =============================================================================

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");
$conn2 = getDBConnection();

// =============================================================================
// EMAIL VERIFICATION FUNCTIONS (FIXED)
// =============================================================================

/**
 * Generate and send verification code to user's email
 */
function sendVerificationCode($user_id, $email, $username) {
    global $phpmailer_available;
    
    // If PHPMailer is not available, disable email verification
    if (!$phpmailer_available) {
        error_log("Email verification attempted but PHPMailer not available for user: $username");
        // You can choose to:
        // 1. Return false (email verification fails)
        // 2. Return true (skip email verification entirely)
        // For now, we'll return false but log the issue
        return false;
    }
    
    $conn = getDBConnection();
    
    // Generate 6-digit random code
    $code = sprintf("%06d", mt_rand(0, 999999));
    
    // Set expiry time
    $expires_at = date('Y-m-d H:i:s', time() + VERIFICATION_CODE_EXPIRY);
    
    // Invalidate any existing codes for this user
    $cleanup_stmt = $conn->prepare("UPDATE verification_codes SET is_used = 1 WHERE user_id = ? AND is_used = 0");
    $cleanup_stmt->bind_param("i", $user_id);
    $cleanup_stmt->execute();
    $cleanup_stmt->close();
    
    // Insert new verification code
    $stmt = $conn->prepare("INSERT INTO verification_codes (user_id, email, code, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $email, $code, $expires_at);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Send email
        if (sendVerificationEmail($email, $username, $code)) {
            return true;
        } else {
            // If email sending fails, mark code as used to prevent security issues
            $fail_stmt = $conn->prepare("UPDATE verification_codes SET is_used = 1 WHERE user_id = ? AND code = ?");
            $fail_stmt->bind_param("is", $user_id, $code);
            $fail_stmt->execute();
            $fail_stmt->close();
            return false;
        }
    }
    
    $stmt->close();
    return false;
}

/**
 * Send verification email using PHPMailer - FIXED VERSION
 */
function sendVerificationEmail($email, $username, $code) {
    global $phpmailer_available;
    
    // Check if PHPMailer is available
    if (!$phpmailer_available) {
        error_log("PHPMailer not available. Cannot send verification email to: $email");
        return false;
    }
    
    // Check if PHPMailer class exists (double check)
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        error_log("PHPMailer class not found. Cannot send verification email to: $email");
        return false;
    }
    
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'localhost';
        $mail->SMTPAuth = true;
        $mail->Username = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
        $mail->Password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
        $mail->SMTPSecure = defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls';
        $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
        
        // Recipients
        $mail->setFrom(defined('FROM_EMAIL') ? FROM_EMAIL : 'noreply@localhost', defined('FROM_NAME') ? FROM_NAME : 'System');
        $mail->addAddress($email, $username);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verification Code - Crane & Trucking Management System';
        
        // Email template
        $emailBody = getVerificationEmailTemplate($username, $code);
        $mail->Body = $emailBody;
        $mail->AltBody = "Hello $username,\n\nYour verification code is: $code\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this code, please contact your administrator.\n\nCrane & Trucking Management System";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    } catch (Error $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get HTML email template for verification code
 */
function getVerificationEmailTemplate($username, $code) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #000; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; background: #f9f9f9; }
            .code-box { background: #fff; border: 2px solid #000; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px; }
            .code { font-size: 32px; font-weight: bold; color: #000; letter-spacing: 5px; }
            .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 15px 0; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Crane & Trucking Management System</h1>
                <p>Email Verification Required</p>
            </div>
            
            <div class='content'>
                <h2>Hello, " . htmlspecialchars($username) . "!</h2>
                <p>You are attempting to log in to the Crane & Trucking Management System. To complete your login, please enter the verification code below:</p>
                
                <div class='code-box'>
                    <div class='code'>" . $code . "</div>
                </div>
                
                <p><strong>This code will expire in 10 minutes.</strong></p>
                
                <div class='warning'>
                    <strong>Security Notice:</strong> If you didn't attempt to log in, please contact your system administrator immediately. Someone may be trying to access your account.
                </div>
                
                <p>For your security:</p>
                <ul>
                    <li>Never share this code with anyone</li>
                    <li>Our support team will never ask for this code</li>
                    <li>This code is only valid for 10 minutes</li>
                </ul>
            </div>
            
            <div class='footer'>
                <p>This is an automated message from the Crane & Trucking Management System.</p>
                <p>If you need assistance, please contact your system administrator.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Verify the entered code
 */
function verifyCode($user_id, $entered_code) {
    $conn = getDBConnection();
    
    // Find active verification code
    $stmt = $conn->prepare("
        SELECT id, code, expires_at, attempts 
        FROM verification_codes 
        WHERE user_id = ? AND is_used = 0 AND expires_at > NOW() 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'No valid verification code found or code has expired.'];
    }
    
    $verification = $result->fetch_assoc();
    $stmt->close();
    
    // Check if too many attempts
    if ($verification['attempts'] >= MAX_VERIFICATION_ATTEMPTS) {
        // Mark as used
        $mark_used = $conn->prepare("UPDATE verification_codes SET is_used = 1 WHERE id = ?");
        $mark_used->bind_param("i", $verification['id']);
        $mark_used->execute();
        $mark_used->close();
        
        return ['success' => false, 'message' => 'Too many incorrect attempts. Please request a new code.'];
    }
    
    // Increment attempts
    $update_attempts = $conn->prepare("UPDATE verification_codes SET attempts = attempts + 1 WHERE id = ?");
    $update_attempts->bind_param("i", $verification['id']);
    $update_attempts->execute();
    $update_attempts->close();
    
    // Verify code
    if ($verification['code'] === $entered_code) {
        // Mark as used
        $mark_used = $conn->prepare("UPDATE verification_codes SET is_used = 1 WHERE id = ?");
        $mark_used->bind_param("i", $verification['id']);
        $mark_used->execute();
        $mark_used->close();
        
        // Create 10-day access token
        createAccessToken($user_id);
        
        return ['success' => true, 'message' => 'Code verified successfully!'];
    } else {
        $remaining_attempts = MAX_VERIFICATION_ATTEMPTS - ($verification['attempts'] + 1);
        return ['success' => false, 'message' => "Incorrect code. $remaining_attempts attempts remaining."];
    }
}

/**
 * Create 10-day access token - FIXED VERSION
 */
function createAccessToken($user_id) {
    $conn = getDBConnection();
    
    // Remove any existing token for this user
    $cleanup = $conn->prepare("DELETE FROM user_access_tokens WHERE user_id = ?");
    $cleanup->bind_param("i", $user_id);
    $cleanup->execute();
    $cleanup->close();
    
    // Generate secure token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', time() + ACCESS_TOKEN_VALIDITY);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Insert new token
    $stmt = $conn->prepare("INSERT INTO user_access_tokens (user_id, token, expires_at, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $token, $expires_at, $ip_address, $user_agent);
    
    if ($stmt->execute()) {
        $stmt->close();
        // Store token in session - THIS IS THE KEY FIX
        $_SESSION['access_token'] = $token;
        $_SESSION['access_token_expires'] = time() + ACCESS_TOKEN_VALIDITY;
        return true;
    } else {
        $stmt->close();
        return false;
    }
}

/**
 * Check if user has valid access token (within 10 days) - FIXED VERSION
 */
function hasValidAccessToken($user_id) {
    $conn = getDBConnection();
    
    // First, check database directly for any valid token for this user
    // Don't rely on session initially - session might be cleared
    $stmt = $conn->prepare("
        SELECT token, expires_at FROM user_access_tokens 
        WHERE user_id = ? AND expires_at > NOW()
        ORDER BY granted_at DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $token_data = $result->fetch_assoc();
        $stmt->close();
        
        // Valid token found - store it in session for future use
        $_SESSION['access_token'] = $token_data['token'];
        $_SESSION['access_token_expires'] = strtotime($token_data['expires_at']);
        
        return true;
    }
    
    $stmt->close();
    
    // No valid token found - clean up session
    unset($_SESSION['access_token']);
    unset($_SESSION['access_token_expires']);
    
    return false;
}

/**
 * Clean up expired verification codes and access tokens
 */
function cleanupVerificationData() {
    $conn = getDBConnection();
    
    // Clean expired verification codes
    $stmt1 = $conn->prepare("DELETE FROM verification_codes WHERE expires_at < NOW()");
    $stmt1->execute();
    $stmt1->close();
    
    // Clean expired access tokens
    $stmt2 = $conn->prepare("DELETE FROM user_access_tokens WHERE expires_at < NOW()");
    $stmt2->execute();
    $stmt2->close();
}

// =============================================================================
// EXISTING SESSION MANAGEMENT FUNCTIONS (ENHANCED)
// =============================================================================

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function checkSessionTimeout() {
    if (isLoggedIn()) {
        $current_time = time();
        
        if (isset($_SESSION['last_activity']) && ($current_time - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            cleanupUserSession($_SESSION['user_id'], session_id());
            session_unset();
            session_destroy();
            header("Location: login.php?timeout=1");
            exit();
        }
        
        $_SESSION['last_activity'] = $current_time;
        
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = $current_time;
        } else if ($current_time - $_SESSION['last_regeneration'] > 600) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = $current_time;
        }
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: index.php?access_denied=1");
        exit();
    }
}

function cleanupUserSession($user_id, $session_id = null) {
    $conn = getDBConnection();
    
    if ($session_id) {
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ? AND session_id = ?");
        $stmt->bind_param("is", $user_id, $session_id);
    } else {
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $stmt->close();
}

// =============================================================================
// EXISTING LOGIN ATTEMPT FUNCTIONS (UNCHANGED)
// =============================================================================

function logLoginAttempt($username, $success = false, $force_log = false) {
    $conn = getDBConnection();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    if (!$success && !$force_log && isIPLockedOut()) {
        return false;
    }
    
    if ($success) {
        $clear_stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND success = 0");
        $clear_stmt->bind_param("s", $ip_address);
        $clear_stmt->execute();
        $clear_stmt->close();
    }
    
    $success_int = $success ? 1 : 0;
    $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $ip_address, $username, $success_int);
    $stmt->execute();
    $stmt->close();
    
    return true;
}

function isIPLockedOut() {
    $conn = getDBConnection();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $lockout_threshold = date('Y-m-d H:i:s', time() - LOCKOUT_DURATION);
    
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
    
    if ($fail_count >= MAX_LOGIN_ATTEMPTS) {
        $trigger_stmt = $conn->prepare("
            SELECT attempted_at 
            FROM login_attempts 
            WHERE ip_address = ? AND success = 0 AND attempted_at > ?
            ORDER BY attempted_at ASC 
            LIMIT 1 OFFSET ?
        ");
        $offset = MAX_LOGIN_ATTEMPTS - 1;
        $trigger_stmt->bind_param("ssi", $ip_address, $lockout_threshold, $offset);
        $trigger_stmt->execute();
        $trigger_result = $trigger_stmt->get_result();
        
        if ($trigger_result->num_rows > 0) {
            $trigger_time = $trigger_result->fetch_assoc()['attempted_at'];
            $trigger_timestamp = strtotime($trigger_time);
            $lockout_expires = $trigger_timestamp + LOCKOUT_DURATION;
            
            $trigger_stmt->close();
            return time() < $lockout_expires;
        }
        $trigger_stmt->close();
    }
    
    return false;
}

function getLockoutTimeRemaining() {
    if (!isIPLockedOut()) {
        return 0;
    }
    
    $conn = getDBConnection();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
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
        $last_attempt = $result->fetch_assoc()['attempted_at'];
        $last_attempt_timestamp = strtotime($last_attempt);
        $lockout_expires = $last_attempt_timestamp + LOCKOUT_DURATION;
        $remaining = $lockout_expires - time();
        
        $stmt->close();
        return max(0, $remaining);
    }
    
    $stmt->close();
    return 0;
}

function cleanupSessions() {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
    $stmt->execute();
    $stmt->close();
    
    $cleanup_time = date('Y-m-d H:i:s', time() - (24 * 60 * 60));
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE attempted_at < ?");
    $stmt->bind_param("s", $cleanup_time);
    $stmt->execute();
    $stmt->close();
}

function cleanOldLoginAttempts() {
    $conn = getDBConnection();
    $cleanup_time = date('Y-m-d H:i:s', time() - (24 * 60 * 60));
    
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE attempted_at < ?");
    $stmt->bind_param("s", $cleanup_time);
    $stmt->execute();
    $stmt->close();
}

// =============================================================================
// EXISTING UTILITY FUNCTIONS (UNCHANGED)
// =============================================================================

if (!function_exists('calculateTaxAmount')) {
    function calculateTaxAmount($taxType, $amount) {
        switch($taxType) {
            case 'VAT':
                return $amount * 0.12;
            case 'Withholding':
                return $amount * 0.02;
            case 'Exempted':
            case 'None':
            default:
                return 0.00;
        }
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return '₱' . number_format($amount, 2);
    }
}

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
// PHPMAILER STATUS CHECK FUNCTION (NEW)
// =============================================================================
function checkPHPMailerStatus() {
    global $phpmailer_available;
    
    if (isset($_GET['check_phpmailer']) && $_GET['check_phpmailer'] == 1) {
        echo "<div style='background: #f9f9f9; padding: 15px; margin: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
        echo "<h3>PHPMailer Status Check</h3>";
        
        if ($phpmailer_available) {
            echo "<p style='color: green;'>✅ PHPMailer is available</p>";
            
            if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                echo "<p style='color: green;'>✅ PHPMailer class loaded successfully</p>";
                
                // Test email configuration constants
                $config_issues = [];
                if (!defined('SMTP_HOST')) $config_issues[] = 'SMTP_HOST';
                if (!defined('SMTP_USERNAME')) $config_issues[] = 'SMTP_USERNAME';
                if (!defined('SMTP_PASSWORD')) $config_issues[] = 'SMTP_PASSWORD';
                if (!defined('SMTP_ENCRYPTION')) $config_issues[] = 'SMTP_ENCRYPTION';
                if (!defined('SMTP_PORT')) $config_issues[] = 'SMTP_PORT';
                if (!defined('FROM_EMAIL')) $config_issues[] = 'FROM_EMAIL';
                if (!defined('FROM_NAME')) $config_issues[] = 'FROM_NAME';
                
                if (empty($config_issues)) {
                    echo "<p style='color: green;'>✅ All email configuration constants are defined</p>";
                } else {
                    echo "<p style='color: orange;'>⚠️ Missing email configuration constants: " . implode(', ', $config_issues) . "</p>";
                    echo "<p>Check your email_config.php file</p>";
                }
                
            } else {
                echo "<p style='color: red;'>❌ PHPMailer class not accessible</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ PHPMailer is NOT available</p>";
            echo "<p>Email verification is disabled. Install PHPMailer to enable email features.</p>";
            
            // Show installation instructions
            echo "<h4>Installation Options:</h4>";
            echo "<p><strong>Option 1 - Composer:</strong></p>";
            echo "<pre>cd " . getcwd() . "\ncomposer require phpmailer/phpmailer</pre>";
            
            echo "<p><strong>Option 2 - Manual Download:</strong></p>";
            echo "<ol>";
            echo "<li>Download from: <a href='https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip' target='_blank'>GitHub</a></li>";
            echo "<li>Extract to your web directory as 'PHPMailer' folder</li>";
            echo "<li>Ensure structure: PHPMailer/src/PHPMailer.php</li>";
            echo "</ol>";
        }
        
        echo "</div>";
    }
}
