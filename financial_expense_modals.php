<!-- ADD Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="addExpenseForm" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">➕ Add Expense</h5>
          <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body row g-3">

          <div class="col-md-6">
            <label for="addDate" class="form-label">Date</label>
            <input type="date" class="form-control" id="addDate" name="expense_date" required />
          </div>

          <div class="col-md-6">
            <label for="addCategory" class="form-label">Category</label>
            <select class="form-select" id="addCategory" name="category" required>
              <option selected disabled>Select Category</option>
              <option>Fuel</option>
              <option>Repair & Maintenance</option>
              <option>Toll & Parking</option>
              <option>Supplies</option>
              <option>Other</option>
            </select>
          </div>

          <div class="col-md-6">
            <label for="addVendor" class="form-label">Vendor / Payee</label>
            <input type="text" class="form-control" id="addVendor" name="vendor" required />
          </div>

          <div class="col-md-6">
            <label for="addAmount" class="form-label">Amount</label>
            <input type="number" step="0.01" min="0" class="form-control" id="addAmount" name="amount" required />
          </div>

          <div class="col-12">
            <label for="addDescription" class="form-label">Description / Remarks</label>
            <textarea class="form-control" id="addDescription" name="remarks" rows="2" required></textarea>
          </div>

          <div class="col-md-6">
            <label for="addTaxType" class="form-label">Tax Type</label>
            <select class="form-select" id="addTaxType" name="tax_type" required>
              <option selected disabled>Select Tax Type</option>
              <option value="VAT">VAT (12%)</option>
              <option value="Exempted">Exempted</option>
              <option value="Withholding">Withholding (2%)</option>
              <option value="None">No Tax</option>
            </select>
          </div>

          <div class="col-md-6">
            <label for="addReceipt" class="form-label">Attach Receipt</label>
            <input type="file" class="form-control" id="addReceipt" name="receipt_file" accept="image/*,application/pdf" />
          </div>

          <div class="col-md-6">
            <label for="addPaymentMethod" class="form-label">Payment Method</label>
            <select class="form-select" id="addPaymentMethod" name="payment_method" required>
              <option selected disabled>Select Payment Method</option>
              <option>Bank</option>
              <option>Cash</option>
              <option>Loan</option>
            </select>
          </div>

          <div class="col-md-6">
            <label for="addVehicle" class="form-label">Vehicle / Crane Assigned</label>
            <input type="text" class="form-control" id="addVehicle" name="vehicle" />
          </div>

          <div class="col-md-6">
            <label for="addJobLinked" class="form-label">Job / Rental Linked</label>
            <input type="text" class="form-control" id="addJobLinked" name="job_linked" />
          </div>

          <div class="col-md-6">
            <label for="addApprovedBy" class="form-label">Approved By</label>
            <input type="text" class="form-control" id="addApprovedBy" name="approved_by" />
          </div>

          <div class="col-md-6">
            <label for="addStatus" class="form-label">Status</label>
            <select class="form-select" id="addStatus" name="status" required>
              <option selected disabled>Select Status</option>
              <option>Pending</option>
              <option>Approved</option>
              <option>Rejected</option>
            </select>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save Expense</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- VIEW Expense Modal -->
