// Improved Ledger Scripts with Enhanced Debugging
document.addEventListener('DOMContentLoaded', function () {
    
    console.log('Ledger scripts initializing...');
    
    // ===================
    // CONFIGURATION
    // ===================
    
    const AJAX_HANDLER = 'ledger_ajax_handler.php';
    
    // Check if ajax handler exists
    fetch(AJAX_HANDLER + '?action=ping')
        .then(response => {
            if (!response.ok) {
                console.error('AJAX handler not accessible:', response.status);
                showAlert('System configuration error. Please check server setup.', 'danger');
            }
        })
        .catch(error => {
            console.error('Cannot reach AJAX handler:', error);
        });
    
    // ===================
    // INITIALIZATION
    // ===================
    
    loadAccounts();
    setupAutoGeneration();
    setupEventListeners();
    
    // ===================
    // UTILITY FUNCTIONS
    // ===================
    
    function showAlert(message, type = 'success') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);';
        alertDiv.innerHTML = `
            <strong>${type === 'danger' ? 'Error!' : type === 'warning' ? 'Warning!' : 'Success!'}</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
    
    function formatCurrency(amount) {
        return parseFloat(amount).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    function showLoading() {
        let loadingDiv = document.getElementById('globalLoading');
        if (!loadingDiv) {
            loadingDiv = document.createElement('div');
            loadingDiv.id = 'globalLoading';
            loadingDiv.innerHTML = `
                <div class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" 
                     style="background: rgba(0,0,0,0.5); z-index: 9998;">
                    <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            document.body.appendChild(loadingDiv);
        }
        loadingDiv.style.display = 'block';
    }
    
    function hideLoading() {
        const loadingDiv = document.getElementById('globalLoading');
        if (loadingDiv) {
            loadingDiv.style.display = 'none';
        }
    }
    
    function refreshPage() {
        setTimeout(() => {
            window.location.reload(true);
        }, 800);
    }
    
    function updateTableRow(rowElement, action = 'remove') {
        if (!rowElement) return;
        
        switch(action) {
            case 'remove':
                rowElement.style.transition = 'all 0.3s ease';
                rowElement.style.opacity = '0';
                rowElement.style.transform = 'translateX(-100%)';
                setTimeout(() => {
                    if (rowElement.parentNode) {
                        rowElement.remove();
                    }
                }, 300);
                break;
            case 'update':
                rowElement.style.transition = 'background-color 0.3s ease';
                rowElement.style.backgroundColor = '#d4edda';
                setTimeout(() => {
                    rowElement.style.backgroundColor = '';
                }, 1000);
                break;
        }
    }
    
    function handleAjaxError(error, operation = 'operation') {
        hideLoading();
        console.error(`Error during ${operation}:`, error);
        
        let errorMessage = `Error during ${operation}: `;
        
        if (error instanceof Response) {
            errorMessage += `Server returned ${error.status} ${error.statusText}`;
        } else if (error.message) {
            errorMessage += error.message;
        } else if (typeof error === 'string') {
            errorMessage += error;
        } else {
            errorMessage += 'Unknown error occurred';
        }
        
        showAlert(errorMessage, 'danger');
    }
    
    async function fetchJSON(url, options = {}) {
        try {
            const response = await fetch(url, options);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                throw new Error('Server returned non-JSON response. Check console for details.');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Fetch error:', error);
            throw error;
        }
    }
    
    // ===================
    // RECEIPT VIEWER - ENHANCED
    // ===================
    
    window.openReceiptViewer = function(receiptPath, receiptFilename) {
        console.log('Opening receipt:', receiptPath, receiptFilename);
        
        const modalElement = document.getElementById('receiptViewerModal');
        if (!modalElement) {
            console.error('Receipt viewer modal not found!');
            showAlert('Receipt viewer modal not found. Please check your page setup.', 'danger');
            return;
        }
        
        const content = document.getElementById('receiptViewerContent');
        const downloadLink = document.getElementById('receiptDownloadLink');
        
        if (!content) {
            console.error('Receipt viewer content element not found!');
            return;
        }
        
        // Get file extension
        const fileExt = receiptFilename.split('.').pop().toLowerCase();
        
        // Set download link
        if (downloadLink) {
            downloadLink.href = receiptPath;
            downloadLink.download = receiptFilename;
        }
        
        // Clear previous content
        content.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
        
        // Show modal
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        
        // Display based on file type
        setTimeout(() => {
            if (fileExt === 'pdf') {
                content.innerHTML = `
                    <div class="ratio ratio-16x9" style="min-height: 500px;">
                        <embed src="${receiptPath}" type="application/pdf" />
                    </div>
                    <p class="mt-3 text-muted small">
                        If PDF doesn't display, <a href="${receiptPath}" target="_blank" class="text-decoration-none">click here to open in new tab</a>
                    </p>
                `;
            } else {
                content.innerHTML = `
                    <img src="${receiptPath}" 
                         class="img-fluid rounded shadow" 
                         alt="${receiptFilename}" 
                         style="max-height: 70vh; width: auto; margin: 0 auto; display: block;" 
                         onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\\'alert alert-danger\\'>Failed to load image</div>';" />
                `;
            }
        }, 100);
    };
    
    // ===================
    // INITIALIZATION FUNCTIONS
    // ===================
    
    function loadAccounts() {
        console.log('Loading accounts...');
        
        fetchJSON(AJAX_HANDLER + '?action=get_accounts')
            .then(accounts => {
                console.log('Accounts loaded:', accounts.length);
                
                const addSelect = document.getElementById('add_journal_account');
                const editSelect = document.getElementById('edit_journal_account');
                
                let options = '<option disabled selected>Select Account</option>';
                accounts.forEach(account => {
                    options += `<option value="${account.account_code}">${account.account_code} - ${account.account_name}</option>`;
                });
                
                if (addSelect) addSelect.innerHTML = options;
                if (editSelect) editSelect.innerHTML = options.replace('selected', '');
            })
            .catch(error => {
                console.error('Error loading accounts:', error);
                showAlert('Failed to load accounts. Please refresh the page.', 'warning');
            });
    }
    
    function setupAutoGeneration() {
        // Journal Entry ID Auto-generation
        const addJournalModal = document.getElementById('addJournalEntryModal');
        if (addJournalModal) {
            addJournalModal.addEventListener('show.bs.modal', function() {
                const referenceField = document.getElementById('add_journal_reference');
                if (referenceField && !referenceField.value) {
                    fetchJSON(AJAX_HANDLER + '?action=generate_entry_id')
                        .then(data => {
                            if (data.success) {
                                referenceField.value = data.entry_id;
                                console.log('Generated entry ID:', data.entry_id);
                            }
                        })
                        .catch(error => {
                            console.error('Error generating entry ID:', error);
                            const timestamp = Date.now().toString().slice(-6);
                            referenceField.value = `GL-${timestamp}`;
                        });
                }
            });
        }
        
        // Liquidation ID Auto-generation
        const addLiquidationModal = document.getElementById('addLiquidationModal');
        if (addLiquidationModal) {
            addLiquidationModal.addEventListener('show.bs.modal', function() {
                const liquidationField = document.getElementById('add_liq_id');
                if (liquidationField && !liquidationField.value) {
                    console.log('Generating liquidation ID...');
                    fetchJSON(AJAX_HANDLER + '?action=generate_liquidation_id')
                        .then(data => {
                            console.log('Liquidation ID response:', data);
                            if (data.success) {
                                liquidationField.value = data.liquidation_id;
                                console.log('Generated liquidation ID:', data.liquidation_id);
                            } else {
                                throw new Error(data.message || 'Failed to generate ID');
                            }
                        })
                        .catch(error => {
                            console.error('Error generating liquidation ID:', error);
                            const year = new Date().getFullYear();
                            const randomNum = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
                            liquidationField.value = `LQ-${year}-${randomNum}`;
                            showAlert('Using temporary ID. Please verify before saving.', 'warning');
                        });
                }
            });
        }
    }
    
    function setupEventListeners() {
        // Journal Entry Forms
        const addJournalForm = document.getElementById('addJournalEntryForm');
        if (addJournalForm) {
            addJournalForm.addEventListener('submit', handleAddJournalEntry);
        }
        
        const editJournalForm = document.getElementById('editJournalEntryForm');
        if (editJournalForm) {
            editJournalForm.addEventListener('submit', handleEditJournalEntry);
        }
        
        // Account Forms
        const addAccountForm = document.getElementById('addAccountForm');
        if (addAccountForm) {
            addAccountForm.addEventListener('submit', handleAddAccount);
        }
        
        const editAccountForm = document.getElementById('editAccountForm');
        if (editAccountForm) {
            editAccountForm.addEventListener('submit', handleEditAccount);
        }
        
        // Liquidation Forms
        const addLiquidationForm = document.getElementById('addLiquidationForm');
        if (addLiquidationForm) {
            const addReceiptInput = document.getElementById('add_liq_receipt');
            if (addReceiptInput) {
                addReceiptInput.addEventListener('change', function() {
                    const preview = document.getElementById('add_receipt_preview');
                    const filename = document.getElementById('add_receipt_filename');
                    
                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        const fileSize = (file.size / 1024 / 1024).toFixed(2); // MB
                        
                        preview.style.display = 'block';
                        filename.textContent = `${file.name} (${fileSize} MB)`;
                        
                        // Validate file size
                        if (file.size > 5 * 1024 * 1024) {
                            showAlert('File size exceeds 5MB limit. Please choose a smaller file.', 'warning');
                            this.value = '';
                            preview.style.display = 'none';
                        }
                    } else {
                        preview.style.display = 'none';
                    }
                });
            }
            
            addLiquidationForm.addEventListener('submit', handleAddLiquidation);
        }
        
        const editLiquidationForm = document.getElementById('editLiquidationForm');
        if (editLiquidationForm) {
            editLiquidationForm.addEventListener('submit', handleEditLiquidation);
        }
        
        // Event Delegation for Buttons
        document.body.addEventListener('click', function(e) {
            // Receipt View Button Handler
            const receiptBtn = e.target.closest('.view-receipt-btn');
            if (receiptBtn) {
                e.preventDefault();
                e.stopPropagation();
                const receiptPath = receiptBtn.getAttribute('data-receipt-path');
                const receiptFilename = receiptBtn.getAttribute('data-receipt-filename');
                console.log('Receipt button clicked:', receiptPath, receiptFilename);
                if (receiptPath && receiptFilename) {
                    openReceiptViewer(receiptPath, receiptFilename);
                } else {
                    showAlert('Receipt information not found', 'warning');
                }
                return;
            }
            
            // Journal Entry Buttons
            if (e.target.classList.contains('view-journal-btn')) {
                handleViewJournalEntry(e.target);
            }
            if (e.target.classList.contains('edit-journal-btn')) {
                handleEditJournalEntryBtn(e.target);
            }
            if (e.target.classList.contains('delete-journal-btn')) {
                handleDeleteJournalEntry(e.target);
            }
            
            // Account Buttons
            if (e.target.classList.contains('view-account-btn')) {
                handleViewAccount(e.target);
            }
            if (e.target.classList.contains('edit-account-btn')) {
                handleEditAccountBtn(e.target);
            }
            if (e.target.classList.contains('delete-account-btn')) {
                handleDeleteAccount(e.target);
            }
            
            // Liquidation Buttons
            if (e.target.classList.contains('view-liquidation-btn')) {
                handleViewLiquidation(e.target);
            }
            if (e.target.classList.contains('edit-liquidation-btn')) {
                handleEditLiquidationBtn(e.target);
            }
            if (e.target.classList.contains('delete-liquidation-btn')) {
                handleDeleteLiquidation(e.target);
            }
        });
    }
    
    // ===================
    // JOURNAL ENTRY FUNCTIONS
    // ===================
    
    function handleAddJournalEntry(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'add_journal_entry');
        
        console.log('Adding journal entry...');
        showLoading();
        
        fetch(AJAX_HANDLER, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            hideLoading();
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Add journal entry response:', data);
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('addJournalEntryModal')).hide();
                showAlert('Journal entry added successfully!', 'success');
                e.target.reset();
                refreshPage();
            } else {
                showAlert('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            handleAjaxError(error, 'adding journal entry');
        });
    }
    
    function handleViewJournalEntry(button) {
        const id = button.dataset.id;
        
        fetchJSON(AJAX_HANDLER + `?action=get_journal_entry&id=${id}`)
            .then(data => {
                if (data.success) {
                    const entry = data.entry;
                    document.getElementById('view_journal_date').textContent = entry.date || '-';
                    document.getElementById('view_journal_entry_id').textContent = entry.entry_id || '-';
                    document.getElementById('view_journal_reference').textContent = entry.reference || '-';
                    document.getElementById('view_journal_account').textContent = entry.account_name || '-';
                    document.getElementById('view_journal_account_code').textContent = entry.account_code || '-';
                    document.getElementById('view_journal_description').textContent = entry.description || '-';
                    document.getElementById('view_journal_debit').textContent = parseFloat(entry.debit) > 0 ? '₱' + formatCurrency(entry.debit) : '-';
                    document.getElementById('view_journal_credit').textContent = parseFloat(entry.credit) > 0 ? '₱' + formatCurrency(entry.credit) : '-';
                    document.getElementById('view_journal_source').textContent = entry.source_module || '-';
                    document.getElementById('view_journal_status').textContent = entry.status || '-';
                    document.getElementById('view_journal_approved').textContent = entry.approved_by || '-';
                } else {
                    showAlert('Error loading journal entry: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
            });
    }
    
    function handleEditJournalEntryBtn(button) {
        const id = button.dataset.id;
        
        fetchJSON(AJAX_HANDLER + `?action=get_journal_entry&id=${id}`)
            .then(data => {
                if (data.success) {
                    const entry = data.entry;
                    document.getElementById('edit_journal_id').value = entry.id;
                    document.getElementById('edit_journal_date').value = entry.date;
                    document.getElementById('edit_journal_reference').value = entry.entry_id;
                    document.getElementById('edit_journal_account').value = entry.account_code;
                    document.getElementById('edit_journal_description').value = entry.description;
                    
                    const sourceModuleField = document.getElementById('edit_journal_source_module');
                    if (sourceModuleField && entry.source_module) {
                        sourceModuleField.value = entry.source_module;
                    }
                    
                    if (parseFloat(entry.debit) > 0) {
                        document.getElementById('edit_journal_amount').value = entry.debit;
                        document.getElementById('edit_journal_type').value = 'debit';
                    } else {
                        document.getElementById('edit_journal_amount').value = entry.credit;
                        document.getElementById('edit_journal_type').value = 'credit';
                    }
                    
                    new bootstrap.Modal(document.getElementById('editJournalEntryModal')).show();
                } else {
                    showAlert('Error loading journal entry: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
            });
    }
    
    function handleEditJournalEntry(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'update_journal_entry');
        
        showLoading();
        fetch(AJAX_HANDLER, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            hideLoading();
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('editJournalEntryModal')).hide();
                showAlert('Journal entry updated successfully!', 'success');
                refreshPage();
            } else {
                showAlert('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            handleAjaxError(error, 'updating journal entry');
        });
    }
    
    function handleDeleteJournalEntry(button) {
        const id = button.dataset.id;
        const row = button.closest('tr');
        
        if (confirm('Are you sure you want to delete this journal entry?')) {
            showLoading();
            
            fetch(AJAX_HANDLER, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete_journal_entry&id=${id}`
            })
            .then(response => {
                hideLoading();
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (row) updateTableRow(row, 'remove');
                    showAlert('Journal entry deleted successfully!', 'success');
                } else {
                    showAlert('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                handleAjaxError(error, 'deleting journal entry');
            });
        }
    }
    
    // ===================
    // ACCOUNT FUNCTIONS
    // ===================
    
    function handleAddAccount(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'add_account');
        
        showLoading();
        fetch(AJAX_HANDLER, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            hideLoading();
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('addAccountModal')).hide();
                showAlert('Account added successfully!', 'success');
                e.target.reset();
                loadAccounts();
                refreshPage();
            } else {
                showAlert('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            handleAjaxError(error, 'adding account');
        });
    }
    
    function handleViewAccount(button) {
        const accountCode = button.dataset.code;
        
        fetchJSON(AJAX_HANDLER + `?action=get_account&account_code=${accountCode}`)
            .then(data => {
                if (data.success) {
                    const account = data.account;
                    document.getElementById('view_account_code').textContent = account.account_code || '-';
                    document.getElementById('view_account_name').textContent = account.account_name || '-';
                    document.getElementById('view_account_type').textContent = account.account_type || '-';
                    document.getElementById('view_account_description').textContent = account.description || '-';
                    document.getElementById('view_account_status').textContent = account.status || '-';
                } else {
                    showAlert('Error loading account: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
            });
    }
    
    function handleEditAccountBtn(button) {
        const accountCode = button.dataset.code;
        
        fetchJSON(AJAX_HANDLER + `?action=get_account&account_code=${accountCode}`)
            .then(data => {
                if (data.success) {
                    const account = data.account;
                    document.getElementById('edit_account_original_code').value = account.account_code;
                    document.getElementById('edit_account_code').value = account.account_code;
                    document.getElementById('edit_account_name').value = account.account_name;
                    document.getElementById('edit_account_type').value = account.account_type;
                    document.getElementById('edit_account_description').value = account.description || '';
                    
                    new bootstrap.Modal(document.getElementById('editAccountModal')).show();
                } else {
                    showAlert('Error loading account: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
            });
    }
    
    function handleEditAccount(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'update_account');
        
        showLoading();
        fetch(AJAX_HANDLER, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            hideLoading();
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('editAccountModal')).hide();
                showAlert('Account updated successfully!', 'success');
                loadAccounts();
                refreshPage();
            } else {
                showAlert('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            handleAjaxError(error, 'updating account');
        });
    }
    
    function handleDeleteAccount(button) {
        const accountCode = button.dataset.code;
        const row = button.closest('tr');
        
        if (!accountCode) {
            showAlert('Error: Account code not found', 'danger');
            return;
        }
        
        const confirmMessage = 'Are you sure you want to delete this account?\n\n' +
                             'WARNING: This will permanently delete the account and ALL associated transactions!\n\n' +
                             'This action cannot be undone.';
        
        if (confirm(confirmMessage)) {
            showLoading();
            
            fetch(AJAX_HANDLER, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete_account&account_code=${encodeURIComponent(accountCode)}`
            })
            .then(response => {
                hideLoading();
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data && data.success) {
                    if (row) updateTableRow(row, 'remove');
                    showAlert(data.message || 'Account deleted successfully!', 'success');
                    loadAccounts();
                } else {
                    showAlert('Error: ' + (data?.message || 'Failed to delete account'), 'danger');
                }
            })
            .catch(error => {
                handleAjaxError(error, 'deleting account');
            });
        }
    }
    
    // ===================
    // LIQUIDATION FUNCTIONS - IMPROVED
    // ===================
    
    function handleAddLiquidation(e) {
        e.preventDefault();
        
        // Validate form
        const employee = document.getElementById('add_liq_employee').value.trim();
        const purpose = document.getElementById('add_liq_purpose').value.trim();
        const amount = parseFloat(document.getElementById('add_liq_amount').value);
        
        if (!employee || !purpose || !amount || amount <= 0) {
            showAlert('Please fill in all required fields with valid values.', 'warning');
            return;
        }
        
        const formData = new FormData(e.target);
        formData.append('action', 'add_liquidation');
        
        console.log('Submitting liquidation form...');
        showLoading();
        
        fetch(AJAX_HANDLER, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            hideLoading();
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Add liquidation response:', data);
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('addLiquidationModal')).hide();
                showAlert('Liquidation record and journal entry created successfully!', 'success');
                e.target.reset();
                document.getElementById('add_receipt_preview').style.display = 'none';
                refreshPage();
            } else {
                showAlert('Error: ' + (data.message || 'Unknown error occurred'), 'danger');
            }
        })
        .catch(error => {
            console.error('Add liquidation error:', error);
            handleAjaxError(error, 'adding liquidation');
        });
    }
    
    function handleViewLiquidation(button) {
        const id = button.dataset.id;
        
        fetchJSON(AJAX_HANDLER + `?action=get_liquidation&id=${id}`)
            .then(data => {
                if (data.success) {
                    const liq = data.liquidation;
                    document.getElementById('view_liq_date').textContent = liq.date || '-';
                    document.getElementById('view_liq_id').textContent = liq.liquidation_id || '-';
                    document.getElementById('view_liq_employee').textContent = liq.employee || '-';
                    document.getElementById('view_liq_purpose').textContent = liq.purpose || '-';
                    document.getElementById('view_liq_amount').textContent = liq.total_amount ? '₱' + formatCurrency(liq.total_amount) : '-';
                    document.getElementById('view_liq_status').innerHTML = `<span class="badge ${liq.status === 'Approved' ? 'bg-success' : (liq.status === 'Rejected' ? 'bg-danger' : 'bg-warning')}">${liq.status}</span>`;
                    
                    const receiptElement = document.getElementById('view_liq_receipt');
                    if (liq.receipt_filename && liq.receipt_path) {
                        const fileExt = liq.receipt_filename.split('.').pop().toLowerCase();
                        const isPdf = fileExt === 'pdf';
                        
                        receiptElement.innerHTML = `
                            <button type="button" class="btn btn-sm btn-outline-primary view-receipt-btn" 
                                    data-receipt-path="${liq.receipt_path}" 
                                    data-receipt-filename="${liq.receipt_filename}">
                                <i class="bi bi-${isPdf ? 'file-pdf' : 'image'}"></i> View Receipt
                            </button>
                        `;
                    } else {
                        receiptElement.textContent = 'No receipt uploaded';
                    }
                } else {
                    showAlert('Error loading liquidation record: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
            });
    }
    
    function handleEditLiquidationBtn(button) {
        const id = button.dataset.id;
        
        fetchJSON(AJAX_HANDLER + `?action=get_liquidation&id=${id}`)
            .then(data => {
                if (data.success) {
                    const liq = data.liquidation;
                    document.getElementById('edit_liq_record_id').value = liq.id;
                    document.getElementById('edit_liq_date').value = liq.date;
                    document.getElementById('edit_liq_id').value = liq.liquidation_id;
                    document.getElementById('edit_liq_employee').value = liq.employee;
                    document.getElementById('edit_liq_purpose').value = liq.purpose;
                    document.getElementById('edit_liq_amount').value = liq.total_amount;
                    document.getElementById('edit_liq_status').value = liq.status;
                    
                    const currentReceiptDiv = document.getElementById('edit_current_receipt');
                    if (currentReceiptDiv) {
                        if (liq.receipt_filename) {
                            const fileExt = liq.receipt_filename.split('.').pop().toLowerCase();
                            const isPdf = fileExt === 'pdf';
                            
                            currentReceiptDiv.innerHTML = `
                                <div class="alert alert-info d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-${isPdf ? 'file-pdf' : 'image'}"></i>
                                        <strong>Current:</strong> ${liq.receipt_filename}
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary view-receipt-btn" 
                                            data-receipt-path="${liq.receipt_path}" 
                                            data-receipt-filename="${liq.receipt_filename}">
                                        View
                                    </button>
                                </div>
                            `;
                        } else {
                            currentReceiptDiv.innerHTML = '<div class="text-muted">No receipt uploaded</div>';
                        }
                    }
                    
                    new bootstrap.Modal(document.getElementById('editLiquidationModal')).show();
                } else {
                    showAlert('Error loading liquidation record: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
            });
    }
    
    function handleEditLiquidation(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'update_liquidation');
        
        showLoading();
        fetch(AJAX_HANDLER, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            hideLoading();
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('editLiquidationModal')).hide();
                showAlert('Liquidation record and journal entry updated successfully!', 'success');
                refreshPage();
            } else {
                showAlert('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            handleAjaxError(error, 'updating liquidation');
        });
    }
    
    function handleDeleteLiquidation(button) {
        const id = button.dataset.id;
        const row = button.closest('tr');
        
        const confirmMessage = 'Are you sure you want to delete this liquidation record?\n\n' +
                             'This will also delete:\n' +
                             '- The associated journal entry\n' +
                             '- The receipt file (if any)\n\n' +
                             'This action cannot be undone.';
        
        if (confirm(confirmMessage)) {
            showLoading();
            
            fetch(AJAX_HANDLER, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete_liquidation&id=${id}`
            })
            .then(response => {
                hideLoading();
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (row) updateTableRow(row, 'remove');
                    showAlert('Liquidation record, journal entry, and receipt deleted successfully!', 'success');
                } else {
                    showAlert('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                handleAjaxError(error, 'deleting liquidation');
            });
        }
    }
    
    console.log('✅ Improved ledger scripts loaded successfully!');
});
