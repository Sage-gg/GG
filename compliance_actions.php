<?php
// compliance_actions.php
// Handles compliance alert actions
session_start();
require_once 'db.php';

function back($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: compliance_dashboard.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'update_status':
            handleUpdateStatus();
            break;
        default:
            back('warning', 'Unknown action.');
    }
} catch (Exception $e) {
    back('danger', 'Error: ' . $e->getMessage());
}

function handleUpdateStatus() {
    global $conn;
    
    $alert_id = (int)($_POST['alert_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';
    $resolution_notes = $_POST['resolution_notes'] ?? '';
    
    if ($alert_id <= 0) {
        back('danger', 'Invalid alert ID.');
    }
    
    if (!in_array($new_status, ['Open', 'In Progress', 'Resolved'])) {
        back('danger', 'Invalid status.');
    }
    
    // If status is being changed to Resolved, set resolved_at timestamp
    if ($new_status === 'Resolved') {
        $sql = "UPDATE compliance_alerts 
                SET status = ?, resolution_notes = ?, resolved_at = NOW() 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $new_status, $resolution_notes, $alert_id);
    } else {
        $sql = "UPDATE compliance_alerts 
                SET status = ?, resolution_notes = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $new_status, $resolution_notes, $alert_id);
    }
    
    if ($stmt->execute()) {
        $stmt->close();
        back('success', "Alert status updated to: {$new_status}");
    } else {
        throw new Exception('Failed to update alert status: ' . $stmt->error);
    }
}
?>
