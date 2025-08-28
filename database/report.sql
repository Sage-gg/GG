-- Financial Reporting Database Schema
-- This schema supports the financial reporting system with all necessary tables

-- ====================================
-- PAYMENTS TABLE (Collections Management)
-- ====================================
CREATE TABLE payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    payment_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    due_date DATE,
    reference_number VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_payment_date (payment_date),
    INDEX idx_payment_status (payment_status),
    INDEX idx_due_date (due_date)
);

-- ====================================
-- EXPENSES TABLE (Expense Tracking)
-- ====================================
CREATE TABLE expenses (
    expense_id INT PRIMARY KEY AUTO_INCREMENT,
    expense_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    expense_category ENUM('fuel', 'maintenance', 'labor', 'equipment', 'office', 'utilities', 'insurance', 'other') NOT NULL,
    description TEXT,
    vendor_name VARCHAR(255),
    receipt_number VARCHAR(100),
    approved_by INT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_expense_date (expense_date),
    INDEX idx_expense_category (expense_category),
    INDEX idx_status (status)
);

-- ====================================
-- BUDGET ALLOCATIONS TABLE
-- ====================================
CREATE TABLE budget_allocations (
    budget_id INT PRIMARY KEY AUTO_INCREMENT,
    budget_category ENUM('fuel', 'maintenance', 'labor', 'equipment', 'office', 'utilities', 'insurance', 'other') NOT NULL,
    allocated_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    department VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_period (period_start, period_end),
    INDEX idx_budget_category (budget_category)
);

-- ====================================
-- GENERAL LEDGER TABLE
-- ====================================
CREATE TABLE general_ledger (
    ledger_id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_date DATE NOT NULL,
    account_type ENUM('cash', 'accounts_receivable', 'equipment', 'vehicle', 'loan', 'accounts_payable', 'revenue', 'expense') NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    debit_credit ENUM('debit', 'credit') NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    reference_number VARCHAR(100),
    description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_account_type (account_type),
    INDEX idx_debit_credit (debit_credit)
);

-- ====================================
-- CUSTOMERS TABLE (for payments reference)
-- ====================================
CREATE TABLE customers (
    customer_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(255) NOT NULL,
    company_name VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    customer_type ENUM('individual', 'company') DEFAULT 'individual',
    credit_limit DECIMAL(15,2) DEFAULT 0.00,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_customer_name (customer_name),
    INDEX idx_status (status)
);

-- ====================================
-- VENDORS TABLE (for expenses reference)
-- ====================================
CREATE TABLE vendors (
    vendor_id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    vendor_type VARCHAR(100),
    tax_id VARCHAR(50),
    payment_terms VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_vendor_name (vendor_name),
    INDEX idx_status (status)
);

-- ====================================
-- PROJECTS TABLE (for revenue tracking)
-- ====================================
CREATE TABLE projects (
    project_id INT PRIMARY KEY AUTO_INCREMENT,
    project_name VARCHAR(255) NOT NULL,
    customer_id INT,
    project_value DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    start_date DATE,
    end_date DATE,
    status ENUM('planned', 'active', 'completed', 'cancelled') DEFAULT 'planned',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
);

-- ====================================
-- EQUIPMENT TABLE (for asset tracking)
-- ====================================
CREATE TABLE equipment (
    equipment_id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_name VARCHAR(255) NOT NULL,
    equipment_type VARCHAR(100),
    purchase_date DATE,
    purchase_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    current_value DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    depreciation_rate DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('active', 'maintenance', 'retired', 'sold') DEFAULT 'active',
    location VARCHAR(255),
    serial_number VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_equipment_type (equipment_type)
);

-- ====================================
-- VEHICLES TABLE (for vehicle asset tracking)
-- ====================================
CREATE TABLE vehicles (
    vehicle_id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_name VARCHAR(255) NOT NULL,
    vehicle_type VARCHAR(100),
    plate_number VARCHAR(50) UNIQUE,
    purchase_date DATE,
    purchase_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    current_value DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'maintenance', 'retired', 'sold') DEFAULT 'active',
    mileage INT DEFAULT 0,
    last_maintenance_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_plate_number (plate_number)
);