<div class="modal fade" id="viewExpenseModal" tabindex="-1" aria-labelledby="viewExpenseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">📄 View Expense</h5>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <dl class="row">
          <dt class="col-sm-4">Date</dt>
          <dd class="col-sm-8" id="viewDate"></dd>

          <dt class="col-sm-4">Category</dt>
          <dd class="col-sm-8" id="viewCategory"></dd>

          <dt class="col-sm-4">Vendor / Payee</dt>
          <dd class="col-sm-8" id="viewVendor"></dd>

          <dt class="col-sm-4">Amount</dt>
          <dd class="col-sm-8">₱<span id="viewAmount"></span></dd>

          <dt class="col-sm-4">Description / Remarks</dt>
          <dd class="col-sm-8" id="viewRemarks"></dd>

          <dt class="col-sm-4">Tax Type</dt>
          <dd class="col-sm-8" id="viewTaxType"></dd>

          <dt class="col-sm-4">Tax Amount</dt>
          <dd class="col-sm-8">₱<span id="viewTaxAmount"></span></dd>

          <dt class="col-sm-4">Receipt Attached</dt>
          <dd class="col-sm-8" id="viewReceiptAttached"></dd>

          <dt class="col-sm-4">Payment Method</dt>
          <dd class="col-sm-8" id="viewPaymentMethod"></dd>

          <dt class="col-sm-4">Vehicle / Crane Assigned</dt>
          <dd class="col-sm-8" id="viewVehicle"></dd>

          <dt class="col-sm-4">Job / Rental Linked</dt>
          <dd class="col-sm-8" id="viewJobLinked"></dd>

          <dt class="col-sm-4">Approved By</dt>
          <dd class="col-sm-8" id="viewApprovedBy"></dd>

          <dt class="col-sm-4">Status</dt>
          <dd class="col-sm-8" id="viewStatus"></dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- EDIT Expense Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="editExpenseForm" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">✏️ Edit Expense</h5>
          <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body row g-3">

          <div class="col-md-6">
            <label for="editDate" class="form-label">Date</label>
            <input type="date" class="form-control" id="editDate" name="expense_date" required />
          </div>

          <div class="col-md-6">
            <label for="editCategory" class="form-label">Category</label>
            <select class="form-select" id="editCategory" name="category" required>
              <option>Fuel</option>
              <option>Repair & Maintenance</option>
              <option>Toll & Parking</option>
              <option>Supplies</option>
              <option>Other</option>
            </select>
          </div>

          <div class="col-md-6">
            <label for="editVendor" class="form-label">Vendor / Payee</label>
            <input type="text" class="form-control" id="editVendor" name="vendor" required />
          </div>

          <div class="col-md-6">
            <label for="editAmount" class="form-label">Amount</label>
            <input type="number" step="0.01" min="0" class="form-control" id="editAmount" name="amount" required />
          </div>

          <div class="col-12">
            <label for="editDescription" class="form-label">Description / Remarks</label>
            <textarea class="form-control" id="editDescription" name="remarks" rows="2" required></textarea>
          </div>

          <div class="col-md-6">
            <label for="editTaxType" class="form-label">Tax Type</label>
            <select class="form-select" id="editTaxType" name="tax_type" required>
              <option>VAT (12%)</option>
              <option>Exempted</option>
              <option>Withholding (2%)</option>
              <option>No Tax</option>
            </select>
          </div>

          <div class="col-md-6">
            <label for="editReceipt" class="form-label">Attach Receipt</label>
            <input type="file" class="form-control" id="editReceipt" name="receipt_file" accept="image/*,application/pdf" />
          </div>

          <div class="col-md-6">
            <label for="editPaymentMethod" class="form-label">Payment Method</label>
            <select class="form-select" id="editPaymentMethod" name="payment_method" required>
              <option>Bank</option>
              <option>Cash</option>
              <option>Loan</option>
            </select>
          </div>

          <div class="col-md-6">
            <label for="editVehicle" class="form-label">Vehicle / Crane Assigned</label>
            <input type="text" class="form-control" id="editVehicle" name="vehicle" />
          </div>

          <div class="col-md-6">
            <label for="editJobLinked" class="form-label">Job / Rental Linked</label>
            <input type="text" class="form-control" id="editJobLinked" name="job_linked" />
          </div>

          <div class="col-md-6">
            <label for="editApprovedBy" class="form-label">Approved By</label>
            <input type="text" class="form-control" id="editApprovedBy" name="approved_by" />
          </div>

          <div class="col-md-6">
            <label for="editStatus" class="form-label">Status</label>
            <select class="form-select" id="editStatus" name="status" required>
              <option>Pending</option>
              <option>Approved</option>
              <option>Rejected</option>
            </select>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Expense</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Tax Report Modal (FIXED) -->
