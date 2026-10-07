<?php
// Smart Water Tank Monitoring System - Logout Endpoint
// Securely terminates session, deletes session cookie, creates LOGOUT audit log,
// and redirects user to public landing page.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// Log out user & destroy session
logout_user();

// Send anti-caching headers to protect back button
prevent_client_caching();

// Redirect to landing page (or root URL)
header('Location: landing.php?logout=1');
exit;
