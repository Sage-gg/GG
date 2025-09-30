<?php
// Show errors on screen
// ONLY USE THIS IF THE VERIFY_EMAIL.PHP IS NOT PROPERLY WORKING
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

// DEBUG: Show when forms are submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div style='background: yellow; padding: 10px; margin: 10px; border: 1px solid orange;'>";
    echo "<strong>DEBUG: Form submitted!</strong><br>";
    echo "POST data: " . print_r($_POST, true) . "<br>";
    
    if (isset($_POST['verify_code'])) {
        echo "VERIFY CODE button clicked<br>";
    }
    
    if (isset($_POST['resend_code'])) {
        echo "RESEND CODE button clicked<br>";
    }
    echo "</div>";
}

// Check if user has a pending verification
if (!isset($_SESSION['pending_verification']) || !isset($_SESSION['temp_user_id'])) {
    echo "<div style='background: red; color: white; padding: 10px;'>ERROR: No pending verification found. Redirecting to login.</div>";
    // header("Location: login.php");
    // exit();
}

$temp_user_id = $_SESSION['temp_user_id'] ?? 'NOT_SET';
$temp_email = $_SESSION['temp_email'] ?? 'NOT_SET';
$temp_username = $_SESSION['temp_username'] ?? 'NOT_SET';

echo "<div style='background: lightblue; padding: 10px; margin: 10px;'>";
echo "<strong>DEBUG: Session Info</strong><br>";
echo "User ID: $temp_user_id<br>";
echo "Email: $temp_email<br>";
echo "Username: $temp_username<br>";
echo "</div>";

$error_message = '';
$success_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div style='background: lightgreen; padding: 10px; margin: 10px;'>";
    echo "<strong>DEBUG: Processing form...</strong><br>";
    
    if (isset($_POST['verify_code'])) {
        echo "Processing verification code...<br>";
        $entered_code = trim($_POST['code']);
        echo "Entered code: '$entered_code'<br>";
        
        if (empty($entered_code)) {
            $error_message = "Please enter the verification code.";
            echo "Error: Empty code<br>";
        } elseif (!preg_match('/^\d{6}$/', $entered_code)) {
            $error_message = "Please enter a valid 6-digit code.";
            echo "Error: Invalid format<br>";
        } else {
    echo "Calling verifyCode function...<br>";
    
    // ADD ENHANCED DEBUG HERE
    echo "<div style='background: #fff3cd; border: 2px solid #ffc107; padding: 10px; margin: 5px;'>";
    echo "<strong>ENHANCED DEBUG: Before verifyCode() call</strong><br>";
    echo "User ID: $temp_user_id<br>";
    echo "Entered Code: '$entered_code'<br>";
    echo "MAX_VERIFICATION_ATTEMPTS: " . (defined('MAX_VERIFICATION_ATTEMPTS') ? MAX_VERIFICATION_ATTEMPTS : 'NOT DEFINED') . "<br>";
    
    // Check current database state BEFORE calling verifyCode
    $pre_conn = getDBConnection();
    $pre_stmt = $pre_conn->prepare("SELECT id, attempts, is_used FROM verification_codes WHERE user_id = ? AND is_used = 0 ORDER BY created_at DESC LIMIT 1");
    $pre_stmt->bind_param("i", $temp_user_id);
    $pre_stmt->execute();
    $pre_result = $pre_stmt->get_result();
    if ($pre_result->num_rows > 0) {
        $pre_data = $pre_result->fetch_assoc();
        echo "Database BEFORE verifyCode: ID={$pre_data['id']}, attempts={$pre_data['attempts']}, is_used={$pre_data['is_used']}<br>";
    }
    $pre_stmt->close();
    echo "</div>";
    
    $result = verifyCode($temp_user_id, $entered_code);
    
    // ADD DEBUG AFTER verifyCode() call
    echo "<div style='background: #d1ecf1; border: 2px solid #bee5eb; padding: 10px; margin: 5px;'>";
    echo "<strong>ENHANCED DEBUG: After verifyCode() call</strong><br>";
    echo "Result success: " . ($result['success'] ? 'TRUE' : 'FALSE') . "<br>";
    echo "Result message: " . $result['message'] . "<br>";
    
    // Check database state AFTER calling verifyCode
    $post_conn = getDBConnection();
    $post_stmt = $post_conn->prepare("SELECT id, attempts, is_used FROM verification_codes WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $post_stmt->bind_param("i", $temp_user_id);
    $post_stmt->execute();
    $post_result = $post_stmt->get_result();
    if ($post_result->num_rows > 0) {
        $post_data = $post_result->fetch_assoc();
        echo "Database AFTER verifyCode: ID={$post_data['id']}, attempts={$post_data['attempts']}, is_used={$post_data['is_used']}<br>";
        
        // Compare before and after
        if (isset($pre_data)) {
            if ($pre_data['attempts'] == $post_data['attempts']) {
                echo "<strong style='color: red;'>WARNING: Attempts counter did NOT change!</strong><br>";
            } else {
                echo "<strong style='color: green;'>SUCCESS: Attempts increased from {$pre_data['attempts']} to {$post_data['attempts']}</strong><br>";
            }
        }
    } else {
        echo "No verification code found after verifyCode() call!<br>";
    }
    $post_stmt->close();
    echo "</div>";
    
    echo "verifyCode result: " . print_r($result, true) . "<br>";
            if ($result['success']) {
                $success_message = $result['message'];
                echo "SUCCESS: Code verified!<br>";
                // Don't redirect yet for debugging
            } else {
                $error_message = $result['message'];
                echo "FAILED: " . $result['message'] . "<br>";
            }
        }
    } elseif (isset($_POST['resend_code'])) {
        echo "Processing resend request...<br>";
        echo "Calling sendVerificationCode function...<br>";
        
        $resend_result = sendVerificationCode($temp_user_id, $temp_email, $temp_username);
        echo "sendVerificationCode result: " . ($resend_result ? 'SUCCESS' : 'FAILED') . "<br>";
        
        if ($resend_result) {
            $success_message = "A new verification code has been sent to your email.";
        } else {
            $error_message = "Failed to send verification code. Please try again.";
        }
    }
    echo "</div>";
}