-- ====================================
-- LOANS TABLE (for liability tracking)
-- ====================================
CREATE TABLE loans (
    loan_id INT PRIMARY KEY AUTO_INCREMENT,
    loan_name VARCHAR(255) NOT NULL,
    lender_name VARCHAR(255) NOT NULL,
    principal_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    current_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    interest_rate DECIMAL(5,4) DEFAULT 0.0000,
    start_date DATE NOT NULL,
    end_date DATE,
    payment_frequency ENUM('monthly', 'quarterly', 'annually') DEFAULT 'monthly',
    status ENUM('active', 'paid', 'defaulted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
);

-- ====================================
-- SAMPLE DATA INSERTIONS
-- ====================================

-- Insert sample customers
INSERT INTO customers (customer_name, company_name, email, phone, customer_type, status) VALUES
('ABC Construction Inc.', 'ABC Construction Inc.', 'contact@abcconstruction.com', '+63-2-123-4567', 'company', 'active'),
('XYZ Development Corp.', 'XYZ Development Corp.', 'info@xyzdevelopment.com', '+63-2-987-6543', 'company', 'active'),
('Juan Dela Cruz', NULL, 'juan.delacruz@email.com', '+63-917-123-4567', 'individual', 'active');

-- Insert sample payments (revenue)
INSERT INTO payments (customer_id, payment_date, amount, payment_status, payment_method, due_date, description) VALUES
(1, '2025-07-15', 300000.00, 'completed', 'bank_transfer', '2025-07-15', 'Project ABC - Phase 1 Payment'),
(2, '2025-07-20', 150000.00, 'completed', 'check', '2025-07-20', 'Project XYZ - Milestone 1'),
(1, '2025-08-15', 250000.00, 'pending', 'bank_transfer', '2025-08-15', 'Project ABC - Phase 2 Payment');

-- Insert sample expenses
INSERT INTO expenses (expense_date, amount, expense_category, description, vendor_name, status) VALUES
('2025-07-10', 25000.00, 'fuel', 'Diesel fuel for trucks', 'Petron Station', 'approved'),
('2025-07-12', 15000.00, 'maintenance', 'Truck engine maintenance', 'Auto Repair Shop', 'approved'),
('2025-07-15', 80000.00, 'labor', 'Monthly salaries and wages', 'HR Department', 'approved'),
('2025-07-20', 12000.00, 'equipment', 'Tools and equipment purchase', 'Hardware Store', 'approved');

-- Insert sample budget allocations
INSERT INTO budget_allocations (budget_category, allocated_amount, period_start, period_end, description) VALUES
('fuel', 50000.00, '2025-07-01', '2025-07-31', 'Monthly fuel budget'),
('maintenance', 30000.00, '2025-07-01', '2025-07-31', 'Monthly maintenance budget'),
('labor', 100000.00, '2025-07-01', '2025-07-31', 'Monthly labor budget'),
('equipment', 20000.00, '2025-07-01', '2025-07-31', 'Monthly equipment budget');

-- Insert sample general ledger entries
INSERT INTO general_ledger (transaction_date, account_type, account_name, debit_credit, amount, balance, description) VALUES
('2025-07-15', 'cash', 'Main Cash Account', 'debit', 300000.00, 300000.00, 'Payment received from ABC Construction'),
('2025-07-15', 'revenue', 'Project Revenue', 'credit', 300000.00, 300000.00, 'Revenue from Project ABC'),
('2025-07-10', 'expense', 'Fuel Expense', 'debit', 25000.00, 25000.00, 'Fuel expenses'),
('2025-07-10', 'cash', 'Main Cash Account', 'credit', 25000.00, 275000.00, 'Cash payment for fuel'),
('2025-07-20', 'cash', 'Main Cash Account', 'debit', 150000.00, 425000.00, 'Payment from XYZ Development');

-- Insert sample equipment
INSERT INTO equipment (equipment_name, equipment_type, purchase_date, purchase_cost, current_value, status) VALUES
('Excavator Model X1', 'Heavy Equipment', '2024-01-15', 2500000.00, 2300000.00, 'active'),
('Concrete Mixer A1', 'Construction Equipment', '2024-03-10', 150000.00, 140000.00, 'active');

-- Insert sample vehicles
INSERT INTO vehicles (vehicle_name, vehicle_type, plate_number, purchase_date, purchase_cost, current_value, status) VALUES
('Dump Truck 1', 'Heavy Vehicle', 'ABC-1234', '2023-06-01', 1800000.00, 1600000.00, 'active'),
('Service Van', 'Light Vehicle', 'XYZ-5678', '2024-02-15', 800000.00, 750000.00, 'active');

-- Insert sample loans
INSERT INTO loans (loan_name, lender_name, principal_amount, current_balance, interest_rate, start_date, end_date, status) VALUES
('Equipment Loan', 'Philippine Bank', 2000000.00, 1500000.00, 0.0650, '2024-01-01', '2029-01-01', 'active'),
('Working Capital Loan', 'Metro Bank', 500000.00, 300000.00, 0.0750, '2024-06-01', '2026-06-01', 'active');

-- ====================================
-- USEFUL VIEWS FOR FINANCIAL REPORTING
-- ====================================

-- View for monthly revenue summary
CREATE VIEW monthly_revenue_summary AS
SELECT 
    DATE_FORMAT(payment_date, '%Y-%m') as month_year,
    SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END) as completed_revenue,
    SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END) as pending_revenue,
    COUNT(*) as total_payments
