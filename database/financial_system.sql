-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 27, 2025 at 07:58 PM
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
(2, 'Monthly', 'Operations', 'Labor', 1000000.00, 100000.00, 'Juan Dela Cruz', 'Approved', 'Payment for laborers during monthly operation', '2025-08-14 14:09:54', '2025-08-14 14:09:54'),
(3, 'Monthly', 'Maintenance', 'Maintenance', 300000.00, 50000.00, 'Mark Avelino', 'Approved', 'Maintenance for cranes and other equipment during operations', '2025-08-14 14:11:18', '2025-08-14 14:11:18'),
(4, 'Daily', 'Accounting', 'Truck Lease', 10000.00, 10000.00, 'Francislloyd Manabat', 'Approved', 'Sudden cost due to accident during driving', '2025-08-14 14:13:28', '2025-08-14 14:13:28'),
(6, 'Monthly', 'Logistics', 'Truck Lease', 100000.00, 100000.00, 'Juan Dela Cruz', 'Approved', 'asd', '2025-08-16 18:16:45', '2025-08-16 18:16:45'),
(7, 'Monthly', 'Operations', 'Maintenance', 1111110.00, 100000.00, 'Juan Dela Cruz', 'Approved', 'asd', '2025-08-16 18:17:34', '2025-08-16 18:18:08'),
(8, 'Monthly', 'Operations', 'Crane Rental', 1000000.00, 150000.00, 'Francislloyd Manabat', 'Approved', 'asd', '2025-08-16 18:19:04', '2025-08-16 18:19:04'),
(9, 'Daily', 'Operations', 'Fuel', 100000.00, 10000.00, 'Mark Avelino', 'Approved', 'asdasd', '2025-08-16 18:19:39', '2025-08-16 18:19:39'),
(11, 'Annually', 'Administration', 'Labor', 1000000.00, 400000.00, 'Juan Dela Cruz', 'Approved', 'budget for 2026', '2025-08-20 15:05:59', '2025-08-27 06:02:19');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collections`
--

INSERT INTO `collections` (`id`, `client_name`, `invoice_no`, `billing_date`, `due_date`, `amount_base`, `vat_applied`, `vat_rate`, `vat_amount`, `amount_due`, `amount_paid`, `penalty`, `mode_of_payment`, `payment_status`, `receipt_type`, `collector_name`, `created_at`) VALUES
(7, 'clienttest5', 'test-00000005', '2025-08-03', '2025-08-10', 10000.00, 'Yes', 12.00, 1200.00, 11200.00, 1000.00, 911.20, 'Cash', 'Partial', 'VAT Receipt', 'collectortest5', '2025-08-16 16:04:46'),
(8, 'clienttest', 'test-00000000', '2025-08-17', '2025-08-24', 1000000.00, 'No', 12.00, 0.00, 1000000.00, 1000000.00, 0.00, 'Cash', 'Paid', 'Acknowledgment', 'collectortest', '2025-08-16 18:22:18'),
(9, 'clienttest1', 'test-00000001', '2025-08-17', '2025-08-24', 100000.00, 'Yes', 12.00, 12000.00, 112000.00, 10000.00, 0.00, 'Cash', 'Partial', 'Acknowledgment', 'collectortest1', '2025-08-16 18:24:41'),
(10, 'clienttest2', 'test-00000002', '2025-08-10', '2025-08-16', 100000.00, 'Yes', 12.00, 12000.00, 112000.00, 50000.00, 850.00, 'Cash', 'Partial', 'VAT Receipt', 'collectortest2', '2025-08-16 18:25:43'),
(11, 'clienttest3', 'test-00000003', '2025-07-13', '2025-07-20', 100000.00, 'Yes', 12.00, 12000.00, 112000.00, 10000.00, 3604.00, 'Bank Transfer', 'Partial', 'Acknowledgment', 'collectortest3', '2025-08-16 18:26:53'),
(12, 'clienttest4', 'test-00000004', '2025-06-17', '2025-07-22', 50000.00, 'No', 12.00, 0.00, 50000.00, 0.00, 2100.00, 'Cash', 'Unpaid', 'Acknowledgment', 'collectortest4', '2025-08-16 18:28:29'),
(13, 'clienttest6', 'test-00000006', '2025-07-27', '2025-08-10', 174760.00, 'Yes', 12.00, 20971.20, 195731.20, 30000.00, 1844.39, 'Bank Transfer', 'Partial', 'VAT Receipt', 'collectortest6', '2025-08-16 18:30:04'),
(16, 'clienttest7', 'test-00000007', '2025-08-27', '2025-09-03', 100000.00, 'Yes', 12.00, 12000.00, 112000.00, 112000.00, 0.00, 'Cash', 'Paid', 'VAT Receipt', 'collectortest7', '2025-08-27 06:01:10');

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
(3, '::1', 'admin', 1, '2025-08-26 22:08:37'),
(4, '::1', 'admin', 1, '2025-08-26 22:09:43'),
(7, '::1', 'admin', 1, '2025-08-26 22:12:27'),
(9, '::1', 'testuser', 1, '2025-08-26 22:25:33'),
(10, '::1', 'testuser', 1, '2025-08-26 22:31:18'),
(11, '::1', 'testuser', 1, '2025-08-26 22:31:50'),
(12, '::1', 'testuser', 1, '2025-08-26 22:32:39'),
(13, '::1', 'testuser', 1, '2025-08-26 22:32:55'),
(14, '::1', 'testuser', 1, '2025-08-26 22:33:08'),
(15, '::1', 'admin', 1, '2025-08-26 22:35:12'),
(16, '::1', 'admin', 1, '2025-08-26 22:38:01'),
(17, '::1', 'admin', 1, '2025-08-26 22:39:14'),
(18, '::1', 'testuser', 1, '2025-08-26 22:40:27'),
(24, '::1', 'admin', 1, '2025-08-27 04:56:27'),
(25, '::1', 'testuser', 1, '2025-08-27 04:56:48'),
(26, '::1', 'admin', 1, '2025-08-27 04:57:00'),
(28, '::1', 'admin', 1, '2025-08-27 04:57:38'),
(29, '::1', 'admin', 1, '2025-08-27 05:39:45'),
(30, '::1', 'admin', 1, '2025-08-27 05:47:02'),
(31, '::1', 'testuser', 1, '2025-08-27 05:48:43'),
(33, '::1', 'admin', 1, '2025-08-27 05:58:43'),
(34, '::1', 'admin', 1, '2025-08-27 06:07:04'),
(35, '::1', 'admin', 1, '2025-08-27 17:12:16'),
(36, '::1', 'admin', 1, '2025-08-27 17:22:38');

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
(1, 'admin@system.com', 'admin', '$2y$10$cBzjiYnpoPsVWBR/Iv.8q.CTFH9UVX78KtPO15WGfXa1RaEusgv6.', 'admin', '2025-08-26 21:30:57', '2025-08-27 05:08:20', 1),
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
(1, 1, '4krlmri85mtifhhd2jcl3o3l0q', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 22:08:37', '2025-08-26 16:38:37', 0),
(2, 1, 'pfqqkev937pgk9eof7fp14opni', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 22:09:43', '2025-08-26 16:39:43', 0),
(3, 1, 'p0544f2pc1o2n1i2k516b1mdn0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 22:12:27', '2025-08-26 16:42:27', 0),
(10, 1, 'q1b7v7crv0ouffduf6cp0kk7po', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 22:35:12', '2025-08-26 17:05:12', 0),
(11, 1, '7k7afq12frjigsdi5vqbbspbn6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 22:38:01', '2025-08-26 17:08:01', 0),
(12, 1, 'f0sc0cmoke3gum4kvvkc2ls1u2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 22:39:14', '2025-08-26 17:09:14', 0),
(13, 5, '2n8d6vrp7q6910idqovh3j1ebm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-26 22:40:27', '2025-08-26 17:10:27', 0),
(14, 1, '70rpahb2qcao3skae039fisru5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-27 04:56:27', '2025-08-26 23:26:27', 0),
(15, 5, 'c788h3f56u7iqsjip052e45nim', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-27 04:56:48', '2025-08-26 23:26:48', 0),
(16, 1, '4mmmdrmkgqo4r9cgovt9gvre2b', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-27 04:57:00', '2025-08-26 23:27:00', 0),
(17, 1, 'kh71rhmlr49q8fpaq0vb6a6s0f', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-27 04:57:38', '2025-08-26 23:27:38', 1),
(18, 1, '2emoi1976muukajke5l514eiel', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-27 05:39:45', '2025-08-27 00:09:45', 0),
(19, 1, 'afi1kk3h05sj8a012quq2geb3n', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-27 05:47:02', '2025-08-27 00:17:02', 0),
(20, 5, 'fq6ho18nig5at8o0qbdgv4cv15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-27 05:48:43', '2025-08-27 00:18:43', 0),
(21, 1, 'roabap3k3bpurjhhvevclm5gta', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-27 05:58:43', '2025-08-27 00:08:43', 0),
(22, 1, 'ipna24rttbbol2agku9n2cu1m0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-27 06:07:04', '2025-08-27 00:17:04', 1),
(23, 1, '246qbc07resb1bsfakjkkvmat6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-27 17:12:16', '2025-08-27 11:22:16', 1),
(24, 1, '2llpn18p2djfkfqoqd9otuv7b8', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-27 17:22:38', '2025-08-27 11:32:38', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `collections`
--
ALTER TABLE `collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
