<?php
/**
 * Dashboard Page
 */
require_once 'includes/auth.php';
requireAuth();

$pageTitle = 'Dashboard';
$activePage = 'dashboard';


// Mock data for initial refactor (Backend team will replace these)
$stats = $stats ?? [
    'total_members' => 487,
    'sunday_attendance' => 312,
    'attendance_rate' => 64,
    'monthly_tithe' => '24.6k',
    'active_ministries' => 6
];

$upcoming_events = $upcoming_events ?? [
    ['day' => 29, 'month' => 'Apr', 'title' => 'Midweek Mass', 'time' => '9:00am', 'venue' => 'Main Auditorium', 'badge_color' => 'badge-blue', 'badge_label' => 'Weekly'],
    ['day' => 29, 'month' => 'Apr', 'title' => 'Prayer Meeting', 'time' => '6:30pm', 'venue' => 'Fellowship Hall', 'badge_color' => 'badge-blue', 'badge_label' => 'Weekly'],
    ['day' => 1, 'month' => 'May', 'title' => 'Night of Cry', 'time' => '9:00pm', 'venue' => 'Main Auditorium', 'badge_color' => 'badge-red', 'badge_label' => 'Monthly'],
    ['day' => 3, 'month' => 'May', 'title' => 'Sunday Mass', 'time' => '8:30am', 'venue' => 'Main Auditorium', 'badge_color' => 'badge-green', 'badge_label' => 'Weekly']
];

$recent_members = $recent_members ?? [
    ['initials' => 'AK', 'name' => 'Abena Kusi', 'ministry' => 'Music Ministry', 'status' => 'Active', 'status_badge' => 'badge-green', 'joined' => 'Mar 28', 'avatar_color' => 'var(--gold-pale)', 'text_color' => 'var(--gold)'],
    ['initials' => 'KO', 'name' => 'Kwame Ofori', 'ministry' => 'Youth Wing', 'status' => 'Active', 'status_badge' => 'badge-green', 'joined' => 'Mar 22', 'avatar_color' => '#EEF2FF', 'text_color' => 'var(--deep)'],
    ['initials' => 'SA', 'name' => 'Serwa Acheampong', 'ministry' => 'Intercessory', 'status' => 'Visitor', 'status_badge' => 'badge-yellow', 'joined' => 'Apr 1', 'avatar_color' => '#F5F3FF', 'text_color' => '#7C3AED'],
    ['initials' => 'MB', 'name' => 'Michael Boateng', 'ministry' => 'Evangelism', 'status' => 'Active', 'status_badge' => 'badge-green', 'joined' => 'Apr 3', 'avatar_color' => '#ECFDF5', 'text_color' => '#2E7D57']
];

