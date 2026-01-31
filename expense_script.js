// Global variables
let currentPage = 1;
let currentSearch = '';
let currentEditId = null;
let currentDeleteId = null;

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== EXPENSE SYSTEM INITIALIZATION ===');
    console.log('DOM loaded, initializing expense system...');
    
    // Check if Bootstrap is loaded
    if (typeof bootstrap === 'undefined') {
        console.error('❌ Bootstrap library not loaded! Modals will not work.');
        showAlert('Bootstrap library not loaded. Please refresh the page.', 'danger');
        return;
    }
    
    console.log('✅ Bootstrap loaded. Version:', bootstrap.Modal.VERSION || 'unknown');
    
    // Check if jQuery is loaded (optional but good to know)
    if (typeof jQuery !== 'undefined') {
        console.log('✅ jQuery loaded. Version:', jQuery.fn.jquery);
    } else {
        console.log('ℹ️ jQuery not loaded (not required for Bootstrap 5)');
    }
    
    // Check if key DOM elements exist
    const checks = {
        'addExpenseModal': document.getElementById('addExpenseModal'),
        'editExpenseModal': document.getElementById('editExpenseModal'),
        'viewExpenseModal': document.getElementById('viewExpenseModal'),
        'deleteExpenseModal': document.getElementById('deleteExpenseModal'),
        'addExpenseForm': document.getElementById('addExpenseForm'),
        'expenseTableBody': document.getElementById('expenseTableBody'),
        'addExpenseButton': document.querySelector('[data-bs-target="#addExpenseModal"]')
    };
    
    console.log('=== DOM ELEMENTS CHECK ===');
    Object.keys(checks).forEach(key => {
        if (checks[key]) {
            console.log(`✅ ${key} found`);
        } else {
            console.error(`❌ ${key} NOT FOUND`);
        }
    });
    
    console.log('=== STARTING INITIALIZATION ===');
    loadExpenses();
    setupEventListeners();
    console.log('=== INITIALIZATION COMPLETE ===');
});

// Setup all event listeners
function setupEventListeners() {
    console.log('Setting up event listeners...');
    
    // Manual setup for Add Expense button (in case Bootstrap data attributes don't work)
    const addExpenseBtn = document.querySelector('[data-bs-target="#addExpenseModal"]');
    if (addExpenseBtn) {
        addExpenseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const modalElement = document.getElementById('addExpenseModal');
            if (modalElement && typeof bootstrap !== 'undefined') {
                try {
                    const modal = new bootstrap.Modal(modalElement, {
                        backdrop: 'static',
                        keyboard: true
                    });
                    modal.show();
                } catch (error) {
                    console.error('Error opening add expense modal:', error);
                    showAlert('Unable to open add expense form', 'danger');
                }
            }
        });
        console.log('Add Expense button listener attached');
    } else {
        console.warn('Add Expense button not found');
    }
    
    // Add expense form
    const addForm = document.getElementById('addExpenseForm');
    if (addForm) {
        addForm.addEventListener('submit', handleAddExpense);
    } else {
        console.error('addExpenseForm not found');
    }
    
    // Edit expense form
    const editForm = document.getElementById('editExpenseForm');
    if (editForm) {
        editForm.addEventListener('submit', handleEditExpense);
    } else {
        console.error('editExpenseForm not found');
    }
    
    // Delete confirmation
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', handleDeleteExpense);
    } else {
        console.error('confirmDeleteBtn not found');
    }
    
    // Search functionality
    const searchBtn = document.getElementById('searchBtn');
    const searchInput = document.getElementById('searchInput');
    
    if (searchBtn) {
        searchBtn.addEventListener('click', handleSearch);
    } else {
        console.error('searchBtn not found');
    }
    
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                handleSearch();
            }
        });
    } else {
        console.error('searchInput not found');
    }
    
    // Tax calculation on amount/tax type change
    const addAmount = document.getElementById('addAmount');
    const addTaxType = document.getElementById('addTaxType');
    const editAmount = document.getElementById('editAmount');
    const editTaxType = document.getElementById('editTaxType');
    
    if (addAmount) addAmount.addEventListener('input', calculateAddTax);
    if (addTaxType) addTaxType.addEventListener('change', calculateAddTax);
    if (editAmount) editAmount.addEventListener('input', calculateEditTax);
    if (editTaxType) editTaxType.addEventListener('change', calculateEditTax);
}

