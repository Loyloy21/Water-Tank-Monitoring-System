<?php
// GET /api/readings/latest - Retrieve the latest reading, tank condition, and device status

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

$pdo = get_db();
$settings = get_system_settings($pdo);

if (!is_authenticated() && !authenticate_request($settings)) {
    json_response(['success' => false, 'error' => 'Unauthorized: Please log in or provide API key.'], 401);
}

// 1. Fetch latest reading
$stmt = $pdo->query("
    SELECT * FROM sensor_readings 
    ORDER BY id DESC 
    LIMIT 1
");
$latestReading = $stmt->fetch();

// 2. Fetch device status and evaluate online/offline state
$devStmt = $pdo->query("SELECT * FROM devices ORDER BY last_seen DESC LIMIT 1");
$device = $devStmt->fetch();

$offlineTimeout = intval($settings['offline_timeout_sec'] ?? 30);
$isOnline = false;
$secondsAgo = null;
$lastSeenHuman = "Never";

if ($device && !empty($device['last_seen'])) {
    $lastSeenTs = strtotime($device['last_seen']);
    $secondsAgo = time() - $lastSeenTs;
    if ($secondsAgo <= $offlineTimeout) {
        $isOnline = true;
    }
    
    if ($secondsAgo < 60) {
        $lastSeenHuman = "{$secondsAgo}s ago";
    } elseif ($secondsAgo < 3600) {
        $mins = floor($secondsAgo / 60);
        $lastSeenHuman = "{$mins}m ago";
    } else {
        $lastSeenHuman = date('M d, g:i A', $lastSeenTs);
    }
}

// 3. Count unacknowledged alerts
$alertCountStmt = $pdo->query("SELECT COUNT(*) AS total FROM alerts WHERE acknowledged = 0");
$unacknowledgedAlerts = (int)$alertCountStmt->fetchColumn();

// 4. Formatted values
$formattedDate = $latestReading ? date('F j, Y', strtotime($latestReading['recorded_at'])) : date('F j, Y');
$formattedTime = $latestReading ? date('h:i:s A', strtotime($latestReading['recorded_at'])) : date('h:i:s A');

// Detailed status descriptions for the Dashboard card
$status = $latestReading['tank_status'] ?? 'HIGH';
$percentage = $latestReading ? floatval($latestReading['water_percentage']) : 72.0;

$statusSummary = [
    'title' => 'Normal',
    'subtext' => 'All systems good',
    'badge_class' => 'status-high',
    'description' => 'Water Level status is optimal and tank operates normally.'
];

if ($status === 'HIGH') {
    $statusSummary = [
        'title' => 'Optimal / High',
        'subtext' => 'All systems good',
        'badge_class' => 'status-high',
        'description' => 'Water Level status is optimal but nearing maximum capacity.'
    ];
} elseif ($status === 'MEDIUM') {
    $statusSummary = [
        'title' => 'Moderate',
        'subtext' => 'Normal consumption rate',
        'badge_class' => 'status-medium',
        'description' => 'Water Level is in the medium safe operating range.'
    ];
} elseif ($status === 'LOW') {
    $statusSummary = [
        'title' => 'Low Warning',
        'subtext' => 'Refill recommended',
        'badge_class' => 'status-low',
        'description' => 'Water Level has fallen below safe threshold. Consider refilling soon.'
    ];
} elseif ($status === 'CRITICAL') {
    $statusSummary = [
        'title' => 'Critical Empty',
        'subtext' => 'Buzzer alarm active',
        'badge_class' => 'status-critical',
        'description' => 'Water Level is dangerously low. Pump shutoff and immediate refill required!'
    ];
}

json_response([
    'success' => true,
    'data' => [
        'reading' => $latestReading ? [
            'id' => (int)$latestReading['id'],
            'device_id' => $latestReading['device_id'],
            'sensor_value' => isset($latestReading['sensor_value']) ? intval($latestReading['sensor_value']) : 0,
            'water_level' => floatval($latestReading['water_percentage']),
            'water_percentage' => floatval($latestReading['water_percentage']),
            'water_level_cm' => floatval($latestReading['water_level_cm']),
            'water_volume_liters' => floatval($latestReading['water_volume_liters']),
            'tank_capacity_liters' => floatval($settings['tank_capacity_liters']),
            'tank_height_cm' => floatval($settings['tank_height_cm']),
            'tank_status' => $status,
            'is_simulated' => (bool)$latestReading['is_simulated'],
            'recorded_at' => $latestReading['recorded_at'],
            'formatted_date' => $formattedDate,
            'formatted_time' => $formattedTime,
        ] : null,
        'status_summary' => $statusSummary,
        'device' => [
            'device_id' => $device['device_id'] ?? 'SMART-TANK-001',
            'device_name' => $device['device_name'] ?? $settings['device_name'],
            'is_online' => $isOnline,
            'status' => $isOnline ? 'ONLINE' : 'OFFLINE',
            'last_seen' => $device['last_seen'] ?? null,
            'seconds_ago' => $secondsAgo,
            'last_seen_human' => $lastSeenHuman,
            'offline_timeout_sec' => $offlineTimeout
        ],
        'alerts' => [
            'unacknowledged_count' => $unacknowledgedAlerts,
            'status_label' => $unacknowledgedAlerts > 0 ? "{$unacknowledgedAlerts} Active Alert(s)" : "No Alerts",
            'status_subtext' => $unacknowledgedAlerts > 0 ? "Attention required" : "Zero system anomalies"
        ],
        'thresholds' => [
            'high' => floatval($settings['high_threshold']),
            'medium' => floatval($settings['medium_threshold']),
            'low' => floatval($settings['low_threshold']),
            'critical' => floatval($settings['critical_threshold']),
            'sensor_min_raw' => intval($settings['sensor_min_raw'] ?? 500),
            'sensor_max_raw' => intval($settings['sensor_max_raw'] ?? 2800)
        ],
        'server_time' => date('Y-m-d H:i:s')
    ]
]);
