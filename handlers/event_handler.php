<?php
/**
 * Event & Announcement Handler
 * POST actions: add_event | edit_event | delete_event
 *               add_announcement | delete_announcement
 *
 * Fixes applied:
 *  - verifyCsrf() called correctly (void, no argument)
 *  - target_group column now exists in schema
 *  - logActivity() used (not logError())
 *  - flash() / redirect() helpers used consistently
 *  - $_SESSION['user_id'] used (not 'admin_id')
 *  - category field maps to 'type' column correctly
 */
require_once '../includes/auth.php';
requireAuth();
require_once '../includes/db.php';
require_once '../includes/helpers.php';

verifyCsrf();

$action   = $_POST['action'] ?? '';
$db       = getDB();
$redirect = '../events.php';

// ── ADD EVENT ─────────────────────────────────────────────────────────────────
if ($action === 'add_event') {
    $title       = trim($_POST['title']        ?? '');
    $date        = $_POST['date']              ?? null;
    $time        = $_POST['time']              ?? null;
    $venue       = trim($_POST['location']     ?? '');
    $type        = $_POST['category']          ?? 'Weekly';
    $targetGroup = $_POST['target_group']      ?? 'All Members';
    $description = trim($_POST['description']  ?? '');

    $allowedTypes = ['Weekly','Monthly','Annual','Special','Service','Meeting','Convention','Retreat','Special Program'];

    if (!$title || !$date) {
        redirect($redirect . '?error=missing_fields');
    }

    // Sanitise type — fall back to 'Special' if unknown value submitted
    if (!in_array($type, $allowedTypes, true)) {
        $type = 'Special';
    }

    try {
        $stmt = $db->prepare(
            "INSERT INTO events (title, event_date, event_time, venue, type, target_group, description, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $title,
            $date,
            $time ?: null,
            $venue ?: null,
            $type,
            $targetGroup,
            $description ?: null,
            $_SESSION['user_id']
        ]);

        logActivity("Created new event: {$title}", 'events');
        redirect($redirect . '?success=event_added');

    } catch (PDOException $e) {
        error_log('add_event error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── EDIT EVENT ────────────────────────────────────────────────────────────────
if ($action === 'edit_event') {
    $id          = (int)($_POST['event_id']    ?? 0);
    $title       = trim($_POST['title']        ?? '');
    $date        = $_POST['date']              ?? null;
    $time        = $_POST['time']              ?? null;
    $venue       = trim($_POST['location']     ?? '');
    $type        = $_POST['category']          ?? 'Weekly';
    $targetGroup = $_POST['target_group']      ?? 'All Members';
    $description = trim($_POST['description']  ?? '');

    $allowedTypes = ['Weekly','Monthly','Annual','Special','Service','Meeting','Convention','Retreat','Special Program'];

    if (!$id || !$title || !$date) {
        redirect($redirect . '?error=missing_fields');
    }

    if (!in_array($type, $allowedTypes, true)) {
        $type = 'Special';
    }

    try {
        $stmt = $db->prepare(
            "UPDATE events
             SET title=?, event_date=?, event_time=?, venue=?, type=?, target_group=?, description=?
             WHERE id=?"
        );
        $stmt->execute([
            $title,
            $date,
            $time ?: null,
            $venue ?: null,
            $type,
            $targetGroup,
            $description ?: null,
            $id
        ]);

        logActivity("Updated event: {$title}", 'events');
        redirect($redirect . '?success=event_updated');

    } catch (PDOException $e) {
        error_log('edit_event error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── DELETE EVENT ──────────────────────────────────────────────────────────────
if ($action === 'delete_event') {
    $id = (int)($_POST['event_id'] ?? 0);

    if (!$id) {
        redirect($redirect . '?error=missing_fields');
    }

    try {
        // Fetch title for log before deleting
        $row = $db->prepare("SELECT title FROM events WHERE id = ?");
        $row->execute([$id]);
        $title = $row->fetchColumn() ?: 'Unknown';

        $db->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);

        logActivity("Deleted event: {$title}", 'events');
        redirect($redirect . '?success=event_deleted');

    } catch (PDOException $e) {
        error_log('delete_event error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── ADD ANNOUNCEMENT ──────────────────────────────────────────────────────────
if ($action === 'add_announcement') {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $pinned      = isset($_POST['pinned']) ? 1 : 0;
    $adminId     = $_SESSION['user_id'] ?? null;   // Fixed: was 'admin_id'

    if (!$title || !$description) {
        redirect($redirect . '?error=missing_fields');
    }

    try {
        $stmt = $db->prepare(
            "INSERT INTO announcements (title, description, pinned, posted_by)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$title, $description, $pinned, $adminId]);

        logActivity("Posted announcement: {$title}", 'events');
        redirect($redirect . '?success=announcement_posted');

    } catch (PDOException $e) {
        error_log('add_announcement error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── DELETE ANNOUNCEMENT ───────────────────────────────────────────────────────
if ($action === 'delete_announcement') {
    $id = (int)($_POST['announcement_id'] ?? 0);

    if (!$id) {
        redirect($redirect . '?error=missing_fields');
    }

    try {
        $row = $db->prepare("SELECT title FROM announcements WHERE id = ?");
        $row->execute([$id]);
        $title = $row->fetchColumn() ?: 'Unknown';

        $db->prepare("DELETE FROM announcements WHERE id = ?")->execute([$id]);

        logActivity("Deleted announcement: {$title}", 'events');
        redirect($redirect . '?success=announcement_deleted');

    } catch (PDOException $e) {
        error_log('delete_announcement error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

redirect($redirect . '?error=unknown_action');
