-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 10, 2026 at 01:47 AM
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
-- Database: `subdivision_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'ADD_HOUSE', 'Added house: BLOCK 13 LOT 31-32 - Owner: Kazwell', '::1', '2025-10-21 17:12:30'),
(2, 1, 'ADD_RESIDENT', 'Added resident: Marthalyn Balaba', '::1', '2025-10-21 17:12:42'),
(3, 1, 'UPDATE_RESIDENT', 'Updated resident ID: 1', '::1', '2025-10-21 17:12:49'),
(4, 1, 'UPDATE_RESIDENT', 'Updated resident ID: 1', '::1', '2025-10-21 17:12:59'),
(5, 1, 'ADD_PAYMENT', 'Added payment for house ID: 1 - Amount: 100000', '::1', '2025-10-21 17:13:32'),
(6, 1, 'ADD_VEHICLE', 'Added vehicle: KWYZX', '::1', '2025-10-21 17:13:41'),
(7, 1, 'UPDATE_PAYMENT', 'Updated payment ID: 1', '::1', '2025-10-21 17:13:53'),
(8, 1, 'CLEAR_AUDIT_LOG', 'Cleared audit logs older than 30 days', '::1', '2025-10-21 17:14:06'),
(9, 1, 'USER_LOGOUT', 'User admin logged out', '::1', '2025-10-21 17:14:08'),
(10, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2025-10-21 17:14:11'),
(11, 1, 'SOFT_DELETE_HOUSE', 'Soft deleted house ID: 1', '::1', '2025-10-21 17:15:04'),
(12, 1, 'USER_LOGOUT', 'User admin logged out', '::1', '2025-10-21 17:15:11'),
(13, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2025-10-21 17:15:18'),
(14, 1, 'USER_LOGOUT', 'User admin logged out', '::1', '2025-10-21 17:17:45'),
(15, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2025-10-21 17:17:49'),
(16, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2025-10-21 17:18:13'),
(17, 1, 'ADD_HOUSE', 'Added house: BLOCK 13 LOT31-32 - Owner: Kazwell', '::1', '2025-10-21 17:18:28'),
(18, 1, 'ADD_PAYMENT', 'Added payment for house ID: 2 - Amount: 50000', '::1', '2025-10-21 17:18:56'),
(19, 1, 'UPDATE_PAYMENT', 'Updated payment ID: 2', '::1', '2025-10-21 17:19:04'),
(20, 1, 'USER_LOGOUT', 'User admin logged out', '::1', '2025-10-21 17:19:16'),
(21, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2025-10-21 17:19:23'),
(22, 1, 'ADD_HOUSE', 'Added house: BLOCK 13 LOT 31-32 - Owner: Kazwell', '::1', '2025-10-21 17:21:37'),
(23, 1, 'SOFT_DELETE_HOUSE', 'Soft deleted house ID: 2', '::1', '2025-10-21 17:21:43'),
(24, 1, 'USER_LOGOUT', 'User admin logged out', '::1', '2025-10-21 17:22:19'),
(25, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2025-10-21 17:35:27'),
(26, 1, 'USER_LOGOUT', 'User admin logged out', '::1', '2025-10-21 17:41:11'),
(27, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2025-10-21 17:41:14'),
(28, 1, 'ADD_HOUSE', 'Added house: 003 - Owner: Jimenez', '::1', '2025-10-21 18:20:58'),
(29, 1, 'SOFT_DELETE_HOUSE', 'Soft deleted house ID: 4', '::1', '2025-10-21 18:21:04'),
(30, 1, 'ADD_HOUSE', 'Added house: BLOCK 15 LOT 7-8 - Owner: Jimenez', '::1', '2025-10-21 18:21:18'),
(31, 1, 'ADD_RESIDENT', 'Added resident: Eironne Jimenez', '::1', '2025-10-21 18:21:46'),
(32, 1, 'ADD_PAYMENT', 'Added payment for house ID: 5 - Amount: 500', '::1', '2025-10-21 18:22:17'),
(33, 1, 'ADD_VEHICLE', 'Added vehicle: KKK065', '::1', '2025-10-21 18:24:20'),
(34, 1, 'UPDATE_VEHICLE', 'Updated vehicle ID: 2', '::1', '2025-10-21 18:24:25'),
(35, 1, 'USER_LOGOUT', 'User admin logged out', '::1', '2025-10-21 18:26:18'),
(36, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2025-10-21 18:26:29'),
(37, 1, 'USER_LOGOUT', 'User admin logged out', '::1', '2025-10-21 21:32:16'),
(38, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2025-10-21 21:33:07'),
(39, 1, 'ADD_HOUSE', 'Added house: BLOCK 16 LOT 32-33 - Owner: Maritha B. Pacudan', '::1', '2025-10-21 21:33:41'),
(40, 1, 'ADD_RESIDENT', 'Added resident: Kylee Jet B. Pacudan', '::1', '2025-10-21 21:34:32'),
(41, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2025-10-22 01:11:52'),
(42, 1, 'ADD_HOUSE', 'Added house: BLOCK 13 LOT 16-17 - Owner: Lalay', '::1', '2025-10-22 01:12:37'),
(43, 1, 'ADD_RESIDENT', 'Added resident: Sabelle J.', '::1', '2025-10-22 01:13:09'),
(44, 1, 'ADD_PAYMENT', 'Added payment for house ID: 7 - Amount: 5000', '::1', '2025-10-22 01:14:30'),
(45, 1, 'ADD_VEHICLE', 'Added vehicle: GTX3060', '::1', '2025-10-22 01:15:15'),
(46, 1, 'SOFT_DELETE_HOUSE', 'Soft deleted house ID: 5', '::1', '2025-10-22 01:16:10'),
(47, 1, 'RESTORE_ITEM', 'Restored from houses, ID: 5', '::1', '2025-10-22 01:16:31'),
(48, 1, 'SOFT_DELETE_HOUSE', 'Soft deleted house ID: 7', '::1', '2025-10-22 01:17:25'),
(49, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2025-10-24 23:07:26'),
(50, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2026-01-05 22:32:20'),
(51, 1, 'UPDATE_PAYMENT', 'Updated payment ID: 4', '::1', '2026-01-05 22:32:41'),
(52, 1, 'USER_LOGOUT', 'User admin logged out', '::1', '2026-01-05 22:54:53'),
(53, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2026-01-05 22:54:57'),
(54, 1, 'USER_LOGOUT', 'User admin logged out', '::1', '2026-01-05 22:57:16'),
(55, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2026-01-05 22:57:56'),
(56, 1, 'USER_LOGOUT', 'User admin logged out', '::1', '2026-01-05 23:03:56'),
(57, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2026-01-05 23:04:03'),
(58, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2026-01-05 23:31:01'),
(59, 1, 'USER_LOGOUT', 'User admin logged out', '::1', '2026-01-05 23:32:26'),
(60, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2026-01-05 23:32:29'),
(61, 1, 'ADD_HOUSE', 'Added house: DASDSA - Owner: DSADAS', '::1', '2026-01-05 23:32:35'),
(62, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2026-01-07 19:21:03'),
(63, 1, 'ADD_HOUSE', 'Added house: BLOCK 18 LOT 10-12 - Owner: Alexander Julian Balaba', '::1', '2026-01-07 19:21:49'),
(64, 1, 'ADD_RESIDENT', 'Added resident: Kevin D Cabanez', '::1', '2026-01-07 19:22:11'),
(65, 1, 'ADD_PAYMENT', 'Added payment for house ID: 9 - Amount: 500', '::1', '2026-01-07 19:22:48'),
(66, 1, 'USER_LOGOUT', 'User admin logged out', '::1', '2026-01-07 19:41:57'),
(67, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2026-01-07 19:42:00'),
(68, 1, 'CREATE_MAINTENANCE', 'Created maintenance request: CUTTING GRASS', '::1', '2026-01-07 19:43:38'),
(69, 1, 'ARCHIVE_MAINTENANCE', 'Archived maintenance ID 1', '::1', '2026-01-07 19:57:40'),
(70, 1, 'RESTORE_ITEM', 'Restored from maintenance_requests, ID: 1', '::1', '2026-01-07 19:57:48'),
(71, 1, 'UPDATE_PAYMENT', 'Updated payment ID: 5', '::1', '2026-01-07 20:00:27'),
(72, 1, 'UPDATE_RESIDENT', 'Updated resident ID: 2', '::1', '2026-01-07 20:01:24'),
(73, 1, 'SOFT_DELETE_RESIDENT', 'Soft deleted resident ID: 5', '::1', '2026-01-07 20:01:30'),
(74, 1, 'SOFT_DELETE_HOUSE', 'Soft deleted house ID: 3', '::1', '2026-01-07 20:01:56'),
(75, 1, 'SOFT_DELETE_HOUSE', 'Soft deleted house ID: 5', '::1', '2026-01-07 20:01:59'),
(76, 1, 'SOFT_DELETE_HOUSE', 'Soft deleted house ID: 6', '::1', '2026-01-07 20:02:02'),
(77, 1, 'SOFT_DELETE_HOUSE', 'Soft deleted house ID: 9', '::1', '2026-01-07 20:02:04'),
(78, 1, 'SOFT_DELETE_HOUSE', 'Soft deleted house ID: 8', '::1', '2026-01-07 20:02:06'),
(79, 1, 'SOFT_DELETE_RESIDENT', 'Soft deleted resident ID: 2', '::1', '2026-01-07 20:02:15'),
(80, 1, 'SOFT_DELETE_RESIDENT', 'Soft deleted resident ID: 3', '::1', '2026-01-07 20:02:17'),
(81, 1, 'SOFT_DELETE_RESIDENT', 'Soft deleted resident ID: 1', '::1', '2026-01-07 20:02:22'),
(82, 1, 'SOFT_DELETE_RESIDENT', 'Soft deleted resident ID: 4', '::1', '2026-01-07 20:02:25'),
(83, 1, 'SOFT_DELETE_PAYMENT', 'Soft deleted payment ID: 5', '::1', '2026-01-07 20:02:30'),
(84, 1, 'SOFT_DELETE_PAYMENT', 'Soft deleted payment ID: 4', '::1', '2026-01-07 20:02:32'),
(85, 1, 'ADD_HOUSE', 'Added house: BLOCK 13 LOT 31-32 - Owner: Misty Kazwell', '::1', '2026-01-07 20:03:08'),
(86, 1, 'ADD_RESIDENT', 'Added resident: Alexander Julian Balaba', '::1', '2026-01-07 20:03:22'),
(87, 1, 'ADD_PAYMENT', 'Added payment for house ID: 10 - Amount: 5000', '::1', '2026-01-07 20:04:10'),
(88, 1, 'ADD_VEHICLE', 'Added vehicle: KYX21', '::1', '2026-01-07 20:04:34'),
(89, 1, 'CREATE_MAINTENANCE', 'Created maintenance request: CUTTING GRASS', '::1', '2026-01-07 20:04:56'),
(90, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2026-01-07 20:23:14'),
(91, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2026-01-10 08:27:46'),
(92, 1, 'USER_LOGOUT', 'User admin logged out', '::1', '2026-01-10 08:27:55'),
(93, 1, 'USER_LOGIN', 'User admin logged in successfully', '::1', '2026-01-10 08:28:01'),
(94, 1, 'ADD_HOUSE', 'Added house: BLOCK 13 LOT31-32 - Owner: Jimenez', '::1', '2026-01-10 08:28:10'),
(95, 1, 'USER_LOGOUT', 'User admin logged out', '::1', '2026-01-10 08:28:12');

-- --------------------------------------------------------

--
-- Table structure for table `houses`
--

CREATE TABLE `houses` (
  `id` int(11) NOT NULL,
  `house_number` varchar(100) NOT NULL,
  `owner_name` varchar(150) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `status` enum('Occupied','Vacant') NOT NULL DEFAULT 'Occupied',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `houses`
--

INSERT INTO `houses` (`id`, `house_number`, `owner_name`, `contact_number`, `status`, `is_deleted`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'BLOCK 13 LOT 31-32', 'Kazwell', NULL, 'Occupied', 1, '2025-10-21 17:12:30', NULL, '2025-10-21 17:15:04'),
(2, 'BLOCK 13 LOT31-32', 'Kazwell', NULL, 'Occupied', 1, '2025-10-21 17:18:28', NULL, '2025-10-21 17:21:43'),
(3, 'BLOCK 13 LOT 31-32', 'Kazwell', NULL, 'Occupied', 1, '2025-10-21 17:21:37', NULL, '2026-01-07 20:01:56'),
(4, '003', 'Jimenez', NULL, 'Occupied', 1, '2025-10-21 18:20:58', NULL, '2025-10-21 18:21:04'),
(5, 'BLOCK 15 LOT 7-8', 'Jimenez', NULL, 'Vacant', 1, '2025-10-21 18:21:18', NULL, '2026-01-07 20:01:59'),
(6, 'BLOCK 16 LOT 32-33', 'Maritha B. Pacudan', NULL, 'Occupied', 1, '2025-10-21 21:33:41', NULL, '2026-01-07 20:02:02'),
(7, 'BLOCK 13 LOT 16-17', 'Lalay', NULL, 'Occupied', 1, '2025-10-22 01:12:37', NULL, '2025-10-22 01:17:25'),
(8, 'DASDSA', 'DSADAS', NULL, 'Occupied', 1, '2026-01-05 23:32:35', NULL, '2026-01-07 20:02:06'),
(9, 'BLOCK 18 LOT 10-12', 'Alexander Julian Balaba', NULL, 'Occupied', 1, '2026-01-07 19:21:49', NULL, '2026-01-07 20:02:04'),
(10, 'BLOCK 13 LOT 31-32', 'Misty Kazwell', NULL, 'Occupied', 0, '2026-01-07 20:03:08', NULL, NULL),
(11, 'BLOCK 13 LOT31-32', 'Jimenez', NULL, 'Occupied', 0, '2026-01-10 08:28:10', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `id` int(11) NOT NULL,
  `house_id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('Low','Medium','High') DEFAULT 'Low',
  `status` enum('Pending','In Progress','Completed','Cancelled') DEFAULT 'Pending',
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maintenance_requests`
--

INSERT INTO `maintenance_requests` (`id`, `house_id`, `resident_id`, `title`, `description`, `priority`, `status`, `is_deleted`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 3, 2, 'CUTTING GRASS', 'Paputol sailahang mga grass sa kilid sa balay', 'High', 'Pending', 0, NULL, '2026-01-07 19:43:38', '2026-01-07 19:57:48'),
(2, 10, 6, 'CUTTING GRASS', 'Palimpyo sa lawn', 'Low', 'Pending', 0, NULL, '2026-01-07 20:04:55', '2026-01-07 20:04:55');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `house_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `due_month` varchar(20) NOT NULL,
  `payment_type` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `house_id`, `amount`, `payment_date`, `due_month`, `payment_type`, `status`, `description`, `is_deleted`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 10000.00, '2025-10-21', 'November 2024', 'Monthly Due', 'Paid', 'Advance Payment', 0, '2025-10-21 17:13:32', '2025-10-21 17:13:53', NULL),
