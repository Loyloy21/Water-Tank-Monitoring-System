<?php
// /api/alerts - Manage and retrieve system alerts

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

// Authentication guard
require_auth(true);

$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

// Handle POST actions (e.g. acknowledge alert)
if ($method === 'POST') {
    $rawBody = file_get_contents('php://input');
    $data = json_decode($rawBody, true) ?: $_POST;
    
    $action = $data['action'] ?? 'acknowledge';
    
    if ($action === 'acknowledge_all') {
        $stmt = $pdo->prepare("UPDATE alerts SET acknowledged = 1 WHERE acknowledged = 0");
        $stmt->execute();
        log_activity('ALERT_ACKNOWLEDGED', 'All active alerts acknowledged by user', 'ALERT');
        json_response([
            'success' => true,
            'message' => 'All active alerts marked as acknowledged.'
        ]);
    } elseif ($action === 'acknowledge') {
        $alertId = intval($data['alert_id'] ?? 0);
        if ($alertId <= 0) {
            json_response(['success' => false, 'error' => 'Invalid alert_id specified.'], 422);
        }
        $stmt = $pdo->prepare("UPDATE alerts SET acknowledged = 1 WHERE id = ?");
        $stmt->execute([$alertId]);
        log_activity('ALERT_ACKNOWLEDGED', "Alert #{$alertId} acknowledged by user", 'ALERT');
        json_response([
            'success' => true,
            'message' => "Alert #{$alertId} acknowledged."
        ]);
    } elseif ($action === 'create') {
        // Trigger manual alert (e.g. test or device offline detection)
        $alertType = $data['alert_type'] ?? 'LOW WATER LEVEL';
        $message = trim($data['message'] ?? 'Alert triggered');
        $deviceId = $data['device_id'] ?? 'SMART-TANK-001';
        $pct = isset($data['water_percentage']) ? floatval($data['water_percentage']) : null;

        $stmt = $pdo->prepare("
            INSERT INTO alerts (device_id, alert_type, message, water_percentage, acknowledged, created_at)
            VALUES (?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$deviceId, $alertType, $message, $pct]);

        json_response([
            'success' => true,
            'message' => 'Alert successfully logged.',
            'alert_id' => $pdo->lastInsertId()
        ], 201);
    }
    
    json_response(['success' => false, 'error' => 'Unknown POST action.'], 400);
}

// Handle GET requests (Listing alerts)
$filter = $_GET['filter'] ?? 'all';
$limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 200) : 50;

$whereClause = "";
if ($filter === 'unacknowledged') {
    $whereClause = "WHERE acknowledged = 0";
} elseif ($filter === 'acknowledged') {
    $whereClause = "WHERE acknowledged = 1";
}

$stmt = $pdo->prepare("SELECT * FROM alerts {$whereClause} ORDER BY created_at DESC LIMIT {$limit}");
$stmt->execute();
$alerts = $stmt->fetchAll();

$formattedAlerts = array_map(function($a) {
    $ts = strtotime($a['created_at']);
    return [
        'id' => (int)$a['id'],
        'device_id' => $a['device_id'],
        'alert_type' => $a['alert_type'],
        'message' => $a['message'],
        'water_percentage' => $a['water_percentage'] !== null ? floatval($a['water_percentage']) : null,
        'acknowledged' => (bool)$a['acknowledged'],
        'created_at' => $a['created_at'],
        'date' => date('M d, Y', $ts),
        'time' => date('h:i:s A', $ts),
        'time_ago' => time_ago_string($ts)
    ];
}, $alerts);

// Count active unacknowledged alerts
$countStmt = $pdo->query("SELECT COUNT(*) FROM alerts WHERE acknowledged = 0");
$unacknowledgedCount = (int)$countStmt->fetchColumn();

function time_ago_string($timestamp) {
    $diff = time() - $timestamp;
    if ($diff < 60) return "Just now";
    if ($diff < 3600) return floor($diff / 60) . " min ago";
    if ($diff < 86400) return floor($diff / 3600) . " hr ago";
    return floor($diff / 86400) . " day(s) ago";
}

json_response([
    'success' => true,
    'total' => count($formattedAlerts),
    'unacknowledged_count' => $unacknowledgedCount,
    'alerts' => $formattedAlerts
]);
