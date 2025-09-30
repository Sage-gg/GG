<?php
require_once 'db.php';

// Check if user has a pending verification
if (!isset($_SESSION['pending_verification']) || !isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit();
}

$temp_user_id = $_SESSION['temp_user_id'];
$temp_email = $_SESSION['temp_email'];
$temp_username = $_SESSION['temp_username'];

$error_message = '';
$success_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_code'])) {
        $entered_code = trim($_POST['code']);
        
        if (empty($entered_code)) {
            $error_message = "Please enter the verification code.";
        } elseif (!preg_match('/^\d{6}$/', $entered_code)) {
            $error_message = "Please enter a valid 6-digit code.";
        } else {
            $result = verifyCode($temp_user_id, $entered_code);
            
            if ($result['success']) {
                // Verification successful - complete login process
                
                // Clean up old sessions for this user and IP
                cleanupUserSession($temp_user_id);
                
                $conn = getDBConnection();
                $cleanup_stmt = $conn->prepare("DELETE FROM user_sessions WHERE ip_address = ? AND expires_at < NOW()");
                $cleanup_stmt->bind_param("s", $_SERVER['REMOTE_ADDR']);
                $cleanup_stmt->execute();
                $cleanup_stmt->close();
                
                // Get user data
                $user_stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
                $user_stmt->bind_param("i", $temp_user_id);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                $user = $user_result->fetch_assoc();
                $user_stmt->close();
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                $_SESSION['last_regeneration'] = time();
                
                // Clear verification session data
                unset($_SESSION['pending_verification']);
                unset($_SESSION['temp_user_id']);
                unset($_SESSION['temp_username']);
                unset($_SESSION['temp_email']);
                
                // Generate session ID for database tracking
                session_regenerate_id(true);
                $session_id = session_id();
                
                // Store session in database
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                $expires_at = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
                
                $session_stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
                $session_stmt->bind_param("issss", $user['id'], $session_id, $ip_address, $user_agent, $expires_at);
                $session_stmt->execute();
                $session_stmt->close();
                
                // CRITICAL: Ensure session is fully written before redirect
                session_write_close();
                session_start(); // Restart session to ensure it's fully established
                
                // Small delay to ensure session is ready
                usleep(100000); // 0.1 second delay
                
                // Redirect to dashboard
                header("Location: index.php");
                exit();
            } else {
                $error_message = $result['message'];
            }
        }
    } elseif (isset($_POST['resend_code'])) {
        $resend_result = sendVerificationCode($temp_user_id, $temp_email, $temp_username);
        
        if ($resend_result) {
            $success_message = "A new verification code has been sent to your email.";
        } else {
            $error_message = "Failed to send verification code. Please try again.";
        }
    }
}

