<?php
require_once 'db.php';

// Configuration - Change these to your desired admin credentials
$admin_email = 'admin@system.com';
$admin_username = 'admin';
$admin_password = 'admin123';  // Change this to your desired password

// Generate password hash
$password_hash = password_hash($admin_password, PASSWORD_DEFAULT);

echo "<h2>Admin Account Creation</h2>\n";
echo "<p><strong>Email:</strong> $admin_email</p>\n";
echo "<p><strong>Username:</strong> $admin_username</p>\n";
echo "<p><strong>Password:</strong> $admin_password</p>\n";
echo "<p><strong>Generated Hash:</strong> $password_hash</p>\n";
echo "<hr>\n";

try {
    // Check if admin already exists
    $check_stmt = $conn->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ?");
    $check_stmt->bind_param("ss", $admin_username, $admin_email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<h3>Existing Admin Found - Updating...</h3>\n";
        $existing = $result->fetch_assoc();
        echo "<p>Found existing user: ID {$existing['id']}, Username: {$existing['username']}, Email: {$existing['email']}</p>\n";
        
        // Update existing admin
        $update_stmt = $conn->prepare("UPDATE users SET email = ?, username = ?, password = ?, role = 'admin', is_active = TRUE WHERE username = ? OR email = ?");
        $update_stmt->bind_param("sssss", $admin_email, $admin_username, $password_hash, $admin_username, $admin_email);
        
        if ($update_stmt->execute()) {
            echo "<p style='color: green;'><strong>SUCCESS:</strong> Admin account updated successfully!</p>\n";
        } else {
            echo "<p style='color: red;'><strong>ERROR:</strong> Failed to update admin account: " . $update_stmt->error . "</p>\n";
        }
        $update_stmt->close();
    } else {
        echo "<h3>Creating New Admin Account...</h3>\n";
        
        // Create new admin
        $insert_stmt = $conn->prepare("INSERT INTO users (email, username, password, role, is_active) VALUES (?, ?, ?, 'admin', TRUE)");
        $insert_stmt->bind_param("sss", $admin_email, $admin_username, $password_hash);
        
        if ($insert_stmt->execute()) {
            $new_id = $conn->insert_id;
            echo "<p style='color: green;'><strong>SUCCESS:</strong> New admin account created with ID: $new_id</p>\n";
        } else {
            echo "<p style='color: red;'><strong>ERROR:</strong> Failed to create admin account: " . $insert_stmt->error . "</p>\n";
        }
        $insert_stmt->close();
    }
    
    $check_stmt->close();
    
    echo "<hr>\n";
    echo "<h3>Verification</h3>\n";
    
    // Verify the account works
    $verify_stmt = $conn->prepare("SELECT id, email, username, role, is_active FROM users WHERE username = ?");
    $verify_stmt->bind_param("s", $admin_username);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        $admin_data = $verify_result->fetch_assoc();
        echo "<p><strong>Account Details:</strong></p>\n";
        echo "<ul>\n";
        echo "<li>ID: {$admin_data['id']}</li>\n";
        echo "<li>Email: {$admin_data['email']}</li>\n";
        echo "<li>Username: {$admin_data['username']}</li>\n";
        echo "<li>Role: {$admin_data['role']}</li>\n";
        echo "<li>Active: " . ($admin_data['is_active'] ? 'Yes' : 'No') . "</li>\n";
        echo "</ul>\n";
        
        // Test password verification
        if (password_verify($admin_password, $password_hash)) {
            echo "<p style='color: green;'><strong>Password verification test: PASSED</strong></p>\n";
        } else {
            echo "<p style='color: red;'><strong>Password verification test: FAILED</strong></p>\n";
        }
    }
    $verify_stmt->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<h3>Instructions</h3>\n";
echo "<ol>\n";
echo "<li>Use the credentials shown above to log into your system</li>\n";
echo "<li>After successful login, delete this file (create_admin.php) for security</li>\n";
echo "<li>Go to <a href='login.php'>Login Page</a> to test the account</li>\n";
echo "</ol>\n";

$conn->close();
?>