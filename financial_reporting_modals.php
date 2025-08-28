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
        <button type="button" class="btn btn-outline-primary" id="exportIncomeStatementPDF">📄 Export PDF</button>
        <button type="button" class="btn btn-outline-success" id="exportIncomeStatementExcel">📊 Export Excel</button>
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
        <button type="button" class="btn btn-outline-primary" id="exportBalanceSheetPDF">📄 Export PDF</button>
        <button type="button" class="btn btn-outline-success" id="exportBalanceSheetExcel">📊 Export Excel</button>
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
        <button type="button" class="btn btn-outline-primary" id="exportCashFlowPDF">📄 Export PDF</button>
        <button type="button" class="btn btn-outline-success" id="exportCashFlowExcel">📊 Export Excel</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Trial Balance Modal -->
<div class="modal fade" id="trialBalanceModal" tabindex="-1" aria-labelledby="trialBalanceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">📋 Trial Balance</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="trialBalanceModalContent">
          <div class="text-center py-4">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading Trial Balance...</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-primary" id="exportTrialBalancePDF">📄 Export PDF</button>
        <button type="button" class="btn btn-outline-success" id="exportTrialBalanceExcel">📊 Export Excel</button>
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
        <button type="button" class="btn btn-outline-primary" id="exportBudgetPerformancePDF">📄 Export PDF</button>
        <button type="button" class="btn btn-outline-success" id="exportBudgetPerformanceExcel">📊 Export Excel</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
// Enhanced modal functionality to work with your existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for modal quick access buttons
    document.querySelectorAll('[data-bs-target*="Modal"]').forEach(button => {
        button.addEventListener('click', function() {
            const reportType = this.getAttribute('data-report');
            const modalId = this.getAttribute('data-bs-target');
            
            // Set the report type in the dropdown
            if (reportType) {
                document.getElementById('reportType').value = reportType;
                
                // Trigger report generation when modal opens
                setTimeout(() => {
                    if (window.FinancialReportingJS) {
                        window.financialReporting.generateReport();
                    }
                }, 500);
            }
        });
    });
    
    // Enhanced export buttons for modals
    const exportButtons = ['PDF', 'Excel'];
    const reportTypes = ['IncomeStatement', 'BalanceSheet', 'CashFlow', 'TrialBalance', 'BudgetPerformance'];
    
    reportTypes.forEach(type => {
        exportButtons.forEach(format => {
            const buttonId = `export${type}${format}`;
            const button = document.getElementById(buttonId);
            if (button) {
                button.addEventListener('click', function() {
                    const formatLower = format.toLowerCase();
                    if (window.financialReporting && window.financialReporting.exportReport) {
                        window.financialReporting.exportReport(formatLower);
                    }
                });
            }
        });
    });
});
</script>