$finance_summary = $finance_summary ?? [
    'tithes' => '14,820',
    'offerings' => '5,450',
    'donations' => '2,300',
    'pledges' => '1,980',
    'total' => '24,550',
    'target_percent' => 82
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
            <div style="font-size:12px; color:var(--muted); margin-top:2px;"><?= date('l, j F Y') ?> — Week <?= date('W') ?></div>
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
            <div class="change" style="color:var(--success);">↑ 12 this month</div>
            <div class="icon-bg" style="background:var(--gold-pale);">
              <i class="ph ph-users" style="color:var(--gold); font-size: 20px;"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--deep);"></div>
            <div class="label">Sunday Attendance</div>
            <div class="value"><?= $stats['sunday_attendance'] ?></div>
            <div class="change" style="color:var(--deep);"><?= $stats['attendance_rate'] ?>% attendance rate</div>
            <div class="icon-bg" style="background:#EEF2FF;">
              <i class="ph ph-clipboard-text" style="color:var(--deep); font-size: 20px;"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:#2E7D57;"></div>
            <div class="label">Monthly Tithe</div>
            <div class="value">GH₵<?= $stats['monthly_tithe'] ?></div>
            <div class="change" style="color:var(--success);">↑ 8% vs last month</div>
            <div class="icon-bg" style="background:#ECFDF5;">
              <i class="ph ph-wallet" style="color:#2E7D57; font-size: 20px;"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--deep3);"></div>
            <div class="label">Active Ministries</div>
            <div class="value"><?= $stats['active_ministries'] ?></div>
            <div class="change" style="color:var(--deep3);">182 members enrolled</div>
            <div class="icon-bg" style="background:#F5F3FF;">
              <i class="ph ph-heart" style="color:var(--deep3); font-size: 20px;"></i>
            </div>
          </div>
        </div>

        <div class="grid-2" style="margin-bottom:24px; gap:24px;">

          <!-- Attendance Chart -->
          <div class="card">
            <div class="card-header">
              <h3>Attendance Trend</h3>
              <div class="tabs" style="padding:2px;">
                <button class="tab active" style="padding:5px 12px; font-size:12px;">Monthly</button>
                <button class="tab" style="padding:5px 12px; font-size:12px;">Weekly</button>
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
              <div class="bar-chart">
                <!-- Static bars kept for now, backend will loop these -->
                <div class="bar-wrap">
                  <div class="bar" style="height:72px;"></div>
                  <div class="bar-label">Oct</div>
                </div>
                <div class="bar-wrap">
                  <div class="bar" style="height:88px;"></div>
                  <div class="bar-label">Nov</div>
                </div>
                <div class="bar-wrap">
                  <div class="bar" style="height:65px;"></div>
                  <div class="bar-label">Dec</div>
                </div>
                <div class="bar-wrap">
                  <div class="bar" style="height:95px;"></div>
                  <div class="bar-label">Jan</div>
                </div>
                <div class="bar-wrap">
                  <div class="bar" style="height:82px;"></div>
                  <div class="bar-label">Feb</div>
                </div>
                <div class="bar-wrap">
                  <div class="bar" style="height:112px;"></div>
                  <div class="bar-label">Mar</div>
                </div>
                <div class="bar-wrap alt">
                  <div class="bar" style="height:48px;"></div>
                  <div class="bar-label">Oct</div>
                </div>
                <div class="bar-wrap alt">
                  <div class="bar" style="height:55px;"></div>
                  <div class="bar-label">Nov</div>
                </div>
                <div class="bar-wrap alt">
                  <div class="bar" style="height:42px;"></div>
                  <div class="bar-label">Dec</div>
                </div>
                <div class="bar-wrap alt">
                  <div class="bar" style="height:60px;"></div>
                  <div class="bar-label">Jan</div>
                </div>
                <div class="bar-wrap alt">
                  <div class="bar" style="height:50px;"></div>
                  <div class="bar-label">Feb</div>
                </div>
                <div class="bar-wrap alt">
                  <div class="bar" style="height:72px;"></div>
                  <div class="bar-label">Mar</div>
                </div>
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
                  <div style="font-size:14px;font-weight:600;color:var(--deep2);"><?= htmlspecialchars($event['title']) ?></div>
                  <div style="font-size:12px;color:var(--muted);margin-top:2px;"><?= $event['time'] ?> — <?= htmlspecialchars($event['venue']) ?></div>
                  <span class="badge <?= $event['badge_color'] ?>" style="margin-top:6px;"><?= htmlspecialchars($event['badge_label']) ?></span>
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
                        <div class="avatar" style="background:<?= $member['avatar_color'] ?>;color:<?= $member['text_color'] ?>;"><?= $member['initials'] ?></div>
                        <div>
                          <div style="font-weight:500;"><?= htmlspecialchars($member['name']) ?></div>
                          <div style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($member['ministry']) ?></div>
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
                <span style="font-size:14px;font-weight:600;color:var(--deep2);">GH₵ <?= $finance_summary['tithes'] ?></span>
              </div>
              <div class="summary-row">
                <span style="font-size:13px;color:var(--mid);">Offerings</span>
                <span style="font-size:14px;font-weight:600;color:var(--deep2);">GH₵ <?= $finance_summary['offerings'] ?></span>
              </div>
              <div class="summary-row">
                <span style="font-size:13px;color:var(--mid);">Donations</span>
                <span style="font-size:14px;font-weight:600;color:var(--deep2);">GH₵ <?= $finance_summary['donations'] ?></span>
              </div>
              <div class="summary-row">
                <span style="font-size:13px;color:var(--mid);">Pledges</span>
                <span style="font-size:14px;font-weight:600;color:var(--deep2);">GH₵ <?= $finance_summary['pledges'] ?></span>
              </div>
              <div class="summary-row" style="border-top:2px solid #EDE8DF; padding-top:14px; margin-top:4px;">
                <span style="font-size:14px;font-weight:700;color:var(--deep2);">Total</span>
                <span style="font-size:17px;font-weight:700;color:var(--success);">GH₵ <?= $finance_summary['total'] ?></span>
              </div>
              <div style="margin-top:18px;">
                <div
                  style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:6px;">
                  <span>Monthly target: GH₵ 30,000</span>
                  <span><?= $finance_summary['target_percent'] ?>%</span>
                </div>
                <div style="height:8px;border-radius:10px;background:#EDE8DF;overflow:hidden;">
                  <div style="height:100%;width:<?= $finance_summary['target_percent'] ?>%;border-radius:10px;background:var(--gold);"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </main>


  <script src="assets/js/main.js"></script>
</body>

</html>
