<?php
/**
 * Family Handler — Add & Edit Families
 * POST actions: add_family | edit_family
 */
require_once '../includes/auth.php';
requireAuth();
require_once '../includes/db.php';
require_once '../includes/helpers.php';

verifyCsrf();

$action   = $_POST['action'] ?? '';
$db       = getDB();
$redirect = '../families.php';

if ($action === 'add_family') {
    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon        = trim($_POST['icon']        ?? '✝️');
    $bgColor     = trim($_POST['bg_color']    ?? 'var(--gold-pale)');
    $meetingTime = trim($_POST['meeting_time'] ?? '');
    $slug        = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $name));

    if (!$name) redirect($redirect . '?error=missing_fields');

    try {
        $db->prepare(
            "INSERT INTO families (slug, name, description, meeting_time, icon, bg_color) VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([$slug, $name, $description ?: null, $meetingTime ?: null, $icon, $bgColor]);

        logActivity("Created family: {$name}", 'families');
        redirect($redirect . '?success=family_added');
    } catch (PDOException $e) {
        error_log('add_family: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

if ($action === 'edit_family') {
    $id          = (int)($_POST['family_id'] ?? 0);
    $name        = trim($_POST['name']         ?? '');
    $description = trim($_POST['description']  ?? '');
    $icon        = trim($_POST['icon']         ?? '✝️');
    $meetingTime = trim($_POST['meeting_time'] ?? '');
    $newSlug     = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $name)); // regenerate slug

    if (!$id || !$name) redirect($redirect . '?error=missing_fields');

    try {
        $db->prepare(
            "UPDATE families SET name=?, description=?, meeting_time=?, icon=?, slug=? WHERE id=?"
        )->execute([$name, $description ?: null, $meetingTime ?: null, $icon, $newSlug, $id]);

        logActivity("Updated family: {$name}", 'families');
        redirect($redirect . '?success=family_updated');
    } catch (PDOException $e) {
        error_log('edit_family: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

if ($action === 'delete_family') {
    $id = (int)($_POST['family_id'] ?? 0);

    if (!$id) redirect($redirect . '?error=missing_fields');

    // Guard: prevent deletion if members are still assigned
    $count = $db->prepare("SELECT COUNT(*) FROM member_families WHERE family_id = ?");
    $count->execute([$id]);
    if ((int)$count->fetchColumn() > 0) {
        redirect($redirect . '?error=family_has_members');
    }

    try {
        $nameRow = $db->prepare("SELECT name FROM families WHERE id = ?");
        $nameRow->execute([$id]);
        $mName = $nameRow->fetchColumn() ?: 'Unknown';

        $db->prepare("DELETE FROM families WHERE id = ?")->execute([$id]);

        logActivity("Deleted family: {$mName}", 'families');
        redirect($redirect . '?success=family_deleted');
    } catch (PDOException $e) {
        error_log('delete_family: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

if ($action === 'send_family_bulk_message') {
    $familyId = (int)($_POST['family_id'] ?? 0);
    $subject    = trim($_POST['subject']      ?? '');
    $message    = trim($_POST['message']      ?? '');
    $channel    = $_POST['channel']           ?? 'both';

    if (!$familyId || !$message) {
        redirect($redirect . '?error=missing_fields');
    }

    $result = broadcastFamilyMessage($familyId, $subject, $message, $channel);

    logActivity("Broadcast to {$result['family']}: {$result['sent']} sent, {$result['failed']} failed", 'families');
    redirect($redirect . "?success=messages_sent&sent={$result['sent']}&failed={$result['failed']}");
}

if ($action === 'enrol_family_member') {
    $familyId = (int)($_POST['family_id'] ?? 0);
    $memberId   = (int)($_POST['member_id']   ?? 0);
    $role       = trim($_POST['role']         ?? 'Member');
    $enrolDate  = date('Y-m-d'); // Auto-capture enrol date
    $notes      = trim($_POST['notes']        ?? '');
    $adminId    = $_SESSION['admin_id']       ?? null;

    if (!$familyId || !$memberId) {
        redirect($redirect . '?error=missing_fields');
    }

    try {
        $stmt = $db->prepare(
            "INSERT INTO member_families (member_id, family_id, role, enrol_date, notes, enrolled_by) 
             VALUES (?, ?, ?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE 
             role = VALUES(role), enrol_date = VALUES(enrol_date), notes = VALUES(notes), enrolled_by = VALUES(enrolled_by)"
        );
        $stmt->execute([$memberId, $familyId, $role, $enrolDate, $notes, $adminId]);

        // Send Welcome Message
        if (isset($_POST['send_welcome'])) {
            $memRow = $db->prepare("SELECT first_name, last_name, email, phone, member_code FROM members WHERE id = ?");
            $memRow->execute([$memberId]);
            $member = $memRow->fetch(PDO::FETCH_ASSOC);

            $minRow = $db->prepare("SELECT name FROM families WHERE id = ?");
            $minRow->execute([$familyId]);
            $minName = $minRow->fetchColumn();

            if ($member && $minName) {
                // Send Welcome Message
                $msg = "Dear {$member['first_name']}, you have been enrolled in the {$minName} as a {$role}. God bless you. - Adom Fie CCR";
                if ($member['phone']) sendSMS($member['phone'], $msg);
            }
        }

        logActivity("Enrolled member ID {$memberId} in family ID {$familyId}", 'families');
        redirect($redirect . '?success=member_enrolled');
    } catch (PDOException $e) {
        error_log('enrol_family_member: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

if ($action === 'remove_family_member') {
    $familyId = (int)($_POST['family_id'] ?? 0);
    $memberId   = (int)($_POST['member_id']   ?? 0);

    if (!$familyId || !$memberId) {
        redirect($redirect . '?error=missing_fields');
    }

    try {
        $db->prepare("DELETE FROM member_families WHERE member_id = ? AND family_id = ?")->execute([$memberId, $familyId]);
        logActivity("Removed member ID {$memberId} from family ID {$familyId}", 'families');
        redirect($redirect . '?success=member_removed');
    } catch (PDOException $e) {
        error_log('remove_family_member: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

if ($action === 'edit_family_member') {
    $familyId = (int)($_POST['family_id'] ?? 0);
    $memberId   = (int)($_POST['member_id']   ?? 0);
    $role       = trim($_POST['role']         ?? 'Member');
    $notes      = trim($_POST['notes']        ?? '');

    if (!$familyId || !$memberId) {
        redirect($redirect . '?error=missing_fields');
    }

    try {
        $stmt = $db->prepare("UPDATE member_families SET role = ?, notes = ? WHERE member_id = ? AND family_id = ?");
        $stmt->execute([$role, $notes, $memberId, $familyId]);

        logActivity("Updated member ID {$memberId} in family ID {$familyId}", 'families');
        redirect($redirect . '?success=member_updated');
    } catch (PDOException $e) {
        error_log('edit_family_member: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

redirect($redirect . '?error=unknown_action');
