<?php
// financial_reporting.php - Main reporting page (updated to work with existing backend)
include 'db.php';

// Function to get financial summary data using your existing DB structure
function getFinancialSummary($conn, $start_date = null, $end_date = null) {
    // Set default dates if not provided
    if (!$start_date) $start_date = date('Y-m-01'); // First day of current month
    if (!$end_date) $end_date = date('Y-m-t'); // Last day of current month
    
    try {
        // Get total revenue from collections table
        $revenue_query = "SELECT COALESCE(SUM(amount_paid), 0) as total_revenue 
                         FROM collections 
                         WHERE billing_date BETWEEN ? AND ? 
                         AND payment_status IN ('Paid', 'Partial')";
        $revenue_stmt = $conn->prepare($revenue_query);
        $revenue_stmt->bind_param('ss', $start_date, $end_date);
        $revenue_stmt->execute();
        $revenue_result = $revenue_stmt->get_result();
        $total_revenue = $revenue_result->fetch_assoc()['total_revenue'] ?? 0;
        
        // Get total expenses from expenses table
        $expense_query = "SELECT COALESCE(SUM(amount + tax_amount), 0) as total_expenses 
                         FROM expenses 
                         WHERE expense_date BETWEEN ? AND ? 
                         AND status = 'Approved'";
        $expense_stmt = $conn->prepare($expense_query);
        $expense_stmt->bind_param('ss', $start_date, $end_date);
        $expense_stmt->execute();
        $expense_result = $expense_stmt->get_result();
        $total_expenses = $expense_result->fetch_assoc()['total_expenses'] ?? 0;
        
        // Get total assets from chart of accounts via journal entries
        $assets_query = "SELECT COALESCE(SUM(
                            CASE WHEN coa.account_type = 'Asset' 
                            THEN (COALESCE(debit_sum.total, 0) - COALESCE(credit_sum.total, 0))
                            ELSE 0 END
                         ), 0) as total_assets
                         FROM chart_of_accounts coa
                         LEFT JOIN (
                            SELECT account_code, SUM(debit) as total 
                            FROM journal_entries WHERE status = 'Posted' 
                            GROUP BY account_code
                         ) debit_sum ON coa.account_code = debit_sum.account_code
                         LEFT JOIN (
                            SELECT account_code, SUM(credit) as total 
                            FROM journal_entries WHERE status = 'Posted' 
                            GROUP BY account_code
                         ) credit_sum ON coa.account_code = credit_sum.account_code
                         WHERE coa.account_type = 'Asset' AND coa.status = 'Active'";
        $assets_result = $conn->query($assets_query);
        $total_assets = $assets_result->fetch_assoc()['total_assets'] ?? 0;
        
        return [
            'total_revenue' => $total_revenue,
            'total_expenses' => $total_expenses,
            'net_income' => $total_revenue - $total_expenses,
            'total_assets' => $total_assets
        ];
        
    } catch (Exception $e) {
        return [
            'total_revenue' => 0,
            'total_expenses' => 0,
            'net_income' => 0,
            'total_assets' => 0,
            'error' => $e->getMessage()
        ];
    }
}

// Get financial summary for current period using the first connection
$financial_summary = getFinancialSummary($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Financial Reporting</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css" />
    <style>
        .report-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .financial-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .loading-spinner {
            display: none;
        }
        .currency {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        .filter-group {
            max-width: 250px;
        }
        .date-input, .filter-account {
            border-radius: 6px;
        }
    </style>
</head>
<body>

<?php include 'sidebar_navbar.php'; ?>

<div class="main-content">
    <div class="container-fluid mt-4 px-4">
        <h2 class="fw-bold mb-4">Financial Reporting</h2>

        <!-- Financial Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card financial-summary">
                    <div class="card-body text-center">
                        <h5>Total Revenue</h5>
                        <h3 class="currency" id="totalRevenue"><?php echo formatCurrency($financial_summary['total_revenue']); ?></h3>
                        <small>Current Period</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h5>Total Expenses</h5>
                        <h3 class="currency" id="totalExpenses"><?php echo formatCurrency($financial_summary['total_expenses']); ?></h3>
                        <small>Current Period</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5>Net Income</h5>
                        <h3 class="currency" id="netIncome"><?php echo formatCurrency($financial_summary['net_income']); ?></h3>
                        <small>Current Period</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5>Total Assets</h5>
                        <h3 class="currency" id="totalAssets"><?php echo formatCurrency($financial_summary['total_assets']); ?></h3>
                        <small>As of Today</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-3">
            <div class="d-flex flex-column gap-2 filter-group">
                <input type="date" class="form-control date-input" id="startDate" value="<?php echo date('Y-m-01'); ?>" />
                <input type="date" class="form-control date-input" id="endDate" value="<?php echo date('Y-m-t'); ?>" />
                <select class="form-select filter-account" id="reportType">
                    <option value="" selected disabled>Select Report Type</option>
                    <option value="income_statement">Income Statement</option>
                    <option value="balance_sheet">Balance Sheet</option>
                    <option value="cash_flow">Cash Flow Statement</option>
                    <option value="trial_balance">Trial Balance</option>
                    <option value="budget_performance">Budget Performance</option>
                </select>
                <button class="btn btn-primary btn-sm" id="generateReport">Generate Report</button>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="d-flex justify-content-between mb-3">
            <div></div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    Export Report
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" id="exportPDF">📄 Export PDF</a></li>
                    <li><a class="dropdown-item" href="#" id="exportExcel">📊 Export Excel</a></li>
                </ul>
            </div>
        </div>

        <!-- Report Content -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 id="reportTitle">Financial Report</h5>
                        <div class="loading-spinner">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="reportContent">
                            <p class="text-muted text-center py-5">
                                <i class="fas fa-chart-bar fa-3x mb-3 d-block"></i>
                                Select a report type and date range, then click "Generate Report" to view your financial data.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Report Buttons -->
        <div class="mt-4 d-flex gap-3 justify-content-center flex-wrap">
            <button class="btn btn-outline-primary report-card" data-report="income_statement">
                📈 Income Statement
            </button>
            <button class="btn btn-outline-success report-card" data-report="balance_sheet">
                📊 Balance Sheet
            </button>
            <button class="btn btn-outline-info report-card" data-report="cash_flow">
                💵 Cash Flow
            </button>
            <button class="btn btn-outline-warning report-card" data-report="trial_balance">
                📋 Trial Balance
            </button>
            <button class="btn btn-outline-secondary report-card" data-report="budget_performance">
                📊 Budget Performance
            </button>
        </div>

    </div>
</div>

<!-- Include your existing modals (now cleaned of sample data) -->
<?php include 'financial_reporting_modals.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Use your existing reporting_script.js -->
<script src="reporting_script.js"></script>
</body>
</html>