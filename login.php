<?php
require_once 'db.php';
// login.php
// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error_message = '';
$success_message = '';
$lockout_remaining = 0;
$is_locked_out = false;

// Handle timeout message
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error_message = "Your session has expired due to inactivity. Please log in again.";
}

// Handle logout message
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    $success_message = "You have been successfully logged out.";
}

// Check lockout status before processing login
if (isIPLockedOut()) {
    $is_locked_out = true;
    $lockout_remaining = getLockoutTimeRemaining();
}

// AJAX endpoint for lockout status
if (isset($_GET['ajax']) && $_GET['ajax'] == 'lockout_status') {
    header('Content-Type: application/json');
    
    $locked = isIPLockedOut();
    $remaining = $locked ? getLockoutTimeRemaining() : 0;
    $remaining = floor($remaining);
    
    echo json_encode([
        'locked' => $locked,
        'remaining' => $remaining,
        'minutes' => floor($remaining / 60),
        'seconds' => $remaining % 60,
        'server_time' => time(),
        'debug' => [
            'lockout_duration' => LOCKOUT_DURATION,
            'max_attempts' => MAX_LOGIN_ATTEMPTS
        ]
    ]);
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        // Check if IP is locked out FIRST
        if (isIPLockedOut()) {
            $lockout_remaining = getLockoutTimeRemaining();
            $minutes = floor($lockout_remaining / 60);
            $seconds = $lockout_remaining % 60;
            $error_message = "Account is locked. Please wait {$minutes} minutes and {$seconds} seconds before trying again.";
            $is_locked_out = true;
        } else {
            // Attempt login
            $stmt = $conn->prepare("SELECT id, username, password, role, email, is_active FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Check if account is active
                if (!$user['is_active']) {
                    $error_message = "Your account has been deactivated. Please contact an administrator.";
                    logLoginAttempt($username, false);
                } else {
                    // Verify password
                    if (password_verify($password, $user['password'])) {
                        // Password is correct - Log successful password verification FIRST
                        logLoginAttempt($username, true);
                        
                        // Check if user has valid 10-day access token
                        if (hasValidAccessToken($user['id'])) {
                            // User has valid access token - complete login immediately without email verification
                            
                            // Clean up old sessions for this user and IP
                            cleanupUserSession($user['id']);
                            
                            $cleanup_stmt = $conn->prepare("DELETE FROM user_sessions WHERE ip_address = ? AND expires_at < NOW()");
                            $cleanup_stmt->bind_param("s", $_SERVER['REMOTE_ADDR']);
                            $cleanup_stmt->execute();
                            $cleanup_stmt->close();
                            
                            // Set session variables
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['role'] = $user['role'];
                            $_SESSION['last_activity'] = time();
                            $_SESSION['last_regeneration'] = time();
                            
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
                            
                            // Redirect to dashboard
                            header("Location: index.php");
                            exit();
                            
                        } else {
                            // User needs email verification - send code
                            if (sendVerificationCode($user['id'], $user['email'], $user['username'])) {
                                // Store temporary user data in session
                                $_SESSION['pending_verification'] = true;
                                $_SESSION['temp_user_id'] = $user['id'];
                                $_SESSION['temp_username'] = $user['username'];
                                $_SESSION['temp_email'] = $user['email'];
                                
                                // Redirect to verification page
                                header("Location: verify_email.php");
                                exit();
                            } else {
                                $error_message = "Failed to send verification code. Please try again or contact an administrator.";
                            }
                        }
                        
                    } else {
                        $error_message = "Invalid username or password.";
                        logLoginAttempt($username, false);
                        
                        // Check if this attempt caused a lockout
                        if (isIPLockedOut()) {
                            $lockout_remaining = getLockoutTimeRemaining();
                            $is_locked_out = true;
                        }
                    }
                }
            } else {
                $error_message = "Invalid username or password.";
                logLoginAttempt($username, false);
                
                // Check if this attempt caused a lockout
                if (isIPLockedOut()) {
                    $lockout_remaining = getLockoutTimeRemaining();
                    $is_locked_out = true;
                }
            }
            $stmt->close();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login - Financial System</title>
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

    .login-card {
      display: flex;
      width: 1100px;
      height: 450px;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.8);
      border: 5px solid rgba(255, 255, 255, 0.15);
      background: rgba(236, 190, 190, 0.5);
      backdrop-filter: blur(8px);
    }

    .card-left {
      flex: 1;
      background: url("logo.png") no-repeat center center/cover;
      position: relative;
    }
    .card-left::after {
      content:"";
      position:absolute;
      top:0; left:0; right:0; bottom:0;
    }

    .card-right {
      flex: 1;
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
      font-size: 20px;
      margin: 0 0 8px 0;
    }
    
    p.lead {
      margin: 0;
      font-size: 14px;
      color: rgba(0, 0, 0, 0.7);
    }

    .notifications {
      width: 100%;
      margin-bottom: 20px;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 14px;
      width: 100%;
    }

    .input {
      background: rgba(226, 225, 225, 0.11);
      border: 1px solid rgb(0, 0, 0);
      padding: 12px;
      border-radius: 8px;
      color: #000000;
      font-size: 14px;
      outline: none;
    }

    .input::placeholder {
      color: rgba(0, 0, 0, 0.7);
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
    }
    .btn:hover {
      background: linear-gradient(90deg,#000000,#666);
    }
    
    .btn:disabled {
      background: #666;
      cursor: not-allowed;
    }

    .register {
      margin-top: 20px;
      font-size: 13px;
      color: #020202;
    }
    .register a {
      color: #eef1f3ec;
      text-decoration: none;
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

    .lockout-timer {
      background: linear-gradient(135deg, #ff6b6b, #ee5a24);
      color: white;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 15px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      width: 100%;
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .lockout-content {
      display: flex;
      align-items: center;
      gap: 15px;
      width: 100%;
    }
    
    .lockout-icon {
      font-size: 1.5rem;
      flex-shrink: 0;
    }
    
    .lockout-info {
      flex-grow: 1;
      text-align: left;
    }
    
    .lockout-title {
      font-size: 0.9rem;
      font-weight: 600;
      margin: 0 0 4px 0;
      opacity: 0.95;
    }
    
    .lockout-message {
      font-size: 0.8rem;
      margin: 0;
      opacity: 0.85;
    }
    
    .countdown-display {
      font-size: 1.4rem;
      font-weight: bold;
      font-family: 'Courier New', monospace;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
      flex-shrink: 0;
    }
    
    .progress-ring {
      transform: rotate(-90deg);
      flex-shrink: 0;
    }
    
    .progress-ring-circle {
      transition: stroke-dashoffset 1s linear;
      stroke: #fff;
      stroke-width: 3;
      fill: transparent;
      stroke-linecap: round;
    }
    
    .form-disabled {
      opacity: 0.6;
      pointer-events: none;
    }
    
    .pulse {
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0% { opacity: 1; }
      50% { opacity: 0.7; }
      100% { opacity: 1; }
    }

    .security-info {
      background: rgba(23, 162, 184, 0.1);
      border: 1px solid rgba(23, 162, 184, 0.3);
      color: #0c5460;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 15px;
      font-size: 13px;
      text-align: center;
    }

    @media (max-width: 850px) {
      .login-card { 
        flex-direction: column; 
        height: auto; 
        width: 95%; 
      }
      .card-left { 
        height: 200px; 
      }
      .card-right {
        padding: 30px 20px;
      }
      .header-section {
        margin-bottom: 20px;
      }
      
      .lockout-timer {
        flex-direction: column;
        gap: 10px;
        text-align: center;
      }
      
      .lockout-content {
        flex-direction: column;
        gap: 10px;
      }
      
      .lockout-info {
        text-align: center;
      }
      
      .countdown-display {
        font-size: 1.6rem;
      }
    }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="card-left"></div>

    <div class="card-right">
      <div class="header-section">
        <h1>Crane and Trucking Management System</h1>
        <p class="lead">Welcome, Login your account</p>
      </div>

      <div class="notifications">
        
        <div id="lockoutTimer" class="lockout-timer" style="display: <?php echo $is_locked_out ? 'block' : 'none'; ?>;">
          <div class="lockout-content">
            <div class="lockout-icon">🔒</div>
            
            <div class="lockout-info">
              <div class="lockout-title">Account Temporarily Locked</div>
              <div class="lockout-message">Too many failed attempts. Please wait...</div>
            </div>
            
            <svg class="progress-ring" width="50" height="50">
              <circle class="progress-ring-circle" 
                      cx="25" cy="25" r="20"
                      stroke-dasharray="125.7" 
                      stroke-dashoffset="125.7"
                      id="progressCircle">
              </circle>
            </svg>
            
            <div class="countdown-display" id="countdownDisplay">
              <span id="minutes">00</span>:<span id="seconds">00</span>
            </div>
          </div>
        </div>
        
        <?php if (!empty($error_message) && !$is_locked_out): ?>
          <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
          <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
          </div>
        <?php endif; ?>
      </div>

      <form method="POST" action="login.php" id="loginForm" class="<?php echo $is_locked_out ? 'form-disabled' : ''; ?>">
        <input class="input" 
               type="text" 
               id="username"
               name="username"
               placeholder="Username" 
               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
               <?php echo $is_locked_out ? 'disabled' : 'required'; ?>>
        
        <input class="input" 
               type="password" 
               id="password"
               name="password"
               placeholder="Password" 
               <?php echo $is_locked_out ? 'disabled' : 'required'; ?>>
        
        <button class="btn" 
                type="submit"
                id="loginButton"
                <?php echo $is_locked_out ? 'disabled' : ''; ?>>
          <span id="loginButtonText"><?php echo $is_locked_out ? 'Locked' : 'Sign in'; ?></span>
        </button>
      </form>

      <div class="register">
        Don't have an account? Contact your Administrator
      </div>
    </div>
  </div>

  <script>
    // FIXED JavaScript for login page - prevents session timeout loops
    let countdownInterval;
    let lockoutRemaining = <?php echo $lockout_remaining; ?>;
    let serverSyncInterval;
    const isInitiallyLocked = <?php echo $is_locked_out ? 'true' : 'false'; ?>;

    const lockoutTimer = document.getElementById('lockoutTimer');
    const countdownDisplay = document.getElementById('countdownDisplay');
    const minutesSpan = document.getElementById('minutes');
    const secondsSpan = document.getElementById('seconds');
    const loginForm = document.getElementById('loginForm');
    const loginButton = document.getElementById('loginButton');
    const loginButtonText = document.getElementById('loginButtonText');
    const progressCircle = document.getElementById('progressCircle');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');

    const circumference = 2 * Math.PI * 20;
    const totalLockoutTime = 15 * 60;

    function updateCountdown() {
        if (lockoutRemaining <= 0) {
            clearInterval(countdownInterval);
            countdownInterval = null;
            
            checkLockoutStatus().then(() => {
                if (lockoutRemaining <= 0) {
                    unlockForm();
                }
            });
            return;
        }
        
        const minutes = Math.floor(lockoutRemaining / 60);
        const seconds = lockoutRemaining % 60;
        
        minutesSpan.textContent = minutes.toString().padStart(2, '0');
        secondsSpan.textContent = seconds.toString().padStart(2, '0');
        
        const progress = (totalLockoutTime - lockoutRemaining) / totalLockoutTime;
        const dashOffset = circumference - (progress * circumference);
        progressCircle.style.strokeDashoffset = dashOffset;
        
        lockoutRemaining--;
    }

    function lockForm() {
        lockoutTimer.style.display = 'block';
        loginForm.classList.add('form-disabled');
        loginButton.disabled = true;
        usernameInput.disabled = true;
        passwordInput.disabled = true;
        loginButtonText.textContent = 'Locked';
        lockoutTimer.classList.add('pulse');
    }

    function unlockForm() {
        clearInterval(countdownInterval);
        clearInterval(serverSyncInterval);
        
        lockoutTimer.style.display = 'none';
        loginForm.classList.remove('form-disabled');
        loginButton.disabled = false;
        usernameInput.disabled = false;
        passwordInput.disabled = false;
        loginButtonText.textContent = 'Sign in';
        lockoutTimer.classList.remove('pulse');
        
        const existingAlert = document.querySelector('.alert-success');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        const notificationsDiv = document.querySelector('.notifications');
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success';
        alertDiv.innerHTML = '🔓 Lockout expired! You can now attempt to login again.';
        notificationsDiv.appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 5000);
    }

    function checkLockoutStatus() {
        return fetch(window.location.pathname + '?ajax=lockout_status&_=' + Date.now())
            .then(response => response.json())
            .then(data => {
                if (data.locked && data.remaining > 0) {
                    if (!countdownInterval) {
                        lockoutRemaining = data.remaining;
                        lockForm();
                        countdownInterval = setInterval(updateCountdown, 1000);
                        updateCountdown();
                    } else {
                        if (Math.abs(lockoutRemaining - data.remaining) > 2) {
                            lockoutRemaining = data.remaining;
                            updateCountdown();
                        }
                    }
                } else {
                    if (countdownInterval || lockoutTimer.style.display !== 'none') {
                        lockoutRemaining = 0;
                        unlockForm();
                    }
                }
            })
            .catch(error => {
                console.log('Failed to check lockout status:', error);
            });
    }

    if (isInitiallyLocked && lockoutRemaining > 0) {
        lockForm();
        countdownInterval = setInterval(updateCountdown, 1000);
        updateCountdown();
        serverSyncInterval = setInterval(checkLockoutStatus, 5000);
    } else {
        setInterval(checkLockoutStatus, 30000);
    }

    document.getElementById('loginForm').addEventListener('submit', function(e) {
        if (lockoutRemaining > 0 || loginButton.disabled) {
            e.preventDefault();
            checkLockoutStatus().then(() => {
                if (lockoutRemaining <= 0 && !loginButton.disabled) {
                    this.submit();
                }
            });
            return false;
        }
    });

    // Simple activity tracking - no session timeout management on login page
    // (Session timeout only applies to logged-in users)
    
  </script>

</body>
</html>
