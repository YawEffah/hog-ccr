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
        'secure'   => (APP_ENV === 'production'), // set true when on HTTPS
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

// ── Idle Timeout ─────────────────────────────────────────────────────────────
define('SESSION_IDLE_TIMEOUT', 1800); // 30 minutes in seconds

/**
 * Redirect to login if not authenticated.
 * Also enforces idle session timeout.
 */
function requireAuth(): void
{
    if (!isAuthenticated()) {
        $currentPage = basename($_SERVER['PHP_SELF']);
        if ($currentPage !== 'login.php') {
            header('Location: login.php');
            exit();
        }
    } else {
        // Check idle timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT) {
            destroySession();
            header('Location: login.php?reason=idle');
            exit();
        }
        $_SESSION['last_activity'] = time();
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

/**
 * Check if the currently logged-in user has a specific permission.
 */
function hasPermission(string $perm): bool
{
    global $currentUser;
    if (!$currentUser || empty($currentUser['permissions'])) {
        return false;
    }
    return !empty($currentUser['permissions'][$perm]);
}
