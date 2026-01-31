// reimbursement_scripts.js

// Department to Cost Center mapping
const reimbursementCostCenters = {
    'HR': ['Training Budget', 'Reimbursement Budget', 'Benefits Budget'],
    'Core': ['Log Maintenance Costs', 'Depreciation Charges', 'Insurance Fees', 'Vehicle Operational Budget']
};

let currentReimbursementId = null;

function updateReimbursementCostCenter(prefix) {
    const deptSelect = document.getElementById(prefix + '_dept');
    const costCenterSelect = document.getElementById(prefix + '_cost_center');
    
    if (!deptSelect || !costCenterSelect) return;
    
    const selectedDept = deptSelect.value;
    costCenterSelect.innerHTML = '';
    
    if (selectedDept && reimbursementCostCenters[selectedDept]) {
        reimbursementCostCenters[selectedDept].forEach(cc => {
            const option = document.createElement('option');
            option.value = cc;
            option.textContent = cc;
            costCenterSelect.appendChild(option);
        });
    } else {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Select Department First';
        option.disabled = true;
        option.selected = true;
        costCenterSelect.appendChild(option);
    }
}

function viewReimbursement(btn) {
    try {
        const rec = JSON.parse(btn.dataset.record);
        currentReimbursementId = rec.id;
        
        document.getElementById('v_employee_name').textContent = rec.employee_name || '-';
        document.getElementById('v_employee_id').textContent = rec.employee_id || '-';
        document.getElementById('v_address').textContent = rec.address || '-';
        document.getElementById('v_contact_no').textContent = rec.contact_no || '-';
        document.getElementById('v_department').textContent = rec.department || '-';
        document.getElementById('v_cost_center').textContent = rec.cost_center || '-';
        document.getElementById('v_type').textContent = rec.reimbursement_type || '-';
        document.getElementById('v_amount').textContent = peso(rec.amount);
        document.getElementById('v_expense_date').textContent = formatDate(rec.expense_date);
        document.getElementById('v_submission_date').textContent = formatDate(rec.submission_date);
        document.getElementById('v_description').textContent = rec.description || '-';
        
        // Display receipt file info
        const receiptFileEl = document.getElementById('v_receipt_file');
        if (rec.receipt_file && rec.receipt_folder) {
            const filePath = rec.receipt_folder + rec.receipt_file;
            receiptFileEl.innerHTML = `<a href="${filePath}" target="_blank" class="text-primary"><i class="bi bi-file-earmark-text"></i> ${rec.receipt_file}</a>`;
        } else {
            receiptFileEl.textContent = 'No receipt uploaded';
        }
        
        // Status badge (removed 'Paid' option)
        const statusEl = document.getElementById('v_status');
        statusEl.innerHTML = getStatusBadge(rec.status);
        
        document.getElementById('v_approved_by').textContent = rec.approved_by || 'Pending';
        
        // Show/hide remarks section
        const remarksSection = document.getElementById('remarks_section');
        if (rec.remarks) {
            remarksSection.style.display = 'block';
            document.getElementById('v_remarks').textContent = rec.remarks;
        } else {
            remarksSection.style.display = 'none';
        }
        
        // Show approve/reject buttons only for pending status (for managers)
        const approveBtn = document.getElementById('approve_btn');
        const rejectBtn = document.getElementById('reject_btn');
        if (rec.status === 'Pending') {
            approveBtn.style.display = 'inline-block';
            rejectBtn.style.display = 'inline-block';
        } else {
            approveBtn.style.display = 'none';
            rejectBtn.style.display = 'none';
        }
        
    } catch (error) {
        console.error('Error viewing reimbursement:', error);
        alert('Error loading reimbursement details');
    }
}

function editReimbursement(btn) {
    try {
        const rec = JSON.parse(btn.dataset.record);
        
        document.getElementById('edit_id').value = rec.id;
        document.getElementById('edit_employee_name').value = rec.employee_name || '';
        document.getElementById('edit_employee_id').value = rec.employee_id || '';
        document.getElementById('edit_address').value = rec.address || '';
        document.getElementById('edit_contact_no').value = rec.contact_no || '';
        document.getElementById('edit_dept').value = rec.department || '';
        
        updateReimbursementCostCenter('edit');
        
        setTimeout(() => {
            document.getElementById('edit_cost_center').value = rec.cost_center || '';
        }, 100);
        
        document.getElementById('edit_type').value = rec.reimbursement_type || '';
        document.getElementById('edit_amount').value = rec.amount || '';
        document.getElementById('edit_expense_date').value = rec.expense_date || '';
        document.getElementById('edit_description').value = rec.description || '';
        
    } catch (error) {
        console.error('Error editing reimbursement:', error);
        alert('Error loading reimbursement for editing');
    }
}

function deleteReimbursement(btn) {
    document.getElementById('delete_id').value = btn.dataset.id;
    document.getElementById('delete_name').textContent = btn.dataset.name;
}

function approveReimbursement() {
    if (!currentReimbursementId) return;
    
    if (confirm('Approve this reimbursement request?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'reimbursement_actions.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'approve';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = currentReimbursementId;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectReimbursement() {
    if (!currentReimbursementId) return;
    
    const remarks = prompt('Please provide a reason for rejection:');
    if (remarks === null) return; // User cancelled
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'reimbursement_actions.php';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'reject';
    
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id';
    idInput.value = currentReimbursementId;
    
    const remarksInput = document.createElement('input');
    remarksInput.type = 'hidden';
    remarksInput.name = 'remarks';
    remarksInput.value = remarks || 'No reason provided';
    
    form.appendChild(actionInput);
    form.appendChild(idInput);
    form.appendChild(remarksInput);
    document.body.appendChild(form);
    form.submit();
}

function getStatusBadge(status) {
    // Removed 'Paid' status
    const badges = {
        'Pending': '<span class="badge bg-warning">Pending</span>',
        'Approved': '<span class="badge bg-success">Approved</span>',
        'Rejected': '<span class="badge bg-danger">Rejected</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function peso(amount) {
    return '₱' + parseFloat(amount || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('Reimbursement Management System Initialized');
});
