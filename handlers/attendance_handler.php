<?php
/**
 * Attendance Handler — Ministry-Scoped Attendance
 * POST action: record_ministry_attendance
 */
require_once '../includes/auth.php';
requireAuth();
require_once '../includes/db.php';
require_once '../includes/helpers.php';

verifyCsrf();

$action   = $_POST['action'] ?? '';
$db       = getDB();
$redirect = '../ministries.php';

if ($action === 'record_ministry_attendance') {
    $ministryId  = (int)($_POST['ministry_id']    ?? 0);
    $sessionType = trim($_POST['session_type']     ?? '');
    $sessionDate = $_POST['session_date']          ?? date('Y-m-d');
    $sessionTime = $_POST['session_time']          ?? null;
    $presentIds  = $_POST['present_members']       ?? [];   // array of member IDs marked present

    if (!$ministryId || !$sessionType || !$sessionDate) {
        flash('error', 'Please select a ministry, session type, and date.');
        redirect($redirect);
    }

    // Verify ministry exists
    $minCheck = $db->prepare("SELECT id, name FROM ministries WHERE id = ?");
    $minCheck->execute([$ministryId]);
    $ministry = $minCheck->fetch();
    if (!$ministry) {
        flash('error', 'Ministry not found.');
        redirect($redirect);
    }

    try {
        $db->beginTransaction();

        // Upsert session (scoped to this ministry)
        $sessStmt = $db->prepare(
            "INSERT INTO attendance_sessions (session_type, ministry_id, session_date, session_time, recorded_by)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE session_time = VALUES(session_time), recorded_by = VALUES(recorded_by)"
        );
        $sessStmt->execute([$sessionType, $ministryId, $sessionDate, $sessionTime ?: null, $_SESSION['user_id']]);

        // Get session ID
        $sessionId = (int)$db->lastInsertId();
        if (!$sessionId) {
            $sel = $db->prepare("SELECT id FROM attendance_sessions WHERE session_type=? AND session_date=? AND ministry_id=?");
            $sel->execute([$sessionType, $sessionDate, $ministryId]);
            $sessionId = (int)$sel->fetchColumn();
        }

        // Fetch only members of this ministry
        $ministryMembers = $db->prepare(
            "SELECT m.id FROM members m
             JOIN member_ministries mm ON m.id = mm.member_id
             WHERE mm.ministry_id = ? AND m.status != 'Affiliate Community Member'"
        );
        $ministryMembers->execute([$ministryId]);
        $allMemberIds = $ministryMembers->fetchAll(PDO::FETCH_COLUMN);

        $presentSet = array_map('intval', $presentIds);

        $recStmt = $db->prepare(
            "INSERT INTO attendance_records (session_id, member_id, status, check_in_time)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE status = VALUES(status), check_in_time = VALUES(check_in_time)"
        );

        $checkInTime = $sessionTime ?: date('H:i:s');

        foreach ($allMemberIds as $mid) {
            if (in_array((int)$mid, $presentSet, true)) {
                $recStmt->execute([$sessionId, $mid, 'Present', $checkInTime]);
            } else {
                $recStmt->execute([$sessionId, $mid, 'Absent', null]);
            }
        }

        $db->commit();

        $presentCount = count($presentSet);
        logActivity(
            "Recorded {$ministry['name']} attendance for {$sessionType} on {$sessionDate}: {$presentCount} present",
            'attendance'
        );
        flash('success', "Attendance recorded for {$ministry['name']}: {$presentCount} present.");
        redirect($redirect);

    } catch (PDOException $e) {
        $db->rollBack();
        error_log('record_ministry_attendance error: ' . $e->getMessage());
        flash('error', 'A database error occurred. Please try again.');
        redirect($redirect);
    }
}

if ($action === 'record_family_attendance') {
    $familyId  = (int)($_POST['family_id']    ?? 0);
    $sessionType = trim($_POST['session_type']     ?? '');
    $sessionDate = $_POST['session_date']          ?? date('Y-m-d');
    $sessionTime = $_POST['session_time']          ?? null;
    $presentIds  = $_POST['present_members']       ?? [];   // array of member IDs marked present

    if (!$familyId || !$sessionType || !$sessionDate) {
        flash('error', 'Please select a family, session type, and date.');
        redirect('../families.php');
    }

    // Verify family exists
    $famCheck = $db->prepare("SELECT id, name FROM families WHERE id = ?");
    $famCheck->execute([$familyId]);
    $family = $famCheck->fetch();
    if (!$family) {
        flash('error', 'Family not found.');
        redirect('../families.php');
    }

    try {
        $db->beginTransaction();

        // Upsert session (scoped to this family)
        $sessStmt = $db->prepare(
            "INSERT INTO attendance_sessions (session_type, family_id, session_date, session_time, recorded_by)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE session_time = VALUES(session_time), recorded_by = VALUES(recorded_by)"
        );
        $sessStmt->execute([$sessionType, $familyId, $sessionDate, $sessionTime ?: null, $_SESSION['user_id']]);

        // Get session ID
        $sessionId = (int)$db->lastInsertId();
        if (!$sessionId) {
            $sel = $db->prepare("SELECT id FROM attendance_sessions WHERE session_type=? AND session_date=? AND family_id=?");
            $sel->execute([$sessionType, $sessionDate, $familyId]);
            $sessionId = (int)$sel->fetchColumn();
        }

        // Fetch only members of this family
        $familyMembers = $db->prepare(
            "SELECT m.id FROM members m
             JOIN member_families mf ON m.id = mf.member_id
             WHERE mf.family_id = ? AND m.status != 'Affiliate Community Member'"
        );
        $familyMembers->execute([$familyId]);
        $allMemberIds = $familyMembers->fetchAll(PDO::FETCH_COLUMN);

        $presentSet = array_map('intval', $presentIds);

        $recStmt = $db->prepare(
            "INSERT INTO attendance_records (session_id, member_id, status, check_in_time)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE status = VALUES(status), check_in_time = VALUES(check_in_time)"
        );

        $checkInTime = $sessionTime ?: date('H:i:s');

        foreach ($allMemberIds as $mid) {
            if (in_array((int)$mid, $presentSet, true)) {
                $recStmt->execute([$sessionId, $mid, 'Present', $checkInTime]);
            } else {
                $recStmt->execute([$sessionId, $mid, 'Absent', null]);
            }
        }

        $db->commit();

        $presentCount = count($presentSet);
        logActivity(
            "Recorded {$family['name']} attendance for {$sessionType} on {$sessionDate}: {$presentCount} present",
            'attendance'
        );
        flash('success', "Attendance recorded for {$family['name']}: {$presentCount} present.");
        redirect('../families.php');

    } catch (PDOException $e) {
        $db->rollBack();
        error_log('record_family_attendance error: ' . $e->getMessage());
        flash('error', 'A database error occurred. Please try again.');
        redirect('../families.php');
    }
}

redirect($redirect . '?error=unknown_action');
