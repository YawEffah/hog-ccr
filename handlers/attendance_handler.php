<?php
/**
 * Attendance Handler — Record Session & Attendance
 * POST action: record_attendance
 */
require_once '../includes/auth.php';
requireAuth();
require_once '../includes/db.php';
require_once '../includes/helpers.php';

verifyCsrf();

$action   = $_POST['action'] ?? '';
$db       = getDB();
$redirect = '../attendance.php';

if ($action === 'record_attendance') {
    $sessionType = trim($_POST['session_type'] ?? '');
    $sessionDate = $_POST['session_date']      ?? date('Y-m-d');
    $sessionTime = $_POST['session_time']      ?? null;
    $presentIds  = $_POST['present_members']   ?? [];   // array of member IDs marked present
    $visitorIds  = $_POST['visitor_members']   ?? [];   // array of member IDs marked visitor

    if (!$sessionType || !$sessionDate) {
        redirect($redirect . '?error=missing_fields');
    }

    try {
        $db->beginTransaction();

        // Upsert session
        $sessStmt = $db->prepare(
            "INSERT INTO attendance_sessions (session_type, session_date, session_time, recorded_by)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE session_time = VALUES(session_time), recorded_by = VALUES(recorded_by)"
        );
        $sessStmt->execute([$sessionType, $sessionDate, $sessionTime ?: null, $_SESSION['user_id']]);

        // Get session ID
        $sessionId = (int)$db->lastInsertId();
        if (!$sessionId) {
            $sel = $db->prepare("SELECT id FROM attendance_sessions WHERE session_type=? AND session_date=?");
            $sel->execute([$sessionType, $sessionDate]);
            $sessionId = (int)$sel->fetchColumn();
        }

        // Fetch all active members to compute absent list
        $allMembers = $db->query("SELECT id FROM members WHERE status != 'Visitor'")->fetchAll(PDO::FETCH_COLUMN);

        $presentSet = array_map('intval', $presentIds);
        $visitorSet = array_map('intval', $visitorIds);

        $recStmt = $db->prepare(
            "INSERT INTO attendance_records (session_id, member_id, status, check_in_time)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE status = VALUES(status), check_in_time = VALUES(check_in_time)"
        );

        $checkInTime = $sessionTime ?: date('H:i:s');

        foreach ($allMembers as $mid) {
            if (in_array($mid, $presentSet, true)) {
                $recStmt->execute([$sessionId, $mid, 'Present', $checkInTime]);
            } elseif (in_array($mid, $visitorSet, true)) {
                $recStmt->execute([$sessionId, $mid, 'Visitor', $checkInTime]);
            } else {
                $recStmt->execute([$sessionId, $mid, 'Absent', null]);
            }
        }

        $db->commit();

        $presentCount = count($presentSet);
        logActivity(
            "Recorded attendance for {$sessionType} on {$sessionDate}: {$presentCount} present",
            'attendance'
        );
        redirect($redirect . '?success=attendance_recorded');

    } catch (PDOException $e) {
        $db->rollBack();
        error_log('record_attendance error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

redirect($redirect . '?error=unknown_action');
