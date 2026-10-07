<?php
// Smart Water Tank Monitoring System - Core Configuration
// Handles environment loading, PDO connection, authentication, and helper functions

// Set timezone
date_default_timezone_set('Asia/Manila');

// 1. Simple, robust .env parser
function load_env($filePath) {
    if (!file_exists($filePath)) {
        return;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $val = trim($parts[1]);
            // Remove surrounding quotes if present
            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            }
            if (!getenv($name)) {
                putenv("{$name}={$val}");
                $_ENV[$name] = $val;
                $_SERVER[$name] = $val;
            }
        }
    }
}

load_env(__DIR__ . '/.env');

// Helper to read env with fallback
function env($key, $default = null) {
    $val = getenv($key);
    return $val !== false ? $val : $default;
}

// 2. PDO Database Connection Singleton
function get_db() {
    static $pdo = null;
    if ($pdo === null) {
        $host = env('DB_HOST', '127.0.0.1');
        $port = env('DB_PORT', '3306');
        $dbname = env('DB_NAME', 'water_tank_db');
        $user = env('DB_USER', 'root');
        $pass = env('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Database connection failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    return $pdo;
}

// 3. System Settings Loader
function get_system_settings($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM system_settings WHERE id = 1 LIMIT 1");
        $settings = $stmt->fetch();
        if (!$settings) {
            // Default fallback
            $settings = [
                'tank_height_cm' => floatval(env('DEFAULT_TANK_HEIGHT_CM', 100)),
                'tank_capacity_liters' => floatval(env('DEFAULT_TANK_CAPACITY_LITERS', 1000)),
                'high_threshold' => floatval(env('DEFAULT_HIGH_THRESHOLD', 70)),
                'medium_threshold' => floatval(env('DEFAULT_MEDIUM_THRESHOLD', 30)),
                'low_threshold' => floatval(env('DEFAULT_LOW_THRESHOLD', 30)),
                'critical_threshold' => floatval(env('DEFAULT_CRITICAL_THRESHOLD', 15)),
                'sensor_min_raw' => intval(env('DEFAULT_SENSOR_MIN_RAW', 500)),
                'sensor_max_raw' => intval(env('DEFAULT_SENSOR_MAX_RAW', 2800)),
                'device_name' => 'ESP32 Tank Node 1',
                'reading_interval_sec' => 5,
                'offline_timeout_sec' => intval(env('OFFLINE_TIMEOUT_SECONDS', 30)),
                'api_key' => env('API_KEY', 'smarttank_secret_key_2026')
            ];
        }
        return $settings;
    } catch (Exception $e) {
        return [
            'tank_height_cm' => 100.0,
            'tank_capacity_liters' => 1000.0,
            'high_threshold' => 70.0,
            'medium_threshold' => 30.0,
            'low_threshold' => 30.0,
            'critical_threshold' => 15.0,
            'sensor_min_raw' => 500,
            'sensor_max_raw' => 2800,
            'device_name' => 'ESP32 Tank Node 1',
            'reading_interval_sec' => 5,
            'offline_timeout_sec' => 30,
            'api_key' => 'smarttank_secret_key_2026'
        ];
    }
}

// 4. API Key Validator
function authenticate_request($settings) {
    // Check X-API-KEY header
    $providedKey = null;
    if (isset($_SERVER['HTTP_X_API_KEY'])) {
        $providedKey = trim($_SERVER['HTTP_X_API_KEY']);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = trim($_SERVER['HTTP_AUTHORIZATION']);
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            $providedKey = $matches[1];
        }
    } elseif (isset($_REQUEST['api_key'])) {
        $providedKey = trim($_REQUEST['api_key']);
    }

    $expectedKey = !empty($settings['api_key']) ? $settings['api_key'] : env('API_KEY', 'smarttank_secret_key_2026');

    // Also allow simulation requests if specifically flagged
    if (isset($_REQUEST['is_simulation']) && $_REQUEST['is_simulation'] == '1') {
        return true;
    }

    if ($providedKey && hash_equals($expectedKey, $providedKey)) {
        return true;
    }

    return false;
}

// 5. Standard JSON Responder
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-API-Key, Authorization');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

// Handle CORS preflight
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-API-Key, Authorization');
    http_response_code(204);
    exit;
}