// Get current code info for display purposes
$conn = getDBConnection();
$time_stmt = $conn->prepare("
    SELECT expires_at, attempts
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

$time_remaining = 0;
$attempts_remaining = MAX_VERIFICATION_ATTEMPTS;

if ($code_info) {
    $expires_timestamp = strtotime($code_info['expires_at']);
    $time_remaining = max(0, $expires_timestamp - time());
    $attempts_remaining = MAX_VERIFICATION_ATTEMPTS - $code_info['attempts'];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Email Verification - Financial System</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --accent1: #000000;
      --accent2: #000000;
    }
    * { box-sizing: border-box; }
    html, body { height: 100%; margin: 0; }

    body {
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      background: url("pexels-rezwan-1078884.jpg") no-repeat center center/cover;
      position: relative;
      overflow: hidden;
    }

    .verification-card {
      width: 500px;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.8);
      border: 5px solid rgba(255, 255, 255, 0.15);
      background: rgba(236, 190, 190, 0.5);
      backdrop-filter: blur(8px);
      padding: 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
    }

    .header-section {
      text-align: center;
      margin-bottom: 25px;
    }

    h1 {
      font-size: 18px;
      margin: 0 0 8px 0;
    }
    
    .lead {
      margin: 0 0 8px 0;
      font-size: 14px;
      color: rgba(0, 0, 0, 0.7);
    }

    .user-info {
      background: rgba(23, 162, 184, 0.1);
      border: 1px solid rgba(23, 162, 184, 0.3);
      color: #0c5460;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 13px;
      text-align: center;
      width: 100%;
    }

    .notifications {
      width: 100%;
      margin-bottom: 20px;
    }

    .verification-form {
      display: flex;
      flex-direction: column;
      gap: 14px;
      width: 100%;
      margin-bottom: 20px;
    }

    .input {
      background: rgba(226, 225, 225, 0.11);
      border: 1px solid rgb(0, 0, 0);
      padding: 12px;
      border-radius: 8px;
      color: #000000;
      font-size: 16px;
      outline: none;
      text-align: center;
      letter-spacing: 3px;
      font-weight: 600;
    }

    .input::placeholder {
      color: rgba(0, 0, 0, 0.7);
      letter-spacing: normal;
    }

    .input:focus {
      border-color: var(--accent2);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.904);
    }

    .btn {
      padding: 12px;
      border-radius: 8px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      background: linear-gradient(90deg,var(--accent1),var(--accent2));
      color: #fff;
      transition: 0.3s;
      font-size: 14px;
    }
    .btn:hover {
      background: linear-gradient(90deg,#000000,#666);
    }
    
    .btn:disabled {
      background: #666;
      cursor: not-allowed;
    }

    .btn-secondary {
      background: rgba(108, 117, 125, 0.8);
      color: white;
    }

    .btn-secondary:hover {
      background: rgba(108, 117, 125, 1);
    }

    .back-link {
      margin-top: 20px;
      font-size: 13px;
      color: #020202;
    }
    .back-link a {
      color: #000000;
      text-decoration: none;
      font-weight: 600;
    }
    .back-link a:hover {
      text-decoration: underline;
    }

    .alert {
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 15px;
      font-size: 14px;
      width: 100%;
      position: relative;
    }
    
    .alert-danger {
      background: rgba(220, 53, 69, 0.1);
      border: 1px solid rgba(220, 53, 69, 0.3);
      color: #721c24;
    }
    
    .alert-success {
      background: rgba(40, 167, 69, 0.1);
      border: 1px solid rgba(40, 167, 69, 0.3);
      color: #155724;
    }

    .timer-info {
      background: rgba(255, 193, 7, 0.1);
      border: 1px solid rgba(255, 193, 7, 0.3);
      color: #856404;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 15px;
      font-size: 13px;
      text-align: center;
    }

    .attempts-info {
      font-size: 12px;
      color: rgba(0, 0, 0, 0.6);
      text-align: center;
      margin-bottom: 15px;
    }

    .code-instructions {
      font-size: 13px;
      color: rgba(0, 0, 0, 0.7);
      text-align: center;
      margin-bottom: 20px;
      line-height: 1.4;
    }

    @media (max-width: 850px) {
      .verification-card { 
        width: 95%; 
        padding: 30px 20px;
      }
      .header-section {
        margin-bottom: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="verification-card">

      <div class="header-section">
        <h1>Email Verification Required</h1>
        <p class="lead">Please check your email for the verification code</p>
      </div>

      <div class="user-info">
        <strong>Verification sent to:</strong> <?php echo htmlspecialchars($temp_email); ?>
      </div>

      <div class="notifications">
        <?php if (!empty($error_message)): ?>
          <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
          <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
          </div>
        <?php endif; ?>

        <?php if ($time_remaining > 0): ?>
          <div class="timer-info" id="timerInfo">
            <strong>Code expires in:</strong> <span id="timeRemaining"><?php echo gmdate("i:s", $time_remaining); ?></span>
          </div>
        <?php endif; ?>

        <?php if ($attempts_remaining > 0): ?>
          <div class="attempts-info">
            <?php echo $attempts_remaining; ?> attempt<?php echo $attempts_remaining !== 1 ? 's' : ''; ?> remaining
          </div>
        <?php endif; ?>
      </div>

      <div class="code-instructions">
        Enter the 6-digit verification code sent to your email address to complete your login.
      </div>

      <form method="POST" action="verify_email.php" class="verification-form">
        <input type="text" 
               name="code" 
               class="input"
               placeholder="000000" 
               maxlength="6" 
               pattern="\d{6}"
               required
               autocomplete="off"
               id="codeInput">
        
        <button type="submit" name="verify_code" class="btn">
          Verify Code
        </button>
      </form>

      <form method="POST" action="verify_email.php">
        <button type="submit" name="resend_code" class="btn btn-secondary">
          Send New Code
        </button>
      </form>

      <div class="back-link">
        <a href="login.php">← Back to Login</a>
      </div>
  </div>

  <script>
    // Countdown timer
    <?php if ($time_remaining > 0): ?>
    let timeRemaining = <?php echo $time_remaining; ?>;
    const timerElement = document.getElementById('timeRemaining');
    const timerInfo = document.getElementById('timerInfo');

    function updateTimer() {
      if (timeRemaining <= 0) {
        timerInfo.innerHTML = '<strong style="color: #dc3545;">Code has expired</strong> - Please request a new code';
        timerInfo.style.background = 'rgba(220, 53, 69, 0.1)';
        timerInfo.style.borderColor = 'rgba(220, 53, 69, 0.3)';
        timerInfo.style.color = '#721c24';
        return;
      }

      const minutes = Math.floor(timeRemaining / 60);
      const seconds = timeRemaining % 60;
      timerElement.textContent = minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0');
      
      timeRemaining--;
      setTimeout(updateTimer, 1000);
    }

    setTimeout(updateTimer, 1000);
    <?php endif; ?>
  </script>

</body>
</html>
