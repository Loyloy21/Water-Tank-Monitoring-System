<?php
// /api/stream - Server-Sent Events (SSE) real-time feed
// Streams latest reading and device state to connected web dashboards in real time

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

$pdo = get_db();
$settings = get_system_settings($pdo);

if (!is_authenticated() && !authenticate_request($settings)) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Prevent script timeout and close output buffering
if (function_exists('set_time_limit')) {
    set_time_limit(0);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);

// Send SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('X-Accel-Buffering: no'); // For Nginx/Apache proxies

// Flush existing buffers
while (ob_get_level() > 0) {
    ob_end_flush();
}
flush();

$pdo = get_db();
$settings = get_system_settings($pdo);

$lastSentId = 0;
$heartbeatCount = 0;
$startTime = time();

// Keep SSE loop running for up to 60 seconds per connection (browser will auto-reconnect)
while (time() - $startTime < 60) {
    if (connection_aborted()) {
        break;
    }

    try {
        // Fetch latest reading
        $stmt = $pdo->query("SELECT * FROM sensor_readings ORDER BY id DESC LIMIT 1");
        $latest = $stmt->fetch();

        // Fetch device status
        $devStmt = $pdo->query("SELECT * FROM devices ORDER BY last_seen DESC LIMIT 1");
        $device = $devStmt->fetch();

        $offlineTimeout = intval($settings['offline_timeout_sec'] ?? 30);
        $isOnline = false;
        $secondsAgo = 999999;
        $lastSeenHuman = "Never";

        if ($device && !empty($device['last_seen'])) {
            $lastSeenTs = strtotime($device['last_seen']);
            $secondsAgo = time() - $lastSeenTs;
            $isOnline = ($secondsAgo <= $offlineTimeout);
            $lastSeenHuman = $secondsAgo < 60 ? "{$secondsAgo}s ago" : floor($secondsAgo / 60) . "m ago";
        }

        // Fetch unread alerts count
        $alertCount = (int)$pdo->query("SELECT COUNT(*) FROM alerts WHERE acknowledged = 0")->fetchColumn();

        $payload = [
            'reading' => $latest ? [
                'id' => (int)$latest['id'],
                'sensor_value' => isset($latest['sensor_value']) ? intval($latest['sensor_value']) : 0,
                'water_level' => floatval($latest['water_percentage']),
                'water_percentage' => floatval($latest['water_percentage']),
                'water_level_cm' => floatval($latest['water_level_cm']),
                'water_volume_liters' => floatval($latest['water_volume_liters']),
                'tank_status' => $latest['tank_status'],
                'is_simulated' => (bool)$latest['is_simulated'],
                'recorded_at' => $latest['recorded_at'],
                'formatted_time' => date('h:i:s A', strtotime($latest['recorded_at'])),
                'formatted_date' => date('F j, Y', strtotime($latest['recorded_at']))
            ] : null,
            'device' => [
                'device_id' => $device['device_id'] ?? 'SMART-TANK-001',
                'is_online' => $isOnline,
                'status' => $isOnline ? 'ONLINE' : 'OFFLINE',
                'seconds_ago' => $secondsAgo,
                'last_seen_human' => $lastSeenHuman
            ],
            'unread_alerts' => $alertCount,
            'server_time' => date('Y-m-d H:i:s')
        ];

        // Send SSE event
        echo "id: " . ($latest['id'] ?? time()) . "\n";
        echo "event: tank_update\n";
        echo "data: " . json_encode($payload) . "\n\n";

        flush();

        // Sleep 2 seconds before checking next update
        sleep(2);

    } catch (Exception $e) {
        echo "event: error\n";
        echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
        flush();
        break;
    }
}
