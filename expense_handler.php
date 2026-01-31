<?php
// Prevent any output before JSON
ob_start();

// Disable HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set JSON header first
header('Content-Type: application/json');

// Wrap everything in try-catch
try {
    require_once 'expense_functions.php';

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'add':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = [
                    'expense_date' => $_POST['expense_date'] ?? '',
                    'category' => $_POST['category'] ?? '',
                    'vendor' => $_POST['vendor'] ?? '',
                    'amount' => floatval($_POST['amount'] ?? 0),
                    'remarks' => $_POST['remarks'] ?? '',
                    'tax_type' => $_POST['tax_type'] ?? 'None',
                    'payment_method' => $_POST['payment_method'] ?? '',
                    'vehicle' => $_POST['vehicle'] ?? '',
                    'job_linked' => $_POST['job_linked'] ?? '',
                    'approved_by' => $_POST['approved_by'] ?? '',
                    'status' => $_POST['status'] ?? 'Pending'
                ];
                
                $receiptFile = isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] == 0 ? $_FILES['receipt_file'] : null;
                
                $result = addExpense($data, $receiptFile);
                
                // Clear any buffered output
                ob_clean();
                echo json_encode([
                    'success' => $result, 
                    'message' => $result ? 'Expense added successfully' : 'Failed to add expense'
                ]);
            } else {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid request method'
                ]);
            }
            break;
            
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = intval($_POST['id'] ?? 0);
                
                if ($id <= 0) {
                    ob_clean();
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid expense ID'
                    ]);
                    break;
                }
                
                $data = [
                    'expense_date' => $_POST['expense_date'] ?? '',
                    'category' => $_POST['category'] ?? '',
                    'vendor' => $_POST['vendor'] ?? '',
                    'amount' => floatval($_POST['amount'] ?? 0),
                    'remarks' => $_POST['remarks'] ?? '',
                    'tax_type' => $_POST['tax_type'] ?? 'None',
                    'payment_method' => $_POST['payment_method'] ?? '',
                    'vehicle' => $_POST['vehicle'] ?? '',
                    'job_linked' => $_POST['job_linked'] ?? '',
                    'approved_by' => $_POST['approved_by'] ?? '',
                    'status' => $_POST['status'] ?? 'Pending'
                ];
                
                $receiptFile = isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] == 0 ? $_FILES['receipt_file'] : null;
                
                $result = updateExpense($id, $data, $receiptFile);
                
                ob_clean();
                echo json_encode([
                    'success' => $result, 
                    'message' => $result ? 'Expense updated successfully' : 'Failed to update expense'
                ]);
            } else {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid request method'
                ]);
            }
            break;
            
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = intval($_POST['id'] ?? 0);
                
                if ($id <= 0) {
                    ob_clean();
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid expense ID'
                    ]);
                    break;
                }
                
                $result = deleteExpense($id);
                ob_clean();
                echo json_encode([
                    'success' => $result, 
                    'message' => $result ? 'Expense deleted successfully' : 'Failed to delete expense'
                ]);
            } else {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid request method'
                ]);
            }
            break;
            
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid expense ID'
                ]);
                break;
            }
            
            $expense = getExpenseById($id);
            ob_clean();
            
            if ($expense) {
                echo json_encode([
                    'success' => true,
                    'expense' => $expense
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Expense not found'
                ]);
            }
            break;
            
        case 'list':
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $search = $_GET['search'] ?? '';
            
            $expenses = getExpenses($page, $limit, $search);
            $total = getTotalExpenses($search);
            $summary = getExpenseSummary($search);
            
            // Format data for display
            foreach ($expenses as &$expense) {
                $expense['formatted_amount'] = formatCurrency($expense['amount']);
                $expense['formatted_tax_amount'] = formatCurrency($expense['tax_amount']);
                $expense['formatted_date'] = date('M d, Y', strtotime($expense['expense_date']));
                $expense['receipt_attached'] = $expense['receipt_file'] ? 'Yes' : 'No';
            }
            
            ob_clean();
            echo json_encode([
                'success' => true,
                'expenses' => $expenses,
                'total' => $total,
                'summary' => [
                    'total_expenses' => formatCurrency($summary['total_expenses']),
                    'total_tax' => formatCurrency($summary['total_tax']),
                    'net_after_tax' => formatCurrency($summary['net_after_tax'])
                ],
                'current_page' => $page,
                'total_pages' => ceil($total / $limit)
            ]);
            break;
            
        default:
            ob_clean();
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
            break;
    }
} catch (Exception $e) {
    // Log error to file instead of displaying
    error_log('Expense Handler Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    
    // Clear any output and return JSON error
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request. Please try again.'
    ]);
}

// End output buffering and flush
ob_end_flush();
?>
