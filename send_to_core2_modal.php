<!-- send_to_core2_modal.php - Modal for sending budget to Core2 -->

<!-- Send to Core2 Modal -->
<div class="modal fade" id="sendToCore2Modal" tabindex="-1" aria-labelledby="sendToCore2ModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold" id="sendToCore2ModalLabel">
          <i class="bi bi-send"></i> Send Budget to Core2 System
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="sendCore2_budgetId" value="">
        
        <!-- Budget Info Display -->
        <div class="alert alert-info mb-3">
          <h6 class="alert-heading mb-2">Budget Information:</h6>
          <div class="row">
            <div class="col-6">
              <small class="text-muted">Department:</small>
              <div class="fw-bold" id="sendCore2_department">-</div>
            </div>
            <div class="col-6">
              <small class="text-muted">Cost Center:</small>
              <div class="fw-bold" id="sendCore2_costCenter">-</div>
            </div>
            <div class="col-6 mt-2">
              <small class="text-muted">Amount:</small>
              <div class="fw-bold text-primary" id="sendCore2_amount">₱0.00</div>
            </div>
            <div class="col-6 mt-2">
              <small class="text-muted">Period:</small>
              <div class="fw-bold" id="sendCore2_period">-</div>
            </div>
          </div>
        </div>

        <!-- Crane Selection -->
        <div class="mb-3">
          <label class="form-label fw-semibold">
            Select Crane <span class="text-danger">*</span>
          </label>
          <select class="form-select" id="sendCore2_craneSelect" required>
            <option value="">-- Loading cranes... --</option>
          </select>
          <small class="text-muted">Choose which crane this budget is allocated for</small>
        </div>

        <!-- Status Message -->
        <div id="sendCore2_statusMessage" class="alert d-none"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          Cancel
        </button>
        <button type="button" class="btn btn-primary" id="sendCore2_confirmBtn" onclick="confirmSendToCore2()">
          <span class="spinner-border spinner-border-sm d-none me-2" id="sendCore2_spinner"></span>
          <i class="bi bi-send"></i> Send to Core2
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Load cranes when modal is shown
document.getElementById('sendToCore2Modal').addEventListener('show.bs.modal', function() {
  loadCore2Cranes();
});

// Load available cranes
function loadCore2Cranes() {
  const select = document.getElementById('sendCore2_craneSelect');
  select.innerHTML = '<option value="">-- Loading cranes... --</option>';
  
  fetch('get_core2_cranes.php')
    .then(res => res.json())
    .then(data => {
      if (data.success && data.cranes && data.cranes.length > 0) {
        select.innerHTML = '<option value="">-- Select Crane --</option>';
        data.cranes.forEach(crane => {
          const option = document.createElement('option');
          option.value = crane.crane_id;
          option.textContent = `${crane.crane_name} - ${crane.crane_type} (SN: ${crane.serial_number})`;
          select.appendChild(option);
        });
      } else {
        select.innerHTML = '<option value="">No cranes available</option>';
      }
    })
    .catch(err => {
      console.error('Error loading cranes:', err);
      select.innerHTML = '<option value="">Error loading cranes</option>';
    });
}

// Open modal with budget data
function openSendToCore2Modal(budgetId, department, costCenter, amount, period) {
  // Set budget info
  document.getElementById('sendCore2_budgetId').value = budgetId;
  document.getElementById('sendCore2_department').textContent = department;
  document.getElementById('sendCore2_costCenter').textContent = costCenter;
  document.getElementById('sendCore2_amount').textContent = amount;
  document.getElementById('sendCore2_period').textContent = period;
  
  // Reset form
  document.getElementById('sendCore2_craneSelect').value = '';
  document.getElementById('sendCore2_statusMessage').classList.add('d-none');
  
  // Show modal
  const modal = new bootstrap.Modal(document.getElementById('sendToCore2Modal'));
  modal.show();
}

// Confirm and send to Core2
function confirmSendToCore2() {
  const budgetId = document.getElementById('sendCore2_budgetId').value;
  const craneId = document.getElementById('sendCore2_craneSelect').value;
  const statusMsg = document.getElementById('sendCore2_statusMessage');
  const confirmBtn = document.getElementById('sendCore2_confirmBtn');
  const spinner = document.getElementById('sendCore2_spinner');
  
  // Validate
  if (!craneId) {
    statusMsg.className = 'alert alert-warning';
    statusMsg.textContent = 'Please select a crane first';
    statusMsg.classList.remove('d-none');
    return;
  }
  
  // Show loading
  confirmBtn.disabled = true;
  spinner.classList.remove('d-none');
  statusMsg.classList.add('d-none');
  
  // Prepare form data
  const formData = new FormData();
  formData.append('budget_id', budgetId);
  formData.append('crane_id', craneId);
  
  // Send to Core2
  fetch('send_to_core2.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    confirmBtn.disabled = false;
    spinner.classList.add('d-none');
    
    if (data.success) {
      statusMsg.className = 'alert alert-success';
      statusMsg.innerHTML = `<strong>Success!</strong> ${data.message}`;
      statusMsg.classList.remove('d-none');
      
      // Auto-close and reload after 2 seconds
      setTimeout(() => {
        bootstrap.Modal.getInstance(document.getElementById('sendToCore2Modal')).hide();
        location.reload();
      }, 2000);
    } else {
      statusMsg.className = 'alert alert-danger';
      statusMsg.innerHTML = `<strong>Error:</strong> ${data.message}`;
      statusMsg.classList.remove('d-none');
    }
  })
  .catch(err => {
    confirmBtn.disabled = false;
    spinner.classList.add('d-none');
    statusMsg.className = 'alert alert-danger';
    statusMsg.innerHTML = `<strong>Error:</strong> ${err.message}`;
    statusMsg.classList.remove('d-none');
  });
}
</script>
