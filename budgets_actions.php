<?php
// budgets_actions.php – ENHANCED WITH REJECT + ALLOCATION AUTO-SYNC
session_start();
include 'db.php';

// ── Helpers ──────────────────────────────────────────────────────────────────

function back($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: financial_budgeting.php');
    exit;
}

function jsonReply(bool $success, string $message = '', array $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// ── Route ────────────────────────────────────────────────────────────────────
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'create':         handleCreate();  break;
        case 'update':         handleUpdate();  break;
        case 'delete':         handleDelete();  break;
        case 'quick_approve':  handleQuickApprove(); break;
        case 'quick_reject':   handleQuickReject(); break;  // NEW
        case 'save_allocations':   handleSaveAllocations();  break;
        case 'delete_allocation':  handleDeleteAllocation(); break;
        default:               back('warning', 'Unknown action.');
    }
} catch (Throwable $e) {
    if (in_array($action, ['save_allocations', 'delete_allocation'])) {
        jsonReply(false, 'Error: ' . $e->getMessage());
    }
    back('danger', 'Error: ' . $e->getMessage());
}

// ══════════════════════════════════════════════════════════════════════════════
//  CRUD HANDLERS
// ══════════════════════════════════════════════════════════════════════════════

function handleCreate() {
    global $conn;

    $period           = $_POST['period']            ?? '';
    $department       = $_POST['department']        ?? '';
    $cost_center      = $_POST['cost_center']       ?? '';
    $amount_allocated = (float)($_POST['amount_allocated'] ?? 0);
    $amount_used      = (float)($_POST['amount_used']      ?? 0);
    $approved_by      = $_POST['approved_by']       ?? '';
    $approval_status  = $_POST['approval_status']   ?? 'Pending';
    $description      = $_POST['description']       ?? '';

    $sql  = "INSERT INTO budgets (period, department, cost_center, amount_allocated, amount_used, approved_by, approval_status, description)
             VALUES (?,?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssddsss', $period, $department, $cost_center, $amount_allocated, $amount_used, $approved_by, $approval_status, $description);
    $ok   = $stmt->execute();
    $newId = $conn->insert_id;
    $stmt->close();

    if (!$ok) back('danger', 'Failed to add budget allocation.');

    back('success', 'Budget allocation added successfully!');
}

function handleUpdate() {
    global $conn;

    $id               = (int)($_POST['id']              ?? 0);
    $period           = $_POST['period']            ?? '';
    $department       = $_POST['department']        ?? '';
    $cost_center      = $_POST['cost_center']       ?? '';
    $amount_allocated = (float)($_POST['amount_allocated'] ?? 0);
    $amount_used      = (float)($_POST['amount_used']      ?? 0);
    $approved_by      = $_POST['approved_by']       ?? '';
    $approval_status  = $_POST['approval_status']   ?? 'Pending';
    $description      = $_POST['description']       ?? '';

    if ($id <= 0) back('danger', 'Invalid record.');

    $sql  = "UPDATE budgets SET period=?, department=?, cost_center=?, amount_allocated=?, amount_used=?,
                                approved_by=?, approval_status=?, description=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssddsssi', $period, $department, $cost_center, $amount_allocated, $amount_used, $approved_by, $approval_status, $description, $id);
    $ok   = $stmt->execute();
    $stmt->close();

    if (!$ok) back('danger', 'Failed to update budget allocation.');

    back('success', 'Budget allocation updated successfully!');
}

function handleDelete() {
    global $conn;

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) back('danger', 'Invalid record.');

    // Delete child allocations first
    $delAlloc = $conn->prepare("DELETE FROM budget_allocations WHERE budget_id = ?");
    if ($delAlloc) { $delAlloc->bind_param('i', $id); $delAlloc->execute(); $delAlloc->close(); }

    $stmt = $conn->prepare("DELETE FROM budgets WHERE id=?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();

    back($ok ? 'success' : 'danger', $ok ? 'Budget allocation deleted.' : 'Failed to delete budget allocation.');
}

// ══════════════════════════════════════════════════════════════════════════════
//  QUICK APPROVE
// ══════════════════════════════════════════════════════════════════════════════

function handleQuickApprove() {
    global $conn;
    
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) back('danger', 'Invalid budget ID.');
    
    // Get current budget details
    $stmt = $conn->prepare("SELECT department, cost_center, approval_status FROM budgets WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $budget = $result->fetch_assoc();
    $stmt->close();
    
    if (!$budget) {
        back('danger', 'Budget not found.');
    }
    
    if ($budget['approval_status'] === 'Approved') {
        back('info', 'This budget is already approved.');
    }
    
    // Get current user (in real system, get from session)
    $approver = $_SESSION['user_name'] ?? 'Finance Manager';
    
    // Update to approved
    $updateSql = "UPDATE budgets SET approval_status = 'Approved', approved_by = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param('si', $approver, $id);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        
        // Log the approval
        logBudgetAction($id, 'Approved', "Budget approved by {$approver}");
        
        back('success', "Budget request for {$budget['department']} - {$budget['cost_center']} has been approved! " .
             "The department can now view and track their allocated budget.");
    } else {
        throw new Exception('Failed to approve budget: ' . $updateStmt->error);
    }
}

