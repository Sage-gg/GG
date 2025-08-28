<?php
require_once 'db.php';

// Require admin privileges to access user management
requireAdmin();

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_user':
            $email = trim($_POST['email'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            
            // Validation
            if (empty($email) || empty($username) || empty($password)) {
                $error_message = "All fields are required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Please enter a valid email address.";
            } elseif (strlen($password) < 6) {
                $error_message = "Password must be at least 6 characters long.";
            } else {
                // Check if email or username already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                $stmt->bind_param("ss", $email, $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error_message = "Email or username already exists.";
                } else {
                    // Hash password and insert user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (email, username, password, role) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $email, $username, $hashed_password, $role);
                    
                    if ($stmt->execute()) {
                        $success_message = "User '{$username}' has been created successfully.";
                    } else {
                        $error_message = "Error creating user. Please try again.";
                    }
                }
                $stmt->close();
            }
            break;
            
        case 'edit_user':
            $user_id = intval($_POST['user_id'] ?? 0);
            $email = trim($_POST['email'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $role = $_POST['role'] ?? 'user';
            $password = $_POST['password'] ?? '';
            
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
                    $error_message = "Email or username already exists for another user.";
                } else {
                    // Update user
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET email = ?, username = ?, password = ?, role = ? WHERE id = ?");
                        $stmt->bind_param("ssssi", $email, $username, $hashed_password, $role, $user_id);
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET email = ?, username = ?, role = ? WHERE id = ?");
                        $stmt->bind_param("sssi", $email, $username, $role, $user_id);
                    }
                    
                    if ($stmt->execute()) {
                        $success_message = "User has been updated successfully.";
                    } else {
                        $error_message = "Error updating user. Please try again.";
                    }
                }
                $stmt->close();
            }
            break;
            
        case 'toggle_status':
            $user_id = intval($_POST['user_id'] ?? 0);
            $current_status = intval($_POST['current_status'] ?? 0);
            $new_status = $current_status ? 0 : 1;
            
            // Don't allow deactivating self
            if ($user_id == $_SESSION['user_id']) {
                $error_message = "You cannot deactivate your own account.";
            } else {
                $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
                $stmt->bind_param("ii", $new_status, $user_id);
                
                if ($stmt->execute()) {
                    $status_text = $new_status ? 'activated' : 'deactivated';
                    $success_message = "User has been {$status_text} successfully.";
                } else {
                    $error_message = "Error updating user status. Please try again.";
                }
                $stmt->close();
            }
            break;
            
        case 'delete_user':
            $user_id = intval($_POST['user_id'] ?? 0);
            
            // Don't allow deleting self
            if ($user_id == $_SESSION['user_id']) {
                $error_message = "You cannot delete your own account.";
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                
                if ($stmt->execute()) {
                    $success_message = "User has been deleted successfully.";
                } else {
                    $error_message = "Error deleting user. Please try again.";
                }
                $stmt->close();
            }
            break;
    }
}

// Get all users for display
$users_result = $conn->query("SELECT id, email, username, role, is_active, created_at FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Management - Financial System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

  <?php include 'sidebar_navbar.php'; ?>

  <!-- Main Content -->
  <div class="main-content">
    <div class="container-fluid mt-4 px-4">
      
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">User Management</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
          <span class="me-2">➕</span> Add New User
        </button>
      </div>
      
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

      <!-- Users Table -->
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">System Users</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($user = $users_result->fetch_assoc()): ?>
                <tr>
                  <td><?php echo $user['id']; ?></td>
                  <td>
                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                      <span class="badge bg-info ms-2">You</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($user['email']); ?></td>
                  <td>
                    <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-warning text-dark' : 'bg-secondary'; ?>">
                      <?php echo ucfirst($user['role']); ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                      <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                  </td>
                  <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                  <td>
                    <div class="btn-group btn-group-sm" role="group">
                      <button class="btn btn-outline-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                        ✏️
                      </button>
                      <?php if ($user['id'] != $_SESSION['user_id']): ?>
                      <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <input type="hidden" name="current_status" value="<?php echo $user['is_active']; ?>">
                        <button type="submit" class="btn <?php echo $user['is_active'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>" 
                                onclick="return confirm('Are you sure you want to <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?> this user?')">
                          <?php echo $user['is_active'] ? '⏸️' : '▶️'; ?>
                        </button>
                      </form>
                      <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <button type="submit" class="btn btn-outline-danger" 
                                onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                          🗑️
                        </button>
                      </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add User Modal -->
  <div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="add_user">
            
            <div class="mb-3">
              <label for="add_email" class="form-label">Email Address</label>
              <input type="email" class="form-control" id="add_email" name="email" required>
            </div>
            
            <div class="mb-3">
              <label for="add_username" class="form-label">Username</label>
              <input type="text" class="form-control" id="add_username" name="username" required>
            </div>
            
            <div class="mb-3">
              <label for="add_password" class="form-label">Password</label>
              <input type="password" class="form-control" id="add_password" name="password" required>
              <small class="form-text text-muted">Minimum 6 characters</small>
            </div>
            
            <div class="mb-3">
              <label for="add_role" class="form-label">Role</label>
              <select class="form-select" id="add_role" name="role" required>
                <option value="user">User</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Create User</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit User Modal -->
  <div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="mb-3">
              <label for="edit_email" class="form-label">Email Address</label>
              <input type="email" class="form-control" id="edit_email" name="email" required>
            </div>
            
            <div class="mb-3">
              <label for="edit_username" class="form-label">Username</label>
              <input type="text" class="form-control" id="edit_username" name="username" required>
            </div>
            
            <div class="mb-3">
              <label for="edit_password" class="form-label">Password</label>
              <input type="password" class="form-control" id="edit_password" name="password">
              <small class="form-text text-muted">Leave blank to keep current password</small>
            </div>
            
            <div class="mb-3">
              <label for="edit_role" class="form-label">Role</label>
              <select class="form-select" id="edit_role" name="role" required>
                <option value="user">User</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Update User</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Session timeout handling -->
  <script>
    let sessionTimeout;
    let warningTimeout;
    let lastActivity = Date.now();

    function resetSessionTimer() {
      clearTimeout(sessionTimeout);
      clearTimeout(warningTimeout);
      lastActivity = Date.now();
      
      warningTimeout = setTimeout(function() {
        if (confirm('Your session will expire in 5 minutes due to inactivity. Click OK to continue your session.')) {
          fetch(window.location.href, {
            method: 'HEAD',
            credentials: 'same-origin'
          });
          resetSessionTimer();
        }
      }, <?php echo (SESSION_TIMEOUT - 300) * 1000; ?>); // 5 minutes before 10-minute timeout
      
      sessionTimeout = setTimeout(function() {
        alert('Your session has expired due to inactivity. You will be redirected to the login page.');
        window.location.href = 'login.php?timeout=1';
      }, <?php echo SESSION_TIMEOUT * 1000; ?>); // 10 minutes
    }

    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(function(event) {
      document.addEventListener(event, function() {
        if (Date.now() - lastActivity > 60000) {
          resetSessionTimer();
        }
      }, { capture: true, passive: true });
    });

    resetSessionTimer();

    // Edit user function
    function editUser(user) {
      document.getElementById('edit_user_id').value = user.id;
      document.getElementById('edit_email').value = user.email;
      document.getElementById('edit_username').value = user.username;
      document.getElementById('edit_role').value = user.role;
      document.getElementById('edit_password').value = '';
      
      var editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
      editModal.show();
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>