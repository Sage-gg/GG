<?php
require_once 'db.php';

// user_management.php
requireSuperAdmin();

$success_message = '';
$error_message = '';

// Get all departments for dropdown (you can modify this based on your needs)
$departments = ['Operations', 'Finance', 'Accounting', 'Management', 'Administration', 'Sales', 'IT'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_user':
            $email = trim($_POST['email'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? ROLE_STAFF;
            $department = trim($_POST['department'] ?? '');
            $cost_center = trim($_POST['cost_center'] ?? '');
            
            // Validation
            if (empty($email) || empty($username) || empty($password)) {
                $error_message = "Email, username, and password are required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Please enter a valid email address.";
            } elseif (strlen($password) < 6) {
                $error_message = "Password must be at least 6 characters long.";
            } elseif (in_array($role, [ROLE_MANAGER]) && empty($department)) {
                $error_message = "Department is required for Manager role.";
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
                    
                    // Set department and cost_center to NULL if empty
                    $dept_value = !empty($department) ? $department : null;
                    $cc_value = !empty($cost_center) ? $cost_center : null;
                    
                    $stmt = $conn->prepare("INSERT INTO users (email, username, password, role, department, cost_center) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssss", $email, $username, $hashed_password, $role, $dept_value, $cc_value);
                    
                    if ($stmt->execute()) {
                        $new_user_id = $conn->insert_id;
                        
                        // Log the role assignment
                        logRoleChange($new_user_id, null, $role, null, $dept_value, "User created by " . $_SESSION['username']);
                        
                        $role_display = getRoleDisplayName($role);
                        $success_message = "User '{$username}' has been created successfully as {$role_display}.";
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
            $role = $_POST['role'] ?? ROLE_STAFF;
            $department = trim($_POST['department'] ?? '');
            $cost_center = trim($_POST['cost_center'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($username)) {
                $error_message = "Email and username are required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Please enter a valid email address.";
            } elseif (in_array($role, [ROLE_MANAGER]) && empty($department)) {
                $error_message = "Department is required for Manager role.";
            } else {
                // Get old user data for audit log
                $old_stmt = $conn->prepare("SELECT role, department FROM users WHERE id = ?");
                $old_stmt->bind_param("i", $user_id);
                $old_stmt->execute();
                $old_data = $old_stmt->get_result()->fetch_assoc();
                $old_stmt->close();
                
                // Check if email or username exists for other users
                $stmt = $conn->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
                $stmt->bind_param("ssi", $email, $username, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error_message = "Email or username already exists for another user.";
                } else {
                    // Set department and cost_center to NULL if empty
                    $dept_value = !empty($department) ? $department : null;
                    $cc_value = !empty($cost_center) ? $cost_center : null;
                    
                    // Update user
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET email = ?, username = ?, password = ?, role = ?, department = ?, cost_center = ? WHERE id = ?");
                        $stmt->bind_param("ssssssi", $email, $username, $hashed_password, $role, $dept_value, $cc_value, $user_id);
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET email = ?, username = ?, role = ?, department = ?, cost_center = ? WHERE id = ?");
                        $stmt->bind_param("sssssi", $email, $username, $role, $dept_value, $cc_value, $user_id);
                    }
                    
                    if ($stmt->execute()) {
                        // Log role/department change if changed
                        if ($old_data['role'] != $role || $old_data['department'] != $dept_value) {
                            logRoleChange($user_id, $old_data['role'], $role, $old_data['department'], $dept_value, "Updated by " . $_SESSION['username']);
                        }
                        
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
$users_result = $conn->query("SELECT id, email, username, role, department, cost_center, is_active, created_at FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Management - Financial System</title>
  
  <!-- ADD THIS BLOCK -->
  <script>
    // Pass PHP session configuration to JavaScript
    window.SESSION_TIMEOUT = <?php echo SESSION_TIMEOUT * 1000; ?>; // Convert to milliseconds
  </script>
  <!-- END OF ADDED BLOCK -->
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .role-badge {
      font-size: 0.75rem;
      padding: 0.25rem 0.5rem;
    }
    .department-info {
      font-size: 0.85rem;
      color: #6c757d;
    }
  </style>
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
                  <th>Department</th>
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
                    <?php
                    $badge_class = 'bg-secondary';
                    if ($user['role'] === ROLE_SUPER_ADMIN) $badge_class = 'bg-danger';
                    elseif ($user['role'] === ROLE_ADMIN) $badge_class = 'bg-warning text-dark';
                    elseif ($user['role'] === ROLE_FINANCE_MANAGER) $badge_class = 'bg-info';
                    elseif ($user['role'] === ROLE_MANAGER) $badge_class = 'bg-primary';
                    ?>
                    <span class="badge role-badge <?php echo $badge_class; ?>">
                      <?php echo getRoleDisplayName($user['role']); ?>
                    </span>
                  </td>
                  <td>
                    <div class="department-info">
                      <?php if (!empty($user['department'])): ?>
                        <div><strong><?php echo htmlspecialchars($user['department']); ?></strong></div>
                      <?php endif; ?>
                      <?php if (!empty($user['cost_center'])): ?>
                        <div><small>CC: <?php echo htmlspecialchars($user['cost_center']); ?></small></div>
                      <?php endif; ?>
                      <?php if (empty($user['department']) && empty($user['cost_center'])): ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td>
                    <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                      <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                  </td>
                  <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                  <td>
                    <div class="btn-group btn-group-sm" role="group">
                      <button class="btn btn-outline-primary" onclick='editUser(<?php echo json_encode($user); ?>)' title="Edit User">
                        ✏️
                      </button>
                      <?php if ($user['id'] != $_SESSION['user_id']): ?>
                      <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <input type="hidden" name="current_status" value="<?php echo $user['is_active']; ?>">
                        <button type="submit" class="btn <?php echo $user['is_active'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>" 
                                onclick="return confirm('Are you sure you want to <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?> this user?')"
                                title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?> User">
                          <?php echo $user['is_active'] ? '⏸️' : '▶️'; ?>
                        </button>
                      </form>
                      <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <button type="submit" class="btn btn-outline-danger" 
                                onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')"
                                title="Delete User">
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

      <!-- Role Legend -->
      <div class="card mt-3">
        <div class="card-body">
          <h6 class="card-title">Role Descriptions</h6>
          <div class="row">
            <div class="col-md-6">
              <ul class="list-unstyled">
                <li><span class="badge bg-danger role-badge">Super Administrator</span> - Full system access including user management</li>
                <li><span class="badge bg-warning text-dark role-badge">Administrator</span> - Full financial operations (Controller/CFO level)</li>
                <li><span class="badge bg-info role-badge">Finance Manager</span> - Manages financial operations and approvals</li>
              </ul>
            </div>
            <div class="col-md-6">
              <ul class="list-unstyled">
                <li><span class="badge bg-primary role-badge">Manager</span> - Department-level budget and expense management</li>
                <li><span class="badge bg-secondary role-badge">Staff</span> - Basic expense submission only</li>
              </ul>
            </div>
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
              <label for="add_email" class="form-label">Email Address *</label>
              <input type="email" class="form-control" id="add_email" name="email" required>
            </div>
            
            <div class="mb-3">
              <label for="add_username" class="form-label">Username *</label>
              <input type="text" class="form-control" id="add_username" name="username" required>
            </div>
            
            <div class="mb-3">
              <label for="add_password" class="form-label">Password *</label>
              <input type="password" class="form-control" id="add_password" name="password" required>
              <small class="form-text text-muted">Minimum 6 characters</small>
            </div>
            
            <div class="mb-3">
              <label for="add_role" class="form-label">Role *</label>
              <select class="form-select" id="add_role" name="role" required onchange="toggleDepartmentField('add')">
                <?php foreach (getAvailableRoles() as $roleKey => $roleName): ?>
                  <option value="<?php echo $roleKey; ?>" <?php echo $roleKey === ROLE_STAFF ? 'selected' : ''; ?>>
                    <?php echo $roleName; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="mb-3" id="add_department_group">
              <label for="add_department" class="form-label">Department</label>
              <select class="form-select" id="add_department" name="department">
                <option value="">-- Select Department --</option>
                <?php foreach ($departments as $dept): ?>
                  <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
                <?php endforeach; ?>
              </select>
              <small class="form-text text-muted">Required for Manager role</small>
            </div>
            
            <div class="mb-3">
              <label for="add_cost_center" class="form-label">Cost Center</label>
              <input type="text" class="form-control" id="add_cost_center" name="cost_center" placeholder="e.g., CC-001">
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
              <label for="edit_email" class="form-label">Email Address *</label>
              <input type="email" class="form-control" id="edit_email" name="email" required>
            </div>
            
            <div class="mb-3">
              <label for="edit_username" class="form-label">Username *</label>
              <input type="text" class="form-control" id="edit_username" name="username" required>
            </div>
            
            <div class="mb-3">
              <label for="edit_password" class="form-label">Password</label>
              <input type="password" class="form-control" id="edit_password" name="password">
              <small class="form-text text-muted">Leave blank to keep current password</small>
            </div>
            
            <div class="mb-3">
              <label for="edit_role" class="form-label">Role *</label>
              <select class="form-select" id="edit_role" name="role" required onchange="toggleDepartmentField('edit')">
                <?php foreach (getAvailableRoles() as $roleKey => $roleName): ?>
                  <option value="<?php echo $roleKey; ?>">
                    <?php echo $roleName; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div class="mb-3" id="edit_department_group">
              <label for="edit_department" class="form-label">Department</label>
              <select class="form-select" id="edit_department" name="department">
                <option value="">-- Select Department --</option>
                <?php foreach ($departments as $dept): ?>
                  <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
                <?php endforeach; ?>
              </select>
              <small class="form-text text-muted">Required for Manager role</small>
            </div>
            
            <div class="mb-3">
              <label for="edit_cost_center" class="form-label">Cost Center</label>
              <input type="text" class="form-control" id="edit_cost_center" name="cost_center" placeholder="e.g., CC-001">
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

  <script>
    // Session timeout configuration
    window.SESSION_TIMEOUT = <?php echo SESSION_TIMEOUT * 1000; ?>;
  </script>
  <script src="session_check.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Toggle department field based on role selection
    function toggleDepartmentField(prefix) {
      const roleSelect = document.getElementById(prefix + '_role');
      const departmentGroup = document.getElementById(prefix + '_department_group');
      const departmentSelect = document.getElementById(prefix + '_department');
      
      if (roleSelect.value === '<?php echo ROLE_MANAGER; ?>') {
        departmentGroup.style.display = 'block';
        departmentSelect.required = true;
      } else {
        departmentGroup.style.display = 'block'; // Still show but not required
        departmentSelect.required = false;
      }
    }

    // Edit user function
    function editUser(user) {
      document.getElementById('edit_user_id').value = user.id;
      document.getElementById('edit_email').value = user.email;
      document.getElementById('edit_username').value = user.username;
      document.getElementById('edit_role').value = user.role;
      document.getElementById('edit_department').value = user.department || '';
      document.getElementById('edit_cost_center').value = user.cost_center || '';
      document.getElementById('edit_password').value = '';
      
      toggleDepartmentField('edit');
      
      var editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
      editModal.show();
    }

    // Initialize department field visibility on page load
    document.addEventListener('DOMContentLoaded', function() {
      toggleDepartmentField('add');
    });
  </script>

</body>
</html>
