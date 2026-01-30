<?php
// =============================================================================
// db.php - ENHANCED WITH ROLE-BASED ACCESS CONTROL
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
// EMAIL VERIFICATION CONSTANTS
// =============================================================================
if (!defined('VERIFICATION_CODE_EXPIRY')) {
    define('VERIFICATION_CODE_EXPIRY', 10 * 60); // 10 minutes
}
if (!defined('MAX_VERIFICATION_ATTEMPTS')) {
    define('MAX_VERIFICATION_ATTEMPTS', 3);
}

// =============================================================================
// ROLE CONSTANTS
// =============================================================================
if (!defined('ROLE_SUPER_ADMIN')) define('ROLE_SUPER_ADMIN', 'super_admin');
if (!defined('ROLE_ADMIN')) define('ROLE_ADMIN', 'admin');
if (!defined('ROLE_FINANCE_MANAGER')) define('ROLE_FINANCE_MANAGER', 'finance_manager');
if (!defined('ROLE_MANAGER')) define('ROLE_MANAGER', 'manager');
if (!defined('ROLE_STAFF')) define('ROLE_STAFF', 'staff');

// =============================================================================
// PHPMAILER SETUP
// =============================================================================
$phpmailer_available = false;

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

if (!$phpmailer_available) {
    error_log("WARNING: PHPMailer not found. Email verification will be disabled.");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// =============================================================================
// DATABASE CONFIGURATION
// =============================================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'fina_LhayYhan');
define('DB_PASS', 'H8@r%ml2#0myfd-n');
define('DB_NAME', 'fina_financial_system');


$host = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$database = DB_NAME;

// =============================================================================
// SESSION CONFIGURATION
// =============================================================================

define('SESSION_TIMEOUT', 2 * 60); // 2 minutes in seconds
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
// GLOBAL CONNECTION VARIABLES
// =============================================================================

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");
$conn2 = getDBConnection();

// =============================================================================
// ROLE-BASED ACCESS CONTROL FUNCTIONS
// =============================================================================

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role']);
}

/**
 * Get current user's role
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user's department
 */
function getUserDepartment() {
    return $_SESSION['department'] ?? null;
}

/**
 * Get current user's cost center
 */
function getUserCostCenter() {
    return $_SESSION['cost_center'] ?? null;
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isLoggedIn() && getUserRole() === $role;
}

/**
 * Check if user has any of the specified roles
 */
function hasAnyRole($roles) {
    if (!isLoggedIn()) return false;
    $userRole = getUserRole();
    return in_array($userRole, $roles);
}

/**
 * Role hierarchy checks
 */
function isSuperAdmin() {
    return hasRole(ROLE_SUPER_ADMIN);
}

function isAdmin() {
    return hasRole(ROLE_ADMIN);
}

function isFinanceManager() {
    return hasRole(ROLE_FINANCE_MANAGER);
}

function isManager() {
    return hasRole(ROLE_MANAGER);
}

function isStaff() {
    return hasRole(ROLE_STAFF);
}

/**
 * Check if user has admin-level privileges (Super Admin or Admin)
 */
function isAdminLevel() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN]);
}

/**
 * Check if user has finance privileges (Super Admin, Admin, or Finance Manager)
 */
function isFinanceLevel() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_FINANCE_MANAGER]);
}

/**
 * Check if user has manager-level privileges or higher
 */
function isManagerLevel() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_FINANCE_MANAGER, ROLE_MANAGER]);
}

/**
 * Get user's permission for a specific module
 * Returns array: ['can_view', 'can_create', 'can_edit', 'can_delete', 'can_approve']
 */