// Load expenses from database with comprehensive error handling
function loadExpenses(page = 1, search = '') {
    console.log(`Loading expenses - Page: ${page}, Search: "${search}"`);
    
    currentPage = page;
    currentSearch = search;
    
    // Show loading state
    const tbody = document.getElementById('expenseTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="15" class="text-center">Loading expenses...</td></tr>';
    }
    
    const params = new URLSearchParams({
        action: 'list',
        page: page,
        limit: 10,
        search: search
    });
    
    const url = `expense_handler.php?${params}`;
    console.log('Fetching URL:', url);
    
    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.warn('Response is not JSON, getting as text...');
                return response.text().then(text => {
                    throw new Error(`Expected JSON but got: ${text.substring(0, 200)}...`);
                });
            }
            
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data);
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Validate data structure
            if (!data.hasOwnProperty('expenses')) {
                console.error('Missing expenses property in response');
                throw new Error('Invalid response format: missing expenses data');
            }
            
            renderExpenseTable(data.expenses || []);
            renderPagination(data.current_page || 1, data.total_pages || 1);
            updateSummary(data.summary || {
                total_expenses: '₱0.00',
                total_tax: '₱0.00', 
                net_after_tax: '₱0.00'
            });
        })
        .catch(error => {
            console.error('Error loading expenses:', error);
            showAlert(`Error loading expenses: ${error.message}`, 'danger');
            
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="15" class="text-center text-danger">
                            <div class="alert alert-danger">
                                <strong>Error loading expenses:</strong><br>
                                ${error.message}
                                <br><br>
                                <button class="btn btn-sm btn-outline-danger" onclick="loadExpenses()">
                                    Try Again
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }
        });
}