// Get current code info
$conn = getDBConnection();
$time_stmt = $conn->prepare("
    SELECT id, expires_at, attempts, code, created_at
    FROM verification_codes 
    WHERE user_id = ? AND is_used = 0 
    ORDER BY created_at DESC 
    LIMIT 1
");
$time_stmt->bind_param("i", $temp_user_id);
$time_stmt->execute();
$time_result = $time_stmt->get_result();
$code_info = $time_result->num_rows > 0 ? $time_result->fetch_assoc() : null;
$time_stmt->close();

echo "<div style='background: lightyellow; padding: 10px; margin: 10px;'>";
echo "<strong>DEBUG: Database Code Info</strong><br>";
if ($code_info) {
    echo "Code ID: " . $code_info['id'] . "<br>";
    echo "Code: " . $code_info['code'] . "<br>";
    echo "Attempts: " . $code_info['attempts'] . "<br>";
    echo "Created: " . $code_info['created_at'] . "<br>";
    echo "Expires: " . $code_info['expires_at'] . "<br>";
    
    $expires_timestamp = strtotime($code_info['expires_at']);
    $time_remaining = max(0, $expires_timestamp - time());
    echo "Time remaining: $time_remaining seconds<br>";
    
    $attempts_remaining = MAX_VERIFICATION_ATTEMPTS - $code_info['attempts'];
    echo "Attempts remaining: $attempts_remaining<br>";
} else {
    echo "No active verification code found<br>";
}
echo "</div>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Email Verification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-section { background: #f0f0f0; padding: 20px; margin: 10px 0; border: 1px solid #ccc; }
        input, button { padding: 10px; margin: 5px; font-size: 16px; }
        .alert { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
    </style>
</head>
<body>
    <h1>Email Verification Debug Page</h1>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <div class="form-section">
        <h3>Verify Code</h3>
        <form method="POST" action="verify_email.php">
            <input type="text" name="code" placeholder="Enter 6-digit code" maxlength="6" required>
            <button type="submit" name="verify_code">Verify Code</button>
        </form>
    </div>
    
    <div class="form-section">
        <h3>Resend Code</h3>
        <form method="POST" action="verify_email.php">
            <button type="submit" name="resend_code">Send New Code</button>
        </form>
    </div>
    
    <div class="form-section">
        <h3>Back to Login</h3>
        <a href="login.php">← Back to Login</a>
    </div>
</body>
</html>
