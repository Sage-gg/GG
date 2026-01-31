<!-- Add Journal Entry Modal -->
<div class="modal fade" id="addJournalEntryModal" tabindex="-1" aria-labelledby="addJournalEntryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="addJournalEntryForm">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Add Journal Entry</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label class="form-label">Date</label>
            <input id="add_journal_date" name="date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Reference #</label>
            <input id="add_journal_reference" name="reference" type="text" class="form-control" placeholder="Auto-generated" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Account</label>
            <select id="add_journal_account" name="account_code" class="form-select" required>
              <option disabled selected>Select Account</option>
              <!-- Will be populated by JavaScript -->
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Amount</label>
            <input id="add_journal_amount" name="amount" type="number" class="form-control" step="0.01" min="0.01" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Type</label>
            <select id="add_journal_type" name="type" class="form-select" required>
              <option value="debit">Debit</option>
              <option value="credit">Credit</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Source Module</label>
            <select id="add_journal_source_module" name="source_module" class="form-select" required>
              <option value="" disabled selected>Select Source Module</option>
              <option value="Manual Entry">Manual Entry</option>
              <option value="Payroll">Payroll</option>
              <option value="Procurement">Procurement</option>
              <option value="Liquidation">Liquidation</option>
              <option value="Inventory">Inventory</option>
              <option value="Sales">Sales</option>
              <option value="Expenses">Expenses</option>
              <option value="Adjustments">Adjustments</option>
              <option value="Bank Reconciliation">Bank Reconciliation</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea id="add_journal_description" name="description" class="form-control" rows="2" placeholder="Enter transaction description..." required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save Entry</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Journal Entry Modal -->
<div class="modal fade" id="editJournalEntryModal" tabindex="-1" aria-labelledby="editJournalEntryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="editJournalEntryForm">
        <input type="hidden" id="edit_journal_id" name="id">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Edit Journal Entry</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label class="form-label">Date</label>
            <input id="edit_journal_date" name="date" type="date" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Reference #</label>
            <input id="edit_journal_reference" name="reference" type="text" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Account</label>
            <select id="edit_journal_account" name="account_code" class="form-select" required>
              <option disabled>Select Account</option>
              <!-- Will be populated by JavaScript -->
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Amount</label>
            <input id="edit_journal_amount" name="amount" type="number" class="form-control" step="0.01" min="0.01" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Type</label>
            <select id="edit_journal_type" name="type" class="form-select" required>
              <option value="debit">Debit</option>
              <option value="credit">Credit</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Source Module</label>
            <select id="edit_journal_source_module" name="source_module" class="form-select" required>
              <option value="" disabled>Select Source Module</option>
              <option value="Manual Entry">Manual Entry</option>
              <option value="Payroll">Payroll</option>
              <option value="Procurement">Procurement</option>
              <option value="Liquidation">Liquidation</option>
              <option value="Inventory">Inventory</option>
              <option value="Sales">Sales</option>
              <option value="Expenses">Expenses</option>
              <option value="Adjustments">Adjustments</option>
              <option value="Bank Reconciliation">Bank Reconciliation</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea id="edit_journal_description" name="description" class="form-control" rows="2" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Update Entry</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- View Journal Entry Modal -->
<div class="modal fade" id="viewJournalModal" tabindex="-1" aria-labelledby="viewJournalModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">View Journal Entry</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-borderless">
          <tbody>
            <tr><th style="width:180px">Date</th><td id="view_journal_date">-</td></tr>
            <tr><th>Entry ID</th><td id="view_journal_entry_id">-</td></tr>
            <tr><th>Reference</th><td id="view_journal_reference">-</td></tr>
            <tr><th>Account</th><td id="view_journal_account">-</td></tr>
            <tr><th>Account Code</th><td id="view_journal_account_code">-</td></tr>
            <tr><th>Description</th><td id="view_journal_description">-</td></tr>
            <tr><th>Debit</th><td id="view_journal_debit">-</td></tr>
            <tr><th>Credit</th><td id="view_journal_credit">-</td></tr>
            <tr><th>Source Module</th><td id="view_journal_source">-</td></tr>
            <tr><th>Status</th><td id="view_journal_status">-</td></tr>
            <tr><th>Approved By</th><td id="view_journal_approved">-</td></tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="addAccountForm">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Add New Account</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label class="form-label">Account Code</label>
            <input id="add_account_code" name="account_code" type="text" class="form-control" placeholder="e.g., 5005" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Account Name</label>
            <input id="add_account_name" name="account_name" type="text" class="form-control" placeholder="e.g., Office Rent" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Account Type</label>
            <select id="add_account_type" name="account_type" class="form-select" required>
              <option disabled selected>Select Type</option>
              <option value="Asset">Asset</option>
              <option value="Liability">Liability</option>
              <option value="Equity">Equity</option>
              <option value="Revenue">Revenue</option>
              <option value="Expense">Expense</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea id="add_account_description" name="description" class="form-control" rows="2" placeholder="Account description..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- View Account Modal -->
