<?php
// Smart Water Tank Monitoring System - Activity & System Logs API
// Provides filtered, searched, and paginated logs for the dashboard.
// Protected: Requires authenticated user session.

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';

// Authentication guard: Return 401 Unauthorized if not logged in
require_auth(true);

$pdo = get_db();

// 1. Parse parameters
$search     = trim($_GET['search'] ?? '');
$eventType  = trim($_GET['event_type'] ?? '');
$action     = trim($_GET['action'] ?? '');
$username   = trim($_GET['user'] ?? '');
$date       = trim($_GET['date'] ?? '');
$page       = max(1, intval($_GET['page'] ?? 1));
$limit      = min(100, max(5, intval($_GET['limit'] ?? 20)));
$offset     = ($page - 1) * $limit;

// 2. Build Query Conditions
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(action LIKE :s1 OR description LIKE :s2 OR username LIKE :s3 OR device_id LIKE :s4 OR ip_address LIKE :s5)";
    $like = "%{$search}%";
    $params[':s1'] = $like;
    $params[':s2'] = $like;
    $params[':s3'] = $like;
    $params[':s4'] = $like;
    $params[':s5'] = $like;
}

if ($eventType !== '' && $eventType !== 'ALL') {
    $where[] = "event_type = :etype";
    $params[':etype'] = strtoupper($eventType);
}

if ($action !== '' && $action !== 'ALL') {
    $where[] = "action = :act";
    $params[':act'] = strtoupper($action);
}

if ($username !== '' && $username !== 'ALL') {
    $where[] = "username = :uname";
    $params[':uname'] = $username;
}

if ($date !== '') {
    $where[] = "DATE(created_at) = :dt";
    $params[':dt'] = $date;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    // 3. Count total matching rows
    $countSql = "SELECT COUNT(*) FROM system_logs {$whereClause}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalLogs = intval($countStmt->fetchColumn());
    $totalPages = max(1, ceil($totalLogs / $limit));

    // 4. Fetch Paginated Logs (Newest First)
    $dataSql = "
        SELECT id, user_id, username, action, description, event_type, ip_address, user_agent, device_id, created_at 
        FROM system_logs 
        {$whereClause} 
        ORDER BY id DESC 
        LIMIT {$limit} OFFSET {$offset}
    ";
    $dataStmt = $pdo->prepare($dataSql);
    $dataStmt->execute($params);
    $logs = $dataStmt->fetchAll();

    // 5. Fetch unique actions and users for dynamic filter dropdowns
    $actionsStmt = $pdo->query("SELECT DISTINCT action FROM system_logs ORDER BY action ASC");
    $actionsList = $actionsStmt->fetchAll(PDO::FETCH_COLUMN);

    $usersStmt = $pdo->query("SELECT DISTINCT username FROM system_logs WHERE username IS NOT NULL AND username != '' ORDER BY username ASC");
    $usersList = $usersStmt->fetchAll(PDO::FETCH_COLUMN);

    json_response([
        'success'    => true,
        'logs'       => $logs,
        'pagination' => [
            'page'        => $page,
            'limit'       => $limit,
            'total'       => $totalLogs,
            'total_pages' => $totalPages
        ],
        'filters'    => [
            'actions' => $actionsList,
            'users'   => $usersList
        ]
    ]);
} catch (Exception $e) {
    json_response([
        'success' => false,
        'error'   => 'Failed to retrieve system logs: ' . $e->getMessage()
    ], 500);
}
