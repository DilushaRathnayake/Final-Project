-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 26, 2026 at 06:05 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smart_budget_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `banks`
--

CREATE TABLE `banks` (
  `bank_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT '0.00',
  `linked_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `banks`
--

INSERT INTO `banks` (`bank_id`, `user_id`, `bank_name`, `account_number`, `balance`, `linked_at`) VALUES
(2, 3, 'BOC', '46613587', 9000.00, '2026-04-26 05:38:45');

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `category` varchar(100) NOT NULL,
  `allocated_amount` decimal(10,2) NOT NULL,
  `priority` enum('Need','Want') DEFAULT 'Need',
  `month_year` varchar(7) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `budgets`
--

INSERT INTO `budgets` (`id`, `user_id`, `category`, `allocated_amount`, `priority`, `month_year`, `created_at`) VALUES
(6, 11, 'Food', 9000.00, 'Need', '2026-04', '2026-04-24 02:33:33'),
(7, 11, 'Transport', 10000.00, 'Need', '2026-04', '2026-04-24 04:06:33'),
(8, 11, 'Entertainment', 5000.00, 'Need', '2026-04', '2026-04-24 04:08:19');

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `category_id` int NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `icon` varchar(30) DEFAULT NULL,
  `color_hex` varchar(7) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_records`
--

CREATE TABLE `expense_records` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `note` text,
  `type` enum('Fixed','Variable') DEFAULT 'Variable',
  `description` text,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `expense_records`
--

INSERT INTO `expense_records` (`id`, `user_id`, `amount`, `category`, `note`, `type`, `description`, `date`, `created_at`) VALUES
(8, 3, 16000.00, 'Food', 'Foods', 'Variable', NULL, '2026-04-09', '2026-04-09 06:02:29'),
(9, 11, 10000.00, 'Food', '', 'Variable', NULL, '2026-04-24', '2026-04-24 02:30:12'),
(10, 11, 8000.00, 'Transport', '', 'Variable', NULL, '2026-04-24', '2026-04-24 04:06:21'),
(11, 11, 5000.00, 'Entertainment', '', 'Variable', NULL, '2026-04-24', '2026-04-24 04:06:58'),
(12, 3, 1000.00, 'Seettu', NULL, 'Variable', 'Monthly Seettu Installment', '2026-04-26', '2026-04-26 05:04:25'),
(13, 3, 1000.00, 'Loan Repayment', NULL, 'Variable', 'Monthly EMI Payment', '2026-04-26', '2026-04-26 05:39:26');

-- --------------------------------------------------------

--
-- Table structure for table `factory_seettu`
--

CREATE TABLE `factory_seettu` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `group_name` varchar(100) NOT NULL,
  `monthly_amount` decimal(10,2) NOT NULL,
  `total_members` int DEFAULT '0',
  `completed_months` int DEFAULT '0',
  `payout_month` int DEFAULT NULL,
  `start_date` date NOT NULL,
  `status` enum('Active','Completed') DEFAULT 'Active',
  `total_months` int NOT NULL DEFAULT '10'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `factory_seettu`
--

INSERT INTO `factory_seettu` (`id`, `user_id`, `group_name`, `monthly_amount`, `total_members`, `completed_months`, `payout_month`, `start_date`, `status`, `total_months`) VALUES
(7, NULL, 'First Seettu Group', 1000.00, 0, 0, NULL, '2026-02-04', 'Active', 10);

-- --------------------------------------------------------

--
-- Table structure for table `income_records`
--

CREATE TABLE `income_records` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `source` varchar(100) DEFAULT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `type` enum('Salary','Other') DEFAULT 'Other',
  `added_by` enum('User','Admin') DEFAULT 'User'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `income_records`
--

INSERT INTO `income_records` (`id`, `user_id`, `amount`, `source`, `date`, `created_at`, `type`, `added_by`) VALUES
(10, 6, 46000.00, 'Monthly Salary (Net after EPF)', '2026-04-09', '2026-04-09 05:59:37', 'Salary', 'User'),
(11, 3, 10000.00, 'Part Time', '2026-04-09', '2026-04-09 06:01:50', 'Other', 'User'),
(12, 11, 10000.00, 'Overtime', '2026-04-24', '2026-04-24 02:29:30', 'Other', 'User'),
(13, 11, 46000.00, 'Monthly Salary (Net after EPF)', '2026-04-24', '2026-04-24 03:32:30', 'Salary', 'User'),
(15, 3, 46000.00, 'Monthly Salary (Net after EPF)', '2026-04-24', '2026-04-24 03:39:52', 'Salary', 'User'),
(16, 10, 46000.00, 'Monthly Salary (Net after EPF)', '2026-04-24', '2026-04-24 03:39:52', 'Salary', 'User');

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `loan_source` varchar(100) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `monthly_repayment` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `payment_status` varchar(20) DEFAULT 'Pending',
  `status` enum('Active','Paid') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `user_id`, `loan_source`, `total_amount`, `monthly_repayment`, `due_date`, `payment_status`, `status`, `created_at`) VALUES
(3, 3, 'Bank of Ceylon (BOC)', 10000.00, 1000.00, '2026-04-09', 'Paid', 'Active', '2026-04-09 06:03:48'),
(4, 11, 'People\'s Bank', 100000.00, 10000.00, '2027-04-24', 'Pending', 'Active', '2026-04-24 02:37:19');

-- --------------------------------------------------------

--
-- Table structure for table `payday_plans`
--

CREATE TABLE `payday_plans` (
  `plan_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `plan_month` varchar(20) DEFAULT NULL,
  `total_salary` decimal(10,2) DEFAULT NULL,
  `fixed_debts` decimal(10,2) DEFAULT NULL,
  `savings_target` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `needs_amount` decimal(10,2) DEFAULT NULL,
  `wants_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('Planned','Completed') DEFAULT 'Planned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payday_plans`
--

INSERT INTO `payday_plans` (`plan_id`, `user_id`, `plan_month`, `total_salary`, `fixed_debts`, `savings_target`, `created_at`, `needs_amount`, `wants_amount`, `status`) VALUES
(1, 3, 'April 2026', 0.00, 0.00, 0.00, '2026-04-02 08:48:28', NULL, NULL, 'Planned');

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `category` enum('Bill','Loan','Boarding','Other') DEFAULT 'Bill',
  `status` enum('Pending','Paid') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salary_records`
--

CREATE TABLE `salary_records` (
  `salary_id` int NOT NULL,
  `user_id` int NOT NULL,
  `gross_salary` decimal(10,2) NOT NULL,
  `epf_employee` decimal(10,2) NOT NULL,
  `epf_employer` decimal(10,2) NOT NULL,
  `etf_employer` decimal(10,2) NOT NULL,
  `net_salary` decimal(10,2) NOT NULL,
  `pay_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `salary_records`
--

INSERT INTO `salary_records` (`salary_id`, `user_id`, `gross_salary`, `epf_employee`, `epf_employer`, `etf_employer`, `net_salary`, `pay_date`) VALUES
(10, 3, 50000.00, 4000.00, 6000.00, 1500.00, 46000.00, '2026-04-24'),
(11, 10, 50000.00, 4000.00, 6000.00, 1500.00, 46000.00, '2026-04-24'),
(12, 11, 50000.00, 4000.00, 6000.00, 1500.00, 46000.00, '2026-04-24');

-- --------------------------------------------------------

--
-- Table structure for table `savings`
--

CREATE TABLE `savings` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `goal_name` varchar(255) NOT NULL,
  `location` enum('Bank','At Home','Seettu') DEFAULT 'At Home',
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `savings`
--

INSERT INTO `savings` (`id`, `user_id`, `amount`, `goal_name`, `location`, `date`, `created_at`) VALUES
(3, 3, 10000.00, 'House/Land', 'Bank', '2026-04-09', '2026-04-09 06:02:55'),
(4, 3, 2000.00, 'Education', 'At Home', '2026-04-09', '2026-04-09 06:03:12'),
(5, 11, 500000.00, 'House/Land', 'Bank', '2026-04-24', '2026-04-24 02:34:45'),
(6, 11, 10000.00, 'Seettu Winner - April', 'At Home', '2026-04-24', '2026-04-24 03:58:11');

-- --------------------------------------------------------

--
-- Table structure for table `seettu_members`
--

CREATE TABLE `seettu_members` (
  `id` int NOT NULL,
  `seettu_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `joined_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `seettu_members`
--

INSERT INTO `seettu_members` (`id`, `seettu_id`, `user_id`, `joined_date`) VALUES
(9, 7, 3, '2026-04-24'),
(10, 7, 10, '2026-04-24'),
(11, 7, 11, '2026-04-24');

-- --------------------------------------------------------

--
-- Table structure for table `seettu_payments`
--

CREATE TABLE `seettu_payments` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `seettu_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_month` varchar(20) NOT NULL,
  `status` varchar(20) DEFAULT 'Paid',
  `pay_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seettu_winners`
--

CREATE TABLE `seettu_winners` (
  `winner_id` int NOT NULL,
  `seettu_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `draw_month` varchar(50) DEFAULT NULL,
  `payout_amount` decimal(10,2) DEFAULT NULL,
  `draw_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `seettu_winners`
--

INSERT INTO `seettu_winners` (`winner_id`, `seettu_id`, `user_id`, `draw_month`, `payout_amount`, `draw_date`) VALUES
(6, 7, 11, 'April', 10000.00, '2026-04-24 04:00:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `nic` varchar(12) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `factory` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `role` enum('admin','supervisor','user') DEFAULT 'user',
  `lang` enum('en','si','ta') DEFAULT 'en',
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `profile_pic` varchar(255) DEFAULT 'default_avatar.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `nic`, `phone`, `factory`, `department`, `role`, `lang`, `password_hash`, `created_at`, `profile_pic`) VALUES
(3, 'Test1', 'user3test1@example.com', '985621478v', '0774569823', 'Inter Fashion (pvt) Ltd', 'Sewigning', 'user', 'en', '$2y$10$sFk60w6JT2/N4w774efgguYWkEHl2nLytpqB2DImK5pM6mEsfnowm', '2026-04-01 02:30:44', '1775715199_images.jpg'),
(4, 'Admin', 'user4test1@example.com', '200156602170', '0775689321', 'Inter Fashion (pvt) Ltd', 'Admin', 'admin', 'en', '$2y$10$6CxegCyJEl2Lb5Z8mcGnAesn1cd5YpnJ7WXgnG8VW90kDCeBAqcne', '2026-04-04 00:38:22', '1775715147_professional-profile-pictures-1500-x-2100-bvjgzg0cwa8r051t.jpg'),
(6, 'Supervisor', 'supervisor1@gmail.com', '641235678v', '0771213569', 'Inter Fashion (pvt) Ltd', 'Sewing', 'supervisor', 'en', '$2y$10$roTIyTmS7ncOQEJHr8JfXe9lRAF57CyvBo7dUNFbXWWiWBzK6yY9m', '2026-04-05 01:07:44', '1775715114_corporate-headshots.png'),
(10, 'Test2', 'Test2@gmail.com', '846291487v', '0754568956', 'Inter Fashion (pvt) Ltd', 'Sewigning', 'user', 'en', '$2y$10$cmbdVPChx0heRbvR9Gu3TelkN9REBgfiLq5g4YSBwaMFM43ay3Nl6', '2026-04-18 01:16:15', 'default_avatar.png'),
(11, 'Dilusha Rathnayaka', 'Test3@gmail.com', '899452521v', '0764779416', 'Inter Fashion (pvt) Ltd', 'Sewigning', 'user', 'en', '$2y$10$A2u7tCFsHOaZZ4FJ6IFqtOAkXVwXVPTOZg80KWBLHXvEjgDP4Qb0S', '2026-04-24 02:15:34', '1776998433_premium_photo-1661726660137-61b182d93809.avif');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `banks`
--
ALTER TABLE `banks`
  ADD PRIMARY KEY (`bank_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `expense_records`
--
ALTER TABLE `expense_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `factory_seettu`
--
ALTER TABLE `factory_seettu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `income_records`
--
ALTER TABLE `income_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payday_plans`
--
ALTER TABLE `payday_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `salary_records`
--
ALTER TABLE `salary_records`
  ADD PRIMARY KEY (`salary_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `savings`
--
ALTER TABLE `savings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `seettu_members`
--
ALTER TABLE `seettu_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seettu_id` (`seettu_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `seettu_payments`
--
ALTER TABLE `seettu_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `seettu_winners`
--
ALTER TABLE `seettu_winners`
  ADD PRIMARY KEY (`winner_id`),
  ADD KEY `seettu_id` (`seettu_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `nic` (`nic`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `banks`
--
ALTER TABLE `banks`
  MODIFY `bank_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_records`
--
ALTER TABLE `expense_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `factory_seettu`
--
ALTER TABLE `factory_seettu`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `income_records`
--
ALTER TABLE `income_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payday_plans`
--
ALTER TABLE `payday_plans`
  MODIFY `plan_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salary_records`
--
ALTER TABLE `salary_records`
  MODIFY `salary_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `savings`
--
ALTER TABLE `savings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `seettu_members`
--
ALTER TABLE `seettu_members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `seettu_payments`
--
ALTER TABLE `seettu_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `seettu_winners`
--
ALTER TABLE `seettu_winners`
  MODIFY `winner_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `banks`
--
ALTER TABLE `banks`
  ADD CONSTRAINT `banks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payday_plans`
--
ALTER TABLE `payday_plans`
  ADD CONSTRAINT `payday_plans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `reminders`
--
ALTER TABLE `reminders`
  ADD CONSTRAINT `reminders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `salary_records`
--
ALTER TABLE `salary_records`
  ADD CONSTRAINT `salary_records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `savings`
--
ALTER TABLE `savings`
  ADD CONSTRAINT `savings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `seettu_members`
--
ALTER TABLE `seettu_members`
  ADD CONSTRAINT `seettu_members_ibfk_1` FOREIGN KEY (`seettu_id`) REFERENCES `factory_seettu` (`id`),
  ADD CONSTRAINT `seettu_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `seettu_winners`
--
ALTER TABLE `seettu_winners`
  ADD CONSTRAINT `seettu_winners_ibfk_1` FOREIGN KEY (`seettu_id`) REFERENCES `factory_seettu` (`id`),
  ADD CONSTRAINT `seettu_winners_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
