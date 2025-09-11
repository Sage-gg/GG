/**
 * Collections Pagination System with Filter Support
 * Handles pagination logic for financial collections table with date range and status filtering
 */

class CollectionsPagination {
  constructor() {
    this.currentPage = 1;
    this.recordsPerPage = 5;
    this.totalRecords = 0;
    this.totalPages = 0;
    this.currentFilters = {
      date_from: '',
      date_to: '',
      payment_status: ''
    };
  }

  // Calculate total pages based on total records
  calculateTotalPages(totalRecords) {
    this.totalRecords = totalRecords;
    this.totalPages = Math.ceil(totalRecords / this.recordsPerPage);
    return this.totalPages;
  }

  // Load data with pagination and filters
  async loadCollectionsWithPagination(filters = {}, page = 1) {
    this.currentPage = page;
    this.currentFilters = { ...this.currentFilters, ...filters };

    const offset = (page - 1) * this.recordsPerPage;
    
    // Build URL with filters
    const params = new URLSearchParams({
      action: 'list',
      limit: this.recordsPerPage.toString(),
      offset: offset.toString()
    });
    
    // Add filter parameters only if they have values
    if (this.currentFilters.date_from && this.currentFilters.date_from.trim() !== '') {
      params.append('date_from', this.currentFilters.date_from.trim());
    }
    if (this.currentFilters.date_to && this.currentFilters.date_to.trim() !== '') {
      params.append('date_to', this.currentFilters.date_to.trim());
    }
    if (this.currentFilters.payment_status && this.currentFilters.payment_status.trim() !== '') {
      params.append('payment_status', this.currentFilters.payment_status.trim());
    }
    
    const url = `collections_action.php?${params.toString()}`;
    
    try {
      const res = await fetch(url);
      const data = await res.json();
      
      // Update total records and pages
      this.calculateTotalPages(data.total_records || 0);
      
      // Render table data
      this.renderTableData(data);
      
      // Render pagination controls
      this.renderPagination();
      
      return data;
    } catch (error) {
      console.error('Error loading collections:', error);
      this.renderError();
    }
  }

  // Render table data
  renderTableData(data) {
    const tbody = document.querySelector('#collections_tbody');
    tbody.innerHTML = '';

    if (!data.rows || data.rows.length === 0) {
      tbody.innerHTML = `<tr><td colspan="12" class="text-center text-muted">No records found</td></tr>`;
    } else {
      data.rows.forEach(row => {
        tbody.insertAdjacentHTML('beforeend', `
          <tr>
            <td>${row.id}</td>
            <td>${this.escapeHtml(row.client_name)}</td>
            <td>${this.escapeHtml(row.invoice_no)}</td>
            <td>${row.billing_date}</td>
            <td>${row.due_date}</td>
            <td class="text-end">${this.peso(row.amount_due)}</td>
            <td class="text-end">${this.peso(row.amount_paid)}</td>
            <td class="text-end">${this.peso(row.penalty)}</td>
            <td>${this.statusBadge(row.payment_status)}</td>
            <td>${this.escapeHtml(row.receipt_type)}</td>
            <td>${this.escapeHtml(row.collector_name)}</td>
            <td>
              <div class="btn-group">
                <button class="btn btn-sm btn-primary" onclick="openView(${row.id})">View</button>
                <button class="btn btn-sm btn-warning" onclick="openEdit(${row.id})">Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deleteRow(${row.id})">Delete</button>
              </div>
            </td>
          </tr>
        `);
      });
    }

    // Update summary cards
    document.querySelector('#card_total_collected').textContent = this.peso(data.totals.total_collected || 0);
    document.querySelector('#card_pending').textContent = this.peso(data.totals.total_pending || 0);
    document.querySelector('#card_overdue').textContent = this.peso(data.totals.total_overdue || 0);
  }

