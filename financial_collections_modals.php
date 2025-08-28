<!-- ADD Collection Modal -->
<div class="modal fade" id="addCollectionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="addCollectionForm">
        <div class="modal-header">
          <h5 class="modal-title">➕ Add Collection</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label class="form-label">Client Name</label>
            <input type="text" class="form-control" name="client_name" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Job Order / Invoice No.</label>
            <input type="text" class="form-control" name="invoice_no" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Billing Date</label>
            <input type="date" class="form-control" name="billing_date" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Due Date</label>
            <input type="date" class="form-control" id="add_due_date" name="due_date" required>
          </div>

          <!-- Amounts + VAT -->
          <div class="col-md-6">
            <label class="form-label">Amount (Before VAT)</label>
            <input type="number" step="0.01" min="0" class="form-control money" id="add_amount_base" name="amount_base" required>
            <div class="small-muted mt-1" id="add_vat_info">VAT: — | Total Due: ₱0.00</div>
            <!-- Hidden fields the server also expects (but will recompute) -->
            <input type="hidden" id="add_vat_amount" name="vat_amount" value="0">
            <input type="hidden" id="add_amount_due" name="amount_due" value="0">
          </div>

          <div class="col-md-6">
            <label class="form-label">Amount Paid</label>
            <input type="number" step="0.01" min="0" class="form-control money" id="add_amount_paid" name="amount_paid" value="0">
          </div>

          <div class="col-md-6">
            <label class="form-label">Penalty (Auto-calculated)</label>
            <input type="number" step="0.01" min="0" class="form-control money" id="add_penalty" name="penalty" value="0" readonly>
            <div class="small-muted mt-1" id="add_penalty_info">Enter due date, payment status, and amounts to see penalty calculation</div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Mode of Payment</label>
            <select class="form-select" name="mode_of_payment" required>
              <option disabled selected value="">Select</option>
              <option>Cash</option>
              <option>Bank Transfer</option>
              <option>Cheque</option>
              <option>Online</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Payment Status</label>
            <select class="form-select" id="add_payment_status" name="payment_status" required>
              <option disabled selected value="">Select</option>
              <option>Unpaid</option>
              <option>Partial</option>
              <option>Paid</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">VAT Applied?</label>
            <select class="form-select" id="add_vat_applied" name="vat_applied">
              <option>No</option>
              <option>Yes</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Receipt Type</label>
            <select class="form-select" name="receipt_type">
              <option>Acknowledgment</option>
              <option>VAT Receipt</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Collector Name</label>
            <input type="text" class="form-control" name="collector_name" required>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
          <button type="submit" class="btn btn-success">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- VIEW Modal -->
<div class="modal fade" id="viewCollectionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">📄 View Collection</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <!-- Filled dynamically by JS (openView) -->
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

<!-- EDIT Modal -->
<div class="modal fade" id="editCollectionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="editCollectionForm">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-header"><h5 class="modal-title">✏️ Edit Collection</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>

        <div class="modal-body row g-3">
          <div class="col-md-6"><label class="form-label">Client Name</label><input type="text" class="form-control" id="edit_client_name" name="client_name" required></div>
          <div class="col-md-6"><label class="form-label">Job Order / Invoice No.</label><input type="text" class="form-control" id="edit_invoice_no" name="invoice_no" required></div>
          <div class="col-md-6"><label class="form-label">Billing Date</label><input type="date" class="form-control" id="edit_billing_date" name="billing_date" required></div>
          <div class="col-md-6"><label class="form-label">Due Date</label><input type="date" class="form-control" id="edit_due_date" name="due_date" required></div>

          <div class="col-md-6">
            <label class="form-label">Amount (Before VAT)</label>
            <input type="number" step="0.01" min="0" class="form-control money" id="edit_amount_base" name="amount_base" required>
            <div class="small-muted mt-1" id="edit_vat_info">VAT: — | Total Due: ₱0.00</div>
            <input type="hidden" id="edit_vat_amount" name="vat_amount" value="0">
            <input type="hidden" id="edit_amount_due" name="amount_due" value="0">
          </div>

          <div class="col-md-6"><label class="form-label">Amount Paid</label><input type="number" step="0.01" min="0" class="form-control money" id="edit_amount_paid" name="amount_paid" value="0"></div>
          
          <div class="col-md-6">
            <label class="form-label">Penalty (Auto-calculated)</label>
            <input type="number" step="0.01" min="0" class="form-control money" id="edit_penalty" name="penalty" value="0" readonly>
            <div class="small-muted mt-1" id="edit_penalty_info">Enter due date, payment status, and amounts to see penalty calculation</div>
          </div>

          <div class="col-md-6"><label class="form-label">Mode of Payment</label>
            <select class="form-select" id="edit_mode_of_payment" name="mode_of_payment" required>
              <option>Cash</option>
              <option>Bank Transfer</option>
              <option>Cheque</option>
              <option>Online</option>
            </select>
          </div>

          <div class="col-md-6"><label class="form-label">Payment Status</label>
            <select class="form-select" id="edit_payment_status" name="payment_status" required>
              <option>Unpaid</option>
              <option>Partial</option>
              <option>Paid</option>
            </select>
          </div>

          <div class="col-md-6"><label class="form-label">VAT Applied?</label>
            <select class="form-select" id="edit_vat_applied" name="vat_applied">
              <option>No</option>
              <option>Yes</option>
            </select>
          </div>

          <div class="col-md-6"><label class="form-label">Receipt Type</label>
            <select class="form-select" id="edit_receipt_type" name="receipt_type">
              <option>Acknowledgment</option>
              <option>VAT Receipt</option>
            </select>
          </div>

          <div class="col-md-6"><label class="form-label">Collector Name</label><input type="text" class="form-control" id="edit_collector_name" name="collector_name" required></div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
          <button type="submit" class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>