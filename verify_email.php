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
$info_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_code'])) {
        // Verify the entered code
        $entered_code = trim($_POST['code']);
        
        if (empty($entered_code)) {
            $error_message = "Please enter the verification code.";
        } elseif (!preg_match('/^\d{6}$/', $entered_code)) {
            $error_message = "Please enter a valid 6-digit code.";
        } else {
            $result = verifyCode($temp_user_id, $entered_code);
            
            if ($result['success']) {
                // Code verified successfully - complete login
                $success_message = $result['message'];
                
                // Clear temporary session data
                unset($_SESSION['pending_verification']);
                unset($_SESSION['temp_user_id']);
                unset($_SESSION['temp_email']);
                unset($_SESSION['temp_username']);
                
                // Set full session data
                $_SESSION['user_id'] = $temp_user_id;
                $_SESSION['username'] = $temp_username;
                $_SESSION['email'] = $temp_email;
                $_SESSION['last_activity'] = time();
                $_SESSION['last_regeneration'] = time();
                
                // Get user role
                $conn = getDBConnection();
                $role_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
                $role_stmt->bind_param("i", $temp_user_id);
                $role_stmt->execute();
                $role_result = $role_stmt->get_result();
                if ($role_result->num_rows > 0) {
                    $user_data = $role_result->fetch_assoc();
                    $_SESSION['role'] = $user_data['role'];
                }
                $role_stmt->close();
                
                // Generate session ID for database tracking
                session_regenerate_id(true);
                $session_id = session_id();
                
                // Store session in database
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                $expires_at = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
                
                $session_stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
                $session_stmt->bind_param("issss", $temp_user_id, $session_id, $ip_address, $user_agent, $expires_at);
                $session_stmt->execute();
                $session_stmt->close();
                
                // Redirect to dashboard after 2 seconds
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'index.php';
                    }, 2000);
                </script>";
            } else {
                $error_message = $result['message'];
            }
        }
    } elseif (isset($_POST['resend_code'])) {
        // Resend verification code
        if (sendVerificationCode($temp_user_id, $temp_email, $temp_username)) {
            $success_message = "A new verification code has been sent to your email.";
        } else {
            $error_message = "Failed to send verification code. Please try again.";
        }
    }
}

