<?php
// Smart Water Tank Monitoring System - Login Controller
// Authenticates credentials against `users` table in database, creates session,
// and supports both AJAX and direct routing to the landing page in-place login modal.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

start_secure_session();

// If already logged in, redirect directly to dashboard
if (is_authenticated()) {
    header('Location: dashboard.php');
    exit;
}

// Check if request is AJAX or expects JSON
$isJson = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
          str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') ||
          str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');

// Handle POST authentication
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $jsonData = json_decode($rawInput, true);
    
    $username = $jsonData['username'] ?? $_POST['username'] ?? '';
    $password = $jsonData['password'] ?? $_POST['password'] ?? '';

    // Real database authentication
    $result = authenticate_user($username, $password);

    if ($isJson) {
        if ($result['success']) {
            json_response(['success' => true, 'redirect' => 'dashboard.php']);
        } else {
            json_response(['success' => false, 'error' => $result['error'] ?? 'Invalid username or password.'], 401);
        }
    } else {
        if ($result['success']) {
            header('Location: dashboard.php');
            exit;
        } else {
            $err = urlencode($result['error'] ?? 'Invalid username or password.');
            header("Location: landing.php?login=1&error={$err}");
            exit;
        }
    }
}

// For direct GET requests to login.php, redirect to landing page with login modal open
$params = [];
$params[] = 'login=1';
if (isset($_GET['expired'])) $params[] = 'expired=1';
if (isset($_GET['logout']))  $params[] = 'logout=1';

$query = '?' . implode('&', $params);
header("Location: landing.php{$query}");
exit;
