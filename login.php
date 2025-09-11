<?php
require_once 'db.php';

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

// Handle AJAX request for lockout status
if (isset($_GET['ajax']) && $_GET['ajax'] == 'lockout_status') {
    header('Content-Type: application/json');
    $remaining = getLockoutTimeRemaining();
    $locked = isIPLockedOut();
    echo json_encode([
        'locked' => $locked,
        'remaining' => $remaining,
        'minutes' => floor($remaining / 60),
        'seconds' => $remaining % 60
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
        // Check if IP is locked out FIRST - before any processing
        if (isIPLockedOut()) {
            $lockout_remaining = getLockoutTimeRemaining();
            $minutes = floor($lockout_remaining / 60);
            $seconds = $lockout_remaining % 60;
            $error_message = "Account is locked. Please wait {$minutes} minutes and {$seconds} seconds before trying again.";
            $is_locked_out = true;
            // DO NOT process login attempt or log anything during lockout
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
                    logLoginAttempt($username, false); // Log this as it's not a password issue
                } else {
                    // Verify password
                    if (password_verify($password, $user['password'])) {
                        // Login successful - log success FIRST to clear failed attempts
                        logLoginAttempt($username, true);
                        
                        // Clean up old sessions for this user and IP before creating new one
                        cleanupUserSession($user['id']);
                        
                        // Also clean up any expired sessions from this IP
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
                        
                        // Store NEW session in database (only one per user now)
                        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                        $expires_at = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
                        
                        $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("issss", $user['id'], $session_id, $ip_address, $user_agent, $expires_at);
                        $stmt->execute();
                        
                        // Redirect to dashboard
                        header("Location: index.php");
                        exit();
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
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Financial System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/login.css" />
  <style>
    .lockout-timer {
      background: linear-gradient(135deg, #ff6b6b, #ee5a24);
      color: white;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .countdown-display {
      font-size: 2rem;
      font-weight: bold;
      font-family: 'Courier New', monospace;
      margin: 10px 0;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }
    
    .countdown-label {
      font-size: 0.9rem;
      opacity: 0.9;
      margin-bottom: 5px;
    }
    
    .progress-ring {
      transform: rotate(-90deg);
      margin: 0 auto;
    }
    
    .progress-ring-circle {
      transition: stroke-dashoffset 1s linear;
      stroke: #fff;
      stroke-width: 4;
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
  </style>
</head>
<body class="d-flex align-items-center justify-content-center">

  <div class="login-container shadow rounded p-4">
    <h2 class="text-center mb-4 fw-bold">Login</h2>
    
    <!-- Lockout Timer Display -->
    <div id="lockoutTimer" class="lockout-timer" style="display: <?php echo $is_locked_out ? 'block' : 'none'; ?>;">
      <div class="countdown-label">🔒 Account Temporarily Locked</div>
      
      <!-- Progress Ring -->
      <svg class="progress-ring" width="80" height="80">
        <circle class="progress-ring-circle" 
                cx="40" cy="40" r="36"
                stroke-dasharray="226.2" 
                stroke-dashoffset="226.2"
                id="progressCircle">
        </circle>
      </svg>
      
      <div class="countdown-display" id="countdownDisplay">
        <span id="minutes">00</span>:<span id="seconds">00</span>
      </div>
      <div class="countdown-label">Too many failed attempts. Please wait...</div>
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
    
    <form method="POST" action="login.php" id="loginForm" class="<?php echo $is_locked_out ? 'form-disabled' : ''; ?>">

      <div class="mb-3">
        <label for="username" class="form-label fw-semibold">Username</label>
        <input type="text" class="form-control" id="username" name="username" 
               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
               <?php echo $is_locked_out ? 'disabled' : 'required'; ?> />
      </div>

      <div class="mb-3">
        <label for="password" class="form-label fw-semibold">Password</label>
        <input type="password" class="form-control" id="password" name="password" 
               <?php echo $is_locked_out ? 'disabled' : 'required'; ?> />
      </div>

      <button type="submit" class="btn btn-primary w-100 fw-semibold" 
              id="loginButton" <?php echo $is_locked_out ? 'disabled' : ''; ?>>
        <span id="loginButtonText">Login</span>
      </button>
    </form>

    <div class="mt-4 text-center">
      <small class="text-muted">Don't have an account? Contact your administrator.</small>
    </div>
  </div>

  <script>
    // Lockout countdown functionality
    let countdownInterval;
    let lockoutRemaining = <?php echo $lockout_remaining; ?>;
    const isInitiallyLocked = <?php echo $is_locked_out ? 'true' : 'false'; ?>;
    
    // Elements
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
    
    // Progress ring settings
    const circumference = 2 * Math.PI * 36; // radius = 36
    const totalLockoutTime = 15 * 60; // 15 minutes in seconds
    
    function updateCountdown() {
      if (lockoutRemaining <= 0) {
        // Lockout expired
        clearInterval(countdownInterval);
        unlockForm();
        return;
      }
      
      const minutes = Math.floor(lockoutRemaining / 60);
      const seconds = lockoutRemaining % 60;
      
      // Update display
      minutesSpan.textContent = minutes.toString().padStart(2, '0');
      secondsSpan.textContent = seconds.toString().padStart(2, '0');
      
      // Update progress ring
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
      lockoutTimer.style.display = 'none';
      loginForm.classList.remove('form-disabled');
      loginButton.disabled = false;
      usernameInput.disabled = false;
      passwordInput.disabled = false;
      loginButtonText.textContent = 'Login';
      lockoutTimer.classList.remove('pulse');
      
      // Show success message
      const alertDiv = document.createElement('div');
      alertDiv.className = 'alert alert-success';
      alertDiv.innerHTML = '🔓 Lockout expired! You can now attempt to login again.';
      loginForm.parentNode.insertBefore(alertDiv, loginForm);
      
      // Remove the success message after 5 seconds
      setTimeout(() => {
        if (alertDiv.parentNode) {
          alertDiv.parentNode.removeChild(alertDiv);
        }
      }, 5000);
    }
    
    function checkLockoutStatus() {
      fetch(window.location.pathname + '?ajax=lockout_status')
        .then(response => response.json())
        .then(data => {
          if (data.locked && !countdownInterval) {
            // Server says locked and we don't have a countdown running
            lockoutRemaining = data.remaining;
            lockForm();
            countdownInterval = setInterval(updateCountdown, 1000);
            updateCountdown(); // Update immediately
          } else if (data.locked && countdownInterval && Math.abs(lockoutRemaining - data.remaining) > 5) {
            // Server has different time remaining (sync if difference > 5 seconds)
            lockoutRemaining = data.remaining;
            updateCountdown();
          } else if (!data.locked && countdownInterval) {
            // Server says unlocked, clear our countdown
            lockoutRemaining = 0;
            clearInterval(countdownInterval);
            countdownInterval = null;
            unlockForm();
          }
        })
        .catch(error => {
          console.log('Failed to check lockout status:', error);
        });
    }
    
    // Initialize countdown if locked out
    if (isInitiallyLocked && lockoutRemaining > 0) {
      lockForm();
      countdownInterval = setInterval(updateCountdown, 1000);
      updateCountdown(); // Update immediately
    }
    
    // Check server status every 30 seconds to sync with server
    setInterval(checkLockoutStatus, 30000);

    // Session timeout warning (existing code)
    let sessionTimeout;
    let warningTimeout;
    let lastActivity = Date.now();

    function resetSessionTimer() {
      clearTimeout(sessionTimeout);
      clearTimeout(warningTimeout);
      lastActivity = Date.now();
      
      // Show warning 5 minutes before logout
      warningTimeout = setTimeout(function() {
        if (confirm('Your session will expire in 5 minutes due to inactivity. Click OK to continue your session.')) {
          // User clicked OK, send activity ping
          fetch(window.location.href, {
            method: 'HEAD',
            credentials: 'same-origin'
          });
          resetSessionTimer();
        }
      }, <?php echo (SESSION_TIMEOUT - 300) * 1000; ?>); // 5 minutes before 10-minute timeout
      
      // Auto logout after full timeout
      sessionTimeout = setTimeout(function() {
        alert('Your session has expired due to inactivity. You will be redirected to the login page.');
        window.location.href = 'login.php?timeout=1';
      }, <?php echo SESSION_TIMEOUT * 1000; ?>); // 10 minutes
    }

    // Track user activity
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(function(event) {
      document.addEventListener(event, function() {
        if (Date.now() - lastActivity > 60000) { // Only reset if more than 1 minute passed
          resetSessionTimer();
        }
      }, { capture: true, passive: true });
    });

    // Only start timer if we're on a page that requires authentication
    <?php if (isLoggedIn()): ?>
    resetSessionTimer();
    <?php endif; ?>
  </script>

</body>
</html>