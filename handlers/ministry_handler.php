<?php
/**
 * Ministry Handler — Add & Edit Ministries
 * POST actions: add_ministry | edit_ministry
 */
require_once '../includes/auth.php';
requireAuth();
require_once '../includes/db.php';
require_once '../includes/helpers.php';

verifyCsrf();

$action   = $_POST['action'] ?? '';
$db       = getDB();
$redirect = '../ministries.php';

if ($action === 'add_ministry') {
    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon        = trim($_POST['icon']        ?? '✝️');
    $bgColor     = trim($_POST['bg_color']    ?? 'var(--gold-pale)');
    $headId      = (int)($_POST['head_id']    ?? 0);
    $slug        = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $name));

    if (!$name) redirect($redirect . '?error=missing_fields');

    try {
        $db->prepare(
            "INSERT INTO ministries (slug, name, description, head_id, icon, bg_color) VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([$slug, $name, $description ?: null, $headId ?: null, $icon, $bgColor]);

        logActivity("Created ministry: {$name}", 'ministries');
        redirect($redirect . '?success=ministry_added');
    } catch (PDOException $e) {
        error_log('add_ministry: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

if ($action === 'edit_ministry') {
    $id          = (int)($_POST['ministry_id'] ?? 0);
    $name        = trim($_POST['name']         ?? '');
    $description = trim($_POST['description']  ?? '');
    $icon        = trim($_POST['icon']         ?? '✝️');
    $headId      = (int)($_POST['head_id']     ?? 0);
    $newSlug     = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $name)); // regenerate slug

    if (!$id || !$name) redirect($redirect . '?error=missing_fields');

    try {
        $db->prepare(
            "UPDATE ministries SET name=?, description=?, head_id=?, icon=?, slug=? WHERE id=?"
        )->execute([$name, $description ?: null, $headId ?: null, $icon, $newSlug, $id]);

        logActivity("Updated ministry: {$name}", 'ministries');
        redirect($redirect . '?success=ministry_updated');
    } catch (PDOException $e) {
        error_log('edit_ministry: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

if ($action === 'delete_ministry') {
    $id = (int)($_POST['ministry_id'] ?? 0);

    if (!$id) redirect($redirect . '?error=missing_fields');

    // Guard: prevent deletion if members are still assigned
    $count = $db->prepare("SELECT COUNT(*) FROM members WHERE ministry_id = ?");
    $count->execute([$id]);
    if ((int)$count->fetchColumn() > 0) {
        redirect($redirect . '?error=ministry_has_members');
    }

    try {
        $nameRow = $db->prepare("SELECT name FROM ministries WHERE id = ?");
        $nameRow->execute([$id]);
        $mName = $nameRow->fetchColumn() ?: 'Unknown';

        $db->prepare("DELETE FROM ministries WHERE id = ?")->execute([$id]);

        logActivity("Deleted ministry: {$mName}", 'ministries');
        redirect($redirect . '?success=ministry_deleted');
    } catch (PDOException $e) {
        error_log('delete_ministry: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

redirect($redirect . '?error=unknown_action');
