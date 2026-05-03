<?php
/**
 * Dashboard Page
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

$db = getDB();

// ── Dashboard Statistics ─────────────────────────────────────────────────────
$total_members  = (int)$db->query("SELECT COUNT(*) FROM members")->fetchColumn();
$active_members = (int)$db->query("SELECT COUNT(*) FROM members WHERE status='Active'")->fetchColumn();

// Finance totals this month
$monthStart = date('Y-m-01');

$stmt = $db->prepare("SELECT SUM(amount) FROM finance_transactions WHERE transaction_date >= ?");
$stmt->execute([$monthStart]);
$monthFinance = (float)$stmt->fetchColumn();

// Welfare fund (total collected ever)
$welfareFund = (float)$db->query("SELECT SUM(amount) FROM welfare_contributions")->fetchColumn();

// Stats for cards
// Stats for cards
$titheStmt = $db->prepare("SELECT SUM(amount) FROM finance_transactions WHERE type='Tithe' AND transaction_date >= ?");
$titheStmt->execute([$monthStart]);
$monthlyTithe = (float)$titheStmt->fetchColumn();

// New members this month
$newMembersStmt = $db->prepare("SELECT COUNT(*) FROM members WHERE DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')");
$newMembersStmt->execute();
$newThisMonth = (int)$newMembersStmt->fetchColumn();

// Finance vs last month
$lastMonthStart = date('Y-m-01', strtotime('-1 month'));
$lastMonthEnd   = date('Y-m-t',  strtotime('-1 month'));
$lmStmt = $db->prepare("SELECT SUM(amount) FROM finance_transactions WHERE transaction_date BETWEEN ? AND ?");
$lmStmt->execute([$lastMonthStart, $lastMonthEnd]);
$lastMonthFinance = (float)$lmStmt->fetchColumn();
$financeGrowth = $lastMonthFinance > 0 ? round((($monthFinance - $lastMonthFinance) / $lastMonthFinance) * 100) : 0;

// Members in any ministry
$enrolledInMinistry = (int)$db->query("SELECT COUNT(*) FROM members WHERE ministry_id IS NOT NULL")->fetchColumn();

$stats = [
    'total_members'      => number_format($total_members),
    'active_members'     => number_format($active_members),
    'monthly_revenue'    => formatGhc($monthFinance),
    'monthly_tithe'      => number_format($monthlyTithe, 2),
    'active_ministries'  => (int)$db->query("SELECT COUNT(*) FROM ministries")->fetchColumn(),
    'welfare_fund'       => number_format($welfareFund, 2),
    'welfare_members'    => (int)$db->query("SELECT COUNT(*) FROM welfare_members")->fetchColumn(),
    'new_this_month'     => $newThisMonth,
    'finance_growth'     => $financeGrowth,
    'enrolled_ministry'  => $enrolledInMinistry,
];

// ── Recent Activity ──────────────────────────────────────────────────────────
$activityStmt = $db->query(
    "SELECT l.*, a.name as admin_name, a.initials 
     FROM activity_log l
     LEFT JOIN admins a ON l.admin_id = a.id
     ORDER BY l.created_at DESC 
     LIMIT 5"
);
$activities = $activityStmt->fetchAll();

// ── Finance Summary (Last 6 Months) ──────────────────────────────────────────
$financeSummary = [];
for ($i = 5; $i >= 0; $i--) {
    $mDate = date('Y-m-01', strtotime("-$i months"));
    $mName = date('M', strtotime($mDate));
    
    $stmt = $db->prepare("SELECT SUM(amount) FROM finance_transactions WHERE transaction_date >= ? AND transaction_date <= LAST_DAY(?)");
    $stmt->execute([$mDate, $mDate]);
    $amt = (float)$stmt->fetchColumn();
    
    $financeSummary[] = ['month' => $mName, 'amount' => number_format($amt, 2), 'height' => min(100, ($amt / 10000) * 100)];
}

// ── Upcoming Events ──────────────────────────────────────────────────────────
$eventsStmt = $db->query(
    "SELECT * FROM events 
     WHERE event_date >= CURRENT_DATE 
     ORDER BY event_date ASC, event_time ASC 
     LIMIT 4"
);
$rawEvents = $eventsStmt->fetchAll();

$eventBadges = [
    'Weekly'  => 'badge-blue',
    'Monthly' => 'badge-red',
    'Annual'  => 'badge-purple',
    'Special' => 'badge-green'
];

$upcoming_events = array_map(function($e) use ($eventBadges) {
    return [
        'day'         => date('j', strtotime($e['event_date'])),
        'month'       => date('M', strtotime($e['event_date'])),
        'title'       => $e['title'],
        'time'        => $e['event_time'] ? date('g:ia', strtotime($e['event_time'])) : '—',
        'venue'       => $e['venue'] ?: 'TBA',
        'badge_color' => $eventBadges[$e['type']] ?? 'badge-blue',
        'badge_label' => $e['type']
    ];
}, $rawEvents);

// ── Attendance Trend (Monthly & Weekly) ──────────────────────────────────────
$attendance_trend_monthly = [];
$attendance_trend_weekly = [];
$max_attendance = 0;

// Monthly Trend (Last 6 Months)
for ($i = 5; $i >= 0; $i--) {
    $mDate = date('Y-m-01', strtotime("-$i months"));
    $mName = date('M', strtotime($mDate));
    
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
    $sun_count = (int)$row['sunday_count'];
    $oth_count = (int)$row['other_count'];
    
    $attendance_trend_monthly[] = [
        'label' => $mName,
        'sunday_count' => $sun_count,
        'other_count' => $oth_count
    ];
    if ($sun_count > $max_attendance) $max_attendance = $sun_count;
    if ($oth_count > $max_attendance) $max_attendance = $oth_count;
}

// Weekly Trend (Last 6 Weeks)
for ($i = 5; $i >= 0; $i--) {
    // $i weeks ago, from the start of the week (Monday)
    $weekStart = strtotime("-$i weeks", strtotime('monday this week'));
    $weekEnd = strtotime("+6 days", $weekStart);
    $wLabel = date('M d', $weekStart);
    
    $attStmt = $db->prepare(
        "SELECT 
            SUM(CASE WHEN s.session_type = 'Sunday Service' THEN 1 ELSE 0 END) as sunday_count,
            SUM(CASE WHEN s.session_type != 'Sunday Service' THEN 1 ELSE 0 END) as other_count
         FROM attendance_records ar
         JOIN attendance_sessions s ON ar.session_id = s.id
         WHERE s.session_date BETWEEN ? AND ? AND ar.status = 'Present'"
    );
    $attStmt->execute([date('Y-m-d', $weekStart), date('Y-m-d', $weekEnd)]);
    $row = $attStmt->fetch();
    $sun_count = (int)$row['sunday_count'];
    $oth_count = (int)$row['other_count'];
    
    $attendance_trend_weekly[] = [
        'label' => $wLabel,
        'sunday_count' => $sun_count,
        'other_count' => $oth_count
    ];
    if ($sun_count > $max_attendance) $max_attendance = $sun_count;
    if ($oth_count > $max_attendance) $max_attendance = $oth_count;
}

// Calculate relative heights for the chart (max height = 80px)
$max_attendance = max($max_attendance, 1); // prevent division by zero
foreach ($attendance_trend_monthly as &$at) {
    $at['sun_height'] = $at['sunday_count'] > 0 ? max(4, round(($at['sunday_count'] / $max_attendance) * 80)) : 0;
    $at['oth_height'] = $at['other_count'] > 0 ? max(4, round(($at['other_count'] / $max_attendance) * 80)) : 0;
}
unset($at);

foreach ($attendance_trend_weekly as &$at) {
    $at['sun_height'] = $at['sunday_count'] > 0 ? max(4, round(($at['sunday_count'] / $max_attendance) * 80)) : 0;
    $at['oth_height'] = $at['other_count'] > 0 ? max(4, round(($at['other_count'] / $max_attendance) * 80)) : 0;
}
unset($at);

// ── Recent Members ───────────────────────────────────────────────────────────
$memStmt = $db->query(
    "SELECT m.*, min.name as ministry_name, min.bg_color, min.icon
     FROM members m
     LEFT JOIN ministries min ON m.ministry_id = min.id
     ORDER BY m.created_at DESC 
     LIMIT 4"
);
$rawMembers = $memStmt->fetchAll();

$recent_members = array_map(function($m) {
    $initials = strtoupper(substr($m['first_name'], 0, 1) . substr($m['last_name'], 0, 1));
    $statusBadges = [
        'Active'   => 'badge-green',
        'Inactive' => 'badge-gray',
        'Visitor'  => 'badge-yellow'
    ];
    return [
        'initials'     => $initials,
        'name'         => $m['first_name'] . ' ' . $m['last_name'],
        'ministry'     => $m['ministry_name'] ?: 'No Ministry',
        'status'       => $m['status'],
        'status_badge' => $statusBadges[$m['status']] ?? 'badge-gray',
        'joined'       => date('M j', strtotime($m['created_at'])),
        'avatar_color' => $m['bg_color'] ?: '#F3F4F6',
        'text_color'   => 'var(--deep2)'
    ];
}, $rawMembers);

// ── Finance Summary (Current Month Breakdown) ───────────────────────────────
$breakdownStmt = $db->prepare(
    "SELECT type, SUM(amount) as total 
     FROM finance_transactions 
     WHERE transaction_date >= ?
     GROUP BY type"
);
$breakdownStmt->execute([$monthStart]);
$rawBreakdown = $breakdownStmt->fetchAll();

$typeTotals = [];
$totalAll = 0;
foreach ($rawBreakdown as $b) {
    $typeTotals[strtolower($b['type'])] = (float)$b['total'];
    $totalAll += (float)$b['total'];
}

$targetStmt = $db->prepare("SELECT target_amount FROM finance_targets WHERE DATE_FORMAT(target_month, '%Y-%m') = ?");
$targetStmt->execute([date('Y-m')]);
$monthlyTarget = (float)$targetStmt->fetchColumn() ?: 10000;

$finance_summary = [
    'tithes'         => number_format($typeTotals['tithe'] ?? 0),
    'offerings'      => number_format($typeTotals['offering'] ?? 0),
    'donations'      => number_format($typeTotals['donation'] ?? 0),
    'pledges'        => number_format($typeTotals['pledge'] ?? 0),
    'welfare'        => number_format($typeTotals['welfare'] ?? 0),
    'total'          => number_format($totalAll),
    'target_percent' => $monthlyTarget > 0 ? min(100, round(($totalAll / $monthlyTarget) * 100)) : 0
];
?>
<!DOCTYPE html>
<html lang="en">

<?php require_once 'includes/head.php'; ?>

<body>

  <?php require_once 'includes/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main id="main">

    <div id="page-dashboard" class="page">
      <div class="topbar">
        <div style="display:flex;align-items:center;">
          <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="ph ph-list"></i>
          </button>
          <div>
            <div class="topbar-title">Good morning, <?= htmlspecialchars($currentUser['name']) ?> 👋</div>
            <div style="font-size:12px; color:var(--muted); margin-top:2px;"><?= date('l, j F Y') ?> — Week
              <?= date('W') ?></div>
          </div>
        </div>
        <div class="topbar-actions">
          <button class="btn btn-outline btn-sm" id="notifBtn" onclick="toggleNotifications()">
            <i class="ph ph-bell"></i>
            <span class="notif-dot"></span>
          </button>
          <?php include 'includes/notifications.php'; ?>
        </div>
      </div>
      <div class="content">

        <!-- Stat Cards -->
        <div class="grid-4" style="margin-bottom:24px;">
          <div class="stat-card">
            <div class="accent-bar" style="background: var(--gold);"></div>
            <div class="label">Total Members</div>
            <div class="value"><?= $stats['total_members'] ?></div>
            <div class="change" style="color:var(--success);">↑ <?= $stats['new_this_month'] ?> this month</div>
            <div class="icon-bg" style="background:var(--gold-pale);">
              <i class="ph ph-users" style="color:var(--gold); font-size: 20px;"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:#2E7D57;"></div>
            <div class="label">Monthly Tithe</div>
            <div class="value">GH₵<?= $stats['monthly_tithe'] ?></div>
            <div class="change" style="color:<?= $stats['finance_growth'] >= 0 ? 'var(--success)' : '#DC2626' ?>">
              <?= $stats['finance_growth'] >= 0 ? '↑' : '↓' ?> <?= abs($stats['finance_growth']) ?>% vs last month</div>
            <div class="icon-bg" style="background:#ECFDF5;">
              <i class="ph ph-wallet" style="color:#2E7D57; font-size: 20px;"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--deep3);"></div>
            <div class="label">Active Ministries</div>
            <div class="value"><?= $stats['active_ministries'] ?></div>
            <div class="change" style="color:var(--deep3);"><?= $stats['enrolled_ministry'] ?> members enrolled</div>
            <div class="icon-bg" style="background:#F5F3FF;">
              <i class="ph ph-heart" style="color:var(--deep3); font-size: 20px;"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:#0D9488;"></div>
            <div class="label">Welfare Fund</div>
            <div class="value">GH₵<?= $stats['welfare_fund'] ?></div>
            <div class="change" style="color:#0D9488;"><?= $stats['welfare_members'] ?> members active</div>
            <div class="icon-bg" style="background:#CCFBF1;">
              <i class="ph ph-hand-heart" style="color:#0D9488; font-size: 20px;"></i>
            </div>
          </div>
        </div>

        <div class="grid-2" style="margin-bottom:24px; gap:24px;">

          <!-- Attendance Chart -->
          <div class="card">
            <div class="card-header">
              <h3>Attendance Trend</h3>
              <div class="tabs" style="padding:2px;">
                <button class="tab active" id="tabMonthly" onclick="toggleAttendanceChart('monthly')" style="padding:5px 12px; font-size:12px;">Monthly</button>
                <button class="tab" id="tabWeekly" onclick="toggleAttendanceChart('weekly')" style="padding:5px 12px; font-size:12px;">Weekly</button>
              </div>
            </div>
            <div class="card-body">
              <div style="display:flex; gap:16px; margin-bottom:16px;">
                <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);">
                  <span
                    style="width:10px;height:10px;border-radius:2px;background:var(--gold);display:inline-block;"></span>Sunday
                  Service
                </div>
                <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);">
                  <span
                    style="width:10px;height:10px;border-radius:2px;background:var(--deep);display:inline-block;"></span>Midweek
                </div>
              </div>
              <!-- Monthly Chart (Default) -->
              <div class="bar-chart" id="chartMonthly">
                <?php foreach ($attendance_trend_monthly as $at): ?>
                <div style="display:flex;gap:2px;flex:1;justify-content:center;align-items:flex-end;">
                  <div class="bar-wrap" title="<?= $at['sunday_count'] ?> present (Sunday)">
                    <div class="bar" style="height:<?= $at['sun_height'] ?>px;"></div>
                    <div class="bar-label" style="font-size:9px;"><?= $at['label'] ?></div>
                  </div>
                  <div class="bar-wrap alt" title="<?= $at['other_count'] ?> present (Midweek)">
                    <div class="bar" style="height:<?= $at['oth_height'] ?>px;"></div>
                    <div class="bar-label" style="opacity:0;font-size:9px;">-</div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>

              <!-- Weekly Chart (Hidden) -->
              <div class="bar-chart" id="chartWeekly" style="display:none;">
                <?php foreach ($attendance_trend_weekly as $at): ?>
                <div style="display:flex;gap:2px;flex:1;justify-content:center;align-items:flex-end;">
                  <div class="bar-wrap" title="<?= $at['sunday_count'] ?> present (Sunday)">
                    <div class="bar" style="height:<?= $at['sun_height'] ?>px;"></div>
                    <div class="bar-label" style="font-size:9px;white-space:nowrap;"><?= $at['label'] ?></div>
                  </div>
                  <div class="bar-wrap alt" title="<?= $at['other_count'] ?> present (Midweek)">
                    <div class="bar" style="height:<?= $at['oth_height'] ?>px;"></div>
                    <div class="bar-label" style="opacity:0;font-size:9px;">-</div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Upcoming Events -->
          <div class="card">
            <div class="card-header">
              <h3>Upcoming Events</h3>
              <a href="events.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="card-body" style="display:flex; flex-direction:column; gap:14px; padding-top:18px;">
              <?php foreach ($upcoming_events as $event): ?>
                <div class="event-card" style="padding:14px;">
                  <div class="event-date">
                    <div class="day"><?= $event['day'] ?></div>
                    <div class="month"><?= $event['month'] ?></div>
                  </div>
                  <div>
                    <div style="font-size:14px;font-weight:600;color:var(--deep2);">
                      <?= htmlspecialchars($event['title']) ?></div>
                    <div style="font-size:12px;color:var(--muted);margin-top:2px;"><?= $event['time'] ?> —
                      <?= htmlspecialchars($event['venue']) ?></div>
                    <span class="badge <?= $event['badge_color'] ?>"
                      style="margin-top:6px;"><?= htmlspecialchars($event['badge_label']) ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Recent Members + Finance Summary -->
        <div class="grid-2" style="gap:24px;">
          <div class="card">
            <div class="card-header">
              <h3>Recent Members</h3>
              <a href="members.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="table-responsive">
              <table>
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Joined</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recent_members as $member): ?>
                    <tr>
                      <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                          <div class="avatar"
                            style="background:<?= $member['avatar_color'] ?>;color:<?= $member['text_color'] ?>;">
                            <?= $member['initials'] ?></div>
                          <div>
                            <div style="font-weight:500;"><?= htmlspecialchars($member['name']) ?></div>
                            <div style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($member['ministry']) ?>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td><span class="badge <?= $member['status_badge'] ?>"><?= $member['status'] ?></span></td>
                      <td style="color:var(--muted);font-size:12px;"><?= $member['joined'] ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Finance summary -->
          <div class="card">
            <div class="card-header">
              <h3>Finance Summary — <?= date('F') ?></h3>
              <a href="finance.php" class="btn btn-outline btn-sm">Details</a>
            </div>
            <div class="card-body">
              <div class="summary-row">
                <span style="font-size:13px;color:var(--mid);">Tithes</span>
                <span style="font-size:14px;font-weight:600;color:var(--deep2);">GH₵
                  <?= $finance_summary['tithes'] ?></span>
              </div>
              <div class="summary-row">
                <span style="font-size:13px;color:var(--mid);">Offerings</span>
                <span style="font-size:14px;font-weight:600;color:var(--deep2);">GH₵
                  <?= $finance_summary['offerings'] ?></span>
              </div>
              <div class="summary-row">
                <span style="font-size:13px;color:var(--mid);">Donations</span>
                <span style="font-size:14px;font-weight:600;color:var(--deep2);">GH₵
                  <?= $finance_summary['donations'] ?></span>
              </div>
              <div class="summary-row">
                <span style="font-size:13px;color:var(--mid);">Pledges</span>
                <span style="font-size:14px;font-weight:600;color:var(--deep2);">GH₵
                  <?= $finance_summary['pledges'] ?></span>
              </div>
              <div class="summary-row">
                <span style="font-size:13px;color:var(--mid);">Welfare</span>
                <span style="font-size:14px;font-weight:600;color:var(--deep2);">GH₵
                  <?= $finance_summary['welfare'] ?></span>
              </div>
              <div class="summary-row" style="border-top:2px solid #EDE8DF; padding-top:14px; margin-top:4px;">
                <span style="font-size:14px;font-weight:700;color:var(--deep2);">Total</span>
                <span style="font-size:17px;font-weight:700;color:var(--success);">GH₵
                  <?= $finance_summary['total'] ?></span>
              </div>
              <div style="margin-top:18px;">
                <div
                  style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:6px;">
                  <span>Monthly target: <?= formatGhc($monthlyTarget) ?></span>
                  <span><?= $finance_summary['target_percent'] ?>%</span>
                </div>
                <div style="height:8px;border-radius:10px;background:#EDE8DF;overflow:hidden;">
                  <div
                    style="height:100%;width:<?= $finance_summary['target_percent'] ?>%;border-radius:10px;background:var(--gold);">
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
    function toggleAttendanceChart(type) {
      if (type === 'monthly') {
        document.getElementById('tabMonthly').classList.add('active');
        document.getElementById('tabWeekly').classList.remove('active');
        document.getElementById('chartMonthly').style.display = 'flex';
        document.getElementById('chartWeekly').style.display = 'none';
      } else {
        document.getElementById('tabWeekly').classList.add('active');
        document.getElementById('tabMonthly').classList.remove('active');
        document.getElementById('chartWeekly').style.display = 'flex';
        document.getElementById('chartMonthly').style.display = 'none';
      }
    }
  </script>
</body>

</html>