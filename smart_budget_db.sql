-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 06, 2026 at 12:09 AM
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
  `status` enum('Active','Completed') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `status` enum('Active','Paid') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(3, 'Test1', 'user3test1@example.com', '985621478v', '0774569823', 'Inter Fashion (pvt) Ltd', 'Sewing Deparment', 'user', 'en', '$2y$10$sFk60w6JT2/N4w774efgguYWkEHl2nLytpqB2DImK5pM6mEsfnowm', '2026-04-01 02:30:44', 'default_avatar.png'),
(4, 'Admin', 'user4test1@example.com', '200156602170', NULL, 'Inter Fashion (pvt) Ltd', 'Admin', 'admin', 'en', '$2y$10$6CxegCyJEl2Lb5Z8mcGnAesn1cd5YpnJ7WXgnG8VW90kDCeBAqcne', '2026-04-04 00:38:22', 'default_avatar.png'),
(6, 'Supervisor', 'supervisor1@gmail.com', '641235678v', '0771213569', 'Inter Fashion (pvt) Ltd', 'Sewing Deparment', 'supervisor', 'en', '$2y$10$roTIyTmS7ncOQEJHr8JfXe9lRAF57CyvBo7dUNFbXWWiWBzK6yY9m', '2026-04-05 01:07:44', '1775352430_WhatsApp Image 2025-01-09 at 11.24.13_3e5ef257.jpg');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `category_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_records`
--
ALTER TABLE `expense_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `factory_seettu`
--
ALTER TABLE `factory_seettu`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `income_records`
--
ALTER TABLE `income_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `salary_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `savings`
--
ALTER TABLE `savings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `seettu_members`
--
ALTER TABLE `seettu_members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `seettu_winners`
--
ALTER TABLE `seettu_winners`
  MODIFY `winner_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

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
