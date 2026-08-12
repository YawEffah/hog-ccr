<?php
/**
 * Welfare Member Details Page
 * — Monthly filtering, 12-month compliance calendar, printable statement
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

$pageTitle  = 'Welfare Member Details';
$activePage = 'welfare';

$welfare_id = $_GET['id'] ?? null;
if (!$welfare_id) {
    header('Location: welfare.php');
    exit;
}

$db = getDB();

$successMsg = flash('success');
$errorMsg   = flash('error');

// ── Filter params ────────────────────────────────────────────────────────────
$filterYear  = (int)($_GET['year']  ?? date('Y'));
$filterMonth = $_GET['month'] ?? 'all';  // 'all' or '01'–'12'

// Validate
if ($filterYear < 2000 || $filterYear > (int)date('Y') + 1) $filterYear = (int)date('Y');
if ($filterMonth !== 'all' && !preg_match('/^(0[1-9]|1[0-2])$/', $filterMonth)) $filterMonth = 'all';

// ── Fetch member details ──────────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT wm.*, m.first_name, m.last_name, m.member_code, m.phone, m.email,
           (SELECT SUM(amount) FROM welfare_contributions WHERE welfare_id = wm.id) as total_paid,
           (SELECT payment_date FROM welfare_contributions WHERE welfare_id = wm.id ORDER BY payment_date DESC LIMIT 1) as last_payment_date
    FROM welfare_members wm
    JOIN members m ON wm.member_id = m.id
    WHERE wm.id = ?
");
$stmt->execute([$welfare_id]);
$member = $stmt->fetch();
if (!$member) {
    header('Location: welfare.php');
    exit;
}

// Calculate dynamic expected months and arrears
$monthlyAmount = (float)$member['monthly_amount'];
$totalPaid     = (float)$member['total_paid'];

// Calculate differences in months since enrollment
$enrolTime    = strtotime($member['enrol_date']);
$enrolYear    = (int)date('Y', $enrolTime);
$enrolMonth   = (int)date('m', $enrolTime);
$currentYear  = (int)date('Y');
$currentMonthNum = (int)date('m');

$diffMonths = (($currentYear - $enrolYear) * 12) + ($currentMonthNum - $enrolMonth) + 1;
$expectedMonths = max(0, $diffMonths);
$expectedAmount = $expectedMonths * $monthlyAmount;

$arrears = max(0.00, $expectedAmount - $totalPaid);

$rawName  = $member['first_name'] . ' ' . $member['last_name'];
$name     = htmlspecialchars($rawName);
$initials = strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1));
$currentMonthStatus = ($arrears <= 0) ? 'Up to date' : 'Arrears';
$statusBadge        = $currentMonthStatus === 'Up to date' ? 'badge-welfare' : 'badge-red';

// ── Fetch filtered contributions ─────────────────────────────────────────────
if ($filterMonth === 'all') {
    $cStmt = $db->prepare("
        SELECT * FROM welfare_contributions
        WHERE welfare_id = ? AND YEAR(payment_date) = ?
        ORDER BY payment_date DESC, created_at DESC
    ");
    $cStmt->execute([$welfare_id, $filterYear]);
} else {
    $cStmt = $db->prepare("
        SELECT * FROM welfare_contributions
        WHERE welfare_id = ? AND DATE_FORMAT(payment_date, '%Y-%m') = ?
        ORDER BY payment_date DESC, created_at DESC
    ");
    $cStmt->execute([$welfare_id, $filterYear . '-' . $filterMonth]);
}
$contributions = $cStmt->fetchAll();

// ── Filtered period total ─────────────────────────────────────────────────────
$filteredTotal = array_sum(array_column($contributions, 'amount'));

// ── All-time payment method breakdown ────────────────────────────────────────
$allStmt = $db->prepare("SELECT payment_method, amount FROM welfare_contributions WHERE welfare_id = ?");
$allStmt->execute([$welfare_id]);
$allContribs = $allStmt->fetchAll();
$methods = [];
foreach ($allContribs as $c) {
    $methods[$c['payment_method']] = ($methods[$c['payment_method']] ?? 0) + (float)$c['amount'];
}
$total_c = array_sum($methods);

// ── 12-month compliance calendar for the selected year ───────────────────────
$monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$paidMonths = [];

// Enrolment month — months before this are "N/A"
$enrolYear  = (int)date('Y', strtotime($member['enrol_date']));
$enrolMonth = (int)date('n', strtotime($member['enrol_date']));

// Available years for selector
$thisYear = (int)date('Y');

// Month labels for dropdowns
$monthList = [
    '01'=>'January','02'=>'February','03'=>'March','04'=>'April',
    '05'=>'May','06'=>'June','07'=>'July','08'=>'August',
    '09'=>'September','10'=>'October','11'=>'November','12'=>'December'
];

// Label for the print statement period
if ($filterMonth === 'all') {
    $periodLabel = "Annual Statement — {$filterYear}";
} else {
    $periodLabel = "Monthly Statement — " . $monthList[$filterMonth] . " {$filterYear}";
}
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once 'includes/head.php'; ?>

<style>
/* ── Compliance Calendar ───────────────────────────────────────────── */
.compliance-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 6px;
  margin-top: 12px;
}
.cal-cell {
  border-radius: 8px;
  padding: 8px 6px;
  text-align: center;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.15s, box-shadow 0.15s;
  border: 2px solid transparent;
  text-decoration: none;
}
.cal-cell:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.1); }
.cal-paid   { background: #CCFBF1; color: #0F766E; border-color: #5EEAD4; }
.cal-missed { background: #FEE2E2; color: #B91C1C; border-color: #FCA5A5; }
.cal-na     { background: #F1F5F9; color: #94A3B8; cursor: default; }
.cal-active { outline: 3px solid #0D9488; outline-offset: 2px; }

/* ── Print styles ──────────────────────────────────────────────────── */
@media print {
  body > * { display: none !important; }
  #print-statement { display: block !important; }
}
@media screen {
  #print-statement { display: none; }
}
#print-statement {
  font-family: 'Inter', sans-serif;
  color: #1E293B;
  padding: 32px 40px;
  max-width: 760px;
  margin: 0 auto;
}
.print-header { text-align: center; border-bottom: 3px solid #0D9488; padding-bottom: 16px; margin-bottom: 24px; }
.print-church { font-size: 22px; font-weight: 800; color: #0D9488; letter-spacing: -0.5px; }
.print-sub    { font-size: 12px; color: #0F766E; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; }
.print-title  { font-size: 15px; font-weight: 700; color: #1E293B; margin-top: 10px; }
.print-meta   { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px; padding: 16px; background: #F8FAFC; border-radius: 8px; border: 1px solid #E2E8F0; }
.print-meta-item .lbl { font-size: 10px; text-transform: uppercase; color: #94A3B8; font-weight: 600; letter-spacing: 0.5px; }
.print-meta-item .val { font-size: 13px; font-weight: 600; color: #1E293B; margin-top: 2px; }
.print-table  { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 20px; }
.print-table th { background: #F1F5F9; padding: 8px 10px; text-align: left; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748B; border-bottom: 2px solid #E2E8F0; }
.print-table td { padding: 8px 10px; border-bottom: 1px solid #F1F5F9; }
.print-table tr:last-child td { border-bottom: none; }
.print-total  { display: flex; justify-content: flex-end; margin-bottom: 32px; }
.print-total-box { background: #F0FDFA; border: 1px solid #CCFBF1; border-radius: 8px; padding: 12px 20px; text-align: right; }
.print-total-box .lbl { font-size: 11px; color: #0F766E; font-weight: 600; }
.print-total-box .val { font-size: 20px; font-weight: 800; color: #0D9488; }
.print-footer { text-align: center; font-size: 11px; color: #94A3B8; border-top: 1px solid #E2E8F0; padding-top: 14px; }
</style>

<body>
  <?php require_once 'includes/sidebar.php'; ?>
  <main id="main">
    <div class="page" id="page-welfare-details">
      <div class="topbar">
        <div style="display:flex;align-items:center;">
          <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="ph ph-list"></i>
          </button>
          <div class="topbar-title">Member Details</div>
        </div>
        <div class="topbar-actions">
          <a href="welfare.php" class="btn btn-outline btn-sm">
            <i class="ph ph-arrow-left"></i> Back to Welfare
          </a>
          <button class="btn btn-outline btn-sm" onclick="window.print()" id="printBtn">
            <i class="ph ph-printer"></i> Print Statement
          </button>
          <button class="btn btn-primary btn-sm" onclick="openRecordPaymentFor('<?= $member['id'] ?>', '<?= addslashes($name) ?>')">
            <i class="ph ph-plus"></i> Record Payment
          </button>
        </div>
      </div>

      <div class="content">
        <?php renderToastAlerts($successMsg, $errorMsg); ?>

        <!-- Top two-col cards -->
        <div class="grid-2" style="gap:24px;margin-bottom:24px;">

          <!-- Profile Card -->
          <div class="card">
            <div class="card-body">
              <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;">
                <div class="avatar" style="width:64px;height:64px;font-size:24px;background:var(--gold-pale);color:var(--gold);">
                  <?= $initials ?>
                </div>
                <div>
                  <h2 style="margin:0;font-size:20px;"><?= $name ?></h2>
                  <div style="color:var(--muted);font-size:13px;"><?= htmlspecialchars($member['member_code']) ?> · Welfare Member</div>
                  <div style="margin-top:6px;">
                    <span class="badge <?= $statusBadge ?>"><?= $currentMonthStatus ?> <span style="font-weight:400;font-size:10px;">(this month)</span></span>
                  </div>
                </div>
              </div>
              <div class="grid-2" style="gap:16px;">
                <div>
                  <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Phone</div>
                  <div style="font-weight:500;"><?= htmlspecialchars($member['phone'] ?? '—') ?></div>
                </div>
                <div>
                  <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Email</div>
                  <div style="font-weight:500;"><?= htmlspecialchars($member['email'] ?? '—') ?></div>
                </div>
                <div>
                  <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Enrolled</div>
                  <div style="font-weight:500;"><?= date('M Y', strtotime($member['enrol_date'])) ?></div>
                </div>
                <div>
                  <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Family Group</div>
                  <div style="font-weight:500;"><span class="badge badge-gray"><?= htmlspecialchars($member['family_group'] ?? '—') ?></span></div>
                </div>
                <div>
                  <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Total Contributed (All Time)</div>
                  <div style="font-weight:700;color:#0D9488;font-size:18px;">GH₵ <?= number_format((float)$member['total_paid'], 2) ?></div>
                </div>
                <div>
                  <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Last Payment</div>
                  <div style="font-weight:500;"><?= $member['last_payment_date'] ? date('M j, Y', strtotime($member['last_payment_date'])) : 'Never' ?></div>
                </div>
                <div>
                  <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Current Arrears</div>
                  <div style="font-weight:700;color:#DC2626;font-size:18px;">GH₵ <?= number_format($arrears, 2) ?></div>
                </div>
                <div>
                  <div style="font-size:11px;text-transform:uppercase;color:var(--muted);margin-bottom:4px;">Monthly Contribution Target</div>
                  <div style="font-weight:500;font-size:16px;">GH₵ <?= number_format($monthlyAmount, 2) ?></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Financial Summary + Compliance Calendar -->
          <div class="card">
            <div class="card-body">

              <!-- Year selector for calendar -->
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <h4 style="margin:0;font-size:13px;font-weight:700;color:var(--deep);">
                  <i class="ph ph-calendar-check" style="color:#0D9488;margin-right:4px;"></i>
                  Payment Compliance — <?= $filterYear ?>
                </h4>
                <form method="GET" style="display:flex;gap:6px;align-items:center;">
                  <input type="hidden" name="id" value="<?= $welfare_id ?>">
                  <input type="hidden" name="month" value="<?= htmlspecialchars($filterMonth) ?>">
                  <select name="year" class="form-control" style="width:90px;padding:5px 10px;font-size:12px;" onchange="this.form.submit()">
                    <?php for ($y = $thisYear; $y >= 2021; $y--): ?>
                      <option value="<?= $y ?>" <?= $filterYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                  </select>
                </form>
              </div>

              <!-- 12-month grid -->
              <div class="compliance-grid">
                <?php 
                  for ($m = 1; $m <= 12; $m++):

                  $mm = str_pad($m, 2, '0', STR_PAD_LEFT);
                  $isBeforeEnrol = ($filterYear < $enrolYear) || ($filterYear === $enrolYear && $m < $enrolMonth);
                  
                  // Calculate expected amount up to this specific calendar month
                  $gridDiffMonths = (($filterYear - $enrolYear) * 12) + ($m - $enrolMonth) + 1;
                  $gridExpectedMonths = max(0, $gridDiffMonths);
                  $gridExpectedAmount = $gridExpectedMonths * $monthlyAmount;
                  
                  // Are we fully covered up to this month?
                  $isPaid = (!$isBeforeEnrol && $totalPaid >= $gridExpectedAmount);
                  
                  $isFuture = ($filterYear === $thisYear && $m > (int)date('n')) || $filterYear > $thisYear;

                  if ($isBeforeEnrol):
                    $cls = 'cal-na'; $title = 'Not enrolled yet'; $icon = '—';
                  elseif ($isPaid):
                    $cls = 'cal-paid'; $title = 'Covered'; $icon = '✓';
                  elseif ($isFuture):
                    $cls = 'cal-na'; $title = 'Not yet due'; $icon = '·';
                  else:
                    $cls = 'cal-missed'; $title = 'Arrears'; $icon = '✗';
                  endif;

                  $isActive = (!$isBeforeEnrol && !$isFuture && $filterMonth === $mm) ? ' cal-active' : '';
                  $href = "welfare_member_details.php?id={$welfare_id}&year={$filterYear}&month={$mm}";
                ?>
                  <?php if ($isBeforeEnrol || $isFuture): ?>
                    <div class="cal-cell <?= $cls . $isActive ?>" title="<?= $title ?>">
                      <div><?= $monthNames[$m-1] ?></div>
                      <div style="font-size:14px;margin-top:2px;"><?= $icon ?></div>
                    </div>
                  <?php else: ?>
                    <a href="<?= $href ?>" class="cal-cell <?= $cls . $isActive ?>" title="<?= $title ?>">
                      <div><?= $monthNames[$m-1] ?></div>
                      <div style="font-size:14px;margin-top:2px;"><?= $icon ?></div>
                    </a>
                  <?php endif; ?>
                <?php endfor; ?>
              </div>

              <div style="display:flex;gap:12px;margin-top:12px;font-size:11px;color:var(--muted);">
                <span><span style="color:#0F766E;font-weight:700;">✓</span> Paid</span>
                <span><span style="color:#B91C1C;font-weight:700;">✗</span> Missed</span>
                <span><span style="color:#94A3B8;font-weight:700;">—</span> N/A</span>
              </div>

              <!-- Payment method breakdown (all time) -->
              <?php if (!empty($methods)): ?>
              <div style="margin-top:20px;padding-top:16px;border-top:1px solid #EDE8DF;">
                <div style="font-size:11px;text-transform:uppercase;color:var(--muted);font-weight:700;letter-spacing:0.5px;margin-bottom:10px;">All-Time Payment Methods</div>
                <div style="display:flex;flex-direction:column;gap:10px;">
                  <?php
                  $method_colors = ['MoMo'=>'#0D9488','Cash'=>'var(--gold)','Bank Transfer'=>'var(--deep)','Cheque'=>'var(--deep3)'];
                  foreach ($methods as $method => $sum):
                    $pct = $total_c > 0 ? round(($sum / $total_c) * 100) : 0;
                  ?>
                  <div>
                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
                      <span style="font-weight:500;"><?= $method ?></span>
                      <span style="color:var(--mid);">GH₵ <?= number_format($sum, 2) ?> <span style="color:var(--muted);font-size:10px;">(<?= $pct ?>%)</span></span>
                    </div>
                    <div style="height:6px;border-radius:10px;background:#EDE8DF;overflow:hidden;">
                      <div style="height:100%;width:<?= $pct ?>%;background:<?= $method_colors[$method] ?? '#0D9488' ?>;border-radius:10px;"></div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Contribution History Table -->
        <div class="card">
          <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <div>
              <h3 style="margin:0;">Contribution History</h3>
              <div style="font-size:12px;color:var(--muted);margin-top:3px;">
                <?php if ($filterMonth === 'all'): ?>
                  Showing all <?= count($contributions) ?> record(s) for <?= $filterYear ?>
                <?php else: ?>
                  Showing <?= count($contributions) ?> record(s) for <?= $monthList[$filterMonth] . ' ' . $filterYear ?>
                <?php endif; ?>
                — <strong style="color:#0D9488;">GH₵ <?= number_format($filteredTotal, 2) ?></strong>
              </div>
            </div>

            <!-- Year + Month filter form -->
            <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
              <input type="hidden" name="id" value="<?= $welfare_id ?>">
              <select name="year" class="form-control" style="width:90px;padding:8px 12px;font-size:13px;">
                <?php for ($y = $thisYear; $y >= 2021; $y--): ?>
                  <option value="<?= $y ?>" <?= $filterYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
              </select>
              <select name="month" class="form-control" style="width:140px;padding:8px 12px;font-size:13px;">
                <option value="all" <?= $filterMonth === 'all' ? 'selected' : '' ?>>All Months</option>
                <?php foreach ($monthList as $num => $lbl): ?>
                  <option value="<?= $num ?>" <?= $filterMonth === $num ? 'selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-primary btn-sm"><i class="ph ph-funnel"></i> Filter</button>
              <a href="welfare_member_details.php?id=<?= $welfare_id ?>" class="btn btn-outline btn-sm">Reset</a>
            </form>
          </div>

          <div class="table-responsive">
            <table>
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Amount</th>
                  <th>Method</th>
                  <th>Reference</th>
                  <th>Notif. Sent</th>
                  <th style="text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($contributions)): ?>
                  <tr>
                    <td colspan="6" style="text-align:center;padding:48px;color:var(--muted);">
                      <div style="font-size:36px;margin-bottom:10px;">📭</div>
                      <div style="font-weight:600;">No contributions found</div>
                      <div style="font-size:12px;margin-top:4px;">
                        <?= $filterMonth === 'all' ? "No records for {$filterYear}." : "No records for {$monthList[$filterMonth]} {$filterYear}." ?>
                      </div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($contributions as $c): ?>
                  <?php
                    $receiptObj = [
                      'id'        => $c['id'],
                      'date'      => date('M j, Y', strtotime($c['payment_date'])),
                      'member'    => $rawName,
                      'amount'    => number_format($c['amount'], 2),
                      'method'    => $c['payment_method'],
                      'reference' => $c['reference_no'] ?: '—'
                    ];
                  ?>
                  <tr>
                    <td><div style="font-weight:500;"><?= date('M j, Y', strtotime($c['payment_date'])) ?></div></td>
                    <td><div style="font-weight:600;color:#0D9488;">GH₵ <?= number_format($c['amount'], 2) ?></div></td>
                    <td><span class="badge badge-gray"><?= htmlspecialchars($c['payment_method']) ?></span></td>
                    <td style="font-size:13px;color:var(--muted);"><?= htmlspecialchars($c['reference_no'] ?: '—') ?></td>
                    <td>
                      <?php if ($c['notif_sent']): ?>
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;color:#0D9488;">
                          <i class="ph ph-check-circle" style="font-size:16px;"></i> Sent
                        </span>
                      <?php else: ?>
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;color:var(--muted);">
                          <i class="ph ph-x-circle" style="font-size:16px;"></i> Not sent
                        </span>
                      <?php endif; ?>
                    </td>
                    <td style="text-align:right;">
                      <div style="display:flex;justify-content:flex-end;gap:6px;">
                        <button class="btn btn-outline btn-sm" title="View Receipt"
                          onclick='openWelfareReceiptModal(<?= htmlspecialchars(json_encode($receiptObj), ENT_QUOTES, "UTF-8") ?>)'>
                          <i class="ph ph-receipt"></i>
                        </button>
                        <button class="btn btn-danger-soft btn-sm" title="Delete Contribution"
                          onclick="confirmDeleteContrib('<?= $c['id'] ?>')">
                          <i class="ph ph-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div><!-- /content -->
    </div><!-- /page -->
  </main>

  <!-- ═══════════════════════════════════════════════════════
       PRINT-ONLY STATEMENT (hidden on screen, visible on print)
       ═══════════════════════════════════════════════════════ -->
  <div id="print-statement">
    <div class="print-header">
      <div class="print-church">ADOM FIE CCR COMMUNITY</div>
      <div class="print-sub">Welfare Scheme</div>
      <div class="print-title"><?= htmlspecialchars($periodLabel) ?></div>
    </div>

    <!-- Member meta -->
    <div class="print-meta">
      <div class="print-meta-item">
        <div class="lbl">Member Name</div>
        <div class="val"><?= $name ?></div>
      </div>
      <div class="print-meta-item">
        <div class="lbl">Member Code</div>
        <div class="val"><?= htmlspecialchars($member['member_code']) ?></div>
      </div>
      <div class="print-meta-item">
        <div class="lbl">Family Group</div>
        <div class="val"><?= htmlspecialchars($member['family_group'] ?? '—') ?></div>
      </div>
      <div class="print-meta-item">
        <div class="lbl">Phone</div>
        <div class="val"><?= htmlspecialchars($member['phone'] ?? '—') ?></div>
      </div>
      <div class="print-meta-item">
        <div class="lbl">Enrolled</div>
        <div class="val"><?= date('M Y', strtotime($member['enrol_date'])) ?></div>
      </div>
      <div class="print-meta-item">
        <div class="lbl">Status (This Month)</div>
        <div class="val" style="color: <?= $currentMonthStatus === 'Up to date' ? '#0D9488' : '#DC2626' ?>">
          <?= $currentMonthStatus ?>
        </div>
      </div>
    </div>

    <!-- Contributions table -->
    <?php if (empty($contributions)): ?>
      <p style="text-align:center;color:#94A3B8;padding:20px 0;">No contributions recorded for this period.</p>
    <?php else: ?>
    <table class="print-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Date</th>
          <th>Amount (GH₵)</th>
          <th>Payment Method</th>
          <th>Reference</th>
        </tr>
      </thead>
      <tbody>
        <?php $row = 1; foreach ($contributions as $c): ?>
        <tr>
          <td style="color:#94A3B8;"><?= $row++ ?></td>
          <td><?= date('M j, Y', strtotime($c['payment_date'])) ?></td>
          <td style="font-weight:700;color:#0D9488;"><?= number_format($c['amount'], 2) ?></td>
          <td><?= htmlspecialchars($c['payment_method']) ?></td>
          <td style="color:#64748B;"><?= htmlspecialchars($c['reference_no'] ?: '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>

    <!-- Total box -->
    <div class="print-total">
      <div class="print-total-box">
        <div class="lbl">TOTAL FOR PERIOD</div>
        <div class="val">GH₵ <?= number_format($filteredTotal, 2) ?></div>
        <div style="font-size:10px;color:#0F766E;margin-top:4px;">(<?= count($contributions) ?> contribution<?= count($contributions) !== 1 ? 's' : '' ?>)</div>
      </div>
    </div>

    <div class="print-footer">
      <p>All-time total contributed: <strong>GH₵ <?= number_format((float)$member['total_paid'], 2) ?></strong></p>
      <p>This statement was generated on <?= date('F j, Y \a\t g:i A') ?></p>
      <p style="margin-top:8px;">"God loves a cheerful giver." — 2 Corinthians 9:7</p>
    </div>
  </div><!-- /print-statement -->

  <!-- Hidden forms -->
  <form method="POST" action="handlers/welfare_handler.php" id="deleteContribForm" style="display:none;">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="delete_contribution">
    <input type="hidden" name="contribution_id" id="deleteContribId">
    <input type="hidden" name="return_to" value="../welfare_member_details.php?id=<?= $welfare_id ?>&year=<?= $filterYear ?>&month=<?= $filterMonth ?>">
  </form>

  <?php
    $welfare_members  = [['id' => $member['id'], 'name' => $rawName, 'member_id' => $member['member_code']]];
    $nonWelfareMembers = [];
    require_once 'includes/modals/welfare_modals.php';
  ?>

  <script src="assets/js/main.js"></script>
  <script>
    function openRecordPaymentFor(wid, name) {
      const displayInput = document.getElementById('paymentMemberDisplay');
      const idInput      = document.getElementById('paymentWelfareMemberId');
      if (displayInput) displayInput.value = name;
      if (idInput)      idInput.value = wid;
      openModal('recordWelfarePaymentModal');
    }

    function confirmDeleteContrib(id) {
      showConfirmModal(
        'Delete Contribution',
        'Are you sure you want to delete this contribution record? This action cannot be undone.',
        'Delete',
        function() {
          document.getElementById('deleteContribId').value = id;
          document.getElementById('deleteContribForm').submit();
        },
        'danger'
      );
    }
  </script>
</body>
</html>
