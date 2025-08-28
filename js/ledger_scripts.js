// Complete Fixed Ledger Scripts - Frontend JavaScript functions - OPTIMIZED VERSION
document.addEventListener('DOMContentLoaded', function () {
    
    // ===================
    // INITIALIZATION
    // ===================
    
    // Load accounts for dropdowns
    loadAccounts();
    
    // Auto-generate reference numbers
    setupAutoGeneration();
    
    // Setup all event listeners
    setupEventListeners();
    
    // ===================
    // UTILITY FUNCTIONS - IMPROVED
    // ===================
    
    function showAlert(message, type = 'success') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 350px;';
        alertDiv.innerHTML = `
            ${message}
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
    
    // IMPROVED LOADING FUNCTIONS FOR BETTER PERFORMANCE
    function showLoading() {
        const loadingModal = document.getElementById('loadingModal');
        if (loadingModal) {
            // Use faster modal show method
            const modal = new bootstrap.Modal(loadingModal, {
                backdrop: 'static',
                keyboard: false
            });
            modal.show();
        }
    }
    
    function hideLoading() {
        const loadingModal = document.getElementById('loadingModal');
        const modalInstance = bootstrap.Modal.getInstance(loadingModal);
        if (modalInstance) {
            modalInstance.hide();
        }
        
        // Force hide if still visible after 100ms
        setTimeout(() => {
            if (loadingModal && loadingModal.classList.contains('show')) {
                loadingModal.classList.remove('show');
                loadingModal.style.display = 'none';
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) backdrop.remove();
            }
        }, 100);
    }
    
    // OPTIONAL: IMPROVED REFRESH FUNCTION (USE ONLY IF NEEDED)
    function refreshPage() {
        // Add a small delay to ensure any pending operations complete
        setTimeout(() => {
            window.location.reload(true); // Force reload from server
        }, 200);
    }
    
    // ALTERNATIVE: UPDATE TABLE WITHOUT FULL PAGE REFRESH
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
    
    // ENHANCED ERROR HANDLING
    function handleAjaxError(error, operation = 'operation') {
        hideLoading();
        console.error(`Error during ${operation}:`, error);
        
        let errorMessage = `Error during ${operation}: `;
        if (error.message) {
            errorMessage += error.message;
        } else if (typeof error === 'string') {
            errorMessage += error;
        } else {
            errorMessage += 'Unknown error occurred';
        }
        
        showAlert(errorMessage, 'danger');
    }
    
    // ===================
    // INITIALIZATION FUNCTIONS
    // ===================
    
    function loadAccounts() {
        fetch('ajax_handlers.php?action=get_accounts')
            .then(response => response.json())
            .then(accounts => {
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
                showAlert('Error loading accounts: ' + error.message, 'danger');
            });
    }
    
    function setupAutoGeneration() {
        // Auto-generate journal entry reference
        const addJournalModal = document.getElementById('addJournalEntryModal');
        if (addJournalModal) {
            addJournalModal.addEventListener('show.bs.modal', function() {
                const referenceField = document.getElementById('add_journal_reference');
                if (referenceField && !referenceField.value) {
                    fetch('ajax_handlers.php?action=generate_entry_id')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                referenceField.value = data.entry_id;
                            }
                        })
                        .catch(() => {
                            // Fallback generation
                            const timestamp = Date.now().toString().slice(-6);
                            referenceField.value = `GL-${timestamp}`;
                        });
                }
            });
        }
        
        // Auto-generate liquidation ID
        const addLiquidationModal = document.getElementById('addLiquidationModal');
        if (addLiquidationModal) {
            addLiquidationModal.addEventListener('show.bs.modal', function() {
                const liquidationField = document.getElementById('add_liq_id');
                if (liquidationField && !liquidationField.value) {
                    fetch('ajax_handlers.php?action=generate_liquidation_id')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                liquidationField.value = data.liquidation_id;
                            }
                        })
                        .catch(() => {
                            // Fallback generation
                            const year = new Date().getFullYear();
                            const randomNum = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
                            liquidationField.value = `LQ-${year}-${randomNum}`;
                        });
                }
            });
        }
    }
    
    function setupEventListeners() {
        // Journal Entry Form Handlers
        const addJournalForm = document.getElementById('addJournalEntryForm');
        if (addJournalForm) {
            addJournalForm.addEventListener('submit', handleAddJournalEntry);
        }
        
        const editJournalForm = document.getElementById('editJournalEntryForm');
        if (editJournalForm) {
            editJournalForm.addEventListener('submit', handleEditJournalEntry);
        }
        
        // Account Form Handlers
        const addAccountForm = document.getElementById('addAccountForm');
        if (addAccountForm) {
            addAccountForm.addEventListener('submit', handleAddAccount);
        }
        
        const editAccountForm = document.getElementById('editAccountForm');
        if (editAccountForm) {
            editAccountForm.addEventListener('submit', handleEditAccount);
        }
        
        // Liquidation Form Handlers
        const addLiquidationForm = document.getElementById('addLiquidationForm');
        if (addLiquidationForm) {
            addLiquidationForm.addEventListener('submit', handleAddLiquidation);
        }
        
        const editLiquidationForm = document.getElementById('editLiquidationForm');
        if (editLiquidationForm) {
            editLiquidationForm.addEventListener('submit', handleEditLiquidation);
        }
        
        // Button Event Delegation
        document.body.addEventListener('click', function(e) {
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
        
        showLoading();
        fetch('ajax_handlers.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            hideLoading();
            return response.json();
        })
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('addJournalEntryModal')).hide();
                showAlert('Journal entry added successfully!', 'success');
                e.target.reset();
                setTimeout(refreshPage, 500);
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
        
        fetch(`ajax_handlers.php?action=get_journal_entry&id=${id}`)
            .then(response => response.json())
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
        
        fetch(`ajax_handlers.php?action=get_journal_entry&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const entry = data.entry;
                    document.getElementById('edit_journal_id').value = entry.id;
                    document.getElementById('edit_journal_date').value = entry.date;
                    document.getElementById('edit_journal_reference').value = entry.entry_id;
                    document.getElementById('edit_journal_account').value = entry.account_code;
                    document.getElementById('edit_journal_description').value = entry.description;
                    
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
        fetch('ajax_handlers.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            hideLoading();
            return response.json();
        })
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('editJournalEntryModal')).hide();
                showAlert('Journal entry updated successfully!', 'success');
                setTimeout(refreshPage, 500);
            } else {
                showAlert('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            handleAjaxError(error, 'updating journal entry');
        });
    }
    
    // OPTIMIZED DELETE JOURNAL ENTRY - FIXED LOADING ISSUES
    function handleDeleteJournalEntry(button) {
        const id = button.dataset.id;
        const row = button.closest('tr'); // Get row reference early
        
        if (confirm('Are you sure you want to delete this journal entry?')) {
            // Show loading with shorter timeout
            showLoading();
            
            fetch('ajax_handlers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete_journal_entry&id=${id}`
            })
            .then(response => {
                // Hide loading immediately when response received
                hideLoading();
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Remove row immediately for instant feedback
                    if (row) {
                        updateTableRow(row, 'remove');
                    }
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
    // ACCOUNT FUNCTIONS - COMPLETELY FIXED
    // ===================
    
    function handleAddAccount(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'add_account');
        
        showLoading();
        fetch('ajax_handlers.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            hideLoading();
            return response.json();
        })
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('addAccountModal')).hide();
                showAlert('Account added successfully!', 'success');
                e.target.reset();
                loadAccounts(); // Reload accounts for dropdowns
                setTimeout(refreshPage, 500);
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
        
        fetch(`ajax_handlers.php?action=get_account&account_code=${accountCode}`)
            .then(response => response.json())
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
        
        fetch(`ajax_handlers.php?action=get_account&account_code=${accountCode}`)
            .then(response => response.json())
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
        fetch('ajax_handlers.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            hideLoading();
            return response.json();
        })
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('editAccountModal')).hide();
                showAlert('Account updated successfully!', 'success');
                loadAccounts(); // Reload accounts for dropdowns
                setTimeout(refreshPage, 500);
            } else {
                showAlert('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            handleAjaxError(error, 'updating account');
        });
    }
    
    // COMPLETELY FIXED Delete Account Handler - Now actually deletes from database
    function handleDeleteAccount(button) {
        const accountCode = button.dataset.code;
        const row = button.closest('tr'); // Get row reference early
        
        if (!accountCode) {
            showAlert('Error: Account code not found', 'danger');
            return;
        }
        
        const confirmMessage = 'Are you sure you want to delete this account?\n\n' +
                             'WARNING: This will permanently delete the account and ALL associated transactions!\n\n' +
                             'This action cannot be undone.';
        
        if (confirm(confirmMessage)) {
            showLoading();
            
            fetch('ajax_handlers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=delete_account&account_code=${encodeURIComponent(accountCode)}`
            })
            .then(response => {
                hideLoading();
                return response.json();
            })
            .then(data => {
                if (data && data.success) {
                    // Remove the row from the table immediately
                    if (row) {
                        updateTableRow(row, 'remove');
                    }
                    showAlert(data.message || 'Account deleted successfully!', 'success');
                    loadAccounts(); // Reload accounts for dropdowns to reflect changes
                    
                    // Optional: Force refresh to ensure database sync
                    // setTimeout(refreshPage, 1000);
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
    // LIQUIDATION FUNCTIONS
    // ===================
    
    function handleAddLiquidation(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'add_liquidation');
        
        showLoading();
        fetch('ajax_handlers.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            hideLoading();
            return response.json();
        })
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('addLiquidationModal')).hide();
                showAlert('Liquidation record added successfully!', 'success');
                e.target.reset();
                setTimeout(refreshPage, 500);
            } else {
                showAlert('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            handleAjaxError(error, 'adding liquidation');
        });
    }
    
    function handleViewLiquidation(button) {
        const id = button.dataset.id;
        
        fetch(`ajax_handlers.php?action=get_liquidation&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const liq = data.liquidation;
                    document.getElementById('view_liq_date').textContent = liq.date || '-';
                    document.getElementById('view_liq_id').textContent = liq.liquidation_id || '-';
                    document.getElementById('view_liq_employee').textContent = liq.employee || '-';
                    document.getElementById('view_liq_purpose').textContent = liq.purpose || '-';
                    document.getElementById('view_liq_amount').textContent = liq.total_amount ? '₱' + formatCurrency(liq.total_amount) : '-';
                    document.getElementById('view_liq_status').textContent = liq.status || '-';
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
        
        fetch(`ajax_handlers.php?action=get_liquidation&id=${id}`)
            .then(response => response.json())
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
        fetch('ajax_handlers.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            hideLoading();
            return response.json();
        })
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('editLiquidationModal')).hide();
                showAlert('Liquidation record updated successfully!', 'success');
                setTimeout(refreshPage, 500);
            } else {
                showAlert('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            handleAjaxError(error, 'updating liquidation');
        });
    }
    
    // OPTIMIZED DELETE LIQUIDATION - FIXED LOADING ISSUES
    function handleDeleteLiquidation(button) {
        const id = button.dataset.id;
        const row = button.closest('tr'); // Get row reference early
        
        if (confirm('Are you sure you want to delete this liquidation record?')) {
            // Show loading with immediate response handling
            showLoading();
            
            fetch('ajax_handlers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete_liquidation&id=${id}`
            })
            .then(response => {
                // Hide loading immediately when response received
                hideLoading();
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Remove row immediately with smooth animation
                    if (row) {
                        updateTableRow(row, 'remove');
                    }
                    showAlert('Liquidation record deleted successfully!', 'success');
                } else {
                    showAlert('Error: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                handleAjaxError(error, 'deleting liquidation');
            });
        }
    }
    
    console.log('Complete Fixed Ledger scripts loaded - Optimized loading and smooth UX');
});