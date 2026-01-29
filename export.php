<?php
ob_start(); // start output buffering
require_once 'db.php';

// Check authentication
requireLogin();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

$tcpdfPath = __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php';

if (!file_exists($tcpdfPath)) {
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'TCPDF not found. Please clone https://github.com/tecnickcom/TCPDF into /vendor/tecnickcom/tcpdf'
    ]);
    exit;
}

require_once $tcpdfPath;

class FinancialPDFExporter {
    private $conn;
    private $pdf;
    private $useTCPDF;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->useTCPDF = true;
    }
    
    public function exportReport($reportType, $startDate, $endDate, $additionalParams = []) {
        // Get report data
        $reportData = $this->getReportData($reportType, $startDate, $endDate, $additionalParams);
        
        // Create PDF
        $this->initializePDF($reportType, $startDate, $endDate);
        
        // Generate content based on report type
        switch ($reportType) {
            case 'income_statement':
                $this->generateIncomeStatementPDF($reportData, $startDate, $endDate);
                break;
            case 'balance_sheet':
                $this->generateBalanceSheetPDF($reportData, $endDate);
                break;
            case 'cash_flow':
                $this->generateCashFlowPDF($reportData, $startDate, $endDate);
                break;
            case 'trial_balance':
                $this->generateTrialBalancePDF($reportData, $endDate);
                break;
            case 'budget_performance':
                $this->generateBudgetPerformancePDF($reportData, $startDate, $endDate);
                break;
            default:
                throw new Exception('Invalid report type');
        }
        
        // Output PDF
        $this->outputPDF($reportType, $startDate, $endDate);
    }
    
    private function initializePDF($reportType, $startDate, $endDate) {
        if ($this->useTCPDF) {
            // Initialize TCPDF with UTF-8 support
            $this->pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            
            // Set document information
            $this->pdf->SetCreator('Financial Reporting System');
            $this->pdf->SetAuthor('Financial Management System');
            $this->pdf->SetTitle($this->getReportTitle($reportType));
            
            // *** PASSWORD PROTECTION - PIN: "2026" ***
            // Set PDF protection with PIN "2026" required to open the document
            // Parameters: permissions, user_password, owner_password, encryption_mode
            $this->pdf->SetProtection(
                array('print', 'copy'),  // Permissions: allow printing and copying after opening
                '2026',                   // User password (PIN required to open the PDF)
                null,                     // Owner password (null = same as user password)
                2,                        // Encryption: 0=RC4 40bit, 1=RC4 128bit, 2=AES 128bit, 3=AES 256bit
                null                      // Pubkeys (optional)
            );
            
            // Remove default header/footer
            $this->pdf->setPrintHeader(false);
            $this->pdf->setPrintFooter(false);
            
            // Set margins
            $this->pdf->SetMargins(12, 15, 12);
            $this->pdf->SetAutoPageBreak(true, 15);
            
            // CRITICAL FIX: Enable font subsetting for special characters
            $this->pdf->setFontSubsetting(true);
            
            // Add a page
            $this->pdf->AddPage();
            
            // Set default font (DejaVu Sans supports more Unicode characters than Helvetica)
            $this->pdf->SetFont('dejavusans', '', 10);
        }
    }
    
    private function getReportData($reportType, $startDate, $endDate, $additionalParams) {
        switch ($reportType) {
            case 'income_statement':
                return $this->getIncomeStatementData($startDate, $endDate);
            case 'balance_sheet':
                return $this->getBalanceSheetData($endDate);
            case 'cash_flow':
                return $this->getCashFlowData($startDate, $endDate);
            case 'trial_balance':
                return $this->getTrialBalanceData($endDate);
            case 'budget_performance':
                return $this->getBudgetPerformanceData($startDate, $endDate, $additionalParams);
            default:
                return [];
        }
    }
    
    private function getIncomeStatementData($startDate, $endDate) {
        $data = [
            'revenue' => 0,
            'expenses' => 0,
            'net_income' => 0,
            'revenue_details' => [],
            'expense_details' => []
        ];
        
        try {
            // Get revenue
            $revenueQuery = "SELECT client_name, SUM(amount_paid) as total_amount 
                            FROM collections 
                            WHERE DATE(billing_date) BETWEEN ? AND ? 
                            AND payment_status IN ('Paid', 'Partial')
                            AND amount_paid > 0
                            GROUP BY client_name
                            ORDER BY total_amount DESC
                            LIMIT 15";
            
            $stmt = $this->conn->prepare($revenueQuery);
            $stmt->bind_param('ss', $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $data['revenue_details'][] = [
                    'client_name' => $row['client_name'] ?: 'Unknown Client',
                    'total_amount' => floatval($row['total_amount'])
                ];
                $data['revenue'] += floatval($row['total_amount']);
            }
            $stmt->close();
            
            // Get expenses
            $expenseQuery = "SELECT category, SUM(amount) as total_amount 
                            FROM expenses 
                            WHERE DATE(expense_date) BETWEEN ? AND ? 
                            AND amount > 0
                            GROUP BY category
                            ORDER BY total_amount DESC
                            LIMIT 15";
            
            $stmt = $this->conn->prepare($expenseQuery);
            $stmt->bind_param('ss', $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $data['expense_details'][] = [
                    'category' => $row['category'] ?: 'General Expenses',
                    'total_amount' => floatval($row['total_amount'])
                ];
                $data['expenses'] += floatval($row['total_amount']);
            }
            $stmt->close();
            
            $data['net_income'] = $data['revenue'] - $data['expenses'];
            
        } catch (Exception $e) {
            error_log("Income Statement Data Error: " . $e->getMessage());
        }
        
        return $data;
    }
    
    private function getBalanceSheetData($asOfDate) {
        $data = [
            'assets' => [],
            'liabilities' => [],
            'equity' => [],
            'total_assets' => 0,
            'total_liabilities' => 0,
            'total_equity' => 0
        ];
        
        try {
            // Get cash and receivables
            $query = "SELECT 
                SUM(CASE WHEN payment_status = 'Paid' THEN amount_paid ELSE 0 END) as cash,
                SUM(CASE WHEN payment_status IN ('Pending', 'Partial') THEN (amount_due - COALESCE(amount_paid, 0)) ELSE 0 END) as receivables
                FROM collections 
                WHERE DATE(billing_date) <= ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $asOfDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result) {
                $row = $result->fetch_assoc();
                $cash = floatval($row['cash'] ?? 0);
                $receivables = floatval($row['receivables'] ?? 0);
                
                if ($cash > 0) {
                    $data['assets'][] = ['account_name' => 'Cash and Cash Equivalents', 'balance' => $cash];
                    $data['total_assets'] += $cash;
                }
                if ($receivables > 0) {
                    $data['assets'][] = ['account_name' => 'Accounts Receivable', 'balance' => $receivables];
                    $data['total_assets'] += $receivables;
                }
            }
            $stmt->close();
            
            // Add equity
            if ($data['total_assets'] > 0) {
                $data['equity'][] = ['account_name' => "Owner's Equity", 'balance' => $data['total_assets']];
                $data['total_equity'] = $data['total_assets'];
            }
            
        } catch (Exception $e) {
            error_log("Balance Sheet Data Error: " . $e->getMessage());
        }
        
        return $data;
    }
    
    private function getCashFlowData($startDate, $endDate) {
        $data = [
            'operating' => 0,
            'investing' => 0,
            'financing' => 0,
            'net_cash_flow' => 0
        ];
        
        try {
            // Get revenue (cash inflows)
            $revenueQuery = "SELECT COALESCE(SUM(amount_paid), 0) as revenue 
                            FROM collections 
                            WHERE DATE(billing_date) BETWEEN ? AND ? 
                            AND payment_status IN ('Paid', 'Partial')";
            
            $stmt = $this->conn->prepare($revenueQuery);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            $revenue = 0;
            if ($result) {
                $row = $result->fetch_assoc();
                $revenue = floatval($row['revenue'] ?? 0);
            }
            $stmt->close();
            
            // Get expenses (cash outflows)
            $expenseQuery = "SELECT COALESCE(SUM(amount), 0) as expenses 
                            FROM expenses 
                            WHERE DATE(expense_date) BETWEEN ? AND ? 
                            AND amount > 0";
            
            $stmt = $this->conn->prepare($expenseQuery);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            $expenses = 0;
            if ($result) {
                $row = $result->fetch_assoc();
                $expenses = floatval($row['expenses'] ?? 0);
            }
            $stmt->close();
            
            $data['operating'] = $revenue - $expenses;
            $data['net_cash_flow'] = $data['operating'];
            
        } catch (Exception $e) {
            error_log("Cash Flow Data Error: " . $e->getMessage());
        }
        
        return $data;
    }
    
    private function getTrialBalanceData($asOfDate) {
        $accounts = [];
        $totalDebits = 0;
        $totalCredits = 0;
        
        try {
            // Get revenue accounts (Credits)
            $revenueQuery = "SELECT 
                'Revenue' as account_type,
                client_name as account_name,
                SUM(amount_paid) as amount,
                'Credit' as normal_balance
                FROM collections 
                WHERE DATE(billing_date) <= ? 
                AND payment_status IN ('Paid', 'Partial')
                AND amount_paid > 0
                GROUP BY client_name
                HAVING amount > 0
                ORDER BY amount DESC
                LIMIT 10";
            
            $stmt = $this->conn->prepare($revenueQuery);
            if ($stmt) {
                $stmt->bind_param("s", $asOfDate);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $amount = floatval($row['amount']);
                    $accounts[] = [
                        'account_code' => 'REV-' . substr(md5($row['account_name']), 0, 4),
                        'account_name' => 'Revenue - ' . ($row['account_name'] ?: 'General'),
                        'account_type' => 'Revenue',
                        'debit' => 0,
                        'credit' => $amount,
                        'balance' => $amount
                    ];
                    $totalCredits += $amount;
                }
                $stmt->close();
            }
            
            // Get expense accounts (Debits)
            $expenseQuery = "SELECT 
                category,
                SUM(amount) as amount
                FROM expenses 
                WHERE DATE(expense_date) <= ? 
                AND amount > 0
                GROUP BY category
                ORDER BY amount DESC
                LIMIT 15";
            
            $stmt = $this->conn->prepare($expenseQuery);
            if ($stmt) {
                $stmt->bind_param("s", $asOfDate);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $amount = floatval($row['amount']);
                    if ($amount > 0) {
                        $accounts[] = [
                            'account_code' => 'EXP-' . substr(md5($row['category']), 0, 4),
                            'account_name' => 'Expense - ' . ($row['category'] ?: 'General'),
                            'account_type' => 'Expense',
                            'debit' => $amount,
                            'credit' => 0,
                            'balance' => $amount
                        ];
                        $totalDebits += $amount;
                    }
                }
                $stmt->close();
            }
            
            // Add Cash Account to balance
            $cashAmount = $totalCredits - $totalDebits;
            if ($cashAmount != 0) {
                $accounts[] = [
                    'account_code' => 'CASH-001',
                    'account_name' => 'Cash and Cash Equivalents',
                    'account_type' => 'Asset',
                    'debit' => $cashAmount > 0 ? $cashAmount : 0,
                    'credit' => $cashAmount < 0 ? abs($cashAmount) : 0,
                    'balance' => $cashAmount
                ];
                
                if ($cashAmount > 0) {
                    $totalDebits += $cashAmount;
                } else {
                    $totalCredits += abs($cashAmount);
                }
            }
            
            // Add Owner's Equity to balance if needed
            $balanceDifference = $totalDebits - $totalCredits;
            if (abs($balanceDifference) > 0.01) {
                $accounts[] = [
                    'account_code' => 'EQ-001',
                    'account_name' => "Owner's Equity",
                    'account_type' => 'Equity',
                    'debit' => $balanceDifference < 0 ? abs($balanceDifference) : 0,
                    'credit' => $balanceDifference > 0 ? $balanceDifference : 0,
                    'balance' => -$balanceDifference
                ];
                
                if ($balanceDifference > 0) {
                    $totalCredits += $balanceDifference;
                } else {
                    $totalDebits += abs($balanceDifference);
                }
            }
            
        } catch (Exception $e) {
            error_log("Trial Balance Data Error: " . $e->getMessage());
        }
        
        return [
            'accounts' => $accounts,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'is_balanced' => abs($totalDebits - $totalCredits) < 0.01
        ];
    }
    
    private function getBudgetPerformanceData($startDate, $endDate, $params) {
        $budgets = [];
        
        try {
            // Check if budget table exists
            $tableCheckQuery = "SHOW TABLES LIKE 'budgets'";
            $tableExists = $this->conn->query($tableCheckQuery);
            
            if ($tableExists && $tableExists->num_rows > 0) {
                // Use actual budget table
                $query = "SELECT period, department, cost_center, 
                          amount_allocated, amount_used
                          FROM budgets 
                          WHERE DATE(period_start) >= ? AND DATE(period_end) <= ?";
                
                if (isset($params['department_filter']) && !empty($params['department_filter'])) {
                    $query .= " AND department = ?";
                }
                
                $query .= " ORDER BY department, cost_center LIMIT 15";
                
                $stmt = $this->conn->prepare($query);
                
                if (isset($params['department_filter']) && !empty($params['department_filter'])) {
                    $stmt->bind_param('sss', $startDate, $endDate, $params['department_filter']);
                } else {
                    $stmt->bind_param('ss', $startDate, $endDate);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $budgets[] = $row;
                }
                $stmt->close();
            } else {
                // Generate budget performance from actual data
                $budgets = $this->generateBudgetPerformanceFromActuals($startDate, $endDate);
            }
            
            // Calculate performance metrics
            foreach ($budgets as &$budget) {
                $allocated = floatval($budget['amount_allocated'] ?? 0);
                $used = floatval($budget['amount_used'] ?? 0);
                
                $budget['amount_allocated'] = $allocated;
                $budget['amount_used'] = $used;
                $budget['remaining'] = $allocated - $used;
                $budget['variance'] = $used - $allocated;
                $budget['utilization_percentage'] = $allocated > 0 ? round(($used / $allocated) * 100, 1) : 0;
                
                // Determine status
                if ($budget['utilization_percentage'] > 100) {
                    $budget['approval_status'] = 'Over';
                } elseif ($budget['utilization_percentage'] > 90) {
                    $budget['approval_status'] = 'Risk';
                } elseif ($budget['utilization_percentage'] > 70) {
                    $budget['approval_status'] = 'Track';
                } else {
                    $budget['approval_status'] = 'Under';
                }
            }
            
        } catch (Exception $e) {
            error_log("Budget Performance Data Error: " . $e->getMessage());
        }
        
        return $budgets;
    }
    
    private function generateBudgetPerformanceFromActuals($startDate, $endDate) {
        $budgets = [];
        
        try {
            // Get expenses by category
            $expenseQuery = "SELECT 
                            COALESCE(category, 'Uncategorized') as category,
                            SUM(amount) as amount
                          FROM expenses 
                          WHERE DATE(expense_date) BETWEEN ? AND ?
                          AND amount > 0
                          GROUP BY category
                          ORDER BY amount DESC
                          LIMIT 8";
            
            $stmt = $this->conn->prepare($expenseQuery);
            if ($stmt) {
                $stmt->bind_param("ss", $startDate, $endDate);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $actualAmount = floatval($row['amount']);
                    $budgetAmount = $actualAmount * 1.15; // Assume budget was 15% higher
                    
                    $budgets[] = [
                        'period' => date('Y-m', strtotime($startDate)),
                        'department' => 'Ops',
                        'cost_center' => substr($row['category'], 0, 15),
                        'amount_allocated' => $budgetAmount,
                        'amount_used' => $actualAmount
                    ];
                }
                $stmt->close();
            }
            
        } catch (Exception $e) {
            error_log("Generate Budget From Actuals Error: " . $e->getMessage());
        }
        
        return $budgets;
    }
    
    // ==================== PDF GENERATION METHODS ====================
    
    private function generateIncomeStatementPDF($data, $startDate, $endDate) {
        // Header
        $this->pdf->SetFont('dejavusans', 'B', 16);
        $this->pdf->Cell(0, 10, 'INCOME STATEMENT', 0, 1, 'C');
        
        // *** FIX: Add date range prominently ***
        $this->pdf->SetFont('dejavusans', 'B', 11);
        $this->pdf->SetTextColor(0, 0, 128); // Dark blue
        $this->pdf->Cell(0, 6, 'Report Period: ' . $this->formatDateForDisplay($startDate) . ' to ' . $this->formatDateForDisplay($endDate), 0, 1, 'C');
        $this->pdf->SetTextColor(0, 0, 0); // Reset to black
        $this->pdf->Ln(5);
        
        // Revenue Section
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->SetFillColor(200, 200, 200);
        $this->pdf->Cell(0, 7, 'REVENUE', 1, 1, 'L', true);
        
        $this->pdf->SetFont('dejavusans', '', 9);
        if (!empty($data['revenue_details'])) {
            foreach ($data['revenue_details'] as $item) {
                $this->pdf->Cell(140, 6, '  ' . $item['client_name'], 'LR', 0);
                $this->pdf->Cell(46, 6, $this->formatCurrency($item['total_amount']), 'R', 1, 'R');
            }
        } else {
            $this->pdf->Cell(0, 6, '  No detailed revenue data available', 1, 1, 'L');
        }
        
        $this->pdf->SetFont('dejavusans', 'B', 10);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(140, 7, 'Total Revenue', 1, 0, 'L', true);
        $this->pdf->Cell(46, 7, $this->formatCurrency($data['revenue']), 1, 1, 'R', true);
        $this->pdf->Ln(3);
        
        // Expenses Section
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->SetFillColor(200, 200, 200);
        $this->pdf->Cell(0, 7, 'EXPENSES', 1, 1, 'L', true);
        
        $this->pdf->SetFont('dejavusans', '', 9);
        if (!empty($data['expense_details'])) {
            foreach ($data['expense_details'] as $item) {
                $this->pdf->Cell(140, 6, '  ' . $item['category'], 'LR', 0);
                $this->pdf->Cell(46, 6, $this->formatCurrency($item['total_amount']), 'R', 1, 'R');
            }
        } else {
            $this->pdf->Cell(0, 6, '  No detailed expense data available', 1, 1, 'L');
        }
        
        $this->pdf->SetFont('dejavusans', 'B', 10);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(140, 7, 'Total Expenses', 1, 0, 'L', true);
        $this->pdf->Cell(46, 7, $this->formatCurrency($data['expenses']), 1, 1, 'R', true);
        $this->pdf->Ln(3);
        
        // Net Income
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(140, 8, 'NET INCOME', 1, 0, 'L', true);
        $this->pdf->Cell(46, 8, $this->formatCurrency($data['net_income']), 1, 1, 'R', true);
        
        // Summary box
        $this->pdf->Ln(5);
        $this->pdf->SetFont('dejavusans', 'B', 11);
        $this->pdf->Cell(0, 6, 'Financial Summary', 0, 1);
        $this->pdf->SetFont('dejavusans', '', 9);
        $this->pdf->MultiCell(0, 5, 
            "Total Revenue: " . $this->formatCurrency($data['revenue']) . "\n" .
            "Total Expenses: " . $this->formatCurrency($data['expenses']) . "\n" .
            "Net Income: " . $this->formatCurrency($data['net_income']),
            1, 'L');
    }
    
    private function generateBalanceSheetPDF($data, $asOfDate) {
        // Header
        $this->pdf->SetFont('dejavusans', 'B', 16);
        $this->pdf->Cell(0, 10, 'BALANCE SHEET', 0, 1, 'C');
        
        // *** FIX: Add date prominently ***
        $this->pdf->SetFont('dejavusans', 'B', 11);
        $this->pdf->SetTextColor(0, 0, 128); // Dark blue
        $this->pdf->Cell(0, 6, 'As of: ' . $this->formatDateForDisplay($asOfDate), 0, 1, 'C');
        $this->pdf->SetTextColor(0, 0, 0); // Reset to black
        $this->pdf->Ln(5);
        
        // Assets Section
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->SetFillColor(200, 200, 200);
        $this->pdf->Cell(0, 7, 'ASSETS', 1, 1, 'L', true);
        
        $this->pdf->SetFont('dejavusans', '', 9);
        if (!empty($data['assets'])) {
            foreach ($data['assets'] as $item) {
                $this->pdf->Cell(140, 6, '  ' . $item['account_name'], 'LR', 0);
                $this->pdf->Cell(46, 6, $this->formatCurrency($item['balance']), 'R', 1, 'R');
            }
        } else {
            $this->pdf->Cell(0, 6, '  No asset data available', 1, 1, 'L');
        }
        
        $this->pdf->SetFont('dejavusans', 'B', 10);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(140, 7, 'Total Assets', 1, 0, 'L', true);
        $this->pdf->Cell(46, 7, $this->formatCurrency($data['total_assets']), 1, 1, 'R', true);
        $this->pdf->Ln(3);
        
        // Liabilities Section
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->SetFillColor(200, 200, 200);
        $this->pdf->Cell(0, 7, 'LIABILITIES', 1, 1, 'L', true);
        
        $this->pdf->SetFont('dejavusans', '', 9);
        if (!empty($data['liabilities'])) {
            foreach ($data['liabilities'] as $item) {
                $this->pdf->Cell(140, 6, '  ' . $item['account_name'], 'LR', 0);
                $this->pdf->Cell(46, 6, $this->formatCurrency($item['balance']), 'R', 1, 'R');
            }
        } else {
            $this->pdf->Cell(0, 6, '  No liability data available', 1, 1, 'L');
        }
        
        $this->pdf->SetFont('dejavusans', 'B', 10);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(140, 7, 'Total Liabilities', 1, 0, 'L', true);
        $this->pdf->Cell(46, 7, $this->formatCurrency($data['total_liabilities']), 1, 1, 'R', true);
        $this->pdf->Ln(3);
        
        // Equity Section
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->SetFillColor(200, 200, 200);
        $this->pdf->Cell(0, 7, 'EQUITY', 1, 1, 'L', true);
        
        $this->pdf->SetFont('dejavusans', '', 9);
        if (!empty($data['equity'])) {
            foreach ($data['equity'] as $item) {
                $this->pdf->Cell(140, 6, '  ' . $item['account_name'], 'LR', 0);
                $this->pdf->Cell(46, 6, $this->formatCurrency($item['balance']), 'R', 1, 'R');
            }
        } else {
            $this->pdf->Cell(0, 6, '  No equity data available', 1, 1, 'L');
        }
        
        $this->pdf->SetFont('dejavusans', 'B', 10);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(140, 7, 'Total Equity', 1, 0, 'L', true);
        $this->pdf->Cell(46, 7, $this->formatCurrency($data['total_equity']), 1, 1, 'R', true);
        $this->pdf->Ln(3);
        
        // Total Liabilities + Equity
        $this->pdf->SetFont('dejavusans', 'B', 11);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(140, 8, 'TOTAL LIABILITIES + EQUITY', 1, 0, 'L', true);
        $this->pdf->Cell(46, 8, $this->formatCurrency($data['total_liabilities'] + $data['total_equity']), 1, 1, 'R', true);
    }
    
    private function generateCashFlowPDF($data, $startDate, $endDate) {
        // Header
        $this->pdf->SetFont('dejavusans', 'B', 16);
        $this->pdf->Cell(0, 10, 'CASH FLOW STATEMENT', 0, 1, 'C');
        
        // *** FIX: Add date range prominently ***
        $this->pdf->SetFont('dejavusans', 'B', 11);
        $this->pdf->SetTextColor(0, 0, 128); // Dark blue
        $this->pdf->Cell(0, 6, 'Report Period: ' . $this->formatDateForDisplay($startDate) . ' to ' . $this->formatDateForDisplay($endDate), 0, 1, 'C');
        $this->pdf->SetTextColor(0, 0, 0); // Reset to black
        $this->pdf->Ln(5);
        
        // Operating Activities
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->SetFillColor(200, 200, 200);
        $this->pdf->Cell(0, 7, 'OPERATING ACTIVITIES', 1, 1, 'L', true);
        
        $this->pdf->SetFont('dejavusans', '', 9);
        $this->pdf->Cell(140, 6, '  Net Cash from Operating Activities', 'LR', 0);
        $this->pdf->Cell(46, 6, $this->formatCurrency($data['operating']), 'R', 1, 'R');
        
        $this->pdf->SetFont('dejavusans', 'B', 10);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(140, 7, 'Total Operating Cash Flow', 1, 0, 'L', true);
        $this->pdf->Cell(46, 7, $this->formatCurrency($data['operating']), 1, 1, 'R', true);
        $this->pdf->Ln(3);
        
        // Investing Activities
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->SetFillColor(200, 200, 200);
        $this->pdf->Cell(0, 7, 'INVESTING ACTIVITIES', 1, 1, 'L', true);
        
        $this->pdf->SetFont('dejavusans', '', 9);
        $this->pdf->Cell(140, 6, '  Net Cash from Investing Activities', 'LR', 0);
        $this->pdf->Cell(46, 6, $this->formatCurrency($data['investing']), 'R', 1, 'R');
        
        $this->pdf->SetFont('dejavusans', 'B', 10);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(140, 7, 'Total Investing Cash Flow', 1, 0, 'L', true);
        $this->pdf->Cell(46, 7, $this->formatCurrency($data['investing']), 1, 1, 'R', true);
        $this->pdf->Ln(3);
        
        // Financing Activities
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->SetFillColor(200, 200, 200);
        $this->pdf->Cell(0, 7, 'FINANCING ACTIVITIES', 1, 1, 'L', true);
        
        $this->pdf->SetFont('dejavusans', '', 9);
        $this->pdf->Cell(140, 6, '  Net Cash from Financing Activities', 'LR', 0);
        $this->pdf->Cell(46, 6, $this->formatCurrency($data['financing']), 'R', 1, 'R');
        
        $this->pdf->SetFont('dejavusans', 'B', 10);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(140, 7, 'Total Financing Cash Flow', 1, 0, 'L', true);
        $this->pdf->Cell(46, 7, $this->formatCurrency($data['financing']), 1, 1, 'R', true);
        $this->pdf->Ln(3);
        
        // Net Cash Flow
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(140, 8, 'NET INCREASE (DECREASE) IN CASH', 1, 0, 'L', true);
        $this->pdf->Cell(46, 8, $this->formatCurrency($data['net_cash_flow']), 1, 1, 'R', true);
    }
    
    private function generateTrialBalancePDF($data, $asOfDate) {
        // Header
        $this->pdf->SetFont('dejavusans', 'B', 16);
        $this->pdf->Cell(0, 10, 'TRIAL BALANCE', 0, 1, 'C');
        
        // *** FIX: Add date prominently ***
        $this->pdf->SetFont('dejavusans', 'B', 11);
        $this->pdf->SetTextColor(0, 0, 128); // Dark blue
        $this->pdf->Cell(0, 6, 'As of: ' . $this->formatDateForDisplay($asOfDate), 0, 1, 'C');
        $this->pdf->SetTextColor(0, 0, 0); // Reset to black
        $this->pdf->Ln(3);
        
        // Balance status
        $balanceStatus = $data['is_balanced'] ? 'BALANCED' : 'OUT OF BALANCE';
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 7, 'Status: ' . $balanceStatus, 0, 1, 'C');
        $this->pdf->Ln(3);
        
        if (empty($data['accounts'])) {
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->Cell(0, 6, 'No account data available for trial balance.', 1, 1, 'C');
            return;
        }
        
        // Table Header
        $this->pdf->SetFont('dejavusans', 'B', 8);
        $this->pdf->SetFillColor(200, 200, 200);
        $this->pdf->Cell(20, 7, 'Code', 1, 0, 'C', true);
        $this->pdf->Cell(60, 7, 'Account Name', 1, 0, 'C', true);
        $this->pdf->Cell(22, 7, 'Type', 1, 0, 'C', true);
        $this->pdf->Cell(28, 7, 'Debit', 1, 0, 'C', true);
        $this->pdf->Cell(28, 7, 'Credit', 1, 0, 'C', true);
        $this->pdf->Cell(28, 7, 'Balance', 1, 1, 'C', true);
        
        // Group accounts by type
        $accountsByType = [];
        foreach ($data['accounts'] as $account) {
            $type = $account['account_type'];
            if (!isset($accountsByType[$type])) {
                $accountsByType[$type] = [];
            }
            $accountsByType[$type][] = $account;
        }
        
        // Display accounts grouped by type
        $typeOrder = ['Asset', 'Expense', 'Revenue', 'Equity', 'Liability'];
        $this->pdf->SetFont('dejavusans', '', 7);
        
        foreach ($typeOrder as $type) {
            if (!isset($accountsByType[$type])) continue;
            
            // Type header
            $this->pdf->SetFont('dejavusans', 'B', 8);
            $this->pdf->SetFillColor(180, 180, 180);
            $this->pdf->Cell(186, 6, strtoupper($type) . ' ACCOUNTS', 1, 1, 'L', true);
            
            $this->pdf->SetFont('dejavusans', '', 7);
            foreach ($accountsByType[$type] as $account) {
                $this->pdf->Cell(20, 5, $account['account_code'], 1, 0, 'L');
                $this->pdf->Cell(60, 5, substr($account['account_name'], 0, 35), 1, 0, 'L');
                $this->pdf->Cell(22, 5, $account['account_type'], 1, 0, 'L');
                $this->pdf->Cell(28, 5, $account['debit'] > 0 ? $this->formatCurrency($account['debit']) : '-', 1, 0, 'R');
                $this->pdf->Cell(28, 5, $account['credit'] > 0 ? $this->formatCurrency($account['credit']) : '-', 1, 0, 'R');
                $this->pdf->Cell(28, 5, $this->formatCurrency(abs($account['balance'])), 1, 1, 'R');
            }
        }
        
        // Totals
        $this->pdf->SetFont('dejavusans', 'B', 8);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(102, 7, 'TOTALS', 1, 0, 'R', true);
        $this->pdf->Cell(28, 7, $this->formatCurrency($data['total_debits']), 1, 0, 'R', true);
        $this->pdf->Cell(28, 7, $this->formatCurrency($data['total_credits']), 1, 0, 'R', true);
        $this->pdf->Cell(28, 7, '-', 1, 1, 'R', true);
        
        // Balance warning if out of balance
        if (!$data['is_balanced']) {
            $this->pdf->Ln(3);
            $this->pdf->SetFont('dejavusans', 'B', 9);
            $this->pdf->SetFillColor(255, 200, 200);
            $difference = abs($data['total_debits'] - $data['total_credits']);
            $this->pdf->MultiCell(0, 5, 
                'Warning: Trial Balance is out of balance by ' . $this->formatCurrency($difference) . '. ' .
                'This may indicate data entry errors or missing transactions.',
                1, 'L', true);
        }
    }
    
    private function generateBudgetPerformancePDF($data, $startDate, $endDate) {
        // Header
        $this->pdf->SetFont('dejavusans', 'B', 16);
        $this->pdf->Cell(0, 10, 'BUDGET PERFORMANCE REPORT', 0, 1, 'C');
        
        // *** FIX: Add date range prominently ***
        $this->pdf->SetFont('dejavusans', 'B', 11);
        $this->pdf->SetTextColor(0, 0, 128); // Dark blue
        $this->pdf->Cell(0, 6, 'Report Period: ' . $this->formatDateForDisplay($startDate) . ' to ' . $this->formatDateForDisplay($endDate), 0, 1, 'C');
        $this->pdf->SetTextColor(0, 0, 0); // Reset to black
        $this->pdf->Ln(3);
        
        if (empty($data)) {
            $this->pdf->SetFont('dejavusans', '', 10);
            $this->pdf->MultiCell(0, 6, 
                'No budget data available for the selected period.' . "\n" .
                'Possible reasons: No budget table, no entries for date range, or different data format.',
                1, 'C');
            return;
        }
        
        // Calculate summary metrics
        $totalAllocated = array_sum(array_column($data, 'amount_allocated'));
        $totalUsed = array_sum(array_column($data, 'amount_used'));
        $overallUtilization = $totalAllocated > 0 ? ($totalUsed / $totalAllocated) * 100 : 0;
        
        // KPI Summary (4 boxes in a row)
        $this->pdf->SetFont('dejavusans', 'B', 8);
        $this->pdf->SetFillColor(240, 240, 240);
        
        $boxWidth = 46.5;
        $this->pdf->Cell($boxWidth, 5, 'Total Allocated', 1, 0, 'C', true);
        $this->pdf->Cell($boxWidth, 5, 'Total Used', 1, 0, 'C', true);
        $this->pdf->Cell($boxWidth, 5, 'Utilization %', 1, 0, 'C', true);
        $this->pdf->Cell($boxWidth, 5, 'Remaining', 1, 1, 'C', true);
        
        $this->pdf->SetFont('dejavusans', 'B', 10);
        $this->pdf->Cell($boxWidth, 6, $this->formatCurrency($totalAllocated), 1, 0, 'C');
        $this->pdf->Cell($boxWidth, 6, $this->formatCurrency($totalUsed), 1, 0, 'C');
        $this->pdf->Cell($boxWidth, 6, number_format($overallUtilization, 1) . '%', 1, 0, 'C');
        $this->pdf->Cell($boxWidth, 6, $this->formatCurrency($totalAllocated - $totalUsed), 1, 1, 'C');
        $this->pdf->Ln(3);
        
        // Main budget table header
        $this->pdf->SetFont('dejavusans', 'B', 7);
        $this->pdf->SetFillColor(200, 200, 200);
        $this->pdf->Cell(18, 6, 'Dept', 1, 0, 'C', true);
        $this->pdf->Cell(35, 6, 'Cost Center', 1, 0, 'C', true);
        $this->pdf->Cell(28, 6, 'Allocated', 1, 0, 'C', true);
        $this->pdf->Cell(28, 6, 'Used', 1, 0, 'C', true);
        $this->pdf->Cell(25, 6, 'Remain', 1, 0, 'C', true);
        $this->pdf->Cell(18, 6, 'Use%', 1, 0, 'C', true);
        $this->pdf->Cell(24, 6, 'Variance', 1, 0, 'C', true);
        $this->pdf->Cell(16, 6, 'Status', 1, 1, 'C', true);
        
        // Table data
        $this->pdf->SetFont('dejavusans', '', 7);
        foreach ($data as $row) {
            // Set row background based on utilization
            if ($row['utilization_percentage'] > 100) {
                $this->pdf->SetFillColor(255, 230, 230);
            } elseif ($row['utilization_percentage'] > 80) {
                $this->pdf->SetFillColor(255, 250, 230);
            } else {
                $this->pdf->SetFillColor(230, 245, 230);
            }
            
            $this->pdf->Cell(18, 5, substr($row['department'], 0, 8), 1, 0, 'L', true);
            $this->pdf->Cell(35, 5, substr($row['cost_center'], 0, 18), 1, 0, 'L', true);
            $this->pdf->Cell(28, 5, $this->formatCurrency($row['amount_allocated']), 1, 0, 'R', true);
            $this->pdf->Cell(28, 5, $this->formatCurrency($row['amount_used']), 1, 0, 'R', true);
            $this->pdf->Cell(25, 5, $this->formatCurrency($row['remaining']), 1, 0, 'R', true);
            $this->pdf->Cell(18, 5, number_format($row['utilization_percentage'], 1) . '%', 1, 0, 'R', true);
            $this->pdf->Cell(24, 5, $this->formatCurrency($row['variance']), 1, 0, 'R', true);
            $this->pdf->Cell(16, 5, $row['approval_status'], 1, 1, 'C', true);
        }
        
        // Summary totals
        $this->pdf->SetFont('dejavusans', 'B', 7);
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(53, 6, 'TOTALS', 1, 0, 'R', true);
        $this->pdf->Cell(28, 6, $this->formatCurrency($totalAllocated), 1, 0, 'R', true);
        $this->pdf->Cell(28, 6, $this->formatCurrency($totalUsed), 1, 0, 'R', true);
        $this->pdf->Cell(25, 6, $this->formatCurrency($totalAllocated - $totalUsed), 1, 0, 'R', true);
        $this->pdf->Cell(18, 6, number_format($overallUtilization, 1) . '%', 1, 0, 'R', true);
        $this->pdf->Cell(40, 6, '', 1, 1, 'C', true);
        
        // Analysis section
        $this->pdf->Ln(3);
        $overBudgetCount = count(array_filter($data, function($item) { return $item['utilization_percentage'] > 100; }));
        $onTrackCount = count(array_filter($data, function($item) { return $item['utilization_percentage'] <= 100 && $item['utilization_percentage'] > 70; }));
        $underBudgetCount = count(array_filter($data, function($item) { return $item['utilization_percentage'] <= 70; }));
        
        $this->pdf->SetFont('dejavusans', 'B', 9);
        $this->pdf->Cell(0, 5, 'Analysis', 0, 1);
        $this->pdf->SetFont('dejavusans', '', 8);
        $this->pdf->MultiCell(0, 4,
            'Over Budget: ' . $overBudgetCount . ' | At Risk: ' . $onTrackCount . ' | Under Budget: ' . $underBudgetCount . "\n" .
            ($overallUtilization > 100 ? 'Alert: Overall budget exceeded - Review spending priorities' :
             ($overallUtilization > 90 ? 'Warning: High utilization - Monitor closely' : 'Status: Within acceptable ranges')),
            1, 'L');
    }
    
    // ==================== HELPER METHODS ====================
    
    private function outputPDF($reportType, $startDate, $endDate) {
        $filename = $this->getReportFilename($reportType, $startDate, $endDate);
        
        if ($this->useTCPDF) {
            ob_end_clean(); // Remove any accidental output before sending PDF
            $this->pdf->Output($filename, 'D'); // Send PDF as download
            exit; // Stop script to prevent extra output
        }
    }
    
    private function getReportTitle($reportType) {
        $titles = [
            'income_statement' => 'Income Statement',
            'balance_sheet' => 'Balance Sheet',
            'cash_flow' => 'Cash Flow Statement',
            'trial_balance' => 'Trial Balance',
            'budget_performance' => 'Budget Performance Report'
        ];
        return $titles[$reportType] ?? 'Financial Report';
    }
    
    private function getReportFilename($reportType, $startDate, $endDate) {
        $title = str_replace(' ', '_', $this->getReportTitle($reportType));
        return $title . '_' . $startDate . '_to_' . $endDate . '.pdf';
    }
    
    private function formatCurrency($amount) {
        // Using peso symbol
        return '₱' . number_format(floatval($amount), 2);
    }
    
    private function formatDateForDisplay($date) {
        return date('F j, Y', strtotime($date));
    }
}

// Main execution
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action !== 'exportPDF') {
        throw new Exception('Invalid action');
    }
    
    $reportType = $_POST['report_type'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    
    if (empty($reportType) || empty($startDate) || empty($endDate)) {
        throw new Exception('Missing required parameters');
    }
    
    // Additional parameters
    $additionalParams = [
        'budget_period' => $_POST['budget_period'] ?? null,
        'department_filter' => $_POST['department_filter'] ?? null
    ];
    
    // Get database connection
    $conn = getDBConnection();
    
    // Create exporter and generate PDF
    $exporter = new FinancialPDFExporter($conn);
    $exporter->exportReport($reportType, $startDate, $endDate, $additionalParams);
    
} catch (Exception $e) {
    // Return error as JSON
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
