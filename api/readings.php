<?php
// POST /api/readings - Ingest ESP32 or simulated sensor readings
// GET /api/readings - Returns latest reading or history based on query parameters

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

$pdo = get_db();
$settings = get_system_settings($pdo);

$method = $_SERVER['REQUEST_METHOD'];

// Handle GET requests (fallback routing)
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'latest';
    if ($action === 'history') {
        require __DIR__ . '/history.php';
        exit;
    } else {
        require __DIR__ . '/latest.php';
        exit;
    }
}

// Ensure POST method
if ($method !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed. Use POST.'], 405);
}

// Check Authentication
$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

// If form-encoded fallback
if (!$data && !empty($_POST)) {
    $data = $_POST;
}

if (!is_array($data)) {
    json_response(['success' => false, 'error' => 'Invalid JSON payload received.'], 400);
}

// Authentication verification
if (!authenticate_request($settings)) {
    // If payload contains api_key, verify it
    $payloadKey = $data['api_key'] ?? '';
    if (!hash_equals($settings['api_key'], $payloadKey) && empty($data['is_simulated'])) {
        json_response(['success' => false, 'error' => 'Unauthorized: Invalid or missing API key.'], 401);
    }
}

// Extraction and Validation
$deviceId = trim($data['device_id'] ?? 'SMART-TANK-001');
if (empty($deviceId)) {
    json_response(['success' => false, 'error' => 'Missing device_id field.'], 422);
}

$isSimulated = !empty($data['is_simulated']) ? 1 : 0;

// Tank Geometry & Threshold Settings
$tankHeight = floatval($settings['tank_height_cm']);
$tankCapacity = floatval($settings['tank_capacity_liters']);
$highThresh = floatval($settings['high_threshold']);
$medThresh = floatval($settings['medium_threshold']);
$lowThresh = floatval($settings['low_threshold']);
$critThresh = floatval($settings['critical_threshold']);
$sensorMinRaw = intval($settings['sensor_min_raw'] ?? 500);
$sensorMaxRaw = intval($settings['sensor_max_raw'] ?? 2800);

// Extract water level percentage or compute from raw sensor_value
$sensorValue = null;
if (isset($data['sensor_value'])) {
    $sensorValue = intval($data['sensor_value']);
}

$waterPercentage = null;
if (isset($data['water_level'])) {
    $waterPercentage = floatval($data['water_level']);
} elseif (isset($data['water_percentage'])) {
    $waterPercentage = floatval($data['water_percentage']);
} elseif (isset($data['percentage'])) {
    $waterPercentage = floatval($data['percentage']);
}

// If water percentage not directly supplied, calculate from raw analog sensor reading:
// waterPercentage = ((sensorValue - sensorMinRaw) / (sensorMaxRaw - sensorMinRaw)) * 100
if ($waterPercentage === null && $sensorValue !== null) {
    if ($sensorMaxRaw > $sensorMinRaw) {
        $waterPercentage = (($sensorValue - $sensorMinRaw) / ($sensorMaxRaw - $sensorMinRaw)) * 100.0;
    } else {
        $waterPercentage = 0.0;
    }
}

// Default fallback if neither provided
if ($waterPercentage === null) {
    $waterPercentage = 0.0;
}

// Clamp between 0% and 100% as required:
// LOWEST SENSOR VALUE = 0% water level, HIGHEST SENSOR VALUE = 100% water level
$waterPercentage = max(0.0, min(100.0, round($waterPercentage, 1)));

// If sensorValue was not explicitly passed, extrapolate calibrated raw ADC value
if ($sensorValue === null) {
    $sensorValue = intval(round($sensorMinRaw + ($waterPercentage / 100.0) * ($sensorMaxRaw - $sensorMinRaw)));
}

// Calculate water level height in cm: (percentage / 100) * tankHeight
$waterLevelCM = round(($waterPercentage / 100.0) * $tankHeight, 1);

// Calculate water volume in liters: (percentage / 100) * tankCapacity
$waterVolumeLiters = round(($waterPercentage / 100.0) * $tankCapacity, 1);

// Status evaluation according to specifications:
// HIGH: >= 70%
// MEDIUM: >= 30% and < 70%
// LOW: >= 16% and < 30%
// CRITICAL: <= 15%
if ($waterPercentage >= $highThresh) {
    $tankStatus = 'HIGH';
} elseif ($waterPercentage >= $medThresh) {
    $tankStatus = 'MEDIUM';
} elseif ($waterPercentage > $critThresh) {
    $tankStatus = 'LOW';
} else {
    $tankStatus = 'CRITICAL';
}

