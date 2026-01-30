<?php
require_once 'db.php';

// verify_email.php
if (!isset($_SESSION['pending_verification']) || !isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit();
}

$error_message = '';
$success_message = '';
$user_id = $_SESSION['temp_user_id'];
$email = $_SESSION['temp_email'] ?? '';
$username = $_SESSION['temp_username'] ?? '';

// Handle code verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'verify_code') {
        $code = trim($_POST['code'] ?? '');
        
        if (empty($code)) {
            $error_message = "Please enter the verification code.";
        } else {
            $result = verifyCode($user_id, $code);
            
            if ($result['success']) {
                // Verification successful - Complete the login with ROLE, DEPARTMENT, COST_CENTER
                $_SESSION['user_id'] = $_SESSION['temp_user_id'];
                $_SESSION['username'] = $_SESSION['temp_username'];
                $_SESSION['email'] = $_SESSION['temp_email'];
                $_SESSION['role'] = $_SESSION['temp_role'];
                $_SESSION['department'] = $_SESSION['temp_department'];
                $_SESSION['cost_center'] = $_SESSION['temp_cost_center'];
                $_SESSION['last_activity'] = time();
                $_SESSION['last_regeneration'] = time();
                
                // Clear temporary session data
                unset($_SESSION['pending_verification']);
                unset($_SESSION['temp_user_id']);
                unset($_SESSION['temp_username']);
                unset($_SESSION['temp_email']);
                unset($_SESSION['temp_role']);
                unset($_SESSION['temp_department']);
                unset($_SESSION['temp_cost_center']);
                
                // Redirect based on role
                $redirect_url = 'index.php'; // Default to dashboard
                
                // Customize redirect based on role
                if ($_SESSION['role'] === ROLE_STAFF) {
                    $redirect_url = 'financial_expense.php'; // Staff goes to expenses
                } elseif ($_SESSION['role'] === ROLE_MANAGER) {
                    $redirect_url = 'financial_budgeting.php'; // Manager goes to budgeting
                } else {
                    $redirect_url = 'index.php'; // Finance levels go to dashboard
                }
                
                header("Location: " . $redirect_url);
                exit();
            } else {
                $error_message = $result['message'];
            }
        }
    } elseif ($action === 'resend_code') {
        if (sendVerificationCode($user_id, $email, $username)) {
            $success_message = "A new verification code has been sent to your email.";
        } else {
            $error_message = "Failed to resend verification code. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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

    .verify-card {
      width: 500px;
      background: rgba(236, 190, 190, 0.5);
      backdrop-filter: blur(8px);
      border-radius: 10px;
      padding: 40px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.8);
      border: 5px solid rgba(255, 255, 255, 0.15);
    }

    .header-section {
      text-align: center;
      margin-bottom: 30px;
    }

    h1 {
      font-size: 24px;
      margin: 0 0 10px 0;
      color: #000;
    }
    
    .lead {
      margin: 0;
      font-size: 14px;
      color: rgba(0, 0, 0, 0.7);
    }

    .email-info {
      background: rgba(255, 255, 255, 0.3);
      border: 1px solid rgba(0, 0, 0, 0.2);
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
    }

    .email-info strong {
      color: #000;
    }

    .alert {
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
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

    form {
      margin-bottom: 20px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #000;
    }

    .input {
      width: 100%;
      background: rgba(226, 225, 225, 0.11);
      border: 1px solid rgb(0, 0, 0);
      padding: 12px;
      border-radius: 8px;
      color: #000000;
      font-size: 18px;
      outline: none;
      text-align: center;
      letter-spacing: 5px;
      font-weight: bold;
    }

    .input::placeholder {
      color: rgba(0, 0, 0, 0.5);
      letter-spacing: normal;
      font-weight: normal;
    }

    .input:focus {
      border-color: var(--accent2);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.904);
    }

    .btn {
      width: 100%;
      padding: 12px;
      border-radius: 8px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      background: linear-gradient(90deg,var(--accent1),var(--accent2));
      color: #fff;
      transition: 0.3s;
      font-size: 16px;
    }
    
    .btn:hover {
      background: linear-gradient(90deg,#000000,#666);
    }

    .btn-secondary {
      background: rgba(108, 117, 125, 0.8);
      margin-top: 10px;
    }

    .btn-secondary:hover {
      background: rgba(90, 98, 104, 0.9);
    }

    .instructions {
      background: rgba(255, 243, 205, 0.3);
      border: 1px solid rgba(255, 193, 7, 0.5);
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 13px;
      color: #000;
    }

    .instructions ul {
      margin: 10px 0 0 0;
      padding-left: 20px;
    }

    .instructions li {
      margin-bottom: 5px;
    }

    .back-to-login {
      text-align: center;
      margin-top: 20px;
    }

    .back-to-login a {
      color: #000;
      text-decoration: none;
      font-size: 14px;
    }

    .back-to-login a:hover {
      text-decoration: underline;
    }

    .timer {
      text-align: center;
      font-size: 13px;
      color: rgba(0, 0, 0, 0.7);
      margin-bottom: 15px;
    }

    @media (max-width: 600px) {
      .verify-card {
        width: 95%;
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body>
  <div class="verify-card">
    <div class="header-section">
      <h1>📧 Email Verification</h1>
      <p class="lead">Enter the 6-digit code sent to your email</p>
    </div>

    <div class="email-info">
      Code sent to: <strong><?php echo htmlspecialchars($email); ?></strong>
    </div>

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

    <div class="instructions">
      <strong>📋 Instructions:</strong>
      <ul>
        <li>Check your email inbox for the verification code</li>
        <li>Enter the 6-digit code below</li>
        <li>Code expires in 10 minutes</li>
        <li>You have 3 attempts to enter the correct code</li>
      </ul>
    </div>

    <div class="timer">
      ⏱️ Code expires in <span id="countdown">10:00</span>
    </div>

    <form method="POST" action="">
      <input type="hidden" name="action" value="verify_code">
      
      <div class="form-group">
        <label for="code">Verification Code</label>
        <input 
          type="text" 
          class="input" 
          id="code" 
          name="code" 
          placeholder="000000"
          maxlength="6"
          pattern="[0-9]{6}"
          required
          autofocus
          autocomplete="off">
      </div>
      
      <button type="submit" class="btn">Verify Code</button>
    </form>

    <form method="POST" action="">
      <input type="hidden" name="action" value="resend_code">
      <button type="submit" class="btn btn-secondary">📨 Resend Code</button>
    </form>

    <div class="back-to-login">
      <a href="logout.php">← Back to Login</a>
    </div>
  </div>

  <script>
    // Countdown timer for code expiration
    let timeRemaining = 600; // 10 minutes in seconds
    const countdownElement = document.getElementById('countdown');

    function updateCountdown() {
      const minutes = Math.floor(timeRemaining / 60);
      const seconds = timeRemaining % 60;
      
      countdownElement.textContent = 
        minutes.toString().padStart(2, '0') + ':' + 
        seconds.toString().padStart(2, '0');
      
      if (timeRemaining <= 0) {
        clearInterval(countdownInterval);
        countdownElement.textContent = 'EXPIRED';
        countdownElement.style.color = '#dc3545';
        
        // Show expired message
        const form = document.querySelector('form');
        form.innerHTML = '<div class="alert alert-danger">Code has expired. Please request a new code.</div>';
      } else if (timeRemaining <= 60) {
        countdownElement.style.color = '#dc3545';
        countdownElement.style.fontWeight = 'bold';
      }
      
      timeRemaining--;
    }

    const countdownInterval = setInterval(updateCountdown, 1000);
    updateCountdown();

    // Auto-format code input
    const codeInput = document.getElementById('code');
    codeInput.addEventListener('input', function(e) {
      this.value = this.value.replace(/[^0-9]/g, '');
      
      // Auto-submit when 6 digits are entered
      if (this.value.length === 6) {
        // Optional: Auto-submit the form
        // this.form.submit();
      }
    });

    // Prevent paste of non-numeric characters
    codeInput.addEventListener('paste', function(e) {
      e.preventDefault();
      const pastedText = (e.clipboardData || window.clipboardData).getData('text');
      const numericOnly = pastedText.replace(/[^0-9]/g, '').substring(0, 6);
      this.value = numericOnly;
    });
  </script>
</body>
</html>