// Get time remaining info for display
$conn = getDBConnection();
$time_stmt = $conn->prepare("
    SELECT expires_at, attempts 
    FROM verification_codes 
    WHERE user_id = ? AND is_used = 0 AND expires_at > NOW() 
    ORDER BY created_at DESC 
    LIMIT 1
");
$time_stmt->bind_param("i", $temp_user_id);
$time_stmt->execute();
$time_result = $time_stmt->get_result();
$code_info = $time_result->num_rows > 0 ? $time_result->fetch_assoc() : null;
$time_stmt->close();

$expires_timestamp = $code_info ? strtotime($code_info['expires_at']) : 0;
$time_remaining = max(0, $expires_timestamp - time());
$attempts_used = $code_info ? $code_info['attempts'] : 0;
$attempts_remaining = MAX_VERIFICATION_ATTEMPTS - $attempts_used;
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
      --success: #28a745;
      --warning: #ffc107;
      --danger: #dc3545;
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
      text-align: center;
    }

    .header-section {
      margin-bottom: 30px;
    }

    .header-section h1 {
      font-size: 24px;
      margin: 0 0 10px 0;
      color: #000;
    }

    .header-section p {
      margin: 0 0 15px 0;
      font-size: 16px;
      color: rgba(0, 0, 0, 0.8);
    }

    .email-display {
      background: rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(0, 0, 0, 0.3);
      padding: 10px;
      border-radius: 5px;
      font-weight: 600;
      color: #000;
      margin: 10px 0;
    }

    .timer-section {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(0, 0, 0, 0.2);
      padding: 15px;
      border-radius: 8px;
      margin: 20px 0;
    }

    .timer-display {
      font-size: 18px;
      font-weight: bold;
      color: #000;
      margin-bottom: 5px;
    }

    .timer-info {
      font-size: 12px;
      color: rgba(0, 0, 0, 0.7);
    }

    .attempts-info {
      background: rgba(255, 193, 7, 0.1);
      border: 1px solid rgba(255, 193, 7, 0.5);
      color: #856404;
      padding: 10px;
      border-radius: 5px;
      margin: 15px 0;
      font-size: 14px;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 15px;
      margin: 20px 0;
    }

    .code-input {
      background: rgba(226, 225, 225, 0.11);
      border: 1px solid rgb(0, 0, 0);
      padding: 15px;
      border-radius: 8px;
      color: #000000;
      font-size: 18px;
      text-align: center;
      letter-spacing: 2px;
      font-weight: bold;
      outline: none;
    }

    .code-input::placeholder {
      color: rgba(0, 0, 0, 0.5);
      letter-spacing: normal;
      font-weight: normal;
    }

    .code-input:focus {
      border-color: var(--accent2);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.904);
    }

    .btn {
      padding: 12px;
      border-radius: 8px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s;
      font-size: 14px;
    }

    .btn-primary {
      background: linear-gradient(90deg,var(--accent1),var(--accent2));
      color: #fff;
    }
    .btn-primary:hover {
      background: linear-gradient(90deg,#000000,#666);
    }

    .btn-secondary {
      background: rgba(108, 117, 125, 0.1);
      border: 1px solid rgba(108, 117, 125, 0.5);
      color: #000;
    }
    .btn-secondary:hover {
      background: rgba(108, 117, 125, 0.2);
    }
    
    .btn:disabled {
      background: #666;
      cursor: not-allowed;
      opacity: 0.6;
    }

    .back-link {
      margin-top: 20px;
      font-size: 14px;
    }
    .back-link a {
      color: #000;
      text-decoration: none;
    }
    .back-link a:hover {
      text-decoration: underline;
    }

    .alert {
      padding: 15px;
      border-radius: 8px;
      margin: 15px 0;
      font-size: 14px;
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

    .alert-info {
      background: rgba(23, 162, 184, 0.1);
      border: 1px solid rgba(23, 162, 184, 0.3);
      color: #0c5460;
    }

    .instructions {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(0, 0, 0, 0.2);
      padding: 15px;
      border-radius: 8px;
      margin: 20px 0;
      text-align: left;
      font-size: 13px;
      color: rgba(0, 0, 0, 0.8);
    }

    .instructions ul {
      margin: 10px 0;
      padding-left: 20px;
    }

    .instructions li {
      margin: 5px 0;
    }

    .expired-notice {
      background: rgba(220, 53, 69, 0.1);
      border: 1px solid rgba(220, 53, 69, 0.3);
      color: #721c24;
      padding: 15px;
      border-radius: 8px;
      margin: 15px 0;
    }

    @media (max-width: 600px) {
      .verification-card {
        width: 95%;
        padding: 30px 20px;
      }
      
      .header-section h1 {
        font-size: 20px;
      }
      
      .timer-display {
        font-size: 16px;
      }
    }
  </style>
</head>
<body>
  <div class="verification-card">
    <div class="header-section">
      <h1>📧 Email Verification Required</h1>
      <p>Please check your email and enter the 6-digit verification code</p>
      
      <div class="email-display">
        📫 <?php echo htmlspecialchars($temp_email); ?>
      </div>
    </div>

    <!-- Notifications -->
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
      <!-- Timer Section -->
      <div class="timer-section">
        <div class="timer-display" id="timerDisplay">
          ⏰ <span id="minutes">--</span>:<span id="seconds">--</span>
        </div>
        <div class="timer-info">Time remaining for current code</div>
      </div>

      <?php if ($attempts_remaining > 0): ?>
        <!-- Attempts Info -->
        <div class="attempts-info">
          ⚠️ <?php echo $attempts_remaining; ?> verification attempt<?php echo $attempts_remaining !== 1 ? 's' : ''; ?> remaining
        </div>

        <!-- Verification Form -->
        <form method="POST" action="verify_email.php">
          <input class="code-input" 
                 type="text" 
                 name="code"
                 placeholder="Enter 6-digit code"
                 maxlength="6"
                 pattern="\d{6}"
                 required
                 autocomplete="off">
          
          <button class="btn btn-primary" type="submit" name="verify_code">
            ✅ Verify Code
          </button>
        </form>
      <?php else: ?>
        <div class="expired-notice">
          ❌ Too many incorrect attempts. The current code has been disabled for security.
        </div>
      <?php endif; ?>

      <!-- Resend Button -->
      <form method="POST" action="verify_email.php" style="margin-top: 10px;">
        <button class="btn btn-secondary" type="submit" name="resend_code">
          📤 Send New Code
        </button>
      </form>

    <?php else: ?>
      <!-- Expired Code -->
      <div class="expired-notice">
        ⏰ Your verification code has expired.
      </div>
      
      <form method="POST" action="verify_email.php">
        <button class="btn btn-primary" type="submit" name="resend_code">
          📤 Send New Code
        </button>
      </form>
    <?php endif; ?>

    <!-- Instructions -->
    <div class="instructions">
      <strong>📋 Instructions:</strong>
      <ul>
        <li>Check your email inbox for the verification code</li>
        <li>Check your spam/junk folder if you don't see the email</li>
        <li>Each code expires in 10 minutes</li>
        <li>You have 3 attempts per code</li>
        <li>Use "Send New Code" if your code expires or fails</li>
      </ul>
    </div>

    <!-- Back to Login -->
    <div class="back-link">
      <a href="login.php">← Back to Login</a>
    </div>
  </div>

  <script>
    let timeRemaining = <?php echo $time_remaining; ?>;
    const minutesSpan = document.getElementById('minutes');
    const secondsSpan = document.getElementById('seconds');
    const timerDisplay = document.getElementById('timerDisplay');
    
    function updateTimer() {
      if (timeRemaining <= 0) {
        // Reload page when timer expires
        location.reload();
        return;
      }
      
      const minutes = Math.floor(timeRemaining / 60);
      const seconds = timeRemaining % 60;
      
      if (minutesSpan && secondsSpan) {
        minutesSpan.textContent = minutes.toString().padStart(2, '0');
        secondsSpan.textContent = seconds.toString().padStart(2, '0');
      }
      
      timeRemaining--;
    }
    
    // Only start timer if we have time remaining
    if (timeRemaining > 0) {
      updateTimer(); // Initial call
      setInterval(updateTimer, 1000);
    }
    
    // Auto-format code input (digits only)
    const codeInput = document.querySelector('.code-input');
    if (codeInput) {
      codeInput.addEventListener('input', function(e) {
        // Remove any non-digit characters
        this.value = this.value.replace(/\D/g, '');
        
        // Limit to 6 digits
        if (this.value.length > 6) {
          this.value = this.value.substring(0, 6);
        }
      });
      
      // Auto-submit when 6 digits are entered
      codeInput.addEventListener('keyup', function(e) {
        if (this.value.length === 6 && /^\d{6}$/.test(this.value)) {
          // Small delay to allow user to see the complete code
          setTimeout(() => {
            const form = this.closest('form');
            if (form) {
              form.querySelector('button[name="verify_code"]').click();
            }
          }, 500);
        }
      });
    }
  </script>
</body>
</html>