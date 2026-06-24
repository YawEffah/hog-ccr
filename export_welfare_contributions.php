<?php
/**
 * Welfare Contributions - CSV Exporter
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

// ── Query welfare contributions ──────────────────────────────────────────────
$query = "
    SELECT wc.*, m.first_name, m.last_name, m.member_code, wm.family_group
    FROM welfare_contributions wc
    JOIN welfare_members wm ON wc.welfare_id = wm.id
    JOIN members m ON wm.member_id = m.id
    WHERE DATE_FORMAT(wc.payment_date, '%Y-%m') = :month
";

$bindParams = [':month' => $filterMonth];

if ($search !== '') {
    $query .= " AND (m.first_name LIKE :search OR m.last_name LIKE :search OR m.member_code LIKE :search OR wc.reference_no LIKE :search)";
    $bindParams[':search'] = "%{$search}%";
}

if ($family !== 'All' && $family !== '') {
    $query .= " AND wm.family_group = :family";
    $bindParams[':family'] = $family;
}

$query .= " ORDER BY wc.payment_date DESC, wc.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($bindParams);
$rawContribs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set CSV download headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=welfare_contributions_' . $filterMonth . '_' . date('Ymd_His') . '.csv');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Microsoft Excel encoding compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Column headers
fputcsv($output, [
    'Contribution ID',
    'Member ID',
    'Member Name',
    'Family Group',
    'Amount (GHS)',
    'Payment Method',
    'Payment Date',
    'Reference No',
    'Notification Status'
]);

// Write rows
foreach ($rawContribs as $c) {
    fputcsv($output, [
        $c['id'],
        $c['member_code'],
        $c['first_name'] . ' ' . $c['last_name'],
        $c['family_group'] ?: '—',
        number_format((float)$c['amount'], 2, '.', ''),
        $c['payment_method'],
        date('M j, Y', strtotime($c['payment_date'])),
        $c['reference_no'] ?: '—',
        $c['notif_sent'] ? 'Sent' : 'Not sent'
    ]);
}

fclose($output);
exit();
