<?php
/**
 * Welfare Financial Reports - Excel Exporter
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';

if (!isset($_GET['report'])) {
    die("Invalid report request.");
}
$reportType = $_GET['report'];

$db = getDB();

$filterMonth = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $filterMonth)) {
    $filterMonth = date('Y-m');
}
$currentYear = explode('-', $filterMonth)[0];
$periodEndDate = date('jS F, Y', strtotime(date('Y-m-t', strtotime($filterMonth . '-01'))));

// Calculate Ledger Balances
$accountsStmt = $db->prepare("
    SELECT a.code, a.name, a.type, 
    SUM(CASE WHEN l.transaction_date <= LAST_DAY(STR_TO_DATE(CONCAT(?, '-01'), '%Y-%m-%d')) THEN l.debit ELSE 0 END) as total_debit,
    SUM(CASE WHEN l.transaction_date <= LAST_DAY(STR_TO_DATE(CONCAT(?, '-01'), '%Y-%m-%d')) THEN l.credit ELSE 0 END) as total_credit,
    SUM(CASE WHEN YEAR(l.transaction_date) = ? AND l.transaction_date <= LAST_DAY(STR_TO_DATE(CONCAT(?, '-01'), '%Y-%m-%d')) THEN l.debit ELSE 0 END) as ytd_debit,
    SUM(CASE WHEN YEAR(l.transaction_date) = ? AND l.transaction_date <= LAST_DAY(STR_TO_DATE(CONCAT(?, '-01'), '%Y-%m-%d')) THEN l.credit ELSE 0 END) as ytd_credit
    FROM welfare_accounts a
    LEFT JOIN welfare_ledger l ON a.id = l.account_id
    GROUP BY a.id
");
$accountsStmt->execute([$filterMonth, $filterMonth, $currentYear, $filterMonth, $currentYear, $filterMonth]);
$accounts = $accountsStmt->fetchAll(PDO::FETCH_ASSOC);

$balances = [];
foreach ($accounts as $acc) {
    if ($acc['type'] == 'Revenue' || $acc['type'] == 'Expense') {
        $dr = $acc['ytd_debit'];
        $cr = $acc['ytd_credit'];
    } else {
        $dr = $acc['total_debit'];
        $cr = $acc['total_credit'];
    }

    if ($acc['type'] == 'Asset' || $acc['type'] == 'Expense') {
        $bal = $dr - $cr;
    } else {
        $bal = $cr - $dr;
    }
    $balances[$acc['code']] = [
        'name' => $acc['name'],
        'type' => $acc['type'],
        'balance' => $bal
    ];
}

$subIncome = $balances['4000']['balance'] ?? 0;
$othIncome = $balances['4100']['balance'] ?? 0;
$totalIncome = $subIncome + $othIncome;

$benExpense = $balances['5000']['balance'] ?? 0;
$momoExpense = $balances['5100']['balance'] ?? 0;
$totalExpense = $benExpense + $momoExpense;
$surplus = $totalIncome - $totalExpense;

$openAR = 19790.00; 
$closeAR = $balances['1100']['balance'] ?? 0;
$cashReceived = $subIncome - ($closeAR - $openAR); 
$cashPaid = $totalExpense;
$netOperating = $cashReceived - $cashPaid;

$investing = $othIncome;
$financing = 0;
$netIncrease = $netOperating + $investing + $financing;
$openBank = 98344.33; 
$closeBank = $openBank + $netIncrease;

$cashAtBank = $balances['1000']['balance'] ?? 0;
$cashOnHand = $balances['1010']['balance'] ?? 0;
$acctRec = $balances['1100']['balance'] ?? 0;
$currAssets = $cashAtBank + $cashOnHand + $acctRec;

$acctPay = $balances['2000']['balance'] ?? 0;
$netAssets = $currAssets - $acctPay;
$accumFund = $balances['3000']['balance'] ?? 0;

// Set Excel Headers
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Welfare_{$reportType}_" . date('Ymd') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<table border='1'>";

if ($reportType === 'performance') {
    echo "<tr><th colspan='3'>ADOM FIE CCR COMMUNITY WELFARE</th></tr>";
    echo "<tr><th colspan='3'>Statement of Financial Performance (Income & Expenditure) For the period ended {$periodEndDate}</th></tr>";
    echo "<tr><th>Income</th><th>Ghc</th><th>Ghc</th></tr>";
    echo "<tr><td>Subscription for {$currentYear}</td><td></td><td>{$subIncome}</td></tr>";
    echo "<tr><td>Other Income</td><td></td><td>{$othIncome}</td></tr>";
    echo "<tr><td><b>Total Income</b></td><td></td><td><b>{$totalIncome}</b></td></tr>";
    echo "<tr><td colspan='3'></td></tr>";
    echo "<tr><td><b>Expenses</b></td><td></td><td></td></tr>";
    echo "<tr><td>Benefits to Members</td><td>{$benExpense}</td><td></td></tr>";
    echo "<tr><td>Momo Charges</td><td>{$momoExpense}</td><td></td></tr>";
    echo "<tr><td>Total Expenses</td><td></td><td>{$totalExpense}</td></tr>";
    echo "<tr><td colspan='3'></td></tr>";
    echo "<tr><td><b>Surplus</b></td><td></td><td><b>{$surplus}</b></td></tr>";

} elseif ($reportType === 'cashflow') {
    echo "<tr><th colspan='3'>Adom Fie CCR Community Welfare</th></tr>";
    echo "<tr><th colspan='3'>Statement of Cash Flows For the period ended {$periodEndDate}</th></tr>";
    echo "<tr><td><b>Operating Activities</b></td><td></td><td><b>Ghc</b></td></tr>";
    echo "<tr><td>Cash received from Members</td><td></td><td>{$cashReceived}</td></tr>";
    echo "<tr><td>Cash paid for Benefits</td><td></td><td>{$cashPaid}</td></tr>";
    echo "<tr><td><b>Net Cash from Operating Activities</b></td><td></td><td><b>{$netOperating}</b></td></tr>";
    echo "<tr><td colspan='3'></td></tr>";
    echo "<tr><td><b>Investing Activities</b></td><td></td><td></td></tr>";
    echo "<tr><td>Bank Interest</td><td></td><td>{$investing}</td></tr>";
    echo "<tr><td><b>Net Cash from Investing Activities</b></td><td></td><td><b>{$investing}</b></td></tr>";
    echo "<tr><td colspan='3'></td></tr>";
    echo "<tr><td><b>Financing Activities</b></td><td></td><td></td></tr>";
    echo "<tr><td>Proceeds from Loans</td><td></td><td>-</td></tr>";
    echo "<tr><td>Loan Repayments</td><td></td><td>-</td></tr>";
    echo "<tr><td><b>Net Cash from Financing Activities</b></td><td></td><td>-</td></tr>";
    echo "<tr><td colspan='3'></td></tr>";
    echo "<tr><td>Net Increase / (Decrease) in Cash</td><td></td><td>{$netIncrease}</td></tr>";
    echo "<tr><td>Cash at Bank (Opening)</td><td></td><td>{$openBank}</td></tr>";
    echo "<tr><td><b>Cash at Bank (Closing)</b></td><td></td><td><b>{$closeBank}</b></td></tr>";

} elseif ($reportType === 'position') {
    echo "<tr><th colspan='3'>Adom Fie CCR Community Welfare</th></tr>";
    echo "<tr><th colspan='3'>Statement of Financial Position As At {$periodEndDate}</th></tr>";
    echo "<tr><td><b>Assets</b></td><td><b>Ghc</b></td><td><b>Ghc</b></td></tr>";
    echo "<tr><td><b>Current Assets</b></td><td></td><td></td></tr>";
    echo "<tr><td>Cash at Bank</td><td></td><td>{$cashAtBank}</td></tr>";
    echo "<tr><td>Cash on Hand</td><td></td><td>{$cashOnHand}</td></tr>";
    echo "<tr><td>Accounts Receivable</td><td></td><td>{$acctRec}</td></tr>";
    echo "<tr><td></td><td></td><td><b>{$currAssets}</b></td></tr>";
    echo "<tr><td colspan='3'></td></tr>";
    echo "<tr><td><b>Non-Current Assets</b></td><td></td><td></td></tr>";
    echo "<tr><td>Land & Buildings</td><td></td><td>-</td></tr>";
    echo "<tr><td>Furniture & Equipment</td><td></td><td>-</td></tr>";
    echo "<tr><td>Vehicles</td><td></td><td>-</td></tr>";
    echo "<tr><td><b>Total Assets</b></td><td></td><td><b>{$currAssets}</b></td></tr>";
    echo "<tr><td colspan='3'></td></tr>";
    echo "<tr><td><b>Liabilities</b></td><td></td><td></td></tr>";
    echo "<tr><td><b>Current Liabilities</b></td><td></td><td></td></tr>";
    echo "<tr><td>Accounts Payable</td><td>{$acctPay}</td><td></td></tr>";
    echo "<tr><td>Total Liabilities</td><td></td><td>{$acctPay}</td></tr>";
    echo "<tr><td colspan='3'></td></tr>";
    echo "<tr><td><b>Net Assets / Equity (Fund Balance)</b></td><td></td><td></td></tr>";
    echo "<tr><td>Surplus</td><td></td><td>{$surplus}</td></tr>";
    echo "<tr><td>Accumulated Fund</td><td></td><td>{$accumFund}</td></tr>";
    echo "<tr><td><b>Total Net Assets</b></td><td></td><td><b>" . ($accumFund + $surplus) . "</b></td></tr>";

} elseif ($reportType === 'trialbalance') {
    echo "<tr><th rowspan='2'>Particulars</th><th colspan='2'>CCR COMMUNITY</th></tr>";
    echo "<tr><th colspan='2'>As At {$periodEndDate}</th></tr>";
    echo "<tr><th></th><th>Debit</th><th>Credit</th></tr>";
    echo "<tr><td><b>Capital Account</b></td><td></td><td><b>{$accumFund}</b></td></tr>";
    echo "<tr><td><b>Current Assets</b></td><td><b>{$currAssets}</b></td><td><b>{$acctPay}</b></td></tr>";
    echo "<tr><td><b>Direct Income</b></td><td></td><td><b>{$subIncome}</b></td></tr>";
    echo "<tr><td><b>Direct Expenses</b></td><td><b>{$benExpense}</b></td><td></td></tr>";
    echo "<tr><td><b>Indirect Incomes</b></td><td></td><td><b>{$othIncome}</b></td></tr>";
    echo "<tr><td><b>Indirect Expenses</b></td><td><b>{$momoExpense}</b></td><td></td></tr>";
    echo "<tr><td><b>Grand Total</b></td><td><b>" . ($currAssets + $benExpense + $momoExpense) . "</b></td><td><b>" . ($accumFund + $acctPay + $subIncome + $othIncome) . "</b></td></tr>";
}

echo "</table>";
?>
