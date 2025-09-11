-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 10, 2025 at 01:55 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `financial_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL,
  `period` enum('Annually','Monthly','Daily') NOT NULL,
  `department` varchar(100) NOT NULL,
  `cost_center` varchar(120) NOT NULL,
  `amount_allocated` decimal(12,2) NOT NULL,
  `amount_used` decimal(12,2) NOT NULL DEFAULT 0.00,
  `approved_by` varchar(120) DEFAULT NULL,
  `approval_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budgets`
--

INSERT INTO `budgets` (`id`, `period`, `department`, `cost_center`, `amount_allocated`, `amount_used`, `approved_by`, `approval_status`, `description`, `created_at`, `updated_at`) VALUES
(88, 'Annually', 'HR2', 'Training Budget', 240000.00, 185000.00, 'Maria Santos', 'Approved', 'Annual employee training and development programs including technical skills enhancement, leadership training, and certification courses', '2025-09-07 20:13:49', '2025-09-07 20:14:32'),
(89, 'Monthly', 'HR2', 'Reimbursement Budget', 45000.00, 32500.00, 'Maria Santos', 'Approved', 'Monthly employee reimbursements for travel expenses, meal allowances, and work-related purchases', '2025-09-07 20:13:49', '2025-09-07 20:13:49'),
(90, 'Daily', 'HR2', 'Training Budget', 2500.00, 1800.00, 'Carlos Rodriguez', 'Approved', 'Daily training allowance for new employee orientation and onboarding programs', '2025-09-07 20:13:49', '2025-09-07 20:13:49'),
(91, 'Monthly', 'HR2', 'Reimbursement Budget', 38000.00, 42000.00, 'Maria Santos', 'Approved', 'Employee overtime meal reimbursements and transportation allowances - exceeded due to increased project workload', '2025-09-07 20:13:49', '2025-09-07 20:13:49'),
(92, 'Annually', 'HR2', 'Training Budget', 180000.00, 95000.00, 'Jennifer Lee', 'Pending', 'Specialized IT training programs and software certifications for technical staff members', '2025-09-07 20:13:49', '2025-09-07 20:13:49'),
(93, 'Annually', 'HR4', 'Benefits Budget', 850000.00, 425000.00, 'Robert Chen', 'Approved', 'Annual employee benefits package including health insurance, life insurance, and retirement contributions', '2025-09-07 20:13:49', '2025-09-07 20:13:49'),
(94, 'Monthly', 'HR4', 'Benefits Budget', 72000.00, 68500.00, 'Robert Chen', 'Approved', 'Monthly health insurance premiums and medical benefit allocations for all employees', '2025-09-07 20:13:49', '2025-09-07 20:13:49'),
(95, 'Monthly', 'HR4', 'Benefits Budget', 65000.00, 71200.00, 'Sarah Johnson', 'Approved', 'Employee wellness programs, gym memberships, and mental health support services - slightly over budget due to increased participation', '2025-09-07 20:13:49', '2025-09-07 20:13:49'),
(96, 'Annually', 'HR4', 'Benefits Budget', 320000.00, 180000.00, 'Robert Chen', 'Rejected', 'Proposed expansion of dental and vision coverage - requires further review and cost analysis', '2025-09-07 20:13:49', '2025-09-07 20:13:49'),
(97, 'Monthly', 'Core 2', 'Log Maintenance Costs', 125000.00, 98000.00, 'Michael Wong', 'Approved', 'Monthly maintenance costs for equipment, and heavy machinery servicing', '2025-09-07 20:13:49', '2025-09-09 19:10:45'),
(98, 'Annually', 'Core 2', 'Depreciation Charges', 480000.00, 240000.00, 'Angela Davis', 'Approved', 'Annual depreciation charges for logging equipment, vehicles, and facility infrastructure', '2025-09-07 20:13:49', '2025-09-07 20:13:49'),
(99, 'Monthly', 'Core 2', 'Insurance Fees', 85000.00, 85000.00, 'Michael Wong', 'Approved', 'Monthly insurance premiums for equipment coverage, liability insurance, and worker compensation', '2025-09-07 20:13:49', '2025-09-07 20:13:49'),
(100, 'Annually', 'Core 2', 'Log Maintenance Costs', 950000.00, 720000.00, 'David Park', 'Approved', 'Annual budget for preventive maintenance, emergency repairs, and equipment replacement parts', '2025-09-07 20:13:49', '2025-09-07 20:13:49'),
(101, 'Daily', 'Core 2', 'Log Maintenance Costs', 8500.00, 9200.00, 'Michael Wong', 'Approved', 'Daily maintenance allowance for urgent equipment repairs and on-site servicing - exceeded due to unexpected breakdowns', '2025-09-07 20:13:49', '2025-09-07 20:13:49'),
(102, 'Monthly', 'Core 2', 'Depreciation Charges', 42000.00, 42000.00, 'Angela Davis', 'Approved', 'Monthly depreciation allocation for new crane equipment and transportation vehicles', '2025-09-07 20:13:49', '2025-09-07 20:13:49'),
(103, 'Monthly', 'Core 4', 'Vehicle Operational Budget', 195000.00, 178000.00, 'Luis Garcia', 'Approved', 'Monthly vehicle operational costs including fuel, maintenance, repairs, and driver allowances.', '2025-09-07 20:13:49', '2025-09-09 19:12:10'),
(104, 'Daily', 'Core 4', 'Vehicle Operational Budget', 12000.00, 11500.00, 'Luis Garcia', 'Approved', 'Daily fuel allocation and emergency vehicle maintenance for logistics operations', '2025-09-07 20:13:49', '2025-09-07 20:13:49'),
(105, 'Annually', 'Core 4', 'Vehicle Operational Budget', 2200000.00, 1850000.00, 'Thomas Kim', 'Approved', 'Annual vehicle fleet operational budget covering fuel costs, regular maintenance, insurance, and driver compensation', '2025-09-07 20:13:49', '2025-09-07 20:13:49'),
(106, 'Monthly', 'Core 4', 'Vehicle Operational Budget', 165000.00, 189000.00, 'Luis Garcia', 'Pending', 'Vehicle lease payments and operational costs for new transportation units - over budget due to increased fuel prices', '2025-09-07 20:13:49', '2025-09-07 20:13:49'),
(107, 'Annually', 'Core 4', 'Vehicle Operational Budget', 450000.00, 125000.00, 'Thomas Kim', 'Approved', 'Budget allocation for vehicle fleet expansion and modernization including electric vehicle pilot program', '2025-09-07 20:13:49', '2025-09-07 20:13:49'),
(108, 'Monthly', 'HR4', 'Benefits Budget', 100000.00, 98000.00, 'Juan Dela Cruz', 'Approved', 'for benefits of employees', '2025-09-07 20:38:05', '2025-09-07 20:38:54'),
(109, 'Monthly', 'HR4', 'Benefits Budget', 100000.00, 50000.00, 'John Doe', 'Approved', 'Used for excellent performance of employees.', '2025-09-09 03:58:12', '2025-09-09 03:58:48'),
(110, 'Monthly', 'HR2', 'Training Budget', 5000.00, 500.00, 'Juan Dela Cruz', 'Approved', 'Extra allowance for seniors who are assigned to train new hired employees.', '2025-09-09 19:14:43', '2025-09-09 19:14:43'),
(111, 'Annually', 'HR4', 'Benefits Budget', 50000.00, 30000.00, 'Maria Santos', 'Approved', 'Extra bonus for employees during holiday season in december', '2025-09-09 19:16:16', '2025-09-09 19:27:30'),
(112, 'Daily', 'Core 2', 'Depreciation Charges', 10000.00, 10000.00, 'Michael Wong', 'Approved', 'Sudden monitor replacement due to longetivity', '2025-09-09 19:20:17', '2025-09-09 19:20:17'),
(113, 'Daily', 'Core 4', 'Vehicle Operational Budget', 2000.00, 2000.00, 'Luis Garcia', 'Approved', 'Toll expenses from the travel sept. 7, 2025', '2025-09-09 19:21:28', '2025-09-09 21:44:19');

-- --------------------------------------------------------

--
-- Table structure for table `chart_of_accounts`
--

CREATE TABLE `chart_of_accounts` (
  `id` int(11) NOT NULL,
  `account_code` varchar(10) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_type` enum('Asset','Liability','Equity','Income','Expense') NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chart_of_accounts`
--

INSERT INTO `chart_of_accounts` (`id`, `account_code`, `account_name`, `account_type`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, '1001', 'Cash', 'Asset', 'Main company cash account', 'Active', '2025-08-30 14:51:55', '2025-08-30 14:51:55'),
(2, '2001', 'Accounts Payable', 'Liability', 'Amounts owed to suppliers', 'Active', '2025-08-30 14:51:55', '2025-08-30 14:51:55'),
(3, '4001', 'Revenue', 'Income', 'Sales and service income', 'Active', '2025-08-30 14:51:55', '2025-08-30 14:51:55'),
(4, '5001', 'Fuel Expenses', 'Expense', 'Fuel for vehicles', 'Active', '2025-08-30 14:51:55', '2025-08-30 14:51:55'),
(5, '5002', 'Vehicle Maintenance', 'Expense', 'Vehicle maintenance and repairs', 'Active', '2025-08-30 14:51:55', '2025-08-30 14:51:55');

-- --------------------------------------------------------

--
-- Table structure for table `collections`
--

CREATE TABLE `collections` (
  `id` int(11) NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `billing_date` date NOT NULL,
  `due_date` date NOT NULL,
  `amount_base` decimal(12,2) NOT NULL DEFAULT 0.00,
  `vat_applied` enum('Yes','No') NOT NULL DEFAULT 'No',
  `vat_rate` decimal(5,2) NOT NULL DEFAULT 12.00,
  `vat_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `penalty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `mode_of_payment` varchar(50) NOT NULL,
  `payment_status` enum('Unpaid','Partial','Paid') NOT NULL,
  `receipt_type` varchar(50) NOT NULL,
  `collector_name` varchar(255) NOT NULL,
  `receipt_attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collections`
--

INSERT INTO `collections` (`id`, `client_name`, `invoice_no`, `billing_date`, `due_date`, `amount_base`, `vat_applied`, `vat_rate`, `vat_amount`, `amount_due`, `amount_paid`, `penalty`, `mode_of_payment`, `payment_status`, `receipt_type`, `collector_name`, `receipt_attachment`, `created_at`) VALUES
(7, 'clienttest5', 'test-00000005', '2025-08-03', '2025-08-10', 10000.00, 'Yes', 12.00, 1200.00, 11200.00, 1000.00, 911.20, 'Cash', 'Partial', 'VAT Receipt', 'collectortest5', NULL, '2025-08-16 16:04:46'),
(9, 'clienttest1', 'test-00000001', '2025-08-17', '2025-08-24', 100000.00, 'Yes', 12.00, 12000.00, 112000.00, 10000.00, 0.00, 'Cash', 'Partial', 'Acknowledgment', 'collectortest1', NULL, '2025-08-16 18:24:41'),
(10, 'clienttest2', 'test-00000002', '2025-08-10', '2025-08-16', 100000.00, 'Yes', 12.00, 12000.00, 112000.00, 112000.00, 0.00, 'Cash', 'Paid', 'VAT Receipt', 'collectortest2', NULL, '2025-08-16 18:25:43'),
(11, 'clienttest3', 'test-00000003', '2025-07-13', '2025-07-20', 100000.00, 'Yes', 12.00, 12000.00, 112000.00, 10000.00, 3604.00, 'Bank Transfer', 'Partial', 'Acknowledgment', 'collectortest3', NULL, '2025-08-16 18:26:53'),
(12, 'clienttest4', 'test-00000004', '2025-06-17', '2025-07-22', 50000.00, 'No', 12.00, 0.00, 50000.00, 0.00, 2100.00, 'Cash', 'Unpaid', 'Acknowledgment', 'collectortest4', NULL, '2025-08-16 18:28:29'),
(13, 'clienttest6', 'test-00000006', '2025-07-27', '2025-08-10', 174760.00, 'Yes', 12.00, 20971.20, 195731.20, 30000.00, 4661.82, 'Bank Transfer', 'Partial', 'VAT Receipt', 'collectortest6', NULL, '2025-08-16 18:30:04'),
(16, 'clienttest7', 'test-00000007', '2025-08-27', '2025-09-03', 100000.00, 'Yes', 12.00, 12000.00, 112000.00, 112000.00, 0.00, 'Cash', 'Paid', 'VAT Receipt', 'collectortest7', NULL, '2025-08-27 06:01:10'),
(17, 'clienttest', 'test-00000000', '2025-09-03', '2025-09-17', 100000.00, 'No', 12.00, 0.00, 100000.00, 100000.00, 0.00, 'Cash', 'Paid', 'Acknowledgment', 'collectortest', NULL, '2025-09-03 07:48:15'),
(18, 'clienttest8', 'test-00000008', '2025-08-03', '2025-08-17', 100000.00, 'Yes', 12.00, 12000.00, 112000.00, 56000.00, 1802.00, 'Cash', 'Partial', 'VAT Receipt', 'collectortest8', NULL, '2025-09-03 07:49:33'),
(19, 'clienttest9', 'test-00000009', '2025-05-11', '2025-06-11', 200000.00, 'Yes', 12.00, 24000.00, 224000.00, 0.00, 20114.00, 'Cash', 'Unpaid', 'VAT Receipt', 'collectortest9', '68b9d85387b86_1757010003.png', '2025-09-03 07:51:05'),
(43, 'clienttest11', 'test-00000011', '2025-08-31', '2025-09-14', 50000.00, 'No', 12.00, 0.00, 50000.00, 25000.00, 0.00, 'Bank Transfer', 'Partial', 'Acknowledgment', 'collectortest11', '68b9dbca560b5_1757010890.png', '2025-09-04 18:34:50'),
(44, 'clienttest12', 'test-00000012', '2025-08-05', '2025-08-19', 100000.00, 'Yes', 12.00, 12000.00, 112000.00, 112000.00, 0.00, 'Bank Transfer', 'Paid', 'VAT Receipt', 'collectortest12', '68b9dc93e87da_1757011091.png', '2025-09-04 18:38:11'),
(45, 'clienttest13', 'test-00000013', '2025-09-05', '2025-09-12', 65000.00, 'No', 12.00, 0.00, 65000.00, 65000.00, 0.00, 'Cash', 'Paid', 'Acknowledgment', 'collectortest13', '68b9dd4d4b1d1_1757011277.png', '2025-09-04 18:41:17');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `category` varchar(100) NOT NULL,
  `vendor` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `remarks` text NOT NULL,
  `tax_type` varchar(50) NOT NULL,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `receipt_file` varchar(255) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL,
  `vehicle` varchar(255) DEFAULT NULL,
  `job_linked` varchar(255) DEFAULT NULL,
  `approved_by` varchar(255) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_date`, `category`, `vendor`, `amount`, `remarks`, `tax_type`, `tax_amount`, `receipt_file`, `payment_method`, `vehicle`, `job_linked`, `approved_by`, `status`, `created_at`, `updated_at`) VALUES
