<?php
// hr_reimbursement_actions.php
// HR Department - Handles reimbursement request actions and syncs with Finance
session_start();
require_once 'db.php';

function back($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: hr_reimbursement_dashboard.php');
    exit;
}

$action = $_REQUEST['action'] ?? '';

try {
    if ($action === 'create') {
        // Collect form data
        $employee_name = $_POST['employee_name'] ?? '';
        $employee_id = $_POST['employee_id'] ?? '';
        $address = $_POST['address'] ?? '';
        $contact_no = $_POST['contact_no'] ?? '';
        $cost_center = $_POST['cost_center'] ?? '';
        $reimbursement_type = $_POST['reimbursement_type'] ?? '';
        $amount = (float)($_POST['amount'] ?? 0);
        $expense_date = $_POST['expense_date'] ?? '';
        $description = $_POST['description'] ?? '';
        
        // Validation
        if (empty($employee_name) || empty($cost_center) || $amount <= 0) {
            back('danger', 'Please fill in all required fields.');
        }
        
        // Handle file upload
        $receipt_file = null;
        $receipt_folder = null;
        
        if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
            $year = date('Y');
            $month = date('m');
            $upload_dir = "uploads/receipts/$year/$month/";
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['receipt_file']['name'], PATHINFO_EXTENSION);
            $receipt_file = uniqid('receipt_') . '.' . $file_extension;
            $upload_path = $upload_dir . $receipt_file;
            
            if (move_uploaded_file($_FILES['receipt_file']['tmp_name'], $upload_path)) {
                $receipt_folder = $upload_dir;
            } else {
                $receipt_file = null;
            }
        }
        
        // Find matching budget to link
        $budget_id = null;
        $budget_stmt = $conn->prepare("SELECT id FROM budgets WHERE department = 'HR' AND cost_center = ? AND approval_status = 'Approved' LIMIT 1");
        $budget_stmt->bind_param('s', $cost_center);
        $budget_stmt->execute();
        $budget_result = $budget_stmt->get_result();
        if ($budget_row = $budget_result->fetch_assoc()) {
            $budget_id = $budget_row['id'];
        }
        $budget_stmt->close();
        
        // CRITICAL: Insert into reimbursements table with department = 'HR' and status = 'Pending'
        // This automatically makes it visible to the Finance reimbursement system
        $sql = "INSERT INTO reimbursements (
                    budget_id, employee_name, employee_id, address, contact_no, 
                    department, cost_center, reimbursement_type, amount, expense_date, 
                    description, receipt_file, receipt_folder, status, submission_date
                ) VALUES (?, ?, ?, ?, ?, 'HR', ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('issssssdssss', 
            $budget_id, $employee_name, $employee_id, $address, $contact_no,
            $cost_center, $reimbursement_type, $amount, $expense_date, 
            $description, $receipt_file, $receipt_folder
        );
        
        if ($stmt->execute()) {
            $requestId = $conn->insert_id;
            $stmt->close();
            
            // Log the request
            logReimbursementAction($requestId, 'Submitted', "Reimbursement request submitted to Finance for approval");
            
            back('success', "Reimbursement request submitted successfully! Request ID: #{$requestId}. Your request has been sent to Finance for approval.");
        } else {
            throw new Exception('Failed to submit reimbursement request: ' . $stmt->error);
        }
        
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $employee_name = $_POST['employee_name'] ?? '';
        $employee_id = $_POST['employee_id'] ?? '';
        $address = $_POST['address'] ?? '';
        $contact_no = $_POST['contact_no'] ?? '';
        $cost_center = $_POST['cost_center'] ?? '';
        $reimbursement_type = $_POST['reimbursement_type'] ?? '';
        $amount = (float)($_POST['amount'] ?? 0);
        $expense_date = $_POST['expense_date'] ?? '';
        $description = $_POST['description'] ?? '';
        
        if ($id <= 0) back('danger', 'Invalid reimbursement ID.');
        
        // Check if still pending
        $check_stmt = $conn->prepare("SELECT status FROM reimbursements WHERE id = ? AND department = 'HR'");
        $check_stmt->bind_param('i', $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        $check_stmt->close();
        
        if (!$check_row) {
            back('danger', 'Reimbursement request not found.');
        }
        
        if ($check_row['status'] !== 'Pending') {
            back('warning', 'Only pending reimbursements can be edited.');
        }
        
        // Handle file upload if new file provided
        if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
            $year = date('Y');
            $month = date('m');
            $upload_dir = "uploads/receipts/$year/$month/";
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['receipt_file']['name'], PATHINFO_EXTENSION);
            $receipt_file = uniqid('receipt_') . '.' . $file_extension;
            $upload_path = $upload_dir . $receipt_file;
            
            if (move_uploaded_file($_FILES['receipt_file']['tmp_name'], $upload_path)) {
                $sql = "UPDATE reimbursements 
                        SET employee_name = ?, employee_id = ?, address = ?, contact_no = ?,
                            cost_center = ?, reimbursement_type = ?, amount = ?, 
                            expense_date = ?, description = ?, receipt_file = ?, receipt_folder = ?
                        WHERE id = ? AND department = 'HR'";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssssssdssssi', 
                    $employee_name, $employee_id, $address, $contact_no,
                    $cost_center, $reimbursement_type, $amount, 
                    $expense_date, $description, $receipt_file, $upload_dir, $id
                );
            }
        } else {
            $sql = "UPDATE reimbursements 
                    SET employee_name = ?, employee_id = ?, address = ?, contact_no = ?,
                        cost_center = ?, reimbursement_type = ?, amount = ?, 
                        expense_date = ?, description = ?
                    WHERE id = ? AND department = 'HR'";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssssdsi', 
                $employee_name, $employee_id, $address, $contact_no,
                $cost_center, $reimbursement_type, $amount, 
                $expense_date, $description, $id
            );
        }
        
        if ($stmt->execute()) {
            $stmt->close();
            logReimbursementAction($id, 'Updated', "Reimbursement request updated");
            back('success', 'Reimbursement request updated successfully.');
        } else {
            throw new Exception('Failed to update reimbursement request: ' . $stmt->error);
        }
        
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) back('danger', 'Invalid reimbursement ID.');
        
        // Check if still pending
        $check_stmt = $conn->prepare("SELECT status, employee_name FROM reimbursements WHERE id = ? AND department = 'HR'");
        $check_stmt->bind_param('i', $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        $check_stmt->close();
        
        if (!$check_row) {
            back('danger', 'Reimbursement request not found.');
        }
        
        if ($check_row['status'] !== 'Pending') {
            back('warning', 'Only pending reimbursements can be cancelled.');
        }
        
        $stmt = $conn->prepare("DELETE FROM reimbursements WHERE id = ? AND department = 'HR'");
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            back('success', 'Reimbursement request cancelled successfully.');
        } else {
            throw new Exception('Failed to cancel reimbursement request: ' . $stmt->error);
        }
        
    } else {
        back('warning', 'Unknown action.');
    }
    
} catch (Exception $e) {
    back('danger', 'Error: ' . $e->getMessage());
}

function logReimbursementAction($reimbursement_id, $action, $notes) {
    global $conn;
    
    // Create log table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS reimbursement_action_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reimbursement_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reimbursement_id) REFERENCES reimbursements(id) ON DELETE CASCADE
    )");
    
    $sql = "INSERT INTO reimbursement_action_log (reimbursement_id, action, notes) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('iss', $reimbursement_id, $action, $notes);
        $stmt->execute();
        $stmt->close();
    }
}
?>
