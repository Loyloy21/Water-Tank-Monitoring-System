<?php
// /api/simulate - Developer Demo / Simulation Endpoint
// Allows simulating water level sensor readings (20%, 40%, 60%, 80%, 10%) without physical hardware

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

// Authentication guard
require_auth(true);

$pdo = get_db();
$settings = get_system_settings($pdo);

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true) ?: $_REQUEST;

$tankHeight = floatval($settings['tank_height_cm']);
$tankCapacity = floatval($settings['tank_capacity_liters']);
$highThresh = floatval($settings['high_threshold']);
$medThresh = floatval($settings['medium_threshold']);
$lowThresh = floatval($settings['low_threshold']);
$critThresh = floatval($settings['critical_threshold']);
$sensorMinRaw = intval($settings['sensor_min_raw'] ?? 500);
$sensorMaxRaw = intval($settings['sensor_max_raw'] ?? 2800);

// User can pass target water percentage or raw sensor_value
if (isset($data['percentage'])) {
    $pct = max(0, min(100, floatval($data['percentage'])));
    $sensorValue = intval(round($sensorMinRaw + ($pct / 100.0) * ($sensorMaxRaw - $sensorMinRaw)));
} elseif (isset($data['water_level'])) {
    $pct = max(0, min(100, floatval($data['water_level'])));
    $sensorValue = intval(round($sensorMinRaw + ($pct / 100.0) * ($sensorMaxRaw - $sensorMinRaw)));
} elseif (isset($data['sensor_value'])) {
    $sensorValue = intval($data['sensor_value']);
    $pct = $sensorMaxRaw > $sensorMinRaw ? (($sensorValue - $sensorMinRaw) / ($sensorMaxRaw - $sensorMinRaw)) * 100.0 : 0.0;
    $pct = max(0, min(100, round($pct, 1)));
} else {
    // Default demo preset: 72%
    $pct = 72.0;
    $sensorValue = intval(round($sensorMinRaw + ($pct / 100.0) * ($sensorMaxRaw - $sensorMinRaw)));
}

$waterLevelCM = round(($pct / 100.0) * $tankHeight, 1);
$volumeLiters = round(($pct / 100.0) * $tankCapacity, 1);

// Status evaluation according to specifications:
// HIGH: >= 70%
// MEDIUM: >= 30% and < 70%
// LOW: >= 16% and < 30%
// CRITICAL: <= 15%
if ($pct >= $highThresh) {
    $status = 'HIGH';
} elseif ($pct >= $medThresh) {
    $status = 'MEDIUM';
} elseif ($pct > $critThresh) {
    $status = 'LOW';
} else {
    $status = 'CRITICAL';
}

$deviceId = 'SMART-TANK-001';

// Insert simulated sensor reading
$insertStmt = $pdo->prepare("
    INSERT INTO sensor_readings 
    (device_id, sensor_value, distance_cm, water_level_cm, water_percentage, water_volume_liters, tank_status, is_simulated, recorded_at)
    VALUES (?, ?, 0.00, ?, ?, ?, ?, 1, NOW())
");
$insertStmt->execute([
    $deviceId,
    $sensorValue,
    $waterLevelCM,
    $pct,
    $volumeLiters,
    $status
]);
$readingId = $pdo->lastInsertId();

// Update device heartbeat with clear simulation label
$pdo->prepare("
    INSERT INTO devices (device_id, device_name, status, ip_address, last_seen, created_at)
    VALUES (?, ?, 'ONLINE', '127.0.0.1 (SIM)', NOW(), NOW())
    ON DUPLICATE KEY UPDATE 
        status = 'ONLINE', 
        ip_address = '127.0.0.1 (SIM)', 
        last_seen = NOW()
")->execute([$deviceId, $settings['device_name']]);

// Check if an alert should be triggered
$triggeredAlert = null;
if ($status === 'CRITICAL') {
    $alertMsg = "[DEMO SIMULATION] Water level critically low at {$pct}% ({$volumeLiters} L). Physical buzzer would activate!";
    $pdo->prepare("
        INSERT INTO alerts (device_id, alert_type, message, water_percentage, acknowledged, created_at)
        VALUES (?, 'CRITICAL WATER LEVEL', ?, ?, 0, NOW())
    ")->execute([$deviceId, $alertMsg, $pct]);
    $triggeredAlert = ['type' => 'CRITICAL WATER LEVEL', 'message' => $alertMsg];
} elseif ($status === 'LOW') {
    $alertMsg = "[DEMO SIMULATION] Water level dropped below {$medThresh}% (Current: {$pct}%, {$volumeLiters} L).";
    $pdo->prepare("
        INSERT INTO alerts (device_id, alert_type, message, water_percentage, acknowledged, created_at)
        VALUES (?, 'LOW WATER LEVEL', ?, ?, 0, NOW())
    ")->execute([$deviceId, $alertMsg, $pct]);
    $triggeredAlert = ['type' => 'LOW WATER LEVEL', 'message' => $alertMsg];
}

json_response([
    'success' => true,
    'message' => "Demo/Simulation Mode: Water level set to {$pct}% (Sensor ADC: {$sensorValue}).",
    'is_simulated' => true,
    'mode_label' => 'Demo/Simulation Mode',
    'reading_id' => $readingId,
    'reading' => [
        'device_id' => $deviceId,
        'water_level' => $pct,
        'water_percentage' => $pct,
        'sensor_value' => $sensorValue,
        'water_level_cm' => $waterLevelCM,
        'water_volume_liters' => $volumeLiters,
        'tank_status' => $status,
        'recorded_at' => date('Y-m-d H:i:s'),
        'alert_triggered' => $triggeredAlert
    ]
]);
