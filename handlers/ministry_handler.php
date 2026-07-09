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
    $count = $db->prepare("SELECT COUNT(*) FROM member_ministries WHERE ministry_id = ?");
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

if ($action === 'send_ministry_bulk_message') {
    $ministryId = (int)($_POST['ministry_id'] ?? 0);
    $subject    = trim($_POST['subject']      ?? '');
    $message    = trim($_POST['message']      ?? '');
    $channel    = $_POST['channel']           ?? 'both';

    if (!$ministryId || !$message) {
        redirect($redirect . '?error=missing_fields');
    }

    $result = broadcastMinistryMessage($ministryId, $subject, $message, $channel);

    logActivity("Broadcast to {$result['ministry']}: {$result['sent']} sent, {$result['failed']} failed", 'ministries');
    redirect($redirect . "?success=messages_sent&sent={$result['sent']}&failed={$result['failed']}");
}

if ($action === 'enrol_ministry_member') {
    $ministryId = (int)($_POST['ministry_id'] ?? 0);
    $memberId   = (int)($_POST['member_id']   ?? 0);
    $role       = trim($_POST['role']         ?? 'Member');
    $enrolDate  = date('Y-m-d'); // Auto-capture enrol date
    $notes      = trim($_POST['notes']        ?? '');
    $adminId    = $_SESSION['admin_id']       ?? null;

    if (!$ministryId || !$memberId) {
        redirect($redirect . '?error=missing_fields');
    }

    try {
        $stmt = $db->prepare(
            "INSERT INTO member_ministries (member_id, ministry_id, role, enrol_date, notes, enrolled_by) 
             VALUES (?, ?, ?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE 
             role = VALUES(role), enrol_date = VALUES(enrol_date), notes = VALUES(notes), enrolled_by = VALUES(enrolled_by)"
        );
        $stmt->execute([$memberId, $ministryId, $role, $enrolDate, $notes, $adminId]);

        // Send Welcome Message
        if (isset($_POST['send_welcome'])) {
            $memRow = $db->prepare("SELECT first_name, last_name, email, phone, member_code FROM members WHERE id = ?");
            $memRow->execute([$memberId]);
            $member = $memRow->fetch(PDO::FETCH_ASSOC);

            $minRow = $db->prepare("SELECT name FROM ministries WHERE id = ?");
            $minRow->execute([$ministryId]);
            $minName = $minRow->fetchColumn();

            if ($member && $minName) {
                // Send Welcome Message
                $msg = "Dear {$member['first_name']}, you have been enrolled in the {$minName} as a {$role}. God bless you. - Adom Fie CCR";
                if ($member['phone']) sendSMS($member['phone'], $msg);
            }
        }

        logActivity("Enrolled member ID {$memberId} in ministry ID {$ministryId}", 'ministries');
        redirect($redirect . '?success=member_enrolled');
    } catch (PDOException $e) {
        error_log('enrol_ministry_member: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

if ($action === 'remove_ministry_member') {
    $ministryId = (int)($_POST['ministry_id'] ?? 0);
    $memberId   = (int)($_POST['member_id']   ?? 0);

    if (!$ministryId || !$memberId) {
        redirect($redirect . '?error=missing_fields');
    }

    try {
        $db->prepare("DELETE FROM member_ministries WHERE member_id = ? AND ministry_id = ?")->execute([$memberId, $ministryId]);
        logActivity("Removed member ID {$memberId} from ministry ID {$ministryId}", 'ministries');
        redirect($redirect . '?success=member_removed');
    } catch (PDOException $e) {
        error_log('remove_ministry_member: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

if ($action === 'edit_ministry_member') {
    $ministryId = (int)($_POST['ministry_id'] ?? 0);
    $memberId   = (int)($_POST['member_id']   ?? 0);
    $role       = trim($_POST['role']         ?? 'Member');
    $notes      = trim($_POST['notes']        ?? '');

    if (!$ministryId || !$memberId) {
        redirect($redirect . '?error=missing_fields');
    }

    try {
        $stmt = $db->prepare("UPDATE member_ministries SET role = ?, notes = ? WHERE member_id = ? AND ministry_id = ?");
        $stmt->execute([$role, $notes, $memberId, $ministryId]);

        logActivity("Updated member ID {$memberId} in ministry ID {$ministryId}", 'ministries');
        redirect($redirect . '?success=member_updated');
    } catch (PDOException $e) {
        error_log('edit_ministry_member: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

redirect($redirect . '?error=unknown_action');
