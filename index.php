<?php
// Smart Water Tank Monitoring System - Root Entry Point
// Routes authenticated users to Dashboard, and unauthenticated public visitors to Landing Page.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

start_secure_session();

if (is_authenticated()) {
    // Authenticated user browsing root -> redirect to Dashboard
    header('Location: dashboard.php');
    exit;
} else {
    // Unauthenticated visitor browsing root -> render Public Landing Page directly
    require __DIR__ . '/landing.php';
    exit;
}