// Render expense table
function renderExpenseTable(expenses) {
    console.log('Rendering expense table with', expenses.length, 'expenses');
    
    const tbody = document.getElementById('expenseTableBody');
    if (!tbody) {
        console.error('expenseTableBody not found');
        return;
    }
    
    tbody.innerHTML = '';
    
    if (!expenses || expenses.length === 0) {
        tbody.innerHTML = '<tr><td colspan="15" class="text-center">No expenses found</td></tr>';
        return;
    }
    
    expenses.forEach((expense, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${(currentPage - 1) * 10 + index + 1}</td>
            <td>${expense.formatted_date || formatDate(expense.expense_date)}</td>
            <td>${escapeHtml(expense.category || '')}</td>
            <td>${escapeHtml(expense.vendor || '')}</td>
            <td>${truncateText(expense.remarks || '', 30)}</td>
            <td>${expense.formatted_amount || formatCurrency(expense.amount)}</td>
            <td>${escapeHtml(expense.tax_type || '')}</td>
            <td>${expense.formatted_tax_amount || formatCurrency(expense.tax_amount)}</td>
            <td>
                ${expense.receipt_file ? 
                    `<a href="uploads/receipts/${expense.receipt_file}" target="_blank" class="text-success">View</a>` : 
                    '<span class="text-muted">None</span>'
                }
            </td>
            <td>${escapeHtml(expense.approved_by || '-')}</td>
            <td><span class="badge ${getStatusBadgeClass(expense.status)}">${escapeHtml(expense.status || '')}</span></td>
            <td>${escapeHtml(expense.payment_method || '')}</td>
            <td>${escapeHtml(expense.vehicle || '-')}</td>
            <td>${escapeHtml(expense.job_linked || '-')}</td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary" onclick="viewExpense(${expense.id})" title="View">
                        👁️
                    </button>
                    <button class="btn btn-outline-warning" onclick="editExpense(${expense.id})" title="Edit">
                        ✏️
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteExpense(${expense.id})" title="Delete">
                        🗑️
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Render pagination
function renderPagination(currentPage, totalPages) {
    console.log(`Rendering pagination - Current: ${currentPage}, Total: ${totalPages}`);
    
    const pagination = document.getElementById('pagination');
    if (!pagination) {
        console.error('pagination element not found');
        return;
    }
    
    pagination.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    // Previous button
    const prevDisabled = currentPage === 1 ? 'disabled' : '';
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${prevDisabled}`;
    prevLi.innerHTML = `
        <a class="page-link" href="#" onclick="loadExpenses(${currentPage - 1}, '${currentSearch}')">Previous</a>
    `;
    pagination.appendChild(prevLi);
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        const active = i === currentPage ? 'active' : '';
        const pageLi = document.createElement('li');
        pageLi.className = `page-item ${active}`;
        pageLi.innerHTML = `
            <a class="page-link" href="#" onclick="loadExpenses(${i}, '${currentSearch}')">${i}</a>
        `;
        pagination.appendChild(pageLi);
    }
    
    // Next button
    const nextDisabled = currentPage === totalPages ? 'disabled' : '';
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${nextDisabled}`;
    nextLi.innerHTML = `
        <a class="page-link" href="#" onclick="loadExpenses(${currentPage + 1}, '${currentSearch}')">Next</a>
    `;
    pagination.appendChild(nextLi);
}

// Update summary cards
function updateSummary(summary) {
    console.log('Updating summary:', summary);
    
    const totalExpenses = document.getElementById('totalExpenses');
    const totalTax = document.getElementById('totalTax');
    const netAfterTax = document.getElementById('netAfterTax');
    
    if (totalExpenses) totalExpenses.textContent = summary.total_expenses || '₱0.00';
    if (totalTax) totalTax.textContent = summary.total_tax || '₱0.00';
    if (netAfterTax) netAfterTax.textContent = summary.net_after_tax || '₱0.00';
}

// Handle add expense form submission
function handleAddExpense(e) {
    e.preventDefault();
    console.log('Handling add expense...');
    
    const formData = new FormData(e.target);
    formData.append('action', 'add');
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Saving...';
    submitBtn.disabled = true;
    
    fetch('expense_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Add response:', data);
        
        if (data.success) {
            const modalElement = document.getElementById('addExpenseModal');
            if (modalElement && typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) modal.hide();
            }
            
            document.getElementById('addExpenseForm').reset();
            loadExpenses(currentPage, currentSearch);
            showAlert(data.message || 'Expense added successfully', 'success');
        } else {
            throw new Error(data.message || 'Failed to add expense');
        }
    })
    .catch(error => {
        console.error('Error adding expense:', error);
        showAlert(`Error adding expense: ${error.message}`, 'danger');
    })
    .finally(() => {
        // Restore button state
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

// Handle edit expense form submission
function handleEditExpense(e) {
    e.preventDefault();
    console.log('Handling edit expense...');
    
    if (!currentEditId) {
        showAlert('No expense selected for editing', 'danger');
        return;
    }
    
    const formData = new FormData(e.target);
    formData.append('action', 'update');
    formData.append('id', currentEditId);
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Updating...';
    submitBtn.disabled = true;
    
    fetch('expense_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Edit response:', data);
        
        if (data.success) {
            const modalElement = document.getElementById('editExpenseModal');
            if (modalElement && typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) modal.hide();
            }
            
            loadExpenses(currentPage, currentSearch);
            showAlert(data.message || 'Expense updated successfully', 'success');
        } else {
            throw new Error(data.message || 'Failed to update expense');
        }
    })
    .catch(error => {
        console.error('Error updating expense:', error);
        showAlert(`Error updating expense: ${error.message}`, 'danger');
    })
    .finally(() => {
        // Restore button state
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

// Handle delete expense
function handleDeleteExpense() {
    console.log('Handling delete expense...');
    
    if (!currentDeleteId) {
        showAlert('No expense selected for deletion', 'danger');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', currentDeleteId);
    
    fetch('expense_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Delete response:', data);
        
        if (data.success) {
            const modalElement = document.getElementById('deleteExpenseModal');
            if (modalElement && typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) modal.hide();
            }
            
            loadExpenses(currentPage, currentSearch);
            showAlert(data.message || 'Expense deleted successfully', 'success');
        } else {
            throw new Error(data.message || 'Failed to delete expense');
        }
    })
    .catch(error => {
        console.error('Error deleting expense:', error);
        showAlert(`Error deleting expense: ${error.message}`, 'danger');
    });
}

// Handle search
function handleSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) {
        console.error('searchInput not found');
        return;
    }
    
    const searchTerm = searchInput.value.trim();
    console.log('Searching for:', searchTerm);
    loadExpenses(1, searchTerm);
}

