<?php // financial_budgeting_modals.php – UPDATED WITH REJECT MODAL ?>
<?php include 'budget_forecast_modal.php'; ?>

<!-- ── ADD Budget Modal ──────────────────────────────────────────────────── -->
<div class="modal fade" id="addBudgetModal" tabindex="-1" aria-labelledby="addBudgetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form action="budgets_actions.php" method="post">
        <input type="hidden" name="action" value="create">

        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="addBudgetModalLabel">Add Budget Allocation</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body row g-3">
          <!-- Period -->
          <div class="col-md-6">
            <label class="form-label">Budget Period <span class="text-danger">*</span></label>
            <select class="form-select" name="period" id="add_period" required>
              <option disabled selected value="">Select Period</option>
              <option>Daily</option>
              <option>Bi-weekly</option>
              <option>Monthly</option>
              <option>Annually</option>
            </select>
            <small class="text-muted">Select <strong>Bi-weekly</strong> for payroll budgets.</small>
          </div>

          <!-- Department -->
          <div class="col-md-6">
            <label class="form-label">Department <span class="text-danger">*</span></label>
            <select class="form-select" name="department" id="add_department" onchange="updateCostCenter('add')" required>
              <option disabled selected value="">Select Department</option>
              <option value="HR">HR (Training, Reimbursement, Benefits &amp; Payroll)</option>
              <option value="Core">Core (Asset &amp; Fleet Management)</option>
            </select>
          </div>

          <!-- Cost Center -->
          <div class="col-md-6">
            <label class="form-label">Cost Center <span class="text-danger">*</span></label>
            <select class="form-select" name="cost_center" id="add_cost_center" required>
              <option disabled selected value="">Select Department First</option>
            </select>
            <small class="text-muted">Payroll Budget is available under HR.</small>
          </div>

          <!-- Budgeted Amount -->
          <div class="col-md-6">
            <label class="form-label">Budgeted Amount <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">₱</span>
              <input type="number" class="form-control" step="0.01" min="0" name="amount_allocated" id="add_amount_allocated" required placeholder="0.00" />
            </div>
          </div>

          <!-- Amount Used -->
          <div class="col-md-6">
            <label class="form-label">Amount Used <span class="text-muted">(optional)</span></label>
            <div class="input-group">
              <span class="input-group-text">₱</span>
              <input type="number" class="form-control" step="0.01" min="0" name="amount_used" id="add_amount_used" value="0" placeholder="0.00" />
            </div>
          </div>

          <!-- Approved By -->
          <div class="col-md-6">
            <label class="form-label">Approved By</label>
            <input type="text" class="form-control" name="approved_by" id="add_approved_by" placeholder="Approver name" />
          </div>

          <!-- Approval Status -->
          <div class="col-md-6">
            <label class="form-label">Approval Status</label>
            <select class="form-select" name="approval_status" id="add_approval_status">
              <option>Pending</option>
              <option>Approved</option>
              <option>Rejected</option>
            </select>
          </div>

          <!-- Description -->
          <div class="col-12">
            <label class="form-label">Description / Justification</label>
            <textarea class="form-control" name="description" rows="3" id="add_description" placeholder="Enter budget justification or description…"></textarea>
          </div>

          <!-- Quick-tip for bi-weekly payroll -->
          <div class="col-12">
            <div class="alert alert-info mb-0">
              <strong>💡 Quick Tip:</strong> For bi-weekly payroll budgets, select:
              <ul class="mb-0 mt-2">
                <li>Period: <strong>Bi-weekly</strong></li>
                <li>Department: <strong>HR</strong></li>
                <li>Cost Center: <strong>Payroll Budget</strong></li>
              </ul>
              After saving, use the <strong>Allocate</strong> button on the row to split the budget into specific cost items.
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
          <button type="submit" class="btn btn-success"><i class="bi bi-floppy-disk me-1"></i>Save Budget</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── VIEW Budget Modal ────────────────────────────────────────────────── -->
<div class="modal fade" id="viewBudgetModal" tabindex="-1" aria-labelledby="viewBudgetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold"><i class="bi bi-eye me-2"></i>View Budget Allocation</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <p class="mb-1 text-muted small">Budget Period</p>
            <p class="fw-semibold" id="v_period">–</p>
          </div>
          <div class="col-md-6">
            <p class="mb-1 text-muted small">Department</p>
            <p class="fw-semibold" id="v_department">–</p>
          </div>
          <div class="col-12">
            <p class="mb-1 text-muted small">Cost Center</p>
            <p class="fw-semibold" id="v_cost_center">–</p>
          </div>

          <!-- Amounts row -->
          <div class="col-md-4">
            <div class="card card-body bg-light text-center p-2">
              <div class="text-muted small">Budgeted Amount</div>
              <div class="fw-bold text-primary fs-5" id="v_alloc">–</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card card-body bg-light text-center p-2">
              <div class="text-muted small">Amount Used</div>
              <div class="fw-bold text-danger fs-5" id="v_used">–</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card card-body bg-light text-center p-2">
              <div class="text-muted small">Difference</div>
              <div class="fw-bold fs-5" id="v_diff">–</div>
            </div>
          </div>

          <div class="col-md-6">
            <p class="mb-1 text-muted small">Approved By</p>
            <p class="fw-semibold" id="v_approved_by">–</p>
          </div>
          <div class="col-md-6">
            <p class="mb-1 text-muted small">Approval Status</p>
            <p id="v_approval_status">–</p>
          </div>
          <div class="col-12">
            <p class="mb-1 text-muted small">Description / Justification</p>
            <p class="border-start border-3 border-info bg-light p-2 mb-0" id="v_description">–</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- ── EDIT Budget Modal ────────────────────────────────────────────────── -->
