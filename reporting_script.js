// FIXED: Complete visibility toggle functionality for financial values

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
    
    // Simple toggle - just add/remove the hidden-value class
    if (valueElement.classList.contains('hidden-value')) {
        // Show the value
        valueElement.classList.remove('hidden-value');
        if (toggleIcon) toggleIcon.textContent = '🙈';
        if (toggleBtn) toggleBtn.setAttribute('title', 'Click to hide value');
    } else {
        // Hide the value
        valueElement.classList.add('hidden-value');
        if (toggleIcon) toggleIcon.textContent = '👁️';
        if (toggleBtn) toggleBtn.setAttribute('title', 'Click to show value');
    }
}

// Enhanced Financial Reporting JavaScript - FIXED VERSION
class FinancialReportingJS {
    constructor() {
        this.currentReportData = null;
        this.currentFinancialSummary = null;
        this.valuesVisible = false; // Start with hidden values
        this.initEventListeners();
        this.initDateFilterListeners();
        this.initVisibilityToggle();
    }
    
    initEventListeners() {
        // Generate Report button
        const generateBtn = document.getElementById('generateReport');
        if (generateBtn) {
            generateBtn.addEventListener('click', () => {
                this.generateReport();
            });
        }
        
        // Quick report buttons
        document.querySelectorAll('.report-card').forEach(button => {
            button.addEventListener('click', (e) => {
                const reportType = e.target.getAttribute('data-report');
                document.getElementById('reportType').value = reportType;
                this.generateReport();
            });
        });
        
        // Export buttons
        const exportPDFBtn = document.getElementById('exportPDF');
        if (exportPDFBtn) {
            exportPDFBtn.addEventListener('click', () => {
                this.exportReport('pdf');
            });
        }
    }
    
    initDateFilterListeners() {
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        
        if (startDateInput && endDateInput) {
            startDateInput.addEventListener('change', () => {
                this.updateFinancialSummary();
            });
            
            endDateInput.addEventListener('change', () => {
                this.updateFinancialSummary();
            });
            
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
        
        setTimeout(() => {
            this.updateFinancialSummary();
        }, 1000);
    }
    
    // FIXED: Visibility toggle initialization
    initVisibilityToggle() {
        const toggleAllBtn = document.getElementById('toggleAllValues');
        if (toggleAllBtn) {
            toggleAllBtn.addEventListener('click', () => {
                this.toggleAllValues();
            });
        }
        
        // Start with values hidden
        this.valuesVisible = false;
        this.hideAllValues();
    }
    
    // FIXED: Toggle all values function
    toggleAllValues() {
        this.valuesVisible = !this.valuesVisible;
        
        if (this.valuesVisible) {
            this.showAllValues();
        } else {
            this.hideAllValues();
        }
        
        // Update button text and icon - FIXED
        const toggleText = document.getElementById('toggleText');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (toggleText) {
            toggleText.textContent = this.valuesVisible ? 'Hide' : 'Show';
        }
        if (toggleIcon) {
            toggleIcon.textContent = this.valuesVisible ? '👁️' : '🙈';
        }
    }
    
    // FIXED: Hide all values
    hideAllValues() {
        const valueElements = ['totalRevenue', 'totalExpenses', 'netIncome', 'totalAssets'];
        valueElements.forEach(elementId => {
            const element = document.getElementById(elementId);
            if (element) {
                element.classList.add('hidden-value');
                const card = element.closest('.card');
                if (card) {
                    const toggleIcon = card.querySelector('.toggle-icon');
                    const toggleBtn = card.querySelector('.toggle-visibility-btn');
                    if (toggleIcon) toggleIcon.textContent = '👁️';
                    if (toggleBtn) toggleBtn.setAttribute('title', 'Click to show value');
                }
            }
        });
    }
    
    // FIXED: Show all values
    showAllValues() {
        const valueElements = ['totalRevenue', 'totalExpenses', 'netIncome', 'totalAssets'];
        valueElements.forEach(elementId => {
            const element = document.getElementById(elementId);
            if (element) {
                element.classList.remove('hidden-value');
                const card = element.closest('.card');
                if (card) {
                    const toggleIcon = card.querySelector('.toggle-icon');
                    const toggleBtn = card.querySelector('.toggle-visibility-btn');
                    if (toggleIcon) toggleIcon.textContent = '🙈';
                    if (toggleBtn) toggleBtn.setAttribute('title', 'Click to hide value');
                }
            }
        });
    }
    
    async updateFinancialSummary() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        if (!startDate || !endDate) {
            return;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            this.showError('Start date cannot be later than end date');
            return;
        }
        
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
        // FIXED: Update cards while preserving visibility state
        const valueElements = [
            { id: 'totalRevenue', value: summary.total_revenue },
            { id: 'totalExpenses', value: summary.total_expenses },
            { id: 'netIncome', value: summary.net_income },
            { id: 'totalAssets', value: summary.total_assets }
        ];
        
        valueElements.forEach(item => {
            const element = document.getElementById(item.id);
            if (element) {
                const wasHidden = element.classList.contains('hidden-value');
                const actualValue = element.querySelector('.actual-value');
                if (actualValue) {
                    actualValue.textContent = this.formatCurrency(item.value);
                }
                this.animateCardUpdate(element);
                
                // Maintain hidden state after update
                if (wasHidden) {
                    element.classList.add('hidden-value');
                }
            }
        });
        
        // Update net income card color
        const netIncomeCard = document.getElementById('netIncomeCard');
        if (netIncomeCard) {
            netIncomeCard.className = netIncomeCard.className.replace(/bg-(success|warning|danger)/, '');
            netIncomeCard.classList.add(summary.net_income >= 0 ? 'bg-success' : 'bg-warning');
            // Add back the text-white class
            netIncomeCard.classList.add('text-white');
        }
    }
    
