<?php
/**
 * Finance Expenses - CSV Exporter
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';

$db = getDB();

// ── Process Filters ──────────────────────────────────────────────────────────
$whereClauses = [];
$params = [];

$search    = trim($_GET['search'] ?? '');
$fromDate  = trim($_GET['from_date'] ?? '');
$toDate    = trim($_GET['to_date'] ?? '');

if ($search) {
    $whereClauses[] = "(e.description LIKE ? OR e.reference_no LIKE ? OR e.notes LIKE ?)";
    $searchWildcard = "%{$search}%";
    array_push($params, $searchWildcard, $searchWildcard, $searchWildcard);
}

if ($fromDate) {
    $whereClauses[] = "e.expense_date >= ?";
    $params[] = $fromDate;
}

if ($toDate) {
    $whereClauses[] = "e.expense_date <= ?";
    $params[] = $toDate;
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = "WHERE " . implode(' AND ', $whereClauses);
}

// ── Query Records ────────────────────────────────────────────────────────────
$query = "
    SELECT e.*, a.name as asset_name, ad.name as recorded_by_name
    FROM finance_expenses e
    LEFT JOIN finance_accounts a ON e.asset_account_id = a.id
    LEFT JOIN admins ad ON e.recorded_by = ad.id
    $whereSql
    ORDER BY e.expense_date DESC, e.created_at DESC
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=finance_expenses_' . date('Ymd_His') . '.csv');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Column headings
fputcsv($output, [
    'ID',
    'Date',
    'Type',
    'Paid From',
    'Amount (GHS)',
    'Description',
    'Reference No',
    'Notes',
    'Recorded By'
]);

// Write records
foreach ($expenses as $ex) {
    fputcsv($output, [
        $ex['id'],
        $ex['expense_date'],
        $ex['type'] ?: 'Unknown Type',
        $ex['asset_name'] ?: 'Unknown Asset',
        number_format($ex['amount'], 2, '.', ''),
        $ex['description'],
        $ex['reference_no'] ?: '',
        $ex['notes'] ?: '',
        $ex['recorded_by_name'] ?: ''
    ]);
}

fclose($output);
exit();
