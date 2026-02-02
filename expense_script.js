// Global variables
let currentPage = 1;
let currentSearch = '';
let currentEditId = null;
let currentDeleteId = null;
let expenseChart = null;

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing expense system...');
    loadExpenses();
    setupEventListeners();
    initializeChart();
});

// Initialize Chart
function initializeChart() {
    const ctx = document.getElementById('expenseChart');
    if (!ctx) {
        console.error('Chart canvas not found');
        return;
    }
    
    expenseChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Total Expenses', 'Total Tax', 'Net After Tax'],
            datasets: [{
                data: [0, 0, 0],
                backgroundColor: [
                    'rgba(245, 87, 108, 0.8)',
                    'rgba(166, 193, 238, 0.8)',
                    'rgba(67, 233, 123, 0.8)'
                ],
                borderColor: [
                    'rgba(245, 87, 108, 1)',
                    'rgba(166, 193, 238, 1)',
                    'rgba(67, 233, 123, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += formatCurrency(context.parsed);
                            return label;
                        }
                    }
                }
            }
        }
    });
}

// Update Chart with new data
function updateChart(totalExpenses, totalTax, netAfterTax) {
    if (!expenseChart) return;
    
    // Parse currency values
    const expenseValue = parseCurrency(totalExpenses);
    const taxValue = parseCurrency(totalTax);
    const netValue = parseCurrency(netAfterTax);
    
    expenseChart.data.datasets[0].data = [expenseValue, taxValue, netValue];
    expenseChart.update();
}

// Parse currency string to number
function parseCurrency(currencyString) {
    if (typeof currencyString === 'number') return currencyString;
    return parseFloat(currencyString.replace(/[₱,]/g, '')) || 0;
}