FROM payments 
GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
ORDER BY month_year DESC;

-- View for monthly expense summary
CREATE VIEW monthly_expense_summary AS
SELECT 
    DATE_FORMAT(expense_date, '%Y-%m') as month_year,
    expense_category,
    SUM(amount) as total_amount,
    COUNT(*) as total_expenses
FROM expenses 
WHERE status = 'approved'
GROUP BY DATE_FORMAT(expense_date, '%Y-%m'), expense_category
ORDER BY month_year DESC, expense_category;

-- View for current asset values
CREATE VIEW current_assets AS
SELECT 
    'Equipment' as asset_type,
    SUM(current_value) as total_value,
    COUNT(*) as count
FROM equipment 
WHERE status = 'active'
UNION ALL
SELECT 
    'Vehicles' as asset_type,
    SUM(current_value) as total_value,
    COUNT(*) as count
FROM vehicles 
WHERE status = 'active';

-- View for current liabilities
CREATE VIEW current_liabilities AS
SELECT 
    'Loans' as liability_type,
    SUM(current_balance) as total_balance,
    COUNT(*) as count
FROM loans 
WHERE status = 'active';

-- ====================================
-- INDEXES FOR PERFORMANCE
-- ====================================

-- Additional indexes for better query performance
CREATE INDEX idx_payments_date_status ON payments(payment_date, payment_status);
CREATE INDEX idx_expenses_date_category ON expenses(expense_date, expense_category, status);
CREATE INDEX idx_ledger_date_type ON general_ledger(transaction_date, account_type);

-- ====================================
-- STORED PROCEDURES (Optional)
-- ====================================

DELIMITER //

-- Procedure to get income statement data
CREATE PROCEDURE GetIncomeStatement(IN start_date DATE, IN end_date DATE)
BEGIN
    SELECT 
        'Revenue' as category,
        SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END) as amount
    FROM payments 
    WHERE payment_date BETWEEN start_date AND end_date
    
    UNION ALL
    
    SELECT 
        CONCAT('Expense - ', expense_category) as category,
        SUM(amount) as amount
    FROM expenses 
    WHERE expense_date BETWEEN start_date AND end_date 
    AND status = 'approved'
    GROUP BY expense_category;
END //

-- Procedure to get balance sheet data
CREATE PROCEDURE GetBalanceSheet(IN as_of_date DATE)
BEGIN
    -- Assets
    SELECT 'Assets' as section, 'Cash' as item, 
           COALESCE(SUM(CASE WHEN debit_credit = 'debit' THEN amount ELSE -amount END), 0) as amount
    FROM general_ledger 
    WHERE account_type = 'cash' AND transaction_date <= as_of_date
    
    UNION ALL
    
    SELECT 'Assets' as section, 'Accounts Receivable' as item,
           COALESCE(SUM(amount), 0) as amount
    FROM payments 
    WHERE payment_status = 'pending' AND due_date <= as_of_date
    
    UNION ALL
    
    SELECT 'Assets' as section, 'Equipment' as item,
           COALESCE(SUM(current_value), 0) as amount
    FROM equipment 
    WHERE status = 'active'
    
    UNION ALL
    
    SELECT 'Assets' as section, 'Vehicles' as item,
           COALESCE(SUM(current_value), 0) as amount
    FROM vehicles 
    WHERE status = 'active'
    
    UNION ALL
    
    -- Liabilities
    SELECT 'Liabilities' as section, 'Loans Payable' as item,
           COALESCE(SUM(current_balance), 0) as amount
    FROM loans 
    WHERE status = 'active';
END //

DELIMITER ;