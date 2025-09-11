<?php
require_once 'db.php';

$username = 'testuser';
$password = 'user@123';

echo "<h2>Login Debug Test</h2>\n";
echo "<p><strong>Testing credentials:</strong></p>\n";
echo "<ul>\n";
echo "<li>Username: $username</li>\n";
echo "<li>Password: $password</li>\n";
echo "</ul>\n";

// Step 1: Check if user exists
echo "<h3>Step 1: Database Query</h3>\n";
$stmt = $conn->prepare("SELECT id, username, password, role, email, is_active FROM users WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color: red;'>❌ User not found in database</p>\n";
    exit;
}

$user = $result->fetch_assoc();
echo "<p style='color: green;'>✅ User found in database</p>\n";
echo "<ul>\n";
echo "<li>ID: {$user['id']}</li>\n";
echo "<li>Username: {$user['username']}</li>\n";
echo "<li>Email: {$user['email']}</li>\n";
echo "<li>Role: {$user['role']}</li>\n";
echo "<li>Active: " . ($user['is_active'] ? 'Yes' : 'No') . "</li>\n";
echo "<li>Password Hash: " . substr($user['password'], 0, 20) . "...</li>\n";
echo "</ul>\n";

// Step 2: Check if account is active
echo "<h3>Step 2: Account Status</h3>\n";
if (!$user['is_active']) {
    echo "<p style='color: red;'>❌ Account is deactivated</p>\n";
    exit;
}
echo "<p style='color: green;'>✅ Account is active</p>\n";

// Step 3: Test password verification
echo "<h3>Step 3: Password Verification</h3>\n";
echo "<p><strong>Testing password:</strong> '$password'</p>\n";
echo "<p><strong>Against hash:</strong> {$user['password']}</p>\n";

if (password_verify($password, $user['password'])) {
    echo "<p style='color: green;'>✅ Password verification PASSED</p>\n";
} else {
    echo "<p style='color: red;'>❌ Password verification FAILED</p>\n";
    
    // Additional debugging
    echo "<h4>Debug Information:</h4>\n";
    echo "<ul>\n";
    echo "<li>Password length: " . strlen($password) . "</li>\n";
    echo "<li>Hash algorithm: " . password_get_info($user['password'])['algoName'] . "</li>\n";
    echo "<li>Hash valid: " . (password_get_info($user['password'])['algo'] !== null ? 'Yes' : 'No') . "</li>\n";
    echo "</ul>\n";
    
    // Test with a fresh hash
    $test_hash = password_hash($password, PASSWORD_DEFAULT);
    echo "<p><strong>Fresh hash test:</strong></p>\n";
    echo "<p>New hash: $test_hash</p>\n";
    echo "<p>Fresh hash verification: " . (password_verify($password, $test_hash) ? 'PASSED' : 'FAILED') . "</p>\n";
}

// Step 4: Check IP lockout
echo "<h3>Step 4: IP Lockout Check</h3>\n";
if (isIPLockedOut()) {
    echo "<p style='color: red;'>❌ IP is locked out due to failed attempts</p>\n";
} else {
    echo "<p style='color: green;'>✅ IP is not locked out</p>\n";
}

// Step 5: Show recent login attempts
echo "<h3>Step 5: Recent Login Attempts</h3>\n";
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$stmt = $conn->prepare("SELECT username, success, attempted_at FROM login_attempts WHERE ip_address = ? ORDER BY attempted_at DESC LIMIT 5");
$stmt->bind_param("s", $ip_address);
$stmt->execute();
$attempts = $stmt->get_result();

if ($attempts->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>\n";
    echo "<tr><th>Username</th><th>Success</th><th>Time</th></tr>\n";
    while ($attempt = $attempts->fetch_assoc()) {
        echo "<tr>\n";
        echo "<td>{$attempt['username']}</td>\n";
        echo "<td>" . ($attempt['success'] ? 'Yes' : 'No') . "</td>\n";
        echo "<td>{$attempt['attempted_at']}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
} else {
    echo "<p>No recent login attempts from this IP</p>\n";
}

$stmt->close();
$conn->close();

echo "<hr>\n";
echo "<p><strong>Next steps:</strong></p>\n";
echo "<ol>\n";
echo "<li>Try logging in through the normal login form</li>\n";
echo "<li>If password verification failed, the hash might be corrupted</li>\n";
echo "<li>Delete this debug file after use</li>\n";
echo "</ol>\n";
?>