<?php
header('Content-Type: application/json');
require_once 'expense_functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'expense_date' => $_POST['expense_date'],
                'category' => $_POST['category'],
                'vendor' => $_POST['vendor'],
                'amount' => floatval($_POST['amount']),
                'remarks' => $_POST['remarks'],
                'tax_type' => $_POST['tax_type'],
                'payment_method' => $_POST['payment_method'],
                'vehicle' => $_POST['vehicle'] ?? '',
                'job_linked' => $_POST['job_linked'] ?? '',
                'approved_by' => $_POST['approved_by'] ?? '',
                'status' => $_POST['status']
            ];
            
            $receiptFile = isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] == 0 ? $_FILES['receipt_file'] : null;
            
            $result = addExpense($data, $receiptFile);
            
            echo json_encode(['success' => $result, 'message' => $result ? 'Expense added successfully' : 'Failed to add expense']);
        }
        break;
        
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = intval($_POST['id']);
            $data = [
                'expense_date' => $_POST['expense_date'],
                'category' => $_POST['category'],
                'vendor' => $_POST['vendor'],
                'amount' => floatval($_POST['amount']),
                'remarks' => $_POST['remarks'],
                'tax_type' => $_POST['tax_type'],
                'payment_method' => $_POST['payment_method'],
                'vehicle' => $_POST['vehicle'] ?? '',
                'job_linked' => $_POST['job_linked'] ?? '',
                'approved_by' => $_POST['approved_by'] ?? '',
                'status' => $_POST['status']
            ];
            
            $receiptFile = isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] == 0 ? $_FILES['receipt_file'] : null;
            
            $result = updateExpense($id, $data, $receiptFile);
            
            echo json_encode(['success' => $result, 'message' => $result ? 'Expense updated successfully' : 'Failed to update expense']);
        }
        break;
        
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = intval($_POST['id']);
            $result = deleteExpense($id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Expense deleted successfully' : 'Failed to delete expense']);
        }
        break;
        
    case 'get':
        $id = intval($_GET['id']);
        $expense = getExpenseById($id);
        echo json_encode($expense);
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
        
        echo json_encode([
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
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>