// ══════════════════════════════════════════════════════════════════════════════
//  QUICK REJECT - NEW FEATURE
// ══════════════════════════════════════════════════════════════════════════════

function handleQuickReject() {
    global $conn;
    
    $id = (int)($_POST['id'] ?? 0);
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');
    
    if ($id <= 0) back('danger', 'Invalid budget ID.');
    
    if (empty($rejection_reason)) {
        back('danger', 'Please provide a reason for rejection.');
    }
    
    // Get current budget details
    $stmt = $conn->prepare("SELECT department, cost_center, approval_status FROM budgets WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $budget = $result->fetch_assoc();
    $stmt->close();
    
    if (!$budget) {
        back('danger', 'Budget not found.');
    }
    
    if ($budget['approval_status'] === 'Rejected') {
        back('info', 'This budget is already rejected.');
    }
    
    // Get current user
    $rejector = $_SESSION['user_name'] ?? 'Finance Manager';
    
    // Update to rejected with reason in description
    $updateSql = "UPDATE budgets 
                  SET approval_status = 'Rejected', 
                      approved_by = ?, 
                      description = CONCAT(COALESCE(description, ''), '\n\n--- REJECTION REASON ---\n', ?)
                  WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param('ssi', $rejector, $rejection_reason, $id);
    
    if ($updateStmt->execute()) {
        $updateStmt->close();
        
        // Log the rejection
        logBudgetAction($id, 'Rejected', "Budget rejected by {$rejector}. Reason: {$rejection_reason}");
        
        back('warning', "Budget request for {$budget['department']} - {$budget['cost_center']} has been rejected. " .
             "The department has been notified with the reason.");
    } else {
        throw new Exception('Failed to reject budget: ' . $updateStmt->error);
    }
}

// ══════════════════════════════════════════════════════════════════════════════
//  COST-ALLOCATION HANDLERS - WITH AUTO-SYNC TO AMOUNT_USED
// ══════════════════════════════════════════════════════════════════════════════

function handleSaveAllocations() {
    global $conn;

    $budgetId = (int)($_POST['budget_id'] ?? 0);
    if ($budgetId <= 0) jsonReply(false, 'Invalid budget ID.');

    // Verify the parent budget exists
    $stmt = $conn->prepare("SELECT amount_allocated FROM budgets WHERE id = ?");
    $stmt->bind_param('i', $budgetId);
    $stmt->execute();
    $budget = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$budget) jsonReply(false, 'Budget not found.');

    $budgetAmount = (float)$budget['amount_allocated'];

    // Get current total already allocated
    $stmt = $conn->prepare("SELECT COALESCE(SUM(allocated_amount),0) AS total FROM budget_allocations WHERE budget_id = ?");
    $stmt->bind_param('i', $budgetId);
    $stmt->execute();
    $currentTotal = (float)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Parse incoming lines
    $labels  = $_POST['alloc_label']  ?? [];
    $amounts = $_POST['alloc_amount'] ?? [];
    $notes   = $_POST['alloc_notes']  ?? [];

    if (empty($labels)) jsonReply(false, 'No allocation lines provided.');

    // Validate
    $newTotal = 0;
    $lines    = [];
    for ($i = 0; $i < count($labels); $i++) {
        $label  = trim($labels[$i] ?? '');
        $amount = (float)($amounts[$i] ?? 0);
        $note   = trim($notes[$i]  ?? '');

        if ($label === '')   jsonReply(false, 'Each allocation must have a label.');
        if ($amount <= 0)    jsonReply(false, 'Each allocation amount must be greater than zero.');

        $newTotal += $amount;
        $lines[]  = compact('label', 'amount', 'note');
    }

    // Check ceiling
    if (($currentTotal + $newTotal) > $budgetAmount) {
        jsonReply(false, 'Total allocations exceed the budget amount.');
    }

    // Insert allocations
    $ins = $conn->prepare("INSERT INTO budget_allocations (budget_id, label, allocated_amount, notes) VALUES (?,?,?,?)");
    if (!$ins) jsonReply(false, 'Prepare failed: ' . $conn->error);

    foreach ($lines as $l) {
        $ins->bind_param('isds', $budgetId, $l['label'], $l['amount'], $l['note']);
        if (!$ins->execute()) {
            $ins->close();
            jsonReply(false, 'Insert failed: ' . $conn->error);
        }
    }
    $ins->close();

    // *** AUTO-SYNC: Update amount_used to reflect total allocated ***
    $newGrandTotal = $currentTotal + $newTotal;
    $updateUsed = $conn->prepare("UPDATE budgets SET amount_used = ? WHERE id = ?");
    $updateUsed->bind_param('di', $newGrandTotal, $budgetId);
    $updateUsed->execute();
    $updateUsed->close();

    jsonReply(true, 'Allocations saved and budget usage automatically updated.');
}

