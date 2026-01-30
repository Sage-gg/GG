<?php
require_once 'db.php';

// Require login to access profile
requireLogin();

$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];

// Get current user information
$stmt = $conn->prepare("SELECT email, username, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        
        // Validation
        if (empty($email) || empty($username)) {
            $error_message = "Email and username are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address.";
        } else {
            // Check if email or username exists for other users
            $stmt = $conn->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
            $stmt->bind_param("ssi", $email, $username, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = "Email or username already exists.";
            } else {
                // Update profile
                $stmt = $conn->prepare("UPDATE users SET email = ?, username = ? WHERE id = ?");
                $stmt->bind_param("ssi", $email, $username, $user_id);
                
                if ($stmt->execute()) {
                    // Update session variables
                    $_SESSION['email'] = $email;
                    $_SESSION['username'] = $username;
                    $user_info['email'] = $email;
                    $user_info['username'] = $username;
                    $success_message = "Profile updated successfully.";
                } else {
                    $error_message = "Error updating profile. Please try again.";
                }
            }
            $stmt->close();
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "New password must be at least 6 characters long.";
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if (!password_verify($current_password, $result['password'])) {
                $error_message = "Current password is incorrect.";
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($stmt->execute()) {
                    $success_message = "Password changed successfully.";
                } else {
                    $error_message = "Error changing password. Please try again.";
                }
            }
            $stmt->close();
        }
    }
}

// Get user's active sessions
$stmt = $conn->prepare("SELECT session_id, ip_address, user_agent, created_at, expires_at FROM user_sessions WHERE user_id = ? AND is_active = TRUE ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sessions_result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile - Financial System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

  <?php include 'sidebar_navbar.php'; ?>

  <!-- Main Content -->
  <div class="main-content">
    <div class="container-fluid mt-4 px-4">
      
      <h2 class="fw-bold mb-4">My Profile</h2>
      
      <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($success_message); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      
      <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($error_message); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <div class="row">
        <!-- Profile Information -->
        <div class="col-lg-6 mb-4">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">Profile Information</h5>
            </div>
            <div class="card-body">
              <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="mb-3">
                  <label for="email" class="form-label">Email Address</label>
                  <input type="email" class="form-control" id="email" name="email" 
                         value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                </div>
                
                <div class="mb-3">
                  <label for="username" class="form-label">Username</label>
                  <input type="text" class="form-control" id="username" name="username" 
                         value="<?php echo htmlspecialchars($user_info['username']); ?>" required>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Role</label>
                  <input type="text" class="form-control" readonly 
                         value="<?php echo ucfirst($user_info['role']); ?>">
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Member Since</label>
                  <input type="text" class="form-control" readonly 
                         value="<?php echo date('F j, Y', strtotime($user_info['created_at'])); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Update Profile</button>
              </form>
            </div>
          </div>
        </div>

        <!-- Change Password -->
        <div class="col-lg-6 mb-4">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">Change Password</h5>
            </div>
            <div class="card-body">
              <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div class="mb-3">
                  <label for="current_password" class="form-label">Current Password</label>
                  <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                
                <div class="mb-3">
                  <label for="new_password" class="form-label">New Password</label>
                  <input type="password" class="form-control" id="new_password" name="new_password" required>
                  <small class="form-text text-muted">Minimum 6 characters</small>
                </div>
                
                <div class="mb-3">
                  <label for="confirm_password" class="form-label">Confirm New Password</label>
                  <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-warning">Change Password</button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Active Sessions -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">Active Sessions</h5>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>IP Address</th>
                      <th>Browser/Device</th>
                      <th>Login Time</th>
                      <th>Expires</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $current_session = session_id();
                    while ($session = $sessions_result->fetch_assoc()): 
                    ?>
                    <tr<?php echo $session['session_id'] === $current_session ? ' class="table-primary"' : ''; ?>>
                      <td><?php echo htmlspecialchars($session['ip_address']); ?></td>
                      <td>
                        <small><?php echo htmlspecialchars(substr($session['user_agent'], 0, 80)); ?></small>
                      </td>
                      <td><?php echo date('M j, Y g:i A', strtotime($session['created_at'])); ?></td>
                      <td><?php echo date('M j, Y g:i A', strtotime($session['expires_at'])); ?></td>
                      <td>
                        <?php if ($session['session_id'] === $current_session): ?>
                          <span class="badge bg-success">Current Session</span>
                        <?php else: ?>
                          <span class="badge bg-secondary">Active</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
              <small class="text-muted">
                <i class="me-1">ℹ️</i>
                Sessions automatically expire after 2 minutes of inactivity. The highlighted row is your current session.
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Session timeout handling -->
  <script>
    // Session timeout configuration
    window.SESSION_TIMEOUT = <?php echo SESSION_TIMEOUT * 1000; ?>;
  </script>
  <script src="session_check.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
