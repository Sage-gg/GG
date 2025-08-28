<?php
// Change this to match your actual functions file name
require_once 'ledger_functions.php'; // or whatever your functions file is named

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        
        // ===================
        // JOURNAL ENTRY OPERATIONS
        // ===================
        
        case 'add_journal_entry':
            $date = sanitizeInput($_POST['date']);
            $reference = sanitizeInput($_POST['reference']);
            $accountCode = sanitizeInput($_POST['account_code']);
            $amount = floatval($_POST['amount']);
            $type = sanitizeInput($_POST['type']);
            $description = sanitizeInput($_POST['description']);
            
            // Validation
            $errors = validateJournalEntry($date, $accountCode, $amount, $type, $description);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                exit;
            }
            
            $result = addJournalEntry($date, $reference, $accountCode, $amount, $type, $description);
            echo json_encode($result);
            break;
            
        case 'get_journal_entry':
            $id = intval($_GET['id']);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid entry ID']);
                exit;
            }
            
            $entry = getJournalEntry($id);
            
            if ($entry) {
                echo json_encode(['success' => true, 'entry' => $entry]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Journal entry not found']);
            }
            break;
            
        case 'update_journal_entry':
            $id = intval($_POST['id']);
            $date = sanitizeInput($_POST['date']);
            $reference = sanitizeInput($_POST['reference']);
            $accountCode = sanitizeInput($_POST['account_code']);
            $amount = floatval($_POST['amount']);
            $type = sanitizeInput($_POST['type']);
            $description = sanitizeInput($_POST['description']);
            
            // Validation
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid entry ID']);
                exit;
            }
            
            $errors = validateJournalEntry($date, $accountCode, $amount, $type, $description);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                exit;
            }
            
            $result = updateJournalEntry($id, $date, $reference, $accountCode, $amount, $type, $description);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Journal entry updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update journal entry']);
            }
            break;
            
        case 'delete_journal_entry':
            $id = intval($_POST['id']);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid entry ID']);
                exit;
            }
            
            $result = deleteJournalEntry($id);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Journal entry deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete journal entry']);
            }
            break;
            
        
        // ===================
        // ACCOUNT OPERATIONS - FIXED
        // ===================
        
        case 'add_account':
            $accountCode = strtoupper(sanitizeInput($_POST['account_code']));
            $accountName = sanitizeInput($_POST['account_name']);
            $accountType = sanitizeInput($_POST['account_type']);
            $description = sanitizeInput($_POST['description'] ?? '');
            
            // Basic validation
            if (empty($accountCode) || empty($accountName) || empty($accountType)) {
                echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
                exit;
            }
            
            $result = addAccount($accountCode, $accountName, $accountType, $description);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Account added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add account - Account code may already exist']);
            }
            break;
            
        case 'get_account':
            $accountCode = sanitizeInput($_GET['account_code']);
            if (empty($accountCode)) {
                echo json_encode(['success' => false, 'message' => 'Invalid account code']);
                exit;
            }
            
            $account = getAccount($accountCode);
            
            if ($account) {
                echo json_encode(['success' => true, 'account' => $account]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Account not found']);
            }
            break;
            
        // FIXED delete_account case - now properly deletes from database
        case 'delete_account':
            $accountCode = sanitizeInput($_POST['account_code']);
            if (empty($accountCode)) {
                echo json_encode(['success' => false, 'message' => 'Invalid account code']);
                exit;
            }
            
            $result = deleteAccount($accountCode);
            echo json_encode($result);
            break;

        case 'update_account':
            $originalCode = sanitizeInput($_POST['original_code']);
            $accountCode = strtoupper(sanitizeInput($_POST['account_code']));
            $accountName = sanitizeInput($_POST['account_name']);
            $accountType = sanitizeInput($_POST['account_type']);
            $description = sanitizeInput($_POST['description'] ?? '');
            
            // Basic validation
            if (empty($accountCode) || empty($accountName) || empty($accountType)) {
                echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
                exit;
            }
            
            $result = updateAccount($originalCode, $accountCode, $accountName, $accountType, $description);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Account updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update account']);
            }
            break;
        
        // ===================
        // LIQUIDATION OPERATIONS
        // ===================
        
        case 'add_liquidation':
            $date = sanitizeInput($_POST['date']);
            $liquidationId = sanitizeInput($_POST['liquidation_id']);
            $employee = sanitizeInput($_POST['employee']);
            $purpose = sanitizeInput($_POST['purpose']);
            $totalAmount = floatval($_POST['total_amount']);
            $status = sanitizeInput($_POST['status']);
            
            // Basic validation
            if (empty($date) || empty($employee) || empty($purpose) || $totalAmount <= 0) {
                echo json_encode(['success' => false, 'message' => 'All fields are required and amount must be greater than 0']);
                exit;
            }
            
            $result = addLiquidationRecord($date, $liquidationId, $employee, $purpose, $totalAmount, $status);
            echo json_encode($result);
            break;
            
        case 'get_liquidation':
            $id = intval($_GET['id']);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid liquidation ID']);
                exit;
            }
            
            $liquidation = getLiquidationRecord($id);
            
            if ($liquidation) {
                echo json_encode(['success' => true, 'liquidation' => $liquidation]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Liquidation record not found']);
            }
            break;
            
        case 'update_liquidation':
            $id = intval($_POST['id']);
            $date = sanitizeInput($_POST['date']);
            $liquidationId = sanitizeInput($_POST['liquidation_id']);
            $employee = sanitizeInput($_POST['employee']);
            $purpose = sanitizeInput($_POST['purpose']);
            $totalAmount = floatval($_POST['total_amount']);
            $status = sanitizeInput($_POST['status']);
            
            // Validation
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid liquidation ID']);
                exit;
            }
            
            if (empty($date) || empty($employee) || empty($purpose) || $totalAmount <= 0) {
                echo json_encode(['success' => false, 'message' => 'All fields are required and amount must be greater than 0']);
                exit;
            }
            
            $result = updateLiquidationRecord($id, $date, $liquidationId, $employee, $purpose, $totalAmount, $status);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Liquidation record updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update liquidation record']);
            }
            break;
            
        case 'delete_liquidation':
            $id = intval($_POST['id']);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid liquidation ID']);
                exit;
            }
            
            $result = deleteLiquidationRecord($id);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Liquidation record deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete liquidation record']);
            }
            break;
        
        // ===================
        // UTILITY OPERATIONS
        // ===================
        
        case 'get_accounts':
            $accounts = getChartOfAccounts();
            echo json_encode($accounts);
            break;
            
        case 'generate_entry_id':
            $entryId = generateEntryId();
            echo json_encode(['success' => true, 'entry_id' => $entryId]);
            break;
            
        case 'generate_liquidation_id':
            $liquidationId = generateLiquidationId();
            echo json_encode(['success' => true, 'liquidation_id' => $liquidationId]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
            break;
    }
    
} catch (Exception $e) {
    error_log("AJAX Handler Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred. Please check logs for details.'
    ]);
}
?>