// View expense details
function viewExpense(id) {
    console.log('Viewing expense ID:', id);
    
    fetch(`expense_handler.php?action=get&id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Expense details:', data);
            
            // Check if response is successful
            if (!data.success) {
                throw new Error(data.message || 'Failed to load expense');
            }
            
            // Check if response has expense data
            const expense = data.expense;
            
            if (!expense) {
                throw new Error('Expense not found');
            }
            
            // Safely populate view modal - handle values that might already be formatted
            const setElementText = (elementId, value) => {
                const element = document.getElementById(elementId);
                if (element) {
                    element.textContent = value || '-';
                } else {
                    console.warn(`Element ${elementId} not found in view modal`);
                }
            };
            
            setElementText('viewDate', formatDate(expense.expense_date));
            setElementText('viewCategory', expense.category);
            setElementText('viewVendor', expense.vendor);
            setElementText('viewAmount', formatCurrency(expense.amount));
            setElementText('viewRemarks', expense.remarks);
            setElementText('viewTaxType', expense.tax_type);
            setElementText('viewTaxAmount', formatCurrency(expense.tax_amount));
            setElementText('viewReceiptAttached', expense.receipt_file ? 'Yes' : 'No');
            setElementText('viewPaymentMethod', expense.payment_method);
            setElementText('viewVehicle', expense.vehicle || '-');
            setElementText('viewJobLinked', expense.job_linked || '-');
            setElementText('viewApprovedBy', expense.approved_by || '-');
            setElementText('viewStatus', expense.status);
            
            // Show modal using Bootstrap 5 syntax
            const modalElement = document.getElementById('viewExpenseModal');
            if (!modalElement) {
                throw new Error('View modal element not found in DOM');
            }
            
            // Check if Bootstrap is loaded
            if (typeof bootstrap === 'undefined') {
                throw new Error('Bootstrap library not loaded');
            }
            
            try {
                const modal = new bootstrap.Modal(modalElement, {
                    backdrop: 'static',
                    keyboard: true
                });
                modal.show();
            } catch (modalError) {
                console.error('Modal creation error:', modalError);
                throw new Error('Failed to initialize modal: ' + modalError.message);
            }
        })
        .catch(error => {
            console.error('Error loading expense details:', error);
            showAlert(`Error loading expense details: ${error.message}`, 'danger');
        });
}

// Edit expense
function editExpense(id) {
    console.log('Editing expense ID:', id);
    currentEditId = id;
    
    fetch(`expense_handler.php?action=get&id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Expense for edit:', data);
            
            // Check if response is successful
            if (!data.success) {
                throw new Error(data.message || 'Failed to load expense');
            }
            
            // Check if response has expense data
            const expense = data.expense;
            
            if (!expense) {
                throw new Error('Expense not found');
            }
            
            // Safely set form field values
            const setFieldValue = (fieldId, value) => {
                const element = document.getElementById(fieldId);
                if (element) {
                    element.value = value || '';
                } else {
                    console.warn(`Element ${fieldId} not found in edit modal`);
                }
            };
            
            setFieldValue('editDate', expense.expense_date);
            setFieldValue('editCategory', expense.category);
            setFieldValue('editVendor', expense.vendor);
            setFieldValue('editAmount', expense.amount);
            setFieldValue('editDescription', expense.remarks);
            setFieldValue('editTaxType', expense.tax_type);
            setFieldValue('editPaymentMethod', expense.payment_method);
            setFieldValue('editVehicle', expense.vehicle || '');
            setFieldValue('editJobLinked', expense.job_linked || '');
            setFieldValue('editApprovedBy', expense.approved_by || '');
            setFieldValue('editStatus', expense.status);
            
            // Show modal using Bootstrap 5 syntax
            const modalElement = document.getElementById('editExpenseModal');
            if (!modalElement) {
                throw new Error('Edit modal element not found in DOM');
            }
            
            // Check if Bootstrap is loaded
            if (typeof bootstrap === 'undefined') {
                throw new Error('Bootstrap library not loaded');
            }
            
            try {
                const modal = new bootstrap.Modal(modalElement, {
                    backdrop: 'static',
                    keyboard: true
                });
                modal.show();
            } catch (modalError) {
                console.error('Modal creation error:', modalError);
                throw new Error('Failed to initialize modal: ' + modalError.message);
            }
        })
        .catch(error => {
            console.error('Error loading expense for edit:', error);
            showAlert(`Error loading expense for edit: ${error.message}`, 'danger');
        });
}