<div class="modal fade" id="taxReportModal" tabindex="-1" aria-labelledby="taxReportModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="taxReportModalLabel">📊 Tax Report Summary</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="taxReportContent">
        <!-- Tax report content will be dynamically generated here -->
        <div class="text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading report...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="closeTaxReportBtn">Close</button>
        <button type="button" class="btn btn-primary" id="printTaxReportBtn">Print Report</button>
      </div>
    </div>
  </div>
</div>

<!-- DELETE Expense Modal -->
<div class="modal fade" id="deleteExpenseModal" tabindex="-1" aria-labelledby="deleteExpenseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger fw-bold">🗑️ Confirm Delete</h5>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this expense record?</p>
        <p class="text-muted small">This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Yes, Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
// Fixed Tax Report Generator
document.addEventListener('DOMContentLoaded', function() {
  let taxReportModalInstance = null;
  
  // Initialize tax report modal instance
  const taxReportModalElement = document.getElementById('taxReportModal');
  if (taxReportModalElement) {
    taxReportModalInstance = new bootstrap.Modal(taxReportModalElement, {
      backdrop: true,
      keyboard: true,
      focus: true
    });
  }

  // Generate Tax Report Button Event Listener
  const generateTaxReportBtn = document.getElementById('generateTaxReportBtn');
  if (generateTaxReportBtn) {
    generateTaxReportBtn.addEventListener('click', function() {
      generateTaxReport();
    });
  }

  // Close button event listener
  const closeTaxReportBtn = document.getElementById('closeTaxReportBtn');
  if (closeTaxReportBtn) {
    closeTaxReportBtn.addEventListener('click', function() {
      if (taxReportModalInstance) {
        taxReportModalInstance.hide();
      }
    });
  }

  // Print button event listener
  const printTaxReportBtn = document.getElementById('printTaxReportBtn');
  if (printTaxReportBtn) {
    printTaxReportBtn.addEventListener('click', function() {
      printTaxReport();
    });
  }

  // Modal event listeners for proper cleanup
  if (taxReportModalElement) {
    taxReportModalElement.addEventListener('hidden.bs.modal', function() {
      // Clean up when modal is hidden
      document.body.classList.remove('modal-open');
      const backdrop = document.querySelector('.modal-backdrop');
      if (backdrop) {
        backdrop.remove();
      }
      // Reset modal content
      document.getElementById('taxReportContent').innerHTML = `
        <div class="text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading report...</p>
        </div>
      `;
    });

    taxReportModalElement.addEventListener('show.bs.modal', function() {
      // Ensure proper modal state
      document.body.style.overflow = 'hidden';
    });

    taxReportModalElement.addEventListener('shown.bs.modal', function() {
      // Focus management after modal is shown
      const closeBtn = this.querySelector('.btn-close');
      if (closeBtn) {
        closeBtn.focus();
      }
    });
  }

  function generateTaxReport() {
    const tbody = document.getElementById('expenseTableBody');
    if (!tbody) {
      alert('Expense table not found.');
      return;
    }

    const rows = tbody.querySelectorAll('tr');
    
    if (rows.length === 0) {
      alert('No expense records to generate report.');
      return;
    }

    // Initialize totals
    let totalVat = 0;
    let totalWithholding = 0;
    let totalTax = 0;
    let totalExpenses = 0;
    let exemptedCount = 0;
    let noTaxCount = 0;

    // Collect details per tax type
    rows.forEach(row => {
      const amountCell = row.querySelector('td:nth-child(6)'); // Amount column
      const taxTypeCell = row.querySelector('td:nth-child(7)'); // Tax Type column
      const taxAmountCell = row.querySelector('td:nth-child(8)'); // Tax Amount column

      if (!amountCell || !taxTypeCell || !taxAmountCell) return;

      const amount = parseFloat(amountCell.textContent.replace(/[^0-9.-]+/g, "")) || 0;
      const taxType = taxTypeCell.textContent.trim();
      const taxAmount = parseFloat(taxAmountCell.textContent.replace(/[^0-9.-]+/g, "")) || 0;

      totalExpenses += amount;
      totalTax += taxAmount;

      if (taxType.includes('VAT')) {
        totalVat += taxAmount;
      } else if (taxType.includes('Withholding')) {
        totalWithholding += taxAmount;
      } else if (taxType.includes('Exempted')) {
        exemptedCount++;
      } else if (taxType.includes('No Tax') || taxType.includes('None')) {
        noTaxCount++;
      }
    });

    // Net after tax
    const netAfterTax = totalExpenses - totalTax;

    // Format to currency string
    function formatCurrency(num) {
      return '₱' + num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    // Build comprehensive report HTML
    const reportHtml = `
      <div class="tax-report-content">
        <div class="text-center mb-4">
          <h6 class="text-muted">Generated on ${new Date().toLocaleDateString()}</h6>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <h6 class="fw-bold mb-3">📊 Summary Overview</h6>
            <table class="table table-sm table-bordered">
              <tbody>
                <tr>
                  <td>Total Records</td>
                  <td class="text-end">${rows.length}</td>
                </tr>
                <tr>
                  <td>Total Expenses</td>
                  <td class="text-end">${formatCurrency(totalExpenses)}</td>
                </tr>
                <tr class="table-warning">
                  <td><strong>Total Tax Collected</strong></td>
                  <td class="text-end"><strong>${formatCurrency(totalTax)}</strong></td>
                </tr>
                <tr class="table-success">
                  <td><strong>Net After Tax</strong></td>
                  <td class="text-end"><strong>${formatCurrency(netAfterTax)}</strong></td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div class="col-md-6">
            <h6 class="fw-bold mb-3">💰 Tax Breakdown</h6>
            <table class="table table-sm table-bordered">
              <tbody>
                <tr>
                  <td>VAT (12%)</td>
                  <td class="text-end">${formatCurrency(totalVat)}</td>
                </tr>
                <tr>
                  <td>Withholding (2%)</td>
                  <td class="text-end">${formatCurrency(totalWithholding)}</td>
                </tr>
                <tr>
                  <td>Exempted Records</td>
                  <td class="text-end">${exemptedCount}</td>
                </tr>
                <tr>
                  <td>No Tax Records</td>
                  <td class="text-end">${noTaxCount}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="mt-4">
          <h6 class="fw-bold">📈 Tax Efficiency</h6>
          <div class="progress mb-2">
            <div class="progress-bar bg-info" role="progressbar" 
                 style="width: ${totalExpenses > 0 ? (totalTax / totalExpenses * 100) : 0}%">
              ${totalExpenses > 0 ? (totalTax / totalExpenses * 100).toFixed(1) : 0}% Tax Rate
            </div>
          </div>
          <small class="text-muted">Overall tax collection rate based on total expenses</small>
        </div>
      </div>
    `;

    // Insert into modal content with smooth transition
    const contentElement = document.getElementById('taxReportContent');
    contentElement.style.opacity = '0.5';
    
    setTimeout(() => {
      contentElement.innerHTML = reportHtml;
      contentElement.style.opacity = '1';
    }, 300);

    // Show modal using the instance
    if (taxReportModalInstance) {
      taxReportModalInstance.show();
    }
  }

  function printTaxReport() {
    const reportContent = document.getElementById('taxReportContent').innerHTML;
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
        <title>Tax Report Summary</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
          body { font-family: Arial, sans-serif; margin: 20px; }
          .tax-report-content { max-width: 800px; margin: 0 auto; }
          @media print { .no-print { display: none; } }
        </style>
      </head>
      <body>
        <div class="container">
          <h2 class="text-center mb-4">Tax Report Summary</h2>
          ${reportContent}
          <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-primary">Print</button>
            <button onclick="window.close()" class="btn btn-secondary ms-2">Close</button>
          </div>
        </div>
      </body>
      </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
  }
});
</script>