<div class="modal fade" id="editBudgetModal" tabindex="-1" aria-labelledby="editBudgetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form action="budgets_actions.php" method="post">
        <input type="hidden" name="action" value="update">
        <input type="hidden" id="edit_id" name="id">

        <div class="modal-header bg-warning">
          <h5 class="modal-title fw-bold" id="editBudgetModalLabel"><i class="bi bi-pencil me-2"></i>Edit Budget Allocation</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body row g-3">
          <!-- Period -->
          <div class="col-md-6">
            <label class="form-label">Budget Period <span class="text-danger">*</span></label>
            <select class="form-select" name="period" id="edit_period" required>
              <option>Daily</option>
              <option>Bi-weekly</option>
              <option>Monthly</option>
              <option>Annually</option>
            </select>
          </div>

          <!-- Department -->
          <div class="col-md-6">
            <label class="form-label">Department <span class="text-danger">*</span></label>
            <select class="form-select" name="department" id="edit_department" onchange="updateCostCenter('edit')" required>
              <option value="HR">HR (Training, Reimbursement, Benefits &amp; Payroll)</option>
              <option value="Core">Core (Asset &amp; Fleet Management)</option>
            </select>
          </div>

          <!-- Cost Center -->
          <div class="col-md-6">
            <label class="form-label">Cost Center <span class="text-danger">*</span></label>
            <select class="form-select" name="cost_center" id="edit_cost_center" required>
              <!-- populated by JS -->
            </select>
          </div>

          <!-- Budgeted Amount -->
          <div class="col-md-6">
            <label class="form-label">Budgeted Amount <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">₱</span>
              <input type="number" class="form-control" step="0.01" min="0" name="amount_allocated" id="edit_amount_allocated" required />
            </div>
          </div>

          <!-- Amount Used -->
          <div class="col-md-6">
            <label class="form-label">Amount Used</label>
            <div class="input-group">
              <span class="input-group-text">₱</span>
              <input type="number" class="form-control" step="0.01" min="0" name="amount_used" id="edit_amount_used" />
            </div>
          </div>

          <!-- Approved By -->
          <div class="col-md-6">
            <label class="form-label">Approved By</label>
            <input type="text" class="form-control" name="approved_by" id="edit_approved_by" />
          </div>

          <!-- Approval Status -->
          <div class="col-md-6">
            <label class="form-label">Approval Status</label>
            <select class="form-select" name="approval_status" id="edit_approval_status">
              <option>Pending</option>
              <option>Approved</option>
              <option>Rejected</option>
            </select>
          </div>

          <!-- Description -->
          <div class="col-12">
            <label class="form-label">Description / Justification</label>
            <textarea class="form-control" name="description" rows="3" id="edit_description"></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-floppy-disk me-1"></i>Update Budget</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── DELETE Budget Modal ──────────────────────────────────────────────── -->
<div class="modal fade" id="deleteBudgetModal" tabindex="-1" aria-labelledby="deleteBudgetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="budgets_actions.php" method="post">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" id="delete_id" name="id">

        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning">
            <strong>Warning:</strong> This action cannot be undone!
          </div>
          <p>Are you sure you want to delete <strong id="delete_name">this budget allocation</strong>?</p>
          <p class="text-muted small mb-0">All allocation line items linked to this budget will also be permanently removed.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
          <button class="btn btn-danger" type="submit"><i class="bi bi-trash me-1"></i>Yes, Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── REJECT Budget Modal - NEW ──────────────────────────────────────────────── -->
<div class="modal fade" id="rejectBudgetModal" tabindex="-1" aria-labelledby="rejectBudgetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="budgets_actions.php" method="post" id="rejectBudgetForm">
        <input type="hidden" name="action" value="quick_reject">
        <input type="hidden" id="reject_id" name="id">

        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title fw-bold"><i class="bi bi-x-circle me-2"></i>Reject Budget Request</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        
        <div class="modal-body">
          <div class="alert alert-warning">
            <strong>⚠️ Warning:</strong> This will reject the budget request and notify the department.
          </div>
          
          <p>Are you sure you want to reject the budget request for <strong id="reject_name">this budget</strong>?</p>
          
          <div class="mb-3">
            <label class="form-label fw-bold">Reason for Rejection <span class="text-danger">*</span></label>
            <textarea class="form-control" name="rejection_reason" id="rejection_reason" rows="4" 
                      required placeholder="Please provide a clear reason for rejecting this budget request..."></textarea>
            <small class="text-muted">This reason will be shared with the requesting department.</small>
          </div>
        </div>
        
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
          <button class="btn btn-danger" type="submit">
            <i class="bi bi-x-circle me-1"></i>Yes, Reject Budget
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
