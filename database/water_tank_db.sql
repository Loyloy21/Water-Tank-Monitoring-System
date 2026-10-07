-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 01, 2026 at 07:33 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `water_tank_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `id` bigint(20) NOT NULL,
  `device_id` varchar(64) NOT NULL,
  `alert_type` enum('CRITICAL WATER LEVEL','LOW WATER LEVEL','DEVICE OFFLINE','SENSOR ERROR','HIGH WATER LEVEL') NOT NULL,
  `message` text NOT NULL,
  `water_percentage` decimal(5,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `acknowledged` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alerts`
--

INSERT INTO `alerts` (`id`, `device_id`, `alert_type`, `message`, `water_percentage`, `created_at`, `acknowledged`) VALUES
(1, 'SMART-TANK-001', 'DEVICE OFFLINE', 'No data received from ESP32 for over 30 seconds.', NULL, '2026-10-01 22:33:23', 0),
(2, 'SMART-TANK-001', 'CRITICAL WATER LEVEL', '[SIMULATION] Water level critically low at 10% (100 L). Physical buzzer would activate!', 10.00, '2026-10-01 22:33:24', 1),
(3, 'SMART-TANK-001', 'LOW WATER LEVEL', '[SIMULATION] Water level dropped below 30% (Current: 20%, 200 L).', 20.00, '2026-10-01 22:35:21', 0),
(4, 'SMART-TANK-001', 'CRITICAL WATER LEVEL', '[SIMULATION] Water level critically low at 10% (100 L). Physical buzzer would activate!', 10.00, '2026-10-01 22:35:26', 0),
(5, 'SMART-TANK-001', 'CRITICAL WATER LEVEL', '[DEMO SIMULATION] Water level critically low at 10% (100 L). Physical buzzer would activate!', 10.00, '2026-10-01 23:23:21', 0),
(6, 'SMART-TANK-001', 'CRITICAL WATER LEVEL', '[DEMO SIMULATION] Water level critically low at 10% (100 L). Physical buzzer would activate!', 10.00, '2026-10-01 23:25:21', 0),
(7, 'SMART-TANK-001', 'LOW WATER LEVEL', '[DEMO SIMULATION] Water level dropped below 30% (Current: 20%, 200 L).', 20.00, '2026-10-01 23:25:26', 0),
(8, 'SMART-TANK-001', 'LOW WATER LEVEL', '[DEMO SIMULATION] Water level dropped below 30% (Current: 20%, 200 L).', 20.00, '2026-10-01 23:25:41', 0),
(9, 'SMART-TANK-001', 'CRITICAL WATER LEVEL', 'Water level critically low at 12% (12 L remaining). Buzzer activated!', 12.00, '2026-10-01 23:42:31', 0),
(10, 'SMART-TANK-001', 'LOW WATER LEVEL', '[DEMO SIMULATION] Water level dropped below 30% (Current: 20%, 20 L).', 20.00, '2026-10-01 23:58:04', 0),
(11, 'SMART-TANK-001', 'LOW WATER LEVEL', '[DEMO SIMULATION] Water level dropped below 30% (Current: 20%, 20 L).', 20.00, '2026-10-02 00:25:05', 0),
(12, 'SMART-TANK-001', 'CRITICAL WATER LEVEL', '[DEMO SIMULATION] Water level critically low at 10% (10 L). Physical buzzer would activate!', 10.00, '2026-10-02 00:25:05', 0);

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `device_id` varchar(64) NOT NULL,
  `device_name` varchar(100) NOT NULL DEFAULT 'Main Tank ESP32',
  `status` enum('ONLINE','OFFLINE') NOT NULL DEFAULT 'OFFLINE',
  `ip_address` varchar(45) DEFAULT NULL,
  `last_seen` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `devices`
--

INSERT INTO `devices` (`id`, `device_id`, `device_name`, `status`, `ip_address`, `last_seen`, `created_at`) VALUES
(1, 'SMART-TANK-001', 'ESP32 Tank Node 1', 'ONLINE', '127.0.0.1 (SIM)', '2026-10-02 00:41:13', '2026-10-01 22:25:48');

-- --------------------------------------------------------

--
-- Table structure for table `sensor_readings`
--

