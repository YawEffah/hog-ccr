<?php
/**
 * Welfare Members - CSV Exporter
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';

$db = getDB();

// ── Process Filters ──────────────────────────────────────────────────────────
$filterMonth = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $filterMonth)) {
    $filterMonth = date('Y-m');
}

$search = trim($_GET['search'] ?? '');
$family = trim($_GET['family'] ?? 'All');
$status = trim($_GET['status'] ?? 'All');

// ── Query welfare roster ─────────────────────────────────────────────────────
$query = "
    SELECT wm.*, m.first_name, m.last_name, m.member_code, m.phone, m.email,
           (SELECT SUM(amount) FROM welfare_contributions WHERE welfare_id = wm.id) as total_paid,
           (SELECT payment_date FROM welfare_contributions WHERE welfare_id = wm.id ORDER BY payment_date DESC LIMIT 1) as last_payment_date
    FROM welfare_members wm
    JOIN members m ON wm.member_id = m.id
";

$whereClauses = [];
$bindParams = [];

if ($search !== '') {
    $whereClauses[] = "(m.first_name LIKE :search OR m.last_name LIKE :search OR m.member_code LIKE :search OR m.phone LIKE :search)";
    $bindParams[':search'] = "%{$search}%";
}

if ($family !== 'All' && $family !== '') {
    $whereClauses[] = "wm.family_group = :family";
    $bindParams[':family'] = $family;
}

if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(' AND ', $whereClauses);
}

$query .= " ORDER BY m.last_name ASC";

$stmt = $db->prepare($query);
$stmt->execute($bindParams);
$rawMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set CSV download headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=welfare_members_' . date('Ymd_His') . '.csv');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Microsoft Excel encoding compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Column headers
fputcsv($output, [
    'Member ID',
    'Name',
    'Phone',
    'Email',
    'Enrolled Month',
    'Family Group',
    'Last Payment Date',
    'Total Contributed (GHS)',
    'Status (' . date('F Y', strtotime($filterMonth . '-01')) . ')'
]);

// Write rows matching status criteria
foreach ($rawMembers as $wm) {
    $monthlyAmount = (float)$wm['monthly_amount'];
    $totalPaid = (float)$wm['total_paid'];
    $enrolTime = strtotime($wm['enrol_date']);
    $enrolYear = (int)date('Y', $enrolTime);
    $enrolMonth = (int)date('m', $enrolTime);

    $targetYear = (int)date('Y', strtotime($filterMonth . '-01'));
    $targetMonth = (int)date('m', strtotime($filterMonth . '-01'));

    $diffMonths = (($targetYear - $enrolYear) * 12) + ($targetMonth - $enrolMonth) + 1;
    $expectedMonths = max(0, $diffMonths);
    $expectedAmount = $expectedMonths * $monthlyAmount;
    
    $arrears = max(0.00, $expectedAmount - $totalPaid);
    $rowStatus = ($arrears <= 0) ? 'Up to date' : 'Arrears';
    
    // Status filter is evaluated in PHP to match client-side logic
    if ($status !== 'All' && $status !== '' && $rowStatus !== $status) {
        continue;
    }
    
    fputcsv($output, [
        $wm['member_code'],
        $wm['first_name'] . ' ' . $wm['last_name'],
        $wm['phone'] ?: '—',
        $wm['email'] ?: '—',
        $wm['enrol_date'] ? date('M Y', strtotime($wm['enrol_date'])) : '—',
        $wm['family_group'] ?: '—',
        $wm['last_payment_date'] ? date('M j, Y', strtotime($wm['last_payment_date'])) : 'Never',
        number_format((float)$wm['total_paid'], 2, '.', ''),
        $rowStatus
    ]);
}

fclose($output);
exit();