function getModulePermission($module) {
    if (!isLoggedIn()) {
        return ['can_view' => false, 'can_create' => false, 'can_edit' => false, 'can_delete' => false, 'can_approve' => false];
    }
    
    $conn = getDBConnection();
    $role = getUserRole();
    
    $stmt = $conn->prepare("SELECT can_view, can_create, can_edit, can_delete, can_approve FROM role_permissions WHERE role = ? AND module = ?");
    $stmt->bind_param("ss", $role, $module);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return [
            'can_view' => (bool)$row['can_view'],
            'can_create' => (bool)$row['can_create'],
            'can_edit' => (bool)$row['can_edit'],
            'can_delete' => (bool)$row['can_delete'],
            'can_approve' => (bool)$row['can_approve']
        ];
    }
    
    $stmt->close();
    return ['can_view' => false, 'can_create' => false, 'can_edit' => false, 'can_delete' => false, 'can_approve' => false];
}

/**
 * Check if user can view a specific module
 */
function canView($module) {
    $perms = getModulePermission($module);
    return $perms['can_view'];
}

/**
 * Check if user can create in a specific module
 */
function canCreate($module) {
    $perms = getModulePermission($module);
    return $perms['can_create'];
}

/**
 * Check if user can edit in a specific module
 */
function canEdit($module) {
    $perms = getModulePermission($module);
    return $perms['can_edit'];
}

/**
 * Check if user can delete in a specific module
 */
function canDelete($module) {
    $perms = getModulePermission($module);
    return $perms['can_delete'];
}

/**
 * Check if user can approve in a specific module
 */
function canApprove($module) {
    $perms = getModulePermission($module);
    return $perms['can_approve'];
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        if (!headers_sent()) {
            header("Location: login.php");
            die();
        } else {
            echo '<script>window.location.replace("login.php");</script>';
            die();
        }
    }
    
    checkSessionTimeout();
}

/**
 * Require Super Admin access
 */
function requireSuperAdmin() {
    requireLogin();
    if (!isSuperAdmin()) {
        if (!headers_sent()) {
            header("Location: index.php?access_denied=1");
            die();
        } else {
            echo '<script>window.location.replace("index.php?access_denied=1");</script>';
            die();
        }
    }
}

/**
 * Require Admin level access (Super Admin or Admin)
 */
function requireAdmin() {
    requireLogin();
    if (!isAdminLevel()) {
        if (!headers_sent()) {
            header("Location: index.php?access_denied=1");
            die();
        } else {
            echo '<script>window.location.replace("index.php?access_denied=1");</script>';
            die();
        }
    }
}

/**
 * Require Finance level access (Super Admin, Admin, or Finance Manager)
 */
function requireFinance() {
    requireLogin();
    if (!isFinanceLevel()) {
        if (!headers_sent()) {
            header("Location: index.php?access_denied=1");
            die();
        } else {
            echo '<script>window.location.replace("index.php?access_denied=1");</script>';
            die();
        }
    }
}

/**
 * Require Manager level access or higher
 */
function requireManagerLevel() {
    requireLogin();
    if (!isManagerLevel()) {
        if (!headers_sent()) {
            header("Location: index.php?access_denied=1");
            die();
        } else {
            echo '<script>window.location.replace("index.php?access_denied=1");</script>';
            die();
        }
    }
}

/**
 * Require permission to view a specific module
 */
function requireModuleAccess($module) {
    requireLogin();
    if (!canView($module)) {
        if (!headers_sent()) {
            header("Location: index.php?access_denied=1");
            die();
        } else {
            echo '<script>window.location.replace("index.php?access_denied=1");</script>';
            die();
        }
    }
}

/**
 * Get role display name
 */
function getRoleDisplayName($role) {
    $roleNames = [
        ROLE_SUPER_ADMIN => 'Super Administrator',
        ROLE_ADMIN => 'Administrator',
        ROLE_FINANCE_MANAGER => 'Finance Manager',
        ROLE_MANAGER => 'Manager',
        ROLE_STAFF => 'Staff'
    ];
    
    return $roleNames[$role] ?? 'Unknown';
}

/**
 * Get all available roles (for dropdown in user management)
 */