(1, '2024-08-15', 'Fuel', 'Shell Gas Station', 10500.00, 'Fuel for crane operations', 'VAT', 1260.00, NULL, 'Cash', 'Crane Unit 01', 'Job #2024-001', 'John Manager', 'Approved', '2025-08-22 16:58:23', '2025-08-23 13:23:13'),
(2, '2024-08-14', 'Repair & Maintenance', 'Auto Parts Co.', 2500.00, 'Engine oil change and filter replacement', 'VAT', 300.00, NULL, 'Bank', 'Truck Unit 02', 'Job #2024-002', 'Sarah Supervisor', 'Approved', '2025-08-22 16:58:23', '2025-08-22 16:58:23'),
(3, '2024-08-13', 'Toll & Parking', 'NLEX Toll', 250.00, 'Toll fees for project delivery', 'Exempted', 0.00, NULL, 'Cash', 'Delivery Truck 03', 'Job #2024-001', 'Mike Coordinator', 'Approved', '2025-08-22 16:58:23', '2025-08-22 16:58:23'),
(4, '2024-08-12', 'Supplies', 'Hardware Store', 800.00, 'Safety equipment and tools', 'Withholding', 16.00, NULL, 'Bank', NULL, 'Maintenance', 'John Manager', 'Pending', '2025-08-22 16:58:23', '2025-08-22 16:58:23'),
(5, '2024-08-11', 'Other', 'Office Supplies Inc.', 350.00, 'Office materials and documentation', 'None', 0.00, NULL, 'Cash', NULL, NULL, NULL, 'Rejected', '2025-08-22 16:58:23', '2025-08-22 16:58:23');

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

