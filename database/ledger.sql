-- Create Database
CREATE DATABASE IF NOT EXISTS financial_system;
USE financial_system;

-- Chart of Accounts Table
CREATE TABLE chart_of_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    account_code VARCHAR(10) UNIQUE NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    account_type ENUM('Asset', 'Liability', 'Equity', 'Income', 'Expense') NOT NULL,
    description TEXT,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Journal Entries Table
CREATE TABLE journal_entries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entry_id VARCHAR(20) UNIQUE NOT NULL,
    date DATE NOT NULL,
    account_code VARCHAR(10) NOT NULL,
    debit DECIMAL(15,2) DEFAULT 0.00,
    credit DECIMAL(15,2) DEFAULT 0.00,
    description TEXT NOT NULL,
    reference VARCHAR(50),
    source_module VARCHAR(50),
    approved_by VARCHAR(100),
    status ENUM('Draft', 'Posted', 'Cancelled') DEFAULT 'Posted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_code) REFERENCES chart_of_accounts(account_code)
);

-- Liquidation Records Table
CREATE TABLE liquidation_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    liquidation_id VARCHAR(20) UNIQUE NOT NULL,
    date DATE NOT NULL,
    employee VARCHAR(100) NOT NULL,
    purpose TEXT NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert Sample Chart of Accounts
INSERT INTO chart_of_accounts (account_code, account_name, account_type, description) VALUES
('1001', 'Cash', 'Asset', 'Main company cash account'),
('2001', 'Accounts Payable', 'Liability', 'Amounts owed to suppliers'),
('4001', 'Revenue', 'Income', 'Sales and service income'),
('5001', 'Fuel Expenses', 'Expense', 'Fuel for vehicles'),
('5002', 'Vehicle Maintenance', 'Expense', 'Vehicle maintenance and repairs');

-- Insert Sample Journal Entries
INSERT INTO journal_entries (entry_id, date, account_code, debit, credit, description, reference, source_module, approved_by) VALUES
('GL-1001', '2025-07-10', '5001', 8000.00, 0.00, 'Truck Fuel - Petron', 'COL-2001', 'Expenses', 'Admin'),
('GL-1002', '2025-07-11', '4001', 0.00, 25000.00, 'Client Payment - ABC Construction', 'COL-2002', 'Collections', 'Admin');

-- Insert Sample Liquidation Record
INSERT INTO liquidation_records (liquidation_id, date, employee, purpose, total_amount, status) VALUES
('LQ-2025-001', '2025-08-01', 'John Doe', 'Fuel Reimbursement', 1500.00, 'Approved');