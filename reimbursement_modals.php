<?php // reimbursement_modals.php ?>

<!-- Add Reimbursement Modal -->
<div class="modal fade" id="addReimbursementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="reimbursement_actions.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">Submit Reimbursement Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Employee Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="employee_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Employee ID</label>
                            <input type="text" class="form-control" name="employee_id">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="address" rows="2" required 
                                      placeholder="Enter complete address..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="contact_no" required 
                                   placeholder="e.g., +63 912 345 6789">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department <span class="text-danger">*</span></label>
                            <select class="form-select" name="department" id="add_dept" onchange="updateReimbursementCostCenter('add')" required>
                                <option value="">Select Department</option>
                                <option value="HR">HR</option>
                                <option value="Core">Core</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cost Center <span class="text-danger">*</span></label>
                            <select class="form-select" name="cost_center" id="add_cost_center" required>
                                <option value="">Select Department First</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reimbursement Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="reimbursement_type" required>
                                <option value="">Select Type</option>
                                <option value="Training Course">Training Course</option>
                                <option value="Medical Expenses">Medical Expenses</option>
                                <option value="Transportation">Transportation</option>
                                <option value="Meal Allowance">Meal Allowance</option>
                                <option value="Office Supplies">Office Supplies</option>
                                <option value="Business Travel">Business Travel</option>
                                <option value="Communication">Communication</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expense Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="expense_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Receipt/Proof (Optional)</label>
                            <input type="file" class="form-control" name="receipt_file" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description/Purpose <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="description" rows="3" required 
                                      placeholder="Describe the expense and its business purpose..."></textarea>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> <strong>Note:</strong> 
                                All reimbursement requests require manager approval. Please attach valid receipts for amounts over ₱1,000.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Reimbursement Modal -->
<div class="modal fade" id="viewReimbursementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reimbursement Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Employee Name</label>
                        <div class="fw-bold" id="v_employee_name">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Employee ID</label>
                        <div id="v_employee_id">-</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted small">Address</label>
                        <div id="v_address">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Contact Number</label>
                        <div id="v_contact_no">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Department</label>
                        <div id="v_department">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Cost Center</label>
                        <div id="v_cost_center">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Reimbursement Type</label>
                        <div id="v_type">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Amount</label>
                        <div class="text-primary fw-bold" id="v_amount">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Expense Date</label>
                        <div id="v_expense_date">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Submission Date</label>
                        <div id="v_submission_date">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Receipt File</label>
                        <div id="v_receipt_file">-</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted small">Description</label>
                        <div id="v_description">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Status</label>
                        <div id="v_status">-</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Approved By</label>
                        <div id="v_approved_by">-</div>
                    </div>
                    <div class="col-12" id="remarks_section" style="display:none;">
                        <label class="form-label text-muted small">Remarks</label>
                        <div id="v_remarks">-</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="approve_btn" onclick="approveReimbursement()" style="display:none;">
                    <i class="bi bi-check-circle"></i> Approve
                </button>
                <button type="button" class="btn btn-danger" id="reject_btn" onclick="rejectReimbursement()" style="display:none;">
                    <i class="bi bi-x-circle"></i> Reject
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Reimbursement Modal -->
<div class="modal fade" id="editReimbursementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="reimbursement_actions.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Reimbursement Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Employee Name</label>
                            <input type="text" class="form-control" name="employee_name" id="edit_employee_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Employee ID</label>
                            <input type="text" class="form-control" name="employee_id" id="edit_employee_id">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="edit_address" rows="2" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Number</label>
                            <input type="text" class="form-control" name="contact_no" id="edit_contact_no" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select class="form-select" name="department" id="edit_dept" onchange="updateReimbursementCostCenter('edit')" required>
                                <option value="HR">HR</option>
                                <option value="Core">Core</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cost Center</label>
                            <select class="form-select" name="cost_center" id="edit_cost_center" required>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reimbursement Type</label>
                            <select class="form-select" name="reimbursement_type" id="edit_type" required>
                                <option value="Training Course">Training Course</option>
                                <option value="Medical Expenses">Medical Expenses</option>
                                <option value="Transportation">Transportation</option>
                                <option value="Meal Allowance">Meal Allowance</option>
                                <option value="Office Supplies">Office Supplies</option>
                                <option value="Business Travel">Business Travel</option>
                                <option value="Communication">Communication</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <input type="number" class="form-control" name="amount" id="edit_amount" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expense Date</label>
                            <input type="date" class="form-control" name="expense_date" id="edit_expense_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Update Receipt (Optional)</label>
                            <input type="file" class="form-control" name="receipt_file" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Leave blank to keep existing file</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Reimbursement Modal -->
<div class="modal fade" id="deleteReimbursementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="reimbursement_actions.php" method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the reimbursement request for <strong id="delete_name"></strong>?
                    This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
