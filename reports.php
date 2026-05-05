<?php
/**
 * Reports & Analytics Page
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

$pageTitle = 'Reports';
$activePage = 'reports';

$db = getDB();

$filterMonth = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $filterMonth)) {
  $filterMonth = date('Y-m');
}
$currentY = explode('-', $filterMonth)[0];
$currentM = explode('-', $filterMonth)[1];
$currentYear = $currentY;
$periodEndDate = date('jS F, Y', strtotime(date('Y-m-t', strtotime($filterMonth . '-01'))));

// ==========================================
// 1. GROWTH & PERFORMANCE
// ==========================================
$total_members = (int) $db->query("SELECT COUNT(*) FROM members")->fetchColumn();
$active_members = (int) $db->query("SELECT COUNT(*) FROM members WHERE status='Active'")->fetchColumn();
$visitors = (int) $db->query("SELECT COUNT(*) FROM members WHERE status='Visitor'")->fetchColumn();

$lastYearCount = (int) $db->query("SELECT COUNT(*) FROM members WHERE YEAR(created_at) = " . ($currentYear - 1))->fetchColumn();
$thisYearCount = (int) $db->query("SELECT COUNT(*) FROM members WHERE YEAR(created_at) = " . $currentYear)->fetchColumn();
$yoyPercent = $lastYearCount > 0 ? round((($thisYearCount - $lastYearCount) / $lastYearCount) * 100) : 0;
$yoySign = $yoyPercent >= 0 ? '+' : '';

$growth_stats = [
  'total' => $total_members,
  'active' => $active_members,
  'visitors' => $visitors,
  'percent_yoy' => $yoySign . $yoyPercent . '%',
];

for ($q = 1; $q <= 4; $q++) {
  $mStart = ($q - 1) * 3 + 1;
  $mEnd = $q * 3;
  $count = (int) $db->query("SELECT COUNT(*) FROM members WHERE YEAR(created_at) = $currentYear AND MONTH(created_at) BETWEEN $mStart AND $mEnd")->fetchColumn();
  $growth_stats["q{$q}_height"] = min(100, $count * 5 + 20);
}

// Ministry Performance
$minStmt = $db->prepare(
  "SELECT name, 
            (SELECT COUNT(*) FROM attendance_records ar 
             JOIN attendance_sessions s ON ar.session_id = s.id
             JOIN members m ON ar.member_id = m.id 
             WHERE m.ministry_id = min.id AND ar.status='Present' AND DATE_FORMAT(s.session_date, '%Y-%m') = ?) as present_count,
            (SELECT COUNT(*) FROM attendance_records ar 
             JOIN attendance_sessions s ON ar.session_id = s.id
             JOIN members m ON ar.member_id = m.id 
             WHERE m.ministry_id = min.id AND DATE_FORMAT(s.session_date, '%Y-%m') = ?) as total_count,
            bg_color
     FROM ministries min
     LIMIT 6"
);
$minStmt->execute([$filterMonth, $filterMonth]);
$ministry_performance = array_map(function ($m) {
  $percent = $m['total_count'] > 0 ? round(($m['present_count'] / $m['total_count']) * 100) : 0;
  return [
    'label' => $m['name'],
    'percent' => $percent,
    'bar_class' => $m['bg_color']
  ];
}, $minStmt->fetchAll());

// Attendance Trends (Last 6 Months)
$attendance_trend = [];
$max_att = 0;
for ($i = 5; $i >= 0; $i--) {
  $mDate = date('Y-m-01', strtotime("$filterMonth-01 -$i months"));
  $attStmt = $db->prepare(
    "SELECT 
            SUM(CASE WHEN s.session_type = 'Sunday Service' THEN 1 ELSE 0 END) as sunday_count,
            SUM(CASE WHEN s.session_type != 'Sunday Service' THEN 1 ELSE 0 END) as other_count
         FROM attendance_records ar
         JOIN attendance_sessions s ON ar.session_id = s.id
         WHERE DATE_FORMAT(s.session_date, '%Y-%m') = ? AND ar.status = 'Present'"
  );
  $attStmt->execute([date('Y-m', strtotime($mDate))]);
  $row = $attStmt->fetch();
  $sun = (int) $row['sunday_count'];
  $oth = (int) $row['other_count'];
  $max_att = max($max_att, $sun, $oth);

  $attendance_trend[] = [
    'month' => date('M', strtotime($mDate)),
    'sun' => $sun,
    'oth' => $oth
  ];
}
$max_att = max($max_att, 1);
foreach ($attendance_trend as &$at) {
  $at['sun_h'] = max(5, round(($at['sun'] / $max_att) * 100));
  $at['oth_h'] = max(5, round(($at['oth'] / $max_att) * 100));
}
unset($at);


// ==========================================
// 2. FINANCIAL ANALYTICS
// ==========================================
$annual_finance = [];
$ytd_total_val = 0;
for ($m = 1; $m <= 12; $m++) {
  if ($m > date('n'))
    break;
  $mDate = date("Y-$m-01");
  $mName = date('M', strtotime($mDate));

  $stmt = $db->prepare("SELECT SUM(amount) FROM finance_transactions WHERE MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?");
  $stmt->execute([$m, $currentYear]);
  $amt = (float) $stmt->fetchColumn();

  $wStmt = $db->prepare("SELECT SUM(amount) FROM welfare_contributions WHERE MONTH(payment_date) = ? AND YEAR(payment_date) = ?");
  $wStmt->execute([$m, $currentYear]);
  $amt += (float) $wStmt->fetchColumn();

  $targetStmt = $db->prepare("SELECT target_amount FROM finance_targets WHERE target_month = ?");
  $targetStmt->execute([date('Y-m-01', strtotime($mDate))]);
  $target = (float) $targetStmt->fetchColumn() ?: 10000;

  $percent = round(($amt / $target) * 100);
  $annual_finance[] = [
    'month' => $mName,
    'amount' => number_format($amt, 0),
    'target_percent' => $percent,
    'bar_width_percent' => min(100, $percent),
    'is_success' => $amt >= $target
  ];
  $ytd_total_val += $amt;
}
$ytd_total = number_format($ytd_total_val);

// Revenue Breakdown
$rbStmt = $db->query("SELECT type, SUM(amount) as total FROM finance_transactions WHERE YEAR(transaction_date) = $currentYear GROUP BY type");
$revenue_breakdown = $rbStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$welfare_total = (float) $db->query("SELECT SUM(amount) FROM welfare_contributions WHERE YEAR(payment_date) = $currentYear")->fetchColumn();
if ($welfare_total > 0) {
  $revenue_breakdown['Welfare'] = $welfare_total;
}
$revColors = ['Tithe' => 'var(--gold)', 'Offering' => 'var(--deep)', 'Donation' => '#2E7D57', 'Pledge' => '#7C3AED', 'Welfare' => 'var(--deep3)'];


// ==========================================
// 3. SYSTEMS & OPERATIONS
// ==========================================
$activityStmt = $db->prepare(
  "SELECT l.*, a.name as admin_name 
     FROM activity_log l
     LEFT JOIN admins a ON l.admin_id = a.id
     WHERE DATE_FORMAT(l.created_at, '%Y-%m') = ?
     ORDER BY l.created_at DESC 
     LIMIT 5"
);
$activityStmt->execute([$filterMonth]);
$rawActs = $activityStmt->fetchAll();
$recent_activity = array_map(function ($a, $index) use ($rawActs) {
  return [
    'title' => $a['action'],
    'details' => date('M j \a\t g:ia', strtotime($a['created_at'])) . ' · by ' . ($a['admin_name'] ?? 'System'),
    'dot_color' => 'var(--gold)',
    'is_last' => $index === count($rawActs) - 1
  ];
}, $rawActs, array_keys($rawActs));

$sysStats = [
  'admins' => (int) $db->query("SELECT COUNT(*) FROM admins")->fetchColumn(),
  'logs' => (int) $db->query("SELECT COUNT(*) FROM activity_log WHERE DATE_FORMAT(created_at, '%Y-%m') = '$filterMonth'")->fetchColumn(),
  'events' => (int) $db->query("SELECT COUNT(*) FROM events WHERE DATE_FORMAT(event_date, '%Y-%m') = '$filterMonth'")->fetchColumn()
];

// ==========================================
// 4. WELFARE ACCOUNTING
// ==========================================
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
    'balance' => $bal,
    'dr' => $dr,
    'cr' => $cr
  ];
}

$subIncome = $balances['4000']['balance'] ?? 0;
$othIncome = $balances['4100']['balance'] ?? 0;
$totalIncome = $subIncome + $othIncome;

$benExpense = $balances['5000']['balance'] ?? 0;
$momoExpense = $balances['5100']['balance'] ?? 0;
$totalExpense = $benExpense + $momoExpense;
$surplus = $totalIncome - $totalExpense;

$prevYearEnd = ($currentYear - 1) . '-12-31';
$openStmt = $db->prepare("
    SELECT a.code, 
           SUM(l.debit) - SUM(l.credit) as bal 
    FROM welfare_accounts a 
    JOIN welfare_ledger l ON a.id = l.account_id 
    WHERE l.transaction_date <= ? 
      AND a.type IN ('Asset')
    GROUP BY a.code
");
$openStmt->execute([$prevYearEnd]);
$openBals = $openStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$openAR = $openBals['1100'] ?? 0;
$closeAR = $balances['1100']['balance'] ?? 0;
$cashReceived = $subIncome - ($closeAR - $openAR);
$cashPaid = $totalExpense;
$netOperating = $cashReceived - $cashPaid;

$investing = $othIncome;
$financing = 0;
$netIncrease = $netOperating + $investing + $financing;
$openBank = ($openBals['1000'] ?? 0) + ($openBals['1010'] ?? 0);
$closeBank = $openBank + $netIncrease;

$cashAtBank = $balances['1000']['balance'] ?? 0;
$cashOnHand = $balances['1010']['balance'] ?? 0;
$acctRec = $balances['1100']['balance'] ?? 0;
$currAssets = $cashAtBank + $cashOnHand + $acctRec;

$acctPay = $balances['2000']['balance'] ?? 0;
$netAssets = $currAssets - $acctPay;
$accumFund = $balances['3000']['balance'] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">

<?php require_once 'includes/head.php'; ?>

<style>
  .accordion-item {
    background: white;
    border-radius: 12px;
    border: 1px solid var(--border);
    margin-bottom: 24px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
  }

  .accordion-header {
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    background: var(--bg-light);
    border-bottom: 1px solid transparent;
    transition: background 0.2s;
  }

  .accordion-header:hover {
    background: #F9F8F6;
  }

  .accordion-header h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: var(--deep);
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .accordion-header .ph-caret-down {
    transition: transform 0.3s ease;
    color: var(--muted);
    font-size: 18px;
  }

  .accordion-body {
    display: none;
    padding: 24px;
    background: #FAFAFA;
  }

  .accordion-item.active .accordion-header {
    border-bottom: 1px solid var(--border);
  }

  .accordion-item.active .accordion-body {
    display: block;
  }

  .accordion-item.active .accordion-header .ph-caret-down {
    transform: rotate(180deg);
  }

  /* Reusable tiny chart bars */
  .mini-bar-wrap {
    display: flex;
    gap: 4px;
    align-items: flex-end;
    height: 100px;
    margin-bottom: 10px;
  }

  .mini-bar-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
  }

  .mini-bar {
    width: 100%;
    border-radius: 3px 3px 0 0;
  }
