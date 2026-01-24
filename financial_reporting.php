<?php
require_once 'db.php';

// CRITICAL: Check authentication and session timeout BEFORE any output
requireLogin();

// Enhanced getFinancialSummary function with proper date filtering
function getFinancialSummary($conn, $start_date = null, $end_date = null) {
    // Set default dates if not provided
    if (!$start_date) $start_date = date('Y-m-01'); // First day of current month
    if (!$end_date) $end_date = date('Y-m-t'); // Last day of current month
    
    // Initialize default return array
    $summary = [
        'total_revenue' => 0.00,
        'total_expenses' => 0.00,
        'net_income' => 0.00,
        'total_assets' => 0.00,
        'period_start' => $start_date,
        'period_end' => $end_date,
        'error' => null
    ];
    
    try {
        // Check if connection exists and is valid
        if (!$conn || $conn->connect_error) {
            $summary['error'] = "Database connection failed";
            return $summary;
        }
        
        // 1. Get total revenue from collections table - FILTERED BY DATE RANGE
        $revenue_query = "SELECT COALESCE(SUM(amount_paid), 0) as total_revenue 
                         FROM collections 
                         WHERE DATE(billing_date) BETWEEN ? AND ? 
                         AND payment_status IN ('Paid', 'Partial')
                         AND amount_paid IS NOT NULL 
                         AND amount_paid > 0";
        
        $revenue_stmt = $conn->prepare($revenue_query);
        if ($revenue_stmt) {
            $revenue_stmt->bind_param('ss', $start_date, $end_date);
            if ($revenue_stmt->execute()) {
                $revenue_result = $revenue_stmt->get_result();
                if ($revenue_result) {
                    $revenue_row = $revenue_result->fetch_assoc();
                    $summary['total_revenue'] = floatval($revenue_row['total_revenue'] ?? 0);
                }
            }
            $revenue_stmt->close();
        }
        
        // 2. Get total expenses from expenses table - FILTERED BY DATE RANGE
        $expense_query = "SELECT COALESCE(SUM(amount + COALESCE(tax_amount, 0)), 0) as total_expenses 
                         FROM expenses 
                         WHERE DATE(expense_date) BETWEEN ? AND ? 
                         AND status = 'Approved'
                         AND amount IS NOT NULL 
                         AND amount > 0";
        
        $expense_stmt = $conn->prepare($expense_query);
        if ($expense_stmt) {
            $expense_stmt->bind_param('ss', $start_date, $end_date);
            if ($expense_stmt->execute()) {
                $expense_result = $expense_stmt->get_result();
                if ($expense_result) {
                    $expense_row = $expense_result->fetch_assoc();
                    $summary['total_expenses'] = floatval($expense_row['total_expenses'] ?? 0);
                }
            }
            $expense_stmt->close();
        }
        
        // 3. Calculate assets - Use END DATE for assets calculation (as of end date)
        $assets_from_journal = 0;
        $assets_query = "SELECT COALESCE(SUM(
                            CASE 
                                WHEN coa.account_type = 'Asset' THEN 
                                    (COALESCE(debits.total_debit, 0) - COALESCE(credits.total_credit, 0))
                                ELSE 0 
                            END
                         ), 0) as total_assets
                         FROM chart_of_accounts coa
                         LEFT JOIN (
                            SELECT account_code, SUM(debit) as total_debit 
                            FROM journal_entries 
                            WHERE status = 'Posted' 
                            AND DATE(date) <= ?
                            GROUP BY account_code
                         ) debits ON coa.account_code = debits.account_code
                         LEFT JOIN (
                            SELECT account_code, SUM(credit) as total_credit 
                            FROM journal_entries 
                            WHERE status = 'Posted' 
                            AND DATE(date) <= ?
                            GROUP BY account_code
                         ) credits ON coa.account_code = credits.account_code
                         WHERE coa.account_type = 'Asset' 
                         AND coa.status = 'Active'";
        
        $assets_stmt = $conn->prepare($assets_query);
        if ($assets_stmt) {
            $assets_stmt->bind_param('ss', $end_date, $end_date);
            if ($assets_stmt->execute()) {
                $assets_result = $assets_stmt->get_result();
                if ($assets_result) {
                    $assets_row = $assets_result->fetch_assoc();
                    $assets_from_journal = floatval($assets_row['total_assets'] ?? 0);
                }
            }
            $assets_stmt->close();
        }
        
        // If no assets from journal, calculate from collections
        $assets_from_collections = 0;
        if ($assets_from_journal == 0) {
            $collections_assets_query = "SELECT 
                COALESCE(SUM(CASE WHEN payment_status = 'Paid' THEN amount_paid ELSE 0 END), 0) as cash_received,
                COALESCE(SUM(CASE WHEN payment_status IN ('Pending', 'Partial') THEN (amount_due - COALESCE(amount_paid, 0)) ELSE 0 END), 0) as receivables
                FROM collections 
                WHERE DATE(billing_date) <= ?";
            
            $collections_stmt = $conn->prepare($collections_assets_query);
            if ($collections_stmt) {
                $collections_stmt->bind_param('s', $end_date);
                if ($collections_stmt->execute()) {
                    $collections_result = $collections_stmt->get_result();
                    if ($collections_result) {
                        $collections_row = $collections_result->fetch_assoc();
                        $cash_received = floatval($collections_row['cash_received'] ?? 0);
                        $receivables = floatval($collections_row['receivables'] ?? 0);
                        $assets_from_collections = $cash_received + $receivables;
                    }
                }
                $collections_stmt->close();
            }
        }
        
        // Use the best available asset calculation
        $summary['total_assets'] = max($assets_from_journal, $assets_from_collections, $summary['total_revenue']);
        
        // Calculate net income for the specific period
        $summary['net_income'] = $summary['total_revenue'] - $summary['total_expenses'];
        
    } catch (Exception $e) {
        error_log("Financial Summary Error: " . $e->getMessage());
        $summary['error'] = $e->getMessage();
    }
    
    return $summary;
}

