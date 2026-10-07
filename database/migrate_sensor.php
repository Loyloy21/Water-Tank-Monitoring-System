<?php
// Migration: Update schema for Water Level Sensor
require_once __DIR__ . '/../config.php';

$pdo = get_db();

// 1. Add sensor_value to sensor_readings
$cols = $pdo->query("SHOW COLUMNS FROM sensor_readings")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('sensor_value', $cols)) {
    $pdo->exec("ALTER TABLE sensor_readings ADD COLUMN sensor_value INT NOT NULL DEFAULT 0 AFTER device_id");
    echo "Added sensor_value column to sensor_readings.\n";
} else {
    echo "sensor_value already exists in sensor_readings.\n";
}

// 2. Modify distance_cm to be nullable with default 0.00
$pdo->exec("ALTER TABLE sensor_readings MODIFY COLUMN distance_cm DECIMAL(8,2) NULL DEFAULT 0.00");
echo "Made distance_cm nullable.\n";

// 3. Add sensor_min_raw and sensor_max_raw to system_settings
$settingCols = $pdo->query("SHOW COLUMNS FROM system_settings")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('sensor_min_raw', $settingCols)) {
    $pdo->exec("ALTER TABLE system_settings ADD COLUMN sensor_min_raw INT NOT NULL DEFAULT 500 AFTER critical_threshold");
    echo "Added sensor_min_raw to system_settings.\n";
}
if (!in_array('sensor_max_raw', $settingCols)) {
    $pdo->exec("ALTER TABLE system_settings ADD COLUMN sensor_max_raw INT NOT NULL DEFAULT 2800 AFTER sensor_min_raw");
    echo "Added sensor_max_raw to system_settings.\n";
}

// 4. Update existing readings with a default sensor_value mapped from water_percentage
$pdo->exec("UPDATE sensor_readings SET sensor_value = ROUND(500 + (water_percentage / 100) * 2300) WHERE sensor_value = 0");
echo "Updated existing readings with calibrated sensor_value.\n";

echo "Database migration completed successfully!\n";
