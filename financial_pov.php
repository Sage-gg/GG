<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Financial Data Sender</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
      --danger-gradient: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
    }
    
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }
    
    .navbar {
      background: white;
      box-shadow: 0 2px 20px rgba(0,0,0,0.08);
      padding: 1rem 0;
    }
    
    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
      background: var(--primary-gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    .main-content {
      max-width: 1400px;
      margin: 2rem auto;
      padding: 0 20px;
    }
    
    .hero-section {
      background: white;
      border-radius: 20px;
      padding: 2.5rem;
      margin-bottom: 2rem;
      box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    }
    
    .hero-section h1 {
      font-weight: 700;
      margin-bottom: 0.5rem;
      background: var(--primary-gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    .module-card {
      transition: all 0.3s ease;
      cursor: pointer;
      border: 2px solid transparent;
      border-radius: 16px;
      background: white;
      height: 100%;
      overflow: hidden;
      position: relative;
    }
    
    .module-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--primary-gradient);
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .module-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 35px rgba(0,0,0,0.12);
      border-color: #667eea;
    }
    
    .module-card:hover::before {
      opacity: 1;
    }
    
    .module-card.selected {
      border-color: #11998e;
      background: linear-gradient(135deg, #f8fffd, #e8f9f5);
      box-shadow: 0 8px 30px rgba(17, 153, 142, 0.25);
    }
    
    .module-card.selected::before {
      background: var(--success-gradient);
      opacity: 1;
    }
    
    .module-icon {
      width: 60px;
      height: 60px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      font-size: 1.8rem;
      background: linear-gradient(135deg, #f5f7fa, #e8ecf1);
    }
    
    .module-card.selected .module-icon {
      background: var(--success-gradient);
      color: white;
    }
    
    .data-preview {
      background: linear-gradient(135deg, #fafbfc, #f0f2f5);
      border-radius: 12px;
      border: 2px dashed #dee2e6;
      max-height: 350px;
      overflow-y: auto;
    }
    
    .send-button {
      background: var(--success-gradient);
      border: none;
      padding: 14px 35px;
      border-radius: 12px;
      color: white;
      font-weight: 600;
      font-size: 1.05rem;
      box-shadow: 0 6px 20px rgba(17, 153, 142, 0.3);
      transition: all 0.3s ease;
    }
    
    .send-button:hover:not(:disabled) {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(17, 153, 142, 0.4);
      color: white;
    }
    
    .send-button:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
    
    .status-indicator {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      display: inline-block;
      margin-right: 8px;
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }
    
    .status-success { background-color: #11998e; }
    .status-error { background-color: #ee0979; }
    .status-warning { background-color: #f39c12; }
    
    .log-entry {
      padding: 12px 16px;
      margin-bottom: 8px;
      border-left: 4px solid;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-20px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }
    
    .log-success { border-left-color: #11998e; }
    .log-error { border-left-color: #ee0979; }
    .log-info { border-left-color: #667eea; }
    
    .data-field {
      margin-bottom: 10px;
      padding: 8px 12px;
      background: white;
      border-radius: 8px;
      border: 1px solid #e9ecef;
      transition: all 0.2s ease;
    }
    
    .data-field:hover {
      border-color: #667eea;
      box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
    }
    
    .form-floating label {
      font-size: 0.9rem;
      color: #6c757d;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.15);
    }
    
    .connection-status {
      position: fixed;
      top: 80px;
      right: 20px;
      z-index: 1050;
      padding: 12px 20px;
      border-radius: 12px;
      color: white;
      font-weight: 500;
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
      backdrop-filter: blur(10px);
    }
    
    .database-info {
      background: linear-gradient(135deg, #e3f2fd, #f0f7ff);
      border: 2px solid #2196f3;
      border-radius: 16px;
      padding: 1.5rem;
    }
    
    .card {
      border: none;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      overflow: hidden;
    }
    
    .card-header {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border: none;
      padding: 1.25rem 1.5rem;
    }
    
    .badge-status {
      padding: 6px 12px;
      border-radius: 8px;
      font-weight: 500;
      font-size: 0.85rem;
    }
    
    .section-card {
      background: white;
      border-radius: 16px;
      padding: 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }

    .scroll-smooth {
      scroll-behavior: smooth;
    }
  </style>
</head>
<body class="scroll-smooth">

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-light fixed-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="#">
      <i class="bi bi-database-fill-gear"></i> Financial System
    </a>
    <span class="navbar-text">
      <small class="text-muted">Data Sender</small>
    </span>
  </div>
</nav>

<!-- Connection Status Indicator -->
<div class="connection-status bg-warning" id="connectionStatus">
  <i class="bi bi-wifi"></i> Connecting...
</div>

<div class="main-content" style="margin-top: 80px;">
  <div class="container-fluid">
    <!-- Hero Section -->
    <div class="hero-section">
      <h1>
        <i class="bi bi-send-check"></i> Financial Data Sender
      </h1>
      <p class="text-muted mb-4">
        Send data directly to your financial_system database. Select a destination module, configure data, and insert records seamlessly.
      </p>
      
      <div class="database-info">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h6 class="mb-2"><i class="bi bi-database"></i> Database Connection</h6>
            <p class="mb-1"><strong>Database:</strong> financial_system</p>
            <p class="mb-0" id="dbStatus">Checking connection status...</p>
          </div>
          <div class="text-end">
            <span class="badge bg-info">Connection File: db.php</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Module Selection -->
    <div class="section-card">
      <h4 class="mb-4 fw-bold">Select Destination Module</h4>
      <div class="row g-4">
        <div class="col-md-6 col-lg-4">
          <div class="card module-card" data-module="budgeting" data-table="budgets">
            <div class="card-body text-center p-4">
              <div class="module-icon">
                <i class="bi bi-currency-dollar"></i>
              </div>
              <h6 class="fw-bold mb-2">Budgeting & Cost Allocation</h6>
              <p class="text-muted small mb-3">Insert budget records with allocations</p>
              <span class="status-indicator status-success"></span>
              <small class="text-muted">Active</small>
            </div>
          </div>
        </div>
        
        <div class="col-md-6 col-lg-4">
          <div class="card module-card" data-module="collections" data-table="collections">
            <div class="card-body text-center p-4">
              <div class="module-icon">
                <i class="bi bi-receipt"></i>
              </div>
              <h6 class="fw-bold mb-2">Collections Management</h6>
              <p class="text-muted small mb-3">Insert invoice and payment data</p>
              <span class="status-indicator status-success"></span>
              <small class="text-muted">Active</small>
            </div>
          </div>
        </div>
        
        <div class="col-md-6 col-lg-4">
          <div class="card module-card" data-module="expenses" data-table="expenses">
            <div class="card-body text-center p-4">
              <div class="module-icon">
                <i class="bi bi-credit-card"></i>
              </div>
              <h6 class="fw-bold mb-2">Expense Tracking</h6>
              <p class="text-muted small mb-3">Insert expense and tax records</p>
              <span class="status-indicator status-success"></span>
              <small class="text-muted">Active</small>
            </div>
          </div>
        </div>
        
        <div class="col-md-6 col-lg-4">
          <div class="card module-card" data-module="ledger" data-table="journal_entries">
            <div class="card-body text-center p-4">
              <div class="module-icon">
                <i class="bi bi-journal-text"></i>
              </div>
              <h6 class="fw-bold mb-2">General Ledger</h6>
              <p class="text-muted small mb-3">Insert journal entries</p>
              <span class="status-indicator status-success"></span>
              <small class="text-muted">Active</small>
            </div>
          </div>
        </div>
        
        <div class="col-md-6 col-lg-4">
          <div class="card module-card" data-module="reporting" data-table="financial_reports">
            <div class="card-body text-center p-4">
              <div class="module-icon">
                <i class="bi bi-graph-up"></i>
              </div>
              <h6 class="fw-bold mb-2">Financial Reporting</h6>
              <p class="text-muted small mb-3">Generate and store reports</p>
              <span class="status-indicator status-success"></span>
              <small class="text-muted">Active</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Data Configuration Section -->
    <div class="row mb-4" id="dataConfigSection" style="display: none;">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-1">
              <i class="bi bi-gear"></i> Configure Data for <span id="selectedModuleName">Module</span>
            </h5>
            <small>Table: <span id="selectedTableName">-</span></small>
          </div>
          <div class="card-body p-4">
            <div id="dataForm"></div>
          </div>
        </div>
      </div>
      
      <div class="col-lg-4">
        <div class="card position-sticky" style="top: 100px;">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="bi bi-eye"></i> Data Preview
            </h6>
          </div>
          <div class="card-body">
            <div class="data-preview p-3" id="dataPreview">
              <small class="text-muted">Configure fields to see preview...</small>
            </div>
            <div class="mt-3 d-grid">
              <button class="send-button" id="sendDataBtn" disabled>
                <i class="bi bi-database-add"></i> Insert to Database
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Activity Log -->
    <div class="section-card">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">
          <i class="bi bi-activity"></i> Database Activity Log
        </h4>
        <button class="btn btn-outline-danger btn-sm" id="clearLogBtn">
          <i class="bi bi-trash"></i> Clear Log
        </button>
      </div>
      <div id="activityLog" style="max-height: 350px; overflow-y: auto;">
        <div class="log-entry log-info">
          <strong>System Ready</strong> - Financial Data Sender initialized
          <br><small class="text-muted">Checking database connection to financial_system...</small>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
class FinancialDataSender {
    constructor() {
        this.selectedModule = null;
        this.selectedTable = null;
        this.currentData = {};
        this.dbConnected = false;
        this.initEventListeners();
        this.checkDatabaseConnection();
    }
    
    initEventListeners() {
        document.querySelectorAll('.module-card').forEach(card => {
            card.addEventListener('click', (e) => {
                this.selectModule(card.dataset.module, card.dataset.table);
            });
        });
        
        document.getElementById('sendDataBtn').addEventListener('click', () => {
            this.sendData();
        });
        
        document.getElementById('clearLogBtn').addEventListener('click', () => {
            this.clearLog();
        });
    }
    
    async checkDatabaseConnection() {
        try {
            const response = await fetch('check_db_connection.php');
            const result = await response.json();
            
            if (result.success) {
                this.dbConnected = true;
                document.getElementById('connectionStatus').className = 'connection-status bg-success';
                document.getElementById('connectionStatus').innerHTML = '<i class="bi bi-database-check"></i> Connected';
                document.getElementById('dbStatus').innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Connected successfully</span>';
                this.addLog('Database connection established successfully', 'success');
            } else {
                this.dbConnected = false;
                document.getElementById('connectionStatus').className = 'connection-status bg-danger';
                document.getElementById('connectionStatus').innerHTML = '<i class="bi bi-database-x"></i> Failed';
                document.getElementById('dbStatus').innerHTML = '<span class="text-danger"><i class="bi bi-x-circle-fill"></i> ' + result.error + '</span>';
                this.addLog('Database connection failed: ' + result.error, 'error');
            }
        } catch (error) {
            this.dbConnected = false;
            document.getElementById('connectionStatus').className = 'connection-status bg-danger';
            document.getElementById('connectionStatus').innerHTML = '<i class="bi bi-database-x"></i> Error';
            document.getElementById('dbStatus').innerHTML = '<span class="text-danger"><i class="bi bi-x-circle-fill"></i> ' + error.message + '</span>';
            this.addLog('Database connection error: ' + error.message, 'error');
        }
    }
    
    selectModule(moduleName, tableName) {
        document.querySelectorAll('.module-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        document.querySelector(`[data-module="${moduleName}"]`).classList.add('selected');
        
        this.selectedModule = moduleName;
        this.selectedTable = tableName;
        document.getElementById('selectedModuleName').textContent = this.getModuleDisplayName(moduleName);
        document.getElementById('selectedTableName').textContent = tableName;
        
        document.getElementById('dataConfigSection').style.display = 'block';
        document.getElementById('dataConfigSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        this.loadModuleForm(moduleName);
        
        this.addLog(`Module Selected: ${this.getModuleDisplayName(moduleName)} (Table: ${tableName})`, 'info');
    }
    
    getModuleDisplayName(moduleName) {
        const names = {
            'budgeting': 'Budgeting & Cost Allocation',
            'collections': 'Collections Management',
            'expenses': 'Expense Tracking',
            'ledger': 'General Ledger',
            'reporting': 'Financial Reporting'
        };
        return names[moduleName] || moduleName;
    }
    
    loadModuleForm(moduleName) {
        const formContainer = document.getElementById('dataForm');
        let formHTML = '';
        
        switch (moduleName) {
            case 'budgeting':
                formHTML = this.getBudgetingForm();
                break;
            case 'collections':
                formHTML = this.getCollectionsForm();
                break;
            case 'expenses':
                formHTML = this.getExpensesForm();
                break;
            case 'ledger':
                formHTML = this.getLedgerForm();
                break;
            case 'reporting':
                formHTML = this.getReportingForm();
                break;
        }
        
        formContainer.innerHTML = formHTML;
        this.attachFormListeners();
    }
    
    getBudgetingForm() {
        return `
            <form id="budgetingForm">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="period" required>
                                <option value="">Select Period</option>
                                <option value="Daily">Daily</option>
                                <option value="Weekly">Weekly</option>
                                <option value="Monthly" selected>Monthly</option>
                                <option value="Quarterly">Quarterly</option>
                                <option value="Annually">Annually</option>
                            </select>
                            <label>Budget Period *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="department" required>
                                <option value="">Select Department</option>
                                <option value="HR1">HR1</option>
                                <option value="HR2">HR2</option>
                                <option value="HR3">HR3</option>
                                <option value="HR4">HR4</option>
                                <option value="CORE1">CORE1</option>
                                <option value="CORE2">CORE2</option>
                                <option value="CORE3">CORE3</option>
                                <option value="CORE4">CORE4</option>
                                <option value="ADMIN">ADMIN</option>
                                <option value="FINANCIALS">FINANCIALS</option>
                            </select>
                            <label>Department *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="cost_center" required>
                                <option value="">Select Cost Center</option>
                                <option value="Training Budget">Training Budget</option>
                                <option value="Reimbursement Budget">Reimbursement Budget</option>
                                <option value="Benefits Budget">Benefits Budget</option>
                                <option value="Equipment Budget">Equipment Budget</option>
                                <option value="Maintenance Budget">Maintenance Budget</option>
                                <option value="Travel Budget">Travel Budget</option>
                                <option value="Office Supplies Budget">Office Supplies Budget</option>
                                <option value="Utilities Budget">Utilities Budget</option>
                            </select>
                            <label>Cost Center *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="number" class="form-control" name="amount_allocated" placeholder="0.00" step="0.01" required>
                            <label>Allocated Amount *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="number" class="form-control" name="amount_used" placeholder="0.00" step="0.01" value="0">
                            <label>Amount Used</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="approved_by" placeholder="Approver Name" required>
                            <label>Approved By *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="approval_status">
                                <option value="Pending">Pending</option>
                                <option value="Approved" selected>Approved</option>
                                <option value="Rejected">Rejected</option>
                                <option value="Under Review">Under Review</option>
                            </select>
                            <label>Approval Status</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-floating">
                            <textarea class="form-control" name="description" style="height: 100px" placeholder="Budget description..."></textarea>
                            <label>Description</label>
                        </div>
                    </div>
                </div>
            </form>
        `;
    }
    
    getCollectionsForm() {
        return `
            <form id="collectionsForm">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="client_name" placeholder="Client Name" required>
                            <label>Client Name *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="invoice_no" placeholder="INV-000" required>
                            <label>Invoice Number *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="date" class="form-control" name="billing_date" required>
                            <label>Billing Date *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="date" class="form-control" name="due_date" required>
                            <label>Due Date *</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="number" class="form-control" name="amount_base" step="0.01" required>
                            <label>Base Amount *</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="number" class="form-control" name="amount_paid" step="0.01" value="0">
                            <label>Amount Paid</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <select class="form-select" name="vat_applied">
                                <option value="No">No VAT</option>
                                <option value="Yes" selected>Yes (12%)</option>
                            </select>
                            <label>VAT Applied</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="payment_status">
                                <option value="Pending" selected>Pending</option>
                                <option value="Partial">Partial</option>
                                <option value="Paid">Paid</option>
                                <option value="Overdue">Overdue</option>
                            </select>
                            <label>Payment Status</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="collector_name" placeholder="Collector Name">
                            <label>Collector Name</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="mode_of_payment">
                                <option value="">Select Payment Mode</option>
                                <option value="Cash">Cash</option>
                                <option value="Check">Check</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Online Payment">Online Payment</option>
                            </select>
                            <label>Mode of Payment</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="receipt_type">
                                <option value="">Select Receipt Type</option>
                                <option value="Official Receipt">Official Receipt</option>
                                <option value="Sales Invoice">Sales Invoice</option>
                                <option value="Acknowledgment Receipt">Acknowledgment Receipt</option>
                            </select>
                            <label>Receipt Type</label>
                        </div>
                    </div>
                </div>
            </form>
        `;
    }
    
    getExpensesForm() {
        return `
            <form id="expensesForm">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="date" class="form-control" name="expense_date" required>
                            <label>Expense Date *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Fuel">Fuel</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Office Supplies">Office Supplies</option>
                                <option value="Professional Services">Professional Services</option>
                                <option value="Utilities">Utilities</option>
                                <option value="Travel">Travel</option>
                                <option value="Equipment">Equipment</option>
                                <option value="Training">Training</option>
                                <option value="Insurance">Insurance</option>
                                <option value="Other">Other</option>
                            </select>
                            <label>Category *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="vendor" placeholder="Vendor Name" required>
                            <label>Vendor *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="number" class="form-control" name="amount" step="0.01" required>
                            <label>Amount *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="tax_type">
                                <option value="None">No Tax</option>
                                <option value="VAT" selected>VAT (12%)</option>
                                <option value="Withholding">Withholding Tax</option>
                                <option value="Other">Other Tax</option>
                            </select>
                            <label>Tax Type</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="number" class="form-control" name="tax_amount" step="0.01" value="0">
                            <label>Tax Amount</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="status">
                                <option value="Pending">Pending</option>
                                <option value="Approved" selected>Approved</option>
                                <option value="Rejected">Rejected</option>
                                <option value="Reimbursed">Reimbursed</option>
                            </select>
                            <label>Status</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="approved_by" placeholder="Approver Name">
                            <label>Approved By</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="payment_method">
                                <option value="">Select Payment Method</option>
                                <option value="Cash">Cash</option>
                                <option value="Check">Check</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Petty Cash">Petty Cash</option>
                            </select>
                            <label>Payment Method</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="vehicle_crane" placeholder="Vehicle/Equipment ID">
                            <label>Vehicle/Crane ID</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-floating">
                            <textarea class="form-control" name="description" style="height: 100px" placeholder="Expense description..."></textarea>
                            <label>Description</label>
                        </div>
                    </div>
                </div>
            </form>
        `;
    }
    
    getLedgerForm() {
        return `
            <form id="ledgerForm">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="date" class="form-control" name="date" required>
                            <label>Entry Date *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="entry_id" placeholder="JE-000" required>
                            <label>Entry ID *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="account_code" required>
                                <option value="">Select Account</option>
                                <option value="1001">1001 - Cash</option>
                                <option value="1002">1002 - Accounts Receivable</option>
                                <option value="1003">1003 - Inventory</option>
                                <option value="1004">1004 - Prepaid Expenses</option>
                                <option value="1501">1501 - Equipment</option>
                                <option value="2001">2001 - Accounts Payable</option>
                                <option value="2002">2002 - Accrued Liabilities</option>
                                <option value="3001">3001 - Owner's Equity</option>
                                <option value="4001">4001 - Revenue</option>
                                <option value="5001">5001 - Cost of Goods Sold</option>
                                <option value="6001">6001 - Operating Expenses</option>
                            </select>
                            <label>Account Code *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="reference" placeholder="Reference Document">
                            <label>Reference</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="number" class="form-control" name="debit" step="0.01" value="0">
                            <label>Debit Amount</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="number" class="form-control" name="credit" step="0.01" value="0">
                            <label>Credit Amount</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="source_module">
                                <option value="manual" selected>Manual Entry</option>
                                <option value="budgeting">Budgeting</option>
                                <option value="collections">Collections</option>
                                <option value="expenses">Expenses</option>
                                <option value="reporting">Reporting</option>
                            </select>
                            <label>Source Module</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="status">
                                <option value="Draft">Draft</option>
                                <option value="Posted" selected>Posted</option>
                                <option value="Reversed">Reversed</option>
                            </select>
                            <label>Status</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-floating">
                            <textarea class="form-control" name="description" style="height: 100px" placeholder="Journal entry description..."></textarea>
                            <label>Description</label>
                        </div>
                    </div>
                </div>
            </form>
        `;
    }
    
    getReportingForm() {
        return `
            <form id="reportingForm">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="report_type" required>
                                <option value="">Select Report Type</option>
                                <option value="income_statement">Income Statement</option>
                                <option value="balance_sheet">Balance Sheet</option>
                                <option value="cash_flow">Cash Flow Statement</option>
                                <option value="trial_balance">Trial Balance</option>
                                <option value="budget_variance">Budget Variance Report</option>
                                <option value="aging_report">Aging Report</option>
                                <option value="expense_summary">Expense Summary</option>
                            </select>
                            <label>Report Type *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="period">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly" selected>Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                                <option value="custom">Custom Range</option>
                            </select>
                            <label>Report Period</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="date" class="form-control" name="start_date" required>
                            <label>Start Date *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="date" class="form-control" name="end_date" required>
                            <label>End Date *</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="department">
                                <option value="">All Departments</option>
                                <option value="HR1">HR1</option>
                                <option value="HR2">HR2</option>
                                <option value="HR3">HR3</option>
                                <option value="HR4">HR4</option>
                                <option value="CORE1">CORE1</option>
                                <option value="CORE2">CORE2</option>
                                <option value="CORE3">CORE3</option>
                                <option value="CORE4">CORE4</option>
                                <option value="ADMIN">ADMIN</option>
                                <option value="FINANCIALS">FINANCIALS</option>
                            </select>
                            <label>Department Filter</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" name="report_status">
                                <option value="Generated" selected>Generated</option>
                                <option value="Draft">Draft</option>
                                <option value="Final">Final</option>
                                <option value="Archived">Archived</option>
                            </select>
                            <label>Report Status</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Note:</strong> This will generate and store the financial report data in the database.
                        </div>
                    </div>
                </div>
            </form>
        `;
    }
    
    attachFormListeners() {
        document.querySelectorAll('#dataForm input, #dataForm select, #dataForm textarea').forEach(input => {
            input.addEventListener('input', () => {
                this.updatePreview();
            });
            input.addEventListener('change', () => {
                this.updatePreview();
            });
        });
        
        const today = new Date().toISOString().split('T')[0];
        document.querySelectorAll('input[type="date"]').forEach(input => {
            if (!input.value) {
                input.value = today;
            }
        });
        
        setTimeout(() => this.updatePreview(), 100);
        
        document.getElementById('sendDataBtn').disabled = !this.dbConnected;
    }
    
    updatePreview() {
        const form = document.querySelector('#dataForm form');
        if (!form) return;
        
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                data[key] = value;
            }
        }
        
        this.currentData = data;
        
        const preview = document.getElementById('dataPreview');
        let previewHTML = '';
        
        Object.entries(data).forEach(([key, value]) => {
            previewHTML += `
                <div class="data-field">
                    <strong>${this.formatFieldName(key)}:</strong> ${value}
                </div>
            `;
        });
        
        if (previewHTML === '') {
            preview.innerHTML = '<small class="text-muted">Fill form fields to see data preview...</small>';
        } else {
            preview.innerHTML = previewHTML;
        }
    }
    
    formatFieldName(fieldName) {
        return fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    async sendData() {
        if (!this.selectedModule || Object.keys(this.currentData).length === 0) {
            this.addLog('Error: No module selected or no data configured', 'error');
            return;
        }

        if (!this.dbConnected) {
            this.addLog('Error: Database connection not available', 'error');
            return;
        }
        
        const sendBtn = document.getElementById('sendDataBtn');
        const originalText = sendBtn.innerHTML;
        
        sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Inserting...';
        sendBtn.disabled = true;
        
        try {
            const payload = {
                table: this.selectedTable,
                module: this.selectedModule,
                data: this.currentData
            };
            
            const response = await fetch('insert_financial_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.addLog(`✅ Data inserted successfully into ${this.selectedTable} table`, 'success');
                this.addLog(`Record ID: ${result.insert_id} | Affected Rows: ${result.affected_rows}`, 'info');
                
                this.showNotification('Data inserted successfully into database!', 'success');
                
                this.resetForm();
            } else {
                this.addLog(`❌ Database insertion failed: ${result.error}`, 'error');
                this.showNotification('Database insertion failed: ' + result.error, 'error');
            }
            
        } catch (error) {
            this.addLog(`❌ Network error: ${error.message}`, 'error');
            this.showNotification('Network error occurred', 'error');
        } finally {
            sendBtn.innerHTML = originalText;
            sendBtn.disabled = false;
        }
    }
    
    resetForm() {
        const form = document.querySelector('#dataForm form');
        if (form) {
            form.reset();
            const today = new Date().toISOString().split('T')[0];
            form.querySelectorAll('input[type="date"]').forEach(input => {
                input.value = today;
            });
        }
        
        this.currentData = {};
        this.updatePreview();
    }
    
    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 1051;
            min-width: 350px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            border-radius: 12px;
            animation: slideInRight 0.3s ease;
        `;
        
        notification.innerHTML = `
            <strong>${type === 'success' ? '✓ Success!' : '✗ Error!'}</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
    
    addLog(message, type = 'info') {
        const log = document.getElementById('activityLog');
        const timestamp = new Date().toLocaleTimeString();
        
        const logEntry = document.createElement('div');
        logEntry.className = `log-entry log-${type}`;
        logEntry.innerHTML = `
            <strong>[${timestamp}]</strong> ${message}
        `;
        
        log.appendChild(logEntry);
        log.scrollTop = log.scrollHeight;
    }
    
    clearLog() {
        document.getElementById('activityLog').innerHTML = '';
        this.addLog('Activity log cleared', 'info');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.financialDataSender = new FinancialDataSender();
    console.log('Financial Data Sender initialized');
});
</script>
</body>
</html>