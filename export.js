// Enhanced export.js - Direct PDF Export (No Additional Windows)
class FinancialExportManager {
    constructor() {
        this.initializeExportSystem();
    }

    initializeExportSystem() {
        this.attachExportListeners();
        this.setupModalExportButtons();
        this.initializeBudgetPerformanceFeatures();
    }

    attachExportListeners() {
        // Main export PDF button
        const exportPDFBtn = document.getElementById('exportPDF');
        if (exportPDFBtn) {
            exportPDFBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.exportCurrentReport();
            });
        }

        // Individual report export buttons in modals (PDF only)
        const exportButtons = [
            'exportIncomeStatementPDF',
            'exportBalanceSheetPDF', 
            'exportCashFlowPDF',
            'exportBudgetPerformancePDF'
        ];

        exportButtons.forEach(buttonId => {
            const button = document.getElementById(buttonId);
            if (button) {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const reportType = this.getReportTypeFromButtonId(buttonId);
                    this.exportSpecificReport(reportType);
                });
            }
        });
    }

    setupModalExportButtons() {
        // Add export functionality to modal buttons that might not exist yet
        document.addEventListener('click', (e) => {
            if (e.target.matches('[id*="export"][id*="PDF"]')) {
                e.preventDefault();
                const buttonId = e.target.id;
                const reportType = this.getReportTypeFromButtonId(buttonId);
                if (reportType) {
                    this.exportSpecificReport(reportType);
                }
            }
        });
    }

    initializeBudgetPerformanceFeatures() {
        // Add budget performance specific functionality
        this.setupBudgetPerformanceFilters();
        this.setupBudgetPerformancePreview();
    }

    setupBudgetPerformanceFilters() {
        // Add budget period filter functionality
        const budgetPeriodSelect = document.getElementById('budgetPeriod');
        if (budgetPeriodSelect) {
            budgetPeriodSelect.addEventListener('change', (e) => {
                this.updateBudgetPerformancePreview();
            });
        }

        // Add department filter
        const departmentFilter = document.getElementById('departmentFilter');
        if (departmentFilter) {
            departmentFilter.addEventListener('change', (e) => {
                this.updateBudgetPerformancePreview();
            });
        }
    }

    setupBudgetPerformancePreview() {
        // Setup real-time budget performance preview
        const previewContainer = document.getElementById('budgetPerformancePreview');
        if (previewContainer) {
            this.loadBudgetPerformancePreview();
        }
    }

    async loadBudgetPerformancePreview() {
        try {
            const startDate = document.getElementById('startDate')?.value || this.getDefaultStartDate();
            const endDate = document.getElementById('endDate')?.value || this.getDefaultEndDate();
            
            const formData = new FormData();
            formData.append('action', 'getBudgetPerformancePreview');
            formData.append('start_date', startDate);
            formData.append('end_date', endDate);

            const response = await fetch('export.php', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                const data = await response.json();
                this.displayBudgetPerformancePreview(data);
            }
        } catch (error) {
            console.log('Budget performance preview not available:', error.message);
        }
    }

    displayBudgetPerformancePreview(data) {
        const previewContainer = document.getElementById('budgetPerformancePreview');
        if (!previewContainer) return;

        if (!data || data.length === 0) {
            previewContainer.innerHTML = `
                <div class="alert alert-info">
                    <h6>Budget Performance Preview</h6>
                    <p>No budget data available for the selected period.</p>
                    <small>Budget performance will be generated based on actual financial data.</small>
                </div>
            `;
            return;
        }

        let html = `
            <div class="card">
                <div class="card-header">
                    <h6>Budget Performance Preview</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Cost Center</th>
                                    <th class="text-end">Allocated</th>
                                    <th class="text-end">Used</th>
                                    <th class="text-end">Utilization %</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
        `;

        data.forEach(item => {
            const utilizationClass = item.utilization_percentage > 100 ? 'text-danger' :
                                   item.utilization_percentage > 90 ? 'text-warning' : 'text-success';
            
            html += `
                <tr>
                    <td>${item.department}</td>
                    <td>${item.cost_center}</td>
                    <td class="text-end">${this.formatCurrency(item.amount_allocated)}</td>
                    <td class="text-end">${this.formatCurrency(item.amount_used)}</td>
                    <td class="text-end ${utilizationClass}">${item.utilization_percentage}%</td>
                    <td>
                        <span class="badge ${this.getStatusBadgeClass(item.approval_status)}">
                            ${item.approval_status}
                        </span>
                    </td>
                </tr>
            `;
        });

        html += `
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary btn-sm" onclick="window.financialExportManager.quickExportBudgetPerformance()">
                            Export Full Budget Performance Report
                        </button>
                    </div>
                </div>
            </div>
        `;

        previewContainer.innerHTML = html;
    }

    getReportTypeFromButtonId(buttonId) {
        const typeMap = {
            'exportIncomeStatementPDF': 'income_statement',
            'exportBalanceSheetPDF': 'balance_sheet',
            'exportCashFlowPDF': 'cash_flow',
            'exportBudgetPerformancePDF': 'budget_performance'
        };
        return typeMap[buttonId] || null;
    }

    async exportCurrentReport() {
        const reportType = document.getElementById('reportType')?.value;
        const startDate = document.getElementById('startDate')?.value;
        const endDate = document.getElementById('endDate')?.value;

        if (!reportType) {
            this.showError('Please select a report type first.');
            return;
        }

        if (!startDate || !endDate) {
            this.showError('Please select date range first.');
            return;
        }

        await this.performExport(reportType, startDate, endDate);
    }

    async exportSpecificReport(reportType) {
        const startDate = document.getElementById('startDate')?.value || this.getDefaultStartDate();
        const endDate = document.getElementById('endDate')?.value || this.getDefaultEndDate();

        await this.performExport(reportType, startDate, endDate);
    }

    async performExport(reportType, startDate, endDate) {
        try {
            this.showExportProgress(true, `Generating ${this.getReportDisplayName(reportType)} PDF...`);

            // Validate inputs
            const errors = this.validateExportParameters(reportType, startDate, endDate);
            if (errors.length > 0) {
                throw new Error(errors.join(', '));
            }

            // Special handling for budget performance report
            if (reportType === 'budget_performance') {
                this.showExportProgress(true, 'Analyzing budget performance data...');
            }

            // Use fetch to get the PDF data
            const formData = new FormData();
            formData.append('action', 'exportPDF');
            formData.append('report_type', reportType);
            formData.append('start_date', startDate);
            formData.append('end_date', endDate);

            // Add budget-specific parameters if available
            if (reportType === 'budget_performance') {
                const budgetPeriod = document.getElementById('budgetPeriod')?.value;
                const departmentFilter = document.getElementById('departmentFilter')?.value;
                if (budgetPeriod) formData.append('budget_period', budgetPeriod);
                if (departmentFilter) formData.append('department_filter', departmentFilter);
            }

            console.log('Sending export request to export.php...');
            const response = await fetch('export.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Check content type to determine if it's PDF or error
            const contentType = response.headers.get('content-type');
            console.log('Response content type:', contentType);
            
            if (contentType && contentType.includes('application/pdf')) {
                // It's a PDF file - download it directly
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                
                // Create download link
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `${this.getReportDisplayName(reportType)}_${startDate}_to_${endDate}.pdf`;
                
                document.body.appendChild(a);
                a.click();
                
                // Cleanup
                setTimeout(() => {
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                }, 100);

                // Show success message
                if (reportType === 'budget_performance') {
                    this.showSuccess(`✅ Budget Performance Report PDF downloaded successfully! The report includes utilization analysis, variance tracking, and performance indicators.`);
                } else {
                    this.showSuccess(`✅ ${this.getReportDisplayName(reportType)} PDF downloaded successfully!`);
                }
            } else if (contentType && contentType.includes('application/json')) {
                // Error response
                const result = await response.json();
                if (result.error) {
                    throw new Error(result.error);
                } else {
                    throw new Error('Unexpected response format');
                }
            } else {
                // Try to read as JSON in case content-type header is missing
                try {
                    const text = await response.text();
                    const result = JSON.parse(text);
                    if (result.error) {
                        throw new Error(result.error);
                    }
                } catch (e) {
                    throw new Error('Unexpected content type received: ' + contentType);
                }
            }

        } catch (error) {
            console.error('Export error:', error);
            
            // Special error message if PDF library is missing
            if (error.message.includes('PDF library not found') || error.message.includes('TCPDF')) {
                this.showError(`❌ PDF library not installed. Please install using:\ncomposer require tecnickcom/tcpdf`);
            } else {
                this.showError(`❌ Export failed: ${error.message}`);
            }
        } finally {
            // Reset progress after delay
            setTimeout(() => {
                this.showExportProgress(false);
            }, 2000);
        }
    }

    getReportDisplayName(reportType) {
        const displayNames = {
            'income_statement': 'Income_Statement',
            'balance_sheet': 'Balance_Sheet',
            'cash_flow': 'Cash_Flow_Statement',
            'trial_balance': 'Trial_Balance',
            'budget_performance': 'Budget_Performance_Report'
        };
        return displayNames[reportType] || 'Financial_Report';
    }

    getDefaultStartDate() {
        // First day of current month
        const now = new Date();
        return new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10);
    }

    getDefaultEndDate() {
        // Last day of current month
        const now = new Date();
        return new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10);
    }

    showExportProgress(show, message = 'Exporting...') {
        // Update all export buttons with loading state
        const exportButtons = document.querySelectorAll('[id*="export"][id*="PDF"]');
        exportButtons.forEach(button => {
            if (show) {
                button.disabled = true;
                const originalText = button.textContent;
                button.setAttribute('data-original-text', originalText);
                button.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${message}`;
            } else {
                button.disabled = false;
                const originalText = button.getAttribute('data-original-text') || 'Export PDF';
                button.innerHTML = originalText.includes('📄') ? originalText : '📄 ' + originalText;
            }
        });

        // Show/hide main loading indicator
        const loadingSpinner = document.querySelector('.loading-spinner');
        if (loadingSpinner) {
            loadingSpinner.style.display = show ? 'block' : 'none';
        }

        // Show/hide export progress modal if it exists
        const exportProgress = document.getElementById('exportProgress');
        if (exportProgress) {
            exportProgress.style.display = show ? 'block' : 'none';
        }
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'danger');
    }

    showNotification(message, type) {
        // Remove any existing notifications
        const existingAlerts = document.querySelectorAll('.export-notification');
        existingAlerts.forEach(alert => alert.remove());

        // Create notification element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed export-notification`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 450px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
        
        const icon = type === 'success' ? '✅' : type === 'danger' ? '❌' : 'ℹ️';
        alertDiv.innerHTML = `
            <div class="d-flex align-items-start">
                <div class="me-2" style="font-size: 1.5rem;">${icon}</div>
                <div class="flex-grow-1">
                    <strong>${type === 'success' ? 'Success!' : type === 'danger' ? 'Error!' : 'Info'}</strong><br>
                    ${message.replace(/\n/g, '<br>')}
                </div>
                <button type="button" class="btn-close ms-2" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;

        // Add to page
        document.body.appendChild(alertDiv);

        // Auto-remove after delay
        const autoRemoveDelay = type === 'success' ? 5000 : 8000;
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.classList.remove('show');
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.parentNode.removeChild(alertDiv);
                    }
                }, 150);
            }
        }, autoRemoveDelay);
    }

    // Enhanced validation methods
    validateExportParameters(reportType, startDate, endDate) {
        const errors = [];

        if (!reportType) {
            errors.push('Report type is required');
        }

        if (!startDate || !endDate) {
            errors.push('Start date and end date are required');
        }

        if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
            errors.push('Start date cannot be later than end date');
        }

        // Check for reasonable date range (not more than 5 years)
        if (startDate && endDate) {
            const daysDiff = (new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24);
            if (daysDiff > 1825) { // 5 years
                errors.push('Date range cannot exceed 5 years');
            }

            // Budget performance specific validation
            if (reportType === 'budget_performance' && daysDiff > 365) {
                console.warn('Budget performance reports work best with date ranges of 1 year or less');
            }
        }

        // Check if dates are valid
        if (startDate && !this.isValidDate(startDate)) {
            errors.push('Invalid start date format');
        }

        if (endDate && !this.isValidDate(endDate)) {
            errors.push('Invalid end date format');
        }

        return errors;
    }

    isValidDate(dateString) {
        const date = new Date(dateString);
        return date instanceof Date && !isNaN(date) && dateString.match(/^\d{4}-\d{2}-\d{2}$/);
    }

    // Quick export methods for specific reports
    quickExportIncomeStatement() {
        return this.exportSpecificReport('income_statement');
    }

    quickExportBalanceSheet() {
        return this.exportSpecificReport('balance_sheet');
    }

    quickExportCashFlow() {
        return this.exportSpecificReport('cash_flow');
    }

    quickExportBudgetPerformance() {
        return this.exportSpecificReport('budget_performance');
    }

    // Budget Performance specific methods
    async updateBudgetPerformancePreview() {
        // Reload the budget performance preview when filters change
        await this.loadBudgetPerformancePreview();
    }

    formatCurrency(amount) {
        return '₱' + parseFloat(amount || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    getStatusBadgeClass(status) {
        switch (status) {
            case 'Over Budget':
                return 'bg-danger';
            case 'At Risk':
                return 'bg-warning text-dark';
            case 'On Track':
                return 'bg-success';
            case 'Under Budget':
                return 'bg-info';
            case 'Approved':
                return 'bg-success';
            case 'Pending':
                return 'bg-warning text-dark';
            case 'Rejected':
                return 'bg-danger';
            default:
                return 'bg-secondary';
        }
    }

    // Static initializer
    static initialize() {
        return new FinancialExportManager();
    }
}

// Auto-initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize export system
    window.financialExportManager = FinancialExportManager.initialize();
    
    // Add keyboard shortcuts for quick export
    document.addEventListener('keydown', function(e) {
        // Ctrl+E for quick PDF export
        if (e.ctrlKey && e.key === 'e') {
            e.preventDefault();
            const reportType = document.getElementById('reportType')?.value;
            if (reportType && window.financialExportManager) {
                window.financialExportManager.exportCurrentReport();
            } else {
                alert('Please select a report type first');
            }
        }

        // Ctrl+B for quick budget performance export
        if (e.ctrlKey && e.key === 'b') {
            e.preventDefault();
            if (window.financialExportManager) {
                window.financialExportManager.quickExportBudgetPerformance();
            }
        }
    });

    // Debug info (can be removed in production)
    console.log('✅ Financial Export System initialized - Direct PDF downloads enabled');
});

// Global export functions for backward compatibility and dropdown menus
window.exportFinancialReport = function(reportType) {
    if (window.financialExportManager) {
        return window.financialExportManager.exportSpecificReport(reportType);
    } else {
        console.error('Financial Export Manager not initialized');
    }
};

// Enhanced global functions for budget performance
window.exportBudgetPerformance = function() {
    if (window.financialExportManager) {
        return window.financialExportManager.quickExportBudgetPerformance();
    }
};

// Function for dropdown menu integration
window.exportAllFinancialReports = function() {
    if (!window.financialExportManager) {
        console.error('Financial Export Manager not initialized');
        return;
    }

    const reportTypes = ['income_statement', 'balance_sheet', 'cash_flow', 'budget_performance'];
    let currentIndex = 0;

    function exportNext() {
        if (currentIndex < reportTypes.length) {
            const reportType = reportTypes[currentIndex];
            
            window.financialExportManager.exportSpecificReport(reportType).then(() => {
                currentIndex++;
                // Wait 2 seconds between exports to avoid overwhelming the system
                setTimeout(exportNext, 2000);
            }).catch(() => {
                currentIndex++;
                setTimeout(exportNext, 1000);
            });
        } else {
            window.financialExportManager.showSuccess('✅ All financial reports exported successfully!');
        }
    }

    exportNext();
};