// Delete expense
function deleteExpense(id) {
    console.log('Preparing to delete expense ID:', id);
    currentDeleteId = id;
    
    const modalElement = document.getElementById('deleteExpenseModal');
    if (!modalElement) {
        console.error('Delete modal element not found in DOM');
        showAlert('Unable to open delete confirmation', 'danger');
        return;
    }
    
    // Check if Bootstrap is loaded
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap library not loaded');
        showAlert('Unable to open delete confirmation', 'danger');
        return;
    }
    
    try {
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: true
        });
        modal.show();
    } catch (modalError) {
        console.error('Modal creation error:', modalError);
        showAlert('Failed to open delete confirmation: ' + modalError.message, 'danger');
    }
}

// Calculate tax for add form
function calculateAddTax() {
    const amount = parseFloat(document.getElementById('addAmount')?.value) || 0;
    const taxType = document.getElementById('addTaxType')?.value;
    console.log('Calculating add tax:', amount, taxType);
    // Tax calculation is handled server-side
}

// Calculate tax for edit form
function calculateEditTax() {
    const amount = parseFloat(document.getElementById('editAmount')?.value) || 0;
    const taxType = document.getElementById('editTaxType')?.value;
    console.log('Calculating edit tax:', amount, taxType);
    // Tax calculation is handled server-side
}

// Utility functions
function truncateText(text, maxLength) {
    if (!text) return '-';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

function getStatusBadgeClass(status) {
    if (!status) return 'bg-secondary';
    
    switch (status.toLowerCase()) {
        case 'approved':
            return 'bg-success';
        case 'pending':
            return 'bg-warning';
        case 'rejected':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

function formatCurrency(amount) {
    // Handle null, undefined, or empty
    if (amount === null || amount === undefined || amount === '') {
        return '₱0.00';
    }
    
    // If already a formatted string with ₱, return as is
    if (typeof amount === 'string' && amount.includes('₱')) {
        return amount;
    }
    
    // Convert to number and validate
    const numAmount = parseFloat(amount);
    if (isNaN(numAmount)) {
        console.warn('Invalid amount for currency formatting:', amount);
        return '₱0.00';
    }
    
    // Format with commas and two decimal places
    return '₱' + numAmount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    } catch (e) {
        return dateString;
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showAlert(message, type = 'info') {
    console.log(`Alert (${type}):`, message);
    
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${escapeHtml(message)}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
