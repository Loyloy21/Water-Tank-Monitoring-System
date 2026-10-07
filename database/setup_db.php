<?php
// Automated Database Setup Script for Smart Water Tank Monitoring System

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'water_tank_db';

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. Connect to MySQL server without selecting database first
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 2. Read schema file
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found at: {$schemaFile}");
    }

    $sql = file_get_contents($schemaFile);

    // 3. Execute multi-query script
    $pdo->exec($sql);

    $response = [
        'status' => 'success',
        'message' => "Database '{$dbname}' and all required tables were successfully configured!",
        'timestamp' => date('Y-m-d H:i:s')
    ];

    if (php_sapi_name() === 'cli') {
        echo json_encode($response, JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        echo json_encode($response);
    }
} catch (PDOException $e) {
    http_response_code(500);
    $err = [
        'status' => 'error',
        'message' => 'Database Setup Failed: ' . $e->getMessage()
    ];
    if (php_sapi_name() === 'cli') {
        echo json_encode($err, JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        echo json_encode($err);
    }
} catch (Exception $e) {
    http_response_code(500);
    $err = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    if (php_sapi_name() === 'cli') {
        echo json_encode($err, JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        echo json_encode($err);
    }
}
