<?php
// reporting_handler.php - Financial Reporting Backend Handler (UPDATED WITH EXPORT INTEGRATION)
require_once 'db.php';

// Financial Reporting Class
class FinancialReporting {
    private $conn;
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    // Get Income Statement Data
    public function getIncomeStatement($startDate, $endDate) {
        $data = [
            'revenue' => 0,
            'expenses' => 0,
            'net_income' => 0,
            'details' => []
        ];
        
        // Get Revenue from Collections
        $revenueQuery = "SELECT SUM(amount_paid) as total_revenue 
                        FROM collections 
                        WHERE billing_date BETWEEN ? AND ? 
                        AND payment_status IN ('Paid', 'Partial')";
        
        $stmt = $this->conn->prepare($revenueQuery);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $revenue = $result->fetch_assoc();
        $data['revenue'] = $revenue['total_revenue'] ?? 0;
        
        // Get Expenses
        $expenseQuery = "SELECT SUM(amount + tax_amount) as total_expenses 
                        FROM expenses 
                        WHERE expense_date BETWEEN ? AND ? 
                        AND status = 'Approved'";
        
        $stmt = $this->conn->prepare($expenseQuery);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $expense = $result->fetch_assoc();
        $data['expenses'] = $expense['total_expenses'] ?? 0;
        
        // Calculate Net Income
        $data['net_income'] = $data['revenue'] - $data['expenses'];
        
        // Get detailed breakdown
        $data['details'] = $this->getIncomeStatementDetails($startDate, $endDate);
        
        return $data;
    }
    
    // Get Balance Sheet Data
    public function getBalanceSheet($asOfDate) {
        $data = [
            'assets' => [],
            'liabilities' => [],
            'equity' => [],
            'total_assets' => 0,
            'total_liabilities' => 0,
            'total_equity' => 0
        ];
        
        // Get Assets from Journal Entries
        $assetQuery = "SELECT coa.account_name, 
                             SUM(je.debit - je.credit) as balance
                      FROM journal_entries je 
                      JOIN chart_of_accounts coa ON je.account_code = coa.account_code
                      WHERE coa.account_type = 'Asset' 
                      AND je.date <= ? 
                      AND je.status = 'Posted'
                      GROUP BY coa.account_code, coa.account_name
                      HAVING balance != 0";
        
        $stmt = $this->conn->prepare($assetQuery);
        $stmt->bind_param("s", $asOfDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $data['assets'][] = $row;
            $data['total_assets'] += $row['balance'];
        }
        
        // Get Liabilities
        $liabilityQuery = "SELECT coa.account_name, 
                                 SUM(je.credit - je.debit) as balance
                          FROM journal_entries je 
                          JOIN chart_of_accounts coa ON je.account_code = coa.account_code
                          WHERE coa.account_type = 'Liability' 
                          AND je.date <= ? 
                          AND je.status = 'Posted'
                          GROUP BY coa.account_code, coa.account_name
                          HAVING balance != 0";
        
        $stmt = $this->conn->prepare($liabilityQuery);
        $stmt->bind_param("s", $asOfDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $data['liabilities'][] = $row;
            $data['total_liabilities'] += $row['balance'];
        }
        
        // Get Equity
        $equityQuery = "SELECT coa.account_name, 
                              SUM(je.credit - je.debit) as balance
                       FROM journal_entries je 
                       JOIN chart_of_accounts coa ON je.account_code = coa.account_code
                       WHERE coa.account_type = 'Equity' 
                       AND je.date <= ? 
                       AND je.status = 'Posted'
                       GROUP BY coa.account_code, coa.account_name
                       HAVING balance != 0";
        
        $stmt = $this->conn->prepare($equityQuery);
        $stmt->bind_param("s", $asOfDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $data['equity'][] = $row;
            $data['total_equity'] += $row['balance'];
        }
        
        // Add retained earnings (net income)
        $retainedEarnings = $this->getRetainedEarnings($asOfDate);
        if ($retainedEarnings != 0) {
            $data['equity'][] = [
                'account_name' => 'Retained Earnings',
                'balance' => $retainedEarnings
            ];
            $data['total_equity'] += $retainedEarnings;
        }
        
        return $data;
    }
    
