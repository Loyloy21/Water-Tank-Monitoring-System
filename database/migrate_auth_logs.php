<?php
// Migration: Add users and system_logs tables for Login Sessions & System Logs
require_once __DIR__ . '/../config.php';

$pdo = get_db();

echo "Running Migration: Auth & Activity Logs...\n";

// 1. Create users table
$pdo->exec("
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL DEFAULT 'Administrator',
    `role` ENUM('ADMIN', 'OPERATOR', 'VIEWER') NOT NULL DEFAULT 'ADMIN',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "1. Table `users` created or already exists.\n";

// 2. Create system_logs table
$pdo->exec("
CREATE TABLE IF NOT EXISTS `system_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `username` VARCHAR(50) NULL,
    `action` VARCHAR(64) NOT NULL,
    `description` TEXT NOT NULL,
    `event_type` ENUM('AUTHENTICATION', 'SENSOR', 'ALERT', 'DEVICE', 'SETTINGS', 'SYSTEM', 'ERROR') NOT NULL DEFAULT 'SYSTEM',
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `device_id` VARCHAR(64) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_logs_created` (`created_at`),
    INDEX `idx_logs_type` (`event_type`),
    INDEX `idx_logs_action` (`action`),
    INDEX `idx_logs_user` (`username`),
    INDEX `idx_logs_device` (`device_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "2. Table `system_logs` created or already exists.\n";

// 3. Seed default admin user: admin / admin123
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
$stmt->execute();
if (!$stmt->fetch()) {
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $insert = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role, created_at) VALUES ('admin', :hash, 'System Administrator', 'ADMIN', NOW())");
    $insert->execute([':hash' => $hash]);
    echo "3. Seeded default admin user (admin / admin123).\n";
} else {
    echo "3. Default admin user already exists.\n";
}

// 4. Seed initial system initialization log
$logCheck = $pdo->query("SELECT COUNT(*) FROM system_logs")->fetchColumn();
if ($logCheck == 0) {
    $insLog = $pdo->prepare("INSERT INTO system_logs (user_id, username, action, description, event_type, ip_address, device_id, created_at) VALUES (1, 'system', 'SYSTEM_INITIALIZED', 'Water Tank Monitoring System initialized with Activity Logs and Session Auth', 'SYSTEM', '127.0.0.1', 'SMART-TANK-001', NOW())");
    $insLog->execute();
    echo "4. Seeded initial system event log.\n";
}

echo "Migration finished successfully!\n";
