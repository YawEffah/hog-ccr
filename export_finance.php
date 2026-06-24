<?php
/**
 * Finance Transactions - CSV Exporter
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';

$db = getDB();

// ── Process Filters ──────────────────────────────────────────────────────────
$whereClauses = [];
$params = [];

$search    = trim($_GET['search'] ?? '');
$type      = trim($_GET['type'] ?? '');
$method    = trim($_GET['method'] ?? '');
$fromDate  = trim($_GET['from_date'] ?? '');
$toDate    = trim($_GET['to_date'] ?? '');

if ($search) {
    $whereClauses[] = "(t.week_number LIKE ?)";
    $searchWildcard = "%{$search}%";
    array_push($params, $searchWildcard);
}

if ($type) {
    $whereClauses[] = "t.type = ?";
    $params[] = $type;
}

if ($method) {
    $whereClauses[] = "t.payment_method = ?";
    $params[] = $method;
}

if ($fromDate) {
    $whereClauses[] = "t.transaction_date >= ?";
    $params[] = $fromDate;
}

if ($toDate) {
    $whereClauses[] = "t.transaction_date <= ?";
    $params[] = $toDate;
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = "WHERE " . implode(' AND ', $whereClauses);
}

// ── Query Records ────────────────────────────────────────────────────────────
$query = "
    SELECT t.* 
    FROM finance_transactions t
    $whereSql
    ORDER BY t.transaction_date DESC, t.created_at DESC
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$txns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=finance_transactions_' . date('Ymd_His') . '.csv');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Column headings
fputcsv($output, [
    'Transaction ID',
    'Date',
    'Week',
    'Type',
    'Payment Method',
    'Reference No',
    'Amount (GHS)',
    'Notes'
]);

// Write records
foreach ($txns as $tx) {
    fputcsv($output, [
        $tx['id'],
        $tx['transaction_date'],
        $tx['week_number'],
        $tx['type'],
        $tx['payment_method'],
        $tx['reference_no'] ?: 'N/A',
        number_format($tx['amount'], 2, '.', ''),
        $tx['notes'] ?: ''
    ]);
}

fclose($output);
exit();
