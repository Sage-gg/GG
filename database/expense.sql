-- Create database
CREATE DATABASE IF NOT EXISTS financial_system;
USE financial_system;

-- Create expenses table
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_date DATE NOT NULL,
    category VARCHAR(100) NOT NULL,
    vendor VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    remarks TEXT NOT NULL,
    tax_type VARCHAR(50) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    receipt_file VARCHAR(255) NULL,
    payment_method VARCHAR(50) NOT NULL,
    vehicle VARCHAR(255) NULL,
    job_linked VARCHAR(255) NULL,
    approved_by VARCHAR(255) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data for testing
INSERT INTO expenses (expense_date, category, vendor, amount, remarks, tax_type, tax_amount, payment_method, vehicle, job_linked, approved_by, status) VALUES
('2024-08-15', 'Fuel', 'Shell Gas Station', 1500.00, 'Fuel for crane operations', 'VAT', 180.00, 'Cash', 'Crane Unit 01', 'Job #2024-001', 'John Manager', 'Approved'),
('2024-08-14', 'Repair & Maintenance', 'Auto Parts Co.', 2500.00, 'Engine oil change and filter replacement', 'VAT', 300.00, 'Bank', 'Truck Unit 02', 'Job #2024-002', 'Sarah Supervisor', 'Approved'),
('2024-08-13', 'Toll & Parking', 'NLEX Toll', 250.00, 'Toll fees for project delivery', 'Exempted', 0.00, 'Cash', 'Delivery Truck 03', 'Job #2024-001', 'Mike Coordinator', 'Approved'),
('2024-08-12', 'Supplies', 'Hardware Store', 800.00, 'Safety equipment and tools', 'Withholding', 16.00, 'Bank', NULL, 'Maintenance', 'John Manager', 'Pending'),
('2024-08-11', 'Other', 'Office Supplies Inc.', 350.00, 'Office materials and documentation', 'None', 0.00, 'Cash', NULL, NULL, NULL, 'Rejected');