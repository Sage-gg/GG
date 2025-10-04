<!-- Fixed Financial Reporting Modals - PDF Export Only -->

<!-- Income Statement Modal -->
<div class="modal fade" id="incomeStatementModal" tabindex="-1" aria-labelledby="incomeStatementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">📈 Income Statement</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="incomeStatementModalContent">
          <div class="text-center py-4">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading Income Statement...</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger" id="exportIncomeStatementPDF">📄 Export PDF</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Balance Sheet Modal -->
<div class="modal fade" id="balanceSheetModal" tabindex="-1" aria-labelledby="balanceSheetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">📊 Balance Sheet</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="balanceSheetModalContent">
          <div class="text-center py-4">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading Balance Sheet...</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger" id="exportBalanceSheetPDF">📄 Export PDF</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Cash Flow Modal -->
<div class="modal fade" id="cashFlowModal" tabindex="-1" aria-labelledby="cashFlowModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">💵 Cash Flow Statement</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="cashFlowModalContent">
          <div class="text-center py-4">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading Cash Flow Statement...</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger" id="exportCashFlowPDF">📄 Export PDF</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Budget Performance Modal -->
<div class="modal fade" id="budgetPerformanceModal" tabindex="-1" aria-labelledby="budgetPerformanceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">📊 Budget Performance</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="budgetPerformanceModalContent">
          <div class="text-center py-4">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading Budget Performance...</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger" id="exportBudgetPerformancePDF">📄 Export PDF</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