</style>

<body>

  <?php require_once 'includes/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main id="main">

    <div id="page-reports" class="page">
      <div class="topbar">
        <div style="display:flex;align-items:center;">
          <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="ph ph-list"></i>
          </button>
          <div class="topbar-title">Reports & Analytics</div>
        </div>
        <div class="topbar-actions">
          <?php
          $monthsList = [
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December'
          ];
          $thisYear = date('Y');
          ?>
          <div style="display:flex;gap:8px;">
            <select class="form-control" style="width:120px;padding:8px 12px;" id="reportMonthSelect"
              onchange="updateReportFilter()">
              <?php foreach ($monthsList as $num => $name): ?>
                <option value="<?= $num ?>" <?= $currentM === $num ? 'selected' : '' ?>><?= $name ?></option>
              <?php endforeach; ?>
            </select>
            <select class="form-control" style="width:90px;padding:8px 12px;" id="reportYearSelect"
              onchange="updateReportFilter()">
              <?php for ($y = $thisYear; $y >= $thisYear - 5; $y--): ?>
                <option value="<?= $y ?>" <?= (string) $currentY === (string) $y ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="content">

        <!-- ACCORDION 1: GROWTH & PERFORMANCE -->
        <div class="accordion-item">
          <div class="accordion-header" onclick="toggleAccordion(this)">
            <h2><i class="ph ph-trend-up"></i> Growth & Performance</h2>
            <i class="ph ph-caret-down"></i>
          </div>
          <div class="accordion-body">
            <div class="grid-3" style="gap:24px;">

              <!-- Membership Growth -->
              <div class="card" style="margin:0;" id="card-membership">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                  <div style="display:flex; align-items:center; gap:8px;">
                    <h3 style="margin:0;">Membership Growth</h3><span
                      class="badge badge-green"><?= $growth_stats['percent_yoy'] ?> YoY</span>
                  </div>
                  <button class="btn btn-outline btn-sm" onclick="exportPDF('card-membership', 'Membership Growth')"><i
                      class="ph ph-file-pdf"></i> PDF</button>
                </div>
                <div class="card-body">
                  <div class="mini-bar-wrap" style="height:100px;">
                    <?php for ($q = 1; $q <= 4; $q++): ?>
                      <div class="mini-bar-col" style="height:100%; justify-content:flex-end;">
                        <div class="mini-bar"
                          style="background:var(--gold); height:<?= $growth_stats["q{$q}_height"] ?>px; opacity:<?= 0.7 + ($q * 0.07) ?>;">
                        </div>
                        <div style="font-size:10px;color:var(--muted);">Q<?= $q ?></div>
                      </div>
                    <?php endfor; ?>
                  </div>
                  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:14px;">
                    <div style="text-align:center;">
                      <div
                        style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--deep2);">
                        <?= $growth_stats['total'] ?></div>
                      <div style="font-size:11px;color:var(--muted);">Total</div>
                    </div>
                    <div style="text-align:center;">
                      <div
                        style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--success);">
                        <?= $growth_stats['active'] ?></div>
                      <div style="font-size:11px;color:var(--muted);">Active</div>
                    </div>
                    <div style="text-align:center;">
                      <div
                        style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--gold);">
                        <?= $growth_stats['visitors'] ?></div>
                      <div style="font-size:11px;color:var(--muted);">Visitors</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Ministry Performance -->
              <div class="card" style="margin:0;" id="card-ministry">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                  <h3 style="margin:0;">Ministry Performance</h3>
                  <button class="btn btn-outline btn-sm" onclick="exportPDF('card-ministry', 'Ministry Performance')"><i
                      class="ph ph-file-pdf"></i> PDF</button>
                </div>
                <div class="card-body">
                  <div style="display:flex;flex-direction:column;gap:14px;">
                    <?php foreach ($ministry_performance as $mp): ?>
                      <div>
                        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
                          <span style="font-weight:500;"><?= htmlspecialchars($mp['label']) ?></span>
                          <span style="color:var(--muted);"><?= $mp['percent'] ?>%</span>
                        </div>
                        <div style="height:8px;border-radius:10px;background:#EDE8DF;">
                          <div
                            style="height:100%;width:<?= $mp['percent'] ?>%;background:<?= $mp['bar_class'] ?>;border-radius:10px;">
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>

              <!-- Attendance Trends -->
              <div class="card" style="margin:0;" id="card-attendance">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                  <div>
                    <h3 style="margin:0; margin-bottom:4px;">Attendance Trends</h3>
                    <div style="display:flex; gap:8px; font-size:10px; color:var(--muted);">
                      <div style="display:flex; align-items:center; gap:4px;">
                        <div style="width:8px;height:8px;border-radius:2px;background:var(--primary);"></div> Sun
                      </div>
                      <div style="display:flex; align-items:center; gap:4px;">
                        <div style="width:8px;height:8px;border-radius:2px;background:var(--deep);"></div> Oth
                      </div>
                    </div>
                  </div>
                  <button class="btn btn-outline btn-sm" onclick="exportPDF('card-attendance', 'Attendance Trends')"><i
                      class="ph ph-file-pdf"></i> PDF</button>
                </div>
                <div class="card-body">
                  <div class="mini-bar-wrap" style="height:140px; gap:8px;">
                    <?php foreach ($attendance_trend as $at): ?>
                      <div class="mini-bar-col"
                        style="flex-direction:row; justify-content:center; align-items:flex-end; gap:2px; padding:0 4px; height:100%;">
                        <div class="mini-bar-col" style="justify-content:flex-end; height:100%;">
                          <div class="mini-bar" style="background:var(--primary); height:<?= $at['sun_h'] ?>%;"></div>
                        </div>
                        <div class="mini-bar-col" style="justify-content:flex-end; height:100%;">
                          <div class="mini-bar" style="background:var(--deep); height:<?= $at['oth_h'] ?>%;"></div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <div style="display:flex; justify-content:space-around; margin-top:4px;">
                    <?php foreach ($attendance_trend as $at): ?>
                      <div style="font-size:10px; color:var(--muted); text-align:center; width:16%;"><?= $at['month'] ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>


        <!-- ACCORDION 2: FINANCIAL ANALYTICS -->
        <div class="accordion-item">
          <div class="accordion-header" onclick="toggleAccordion(this)">
            <h2><i class="ph ph-wallet"></i> Financial Analytics</h2>
            <i class="ph ph-caret-down"></i>
          </div>
          <div class="accordion-body">
            <div class="grid-2" style="gap:24px;">

              <!-- Annual Finance -->
              <div class="card" style="margin:0;" id="card-annual-finance">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                  <div style="display:flex; align-items:center; gap:8px;">
                    <h3 style="margin:0;">Annual Finance Progress</h3><span class="badge badge-green">YTD</span>
                  </div>
                  <button class="btn btn-outline btn-sm"
                    onclick="exportPDF('card-annual-finance', 'Annual Finance Progress')"><i class="ph ph-file-pdf"></i>
                    PDF</button>
                </div>
                <div class="card-body">
                  <div style="display:flex;flex-direction:column;gap:12px;">
                    <?php foreach ($annual_finance as $fi): ?>
                      <div class="summary-row" style="<?= $fi === end($annual_finance) ? 'border-bottom: none;' : '' ?>">
                        <span style="font-size:13px;color:var(--mid); min-width:30px;"><?= $fi['month'] ?></span>
                        <div style="display:flex;align-items:center;gap:12px;flex:1;margin-left:16px;">
                          <div style="flex:1;height:8px;border-radius:10px;background:#EDE8DF;overflow:hidden;">
                            <div
                              style="height:100%;width:<?= $fi['bar_width_percent'] ?>%;background:var(--gold);border-radius:10px;">
                            </div>
                          </div>
                          <span
                            style="font-size:13px;font-weight:600;color:<?= $fi['is_success'] ? 'var(--success)' : 'var(--deep2)' ?>;min-width:80px;text-align:right;">GH₵<?= $fi['amount'] ?></span>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <div
                    style="margin-top:18px;padding-top:14px;border-top:1px solid #EDE8DF;display:flex;justify-content:space-between;">
                    <span style="font-size:14px;font-weight:700;">YTD Total</span>
                    <span style="font-size:16px;font-weight:700;color:var(--success);">GH₵ <?= $ytd_total ?></span>
                  </div>
                </div>
              </div>

              <!-- Revenue Breakdown -->
              <div class="card" style="margin:0;" id="card-revenue-breakdown">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                  <div>
                    <h3 style="margin:0; margin-bottom:4px;">Revenue Breakdown</h3>
                    <div style="font-size:12px; color:var(--muted);"><?= $currentYear ?></div>
                  </div>
                  <button class="btn btn-outline btn-sm"
                    onclick="exportPDF('card-revenue-breakdown', 'Revenue Breakdown')"><i class="ph ph-file-pdf"></i>
                    PDF</button>
                </div>
                <div class="card-body">
                  <div style="display:flex; flex-direction:column; gap:16px;">
                    <?php
                    $revTotal = array_sum($revenue_breakdown);
                    if ($revTotal > 0):
                      foreach ($revenue_breakdown as $type => $amt):
                        $pct = round(($amt / $revTotal) * 100);
                        $col = $revColors[$type] ?? 'var(--muted)';
                        ?>
                        <div>
                          <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
                            <span style="font-weight:500;"><span
                                style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= $col ?>;margin-right:6px;"></span><?= $type ?></span>
                            <span style="font-weight:600; color:var(--deep2);">GH₵ <?= number_format($amt, 2) ?> <span
                                style="color:var(--muted); font-weight:400; margin-left:6px;"><?= $pct ?>%</span></span>
                          </div>
                          <div style="height:6px;border-radius:10px;background:#EDE8DF;">
                            <div style="height:100%;width:<?= $pct ?>%;background:<?= $col ?>;border-radius:10px;"></div>
                          </div>
                        </div>
                      <?php endforeach; else: ?>
                      <div style="text-align:center; padding:40px 0; color:var(--muted);">No revenue recorded yet this
                        year.</div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>


        <!-- ACCORDION 3: WELFARE ACCOUNTING -->
        <div class="accordion-item">
          <div class="accordion-header" onclick="toggleAccordion(this)">
            <h2><i class="ph ph-receipt"></i> Welfare Accounting</h2>
            <i class="ph ph-caret-down"></i>
          </div>
          <div class="accordion-body">

            <div class="grid-2" style="gap:24px;">
              <!-- 1. Statement of Financial Performance -->
              <div class="card" style="margin:0;">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                  <h3 style="margin:0;">Financial Performance</h3>
                  <div style="display:flex; gap:8px;">
                    <a href="export_welfare.php?report=performance&month=<?= $filterMonth ?>"
                      class="btn btn-outline btn-sm"><i class="ph ph-file-xls"></i> Excel</a>
                    <button class="btn btn-outline btn-sm"
                      onclick="exportPDF('print-performance', '')"><i
                        class="ph ph-file-pdf"></i> PDF</button>
                  </div>
                </div>
                <div class="card-body" style="overflow-x:auto;">
                  <div id="print-performance">
                    <div style="text-align:center; margin-bottom:15px;">
                      <h3 style="margin:0; font-size:16px; font-weight:bold;">ADOM FIE CCR COMMUNITY WELFARE</h3>
                      <h4 style="margin:5px 0 0 0; font-size:14px; font-weight:normal;">Statement of Financial Performance (Income & Expenditure) For the period ended <?= $periodEndDate ?></h4>
                    </div>
                    <table class="report-table" id="table-performance"
                      style="width:100%; border-collapse:collapse; font-size:13px;">
                      <thead>

                      <tr>
                        <th
                          style="border:1px solid #000; padding:6px; background:#f9f9f9; text-align:left; font-weight:bold;">
                          Income</th>
                        <th
                          style="border:1px solid #000; padding:6px; background:#f9f9f9; text-align:right; font-weight:bold;">
                          Ghc</th>
                        <th
                          style="border:1px solid #000; padding:6px; background:#f9f9f9; text-align:right; font-weight:bold;">
                          Ghc</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Subscription for <?= $currentYear ?></td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                          <?= number_format($subIncome, 2) ?></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Other Income</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                          <?= number_format($othIncome, 2) ?></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Total Income</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($totalIncome, 2) ?></td>
                      </tr>
                      <tr>
                        <td colspan="3" style="border:1px solid #000; padding:6px; height:20px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; text-decoration:underline;">Expenses</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Benefits to Members</td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                          <?= number_format($benExpense, 2) ?></td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Momo Charges</td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                          <?= number_format($momoExpense, 2) ?></td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Total Expenses</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                          <?= number_format($totalExpense, 2) ?></td>
                      </tr>
                      <tr>
                        <td colspan="3" style="border:1px solid #000; padding:6px; height:20px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Surplus</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($surplus, 2) ?></td>
                      </tr>
                    </tbody>
                  </table>
                  </div>
                </div>
              </div>

              <!-- 2. Statement of Cash Flows -->
              <div class="card" style="margin:0;">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                  <h3 style="margin:0;">Statement of Cash Flows</h3>
                  <div style="display:flex; gap:8px;">
                    <a href="export_welfare.php?report=cashflow&month=<?= $filterMonth ?>"
                      class="btn btn-outline btn-sm"><i class="ph ph-file-xls"></i> Excel</a>
                    <button class="btn btn-outline btn-sm"
                      onclick="exportPDF('print-cashflow', '')"><i class="ph ph-file-pdf"></i>
                      PDF</button>
                  </div>
                </div>
                <div class="card-body" style="overflow-x:auto;">
                  <div id="print-cashflow">
                    <div style="text-align:center; margin-bottom:15px;">
                      <h3 style="margin:0; font-size:16px; font-weight:bold;">ADOM FIE CCR COMMUNITY WELFARE</h3>
                      <h4 style="margin:5px 0 0 0; font-size:14px; font-weight:normal;">Statement of Cash Flows For the period ended <?= $periodEndDate ?></h4>
                    </div>
                    <table class="report-table" id="table-cashflow"
                      style="width:100%; border-collapse:collapse; font-size:13px;">

                    <tbody>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; text-decoration:underline;">Operating Activities
                        </td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">Ghc</td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Cash received from Members</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                          <?= number_format($cashReceived, 2) ?></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Cash paid for Benefits</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                          <?= number_format($cashPaid, 2) ?></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Net Cash from Operating
                          Activities</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($netOperating, 2) ?></td>
                      </tr>
                      <tr>
                        <td colspan="3" style="border:1px solid #000; padding:6px; height:20px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; text-decoration:underline;">Investing Activities
                        </td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Bank Interest</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                          <?= number_format($investing, 2) ?></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Net Cash from Investing
                          Activities</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($investing, 2) ?></td>
                      </tr>
                      <tr>
                        <td colspan="3" style="border:1px solid #000; padding:6px; height:20px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; text-decoration:underline;">Financing Activities
                        </td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Proceeds from Loans</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">-</td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Loan Repayments</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">-</td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Net Cash from Financing
                          Activities</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">-</td>
                      </tr>
                      <tr>
                        <td colspan="3" style="border:1px solid #000; padding:6px; height:20px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Net Increase / (Decrease) in Cash</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                          <?= number_format($netIncrease, 2) ?></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Cash at Bank (Opening)</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                          <?= number_format($openBank, 2) ?></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Cash at Bank (Closing)</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($closeBank, 2) ?></td>
                      </tr>
                    </tbody>
                  </table>
                  </div>
                </div>
              </div>

              <!-- 3. Statement of Financial Position -->
              <div class="card" style="margin:0;">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                  <h3 style="margin:0;">Financial Position</h3>
                  <div style="display:flex; gap:8px;">
                    <a href="export_welfare.php?report=position&month=<?= $filterMonth ?>"
                      class="btn btn-outline btn-sm"><i class="ph ph-file-xls"></i> Excel</a>
                    <button class="btn btn-outline btn-sm"
                      onclick="exportPDF('print-position', '')"><i
                        class="ph ph-file-pdf"></i> PDF</button>
                  </div>
                </div>
                <div class="card-body" style="overflow-x:auto;">
                  <div id="print-position">
                    <div style="text-align:center; margin-bottom:15px;">
                      <h3 style="margin:0; font-size:16px; font-weight:bold;">ADOM FIE CCR COMMUNITY WELFARE</h3>
                      <h4 style="margin:5px 0 0 0; font-size:14px; font-weight:normal;">Statement of Financial Position As At <?= $periodEndDate ?></h4>
                    </div>
                    <table class="report-table" id="table-position"
                      style="width:100%; border-collapse:collapse; font-size:13px;">

                    <tbody>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Assets</td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">Ghc</td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">Ghc</td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; text-decoration:underline;">Current Assets</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Cash at Bank</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                          <?= number_format($cashAtBank, 2) ?></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Cash on Hand</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                          <?= number_format($cashOnHand, 2) ?></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Accounts Receivable</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                          <?= number_format($acctRec, 2) ?></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($currAssets, 2) ?></td>
                      </tr>
                      <tr>
                        <td colspan="3" style="border:1px solid #000; padding:6px; height:20px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; text-decoration:underline;">Non-Current Assets
                        </td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Land & Buildings</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">-</td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Furniture & Equipment</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">-</td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Vehicles</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">-</td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Total Assets</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($currAssets, 2) ?></td>
                      </tr>
                      <tr>
                        <td colspan="3" style="border:1px solid #000; padding:6px; height:20px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Liabilities</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; text-decoration:underline;">Current Liabilities
                        </td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Accounts Payable</td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                          <?= number_format($acctPay, 2) ?></td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Total Liabilities</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                          <?= number_format($acctPay, 2) ?></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($acctPay, 2) ?></td>
                      </tr>
                      <tr>
                        <td colspan="3" style="border:1px solid #000; padding:6px; height:20px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Net Assets / Equity (Fund
                          Balance)</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Surplus</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                          <?= number_format($surplus, 2) ?></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px;">Accumulated Fund</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                          <?= number_format($accumFund, 2) ?></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Total Net Assets</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($accumFund + $surplus, 2) ?></td>
                      </tr>
                    </tbody>
                  </table>
                  </div>
                </div>
              </div>

              <!-- 4. Trial Balance -->
              <div class="card" style="margin:0;">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                  <h3 style="margin:0;">Trial Balance</h3>
                  <div style="display:flex; gap:8px;">
                    <a href="export_welfare.php?report=trialbalance&month=<?= $filterMonth ?>"
                      class="btn btn-outline btn-sm"><i class="ph ph-file-xls"></i> Excel</a>
                    <button class="btn btn-outline btn-sm" onclick="exportPDF('print-trialbalance', '')"><i
                        class="ph ph-file-pdf"></i> PDF</button>
                  </div>
                </div>
                <div class="card-body" style="overflow-x:auto;">
                  <div id="print-trialbalance">
                    <div style="text-align:center; margin-bottom:15px;">
                      <h3 style="margin:0; font-size:16px; font-weight:bold;">ADOM FIE CCR COMMUNITY WELFARE</h3>
                      <h4 style="margin:5px 0 0 0; font-size:14px; font-weight:normal;">Trial Balance As At <?= $periodEndDate ?></h4>
                    </div>
                    <table class="report-table" id="table-trialbalance"
                      style="width:100%; border-collapse:collapse; font-size:13px;">
                      <thead>
                      <tr>
                        <th
                          style="border:1px solid #000; padding:6px; background:#f9f9f9; text-align:center; font-weight:bold;">
                          Particulars</th>
                        <th
                          style="border:1px solid #000; padding:6px; background:#f9f9f9; text-align:center; font-weight:bold;">
                          Debit</th>
                        <th
                          style="border:1px solid #000; padding:6px; background:#f9f9f9; text-align:center; font-weight:bold;">
                          Credit</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Capital Account</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($accumFund, 2) ?></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Current Assets</td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($currAssets, 2) ?></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($acctPay, 2) ?></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Direct Income</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($subIncome, 2) ?></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Direct Expenses</td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($benExpense, 2) ?></td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Indirect Incomes</td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($othIncome, 2) ?></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold;">Indirect Expenses</td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($momoExpense, 2) ?></td>
                        <td style="border:1px solid #000; padding:6px;"></td>
                      </tr>
                      <tr>
                        <td style="border:1px solid #000; padding:6px; font-weight:bold; text-align:center;">Grand Total
                        </td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($currAssets + $benExpense + $momoExpense, 2) ?></td>
                        <td style="border:1px solid #000; padding:6px; text-align:right; font-weight:bold;">
                          <?= number_format($accumFund + $acctPay + $subIncome + $othIncome, 2) ?></td>
                      </tr>
                    </tbody>
                  </table>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- ACCORDION 4: SYSTEMS & OPERATIONS -->
        <div class="accordion-item">
          <div class="accordion-header" onclick="toggleAccordion(this)">
            <h2><i class="ph ph-hard-drives"></i> Systems & Operations</h2>
            <i class="ph ph-caret-down"></i>
          </div>
          <div class="accordion-body">
            <div class="grid-2" style="gap:24px;">

              <!-- Recent Activity -->
              <div class="card" style="margin:0;">
                <div class="card-header">
                  <h3>Recent Administrative Activity</h3>
                </div>
                <div class="card-body">
                  <div style="display:flex;flex-direction:column;">
                    <?php foreach ($recent_activity as $act): ?>
                      <div class="timeline-item" style="<?= $act['is_last'] ? 'padding-bottom:0;' : '' ?>">
                        <div class="timeline-dot"
                          style="<?= isset($act['dot_color']) ? "background:{$act['dot_color']};" : '' ?>"></div>
                        <div>
                          <div style="font-size:13px;font-weight:500;color:var(--deep2);">
                            <?= htmlspecialchars($act['title']) ?></div>
                          <div style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($act['details']) ?></div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>

              <!-- System Overview -->
              <div class="card" style="margin:0;">
                <div class="card-header">
                  <h3>System Overview</h3>
                </div>
                <div class="card-body">
                  <div class="grid-2" style="gap:16px;">
                    <div
                      style="background:var(--bg-light); border:1px solid var(--border); border-radius:8px; padding:16px; text-align:center;">
                      <i class="ph ph-users"
                        style="font-size:24px; color:var(--primary); margin-bottom:8px; display:inline-block;"></i>
                      <div style="font-size:24px; font-weight:700; color:var(--deep);"><?= $sysStats['admins'] ?></div>
                      <div style="font-size:12px; color:var(--muted);">Active Admins</div>
                    </div>
                    <div
                      style="background:var(--bg-light); border:1px solid var(--border); border-radius:8px; padding:16px; text-align:center;">
                      <i class="ph ph-list-dashes"
                        style="font-size:24px; color:var(--gold); margin-bottom:8px; display:inline-block;"></i>
                      <div style="font-size:24px; font-weight:700; color:var(--deep);"><?= $sysStats['logs'] ?></div>
                      <div style="font-size:12px; color:var(--muted);">System Logs Recorded</div>
                    </div>
                    <div
                      style="background:var(--bg-light); border:1px solid var(--border); border-radius:8px; padding:16px; text-align:center; grid-column: span 2;">
                      <i class="ph ph-calendar-check"
                        style="font-size:24px; color:var(--deep3); margin-bottom:8px; display:inline-block;"></i>
                      <div style="font-size:24px; font-weight:700; color:var(--deep);"><?= $sysStats['events'] ?></div>
                      <div style="font-size:12px; color:var(--muted);">Total Events Created</div>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

      </div>
    </div>

  </main>

  <script src="assets/js/main.js"></script>
  <script>
    function toggleAccordion(headerEl) {
      const item = headerEl.closest('.accordion-item');
      item.classList.toggle('active');
    }

    function exportPDF(elementId, title) {
      const elHtml = document.getElementById(elementId).outerHTML;
      const printWindow = window.open('', '', 'width=800,height=600');

      let styles = '';
      document.querySelectorAll('style, link[rel="stylesheet"]').forEach(s => {
        styles += s.outerHTML;
      });

      printWindow.document.write('<html><head><title>' + title + '</title>');
      printWindow.document.write(styles);
      printWindow.document.write('<style>body{padding:20px; background:#fff;} table{width:100%;border-collapse:collapse;font-size:12px;} th,td{border:1px solid #000;padding:6px;} .card{box-shadow:none;border:none;} .card-header button, .card-header a{display:none;}</style>');
      printWindow.document.write('</head><body>');
      if (title) {
        printWindow.document.write('<h2>' + title + '</h2>');
      }
      printWindow.document.write(elHtml);
      printWindow.document.write('</body></html>');
      printWindow.document.close();
      printWindow.focus();
      setTimeout(function () {
        printWindow.print();
        printWindow.close();
      }, 500);
    }

    function updateReportFilter() {
      const m = document.getElementById('reportMonthSelect').value;
      const y = document.getElementById('reportYearSelect').value;
      window.location.href = `reports.php?month=${y}-${m}`;
    }
  </script>
</body>

</html>