(2, 2, 50000.00, '2025-10-21', 'November 2024', 'Special Fee', 'Paid', 'Tip for Christmas', 0, '2025-10-21 17:18:56', '2025-10-21 17:19:04', NULL),
(3, 5, 500.00, '2025-10-21', 'November 2024', 'Monthly Due', 'Paid', 'Payment for Monthly Due', 0, '2025-10-21 18:22:17', NULL, NULL),
(4, 6, 5000.00, '2025-10-22', 'February 2025', 'Special Fee', 'Paid', 'Tip for Security', 1, '2025-10-22 01:14:30', '2026-01-05 22:32:41', '2026-01-07 20:02:32'),
(5, 9, 500.00, '2026-01-07', 'January 2026', 'Monthly Due', 'Paid', '', 1, '2026-01-07 19:22:48', '2026-01-07 20:00:27', '2026-01-07 20:02:30'),
(6, 10, 5000.00, '2026-01-07', 'January 2026', 'Monthly Due', 'Paid', 'Advance Payment', 0, '2026-01-07 20:04:10', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `residents`
--

CREATE TABLE `residents` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `house_id` int(11) NOT NULL,
  `relationship` enum('Owner','Family','Tenant') NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `residents`
--

INSERT INTO `residents` (`id`, `name`, `contact_number`, `phone`, `house_id`, `relationship`, `date_of_birth`, `is_primary`, `is_deleted`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Marthalyn C. Balaba', NULL, '0997358903111', 1, 'Family', NULL, 0, 1, '2025-10-21 17:12:42', '2025-10-21 17:12:59', '2026-01-07 20:02:22'),
(2, 'Eironne Jimenez', NULL, '099111002', 5, 'Tenant', NULL, 0, 1, '2025-10-21 18:21:46', '2026-01-07 20:01:24', '2026-01-07 20:02:15'),
(3, 'Kylee Jet B. Pacudan', NULL, '09973589031', 6, 'Family', NULL, 0, 1, '2025-10-21 21:34:32', NULL, '2026-01-07 20:02:17'),
(4, 'Sabelle J.', NULL, '099735890311111', 7, 'Family', NULL, 0, 1, '2025-10-22 01:13:09', NULL, '2026-01-07 20:02:25'),
(5, 'Kevin D Cabanez', NULL, '09997112', 9, 'Family', NULL, 0, 1, '2026-01-07 19:22:11', NULL, '2026-01-07 20:01:30'),
(6, 'Alexander Julian Balaba', NULL, '09973589031', 10, 'Family', NULL, 0, 0, '2026-01-07 20:03:22', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`, `updated_by`) VALUES
(1, 'system_name', 'Subdivision Management System', 'Name of the system', '2025-10-21 17:11:17', 1),
(2, 'monthly_due_amount', '500.00', 'Default monthly due amount', '2025-10-21 17:11:17', 1),
(3, 'contact_email', 'admin@subdivision.com', 'System contact email', '2025-10-21 17:11:17', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$7yE3lD8S0N4Gk6FzVw9tKu2l5XkNnT4T6m5hX3z2g1C0vF1iYt8eS', 'Administrator', 'admin', 1, '2025-10-21 17:11:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `plate_number` varchar(50) NOT NULL,
  `model` varchar(100) NOT NULL,
  `color` varchar(50) NOT NULL,
  `vehicle_type` enum('Car','Motorcycle','Truck','SUV','Van') NOT NULL,
  `house_id` int(11) NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `plate_number`, `model`, `color`, `vehicle_type`, `house_id`, `is_deleted`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'KWYZX', 'Click 125 V3', 'Black', 'Car', 1, 0, '2025-10-21 17:13:41', NULL, NULL),
(2, 'KKK065', '4X4', 'Violet', 'Car', 5, 0, '2025-10-21 18:24:20', '2025-10-21 18:24:25', NULL),
(3, 'GTX3060', 'Revo 2010', 'Red', 'Car', 7, 0, '2025-10-22 01:15:15', NULL, NULL),
(4, 'KYX21', 'Honda Click V3', 'Red', 'Motorcycle', 10, 0, '2026-01-07 20:04:34', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_audit_user` (`user_id`);

--
-- Indexes for table `houses`
--
ALTER TABLE `houses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_maintenance_house` (`house_id`),
  ADD KEY `fk_maintenance_resident` (`resident_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payment_house` (`house_id`);

--
-- Indexes for table `residents`
--
ALTER TABLE `residents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_resident_house` (`house_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `fk_settings_user` (`updated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plate_number` (`plate_number`),
  ADD KEY `fk_vehicle_house` (`house_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `houses`
--
ALTER TABLE `houses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `residents`
--
ALTER TABLE `residents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD CONSTRAINT `fk_maintenance_house` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`),
  ADD CONSTRAINT `fk_maintenance_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_house` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `residents`
--
ALTER TABLE `residents`
  ADD CONSTRAINT `fk_resident_house` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `fk_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `fk_vehicle_house` FOREIGN KEY (`house_id`) REFERENCES `houses` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