// Enhanced modal functionality (PDF export only)
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing PDF-only export modals...');
    
    // Enhanced PDF export buttons for modals
    const exportConfig = [
        { type: 'IncomeStatement', reportType: 'income_statement' },
        { type: 'BalanceSheet', reportType: 'balance_sheet' },
        { type: 'CashFlow', reportType: 'cash_flow' },
        { type: 'TrialBalance', reportType: 'trial_balance' },
        { type: 'BudgetPerformance', reportType: 'budget_performance' }
    ];
    
    exportConfig.forEach(config => {
        const buttonId = `export${config.type}PDF`;
        const button = document.getElementById(buttonId);
        if (button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (window.financialExportManager) {
                    // Use the export manager to handle PDF export
                    console.log('Exporting report:', config.reportType);
                    window.financialExportManager.exportSpecificReport(config.reportType);
                } else {
                    console.error('Export manager not initialized');
                    alert('Export system not ready. Please try again in a moment.');
                    
                    // Try to initialize the export manager if it's not available
                    setTimeout(() => {
                        if (window.financialExportManager) {
                            window.financialExportManager.exportSpecificReport(config.reportType);
                        } else {
                            alert('Export system failed to initialize. Please refresh the page.');
                        }
                    }, 1000);
                }
            });
            
            // Add visual feedback
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
                this.style.transition = 'transform 0.2s ease';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        }
    });
    
    // Add event listeners for modal quick access buttons (if they exist)
    document.querySelectorAll('[data-bs-target*="Modal"]').forEach(button => {
        button.addEventListener('click', function() {
            const reportType = this.getAttribute('data-report');
            const modalId = this.getAttribute('data-bs-target');
            
            console.log('Opening modal for report type:', reportType);
            
            // Set the report type in the dropdown if it exists
            if (reportType) {
                const reportTypeSelect = document.getElementById('reportType');
                if (reportTypeSelect) {
                    reportTypeSelect.value = reportType;
                }
                
                // Trigger report generation when modal opens (if function exists)
                setTimeout(() => {
                    if (window.financialReporting && typeof window.financialReporting.generateReport === 'function') {
                        window.financialReporting.generateReport();
                    }
                }, 500);
            }
        });
    });
    
    // Enhanced modal loading states
    function showModalLoading(modalId, show = true) {
        const modal = document.getElementById(modalId);
        if (modal) {
            const content = modal.querySelector('[id$="ModalContent"]');
            const loadingHtml = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h6>Generating Report...</h6>
                    <p class="text-muted">Please wait while we process your financial data.</p>
                </div>
            `;
            
            if (show && content) {
                content.innerHTML = loadingHtml;
            }
        }
    }
    
    // Enhanced modal error handling
    function showModalError(modalId, errorMessage) {
        const modal = document.getElementById(modalId);
        if (modal) {
            const content = modal.querySelector('[id$="ModalContent"]');
            const errorHtml = `
                <div class="text-center py-5">
                    <div class="text-danger mb-3">
                        <i class="fas fa-exclamation-triangle fa-3x"></i>
                    </div>
                    <h6 class="text-danger">Error Loading Report</h6>
                    <p class="text-muted">${errorMessage}</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                        Retry
                    </button>
                </div>
            `;
            
            if (content) {
                content.innerHTML = errorHtml;
            }
        }
    }
    
    // Modal state management
    const modalStates = {
        incomeStatement: { loaded: false, data: null },
        balanceSheet: { loaded: false, data: null },
        cashFlow: { loaded: false, data: null },
        trialBalance: { loaded: false, data: null },
        budgetPerformance: { loaded: false, data: null }
    };
    
    // Enhanced modal show event handlers
    ['incomeStatementModal', 'balanceSheetModal', 'cashFlowModal', 'trialBalanceModal', 'budgetPerformanceModal'].forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('show.bs.modal', function() {
                const reportType = modalId.replace('Modal', '').replace(/([A-Z])/g, '_$1').toLowerCase().substring(1);
                
                console.log('Modal showing for report type:', reportType);
                
                // Show loading state
                showModalLoading(modalId, true);
                
                // Auto-generate report for this modal if the function exists
                setTimeout(() => {
                    if (window.financialReporting && typeof window.financialReporting.generateReport === 'function') {
                        // Set the report type and generate
                        const reportTypeSelect = document.getElementById('reportType');
                        if (reportTypeSelect) {
                            reportTypeSelect.value = reportType;
                        }
                        
                        window.financialReporting.generateReport().then(() => {
                            // Report generated successfully
                            modalStates[reportType.replace('_', '')] = { 
                                loaded: true, 
                                data: window.financialReporting.currentReportData 
                            };
                        }).catch(error => {
                            console.error('Error generating report for modal:', error);
                            showModalError(modalId, error.message || 'Failed to load report data');
                        });
                    } else {
                        // If no financial reporting system, show basic message
                        const content = this.querySelector('[id$="ModalContent"]');
                        if (content) {
                            content.innerHTML = `
                                <div class="text-center py-5">
                                    <div class="text-info mb-3">
                                        <i class="fas fa-info-circle fa-3x"></i>
                                    </div>
                                    <h6>Report Ready for Export</h6>
                                    <p class="text-muted">Click the "Export PDF" button below to download this report.</p>
                                </div>
                            `;
                        }
                    }
                }, 500);
            });
            
            modal.addEventListener('hidden.bs.modal', function() {
                // Reset modal state when closed
                const content = this.querySelector('[id$="ModalContent"]');
                if (content) {
                    content.innerHTML = `
                        <div class="text-center py-4">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading ${modalId.replace('Modal', '').replace(/([A-Z])/g, ' $1')}...</p>
                        </div>
                    `;
                }
            });
        }
    });
    
    // Add context menu for additional options
    document.querySelectorAll('.modal-content').forEach(modal => {
        modal.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            
            // Create context menu
            const contextMenu = document.createElement('div');
            contextMenu.className = 'position-fixed bg-white border shadow-sm rounded';
            contextMenu.style.cssText = `
                top: ${e.clientY}px;
                left: ${e.clientX}px;
                z-index: 10000;
                min-width: 150px;
            `;
            
            const modalId = this.closest('.modal').id;
            
            contextMenu.innerHTML = `
                <div class="py-1">
                    <button class="btn btn-sm btn-link w-100 text-start" onclick="printModalContent('${modalId}')">
                        🖨️ Print Report
                    </button>
                    <button class="btn btn-sm btn-link w-100 text-start" onclick="copyModalContent('${modalId}')">
                        📋 Copy Content
                    </button>
                    <hr class="my-1">
                    <button class="btn btn-sm btn-link w-100 text-start" onclick="refreshModalContent('${modalId}')">
                        🔄 Refresh Data
                    </button>
                </div>
            `;
            
            document.body.appendChild(contextMenu);
            
            // Remove context menu on click elsewhere
            setTimeout(() => {
                document.addEventListener('click', function removeMenu() {
                    if (contextMenu.parentNode) {
                        contextMenu.parentNode.removeChild(contextMenu);
                    }
                    document.removeEventListener('click', removeMenu);
                }, 100);
            });
        });
    });
    
    // Global utility functions for context menu
    window.printModalContent = function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        const content = modal.querySelector('[id$="ModalContent"]').innerHTML;
        const printWindow = window.open('', '_blank');
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Financial Report</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f5f5f5; }
                    .text-right { text-align: right; }
                    .currency { font-family: "Courier New", monospace; }
                </style>
            </head>
            <body>
                <h2>${modalId.replace('Modal', '').replace(/([A-Z])/g, ' $1')}</h2>
                ${content}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.print();
    };
    
    window.copyModalContent = function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        const content = modal.querySelector('[id$="ModalContent"]');
        if (content) {
            const textContent = content.innerText;
            navigator.clipboard.writeText(textContent).then(() => {
                // Show success feedback
                const toast = document.createElement('div');
                toast.className = 'position-fixed top-0 end-0 m-3 alert alert-success';
                toast.innerHTML = 'Report content copied to clipboard!';
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 3000);
            }).catch(() => {
                alert('Failed to copy content to clipboard');
            });
        }
    };
    
    window.refreshModalContent = function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        const reportType = modalId.replace('Modal', '').replace(/([A-Z])/g, '_$1').toLowerCase().substring(1);
        
        // Show loading state
        showModalLoading(modalId, true);
        
        // Clear cached data
        modalStates[reportType.replace('_', '')] = { loaded: false, data: null };
        
        // Regenerate report if function exists
        setTimeout(() => {
            if (window.financialReporting && typeof window.financialReporting.generateReport === 'function') {
                const reportTypeSelect = document.getElementById('reportType');
                if (reportTypeSelect) {
                    reportTypeSelect.value = reportType;
                }
                
                window.financialReporting.generateReport().then(() => {
                    modalStates[reportType.replace('_', '')] = { 
                        loaded: true, 
                        data: window.financialReporting.currentReportData 
                    };
                }).catch(error => {
                    showModalError(modalId, error.message || 'Failed to refresh report data');
                });
            }
        }, 500);
    };
    
    // Add keyboard shortcuts for modal export
    document.addEventListener('keydown', function(e) {
        // Check if a modal is open
        const openModal = document.querySelector('.modal.show');
        if (!openModal) return;
        
        // Ctrl+P for export PDF from modal
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            const exportButton = openModal.querySelector('[id*="export"][id*="PDF"]');
            if (exportButton) {
                exportButton.click();
            }
        }
        
        // Escape to close modal
        if (e.key === 'Escape') {
            const closeButton = openModal.querySelector('.btn-close');
            if (closeButton) {
                closeButton.click();
            }
        }
    });
    
    console.log('PDF-only export modals initialized successfully');
});

</script>