<div class="modal fade" id="viewAccountModal" tabindex="-1" aria-labelledby="viewAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">View Account</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-borderless">
          <tbody>
            <tr><th style="width:180px">Account Code</th><td id="view_account_code">-</td></tr>
            <tr><th>Account Name</th><td id="view_account_name">-</td></tr>
            <tr><th>Account Type</th><td id="view_account_type">-</td></tr>
            <tr><th>Description</th><td id="view_account_description">-</td></tr>
            <tr><th>Status</th><td id="view_account_status">-</td></tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Account Modal -->
<div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="editAccountForm">
        <input type="hidden" id="edit_account_original_code" name="original_code">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Edit Account</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label class="form-label">Account Code</label>
            <input id="edit_account_code" name="account_code" type="text" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Account Name</label>
            <input id="edit_account_name" name="account_name" type="text" class="form-control" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Account Type</label>
            <select id="edit_account_type" name="account_type" class="form-select" required>
              <option disabled>Select Type</option>
              <option value="Asset">Asset</option>
              <option value="Liability">Liability</option>
              <option value="Equity">Equity</option>
              <option value="Revenue">Revenue</option>
              <option value="Expense">Expense</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea id="edit_account_description" name="description" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Update Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add Liquidation Record Modal -->
<div class="modal fade" id="addLiquidationModal" tabindex="-1" aria-labelledby="addLiquidationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="addLiquidationForm" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Add Liquidation Record</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label class="form-label">Date</label>
            <input id="add_liq_date" name="date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Liquidation ID</label>
            <input id="add_liq_id" name="liquidation_id" type="text" class="form-control" placeholder="Auto-generated" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Employee</label>
            <input id="add_liq_employee" name="employee" type="text" class="form-control" placeholder="Employee Name" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Purpose</label>
            <input id="add_liq_purpose" name="purpose" type="text" class="form-control" placeholder="Purpose of liquidation" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Total Amount (₱)</label>
            <input id="add_liq_amount" name="total_amount" type="number" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Status</label>
            <select id="add_liq_status" name="status" class="form-select" required>
              <option value="Pending" selected>Pending</option>
              <option value="Approved">Approved</option>
              <option value="Rejected">Rejected</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Attach Receipt</label>
            <input id="add_liq_receipt" name="receipt" type="file" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf">
            <small class="text-muted">Accepted formats: JPG, PNG, GIF, PDF (Max 5MB)</small>
          </div>
          <div class="col-12" id="add_receipt_preview" style="display:none;">
            <div class="alert alert-info d-flex align-items-center">
              <i class="bi bi-file-earmark-text me-2"></i>
              <span id="add_receipt_filename"></span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save Record</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Liquidation Record Modal -->
<div class="modal fade" id="editLiquidationModal" tabindex="-1" aria-labelledby="editLiquidationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="editLiquidationForm" enctype="multipart/form-data">
        <input type="hidden" id="edit_liq_record_id" name="id">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Edit Liquidation Record</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label class="form-label">Date</label>
            <input id="edit_liq_date" name="date" type="date" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Liquidation ID</label>
            <input id="edit_liq_id" name="liquidation_id" type="text" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Employee</label>
            <input id="edit_liq_employee" name="employee" type="text" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Purpose</label>
            <input id="edit_liq_purpose" name="purpose" type="text" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Total Amount (₱)</label>
            <input id="edit_liq_amount" name="total_amount" type="number" class="form-control" step="0.01" min="0.01" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Status</label>
            <select id="edit_liq_status" name="status" class="form-select" required>
              <option value="Pending">Pending</option>
              <option value="Approved">Approved</option>
              <option value="Rejected">Rejected</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Current Receipt</label>
            <div id="edit_current_receipt" class="mb-2"></div>
            <label class="form-label">Replace Receipt</label>
            <input id="edit_liq_receipt" name="receipt" type="file" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf">
            <small class="text-muted">Leave empty to keep current receipt. Accepted formats: JPG, PNG, GIF, PDF (Max 5MB)</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Update Record</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- View Liquidation Modal -->
<div class="modal fade" id="viewLiquidationModal" tabindex="-1" aria-labelledby="viewLiquidationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">View Liquidation Record</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-borderless">
          <tbody>
            <tr><th style="width:180px">Date</th><td id="view_liq_date">-</td></tr>
            <tr><th>Liquidation ID</th><td id="view_liq_id">-</td></tr>
            <tr><th>Employee</th><td id="view_liq_employee">-</td></tr>
            <tr><th>Purpose</th><td id="view_liq_purpose">-</td></tr>
            <tr><th>Total Amount</th><td id="view_liq_amount">-</td></tr>
            <tr><th>Status</th><td id="view_liq_status">-</td></tr>
            <tr><th>Receipt</th><td id="view_liq_receipt">-</td></tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Receipt Viewer Modal -->
<div class="modal fade" id="receiptViewerModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Receipt</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center p-4" style="min-height: 400px; max-height: 80vh; overflow: auto;">
        <div id="receiptViewerContent"></div>
      </div>
      <div class="modal-footer">
        <a id="receiptDownloadLink" href="#" target="_blank" class="btn btn-primary" download>
          <i class="bi bi-download"></i> Download
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