    // Get Cash Flow Statement Data
    public function getCashFlowStatement($startDate, $endDate) {
        $data = [
            'operating' => 0,
            'investing' => 0,
            'financing' => 0,
            'net_cash_flow' => 0,
            'details' => []
        ];
        
        // Operating Cash Flow (Collections - Expenses)
        $operatingQuery = "
            SELECT 
                (SELECT COALESCE(SUM(amount_paid), 0) FROM collections 
                 WHERE billing_date BETWEEN ? AND ?) -
                (SELECT COALESCE(SUM(amount + tax_amount), 0) FROM expenses 
                 WHERE expense_date BETWEEN ? AND ? AND status = 'Approved') as operating_cash";
        
        $stmt = $this->conn->prepare($operatingQuery);
        $stmt->bind_param("ssss", $startDate, $endDate, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $operating = $result->fetch_assoc();
        $data['operating'] = $operating['operating_cash'] ?? 0;
        
        // Investing Cash Flow (from journal entries - investing activities)
        $investingQuery = "SELECT COALESCE(SUM(je.debit - je.credit), 0) as investing_cash
                          FROM journal_entries je 
                          JOIN chart_of_accounts coa ON je.account_code = coa.account_code
                          WHERE coa.account_type = 'Asset' 
                          AND coa.account_name LIKE '%Equipment%' 
                          AND je.date BETWEEN ? AND ? 
                          AND je.status = 'Posted'";
        
        $stmt = $this->conn->prepare($investingQuery);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $investing = $result->fetch_assoc();
        $data['investing'] = -($investing['investing_cash'] ?? 0);
        
        // Financing Cash Flow (from liability accounts)
        $financingQuery = "SELECT COALESCE(SUM(je.credit - je.debit), 0) as financing_cash
                          FROM journal_entries je 
                          JOIN chart_of_accounts coa ON je.account_code = coa.account_code
                          WHERE coa.account_type IN ('Liability', 'Equity') 
                          AND je.date BETWEEN ? AND ? 
                          AND je.status = 'Posted'";
        
        $stmt = $this->conn->prepare($financingQuery);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $financing = $result->fetch_assoc();
        $data['financing'] = $financing['financing_cash'] ?? 0;
        
        // Net Cash Flow
        $data['net_cash_flow'] = $data['operating'] + $data['investing'] + $data['financing'];
        
        return $data;
    }
    
    // Helper function to get income statement details
    private function getIncomeStatementDetails($startDate, $endDate) {
        $details = [];
        
        // Revenue details
        $revenueQuery = "SELECT client_name, SUM(amount_paid) as amount
                        FROM collections 
                        WHERE billing_date BETWEEN ? AND ? 
                        AND payment_status IN ('Paid', 'Partial')
                        GROUP BY client_name
                        ORDER BY amount DESC";
        
        $stmt = $this->conn->prepare($revenueQuery);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $details['revenue'] = [];
        while ($row = $result->fetch_assoc()) {
            $details['revenue'][] = $row;
        }
        
        // Expense details
        $expenseQuery = "SELECT category, SUM(amount + tax_amount) as amount
                        FROM expenses 
                        WHERE expense_date BETWEEN ? AND ? 
                        AND status = 'Approved'
                        GROUP BY category
                        ORDER BY amount DESC";
        
        $stmt = $this->conn->prepare($expenseQuery);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $details['expenses'] = [];
        while ($row = $result->fetch_assoc()) {
            $details['expenses'][] = $row;
        }
        
        return $details;
    }
    
    // Helper function to calculate retained earnings
    private function getRetainedEarnings($asOfDate) {
        // Get all income
        $incomeQuery = "SELECT COALESCE(SUM(je.credit - je.debit), 0) as total_income
                       FROM journal_entries je 
                       JOIN chart_of_accounts coa ON je.account_code = coa.account_code
                       WHERE coa.account_type = 'Income' 
                       AND je.date <= ? 
                       AND je.status = 'Posted'";
        
        $stmt = $this->conn->prepare($incomeQuery);
        $stmt->bind_param("s", $asOfDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $income = $result->fetch_assoc();
        
        // Get all expenses
        $expenseQuery = "SELECT COALESCE(SUM(je.debit - je.credit), 0) as total_expense
                        FROM journal_entries je 
                        JOIN chart_of_accounts coa ON je.account_code = coa.account_code
                        WHERE coa.account_type = 'Expense' 
                        AND je.date <= ? 
                        AND je.status = 'Posted'";
        
        $stmt = $this->conn->prepare($expenseQuery);
        $stmt->bind_param("s", $asOfDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $expense = $result->fetch_assoc();
        
        return ($income['total_income'] ?? 0) - ($expense['total_expense'] ?? 0);
    }
    
    // Get Budget Performance Report
    public function getBudgetPerformance($period = null) {
        $whereClause = "";
        $params = [];
        $types = "";
        
        if ($period) {
            $whereClause = "WHERE period = ?";
            $params[] = $period;
            $types = "s";
        }
        
        $query = "SELECT 
                    period,
                    department,
                    cost_center,
                    amount_allocated,
                    amount_used,
                    (amount_allocated - amount_used) as remaining,
                    ROUND((amount_used / amount_allocated) * 100, 2) as utilization_percentage,
                    approval_status
                  FROM budgets 
                  $whereClause
                  ORDER BY period, department, cost_center";
        
        $stmt = $this->conn->prepare($query);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        return $data;
    }
    
    // Enhanced export functions with better error handling
    public function exportToPDF($reportType, $data, $filename) {
        try {
            // Log export attempt
            error_log("PDF Export Request: {$reportType} - {$filename}");
            
            return [
                'success' => true,
                'message' => 'PDF export initiated successfully',
                'filename' => $filename,
                'data' => $data,
                'redirect_url' => 'export.php?action=exportPDF&report_type=' . urlencode($reportType)
            ];
        } catch (Exception $e) {
            error_log("PDF Export Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'PDF export failed: ' . $e->getMessage()
            ];
        }
    }
    
    public function exportToExcel($reportType, $data, $filename) {
        try {
            // Log export attempt
            error_log("Excel Export Request: {$reportType} - {$filename}");
            
            return [
                'success' => true,
                'message' => 'Excel export functionality would be implemented here',
                'filename' => $filename,
                'data' => $data
            ];
        } catch (Exception $e) {
            error_log("Excel Export Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Excel export failed: ' . $e->getMessage()
            ];
        }
    }
}

// Initialize the reporting system
$financialReporting = new FinancialReporting();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'getIncomeStatement':
                $startDate = $_POST['start_date'] ?? date('Y-m-01');
                $endDate = $_POST['end_date'] ?? date('Y-m-t');
                $data = $financialReporting->getIncomeStatement($startDate, $endDate);
                echo json_encode($data);
                break;
                
            case 'getBalanceSheet':
                $asOfDate = $_POST['as_of_date'] ?? date('Y-m-d');
                $data = $financialReporting->getBalanceSheet($asOfDate);
                echo json_encode($data);
                break;
                
            case 'getCashFlow':
                $startDate = $_POST['start_date'] ?? date('Y-m-01');
                $endDate = $_POST['end_date'] ?? date('Y-m-t');
                $data = $financialReporting->getCashFlowStatement($startDate, $endDate);
                echo json_encode($data);
                break;
                
            case 'getTrialBalance':
                $asOfDate = $_POST['as_of_date'] ?? date('Y-m-d');
                $data = $financialReporting->getTrialBalance($asOfDate);
                echo json_encode($data);
                break;
                
            case 'getBudgetPerformance':
                $period = $_POST['period'] ?? null;
                $data = $financialReporting->getBudgetPerformance($period);
                echo json_encode($data);
                break;
                
            case 'exportPDF':
                // Redirect to export.php for PDF handling
                $reportType = $_POST['report_type'];
                $redirectUrl = 'export.php';
                echo json_encode([
                    'success' => true,
                    'redirect' => $redirectUrl,
                    'message' => 'Redirecting to PDF export...'
                ]);
                break;
                
            case 'exportExcel':
                $reportType = $_POST['report_type'];
                $reportData = json_decode($_POST['report_data'], true);
                $filename = $reportType . '_' . date('Y-m-d_H-i-s') . '.xlsx';
                $result = $financialReporting->exportToExcel($reportType, $reportData, $filename);
                echo json_encode($result);
                break;
                
            default:
                throw new Exception('Invalid action: ' . $_POST['action']);
        }
    } catch (Exception $e) {
        error_log("Reporting Handler Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Utility function for formatting currency (can be called from other files)
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return '₱' . number_format(floatval($amount), 2);
    }
}

// Additional utility functions for export support
function sanitizeFilename($filename) {
    // Remove or replace invalid filename characters
    $invalid_chars = ['/', '\\', ':', '*', '?', '"', '<', '>', '|'];
    return str_replace($invalid_chars, '_', $filename);
}

function logExportActivity($reportType, $status, $message = '') {
    $logEntry = date('Y-m-d H:i:s') . " - Export: {$reportType} - Status: {$status}";
    if ($message) {
        $logEntry .= " - {$message}";
    }
    error_log($logEntry);
}

?>
