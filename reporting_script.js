// reporting_script.js - Financial Reporting Frontend JavaScript
class FinancialReportingJS {
    constructor() {
        this.currentReportData = null;
        this.initEventListeners();
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
        
        document.getElementById('exportExcel').addEventListener('click', () => {
            this.exportReport('excel');
        });
    }
    
    async generateReport() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const reportType = document.getElementById('reportType').value;
        
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
                case 'trial_balance':
                    formData.append('action', 'getTrialBalance');
                    formData.append('as_of_date', endDate);
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
            
        } catch (error) {
            console.error('Error generating report:', error);
            this.showError('Failed to generate report. Please try again.');
        } finally {
            this.showLoading(false);
        }
    }
    
    displayReport(reportType, data) {
        const reportContent = document.getElementById('reportContent');
        const reportTitle = document.getElementById('reportTitle');
        
        switch (reportType) {
            case 'income_statement':
                reportTitle.textContent = '📈 Income Statement';
                reportContent.innerHTML = this.generateIncomeStatementHTML(data);
                break;
            case 'balance_sheet':
                reportTitle.textContent = '📊 Balance Sheet';
                reportContent.innerHTML = this.generateBalanceSheetHTML(data);
                break;
            case 'cash_flow':
                reportTitle.textContent = '💵 Cash Flow Statement';
                reportContent.innerHTML = this.generateCashFlowHTML(data);
                break;
            case 'trial_balance':
                reportTitle.textContent = '📋 Trial Balance';
                reportContent.innerHTML = this.generateTrialBalanceHTML(data);
                break;
            case 'budget_performance':
                reportTitle.textContent = '📊 Budget Performance';
                reportContent.innerHTML = this.generateBudgetPerformanceHTML(data);
                break;
        }
    }
    
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
                            <td class="text-end currency">${this.formatCurrency(data.revenue)}</td>
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
                            <td class="text-end currency">${this.formatCurrency(data.expenses)}</td>
                        </tr>
                        <tr class="table-secondary fw-bold">
                            <td>NET INCOME</td>
                            <td class="text-end currency ${data.net_income >= 0 ? 'text-success' : 'text-danger'}">
                                ${this.formatCurrency(data.net_income)}
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
        
        data.assets.forEach(asset => {
            html += `
                <tr>
                    <td>${asset.account_name}</td>
                    <td class="text-end currency">${this.formatCurrency(asset.balance)}</td>
                </tr>`;
        });
        
        html += `
                        <tr class="fw-bold border-top">
                            <td>Total Assets</td>
                            <td class="text-end currency">${this.formatCurrency(data.total_assets)}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6><strong>LIABILITIES & EQUITY</strong></h6>
                    <table class="table table-sm">`;
        
        // Add liabilities
        data.liabilities.forEach(liability => {
            html += `
                <tr>
                    <td>${liability.account_name}</td>
                    <td class="text-end currency">${this.formatCurrency(liability.balance)}</td>
                </tr>`;
        });
        
        // Add equity
        data.equity.forEach(equity => {
            html += `
                <tr>
                    <td>${equity.account_name}</td>
                    <td class="text-end currency">${this.formatCurrency(equity.balance)}</td>
                </tr>`;
        });
        
        html += `
                        <tr class="fw-bold border-top">
                            <td>Total Liabilities + Equity</td>
                            <td class="text-end currency">${this.formatCurrency(data.total_liabilities + data.total_equity)}</td>
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
                            <td class="text-end currency ${data.operating >= 0 ? 'text-success' : 'text-danger'}">
                                ${this.formatCurrency(data.operating)}
                            </td>
                        </tr>
                        <tr>
                            <td>Cash from Investing Activities</td>
                            <td class="text-end currency ${data.investing >= 0 ? 'text-success' : 'text-danger'}">
                                ${this.formatCurrency(data.investing)}
                            </td>
                        </tr>
                        <tr>
                            <td>Cash from Financing Activities</td>
                            <td class="text-end currency ${data.financing >= 0 ? 'text-success' : 'text-danger'}">
                                ${this.formatCurrency(data.financing)}
                            </td>
                        </tr>
                        <tr class="table-secondary fw-bold">
                            <td>NET CASH FLOW</td>
                            <td class="text-end currency ${data.net_cash_flow >= 0 ? 'text-success' : 'text-danger'}">
                                ${this.formatCurrency(data.net_cash_flow)}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>`;
    }
    
    generateTrialBalanceHTML(data) {
        const asOfDate = document.getElementById('endDate').value;
        
        let html = `
            <p><strong>As of:</strong> ${this.formatDate(asOfDate)}</p>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Account Code</th>
                            <th>Account Name</th>
                            <th>Type</th>
                            <th class="text-end">Debit (₱)</th>
                            <th class="text-end">Credit (₱)</th>
                            <th class="text-end">Balance (₱)</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        data.accounts.forEach(account => {
            html += `
                <tr>
                    <td>${account.account_code}</td>
                    <td>${account.account_name}</td>
                    <td>${account.account_type}</td>
                    <td class="text-end currency">${this.formatCurrency(account.total_debit)}</td>
                    <td class="text-end currency">${this.formatCurrency(account.total_credit)}</td>
                    <td class="text-end currency ${account.balance >= 0 ? 'text-success' : 'text-danger'}">
                        ${this.formatCurrency(Math.abs(account.balance))}
                    </td>
                </tr>`;
        });
        
        html += `
                        <tr class="table-secondary fw-bold">
                            <td colspan="3">TOTALS</td>
                            <td class="text-end currency">${this.formatCurrency(data.total_debits)}</td>
                            <td class="text-end currency">${this.formatCurrency(data.total_credits)}</td>
                            <td class="text-end"></td>
                        </tr>
                    </tbody>
                </table>
            </div>`;
        
        return html;
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
        
        data.forEach(budget => {
            const utilizationClass = budget.utilization_percentage > 90 ? 'text-danger' : 
                                   budget.utilization_percentage > 70 ? 'text-warning' : 'text-success';
            
            html += `
                <tr>
                    <td>${budget.period}</td>
                    <td>${budget.department}</td>
                    <td>${budget.cost_center}</td>
                    <td class="text-end currency">${this.formatCurrency(budget.amount_allocated)}</td>
                    <td class="text-end currency">${this.formatCurrency(budget.amount_used)}</td>
                    <td class="text-end currency">${this.formatCurrency(budget.remaining)}</td>
                    <td class="text-end ${utilizationClass}">${budget.utilization_percentage}%</td>
                    <td>
                        <span class="badge ${this.getStatusBadgeClass(budget.approval_status)}">
                            ${budget.approval_status}
                        </span>
                    </td>
                </tr>`;
        });
        
        html += `
                    </tbody>
                </table>
            </div>`;
        
        return html;
    }
    
    async exportReport(format) {
        if (!this.currentReportData) {
            this.showError('No report data available. Please generate a report first.');
            return;
        }
        
        const reportType = document.getElementById('reportType').value;
        
        try {
            const formData = new FormData();
            formData.append('action', format === 'pdf' ? 'exportPDF' : 'exportExcel');
            formData.append('report_type', reportType);
            formData.append('report_data', JSON.stringify(this.currentReportData));
            
            const response = await fetch('reporting_handler.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess(`${format.toUpperCase()} export completed: ${result.filename}`);
            } else {
                this.showError(result.message || 'Export failed');
            }
        } catch (error) {
            console.error('Export error:', error);
            this.showError('Export failed. Please try again.');
        }
    }
    
    formatCurrency(amount) {
        return '₱' + parseFloat(amount).toLocaleString('en-PH', {
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
        
        // Auto-dismiss after 5 seconds
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
        
        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            const alert = document.querySelector('.alert-success');
            if (alert) alert.remove();
        }, 3000);
    }
}

// Initialize the financial reporting system when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new FinancialReportingJS();
});

// Additional utility functions for financial calculations
function calculateFinancialRatios(balanceSheet, incomeStatement) {
    const ratios = {};
    
    // Current Ratio (Current Assets / Current Liabilities)
    const currentAssets = balanceSheet.total_assets; // Simplified
    const currentLiabilities = balanceSheet.total_liabilities; // Simplified
    ratios.currentRatio = currentLiabilities > 0 ? (currentAssets / currentLiabilities).toFixed(2) : 'N/A';
    
    // Debt to Equity Ratio
    ratios.debtToEquity = balanceSheet.total_equity > 0 ? 
        (balanceSheet.total_liabilities / balanceSheet.total_equity).toFixed(2) : 'N/A';
    
    // Net Profit Margin
    ratios.netProfitMargin = incomeStatement.revenue > 0 ? 
        ((incomeStatement.net_income / incomeStatement.revenue) * 100).toFixed(2) + '%' : 'N/A';
    
    return ratios;
}

// Format numbers consistently across the application
function formatNumber(number, decimals = 2) {
    return parseFloat(number).toLocaleString('en-PH', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}