// Helper function to get revenue breakdown by client for the period
function getRevenueBreakdown($conn, $start_date, $end_date) {
    $breakdown = [];
    
    $query = "SELECT 
                client_name,
                COUNT(*) as transaction_count,
                SUM(amount_paid) as total_amount,
                AVG(amount_paid) as average_amount
              FROM collections 
              WHERE DATE(billing_date) BETWEEN ? AND ? 
              AND payment_status IN ('Paid', 'Partial')
              AND amount_paid > 0
              GROUP BY client_name
              ORDER BY total_amount DESC";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('ss', $start_date, $end_date);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $breakdown[] = [
                    'client_name' => $row['client_name'],
                    'transaction_count' => intval($row['transaction_count']),
                    'total_amount' => floatval($row['total_amount']),
                    'average_amount' => floatval($row['average_amount'])
                ];
            }
        }
        $stmt->close();
    }
    
    return $breakdown;
}

// Helper function to get expense breakdown by category for the period
function getExpenseBreakdown($conn, $start_date, $end_date) {
    $breakdown = [];
    
    $query = "SELECT 
                category,
                COUNT(*) as transaction_count,
                SUM(amount + COALESCE(tax_amount, 0)) as total_amount,
                AVG(amount + COALESCE(tax_amount, 0)) as average_amount
              FROM expenses 
              WHERE DATE(expense_date) BETWEEN ? AND ? 
              AND status = 'Approved'
              AND amount > 0
              GROUP BY category
              ORDER BY total_amount DESC";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('ss', $start_date, $end_date);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $breakdown[] = [
                    'category' => $row['category'],
                    'transaction_count' => intval($row['transaction_count']),
                    'total_amount' => floatval($row['total_amount']),
                    'average_amount' => floatval($row['average_amount'])
                ];
            }
        }
        $stmt->close();
    }
    
    return $breakdown;
}

// Initialize with current month as default if no dates are provided
$default_start = date('Y-m-01');
$default_end = date('Y-m-t');

