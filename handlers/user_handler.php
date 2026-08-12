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
if (!hasPermission('perm_manage_users')) {
    redirect('../dashboard.php');
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
    $phone    = trim($_POST['phone']    ?? '');
    $password = $_POST['password']      ?? '';
    $role     = $_POST['role']          ?? 'Secretary';
    $initials = strtoupper(trim($_POST['initials'] ?? ''));

    if (!$name || !$username || !$email || !$password) {
        redirect($redirect . '?error=missing_fields');
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $db->prepare(
            "INSERT INTO admins (name, username, email, phone, password, role, initials) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$name, $username, $email, $phone ?: null, $hashedPassword, $role, $initials]);

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
    $phone    = trim($_POST['phone']    ?? '');
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
                "UPDATE admins SET name=?, username=?, email=?, phone=?, password=?, role=?, initials=? WHERE id=?"
            );
            $stmt->execute([$name, $username, $email, $phone ?: null, $hashedPassword, $role, $initials, $id]);
        } else {
            $stmt = $db->prepare(
                "UPDATE admins SET name=?, username=?, email=?, phone=?, role=?, initials=? WHERE id=?"
            );
            $stmt->execute([$name, $username, $email, $phone ?: null, $role, $initials, $id]);
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

// ── ADD ROLE ────────────────────────────────────────────────────────────────
if ($action === 'add_role') {
    $name = trim($_POST['name'] ?? '');
    $perm_manage_users = isset($_POST['perm_manage_users']) ? 1 : 0;
    $perm_manage_finance = isset($_POST['perm_manage_finance']) ? 1 : 0;
    $perm_manage_welfare = isset($_POST['perm_manage_welfare']) ? 1 : 0;
    $perm_manage_members = isset($_POST['perm_manage_members']) ? 1 : 0;
    $perm_manage_events = isset($_POST['perm_manage_events']) ? 1 : 0;

    if (!$name) {
        redirect($redirect . '?error=missing_fields');
    }

    try {
        $stmt = $db->prepare(
            "INSERT INTO system_roles (name, perm_manage_users, perm_manage_finance, perm_manage_welfare, perm_manage_members, perm_manage_events) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$name, $perm_manage_users, $perm_manage_finance, $perm_manage_welfare, $perm_manage_members, $perm_manage_events]);

        logActivity("Created new role: {$name}", 'system');
        redirect($redirect . '?success=role_added');

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            redirect($redirect . '?error=duplicate_entry');
        }
        error_log('add_role error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── EDIT ROLE ───────────────────────────────────────────────────────────────
if ($action === 'edit_role') {
    $id = (int)($_POST['role_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $perm_manage_users = isset($_POST['perm_manage_users']) ? 1 : 0;
    $perm_manage_finance = isset($_POST['perm_manage_finance']) ? 1 : 0;
    $perm_manage_welfare = isset($_POST['perm_manage_welfare']) ? 1 : 0;
    $perm_manage_members = isset($_POST['perm_manage_members']) ? 1 : 0;
    $perm_manage_events = isset($_POST['perm_manage_events']) ? 1 : 0;

    if (!$id || !$name) {
        redirect($redirect . '?error=missing_fields');
    }

    try {
        // First get the old role name to update users
        $oldRoleStmt = $db->prepare("SELECT name FROM system_roles WHERE id = ?");
        $oldRoleStmt->execute([$id]);
        $oldRole = $oldRoleStmt->fetchColumn();

        $stmt = $db->prepare(
            "UPDATE system_roles SET name=?, perm_manage_users=?, perm_manage_finance=?, perm_manage_welfare=?, perm_manage_members=?, perm_manage_events=? WHERE id=?"
        );
        $stmt->execute([$name, $perm_manage_users, $perm_manage_finance, $perm_manage_welfare, $perm_manage_members, $perm_manage_events, $id]);

        // If name changed, update admins table
        if ($oldRole && $oldRole !== $name) {
            $updateAdmins = $db->prepare("UPDATE admins SET role = ? WHERE role = ?");
            $updateAdmins->execute([$name, $oldRole]);
        }

        logActivity("Updated role: {$name}", 'system');
        redirect($redirect . '?success=role_updated');

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            redirect($redirect . '?error=duplicate_entry');
        }
        error_log('edit_role error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── DELETE ROLE ─────────────────────────────────────────────────────────────
if ($action === 'delete_role') {
    $id = (int)($_POST['role_id'] ?? 0);

    if (!$id) {
        redirect($redirect . '?error=invalid_action');
    }

    try {
        // Get role name
        $roleStmt = $db->prepare("SELECT name FROM system_roles WHERE id = ?");
        $roleStmt->execute([$id]);
        $roleName = $roleStmt->fetchColumn();

        if ($roleName) {
            // Check if any admins are using this role
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM admins WHERE role = ?");
            $checkStmt->execute([$roleName]);
            if ($checkStmt->fetchColumn() > 0) {
                redirect($redirect . '?error=role_in_use');
            }

            $db->prepare("DELETE FROM system_roles WHERE id = ?")->execute([$id]);
            logActivity("Deleted role: {$roleName}", 'system');
            redirect($redirect . '?success=role_deleted');
        } else {
            redirect($redirect . '?error=invalid_action');
        }

    } catch (PDOException $e) {
        error_log('delete_role error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

redirect($redirect . '?error=unknown_action');