// Setup all event listeners
function setupEventListeners() {
    console.log('Setting up event listeners...');
    
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
        tbody.innerHTML = '<tr><td colspan="15" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading expenses...</td></tr>';
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
                        <td colspan="15" class="text-center text-danger py-4">
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
        tbody.innerHTML = '<tr><td colspan="15" class="text-center py-4 text-muted">No expenses found</td></tr>';
        return;
    }
    
    expenses.forEach((expense, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${(currentPage - 1) * 10 + index + 1}</td>
            <td>${expense.formatted_date || formatDate(expense.expense_date)}</td>
            <td><span class="badge bg-info">${escapeHtml(expense.category || '')}</span></td>
            <td>${escapeHtml(expense.vendor || '')}</td>
            <td>${truncateText(expense.remarks || '', 30)}</td>
            <td class="fw-bold text-danger">${expense.formatted_amount || formatCurrency(expense.amount)}</td>
            <td>${escapeHtml(expense.tax_type || '')}</td>
            <td class="text-warning fw-semibold">${expense.formatted_tax_amount || formatCurrency(expense.tax_amount)}</td>
            <td>
                ${expense.receipt_file ? 
                    `<button class="btn btn-sm btn-outline-primary" onclick="viewReceipt('${expense.receipt_file}')" title="View Receipt">
                        <i class="bi bi-file-earmark-image"></i>
                    </button>` : 
                    '<span class="text-muted">—</span>'
                }
            </td>
            <td>${escapeHtml(expense.approved_by || '—')}</td>
            <td><span class="badge ${getStatusBadgeClass(expense.status)}">${escapeHtml(expense.status || '')}</span></td>
            <td>${escapeHtml(expense.payment_method || '')}</td>
            <td>${escapeHtml(expense.vehicle || '—')}</td>
            <td>${escapeHtml(expense.job_linked || '—')}</td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary" onclick="viewExpense(${expense.id})" title="View">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-outline-warning" onclick="editExpense(${expense.id})" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// View Receipt in Modal
window.viewReceipt = function(filename) {
    console.log('Viewing receipt:', filename);
    
    const modal = new bootstrap.Modal(document.getElementById('receiptViewerModal'));
    const content = document.getElementById('receiptModalContent');
    const downloadLink = document.getElementById('receiptDownloadLink');
    
    const receiptPath = `uploads/receipts/${filename}`;
    const fileExt = filename.split('.').pop().toLowerCase();
    
    // Set download link
    downloadLink.href = receiptPath;
    downloadLink.download = filename;
    
    // Show loading
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Load content based on file type
    setTimeout(() => {
        if (fileExt === 'pdf') {
            content.innerHTML = `
                <embed src="${receiptPath}" type="application/pdf" style="width: 100%; min-height: 600px; border-radius: 8px;" />
                <p class="text-center text-muted mt-3">
                    <small>If PDF doesn't display, <a href="${receiptPath}" target="_blank">click here to open in new tab</a></small>
                </p>
            `;
        } else {
            content.innerHTML = `
                <div class="text-center">
                    <img src="${receiptPath}" 
                         alt="Receipt" 
                         style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);"
                         onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\\'alert alert-danger\\'>Failed to load image</div>';" />
                </div>
            `;
        }
    }, 100);
};

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
        <a class="page-link" href="#" onclick="loadExpenses(${currentPage - 1}, '${currentSearch}'); return false;">Previous</a>
    `;
    pagination.appendChild(prevLi);
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        const active = i === currentPage ? 'active' : '';
        const pageLi = document.createElement('li');
        pageLi.className = `page-item ${active}`;
        pageLi.innerHTML = `
            <a class="page-link" href="#" onclick="loadExpenses(${i}, '${currentSearch}'); return false;">${i}</a>
        `;
        pagination.appendChild(pageLi);
    }
    
    // Next button
    const nextDisabled = currentPage === totalPages ? 'disabled' : '';
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${nextDisabled}`;
    nextLi.innerHTML = `
        <a class="page-link" href="#" onclick="loadExpenses(${currentPage + 1}, '${currentSearch}'); return false;">Next</a>
    `;
    pagination.appendChild(nextLi);
}

// Update summary cards and chart
function updateSummary(summary) {
    console.log('Updating summary:', summary);
    
    const totalExpenses = document.getElementById('totalExpenses');
    const totalTax = document.getElementById('totalTax');
    const netAfterTax = document.getElementById('netAfterTax');
    
    if (totalExpenses) totalExpenses.textContent = summary.total_expenses || '₱0.00';
    if (totalTax) totalTax.textContent = summary.total_tax || '₱0.00';
    if (netAfterTax) netAfterTax.textContent = summary.net_after_tax || '₱0.00';
    
    // Update chart
    updateChart(
        summary.total_expenses || '₱0.00',
        summary.total_tax || '₱0.00',
        summary.net_after_tax || '₱0.00'
    );
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('addExpenseModal'));
            if (modal) modal.hide();
            
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('editExpenseModal'));
            if (modal) modal.hide();
            
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteExpenseModal'));
            if (modal) modal.hide();
            
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
            
            const expense = data.expense || data;
            
            if (!expense) {
                throw new Error('Expense not found');
            }
            
            // Populate view modal
            const elements = {
                'viewDate': formatDate(expense.expense_date),
                'viewCategory': expense.category || '',
                'viewVendor': expense.vendor || '',
                'viewAmount': formatCurrency(expense.amount),
                'viewRemarks': expense.remarks || '',
                'viewTaxType': expense.tax_type || '',
                'viewTaxAmount': formatCurrency(expense.tax_amount),
                'viewReceiptAttached': expense.receipt_file ? 'Yes' : 'No',
                'viewPaymentMethod': expense.payment_method || '',
                'viewVehicle': expense.vehicle || '—',
                'viewJobLinked': expense.job_linked || '—',
                'viewApprovedBy': expense.approved_by || '—',
                'viewStatus': expense.status || ''
            };
            
            Object.keys(elements).forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.textContent = elements[id];
                }
            });
            
            const modal = new bootstrap.Modal(document.getElementById('viewExpenseModal'));
            modal.show();
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
            
            const expense = data.expense || data;
            
            if (!expense) {
                throw new Error('Expense not found');
            }
            
            // Populate edit modal
            const fields = {
                'editDate': expense.expense_date,
                'editCategory': expense.category,
                'editVendor': expense.vendor,
                'editAmount': expense.amount,
                'editDescription': expense.remarks,
                'editTaxType': expense.tax_type,
                'editPaymentMethod': expense.payment_method,
                'editVehicle': expense.vehicle || '',
                'editJobLinked': expense.job_linked || '',
                'editApprovedBy': expense.approved_by || '',
                'editStatus': expense.status
            };
            
            Object.keys(fields).forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = fields[id] || '';
                }
            });
            
            const modal = new bootstrap.Modal(document.getElementById('editExpenseModal'));
            modal.show();
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
    
    const modal = new bootstrap.Modal(document.getElementById('deleteExpenseModal'));
    modal.show();
}

// Calculate tax for add form
function calculateAddTax() {
    const amount = parseFloat(document.getElementById('addAmount')?.value) || 0;
    const taxType = document.getElementById('addTaxType')?.value;
    console.log('Calculating add tax:', amount, taxType);
}

// Calculate tax for edit form
function calculateEditTax() {
    const amount = parseFloat(document.getElementById('editAmount')?.value) || 0;
    const taxType = document.getElementById('editTaxType')?.value;
    console.log('Calculating edit tax:', amount, taxType);
}

// Utility functions
function truncateText(text, maxLength) {
    if (!text) return '—';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

function getStatusBadgeClass(status) {
    if (!status) return 'bg-secondary';
    
    switch (status.toLowerCase()) {
        case 'approved':
            return 'bg-success';
        case 'pending':
            return 'bg-warning text-dark';
        case 'rejected':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

function formatCurrency(amount) {
    if (typeof amount === 'string' && amount.includes('₱')) {
        return amount;
    }
    return '₱' + parseFloat(amount || 0).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function formatDate(dateString) {
    if (!dateString) return '—';
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
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
    alertDiv.innerHTML = `
        <strong>${type === 'success' ? '✓' : '⚠'}</strong> ${escapeHtml(message)}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
