<?php
require_once 'db.php';

$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Clear failed attempts for this IP
$stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND success = FALSE");
$stmt->bind_param("s", $ip_address);
$stmt->execute();

echo "Cleared failed login attempts for IP: $ip_address";
echo "<br><a href='login.php'>Try logging in again</a>";

$stmt->close();
?>