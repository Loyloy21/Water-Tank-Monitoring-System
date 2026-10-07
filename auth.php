<?php
// Smart Water Tank Monitoring System - Authentication & System Logging Engine
// Provides secure session management, RBAC, route guards, anti-caching, and audit logging.

require_once __DIR__ . '/config.php';

// Inactivity timeout in seconds (30 minutes default)
define('AUTH_INACTIVITY_TIMEOUT', intval(env('SESSION_TIMEOUT_SECONDS', 1800)));

/**
 * Configure and start a secure session with HttpOnly and SameSite flags
 */
function start_secure_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    if (!headers_sent()) {
        // Secure cookie parameters
        session_set_cookie_params([
            'lifetime' => 0, // Until browser closes
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
    }

    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    // Check inactivity expiration for authenticated sessions
    if (!empty($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > AUTH_INACTIVITY_TIMEOUT)) {
            $userId   = $_SESSION['user_id'] ?? null;
            $username = $_SESSION['username'] ?? 'User';
            
            // Record session expiration log
            log_activity(
                'SESSION_EXPIRED',
                "Session for user '{$username}' expired due to inactivity",
                'AUTHENTICATION',
                $userId,
                $username
            );

            // Destroy the session completely
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            session_destroy();

            // Check if this is an API or Web page request
            $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
            if ($isApi) {
                json_response([
                    'success' => false,
                    'error'   => 'Your session has expired. Please log in again.',
                    'expired' => true
                ], 401);
            } else {
                header('Location: login.php?expired=1');
                exit;
            }
        }

        // Update active heartbeat
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Check if the user has an active authenticated session
 */
function is_authenticated() {
    start_secure_session();
    return !empty($_SESSION['authenticated']) && $_SESSION['authenticated'] === true && !empty($_SESSION['user_id']);
}

/**
 * Retrieve current logged-in user profile
 */
function get_current_user_profile() {
    if (!is_authenticated()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'] ?? 'User',
        'role'      => $_SESSION['role'] ?? 'VIEWER'
    ];
}

/**
 * Enforce authentication guard for private web pages and APIs
 */
function require_auth($isApi = false) {
    start_secure_session();

    if (!is_authenticated()) {
        if ($isApi) {
            json_response([
                'success' => false,
                'error'   => 'Unauthorized: Please log in to access this resource.'
            ], 401);
        } else {
            prevent_client_caching();
            header('Location: login.php');
            exit;
        }
    }
}

/**
 * Send anti-caching HTTP headers so browser Back button cannot display protected pages after logout
 */
function prevent_client_caching() {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
}

/**
 * Authenticate credentials against `users` table
 */
function authenticate_user($username, $password) {
    start_secure_session();
    $username = trim($username);

    if (empty($username) || empty($password)) {
        return ['success' => false, 'error' => 'Please provide both username and password.'];
    }

    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT id, username, password_hash, full_name, role FROM users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Prevent session fixation
            if (session_status() === PHP_SESSION_ACTIVE) {
                @session_regenerate_id(true);
            }

            $_SESSION['authenticated'] = true;
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['full_name']     = $user['full_name'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['last_activity'] = time();

            // Update user's last login
            $upd = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $upd->execute([':id' => $user['id']]);

            // Log successful login
            log_activity(
                'LOGIN',
                "User '{$user['username']}' successfully logged in",
                'AUTHENTICATION',
                $user['id'],
                $user['username']
            );

            return [
                'success' => true,
                'user'    => [
                    'id'        => $user['id'],
                    'username'  => $user['username'],
                    'full_name' => $user['full_name'],
                    'role'      => $user['role']
                ]
            ];
        } else {
            // Log failed login attempt
            log_activity(
                'FAILED_LOGIN',
                "Failed login attempt for username '{$username}'",
                'AUTHENTICATION',
                null,
                $username
            );

            return ['success' => false, 'error' => 'Invalid username or password.'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Authentication service error.'];
    }
}

/**
 * Securely terminate user session and clear cookies
 */
function logout_user() {
    start_secure_session();

    $userId   = $_SESSION['user_id'] ?? null;
    $username = $_SESSION['username'] ?? null;

    if ($userId || $username) {
        log_activity(
            'LOGOUT',
            "User '{$username}' logged out",
            'AUTHENTICATION',
            $userId,
            $username
        );
    }

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Record an activity or system event to `system_logs`
 * Strictly sanitized: Never records passwords, OTPs, or API secrets.
 */
function log_activity($action, $description, $eventType = 'SYSTEM', $userId = null, $username = null, $deviceId = null) {
    try {
        $pdo = get_db();

        // Populate user details from session if not provided
        if ($userId === null && !empty($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        if ($username === null && !empty($_SESSION['username'])) {
            $username = $_SESSION['username'];
        }

        // Detect IP
        $ip = '127.0.0.1';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($parts[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Detect User Agent
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'System/ESP32', 0, 250);

        // Sanitize Description - mask any sensitive terms just in case
        $sanitizedDesc = preg_replace('/(password|api_key|secret|token|otp)\s*[:=]\s*([^\s,;]+)/i', '$1: [PROTECTED]', $description);

        $stmt = $pdo->prepare("
            INSERT INTO system_logs 
                (user_id, username, action, description, event_type, ip_address, user_agent, device_id, created_at)
            VALUES 
                (:uid, :uname, :act, :desc, :etype, :ip, :ua, :did, NOW())
        ");

        $stmt->execute([
            ':uid'   => $userId,
            ':uname' => $username,
            ':act'   => strtoupper(trim($action)),
            ':desc'  => trim($sanitizedDesc),
            ':etype' => strtoupper(trim($eventType)),
            ':ip'    => substr($ip, 0, 45),
            ':ua'    => $userAgent,
            ':did'   => $deviceId
        ]);

        return true;
    } catch (Exception $e) {
        // Logging should fail gracefully and not break operations
        error_log("Failed to insert system log: " . $e->getMessage());
        return false;
    }
}
