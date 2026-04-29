<?php
/**
 * Authentication Helper
 * Handles session management and page protection
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if the user is logged in
 * @return bool
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirects to login page if not authenticated
 */
function requireAuth() {
    if (!isAuthenticated()) {
        // Prevent redirect loop if already on login.php
        $currentPage = basename($_SERVER['PHP_SELF']);
        if ($currentPage !== 'login.php') {
            header('Location: login.php');
            exit();
        }
    }
}

// Initialize global user variable if logged in
if (isAuthenticated()) {
    $currentUser = $_SESSION['user_data'];
}
?>
