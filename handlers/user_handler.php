<?php
/**
 * User Handler — Add, Edit & Delete Admins
 * POST actions: add_user | edit_user | delete_user
 */
require_once '../includes/auth.php';
requireAuth();
require_once '../includes/db.php';
require_once '../includes/helpers.php';

// Only administrators can perform these actions
if (($currentUser['role'] ?? '') !== 'Administrator') {
    redirect('../index.php');
}

verifyCsrf();

$action   = $_POST['action'] ?? '';
$db       = getDB();
$redirect = '../users.php';

// ── ADD USER ────────────────────────────────────────────────────────────────
if ($action === 'add_user') {
    $name     = trim($_POST['name']     ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $role     = $_POST['role']          ?? 'Secretary';
    $initials = strtoupper(trim($_POST['initials'] ?? ''));

    if (!$name || !$username || !$email || !$password) {
        redirect($redirect . '?error=missing_fields');
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $db->prepare(
            "INSERT INTO admins (name, username, email, password, role, initials) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$name, $username, $email, $hashedPassword, $role, $initials]);

        logActivity("Created new admin account: {$username} ({$role})", 'system');
        redirect($redirect . '?success=user_added');

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Integrity constraint violation (duplicate)
            redirect($redirect . '?error=duplicate_entry');
        }
        error_log('add_user error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── EDIT USER ───────────────────────────────────────────────────────────────
if ($action === 'edit_user') {
    $id       = (int)($_POST['user_id'] ?? 0);
    $name     = trim($_POST['name']     ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $role     = $_POST['role']          ?? 'Secretary';
    $initials = strtoupper(trim($_POST['initials'] ?? ''));

    if (!$id || !$name || !$username || !$email) {
        redirect($redirect . '?error=missing_fields');
    }

    try {
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare(
                "UPDATE admins SET name=?, username=?, email=?, password=?, role=?, initials=? WHERE id=?"
            );
            $stmt->execute([$name, $username, $email, $hashedPassword, $role, $initials, $id]);
        } else {
            $stmt = $db->prepare(
                "UPDATE admins SET name=?, username=?, email=?, role=?, initials=? WHERE id=?"
            );
            $stmt->execute([$name, $username, $email, $role, $initials, $id]);
        }

        logActivity("Updated admin account: {$username}", 'system');
        redirect($redirect . '?success=user_updated');

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            redirect($redirect . '?error=duplicate_entry');
        }
        error_log('edit_user error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── DELETE USER ─────────────────────────────────────────────────────────────
if ($action === 'delete_user') {
    $id = (int)($_POST['user_id'] ?? 0);

    if (!$id || $id == $currentUser['id']) {
        redirect($redirect . '?error=invalid_action');
    }

    try {
        $db->prepare("DELETE FROM admins WHERE id = ?")->execute([$id]);
        logActivity("Deleted admin account ID: {$id}", 'system');
        redirect($redirect . '?success=user_deleted');

    } catch (PDOException $e) {
        error_log('delete_user error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

redirect($redirect . '?error=unknown_action');