function handleDeleteAllocation() {
    global $conn;

    $allocId = (int)($_POST['allocation_id'] ?? 0);
    if ($allocId <= 0) jsonReply(false, 'Invalid allocation ID.');

    // Get the allocation details before deleting
    $getStmt = $conn->prepare("SELECT budget_id, allocated_amount FROM budget_allocations WHERE id = ?");
    $getStmt->bind_param('i', $allocId);
    $getStmt->execute();
    $alloc = $getStmt->get_result()->fetch_assoc();
    $getStmt->close();

    if (!$alloc) jsonReply(false, 'Allocation not found.');

    $budgetId = (int)$alloc['budget_id'];
    $deletedAmount = (float)$alloc['allocated_amount'];

    // Delete the allocation
    $stmt = $conn->prepare("DELETE FROM budget_allocations WHERE id = ?");
    if (!$stmt) jsonReply(false, 'Prepare failed.');
    $stmt->bind_param('i', $allocId);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
        // *** AUTO-SYNC: Recalculate and update amount_used ***
        $recalcStmt = $conn->prepare("SELECT COALESCE(SUM(allocated_amount),0) AS total FROM budget_allocations WHERE budget_id = ?");
        $recalcStmt->bind_param('i', $budgetId);
        $recalcStmt->execute();
        $newTotal = (float)$recalcStmt->get_result()->fetch_assoc()['total'];
        $recalcStmt->close();

        $updateUsed = $conn->prepare("UPDATE budgets SET amount_used = ? WHERE id = ?");
        $updateUsed->bind_param('di', $newTotal, $budgetId);
        $updateUsed->execute();
        $updateUsed->close();

        jsonReply(true, 'Allocation removed and budget usage automatically updated.');
    } else {
        jsonReply(false, 'Failed to delete allocation.');
    }
}

// ══════════════════════════════════════════════════════════════════════════════
//  UTILITY FUNCTIONS
// ══════════════════════════════════════════════════════════════════════════════

function logBudgetAction($budget_id, $action, $notes) {
    global $conn;
    
    // Create log table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS budget_action_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        budget_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE
    )");
    
    $sql = "INSERT INTO budget_action_log (budget_id, action, notes) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('iss', $budget_id, $action, $notes);
        $stmt->execute();
        $stmt->close();
    }
}
?>