CREATE TABLE `journal_entries` (
  `id` int(11) NOT NULL,
  `entry_id` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `account_code` varchar(10) NOT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00,
  `description` text NOT NULL,
  `reference` varchar(50) DEFAULT NULL,
  `source_module` varchar(50) DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `status` enum('Draft','Posted','Cancelled') DEFAULT 'Posted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `journal_entries`
--

INSERT INTO `journal_entries` (`id`, `entry_id`, `date`, `account_code`, `debit`, `credit`, `description`, `reference`, `source_module`, `approved_by`, `status`, `created_at`, `updated_at`) VALUES
(1, 'GL-1001', '2025-07-10', '5001', 8000.00, 0.00, 'Truck Fuel - Petron', 'COL-2001', 'Expenses', 'Admin', 'Posted', '2025-08-30 14:51:55', '2025-08-30 14:51:55'),
(2, 'GL-1002', '2025-07-11', '4001', 0.00, 25000.00, 'Client Payment - ABC Construction', 'COL-2002', 'Collections', 'Admin', 'Posted', '2025-08-30 14:51:55', '2025-08-30 14:51:55');

-- --------------------------------------------------------

--
-- Table structure for table `liquidation_records`
--

CREATE TABLE `liquidation_records` (
  `id` int(11) NOT NULL,
  `liquidation_id` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `employee` varchar(100) NOT NULL,
  `purpose` text NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `liquidation_records`
--

INSERT INTO `liquidation_records` (`id`, `liquidation_id`, `date`, `employee`, `purpose`, `total_amount`, `status`, `created_at`, `updated_at`) VALUES
(1, 'LQ-2025-001', '2025-08-01', 'John Doe', 'Fuel Reimbursement', 1500.00, 'Approved', '2025-08-30 14:51:55', '2025-08-30 14:51:55');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `success` tinyint(1) DEFAULT 0,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip_address`, `username`, `success`, `attempted_at`) VALUES
(135, '::1', 'admin', 1, '2025-09-09 03:15:50'),
(136, '::1', 'admin', 1, '2025-09-09 09:37:04'),
(137, '::1', 'admin', 1, '2025-09-09 13:23:14'),
(138, '::1', 'admin', 1, '2025-09-09 18:40:08'),
(139, '::1', 'admin', 1, '2025-09-09 19:09:57'),
(140, '::1', 'admin', 1, '2025-09-09 19:59:57'),
(141, '::1', 'admin', 1, '2025-09-09 21:14:13'),
(142, '::1', 'admin', 1, '2025-09-09 21:41:07'),
(143, '::1', 'admin', 1, '2025-09-09 22:06:34'),
(144, '::1', 'admin', 1, '2025-09-09 22:24:38'),
(145, '::1', 'admin', 1, '2025-09-09 23:11:44'),
(146, '::1', 'admin', 1, '2025-09-09 23:27:18'),
(147, '::1', 'admin', 1, '2025-09-10 10:21:59'),
(148, '::1', 'admin', 1, '2025-09-10 10:24:34'),
(149, '::1', 'admin', 1, '2025-09-10 10:48:37'),
(150, '::1', 'admin', 1, '2025-09-10 11:02:41'),
(151, '::1', 'admin', 1, '2025-09-10 11:37:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `username`, `password`, `role`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'admin@system.com', 'admin', '$2y$10$cBzjiYnpoPsVWBR/Iv.8q.CTFH9UVX78KtPO15WGfXa1RaEusgv6.', 'admin', '2025-08-26 21:30:57', '2025-09-03 07:18:45', 1),
(5, 'testuser@gmail.com', 'testuser', '$2y$10$JdzfERQRwXG9dpvp.upm1eDhUIEfba9H5JsuVNTH5EfDWv2adKFGW', 'user', '2025-08-26 22:39:59', '2025-08-27 04:57:46', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_id`, `ip_address`, `user_agent`, `created_at`, `expires_at`, `is_active`) VALUES
(112, 1, 'bvmldan5q486a6n5vsfdrdf6ah', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-10 11:37:18', '2025-09-10 11:47:18', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_code` (`account_code`);

--
-- Indexes for table `collections`
--
ALTER TABLE `collections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `entry_id` (`entry_id`),
  ADD KEY `account_code` (`account_code`);

--
-- Indexes for table `liquidation_records`
--
ALTER TABLE `liquidation_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `liquidation_id` (`liquidation_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempted_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `collections`
--
ALTER TABLE `collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `liquidation_records`
--
ALTER TABLE `liquidation_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=152;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD CONSTRAINT `journal_entries_ibfk_1` FOREIGN KEY (`account_code`) REFERENCES `chart_of_accounts` (`account_code`);

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
