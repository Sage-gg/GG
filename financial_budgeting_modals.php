<?php // financial_budgeting_modals.php - UPDATED WITH BI-WEEKLY SUPPORT ?>
<?php include 'budget_forecast_modal.php'; ?>

<!-- ADD Budget Modal -->
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
          <div class="col-md-6">
            <label class="form-label">Budget Period <span class="text-danger">*</span></label>
            <select class="form-select" name="period" id="add_period" required>
              <option disabled selected value="">Select Period</option>
              <option>Daily</option>
              <option>Bi-weekly</option>
              <option>Monthly</option>
              <option>Annually</option>
            </select>
            <small class="text-muted">Select bi-weekly for payroll budgets</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Department <span class="text-danger">*</span></label>
            <select class="form-select" name="department" id="add_department" onchange="updateCostCenter('add')" required>
              <option disabled selected value="">Select Department</option>
              <option value="HR">HR (Training, Reimbursement, Benefits & Payroll)</option>
              <option value="Core">Core (Asset & Fleet Management)</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Cost Center <span class="text-danger">*</span></label>
            <select class="form-select" name="cost_center" id="add_cost_center" required>
              <option disabled selected value="">Select Department First</option>
            </select>
            <small class="text-muted">Payroll Budget available for HR department</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Budgeted Amount <span class="text-danger">*</span></label>
            <input type="number" class="form-control" step="0.01" name="amount_allocated" id="add_amount_allocated" required placeholder="₱0.00" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Amount Used (optional)</label>
            <input type="number" class="form-control" step="0.01" name="amount_used" id="add_amount_used" value="0" placeholder="₱0.00" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Approved By</label>
            <input type="text" class="form-control" name="approved_by" id="add_approved_by" placeholder="Approver name" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Approval Status</label>
            <select class="form-select" name="approval_status" id="add_approval_status">
              <option>Pending</option>
              <option>Approved</option>
              <option>Rejected</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Description / Justification</label>
            <textarea class="form-control" name="description" rows="3" id="add_description" placeholder="Enter budget justification or description..."></textarea>
          </div>
          
          <!-- Bi-weekly Payroll Helper -->
          <div class="col-12">
            <div class="alert alert-info mb-0">
              <strong>💡 Quick Tip:</strong> For bi-weekly payroll budgets, select:
              <ul class="mb-0 mt-2">
                <li>Period: <strong>Bi-weekly</strong></li>
                <li>Department: <strong>HR</strong></li>
                <li>Cost Center: <strong>Payroll Budget</strong></li>
              </ul>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <div class="d-flex justify-content-between w-100">
            <div class="d-flex gap-2">
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
              <button type="submit" class="btn btn-success">💾 Save Budget</button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- VIEW Budget Modal -->
<div class="modal fade" id="viewBudgetModal" tabindex="-1" aria-labelledby="viewBudgetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold">📊 View Budget Allocation</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <p class="mb-2"><strong>Budget Period:</strong></p>
            <p class="ps-3" id="v_period"></p>
          </div>
          <div class="col-md-6">
            <p class="mb-2"><strong>Department:</strong></p>
            <p class="ps-3" id="v_department"></p>
          </div>
          <div class="col-md-12">
            <p class="mb-2"><strong>Cost Center:</strong></p>
            <p class="ps-3" id="v_cost_center"></p>
          </div>
          <div class="col-md-4">
            <p class="mb-2"><strong>Budgeted Amount:</strong></p>
            <p class="ps-3 text-primary fw-bold" id="v_alloc"></p>
          </div>
          <div class="col-md-4">
            <p class="mb-2"><strong>Amount Used:</strong></p>
            <p class="ps-3 text-danger fw-bold" id="v_used"></p>
          </div>
          <div class="col-md-4">
            <p class="mb-2"><strong>Difference:</strong></p>
            <p class="ps-3 fw-bold" id="v_diff"></p>
          </div>
          <div class="col-md-6">
            <p class="mb-2"><strong>Approved By:</strong></p>
            <p class="ps-3" id="v_approved_by"></p>
          </div>
          <div class="col-md-6">
            <p class="mb-2"><strong>Approval Status:</strong></p>
            <p class="ps-3" id="v_approval_status"></p>
          </div>
          <div class="col-12">
            <p class="mb-2"><strong>Description / Justification:</strong></p>
            <p class="ps-3 border-start border-3 border-info bg-light p-2" id="v_description"></p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- EDIT Budget Modal -->
<div class="modal fade" id="editBudgetModal" tabindex="-1" aria-labelledby="editBudgetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form action="budgets_actions.php" method="post">
        <input type="hidden" name="action" value="update">
        <input type="hidden" id="edit_id" name="id">
        <div class="modal-header bg-warning">
          <h5 class="modal-title fw-bold" id="editBudgetModalLabel">✏️ Edit Budget Allocation</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label class="form-label">Budget Period <span class="text-danger">*</span></label>
            <select class="form-select" name="period" id="edit_period" required>
              <option>Daily</option>
              <option>Bi-weekly</option>
              <option>Monthly</option>
              <option>Annually</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Department <span class="text-danger">*</span></label>
            <select class="form-select" name="department" id="edit_department" onchange="updateCostCenter('edit')" required>
              <option value="HR">HR (Training, Reimbursement, Benefits & Payroll)</option>
              <option value="Core">Core (Asset & Fleet Management)</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Cost Center <span class="text-danger">*</span></label>
            <select class="form-select" name="cost_center" id="edit_cost_center" required>
              <!-- Options populated by JavaScript based on department selection -->
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Budgeted Amount <span class="text-danger">*</span></label>
            <input type="number" class="form-control" step="0.01" name="amount_allocated" id="edit_amount_allocated" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Amount Used</label>
            <input type="number" class="form-control" step="0.01" name="amount_used" id="edit_amount_used" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Approved By</label>
            <input type="text" class="form-control" name="approved_by" id="edit_approved_by" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Approval Status</label>
            <select class="form-select" name="approval_status" id="edit_approval_status">
              <option>Pending</option>
              <option>Approved</option>
              <option>Rejected</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Description / Justification</label>
            <textarea class="form-control" name="description" rows="3" id="edit_description"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <div class="d-flex justify-content-between w-100">
            <div class="d-flex gap-2">
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
              <button type="submit" class="btn btn-primary">💾 Update Budget</button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- DELETE Budget Modal -->
<div class="modal fade" id="deleteBudgetModal" tabindex="-1" aria-labelledby="deleteBudgetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="budgets_actions.php" method="post">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" id="delete_id" name="id">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title fw-bold">⚠️ Confirm Delete</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning">
            <strong>Warning:</strong> This action cannot be undone!
          </div>
          <p>Are you sure you want to delete <strong id="delete_name">this budget allocation</strong>?</p>
          <p class="text-muted small mb-0">
            All budget data, including allocation amounts and historical records, will be permanently removed.
          </p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
          <button class="btn btn-danger" type="submit">🗑️ Yes, Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>