CREATE TABLE `sensor_readings` (
  `id` bigint(20) NOT NULL,
  `device_id` varchar(64) NOT NULL,
  `sensor_value` int(11) NOT NULL DEFAULT 0,
  `distance_cm` decimal(8,2) DEFAULT 0.00,
  `water_level_cm` decimal(8,2) NOT NULL,
  `water_percentage` decimal(5,2) NOT NULL,
  `water_volume_liters` decimal(10,2) NOT NULL,
  `tank_status` enum('HIGH','MEDIUM','LOW','CRITICAL') NOT NULL,
  `is_simulated` tinyint(1) NOT NULL DEFAULT 0,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sensor_readings`
--

INSERT INTO `sensor_readings` (`id`, `device_id`, `sensor_value`, `distance_cm`, `water_level_cm`, `water_percentage`, `water_volume_liters`, `tank_status`, `is_simulated`, `recorded_at`) VALUES
(1, 'SMART-TANK-001', 2225, 25.00, 75.00, 75.00, 750.00, 'HIGH', 0, '2026-10-01 21:40:48'),
(2, 'SMART-TANK-001', 2156, 28.00, 72.00, 72.00, 720.00, 'HIGH', 0, '2026-10-01 21:55:48'),
(3, 'SMART-TANK-001', 2064, 32.00, 68.00, 68.00, 680.00, 'MEDIUM', 0, '2026-10-01 22:05:48'),
(4, 'SMART-TANK-001', 2110, 30.00, 70.00, 70.00, 700.00, 'HIGH', 0, '2026-10-01 22:15:48'),
(5, 'SMART-TANK-001', 2156, 28.00, 72.00, 72.00, 720.00, 'HIGH', 0, '2026-10-01 22:25:48'),
(6, 'SMART-TANK-001', 2340, 20.00, 80.00, 80.00, 800.00, 'HIGH', 0, '2026-10-01 22:28:58'),
(7, 'SMART-TANK-001', 1765, 45.00, 55.00, 55.00, 550.00, 'MEDIUM', 1, '2026-10-01 22:33:24'),
(8, 'SMART-TANK-001', 730, 90.00, 10.00, 10.00, 100.00, 'CRITICAL', 1, '2026-10-01 22:33:24'),
(9, 'SMART-TANK-001', 2156, 28.00, 72.00, 72.00, 720.00, 'HIGH', 1, '2026-10-01 22:33:51'),
(10, 'SMART-TANK-001', 2570, 10.00, 90.00, 90.00, 900.00, 'HIGH', 1, '2026-10-01 22:35:12'),
(11, 'SMART-TANK-001', 1190, 70.00, 30.00, 30.00, 300.00, 'MEDIUM', 1, '2026-10-01 22:35:15'),
(12, 'SMART-TANK-001', 960, 80.00, 20.00, 20.00, 200.00, 'LOW', 1, '2026-10-01 22:35:21'),
(13, 'SMART-TANK-001', 730, 90.00, 10.00, 10.00, 100.00, 'CRITICAL', 1, '2026-10-01 22:35:26'),
(14, 'SMART-TANK-001', 2570, 10.00, 90.00, 90.00, 900.00, 'HIGH', 1, '2026-10-01 22:35:37'),
(15, 'SMART-TANK-001', 2041, 33.00, 67.00, 67.00, 670.00, 'MEDIUM', 1, '2026-10-01 22:35:42'),
(16, 'SMART-TANK-001', 2800, 0.00, 100.00, 100.00, 1000.00, 'HIGH', 1, '2026-10-01 22:35:45'),
(17, 'SMART-TANK-001', 1765, 45.00, 55.00, 55.00, 550.00, 'MEDIUM', 1, '2026-10-01 22:37:35'),
(18, 'SMART-TANK-001', 2225, 25.00, 75.00, 75.00, 750.00, 'HIGH', 0, '2026-10-01 22:02:26'),
(19, 'SMART-TANK-001', 2156, 28.00, 72.00, 72.00, 720.00, 'HIGH', 0, '2026-10-01 22:17:26'),
(20, 'SMART-TANK-001', 2064, 32.00, 68.00, 68.00, 680.00, 'MEDIUM', 0, '2026-10-01 22:27:26'),
(21, 'SMART-TANK-001', 2110, 30.00, 70.00, 70.00, 700.00, 'HIGH', 0, '2026-10-01 22:37:26'),
(22, 'SMART-TANK-001', 2156, 28.00, 72.00, 72.00, 720.00, 'HIGH', 0, '2026-10-01 22:47:26'),
(23, 'SMART-TANK-001', 2156, 0.00, 72.00, 72.00, 720.00, 'HIGH', 0, '2026-10-01 23:23:21'),
(24, 'SMART-TANK-001', 1420, 0.00, 40.00, 40.00, 400.00, 'MEDIUM', 1, '2026-10-01 23:23:21'),
(25, 'SMART-TANK-001', 730, 0.00, 10.00, 10.00, 100.00, 'CRITICAL', 1, '2026-10-01 23:23:21'),
(26, 'SMART-TANK-001', 2156, 0.00, 72.00, 72.00, 720.00, 'HIGH', 1, '2026-10-01 23:23:35'),
(27, 'SMART-TANK-001', 730, 0.00, 10.00, 10.00, 100.00, 'CRITICAL', 1, '2026-10-01 23:25:21'),
(28, 'SMART-TANK-001', 960, 0.00, 20.00, 20.00, 200.00, 'LOW', 1, '2026-10-01 23:25:26'),
(29, 'SMART-TANK-001', 1420, 0.00, 40.00, 40.00, 400.00, 'MEDIUM', 1, '2026-10-01 23:25:28'),
(30, 'SMART-TANK-001', 1880, 0.00, 60.00, 60.00, 600.00, 'MEDIUM', 1, '2026-10-01 23:25:30'),
(31, 'SMART-TANK-001', 2340, 0.00, 80.00, 80.00, 800.00, 'HIGH', 1, '2026-10-01 23:25:34'),
(32, 'SMART-TANK-001', 1880, 0.00, 60.00, 60.00, 600.00, 'MEDIUM', 1, '2026-10-01 23:25:38'),
(33, 'SMART-TANK-001', 1420, 0.00, 40.00, 40.00, 400.00, 'MEDIUM', 1, '2026-10-01 23:25:39'),
(34, 'SMART-TANK-001', 960, 0.00, 20.00, 20.00, 200.00, 'LOW', 1, '2026-10-01 23:25:41'),
(35, 'SMART-TANK-001', 1420, 0.00, 40.00, 40.00, 400.00, 'MEDIUM', 1, '2026-10-01 23:25:50'),
(36, 'SMART-TANK-001', 2340, 0.00, 80.00, 80.00, 800.00, 'HIGH', 1, '2026-10-01 23:26:02'),
(37, 'SMART-TANK-001', 776, 0.00, 12.00, 12.00, 12.00, 'CRITICAL', 0, '2026-10-01 23:42:31'),
(38, 'SMART-TANK-001', 776, 0.00, 12.00, 12.00, 12.00, 'CRITICAL', 0, '2026-10-01 23:43:00'),
(39, 'SMART-TANK-001', 960, 0.00, 20.00, 20.00, 20.00, 'LOW', 1, '2026-10-01 23:58:04'),
(40, 'SMART-TANK-001', 2340, 0.00, 80.00, 80.00, 80.00, 'HIGH', 1, '2026-10-02 00:25:05'),
(41, 'SMART-TANK-001', 960, 0.00, 20.00, 20.00, 20.00, 'LOW', 1, '2026-10-02 00:25:05'),
(42, 'SMART-TANK-001', 730, 0.00, 10.00, 10.00, 10.00, 'CRITICAL', 1, '2026-10-02 00:25:05'),
(43, 'SMART-TANK-001', 2340, 0.00, 80.00, 80.00, 80.00, 'HIGH', 1, '2026-10-02 00:25:13'),
(44, 'SMART-TANK-001', 1880, 0.00, 60.00, 60.00, 60.00, 'MEDIUM', 1, '2026-10-02 00:41:13');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  `description` text NOT NULL,
  `event_type` enum('AUTHENTICATION','SENSOR','ALERT','DEVICE','SETTINGS','SYSTEM','ERROR') NOT NULL DEFAULT 'SYSTEM',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `device_id` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `username`, `action`, `description`, `event_type`, `ip_address`, `user_agent`, `device_id`, `created_at`) VALUES
(1, 1, 'system', 'SYSTEM_INITIALIZED', 'Water Tank Monitoring System initialized with Activity Logs and Session Auth', 'SYSTEM', '127.0.0.1', NULL, 'SMART-TANK-001', '2026-10-01 23:33:13'),
(2, 1, 'admin', 'LOGIN', 'User \'admin\' successfully logged in', 'AUTHENTICATION', '127.0.0.1', 'System/ESP32', NULL, '2026-10-01 23:33:58'),
(3, NULL, 'admin', 'FAILED_LOGIN', 'Failed login attempt for username \'admin\'', 'AUTHENTICATION', '127.0.0.1', 'System/ESP32', NULL, '2026-10-01 23:34:22'),
(4, 1, 'admin', 'LOGIN', 'User \'admin\' successfully logged in', 'AUTHENTICATION', '127.0.0.1', 'System/ESP32', NULL, '2026-10-01 23:42:31'),
(5, 1, 'admin', 'FAILED_LOGIN', 'Failed login attempt for username \'admin\'', 'AUTHENTICATION', '127.0.0.1', 'System/ESP32', NULL, '2026-10-01 23:42:31'),
(6, NULL, 'ESP32', 'CRITICAL_ALERT', 'Water level critically low at 12% (12 L remaining). Buzzer activated!', 'ALERT', '::1', 'System/ESP32', 'SMART-TANK-001', '2026-10-01 23:42:31'),
(7, NULL, 'ESP32', 'WATER_LEVEL_CHANGED', 'Water level changed from 80% to 12%', 'SENSOR', '::1', 'System/ESP32', 'SMART-TANK-001', '2026-10-01 23:42:31'),
(8, NULL, 'ESP32', 'STATUS_CHANGED', 'Tank status changed from HIGH to CRITICAL', 'SENSOR', '::1', 'System/ESP32', 'SMART-TANK-001', '2026-10-01 23:42:31'),
(9, 1, 'admin', 'LOGIN', 'User \'admin\' successfully logged in', 'AUTHENTICATION', '127.0.0.1', 'System/ESP32', NULL, '2026-10-01 23:43:00'),
(10, 1, 'admin', 'FAILED_LOGIN', 'Failed login attempt for username \'admin\'', 'AUTHENTICATION', '127.0.0.1', 'System/ESP32', NULL, '2026-10-01 23:43:00'),
(11, NULL, 'admin', 'FAILED_LOGIN', 'Failed login attempt for username \'admin\'', 'AUTHENTICATION', '::1', 'System/ESP32', NULL, '2026-10-01 23:45:08'),
(12, 1, 'admin', 'LOGIN', 'User \'admin\' successfully logged in', 'AUTHENTICATION', '::1', 'System/ESP32', NULL, '2026-10-01 23:45:08'),
(13, 1, 'admin', 'DASHBOARD_ACCESS', 'User \'admin\' accessed the monitoring dashboard', 'AUTHENTICATION', '::1', 'System/ESP32', NULL, '2026-10-01 23:45:08'),
(14, 1, 'admin', 'LOGOUT', 'User \'admin\' logged out', 'AUTHENTICATION', '::1', 'System/ESP32', NULL, '2026-10-01 23:45:08'),
(15, NULL, 'admin', 'FAILED_LOGIN', 'Failed login attempt for username \'admin\'', 'AUTHENTICATION', '::1', 'System/ESP32', NULL, '2026-10-01 23:55:24'),
(16, 1, 'admin', 'LOGIN', 'User \'admin\' successfully logged in', 'AUTHENTICATION', '::1', 'System/ESP32', NULL, '2026-10-01 23:55:24'),
(17, 1, 'admin', 'LOGIN', 'User \'admin\' successfully logged in', 'AUTHENTICATION', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', NULL, '2026-10-01 23:57:03'),
(18, 1, 'admin', 'DASHBOARD_ACCESS', 'User \'admin\' accessed the monitoring dashboard', 'AUTHENTICATION', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', NULL, '2026-10-01 23:57:03'),
(19, 1, 'admin', 'LOGOUT', 'User \'admin\' logged out', 'AUTHENTICATION', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', NULL, '2026-10-01 23:58:35'),
(20, 1, 'admin', 'LOGIN', 'User \'admin\' successfully logged in', 'AUTHENTICATION', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', NULL, '2026-10-01 23:58:41'),
(21, 1, 'admin', 'DASHBOARD_ACCESS', 'User \'admin\' accessed the monitoring dashboard', 'AUTHENTICATION', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', NULL, '2026-10-01 23:58:41'),
(22, 1, 'admin', 'LOGIN', 'User \'admin\' successfully logged in', 'AUTHENTICATION', '127.0.0.1', 'curl/8.0.1', NULL, '2026-10-02 00:14:10'),
(23, 1, 'admin', 'DASHBOARD_ACCESS', 'User \'admin\' accessed the monitoring dashboard', 'AUTHENTICATION', '127.0.0.1', 'curl/8.0.1', NULL, '2026-10-02 00:22:27'),
(24, 1, 'admin', 'LOGOUT', 'User \'admin\' logged out', 'AUTHENTICATION', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', NULL, '2026-10-02 00:46:15'),
(25, 1, 'admin', 'LOGIN', 'User \'admin\' successfully logged in', 'AUTHENTICATION', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', NULL, '2026-10-02 00:47:38'),
(26, 1, 'admin', 'DASHBOARD_ACCESS', 'User \'admin\' accessed the monitoring dashboard', 'AUTHENTICATION', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', NULL, '2026-10-02 00:47:38');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `tank_height_cm` decimal(8,2) NOT NULL DEFAULT 100.00,
  `tank_capacity_liters` decimal(10,2) NOT NULL DEFAULT 1000.00,
  `high_threshold` decimal(5,2) NOT NULL DEFAULT 70.00,
  `medium_threshold` decimal(5,2) NOT NULL DEFAULT 30.00,
  `low_threshold` decimal(5,2) NOT NULL DEFAULT 30.00,
  `critical_threshold` decimal(5,2) NOT NULL DEFAULT 15.00,
  `sensor_min_raw` int(11) NOT NULL DEFAULT 500,
  `sensor_max_raw` int(11) NOT NULL DEFAULT 2800,
  `device_name` varchar(100) NOT NULL DEFAULT 'ESP32 Tank Node 1',
  `reading_interval_sec` int(11) NOT NULL DEFAULT 5,
  `offline_timeout_sec` int(11) NOT NULL DEFAULT 30,
  `api_key` varchar(128) NOT NULL DEFAULT 'smarttank_secret_key_2026',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `tank_height_cm`, `tank_capacity_liters`, `high_threshold`, `medium_threshold`, `low_threshold`, `critical_threshold`, `sensor_min_raw`, `sensor_max_raw`, `device_name`, `reading_interval_sec`, `offline_timeout_sec`, `api_key`, `updated_at`) VALUES
(1, 100.00, 100.00, 70.00, 30.00, 30.00, 15.00, 500, 2800, 'ESP32 Tank Node 1', 5, 30, 'smarttank_secret_key_2026', '2026-10-01 23:27:12');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL DEFAULT 'Administrator',
  `role` enum('ADMIN','OPERATOR','VIEWER') NOT NULL DEFAULT 'ADMIN',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `created_at`, `last_login`) VALUES
(1, 'admin', '$2y$10$Haeu8yYWiBgRGSoy.O9/ReX/evs/zEU7jgKm4ZLLQSTVzaMRPcW6a', 'System Administrator', 'ADMIN', '2026-10-01 23:33:13', '2026-10-02 00:47:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_alerts_created` (`created_at`),
  ADD KEY `idx_acknowledged` (`acknowledged`);

--
-- Indexes for table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_id` (`device_id`);

--
-- Indexes for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_device_recorded` (`device_id`,`recorded_at`),
  ADD KEY `idx_recorded_at` (`recorded_at`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_created` (`created_at`),
  ADD KEY `idx_logs_type` (`event_type`),
  ADD KEY `idx_logs_action` (`action`),
  ADD KEY `idx_logs_user` (`username`),
  ADD KEY `idx_logs_device` (`device_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
