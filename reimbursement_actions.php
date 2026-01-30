<?php
// reimbursement_actions.php
session_start();
include 'db.php';

function back($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: financial_reimbursement.php');
    exit;
}

$action = $_REQUEST['action'] ?? '';

try {
    if ($action === 'create') {
        $employee_name = $_POST['employee_name'] ?? '';
        $employee_id = $_POST['employee_id'] ?? '';
        $department = $_POST['department'] ?? '';
        $cost_center = $_POST['cost_center'] ?? '';
        $reimbursement_type = $_POST['reimbursement_type'] ?? '';
        $amount = (float)($_POST['amount'] ?? 0);
        $expense_date = $_POST['expense_date'] ?? '';
        $description = $_POST['description'] ?? '';
        
        // Handle file upload (optional)
        $receipt_file = null;
        if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/receipts/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['receipt_file']['name'], PATHINFO_EXTENSION);
            $receipt_file = uniqid('receipt_') . '.' . $file_extension;
            $upload_path = $upload_dir . $receipt_file;
            
            if (!move_uploaded_file($_FILES['receipt_file']['tmp_name'], $upload_path)) {
                $receipt_file = null;
            }
        }
        
        // Find matching budget to link (optional)
        $budget_id = null;
        $budget_stmt = $conn->prepare("SELECT id FROM budgets WHERE department = ? AND cost_center = ? LIMIT 1");
        $budget_stmt->bind_param('ss', $department, $cost_center);
        $budget_stmt->execute();
        $budget_result = $budget_stmt->get_result();
        if ($budget_row = $budget_result->fetch_assoc()) {
            $budget_id = $budget_row['id'];
        }
        $budget_stmt->close();
        
        $sql = "INSERT INTO reimbursements (budget_id, employee_name, employee_id, department, cost_center, 
                reimbursement_type, amount, expense_date, description, receipt_file, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isssssdsss', $budget_id, $employee_name, $employee_id, $department, 
                         $cost_center, $reimbursement_type, $amount, $expense_date, $description, $receipt_file);
        
        if ($stmt->execute()) {
            back('success', 'Reimbursement request submitted successfully. Awaiting approval.');
        } else {
            back('danger', 'Failed to submit reimbursement request.');
        }
        $stmt->close();
        
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $employee_name = $_POST['employee_name'] ?? '';
        $employee_id = $_POST['employee_id'] ?? '';
        $department = $_POST['department'] ?? '';
        $cost_center = $_POST['cost_center'] ?? '';
        $reimbursement_type = $_POST['reimbursement_type'] ?? '';
        $amount = (float)($_POST['amount'] ?? 0);
        $expense_date = $_POST['expense_date'] ?? '';
        $description = $_POST['description'] ?? '';
        
        if ($id <= 0) back('danger', 'Invalid reimbursement ID.');
        
        // Check if still pending
        $check_stmt = $conn->prepare("SELECT status FROM reimbursements WHERE id = ?");
        $check_stmt->bind_param('i', $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        $check_stmt->close();
        
        if ($check_row['status'] !== 'Pending') {
            back('warning', 'Only pending reimbursements can be edited.');
        }
        
        $sql = "UPDATE reimbursements 
                SET employee_name = ?, employee_id = ?, department = ?, cost_center = ?, 
                    reimbursement_type = ?, amount = ?, expense_date = ?, description = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssdssi', $employee_name, $employee_id, $department, $cost_center, 
                         $reimbursement_type, $amount, $expense_date, $description, $id);
        
        if ($stmt->execute()) {
            back('success', 'Reimbursement request updated successfully.');
        } else {
            back('danger', 'Failed to update reimbursement request.');
        }
        $stmt->close();
        
    } elseif ($action === 'approve') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) back('danger', 'Invalid reimbursement ID.');
        
        // Get current user (in real system, this would come from session)
        $approved_by = $_SESSION['user_name'] ?? 'Manager';
        $approved_date = date('Y-m-d H:i:s');
        
        $sql = "UPDATE reimbursements 
                SET status = 'Approved', approved_by = ?, approved_date = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $approved_by, $approved_date, $id);
        
        if ($stmt->execute()) {
            // Update budget amount_used if linked to budget
            $update_budget_sql = "UPDATE budgets b
                                 JOIN reimbursements r ON b.id = r.budget_id
                                 SET b.amount_used = b.amount_used + r.amount
                                 WHERE r.id = ?";
            $update_stmt = $conn->prepare($update_budget_sql);
            $update_stmt->bind_param('i', $id);
            $update_stmt->execute();
            $update_stmt->close();
            
            back('success', 'Reimbursement approved successfully. Budget updated.');
        } else {
            back('danger', 'Failed to approve reimbursement.');
        }
        $stmt->close();
        
    } elseif ($action === 'reject') {
        $id = (int)($_POST['id'] ?? 0);
        $remarks = $_POST['remarks'] ?? 'No reason provided';
        
        if ($id <= 0) back('danger', 'Invalid reimbursement ID.');
        
        $approved_by = $_SESSION['user_name'] ?? 'Manager';
        
        $sql = "UPDATE reimbursements 
                SET status = 'Rejected', approved_by = ?, remarks = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $approved_by, $remarks, $id);
        
        if ($stmt->execute()) {
            back('warning', 'Reimbursement request rejected.');
        } else {
            back('danger', 'Failed to reject reimbursement.');
        }
        $stmt->close();
        
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) back('danger', 'Invalid reimbursement ID.');
        
        // Check if still pending
        $check_stmt = $conn->prepare("SELECT status FROM reimbursements WHERE id = ?");
        $check_stmt->bind_param('i', $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        $check_stmt->close();
        
        if ($check_row['status'] !== 'Pending') {
            back('warning', 'Only pending reimbursements can be deleted.');
        }
        
        $stmt = $conn->prepare("DELETE FROM reimbursements WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            back('success', 'Reimbursement request deleted.');
        } else {
            back('danger', 'Failed to delete reimbursement request.');
        }
        $stmt->close();
        
    } else {
        back('warning', 'Unknown action.');
    }
    
} catch (Exception $e) {
    back('danger', 'Error: ' . $e->getMessage());
}
