<?php
// /api/device/status - Returns ESP32 connection health, online/offline status and heartbeat

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

$pdo = get_db();
$settings = get_system_settings($pdo);

if (!is_authenticated() && !authenticate_request($settings)) {
    json_response(['success' => false, 'error' => 'Unauthorized: Please log in or provide API key.'], 401);
}

$devStmt = $pdo->query("SELECT * FROM devices ORDER BY last_seen DESC LIMIT 1");
$device = $devStmt->fetch();

$offlineTimeout = intval($settings['offline_timeout_sec'] ?? 30);
$isOnline = false;
$secondsAgo = 999999;
$lastSeenHuman = "Never";
$statusMsg = "OFFLINE - No communication received yet";

if ($device && !empty($device['last_seen'])) {
    $lastSeenTs = strtotime($device['last_seen']);
    $secondsAgo = time() - $lastSeenTs;
    $isOnline = ($secondsAgo <= $offlineTimeout);

    if ($secondsAgo < 60) {
        $lastSeenHuman = "{$secondsAgo} seconds ago";
    } elseif ($secondsAgo < 3600) {
        $mins = floor($secondsAgo / 60);
        $lastSeenHuman = "{$mins} minute(s) ago";
    } else {
        $lastSeenHuman = date('M d, Y h:i A', $lastSeenTs);
    }

    if ($isOnline) {
        $statusMsg = "ONLINE - Last seen: {$lastSeenHuman}";
        // Ensure device table reflects ONLINE
        if ($device['status'] !== 'ONLINE') {
            $pdo->prepare("UPDATE devices SET status = 'ONLINE' WHERE id = ?")->execute([$device['id']]);
        }
    } else {
        $statusMsg = "OFFLINE - Last seen: {$lastSeenHuman}";
        // Update device table to OFFLINE if not already
        if ($device['status'] !== 'OFFLINE') {
            $pdo->prepare("UPDATE devices SET status = 'OFFLINE' WHERE id = ?")->execute([$device['id']]);
            
            // Check if we should log a DEVICE OFFLINE alert (throttled to 30 mins)
            $checkOfflineAlert = $pdo->prepare("
                SELECT id FROM alerts 
                WHERE device_id = ? AND alert_type = 'DEVICE OFFLINE' 
                  AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                LIMIT 1
            ");
            $checkOfflineAlert->execute([$device['device_id']]);
            if (!$checkOfflineAlert->fetch()) {
                $pdo->prepare("
                    INSERT INTO alerts (device_id, alert_type, message, acknowledged, created_at)
                    VALUES (?, 'DEVICE OFFLINE', 'No data received from ESP32 for over {$offlineTimeout} seconds.', 0, NOW())
                ")->execute([$device['device_id']]);
            }
        }
    }
}

json_response([
    'success' => true,
    'device_id' => $device['device_id'] ?? 'SMART-TANK-001',
    'device_name' => $device['device_name'] ?? $settings['device_name'],
    'is_online' => $isOnline,
    'status' => $isOnline ? 'ONLINE' : 'OFFLINE',
    'last_seen' => $device['last_seen'] ?? null,
    'seconds_ago' => $secondsAgo,
    'last_seen_human' => $lastSeenHuman,
    'status_message' => $statusMsg,
    'offline_timeout_sec' => $offlineTimeout,
    'ip_address' => $device['ip_address'] ?? 'Not available'
]);
