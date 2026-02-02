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
              <option value="VAT 12%">VAT 12%</option>
              <option value="Withholding Tax 1%">Withholding Tax 1%</option>
              <option value="Withholding Tax 2%">Withholding Tax 2%</option>
              <option value="Withholding Tax 5%">Withholding Tax 5%</option>
              <option value="Withholding Tax 10%">Withholding Tax 10%</option>
              <option value="None">None</option>
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

          <input type="hidden" id="editExpenseId" name="id" />

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
              <option value="VAT 12%">VAT 12%</option>
              <option value="Withholding Tax 1%">Withholding Tax 1%</option>
              <option value="Withholding Tax 2%">Withholding Tax 2%</option>
              <option value="Withholding Tax 5%">Withholding Tax 5%</option>
              <option value="Withholding Tax 10%">Withholding Tax 10%</option>
              <option value="None">None</option>
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
