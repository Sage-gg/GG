<?php
require_once 'db.php';

// Clean up session from database if user was logged in
if (isLoggedIn()) {
    $session_id = session_id();
    $user_id = $_SESSION['user_id'];
    
    // Remove session from database completely
    cleanupUserSession($user_id, $session_id);
    
    // Clean up any expired sessions for this user
    $cleanup_stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ? AND expires_at < NOW()");
    $cleanup_stmt->bind_param("i", $user_id);
    $cleanup_stmt->execute();
    $cleanup_stmt->close();
}

// Clear all session data first
$_SESSION = array();

// Delete the session cookie properly
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page with logout message
header("Location: login.php?logout=1");
exit();
?>
