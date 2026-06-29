<?php
/**
 * Ministry Detailed Report - CSV Exporter
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';

$db = getDB();

$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: ministries.php?error=not_found');
    exit();
}

// Fetch ministry info
$minStmt = $db->prepare("
    SELECT min.*, 
           h.id as head_member_id,
           CONCAT(h.first_name, ' ', h.last_name) as head_name,
           h.member_code as head_code,
           (SELECT COUNT(*) FROM member_ministries WHERE ministry_id = min.id) as total_count,
           (SELECT COUNT(*) FROM member_ministries mm JOIN members m ON mm.member_id = m.id WHERE mm.ministry_id = min.id AND m.status='Active') as active_count
    FROM ministries min
    LEFT JOIN members h ON min.head_id = h.id
    WHERE min.id = ?
");
$minStmt->execute([$id]);
$m = $minStmt->fetch(PDO::FETCH_ASSOC);

if (!$m) {
    header('Location: ministries.php?error=not_found');
    exit();
}

// Calculate average attendance and session count
$attStmt = $db->prepare("
    SELECT AVG(present_count / total_possible * 100) as avg_att,
           COUNT(session_id) as total_sessions
    FROM (
        SELECT s.id as session_id, 
               SUM(CASE WHEN r.status = 'Present' THEN 1 ELSE 0 END) as present_count,
               COUNT(r.id) as total_possible
        FROM attendance_sessions s
        JOIN attendance_records r ON s.id = r.session_id
        JOIN members m ON r.member_id = m.id
        JOIN member_ministries mm ON m.id = mm.member_id
        WHERE mm.ministry_id = ?
        GROUP BY s.id
    ) as session_stats
");
$attStmt->execute([$id]);
$attData = $attStmt->fetch(PDO::FETCH_ASSOC);
$avgAtt = $attData['avg_att'] !== null ? round((float)$attData['avg_att']) : 0;
$totalSessions = (int)($attData['total_sessions'] ?? 0);

// Fetch members
$memStmt = $db->prepare("
    SELECT m.member_code, m.first_name, m.last_name, m.phone, m.email, m.status, m.joined_date
    FROM members m
    JOIN member_ministries mm ON m.id = mm.member_id
    WHERE mm.ministry_id = ?
    ORDER BY m.last_name ASC
");
$memStmt->execute([$id]);
$members = $memStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch attendance history trend (sessions list)
$sessHistoryStmt = $db->prepare("
    SELECT s.session_date, s.session_type,
           SUM(CASE WHEN r.status = 'Present' THEN 1 ELSE 0 END) as present_count,
           SUM(CASE WHEN r.status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
           COUNT(r.id) as total_count
    FROM attendance_sessions s
    JOIN attendance_records r ON s.id = r.session_id
    JOIN member_ministries mm ON r.member_id = mm.member_id
    WHERE mm.ministry_id = ?
    GROUP BY s.id
    ORDER BY s.session_date DESC
");
$sessHistoryStmt->execute([$id]);
$sessionsHistory = $sessHistoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ministry_report_' . strtolower(str_replace(' ', '_', $m['name'])) . '_' . date('Ymd_His') . '.csv');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write Ministry Overview Section
fputcsv($output, ['MINISTRY PROFILE REPORT']);
fputcsv($output, ['Ministry Name', $m['name']]);
fputcsv($output, ['Description', $m['description']]);
fputcsv($output, ['Ministry Head', $m['head_name'] ? $m['head_name'] . ' (' . $m['head_code'] . ')' : 'N/A']);
fputcsv($output, ['Total Members', $m['total_count']]);
fputcsv($output, ['Active Members', $m['active_count']]);
fputcsv($output, ['Average Attendance', $avgAtt . '%']);
fputcsv($output, ['Total Sessions Recorded', $totalSessions]);
fputcsv($output, []); // Blank spacer row

// Write Members Roster Section
fputcsv($output, ['MEMBERS ROSTER']);
fputcsv($output, [
    'Member ID',
    'Full Name',
    'Phone',
    'Email',
    'Status',
    'Joined Date'
]);

foreach ($members as $mem) {
    fputcsv($output, [
        $mem['member_code'],
        $mem['first_name'] . ' ' . $mem['last_name'],
        $mem['phone'] ?: '—',
        $mem['email'] ?: '—',
        $mem['status'],
        $mem['joined_date'] ? date('Y-m-d', strtotime($mem['joined_date'])) : '—'
    ]);
}

fputcsv($output, []); // Blank spacer row

// Write Attendance History Section
fputcsv($output, ['ATTENDANCE HISTORY']);
fputcsv($output, [
    'Session Date',
    'Session Type',
    'Present Count',
    'Absent Count',
    'Total Possible',
    'Attendance Rate (%)'
]);

foreach ($sessionsHistory as $sh) {
    $rate = $sh['total_count'] > 0 ? round(($sh['present_count'] / $sh['total_count']) * 100) : 0;
    fputcsv($output, [
        $sh['session_date'],
        $sh['session_type'],
        $sh['present_count'],
        $sh['absent_count'],
        $sh['total_count'],
        $rate . '%'
    ]);
}

fclose($output);
exit();