// Check if this is an AJAX request for filtered data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'getFilteredFinancialData') {
    header('Content-Type: application/json');
    
    $start_date = $_POST['start_date'] ?? $default_start;
    $end_date = $_POST['end_date'] ?? $default_end;
    
    try {
        $conn = getDBConnection();
        
        // Get filtered financial summary using enhanced function
        $financial_summary = getFinancialSummary($conn, $start_date, $end_date);
        
        // Get additional details
        $revenue_breakdown = getRevenueBreakdown($conn, $start_date, $end_date);
        $expense_breakdown = getExpenseBreakdown($conn, $start_date, $end_date);
        
        echo json_encode([
            'success' => true,
            'summary' => $financial_summary,
            'details' => [
                'revenue_breakdown' => $revenue_breakdown,
                'expense_breakdown' => $expense_breakdown
            ],
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
    exit;
}

// For regular page load, get initial financial summary
try {
    $conn = getDBConnection();
    $financial_summary = getFinancialSummary($conn, $default_start, $default_end);
} catch (Exception $e) {
    error_log("Financial Summary Error: " . $e->getMessage());
    $financial_summary = [
        'total_revenue' => 0,
        'total_expenses' => 0,
        'net_income' => 0,
        'total_assets' => 0,
        'error' => $e->getMessage()
    ];
}

// Utility function for formatting currency
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return '₱' . number_format(floatval($amount), 2);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Financial Reporting</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="financial-compact-styles.css" />
</head>
<body>

<?php include 'sidebar_navbar.php'; ?>

<div class="main-content">
    <div class="container-fluid mt-2 px-2">
        <h2 class="fw-bold mb-2">Financial Reporting</h2>

        <?php if (isset($financial_summary['error']) && $financial_summary['error']): ?>
        <div class="alert alert-warning py-2 px-2 mb-2" role="alert">
            <h6 class="mb-1">⚠️ Data Issue</h6>
            <p class="mb-0 small"><strong>Error:</strong> <?php echo htmlspecialchars($financial_summary['error']); ?></p>
        </div>
        <?php endif; ?>

        <!-- ULTRA COMPACT Filter & Summary Section -->
        <div class="row mb-1 g-2">
            <!-- Compact Filter Column -->
            <div class="col-md-3">
                <div class="filter-group">
                    <h6 class="mb-2">📅 Date & Report</h6>
                    <label class="form-label">Start:</label>
                    <input type="date" class="form-control form-control-sm" id="startDate" value="<?php echo $default_start; ?>" />
                    
                    <label class="form-label mt-1">End:</label>
                    <input type="date" class="form-control form-control-sm" id="endDate" value="<?php echo $default_end; ?>" />
                    
                    <label class="form-label mt-1">Type:</label>
                    <select class="form-select form-select-sm" id="reportType">
                        <option value="" selected disabled>Select Report</option>
                        <option value="income_statement">Income Statement</option>
                        <option value="balance_sheet">Balance Sheet</option>
                        <option value="cash_flow">Cash Flow</option>
                        <option value="budget_performance">Budget Performance</option>
                    </select>
                    
                    <button class="btn btn-primary btn-sm w-100 mt-2" id="generateReport">
                        Generate Report
                    </button>
                    
                    <!-- Quick Date Presets -->
                    <div class="date-presets mt-2">
                        <small class="text-muted d-block mb-1">Quick:</small>
                        <div class="d-flex flex-wrap gap-1">
                            <button class="btn btn-outline-secondary btn-xs" onclick="window.financialReporting.setDateRangePreset('today')">Today</button>
                            <button class="btn btn-outline-secondary btn-xs" onclick="window.financialReporting.setDateRangePreset('this_week')">Week</button>
                            <button class="btn btn-outline-secondary btn-xs" onclick="window.financialReporting.setDateRangePreset('this_month')">Month</button>
                            <button class="btn btn-outline-secondary btn-xs" onclick="window.financialReporting.setDateRangePreset('this_year')">Year</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Compact Summary Cards Column -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted">Summary</small>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-2" id="toggleAllValues" title="Toggle values" style="font-size: 0.7rem;">
                        <span id="toggleIcon">🙈</span> <span id="toggleText">Show</span>
                    </button>
                </div>
                <div class="row g-1 mb-1">
                    <div class="col-6 col-lg-3">
    <div class="card bg-success text-white" id="revenueCard">
                            <button class="btn btn-sm toggle-visibility-btn text-white" onclick="toggleCardVisibility('totalRevenue')">
                                <span class="toggle-icon">👁️</span>
                            </button>
                            <div class="card-body text-center py-1">
                                <h6 class="card-title mb-0" style="font-size: 0.7rem;">Revenue</h6>
                                <h4 class="card-value hidden-value my-0" id="totalRevenue" style="font-size: 1.1rem;">
                                    <span class="actual-value"><?php echo formatCurrency($financial_summary['total_revenue']); ?></span>
                                    <span class="mask-value">* * * *</span>
                                </h4>
                                <small class="period-text" style="font-size: 0.65rem;">Period</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card bg-danger text-white" id="expenseCard">
                            <button class="btn btn-sm toggle-visibility-btn text-white" onclick="toggleCardVisibility('totalExpenses')">
                                <span class="toggle-icon">👁️</span>
                            </button>
                            <div class="card-body text-center py-1">
                                <h6 class="card-title mb-0" style="font-size: 0.7rem;">Expenses</h6>
                                <h4 class="card-value hidden-value my-0" id="totalExpenses" style="font-size: 1.1rem;">
                                    <span class="actual-value"><?php echo formatCurrency($financial_summary['total_expenses']); ?></span>
                                    <span class="mask-value">* * * *</span>
                                </h4>
                                <small class="period-text" style="font-size: 0.65rem;">Period</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card <?php echo $financial_summary['net_income'] >= 0 ? 'bg-success' : 'bg-warning'; ?> text-white" id="netIncomeCard">
                            <button class="btn btn-sm toggle-visibility-btn text-white" onclick="toggleCardVisibility('netIncome')">
                                <span class="toggle-icon">👁️</span>
                            </button>
                            <div class="card-body text-center py-1">
                                <h6 class="card-title mb-0" style="font-size: 0.7rem;">Net Income</h6>
                                <h4 class="card-value hidden-value my-0" id="netIncome" style="font-size: 1.1rem;">
                                    <span class="actual-value"><?php echo formatCurrency($financial_summary['net_income']); ?></span>
                                    <span class="mask-value">* * * *</span>
                                </h4>
                                <small class="period-text" style="font-size: 0.65rem;">Period</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card bg-info text-white" id="assetsCard">
                            <button class="btn btn-sm toggle-visibility-btn text-white" onclick="toggleCardVisibility('totalAssets')">
                                <span class="toggle-icon">👁️</span>
                            </button>
                            <div class="card-body text-center py-1">
                                <h6 class="card-title mb-0" style="font-size: 0.7rem;">Assets</h6>
                                <h4 class="card-value hidden-value my-0" id="totalAssets" style="font-size: 1.1rem;">
                                    <span class="actual-value"><?php echo formatCurrency($financial_summary['total_assets']); ?></span>
                                    <span class="mask-value">* * * *</span>
                                </h4>
                                <small class="period-text" style="font-size: 0.65rem;">Today</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Report & Export Buttons on Same Line -->
                <div class="d-flex gap-1 justify-content-between flex-wrap align-items-center">
                    <!-- Report Type Buttons -->
                    <div class="d-flex gap-1 flex-wrap">
                        <button class="btn btn-outline-primary btn-sm report-card py-1 px-2" data-report="income_statement" style="font-size: 0.75rem;">📈 Income</button>
                        <button class="btn btn-outline-success btn-sm report-card py-1 px-2" data-report="balance_sheet" style="font-size: 0.75rem;">📊 Balance</button>
                        <button class="btn btn-outline-info btn-sm report-card py-1 px-2" data-report="cash_flow" style="font-size: 0.75rem;">💵 Cash</button>
                        <button class="btn btn-outline-secondary btn-sm report-card py-1 px-2" data-report="budget_performance" style="font-size: 0.75rem;">📊 Budget</button>
                    </div>
                    
                    <!-- Export Buttons -->
                    <div class="d-flex gap-1">
                        <button class="btn btn-success btn-sm py-1 px-2" id="exportPDF" style="font-size: 0.75rem;">📄 PDF</button>
                        <div class="dropdown">
                            <button class="btn btn-info btn-sm dropdown-toggle py-1 px-2" data-bs-toggle="dropdown" style="font-size: 0.75rem;">🚀</button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="exportFinancialReport('income_statement')">📈 Income</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportFinancialReport('balance_sheet')">📊 Balance</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportFinancialReport('cash_flow')">💵 Cash</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportFinancialReport('budget_performance')">📊 Budget</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="exportAllFinancialReports()">📑 All</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Ultra Compact Report Content - Directly Below Buttons -->
                <div class="card shadow-sm mt-1">
                    <div class="card-header d-flex justify-content-between align-items-center py-1">
                        <h5 class="mb-0" id="reportTitle" style="font-size: 0.85rem;">Financial Report</h5>
                        <div class="loading-spinner">
                            <div class="spinner-border spinner-border-sm" role="status" style="width: 1rem; height: 1rem;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body py-1">
                        <div id="reportContent">
                            <p class="text-muted text-center py-2 mb-0">
                                <i class="fas fa-chart-bar mb-1 d-block" style="font-size: 1.5rem;"></i>
                                <span style="font-size: 0.85rem;">Select date range and report type, then click "Generate Report"</span>
                                <br><small style="font-size: 0.7rem;">💡 Change dates to filter data</small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Include modals -->
<?php include 'financial_reporting_modals.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="reporting_script.js"></script>
<script src="export.js"></script>
<script src="financial-reporting-compact.js"></script>

<script>
// IMPORTANT: Define toggle function FIRST before anything else
function toggleCardVisibility(elementId) {
    const valueElement = document.getElementById(elementId);
    if (!valueElement) {
        console.error('Element not found:', elementId);
        return;
    }
    
    const card = valueElement.closest('.card');
    const toggleIcon = card ? card.querySelector('.toggle-icon') : null;
    const toggleBtn = card ? card.querySelector('.toggle-visibility-btn') : null;
    
    console.log('Before toggle:', elementId, 'has hidden-value:', valueElement.classList.contains('hidden-value'));
    
    // Simple toggle - just add/remove the hidden-value class
    if (valueElement.classList.contains('hidden-value')) {
        // Show the value
        console.log('Showing value for', elementId);
        valueElement.classList.remove('hidden-value');
        if (toggleIcon) toggleIcon.textContent = '🙈';
        if (toggleBtn) toggleBtn.setAttribute('title', 'Click to hide value');
    } else {
        // Hide the value
        console.log('Hiding value for', elementId);
        valueElement.classList.add('hidden-value');
        if (toggleIcon) toggleIcon.textContent = '👁️';
        if (toggleBtn) toggleBtn.setAttribute('title', 'Click to show value');
    }
    
    console.log('After toggle:', elementId, 'has hidden-value:', valueElement.classList.contains('hidden-value'));
    console.log('Classes:', valueElement.className);
}

// Enhanced Financial Reporting JavaScript with Date Filtering and Export - COMPLETE IMPLEMENTATION
class FinancialReportingJS {
    constructor() {
        this.currentReportData = null;
        this.currentFinancialSummary = null;
        this.valuesVisible = true; // Track visibility state
        this.initEventListeners();
        this.initDateFilterListeners();
        this.initVisibilityToggle();
    }
    
    initEventListeners() {
        // Generate Report button
        document.getElementById('generateReport').addEventListener('click', () => {
            this.generateReport();
        });
        
        // Quick report buttons
        document.querySelectorAll('.report-card').forEach(button => {
            button.addEventListener('click', (e) => {
                const reportType = e.target.getAttribute('data-report');
                document.getElementById('reportType').value = reportType;
                this.generateReport();
            });
        });
        
        // Export buttons
        document.getElementById('exportPDF').addEventListener('click', () => {
            this.exportReport('pdf');
        });
    }
    
    initDateFilterListeners() {
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        
        if (startDateInput && endDateInput) {
            // Add event listeners for real-time filtering
            startDateInput.addEventListener('change', () => {
                this.updateFinancialSummary();
            });
            
            endDateInput.addEventListener('change', () => {
                this.updateFinancialSummary();
            });
            
            // Add debounced input listener for better performance
            let dateFilterTimeout;
            const debouncedUpdate = () => {
                clearTimeout(dateFilterTimeout);
                dateFilterTimeout = setTimeout(() => {
                    this.updateFinancialSummary();
                }, 500);
            };
            
            startDateInput.addEventListener('input', debouncedUpdate);
            endDateInput.addEventListener('input', debouncedUpdate);
        }
        
        // Add quick date range buttons
        this.addDateRangePresetButtons();
        
        // Auto-update when page loads
        setTimeout(() => {
            this.updateFinancialSummary();
        }, 1000);
    }
    
    addDateRangePresetButtons() {
        const filterGroup = document.querySelector('.filter-group');
        if (filterGroup) {
            const presetButtonsHTML = `
                <div class="date-presets mt-3">
                    <small class="text-muted d-block mb-2">Quick Ranges:</small>
                    <div class="d-flex flex-wrap gap-1">
                        <button class="btn btn-outline-secondary btn-xs" onclick="window.financialReporting.setDateRangePreset('today')">Today</button>
                        <button class="btn btn-outline-secondary btn-xs" onclick="window.financialReporting.setDateRangePreset('this_week')">This Week</button>
                        <button class="btn btn-outline-secondary btn-xs" onclick="window.financialReporting.setDateRangePreset('this_month')">This Month</button>
                        <button class="btn btn-outline-secondary btn-xs" onclick="window.financialReporting.setDateRangePreset('last_month')">Last Month</button>
                        <button class="btn btn-outline-secondary btn-xs" onclick="window.financialReporting.setDateRangePreset('this_quarter')">This Quarter</button>
                        <button class="btn btn-outline-secondary btn-xs" onclick="window.financialReporting.setDateRangePreset('this_year')">This Year</button>
                    </div>
                </div>
            `;
            
            filterGroup.insertAdjacentHTML('beforeend', presetButtonsHTML);
        }
    }
    
    async updateFinancialSummary() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        if (!startDate || !endDate) {
            console.log('Both start and end dates are required for filtering');
            return;
        }
        
        // Validate date range
        if (new Date(startDate) > new Date(endDate)) {
            this.showError('Start date cannot be later than end date');
            return;
        }
        
        // Show loading state on summary cards
        this.showSummaryLoading(true);
        
        try {
            const formData = new FormData();
            formData.append('action', 'getFilteredFinancialData');
            formData.append('start_date', startDate);
            formData.append('end_date', endDate);
            
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success && data.summary) {
                this.currentFinancialSummary = data;
                this.updateSummaryCards(data.summary);
                this.showDateRangeInfo(startDate, endDate);
                
                // If there's an active report, regenerate it with new date range
                const reportType = document.getElementById('reportType').value;
                if (reportType) {
                    this.generateReport();
                }
            } else {
                this.showError(data.error || 'Failed to load filtered financial data');
            }
            
        } catch (error) {
            console.error('Error updating financial summary:', error);
            this.showError('Failed to update financial summary. Please try again.');
        } finally {
            this.showSummaryLoading(false);
        }
    }
    
    updateSummaryCards(summary) {
        // Update Total Revenue card
        const revenueElement = document.getElementById('totalRevenue');
        if (revenueElement) {
            const wasHidden = revenueElement.classList.contains('hidden-value');
            const actualValue = revenueElement.querySelector('.actual-value');
            if (actualValue) {
                actualValue.textContent = this.formatCurrency(summary.total_revenue);
            }
            this.animateCardUpdate(revenueElement);
            // Maintain hidden state after update
            if (wasHidden) {
                revenueElement.classList.add('hidden-value');
            }
        }
        
        // Update Total Expenses card
        const expensesElement = document.getElementById('totalExpenses');
        if (expensesElement) {
            const wasHidden = expensesElement.classList.contains('hidden-value');
            const actualValue = expensesElement.querySelector('.actual-value');
            if (actualValue) {
                actualValue.textContent = this.formatCurrency(summary.total_expenses);
            }
            this.animateCardUpdate(expensesElement);
            // Maintain hidden state after update
            if (wasHidden) {
                expensesElement.classList.add('hidden-value');
            }
        }
        
        // Update Net Income card and color
        const netIncomeElement = document.getElementById('netIncome');
        const netIncomeCard = document.getElementById('netIncomeCard');
        if (netIncomeElement && netIncomeCard) {
            const wasHidden = netIncomeElement.classList.contains('hidden-value');
            const actualValue = netIncomeElement.querySelector('.actual-value');
            if (actualValue) {
                actualValue.textContent = this.formatCurrency(summary.net_income);
            }
            this.animateCardUpdate(netIncomeElement);
            
            // Update card color based on net income value
            netIncomeCard.className = netIncomeCard.className.replace(/bg-(success|warning|danger)/, '');
            netIncomeCard.classList.add(summary.net_income >= 0 ? 'bg-success' : 'bg-warning');
            
            // Maintain hidden state after update
            if (wasHidden) {
                netIncomeElement.classList.add('hidden-value');
            }
        }
        
        // Update Total Assets card
        const assetsElement = document.getElementById('totalAssets');
        if (assetsElement) {
            const wasHidden = assetsElement.classList.contains('hidden-value');
            const actualValue = assetsElement.querySelector('.actual-value');
            if (actualValue) {
                actualValue.textContent = this.formatCurrency(summary.total_assets);
            }
            this.animateCardUpdate(assetsElement);
            // Maintain hidden state after update
            if (wasHidden) {
                assetsElement.classList.add('hidden-value');
            }
        }
        
        // Update period text to show actual date range
        const periodTexts = document.querySelectorAll('.period-text');
        periodTexts.forEach(text => {
            if (text.textContent.includes('Current Period') || text.textContent.includes('Jan') || text.textContent.includes('Dec')) {
                const dateRange = `${this.formatDateShort(summary.period_start)} - ${this.formatDateShort(summary.period_end)}`;
                text.textContent = dateRange;
            }
        });
    }
    
    showDateRangeInfo(startDate, endDate) {
        // Remove existing date info
        const existingInfo = document.querySelector('.date-range-info');
        if (existingInfo) {
            existingInfo.remove();
        }
        
        // Create date range info element
        const dateInfo = document.createElement('div');
        dateInfo.className = 'date-range-info alert alert-primary mt-3';
        dateInfo.innerHTML = `
            <strong>Current Filter:</strong> ${this.formatDate(startDate)} to ${this.formatDate(endDate)}
            <button type="button" class="btn-close float-end" onclick="this.parentElement.remove()"></button>
        `;
        
        // Insert after the summary cards
        const summaryRow = document.querySelector('.row.mb-4');
        if (summaryRow) {
            summaryRow.parentNode.insertBefore(dateInfo, summaryRow.nextSibling);
        }
    }
    
    animateCardUpdate(element) {
        element.closest('.card').classList.add('card-animate');
        setTimeout(() => {
            element.closest('.card').classList.remove('card-animate');
        }, 300);
    }
    
    showSummaryLoading(show) {
        const cards = document.querySelectorAll('#revenueCard, #expenseCard, #netIncomeCard, #assetsCard');
        cards.forEach(card => {
            if (show) {
                card.classList.add('summary-loading');
            } else {
                card.classList.remove('summary-loading');
            }
        });
    }
    
    async generateReport() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const reportType = document.getElementById('reportType').value;
        
        if (!startDate || !endDate) {
            this.showError('Please select both start and end dates');
            return;
        }
        
        if (!reportType) {
            this.showError('Please select a report type');
            return;
        }
        
        this.showLoading(true);
        
        try {
            let response;
            const formData = new FormData();
            
            switch (reportType) {
                case 'income_statement':
                    formData.append('action', 'getIncomeStatement');
                    formData.append('start_date', startDate);
                    formData.append('end_date', endDate);
                    break;
                case 'balance_sheet':
                    formData.append('action', 'getBalanceSheet');
                    formData.append('as_of_date', endDate);
                    break;
                case 'cash_flow':
                    formData.append('action', 'getCashFlow');
                    formData.append('start_date', startDate);
                    formData.append('end_date', endDate);
                    break;
                case 'budget_performance':
                    formData.append('action', 'getBudgetPerformance');
                    const period = document.getElementById('budgetPeriod') ? document.getElementById('budgetPeriod').value : null;
                    if (period) formData.append('period', period);
                    break;
                default:
                    throw new Error('Invalid report type');
            }
            
            response = await fetch('reporting_handler.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            this.currentReportData = data;
            this.displayReport(reportType, data);
            
            // Update the summary cards with report-specific data if needed
            if (reportType === 'income_statement' && data.revenue !== undefined) {
                this.updateSummaryCardsFromReport(data);
            }
            
        } catch (error) {
            console.error('Error generating report:', error);
            this.showError('Failed to generate report. Please try again.');
        } finally {
            this.showLoading(false);
        }
    }
    
    updateSummaryCardsFromReport(reportData) {
        // Update summary cards with data from the generated report
        const summary = {
            total_revenue: reportData.revenue || 0,
            total_expenses: reportData.expenses || 0,
            net_income: reportData.net_income || 0,
            period_start: document.getElementById('startDate').value,
            period_end: document.getElementById('endDate').value
        };
        
        this.updateSummaryCards(summary);
    }
    
    displayReport(reportType, data) {
        const reportContent = document.getElementById('reportContent');
        const reportTitle = document.getElementById('reportTitle');
        
        // Add date range to report title
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const dateRangeText = ` (${this.formatDateShort(startDate)} - ${this.formatDateShort(endDate)})`;
        
        switch (reportType) {
            case 'income_statement':
                reportTitle.textContent = 'Income Statement' + dateRangeText;
                reportContent.innerHTML = this.generateIncomeStatementHTML(data);
                break;
            case 'balance_sheet':
                reportTitle.textContent = 'Balance Sheet' + ` (As of ${this.formatDateShort(endDate)})`;
                reportContent.innerHTML = this.generateBalanceSheetHTML(data);
                break;
            case 'cash_flow':
                reportTitle.textContent = 'Cash Flow Statement' + dateRangeText;
                reportContent.innerHTML = this.generateCashFlowHTML(data);
                break;
            case 'budget_performance':
                reportTitle.textContent = 'Budget Performance' + dateRangeText;
                reportContent.innerHTML = this.generateBudgetPerformanceHTML(data);
                break;
        }
        
        // Highlight the filtered period in the report content
        this.highlightDateRange(startDate, endDate);
    }
    
    highlightDateRange(startDate, endDate) {
        // Add a highlighted date range indicator to the report
        const reportContent = document.getElementById('reportContent');
        if (reportContent) {
            const dateRangeIndicator = document.createElement('div');
            dateRangeIndicator.className = 'alert alert-primary mb-3';
            dateRangeIndicator.innerHTML = `
                <strong>Report Period:</strong> ${this.formatDate(startDate)} to ${this.formatDate(endDate)}
                <br><small class="text-muted">All calculations are based on transactions within this date range.</small>
            `;
            
            // Insert at the beginning of report content
            reportContent.insertBefore(dateRangeIndicator, reportContent.firstChild);
        }
    }
    
    // Quick date range presets
    setDateRangePreset(preset) {
        const today = new Date();
        let startDate, endDate;
        
        switch (preset) {
            case 'today':
                startDate = endDate = today.toISOString().split('T')[0];
                break;
            case 'yesterday':
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                startDate = endDate = yesterday.toISOString().split('T')[0];
                break;
            case 'this_week':
                const thisWeekStart = new Date(today);
                thisWeekStart.setDate(today.getDate() - today.getDay());
                startDate = thisWeekStart.toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'this_month':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'last_month':
                const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                startDate = lastMonth.toISOString().split('T')[0];
                endDate = lastMonthEnd.toISOString().split('T')[0];
                break;
            case 'this_quarter':
                const currentQuarter = Math.floor(today.getMonth() / 3);
                startDate = new Date(today.getFullYear(), currentQuarter * 3, 1).toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            case 'this_year':
                startDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            default:
                return;
        }
        
        // Update the date inputs
        document.getElementById('startDate').value = startDate;
        document.getElementById('endDate').value = endDate;
        
        // Trigger the financial summary update
        this.updateFinancialSummary();
    }
    
    // Report generation methods
    generateIncomeStatementHTML(data) {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        let html = `
            <p><strong>Period:</strong> ${this.formatDate(startDate)} - ${this.formatDate(endDate)}</p>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Description</th>
                            <th class="text-end">Amount (₱)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>REVENUE</strong></td>
                            <td></td>
                        </tr>`;
        
        // Add revenue details
        if (data.details && data.details.revenue) {
            data.details.revenue.forEach(item => {
                html += `
                    <tr>
                        <td>&nbsp;&nbsp;${item.client_name}</td>
                        <td class="text-end currency">${this.formatCurrency(item.amount)}</td>
                    </tr>`;
            });
        }
        
        html += `
                        <tr class="fw-bold">
                            <td>Total Revenue</td>
                            <td class="text-end currency">${this.formatCurrency(data.revenue || 0)}</td>
                        </tr>
                        <tr>
                            <td><strong>EXPENSES</strong></td>
                            <td></td>
                        </tr>`;
        
        // Add expense details
        if (data.details && data.details.expenses) {
            data.details.expenses.forEach(item => {
                html += `
                    <tr>
                        <td>&nbsp;&nbsp;${item.category}</td>
                        <td class="text-end currency">${this.formatCurrency(item.amount)}</td>
                    </tr>`;
            });
        }
        
        html += `
                        <tr class="fw-bold">
                            <td>Total Expenses</td>
                            <td class="text-end currency">${this.formatCurrency(data.expenses || 0)}</td>
                        </tr>
                        <tr class="table-secondary fw-bold">
                            <td>NET INCOME</td>
                            <td class="text-end currency ${(data.net_income || 0) >= 0 ? 'text-success' : 'text-danger'}">
                                ${this.formatCurrency(data.net_income || 0)}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>`;
        
        return html;
    }
    
    generateBalanceSheetHTML(data) {
        const asOfDate = document.getElementById('endDate').value;
        
        let html = `
            <p><strong>As of:</strong> ${this.formatDate(asOfDate)}</p>
            <div class="row">
                <div class="col-md-6">
                    <h6><strong>ASSETS</strong></h6>
                    <table class="table table-sm">`;
        
        if (data.assets && data.assets.length > 0) {
            data.assets.forEach(asset => {
                html += `
                    <tr>
                        <td>${asset.account_name}</td>
                        <td class="text-end currency">${this.formatCurrency(asset.balance)}</td>
                    </tr>`;
            });
        }
        
        html += `
                        <tr class="fw-bold border-top">
                            <td>Total Assets</td>
                            <td class="text-end currency">${this.formatCurrency(data.total_assets || 0)}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6><strong>LIABILITIES & EQUITY</strong></h6>
                    <table class="table table-sm">`;
        
        // Add liabilities
        if (data.liabilities && data.liabilities.length > 0) {
            data.liabilities.forEach(liability => {
                html += `
                    <tr>
                        <td>${liability.account_name}</td>
                        <td class="text-end currency">${this.formatCurrency(liability.balance)}</td>
                    </tr>`;
            });
        }
        
        // Add equity
        if (data.equity && data.equity.length > 0) {
            data.equity.forEach(equity => {
                html += `
                    <tr>
                        <td>${equity.account_name}</td>
                        <td class="text-end currency">${this.formatCurrency(equity.balance)}</td>
                    </tr>`;
            });
        }
        
        html += `
                        <tr class="fw-bold border-top">
                            <td>Total Liabilities + Equity</td>
                            <td class="text-end currency">${this.formatCurrency((data.total_liabilities || 0) + (data.total_equity || 0))}</td>
                        </tr>
                    </table>
                </div>
            </div>`;
        
        return html;
    }
    
    generateCashFlowHTML(data) {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        return `
            <p><strong>Period:</strong> ${this.formatDate(startDate)} - ${this.formatDate(endDate)}</p>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Cash Flow Activity</th>
                            <th class="text-end">Amount (₱)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Cash from Operating Activities</td>
                            <td class="text-end currency ${(data.operating || 0) >= 0 ? 'text-success' : 'text-danger'}">
                                ${this.formatCurrency(data.operating || 0)}
                            </td>
                        </tr>
                        <tr>
                            <td>Cash from Investing Activities</td>
                            <td class="text-end currency ${(data.investing || 0) >= 0 ? 'text-success' : 'text-danger'}">
                                ${this.formatCurrency(data.investing || 0)}
                            </td>
                        </tr>
                        <tr>
                            <td>Cash from Financing Activities</td>
                            <td class="text-end currency ${(data.financing || 0) >= 0 ? 'text-success' : 'text-danger'}">
                                ${this.formatCurrency(data.financing || 0)}
                            </td>
                        </tr>
                        <tr class="table-secondary fw-bold">
                            <td>NET CASH FLOW</td>
                            <td class="text-end currency ${(data.net_cash_flow || 0) >= 0 ? 'text-success' : 'text-danger'}">
                                ${this.formatCurrency(data.net_cash_flow || 0)}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>`;
    }
    
    
    generateBudgetPerformanceHTML(data) {
        let html = `
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Period</th>
                            <th>Department</th>
                            <th>Cost Center</th>
                            <th class="text-end">Allocated (₱)</th>
                            <th class="text-end">Used (₱)</th>
                            <th class="text-end">Remaining (₱)</th>
                            <th class="text-end">Utilization %</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        if (data && data.length > 0) {
            data.forEach(budget => {
                const utilizationClass = (budget.utilization_percentage || 0) > 90 ? 'text-danger' : 
                                       (budget.utilization_percentage || 0) > 70 ? 'text-warning' : 'text-success';
                
                html += `
                    <tr>
                        <td>${budget.period || ''}</td>
                        <td>${budget.department || ''}</td>
                        <td>${budget.cost_center || ''}</td>
                        <td class="text-end currency">${this.formatCurrency(budget.amount_allocated || 0)}</td>
                        <td class="text-end currency">${this.formatCurrency(budget.amount_used || 0)}</td>
                        <td class="text-end currency">${this.formatCurrency(budget.remaining || 0)}</td>
                        <td class="text-end ${utilizationClass}">${budget.utilization_percentage || 0}%</td>
                        <td>
                            <span class="badge ${this.getStatusBadgeClass(budget.approval_status)}">
                                ${budget.approval_status || 'Unknown'}
                            </span>
                        </td>
                    </tr>`;
            });
        } else {
            html += `
                <tr>
                    <td colspan="8" class="text-center text-muted">No budget data available for the selected period</td>
                </tr>`;
        }
        
        html += `
                    </tbody>
                </table>
            </div>`;
        
        return html;
    }
    
    // Export functionality
    async exportReport(format) {
        if (!this.currentReportData) {
            this.showError('No report data available. Please generate a report first.');
            return;
        }
        
        const reportType = document.getElementById('reportType').value;
        
        // Show export progress
        document.getElementById('exportProgress').style.display = 'block';
        
        try {
            const formData = new FormData();
            formData.append('action', format === 'pdf' ? 'exportPDF' : 'exportExcel');
            formData.append('report_type', reportType);
            formData.append('report_data', JSON.stringify(this.currentReportData));
            formData.append('start_date', document.getElementById('startDate').value);
            formData.append('end_date', document.getElementById('endDate').value);
            
            const response = await fetch('reporting_handler.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(`${format.toUpperCase()} export completed successfully!`);
            } else {
                this.showError(result.message || 'Export failed');
            }
        } catch (error) {
            console.error('Export error:', error);
            this.showError('Export failed. Please try again.');
        } finally {
            document.getElementById('exportProgress').style.display = 'none';
        }
    }
    
    // Utility methods
    formatCurrency(amount) {
        return '₱' + parseFloat(amount || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    formatDateShort(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: '2-digit'
        });
    }
    
    getStatusBadgeClass(status) {
        switch (status) {
            case 'Approved': return 'bg-success';
            case 'Pending': return 'bg-warning';
            case 'Rejected': return 'bg-danger';
            default: return 'bg-secondary';
        }
    }
    
    showLoading(show) {
        const spinner = document.querySelector('.loading-spinner');
        if (spinner) {
            spinner.style.display = show ? 'block' : 'none';
        }
    }
    
    showError(message) {
        const alertHTML = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
        
        const container = document.querySelector('.container-fluid');
        container.insertAdjacentHTML('afterbegin', alertHTML);
        
        setTimeout(() => {
            const alert = document.querySelector('.alert-danger');
            if (alert) alert.remove();
        }, 5000);
    }
    
    showSuccess(message) {
        const alertHTML = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
        
        const container = document.querySelector('.container-fluid');
        container.insertAdjacentHTML('afterbegin', alertHTML);
        
        setTimeout(() => {
            const alert = document.querySelector('.alert-success');
            if (alert) alert.remove();
        }, 3000);
    }
    
    // Visibility toggle functionality
    initVisibilityToggle() {
        const toggleAllBtn = document.getElementById('toggleAllValues');
        if (toggleAllBtn) {
            toggleAllBtn.addEventListener('click', () => {
                this.toggleAllValues();
            });
        }
        
        // Default to hidden (values masked with dots)
        this.valuesVisible = false;
        // Values are already hidden by default via CSS class 'value-hidden'
    }
    
    toggleAllValues() {
        this.valuesVisible = !this.valuesVisible;
        
        if (this.valuesVisible) {
            this.showAllValues();
        } else {
            this.hideAllValues();
        }
        
        // Update button text and icon
        const toggleText = document.getElementById('toggleText');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (toggleText) {
            toggleText.textContent = this.valuesVisible ? 'Hide Values' : 'Show Values';
        }
        if (toggleIcon) {
            toggleIcon.textContent = this.valuesVisible ? '👁️' : '🙈';
        }
    }
    
    hideAllValues(animate = true) {
        const valueElements = ['totalRevenue', 'totalExpenses', 'netIncome', 'totalAssets'];
        valueElements.forEach(elementId => {
            const element = document.getElementById(elementId);
            if (element) {
                element.classList.add('hidden-value');
                const card = element.closest('.card');
                const toggleIcon = card.querySelector('.toggle-icon');
                if (toggleIcon) toggleIcon.textContent = '👁️';
            }
        });
    }
    
    showAllValues(animate = true) {
        const valueElements = ['totalRevenue', 'totalExpenses', 'netIncome', 'totalAssets'];
        valueElements.forEach(elementId => {
            const element = document.getElementById(elementId);
            if (element) {
                element.classList.remove('hidden-value');
                const card = element.closest('.card');
                const toggleIcon = card.querySelector('.toggle-icon');
                if (toggleIcon) toggleIcon.textContent = '🙈';
            }
        });
    }
}

// Global export functions for dropdown menu
function exportFinancialReport(reportType) {
    document.getElementById('reportType').value = reportType;
    window.financialReporting.generateReport().then(() => {
        window.financialReporting.exportReport('pdf');
    });
}

function exportAllFinancialReports() {
    const reportTypes = ['income_statement', 'balance_sheet', 'cash_flow', 'trial_balance', 'budget_performance'];
    let currentIndex = 0;
    
    function exportNext() {
        if (currentIndex < reportTypes.length) {
            const reportType = reportTypes[currentIndex];
            document.getElementById('reportType').value = reportType;
            
            window.financialReporting.generateReport().then(() => {
                window.financialReporting.exportReport('pdf').then(() => {
                    currentIndex++;
                    setTimeout(exportNext, 1000); // Wait 1 second between exports
                });
            });
        } else {
            window.financialReporting.showSuccess('All reports exported successfully!');
        }
    }
    
    exportNext();
}

// Initialize the financial reporting system
document.addEventListener('DOMContentLoaded', function() {
    window.financialReporting = new FinancialReportingJS();
    
    // Check if there are any data issues and alert user
    <?php if (isset($financial_summary['error']) && $financial_summary['error']): ?>
    console.warn('Financial data loading issue: <?php echo addslashes($financial_summary['error']); ?>');
    <?php endif; ?>
    
    // Log current financial summary for debugging
    console.log('Financial Summary Data:', <?php echo json_encode($financial_summary); ?>);
    
    // Initialize export system
    console.log('Financial reporting with export capabilities initialized successfully!');
});

// Utility function for formatting currency (PHP equivalent)
function formatCurrency(amount) {
    return '₱' + parseFloat(amount).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
</script>

</body>
</html>
