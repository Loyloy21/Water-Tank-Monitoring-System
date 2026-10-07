<?php
// /api/settings - Read and update system configuration and tank geometry

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

// Authentication guard
require_auth(true);

$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' || $method === 'PUT') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;

    // Validate inputs
    $tankHeight = isset($data['tank_height_cm']) ? floatval($data['tank_height_cm']) : null;
    $tankCapacity = isset($data['tank_capacity_liters']) ? floatval($data['tank_capacity_liters']) : null;
    $highThresh = isset($data['high_threshold']) ? floatval($data['high_threshold']) : null;
    $medThresh = isset($data['medium_threshold']) ? floatval($data['medium_threshold']) : null;
    $lowThresh = isset($data['low_threshold']) ? floatval($data['low_threshold']) : null;
    $critThresh = isset($data['critical_threshold']) ? floatval($data['critical_threshold']) : null;
    $sensorMinRaw = isset($data['sensor_min_raw']) ? intval($data['sensor_min_raw']) : null;
    $sensorMaxRaw = isset($data['sensor_max_raw']) ? intval($data['sensor_max_raw']) : null;
    $deviceName = isset($data['device_name']) ? trim($data['device_name']) : null;
    $readingInterval = isset($data['reading_interval_sec']) ? intval($data['reading_interval_sec']) : null;
    $offlineTimeout = isset($data['offline_timeout_sec']) ? intval($data['offline_timeout_sec']) : null;
    $apiKey = isset($data['api_key']) ? trim($data['api_key']) : null;

    $errors = [];
    if ($tankHeight !== null && $tankHeight <= 0) $errors[] = "Tank height must be greater than 0 cm.";
    if ($tankCapacity !== null && $tankCapacity <= 0) $errors[] = "Tank capacity must be greater than 0 Liters.";
    if ($highThresh !== null && ($highThresh <= 0 || $highThresh > 100)) $errors[] = "High threshold must be between 1% and 100%.";
    if ($medThresh !== null && ($medThresh <= 0 || $medThresh >= $highThresh)) $errors[] = "Medium threshold must be lower than High threshold.";
    if ($critThresh !== null && ($critThresh <= 0 || $critThresh >= $medThresh)) $errors[] = "Critical threshold must be lower than Medium threshold.";
    if ($sensorMinRaw !== null && $sensorMinRaw < 0) $errors[] = "Minimum sensor raw value must be 0 or greater.";
    if ($sensorMaxRaw !== null && $sensorMinRaw !== null && $sensorMaxRaw <= $sensorMinRaw) $errors[] = "Maximum sensor raw value must be greater than minimum value.";
    if ($readingInterval !== null && $readingInterval < 1) $errors[] = "Reading interval must be at least 1 second.";
    if ($offlineTimeout !== null && $offlineTimeout < 5) $errors[] = "Offline timeout must be at least 5 seconds.";

    if (!empty($errors)) {
        json_response(['success' => false, 'error' => implode(" ", $errors)], 422);
    }

    // Build update query dynamically
    $fields = [];
    $params = [];

    if ($tankHeight !== null) { $fields[] = "tank_height_cm = ?"; $params[] = $tankHeight; }
    if ($tankCapacity !== null) { $fields[] = "tank_capacity_liters = ?"; $params[] = $tankCapacity; }
    if ($highThresh !== null) { $fields[] = "high_threshold = ?"; $params[] = $highThresh; }
    if ($medThresh !== null) { $fields[] = "medium_threshold = ?"; $params[] = $medThresh; }
    if ($lowThresh !== null) { $fields[] = "low_threshold = ?"; $params[] = $lowThresh; }
    if ($critThresh !== null) { $fields[] = "critical_threshold = ?"; $params[] = $critThresh; }
    if ($sensorMinRaw !== null) { $fields[] = "sensor_min_raw = ?"; $params[] = $sensorMinRaw; }
    if ($sensorMaxRaw !== null) { $fields[] = "sensor_max_raw = ?"; $params[] = $sensorMaxRaw; }
    if ($deviceName !== null) { $fields[] = "device_name = ?"; $params[] = $deviceName; }
    if ($readingInterval !== null) { $fields[] = "reading_interval_sec = ?"; $params[] = $readingInterval; }
    if ($offlineTimeout !== null) { $fields[] = "offline_timeout_sec = ?"; $params[] = $offlineTimeout; }
    if ($apiKey !== null && $apiKey !== '') { $fields[] = "api_key = ?"; $params[] = $apiKey; }

    if (!empty($fields)) {
        $sql = "UPDATE system_settings SET " . implode(", ", $fields) . ", updated_at = NOW() WHERE id = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Also update device name in devices table if changed
        if ($deviceName !== null) {
            $pdo->prepare("UPDATE devices SET device_name = ? WHERE id = 1")->execute([$deviceName]);
        }

        // Log specific setting updates
        if ($tankCapacity !== null) {
            log_activity('TANK_CAPACITY_UPDATED', "Tank capacity updated to {$tankCapacity} Liters", 'SETTINGS');
        }
        if ($sensorMinRaw !== null || $sensorMaxRaw !== null) {
            log_activity('SENSOR_CALIBRATION_UPDATED', "Sensor calibration updated (Min: {$sensorMinRaw}, Max: {$sensorMaxRaw})", 'SETTINGS');
        }
        log_activity('SYSTEM_SETTINGS_UPDATED', "System operational thresholds and configuration updated", 'SETTINGS');
    }

    $updated = get_system_settings($pdo);
    json_response([
        'success' => true,
        'message' => 'System settings updated successfully.',
        'settings' => $updated
    ]);
}

// GET Request
$settings = get_system_settings($pdo);
json_response([
    'success' => true,
    'settings' => [
        'tank_height_cm' => floatval($settings['tank_height_cm']),
        'tank_capacity_liters' => floatval($settings['tank_capacity_liters']),
        'high_threshold' => floatval($settings['high_threshold']),
        'medium_threshold' => floatval($settings['medium_threshold']),
        'low_threshold' => floatval($settings['low_threshold']),
        'critical_threshold' => floatval($settings['critical_threshold']),
        'sensor_min_raw' => intval($settings['sensor_min_raw'] ?? 500),
        'sensor_max_raw' => intval($settings['sensor_max_raw'] ?? 2800),
        'device_name' => $settings['device_name'],
        'reading_interval_sec' => intval($settings['reading_interval_sec']),
        'offline_timeout_sec' => intval($settings['offline_timeout_sec']),
        'api_key' => $settings['api_key'],
        'updated_at' => $settings['updated_at'] ?? null
    ]
]);
