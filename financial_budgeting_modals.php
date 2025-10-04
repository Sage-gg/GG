<?php // financial_budgeting_modals.php - UPDATED - COMPLETE VERSION ?>
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
            <label class="form-label">Budget Period</label>
            <select class="form-select" name="period" id="add_period" required>
              <option disabled selected value="">Select Period</option>
              <option>Annually</option>
              <option>Monthly</option>
              <option>Daily</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Department</label>
            <select class="form-select" name="department" id="add_department" onchange="updateCostCenter('add')" required>
              <option disabled selected value="">Select Department</option>
              <option value="HR">HR (Training, Reimbursement & Benefits)</option>
              <option value="Core">Core (Asset & Fleet Management)</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Cost Center</label>
            <select class="form-select" name="cost_center" id="add_cost_center" required>
              <option disabled selected value="">Select Department First</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Budgeted Amount</label>
            <input type="number" class="form-control" step="0.01" name="amount_allocated" id="add_amount_allocated" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Amount Used (optional)</label>
            <input type="number" class="form-control" step="0.01" name="amount_used" id="add_amount_used" value="0" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Approved By</label>
            <input type="text" class="form-control" name="approved_by" id="add_approved_by" />
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
            <textarea class="form-control" name="description" rows="3" id="add_description"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <div class="d-flex justify-content-between w-100">
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-info btn-sm" onclick="notifyDepartment('add')" title="Send notification to department">
                Notify Department
              </button>
              <button type="button" class="btn btn-warning btn-sm" onclick="forwardToAdmin('add')" title="Forward request to admin">
                Forward to Admin
              </button>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
              <button type="submit" class="btn btn-success">Save Budget</button>
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
      <div class="modal-header">
        <h5 class="modal-title fw-bold">View Budget Allocation</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><strong>Budget Period:</strong> <span id="v_period"></span></p>
        <p><strong>Department:</strong> <span id="v_department"></span></p>
        <p><strong>Cost Center:</strong> <span id="v_cost_center"></span></p>
        <p><strong>Budgeted Amount:</strong> <span id="v_alloc"></span></p>
        <p><strong>Amount Used:</strong> <span id="v_used"></span></p>
        <p><strong>Difference:</strong> <span id="v_diff"></span></p>
        <p><strong>Approved By:</strong> <span id="v_approved_by"></span></p>
        <p><strong>Approval Status:</strong> <span id="v_approval_status"></span></p>
        <p><strong>Description / Justification:</strong> <span id="v_description"></span></p>
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
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="editBudgetModalLabel">Edit Budget Allocation</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label class="form-label">Budget Period</label>
            <select class="form-select" name="period" id="edit_period">
              <option>Annually</option>
              <option>Monthly</option>
              <option>Daily</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Department</label>
            <select class="form-select" name="department" id="edit_department" onchange="updateCostCenter('edit')">
              <option value="HR">HR (Training, Reimbursement & Benefits)</option>
              <option value="Core">Core (Asset & Fleet Management)</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Cost Center</label>
            <select class="form-select" name="cost_center" id="edit_cost_center">
              <!-- Options populated by JavaScript based on department selection -->
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Budgeted Amount</label>
            <input type="number" class="form-control" step="0.01" name="amount_allocated" id="edit_amount_allocated" />
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
              <button type="button" class="btn btn-info btn-sm" onclick="notifyDepartment('edit')" title="Send notification to department">
                Notify Department
              </button>
              <button type="button" class="btn btn-warning btn-sm" onclick="forwardToAdmin('edit')" title="Forward request to admin">
                Forward to Admin
              </button>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
              <button type="submit" class="btn btn-primary">Update Budget</button>
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
        <div class="modal-header">
          <h5 class="modal-title fw-bold text-danger">Confirm Delete</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete <strong id="delete_name">this budget allocation</strong>? This action cannot be undone.
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
          <button class="btn btn-danger" type="submit">Yes, Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>