    animateCardUpdate(element) {
        const card = element.closest('.card');
        if (card) {
            card.classList.add('card-animate');
            setTimeout(() => {
                card.classList.remove('card-animate');
            }, 300);
        }
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
                    break;
                default:
                    throw new Error('Invalid report type');
            }
            
            const response = await fetch('reporting_handler.php', {
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
    }
    
    generateIncomeStatementHTML(data) {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        let html = `
            <p class="mb-2"><strong>Period:</strong> ${this.formatDate(startDate)} - ${this.formatDate(endDate)}</p>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
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
            <p class="mb-2"><strong>As of:</strong> ${this.formatDate(asOfDate)}</p>
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
        
        if (data.liabilities && data.liabilities.length > 0) {
            data.liabilities.forEach(liability => {
                html += `
                    <tr>
                        <td>${liability.account_name}</td>
                        <td class="text-end currency">${this.formatCurrency(liability.balance)}</td>
                    </tr>`;
            });
        }
        
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
            <p class="mb-2"><strong>Period:</strong> ${this.formatDate(startDate)} - ${this.formatDate(endDate)}</p>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
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
                <table class="table table-bordered table-sm">
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
                    <td colspan="8" class="text-center text-muted">No budget data available</td>
                </tr>`;
        }
        
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
            formData.append('action', 'exportPDF');
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
                this.showSuccess('PDF export completed successfully!');
            } else {
                this.showError(result.message || 'Export failed');
            }
        } catch (error) {
            console.error('Export error:', error);
            this.showError('Export failed. Please try again.');
        }
    }
    
    setDateRangePreset(preset) {
        const today = new Date();
        let startDate, endDate;
        
        switch (preset) {
            case 'today':
                startDate = endDate = today.toISOString().split('T')[0];
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
            case 'this_year':
                startDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
                break;
            default:
                return;
        }
        
        document.getElementById('startDate').value = startDate;
        document.getElementById('endDate').value = endDate;
        this.updateFinancialSummary();
    }
    
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
            <div class="alert alert-danger alert-dismissible fade show py-2 px-2" role="alert">
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
            <div class="alert alert-success alert-dismissible fade show py-2 px-2" role="alert">
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
}

// Global export functions
function exportFinancialReport(reportType) {
    document.getElementById('reportType').value = reportType;
    window.financialReporting.generateReport().then(() => {
        window.financialReporting.exportReport('pdf');
    });
}

function exportAllFinancialReports() {
    const reportTypes = ['income_statement', 'balance_sheet', 'cash_flow', 'budget_performance'];
    let currentIndex = 0;
    
    function exportNext() {
        if (currentIndex < reportTypes.length) {
            const reportType = reportTypes[currentIndex];
            document.getElementById('reportType').value = reportType;
            
            window.financialReporting.generateReport().then(() => {
                window.financialReporting.exportReport('pdf').then(() => {
                    currentIndex++;
                    setTimeout(exportNext, 1000);
                });
            });
        } else {
            window.financialReporting.showSuccess('All reports exported successfully!');
        }
    }
    
    exportNext();
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    window.financialReporting = new FinancialReportingJS();
    console.log('Financial reporting system initialized successfully!');
});