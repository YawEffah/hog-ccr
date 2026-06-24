<?php
/**
 * Members Directory - CSV Exporter
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';

$db = getDB();

// ── Process Filters ──────────────────────────────────────────────────────────
$search    = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$where  = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (m.member_code LIKE ? OR m.first_name LIKE ? OR m.last_name LIKE ?
                     OR m.phone LIKE ? OR min.name LIKE ?)";
    $s = "%{$search}%";
    $params = array_merge($params, [$s, $s, $s, $s, $s]);
}
if ($statusFilter && in_array($statusFilter, ['Active','Inactive','Affiliate Community Member'], true)) {
    $where    .= " AND m.status = ?";
    $params[]  = $statusFilter;
}

// ── Query Records ────────────────────────────────────────────────────────────
$query = "
    SELECT m.*,
            (SELECT GROUP_CONCAT(min.name SEPARATOR ', ') FROM member_ministries mm JOIN ministries min ON mm.ministry_id = min.id WHERE mm.member_id = m.id) AS ministry_name,
            (SELECT GROUP_CONCAT(sacrament) FROM member_sacraments WHERE member_id = m.id) as sacraments,
            (SELECT GROUP_CONCAT(sacrament) FROM member_sacraments_needed WHERE member_id = m.id) as sacraments_needed,
            (SELECT GROUP_CONCAT(programme) FROM member_programmes WHERE member_id = m.id) as programmes
     FROM members m
     LEFT JOIN member_ministries mm2 ON m.id = mm2.member_id
     LEFT JOIN ministries min ON mm2.ministry_id = min.id
     $where
     GROUP BY m.id
     ORDER BY m.created_at DESC
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=members_directory_' . date('Ymd_His') . '.csv');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Column headings
fputcsv($output, [
    'Member ID',
    'First Name',
    'Last Name',
    'Gender',
    'Phone',
    'Secondary Phone',
    'Email',
    'Date of Birth',
    'Joined Date',
    'Status',
    'Address',
    'Home Town',
    'Occupation',
    'Marital Status',
    'Children Count',
    'Baptised',
    'Communicant',
    'Group Memberships',
    'Next of Kin Name',
    'Next of Kin Relation',
    'Next of Kin Address',
    'Next of Kin Phone',
    'Ministries',
    'Sacraments Received',
    'Sacraments Needed',
    'Programmes Attended',
    'Notes'
]);

// Write records
foreach ($members as $m) {
    fputcsv($output, [
        $m['member_code'],
        $m['first_name'],
        $m['last_name'],
        $m['gender'] ?? 'Male',
        $m['phone'] ?: '—',
        $m['phone2'] ?: '—',
        $m['email'] ?: '—',
        $m['dob'] ? date('Y-m-d', strtotime($m['dob'])) : '—',
        $m['joined_date'] ? date('Y-m-d', strtotime($m['joined_date'])) : '—',
        $m['status'],
        $m['address'] ?: '—',
        $m['home_town'] ?: '—',
        $m['occupation'] ?: '—',
        $m['marital_status'] ?: '—',
        $m['children_count'] ?? 0,
        $m['is_baptised'] ? 'Yes' : 'No',
        $m['is_communicant'] ? 'Yes' : 'No',
        $m['group_memberships'] ?: '—',
        $m['next_of_kin_name'] ?: '—',
        $m['next_of_kin_relation'] ?: '—',
        $m['next_of_kin_address'] ?: '—',
        $m['next_of_kin_phone'] ?: '—',
        $m['ministry_name'] ?: 'None',
        $m['sacraments'] ?: 'None',
        $m['sacraments_needed'] ?: 'None',
        $m['programmes'] ?: 'None',
        $m['notes'] ?: ''
    ]);
}

fclose($output);
exit();
