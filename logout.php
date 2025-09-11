<?php
require_once 'db.php';

// Clean up session from database if user was logged in
if (isLoggedIn()) {
    $session_id = session_id();
    $user_id = $_SESSION['user_id'];
    
    // Remove session from database completely (instead of just marking inactive)
    cleanupUserSession($user_id, $session_id);
    
    // Optional: Clean up any other expired sessions for this user
    $cleanup_stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ? AND expires_at < NOW()");
    $cleanup_stmt->bind_param("i", $user_id);
    $cleanup_stmt->execute();
    $cleanup_stmt->close();
}

// Destroy the session
session_unset();
session_destroy();

// Clear any session cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Redirect to login page with logout message
header("Location: login.php?logout=1");
exit();
?>