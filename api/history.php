<?php
// GET /api/readings/history - Retrieve historical readings with filtering and CSV export

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

// Authentication guard
require_auth(true);

$pdo = get_db();

$filter = $_GET['filter'] ?? 'today';
$limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 1000) : 100;
$export = $_GET['export'] ?? '';

// Build query based on filter
$whereClauses = [];
$params = [];

switch ($filter) {
    case 'today':
        $whereClauses[] = "recorded_at >= CURDATE()";
        break;
    case 'week':
        $whereClauses[] = "recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $whereClauses[] = "recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'custom':
        $startDate = $_GET['start'] ?? '';
        $endDate = $_GET['end'] ?? '';
        if (!empty($startDate) && !empty($endDate)) {
            $whereClauses[] = "recorded_at BETWEEN ? AND ?";
            $params[] = $startDate . " 00:00:00";
            $params[] = $endDate . " 23:59:59";
        }
        break;
    case 'all':
    default:
        // No additional date constraint
        break;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Handle CSV Export
if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=water_tank_history_' . date('Ymd_His') . '.csv');
    
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Device ID', 'Date', 'Time', 'Water Level (%)', 'Water Volume (L)', 'Sensor Reading (ADC)', 'Status', 'Simulated']);

    $csvStmt = $pdo->prepare("SELECT * FROM sensor_readings {$whereSql} ORDER BY recorded_at DESC LIMIT 5000");
    $csvStmt->execute($params);

    while ($row = $csvStmt->fetch()) {
        $ts = strtotime($row['recorded_at']);
        fputcsv($out, [
            $row['id'],
            $row['device_id'],
            date('M d, Y', $ts),
            date('h:i:s A', $ts),
            $row['water_percentage'] . '%',
            $row['water_volume_liters'],
            $row['sensor_value'] ?? 0,
            $row['tank_status'],
            $row['is_simulated'] ? 'Yes' : 'No'
        ]);
    }
    fclose($out);
    exit;
}

// Fetch historical records for table (newest first)
$sql = "SELECT * FROM sensor_readings {$whereSql} ORDER BY recorded_at DESC LIMIT {$limit}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$formattedRows = array_map(function($r) {
    $ts = strtotime($r['recorded_at']);
    return [
        'id' => (int)$r['id'],
        'device_id' => $r['device_id'],
        'date' => date('M d, Y', $ts),
        'time' => date('h:i:s A', $ts),
        'timestamp' => $r['recorded_at'],
        'water_level' => floatval($r['water_percentage']),
        'water_percentage' => floatval($r['water_percentage']),
        'water_volume_liters' => floatval($r['water_volume_liters']),
        'sensor_value' => isset($r['sensor_value']) ? intval($r['sensor_value']) : 0,
        'water_level_cm' => floatval($r['water_level_cm']),
        'tank_status' => $r['tank_status'],
        'is_simulated' => (bool)$r['is_simulated']
    ];
}, $rows);

// Fetch recent records for live trend graph (oldest to newest, up to 25 data points)
$graphSql = "SELECT recorded_at, water_percentage, water_volume_liters, sensor_value, tank_status 
             FROM sensor_readings 
             ORDER BY recorded_at DESC LIMIT 25";
$graphStmt = $pdo->query($graphSql);
$graphRows = array_reverse($graphStmt->fetchAll());

$chartData = [
    'labels' => [],
    'percentages' => [],
    'volumes' => [],
    'sensor_values' => []
];

foreach ($graphRows as $gr) {
    $chartData['labels'][] = date('H:i', strtotime($gr['recorded_at']));
    $chartData['percentages'][] = floatval($gr['water_percentage']);
    $chartData['volumes'][] = floatval($gr['water_volume_liters']);
    $chartData['sensor_values'][] = isset($gr['sensor_value']) ? intval($gr['sensor_value']) : 0;
}

json_response([
    'success' => true,
    'filter' => $filter,
    'count' => count($formattedRows),
    'readings' => $formattedRows,
    'chart_data' => $chartData
]);
