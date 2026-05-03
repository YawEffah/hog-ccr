<?php
/**
 * Authentication Helper
 * Handles session management and page protection.
 */

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,           // session cookie (expires when browser closes)
        'path'     => '/',
        'secure'   => false,       // set true when on HTTPS
        'httponly' => true,        // prevent JS access to cookie
        'samesite' => 'Strict',
    ]);
    session_start();
}

/**
 * Check if the current user is logged in.
 */
function isAuthenticated(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_data']);
}

/**
 * Redirect to login if not authenticated.
 * Also regenerates session ID on every page load to prevent fixation.
 */
function requireAuth(): void
{
    if (!isAuthenticated()) {
        $currentPage = basename($_SERVER['PHP_SELF']);
        if ($currentPage !== 'login.php') {
            header('Location: login.php');
            exit();
        }
    }
}

/**
 * Destroy the session completely on logout.
 */
function destroySession(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

// Populate global $currentUser from session
if (isAuthenticated()) {
    $currentUser = $_SESSION['user_data'];
}
