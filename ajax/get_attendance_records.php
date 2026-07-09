<?php
require_once '../includes/auth.php';
requireAuth();
require_once '../includes/db.php';

header('Content-Type: application/json');

$ministryId = (int)($_GET['ministry_id'] ?? 0);
$familyId = (int)($_GET['family_id'] ?? 0);
$sessionType = trim($_GET['session_type'] ?? '');
$date = trim($_GET['date'] ?? '');
$memberId = (int)($_GET['member_id'] ?? 0);

if (!$ministryId && !$familyId) {
    echo json_encode([]);
    exit;
}

$db = getDB();

if ($ministryId) {
    $baseQuery = "
        FROM attendance_records r
        JOIN attendance_sessions s ON r.session_id = s.id
        JOIN members m ON r.member_id = m.id
        WHERE s.ministry_id = ?
    ";
    $params = [$ministryId];
} else {
    $baseQuery = "
        FROM attendance_records r
        JOIN attendance_sessions s ON r.session_id = s.id
        JOIN members m ON r.member_id = m.id
        WHERE s.family_id = ?
    ";
    $params = [$familyId];
}

$whereClause = "";

if ($sessionType !== '') {
    $whereClause .= " AND s.session_type = ?";
    $params[] = $sessionType;
}

if ($date !== '') {
    $whereClause .= " AND s.session_date = ?";
    $params[] = $date;
}

if ($memberId > 0) {
    $whereClause .= " AND m.id = ?";
    $params[] = $memberId;
}

try {
    // 1. Fetch filtered stats
    $statsQuery = "
        SELECT 
            SUM(CASE WHEN r.status = 'Present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN r.status = 'Absent' THEN 1 ELSE 0 END) as absent_count
        " . $baseQuery . $whereClause;
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->execute($params);
    $statsRow = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    $present = (int)$statsRow['present_count'];
    $absent = (int)$statsRow['absent_count'];
    $total = $present + $absent;
    $rate = $total > 0 ? round(($present / $total) * 100) : 0;

    // 2. Fetch filtered records
    $recordsQuery = "
        SELECT r.id, s.session_date, s.session_type, r.status, m.first_name, m.last_name, m.member_code
        " . $baseQuery . $whereClause . "
        ORDER BY s.session_date DESC, m.last_name ASC LIMIT 100
    ";
    $stmt = $db->prepare($recordsQuery);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = array_map(function($r) {
        return [
            'id' => $r['id'],
            'date' => date('M j, Y', strtotime($r['session_date'])),
            'type' => $r['session_type'],
            'member' => $r['first_name'] . ' ' . $r['last_name'],
            'code' => $r['member_code'],
            'status' => $r['status']
        ];
    }, $records);

    echo json_encode([
        'stats' => [
            'present' => $present,
            'absent' => $absent,
            'rate' => $rate . '%'
        ],
        'records' => $formatted
    ]);
} catch (PDOException $e) {
    echo json_encode(['stats' => ['present' => 0, 'absent' => 0, 'rate' => '0%'], 'records' => []]);
}