// 1. Insert into sensor_readings
$insertStmt = $pdo->prepare("
    INSERT INTO sensor_readings 
    (device_id, sensor_value, distance_cm, water_level_cm, water_percentage, water_volume_liters, tank_status, is_simulated, recorded_at)
    VALUES (?, ?, 0.00, ?, ?, ?, ?, ?, NOW())
");
$insertStmt->execute([
    $deviceId,
    $sensorValue,
    $waterLevelCM,
    $waterPercentage,
    $waterVolumeLiters,
    $tankStatus,
    $isSimulated
]);
$readingId = $pdo->lastInsertId();

// 2. Update Device Status & Heartbeat
$clientIp = $_SERVER['REMOTE_ADDR'] ?? null;
$deviceStmt = $pdo->prepare("
    INSERT INTO devices (device_id, device_name, status, ip_address, last_seen, created_at)
    VALUES (?, ?, 'ONLINE', ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE 
        status = 'ONLINE', 
        ip_address = VALUES(ip_address), 
        last_seen = NOW()
");
$deviceStmt->execute([$deviceId, $settings['device_name'], $clientIp]);

// 3. Automated Alert Generation (Throttled to avoid spamming database)
$generatedAlert = null;
if ($tankStatus === 'CRITICAL') {
    // Check if an unacknowledged critical alert was created in the last 10 minutes
    $checkAlert = $pdo->prepare("
        SELECT id FROM alerts 
        WHERE device_id = ? AND alert_type = 'CRITICAL WATER LEVEL' AND acknowledged = 0 
          AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        LIMIT 1
    ");
    $checkAlert->execute([$deviceId]);
    if (!$checkAlert->fetch()) {
        $alertMsg = "Water level critically low at {$waterPercentage}% ({$waterVolumeLiters} L remaining). Buzzer activated!";
        $createAlert = $pdo->prepare("
            INSERT INTO alerts (device_id, alert_type, message, water_percentage, acknowledged, created_at)
            VALUES (?, 'CRITICAL WATER LEVEL', ?, ?, 0, NOW())
        ");
        $createAlert->execute([$deviceId, $alertMsg, $waterPercentage]);
        $generatedAlert = ['type' => 'CRITICAL WATER LEVEL', 'message' => $alertMsg];
        log_activity('CRITICAL_ALERT', $alertMsg, 'ALERT', null, 'ESP32', $deviceId);
    }
} elseif ($tankStatus === 'LOW') {
    // Check if an unacknowledged low alert was created in the last 15 minutes
    $checkAlert = $pdo->prepare("
        SELECT id FROM alerts 
        WHERE device_id = ? AND alert_type = 'LOW WATER LEVEL' AND acknowledged = 0 
          AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        LIMIT 1
    ");
    $checkAlert->execute([$deviceId]);
    if (!$checkAlert->fetch()) {
        $alertMsg = "Water level dropped below {$medThresh}% (Current: {$waterPercentage}%, {$waterVolumeLiters} L).";
        $createAlert = $pdo->prepare("
            INSERT INTO alerts (device_id, alert_type, message, water_percentage, acknowledged, created_at)
            VALUES (?, 'LOW WATER LEVEL', ?, ?, 0, NOW())
        ");
        $createAlert->execute([$deviceId, $alertMsg, $waterPercentage]);
        $generatedAlert = ['type' => 'LOW WATER LEVEL', 'message' => $alertMsg];
        log_activity('ALERT_GENERATED', $alertMsg, 'ALERT', null, 'ESP32', $deviceId);
    }
}

// 4. Log Significant Sensor Events (Water Level Change & Status Change)
try {
    $prevStmt = $pdo->prepare("SELECT water_percentage, tank_status FROM sensor_readings WHERE device_id = ? AND id < ? ORDER BY id DESC LIMIT 1");
    $prevStmt->execute([$deviceId, $readingId]);
    $prevReading = $prevStmt->fetch();

    if (!$prevReading || abs($prevReading['water_percentage'] - $waterPercentage) >= 1.0) {
        $prevPct = $prevReading ? round($prevReading['water_percentage']) : 0;
        log_activity(
            'WATER_LEVEL_CHANGED', 
            "Water level changed from {$prevPct}% to {$waterPercentage}%", 
            'SENSOR', 
            null, 
            'ESP32', 
            $deviceId
        );
    }

    if ($prevReading && $prevReading['tank_status'] !== $tankStatus) {
        log_activity(
            'STATUS_CHANGED', 
            "Tank status changed from {$prevReading['tank_status']} to {$tankStatus}", 
            'SENSOR', 
            null, 
            'ESP32', 
            $deviceId
        );
    }
} catch (Exception $e) {
    // Silently ignore
}

// Response
json_response([
    'success' => true,
    'message' => 'Water level sensor reading successfully logged.',
    'reading_id' => $readingId,
    'data' => [
        'device_id' => $deviceId,
        'water_level' => $waterPercentage,
        'water_percentage' => $waterPercentage,
        'sensor_value' => $sensorValue,
        'water_level_cm' => $waterLevelCM,
        'water_volume_liters' => $waterVolumeLiters,
        'tank_capacity_liters' => $tankCapacity,
        'tank_status' => $tankStatus,
        'is_simulated' => (bool)$isSimulated,
        'recorded_at' => date('Y-m-d H:i:s'),
        'alert_triggered' => $generatedAlert
    ]
], 201);
