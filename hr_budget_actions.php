<?php
// hr_budget_actions.php
// Handles HR department budget request actions
session_start();
require_once 'db.php';

function back($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: hr_budget_dashboard.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create_request':
            handleCreateRequest();
            break;
        case 'record_expense':
            handleRecordExpense();
            break;
        default:
            back('warning', 'Unknown action.');
    }
} catch (Exception $e) {
    back('danger', 'Error: ' . $e->getMessage());
}

function handleCreateRequest() {
    global $conn;
    
    $period = $_POST['period'] ?? '';
    $cost_center = $_POST['cost_center'] ?? '';
    $amount_allocated = (float)($_POST['amount_allocated'] ?? 0);
    $description = $_POST['description'] ?? '';
    
    // Validation
    if (empty($period) || empty($cost_center) || $amount_allocated <= 0) {
        back('danger', 'Please fill in all required fields.');
    }
    
    // Insert into budgets table with department as 'HR' and status as 'Pending'
    $sql = "INSERT INTO budgets 
            (period, department, cost_center, amount_allocated, amount_used, approval_status, description, created_at) 
            VALUES (?, 'HR', ?, ?, 0, 'Pending', ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('ssds', $period, $cost_center, $amount_allocated, $description);
    
    if ($stmt->execute()) {
        $requestId = $conn->insert_id;
        $stmt->close();
        
        // Log the request in a tracking table (optional)
        logBudgetRequest($requestId, 'Created', 'Budget request submitted to Finance');
        
        back('success', "Budget request submitted successfully! Request ID: #{$requestId}. Waiting for Finance approval.");
    } else {
        throw new Exception('Failed to create budget request: ' . $stmt->error);
    }
}

function handleRecordExpense() {
    global $conn;
    
    $budget_id = (int)($_POST['budget_id'] ?? 0);
    $expense_amount = (float)($_POST['expense_amount'] ?? 0);
    $expense_description = $_POST['expense_description'] ?? '';
    
    if ($budget_id <= 0 || $expense_amount <= 0) {
        back('danger', 'Invalid expense data.');
    }
    
    // Verify budget exists and is approved
    $checkSql = "SELECT amount_allocated, amount_used, approval_status, cost_center 
                 FROM budgets WHERE id = ? AND department = 'HR'";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param('i', $budget_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $budget = $result->fetch_assoc();
    $stmt->close();
    
    if (!$budget) {
        back('danger', 'Budget not found.');
    }
    
    if ($budget['approval_status'] !== 'Approved') {
        back('danger', 'Cannot record expense for non-approved budget.');
    }
    
    $new_amount_used = $budget['amount_used'] + $expense_amount;
    $remaining = $budget['amount_allocated'] - $new_amount_used;
    
    // Update the budget
    $updateSql = "UPDATE budgets SET amount_used = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param('di', $new_amount_used, $budget_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Log the expense
        logBudgetRequest($budget_id, 'Expense Recorded', 
            "Expense: ₱" . number_format($expense_amount, 2) . " - " . $expense_description);
        
        // Check if budget is running low or overspent
        if ($remaining < 0) {
            // Create compliance alert for overspending
            createComplianceAlert($budget_id, 'overspent', 
                "Budget overspent by ₱" . number_format(abs($remaining), 2));
            
            back('warning', "Expense recorded. WARNING: Budget is now overspent by ₱" . number_format(abs($remaining), 2) . "!");
        } elseif ($budget['amount_allocated'] > 0 && ($remaining / $budget['amount_allocated']) < 0.1) {
            // Create compliance alert for low budget
            createComplianceAlert($budget_id, 'low_budget', 
                "Budget running low. Only ₱" . number_format($remaining, 2) . " remaining.");
            
            back('warning', "Expense recorded. Budget is running low (₱" . number_format($remaining, 2) . " remaining).");
        } else {
            back('success', "Expense of ₱" . number_format($expense_amount, 2) . " recorded successfully. Remaining: ₱" . number_format($remaining, 2));
        }
    } else {
        throw new Exception('Failed to record expense: ' . $stmt->error);
    }
}

function logBudgetRequest($budget_id, $status, $notes) {
    global $conn;
    
    // Create budget_request_log table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS budget_request_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        budget_id INT NOT NULL,
        status VARCHAR(100) NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE
    )");
    
    $sql = "INSERT INTO budget_request_log (budget_id, status, notes) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('iss', $budget_id, $status, $notes);
        $stmt->execute();
        $stmt->close();
    }
}

function createComplianceAlert($budget_id, $alert_type, $message) {
    global $conn;
    
    // Create compliance_alerts table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS compliance_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        budget_id INT NOT NULL,
        alert_type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('Open', 'In Progress', 'Resolved') DEFAULT 'Open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        resolved_at TIMESTAMP NULL,
        resolution_notes TEXT,
        FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE
    )");
    
    // Check if similar alert already exists for this budget
    $checkSql = "SELECT id FROM compliance_alerts 
                 WHERE budget_id = ? AND alert_type = ? AND status != 'Resolved'";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param('is', $budget_id, $alert_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // No existing alert, create new one
        $insertSql = "INSERT INTO compliance_alerts (budget_id, alert_type, message) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param('iss', $budget_id, $alert_type, $message);
        $insertStmt->execute();
        $insertStmt->close();
    }
    
    $stmt->close();
}
?>