function getAvailableRoles() {
    return [
        ROLE_SUPER_ADMIN => 'Super Administrator',
        ROLE_ADMIN => 'Administrator',
        ROLE_FINANCE_MANAGER => 'Finance Manager',
        ROLE_MANAGER => 'Manager',
        ROLE_STAFF => 'Staff'
    ];
}

/**
 * Log role change for audit trail
 */
function logRoleChange($user_id, $old_role, $new_role, $old_department, $new_department, $reason = null) {
    $conn = getDBConnection();
    $changed_by = $_SESSION['user_id'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO role_change_log (user_id, changed_by, old_role, new_role, old_department, new_department, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $user_id, $changed_by, $old_role, $new_role, $old_department, $new_department, $reason);
    $stmt->execute();
    $stmt->close();
}

// =============================================================================
// EMAIL VERIFICATION FUNCTIONS (UNCHANGED)
// =============================================================================

function sendVerificationCode($user_id, $email, $username) {
    global $phpmailer_available;
    
    error_log("DEBUG sendVerificationCode: Starting for user_id: $user_id, email: $email, username: $username");
    error_log("DEBUG sendVerificationCode: PHPMailer available: " . ($phpmailer_available ? 'YES' : 'NO'));
    
    try {
        $conn = getDBConnection();
        error_log("DEBUG sendVerificationCode: Database connection established");
        
        $code = sprintf("%06d", mt_rand(0, 999999));
        error_log("DEBUG sendVerificationCode: Generated code: $code");
        
        $expires_at = date('Y-m-d H:i:s', time() + VERIFICATION_CODE_EXPIRY);
        error_log("DEBUG sendVerificationCode: Code expires at: $expires_at");
        
        $cleanup_stmt = $conn->prepare("UPDATE verification_codes SET is_used = 1 WHERE user_id = ? AND is_used = 0");
        if (!$cleanup_stmt) {
            error_log("DEBUG sendVerificationCode: Failed to prepare cleanup statement: " . $conn->error);
            return false;
        }
        
        $cleanup_stmt->bind_param("i", $user_id);
        $cleanup_result = $cleanup_stmt->execute();
        error_log("DEBUG sendVerificationCode: Cleanup old codes result: " . ($cleanup_result ? 'SUCCESS' : 'FAILED'));
        if (!$cleanup_result) {
            error_log("DEBUG sendVerificationCode: Cleanup execute error: " . $cleanup_stmt->error);
        }
        $cleanup_stmt->close();
        
        $stmt = $conn->prepare("INSERT INTO verification_codes (user_id, email, code, expires_at) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            error_log("DEBUG sendVerificationCode: Failed to prepare insert statement: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("isss", $user_id, $email, $code, $expires_at);
        $insert_result = $stmt->execute();
        
        if ($insert_result) {
            $new_code_id = $conn->insert_id;
            error_log("DEBUG sendVerificationCode: New code inserted with ID: $new_code_id");
            $stmt->close();
            
            if ($phpmailer_available) {
                error_log("DEBUG sendVerificationCode: Attempting to send email");
                $email_result = sendVerificationEmail($email, $username, $code);
                error_log("DEBUG sendVerificationCode: Email send result: " . ($email_result ? 'SUCCESS' : 'FAILED'));
                
                if ($email_result) {
                    error_log("DEBUG sendVerificationCode: Overall process SUCCESS with email");
                    return true;
                } else {
                    error_log("DEBUG sendVerificationCode: Email sending failed, but code is in database");
                    return true;
                }
            } else {
                error_log("DEBUG sendVerificationCode: PHPMailer not available, but code created in database");
                error_log("DEBUG sendVerificationCode: TESTING MODE - Verification code is: $code");
                return true;
            }
        } else {
            error_log("DEBUG sendVerificationCode: Insert execute error: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
    } catch (Exception $e) {
        error_log("DEBUG sendVerificationCode: Exception caught: " . $e->getMessage());
        return false;
    }
}

function sendVerificationEmail($email, $username, $code) {
    global $phpmailer_available;
    
    error_log("DEBUG: sendVerificationEmail called for email: $email");
    
    if (!$phpmailer_available) {
        error_log("DEBUG: PHPMailer not available in sendVerificationEmail");
        return false;
    }
    
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        error_log("DEBUG: PHPMailer class not found in sendVerificationEmail");
        return false;
    }
    
    $missing_config = [];
    if (!defined('SMTP_HOST')) $missing_config[] = 'SMTP_HOST';
    if (!defined('SMTP_USERNAME')) $missing_config[] = 'SMTP_USERNAME';
    if (!defined('SMTP_PASSWORD')) $missing_config[] = 'SMTP_PASSWORD';
    if (!defined('SMTP_ENCRYPTION')) $missing_config[] = 'SMTP_ENCRYPTION';
    if (!defined('SMTP_PORT')) $missing_config[] = 'SMTP_PORT';
    if (!defined('FROM_EMAIL')) $missing_config[] = 'FROM_EMAIL';
    if (!defined('FROM_NAME')) $missing_config[] = 'FROM_NAME';
    
    if (!empty($missing_config)) {
        error_log("DEBUG: Missing email configuration constants: " . implode(', ', $missing_config));
        return false;
    }
    
    error_log("DEBUG: All email configuration constants are defined");
    
    try {
        $mail = new PHPMailer(true);
        error_log("DEBUG: PHPMailer instance created successfully");
        
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;
        
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($email, $username);
        
        $mail->isHTML(true);
        $mail->Subject = 'Verification Code - Crane & Trucking Management System';
        
        $emailBody = getVerificationEmailTemplate($username, $code);
        $mail->Body = $emailBody;
        $mail->AltBody = "Hello $username,\n\nYour verification code is: $code\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this code, please contact your administrator.\n\nCrane & Trucking Management System";
        
        error_log("DEBUG: Email content set, attempting to send...");
        
        $result = $mail->send();
        error_log("DEBUG: Email send result: " . ($result ? 'SUCCESS' : 'FAILED'));
        
        return $result;
        
    } catch (Exception $e) {
        error_log("DEBUG: PHPMailer Exception: " . $e->getMessage());
        return false;
    } catch (Error $e) {
        error_log("DEBUG: PHPMailer Error: " . $e->getMessage());
        return false;
    }
}

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

function verifyCode($user_id, $entered_code) {
    $conn = getDBConnection();
    
    error_log("DEBUG verifyCode: Starting verification for user_id: $user_id, code: $entered_code");
    
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
        error_log("DEBUG verifyCode: No valid code found");
        return ['success' => false, 'message' => 'No valid verification code found or code has expired.'];
    }
    
    $verification = $result->fetch_assoc();
    $stmt->close();
    
    error_log("DEBUG verifyCode: Found code - ID: {$verification['id']}, attempts: {$verification['attempts']}, expires: {$verification['expires_at']}");
    
    if ($verification['attempts'] >= MAX_VERIFICATION_ATTEMPTS) {
        $mark_used = $conn->prepare("UPDATE verification_codes SET is_used = 1 WHERE id = ?");
        $mark_used->bind_param("i", $verification['id']);
        $mark_used->execute();
        $mark_used->close();
        
        error_log("DEBUG verifyCode: Too many attempts, marking as used");
        return ['success' => false, 'message' => 'Too many incorrect attempts. Please request a new code.'];
    }
    
    if ($verification['code'] === $entered_code) {
        $mark_used = $conn->prepare("UPDATE verification_codes SET is_used = 1 WHERE id = ?");
        $mark_used->bind_param("i", $verification['id']);
        $mark_used->execute();
        $mark_used->close();
        
        error_log("DEBUG verifyCode: Code correct, verification successful");
        return ['success' => true, 'message' => 'Code verified successfully!'];
    } else {
        $new_attempts = $verification['attempts'] + 1;
        
        error_log("DEBUG verifyCode: Incorrect code. Incrementing attempts from {$verification['attempts']} to $new_attempts for ID {$verification['id']}");
        
        $update_attempts = $conn->prepare("UPDATE verification_codes SET attempts = ? WHERE id = ?");
        if (!$update_attempts) {
            error_log("DEBUG verifyCode: Failed to prepare UPDATE statement: " . $conn->error);
            return ['success' => false, 'message' => 'Database error occurred.'];
        }
        
        $bind_result = $update_attempts->bind_param("ii", $new_attempts, $verification['id']);
        if (!$bind_result) {
            error_log("DEBUG verifyCode: Failed to bind parameters: " . $update_attempts->error);
            $update_attempts->close();
            return ['success' => false, 'message' => 'Parameter binding error.'];
        }
        
        $execute_result = $update_attempts->execute();
        if (!$execute_result) {
            error_log("DEBUG verifyCode: Failed to execute UPDATE: " . $update_attempts->error);
            $update_attempts->close();
            return ['success' => false, 'message' => 'Failed to update attempts counter.'];
        }
        
        $affected_rows = $update_attempts->affected_rows;
        error_log("DEBUG verifyCode: UPDATE executed successfully. Affected rows: $affected_rows");
        
        $update_attempts->close();
        
        $remaining_attempts = MAX_VERIFICATION_ATTEMPTS - $new_attempts;
        
        error_log("DEBUG verifyCode: New attempts count: $new_attempts, remaining: $remaining_attempts");
        
        if ($remaining_attempts <= 0) {
            $mark_used = $conn->prepare("UPDATE verification_codes SET is_used = 1 WHERE id = ?");
            $mark_used->bind_param("i", $verification['id']);
            $mark_used->execute();
            $mark_used->close();
            
            return ['success' => false, 'message' => 'Too many incorrect attempts. Please request a new code.'];
        }
        
        return ['success' => false, 'message' => "Incorrect code. $remaining_attempts attempt" . ($remaining_attempts !== 1 ? 's' : '') . " remaining."];
    }
}

function cleanupVerificationData() {
    $conn = getDBConnection();
    
    $stmt1 = $conn->prepare("DELETE FROM verification_codes WHERE expires_at < NOW()");
    $stmt1->execute();
    $stmt1->close();
}

// =============================================================================
// SESSION MANAGEMENT FUNCTIONS (UPDATED WITH ROLE SUPPORT)
// =============================================================================

function checkSessionTimeout() {
    if (!isLoggedIn()) {
        return;
    }
    
    $current_time = time();
    
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = $current_time;
        $_SESSION['last_regeneration'] = $current_time;
        return;
    }
    
    $inactive_time = $current_time - $_SESSION['last_activity'];
    
    if ($inactive_time < 30) {
        $_SESSION['last_activity'] = $current_time;
        return;
    }
    
    if ($inactive_time > SESSION_TIMEOUT) {
        $user_id = $_SESSION['user_id'] ?? null;
        $current_session_id = session_id();
        
        if ($user_id && $current_session_id) {
            cleanupUserSession($user_id, $current_session_id);
        }
        
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        if (!headers_sent()) {
            header("Location: login.php?timeout=1");
            die();
        } else {
            echo '<script>window.location.replace("login.php?timeout=1");</script>';
            die();
        }
    }
    
    $_SESSION['last_activity'] = $current_time;
    
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = $current_time;
    } else if ($current_time - $_SESSION['last_regeneration'] > 600) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = $current_time;
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
// LOGIN ATTEMPT FUNCTIONS (UNCHANGED)
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
// UTILITY FUNCTIONS
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
        
        echo "</div>";
    }
}

// =============================================================================
// PHPMAILER STATUS CHECK FUNCTION
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
        }
        
        echo "</div>";
    }
}

// =============================================================================
// AUTO-CLEANUP ON SCRIPT END
// =============================================================================
register_shutdown_function(function() {
    // Clean up old sessions and login attempts periodically
    if (rand(1, 100) == 1) { // 1% chance on each request
        cleanupSessions();
        cleanupVerificationData();
    }
});
?>