  // Render pagination controls
  renderPagination() {
    const paginationContainer = document.querySelector('.pagination');
    paginationContainer.innerHTML = '';

    if (this.totalPages <= 1) {
      return; // No pagination needed for single page
    }

    const pages = this.generatePaginationPages();
    
    pages.forEach(pageInfo => {
      const li = document.createElement('li');
      li.className = `page-item ${pageInfo.disabled ? 'disabled' : ''} ${pageInfo.active ? 'active' : ''}`;
      
      const a = document.createElement('a');
      a.className = 'page-link';
      a.href = '#';
      a.textContent = pageInfo.label;
      
      if (!pageInfo.disabled) {
        a.addEventListener('click', (e) => {
          e.preventDefault();
          if (pageInfo.page !== this.currentPage) {
            this.loadCollectionsWithPagination(this.currentFilters, pageInfo.page);
          }
        });
      }
      
      li.appendChild(a);
      paginationContainer.appendChild(li);
    });
  }

  // Generate pagination page structure
  generatePaginationPages() {
    const pages = [];
    
    if (this.currentPage === 1) {
      // First page: show 1, 2, 3, >>
      for (let i = 1; i <= Math.min(3, this.totalPages); i++) {
        pages.push({
          label: i.toString(),
          page: i,
          disabled: false,
          active: i === this.currentPage
        });
      }
      
      if (this.totalPages > 3) {
        pages.push({
          label: '>>',
          page: this.totalPages,
          disabled: false,
          active: false
        });
      }
    } else if (this.currentPage === this.totalPages) {
      // Last page: show <<, n-2, n-1, n
      pages.push({
        label: '<<',
        page: 1,
        disabled: false,
        active: false
      });
      
      for (let i = Math.max(1, this.totalPages - 2); i <= this.totalPages; i++) {
        pages.push({
          label: i.toString(),
          page: i,
          disabled: false,
          active: i === this.currentPage
        });
      }
    } else {
      // Middle pages: show <<, prev, current, next, >>
      pages.push({
        label: '<<',
        page: 1,
        disabled: false,
        active: false
      });
      
      // Previous page
      pages.push({
        label: (this.currentPage - 1).toString(),
        page: this.currentPage - 1,
        disabled: false,
        active: false
      });
      
      // Current page
      pages.push({
        label: this.currentPage.toString(),
        page: this.currentPage,
        disabled: false,
        active: true
      });
      
      // Next page
      pages.push({
        label: (this.currentPage + 1).toString(),
        page: this.currentPage + 1,
        disabled: false,
        active: false
      });
      
      pages.push({
        label: '>>',
        page: this.totalPages,
        disabled: false,
        active: false
      });
    }

    return pages;
  }

  // Render error message
  renderError() {
    const tbody = document.querySelector('#collections_tbody');
    tbody.innerHTML = `<tr><td colspan="12" class="text-center text-danger">Error loading data. Please try again.</td></tr>`;
    
    // Clear pagination
    document.querySelector('.pagination').innerHTML = '';
  }

  // Helper methods
  peso(n) {
    return '₱' + (Number(n) || 0).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  statusBadge(s) {
    if (s === 'Paid') return `<span class="badge bg-success">Paid</span>`;
    if (s === 'Partial') return `<span class="badge bg-warning text-dark">Partial</span>`;
    return `<span class="badge bg-danger">Unpaid</span>`;
  }

  escapeHtml(str = '') {
    return (str + '').replace(/[&<>"']/g, m => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    }[m]));
  }

  // Public method to refresh current page with current filters
  refresh() {
    this.loadCollectionsWithPagination(this.currentFilters, this.currentPage);
  }

  // Public method to apply filters and go to first page
  applyFilters(filters) {
    // Reset current filters to prevent old values from persisting
    this.currentFilters = {
      date_from: '',
      date_to: '',
      payment_status: ''
    };
    this.loadCollectionsWithPagination(filters, 1);
  }

  // Public method to reset filters and go to first page
  resetFilters() {
    this.currentFilters = {
      date_from: '',
      date_to: '',
      payment_status: ''
    };
    this.loadCollectionsWithPagination({}, 1);
  }
}

// Global instance
window.collectionsPagination = new CollectionsPagination();