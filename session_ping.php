<?php
require_once 'db.php';

// This file is called by AJAX to keep the session alive
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'logged_out']);
    exit();
}

// Update last activity
$_SESSION['last_activity'] = time();

http_response_code(200);
echo json_encode([
    'status' => 'ok',
    'last_activity' => $_SESSION['last_activity'],
    'current_time' => time()
]);
?>
