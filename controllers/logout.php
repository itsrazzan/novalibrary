<?php
/**
 * Logout Handler
 * Destroys session and redirects to login page
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include helpers for redirect
require_once __DIR__ . '/../config/helpers.php';

// Clear all session variables
$_SESSION = array();

// Destroy the session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirect to login page
header('Location: ' . getRedirectUrl('views/login.php'));
exit;
?>