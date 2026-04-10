<?php
/**
 * Attendance Tracking Page
 * 
 * BACKEND CONTRACT:
 * Expected variables:
 * @var array $attendance_stats { present, absent, visitors, avg_month }
 * @var array $today_register [{ name, ministry, ministry_badge, status, status_badge, time }]
 * @var array $sessions_week [{ type, date, time, status, status_badge, count_present, count_total, percent }]
 */

$pageTitle = 'Attendance';
$activePage = 'attendance';

// Mock data for initial refactor (Backend team will replace these)
$attendance_stats = $attendance_stats ?? [
    'present' => 312,
    'absent' => 175,
    'visitors' => 8,
    'avg_month' => 298
];

$today_register = $today_register ?? [
    ['name' => 'Abena Kusi', 'ministry' => 'Music', 'ministry_badge' => 'badge-purple', 'status' => '✓ Present', 'status_badge' => 'badge-green', 'time' => '8:12am'],
    ['name' => 'Kwame Ofori', 'ministry' => 'Youth', 'ministry_badge' => 'badge-blue', 'status' => '✓ Present', 'status_badge' => 'badge-green', 'time' => '7:58am'],
    ['name' => 'Efua Asare', 'ministry' => 'Intercessory', 'ministry_badge' => 'badge-yellow', 'status' => '✗ Absent', 'status_badge' => 'badge-red', 'time' => '—'],
    ['name' => 'Michael Boateng', 'ministry' => 'Evangelism', 'ministry_badge' => 'badge-green', 'status' => '✓ Present', 'status_badge' => 'badge-green', 'time' => '8:05am'],
    ['name' => 'Serwa Acheampong', 'ministry' => '—', 'ministry_badge' => 'badge-gray', 'status' => 'Visitor', 'status_badge' => 'badge-yellow', 'time' => '8:30am']
];

$sessions_week = $sessions_week ?? [
    ['type' => 'Sunday Service', 'date' => 'Apr 6', 'time' => '8:00am', 'status' => 'Done', 'status_badge' => 'badge-green', 'count_present' => 312, 'count_total' => 487, 'percent' => 64],
    ['type' => 'Midweek Prayer', 'date' => 'Apr 9', 'time' => '6:30pm', 'status' => 'Upcoming', 'status_badge' => 'badge-blue', 'count_present' => 0, 'count_total' => 0, 'percent' => 0],
    ['type' => 'Youth Meeting', 'date' => 'Apr 10', 'time' => '5:00pm', 'status' => 'Upcoming', 'status_badge' => 'badge-blue', 'count_present' => 0, 'count_total' => 0, 'percent' => 0]
];
?>
<!DOCTYPE html>
<html lang="en">

<?php require_once 'includes/head.php'; ?>

<body>

  <?php require_once 'includes/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main id="main">

    <div id="page-attendance" class="page">
      <div class="topbar">
        <div style="display:flex;align-items:center;">
          <button class="mobile-toggle" onclick="toggleSidebar()">
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <div class="topbar-title">Attendance Tracking</div>
        </div>
        <div class="topbar-actions">
          <input type="date" class="form-control" value="<?= date('Y-m-d') ?>" style="width:160px;padding:8px 12px;">
          <button class="btn btn-primary btn-sm" onclick="openModal('recordAttModal')">+ Record Attendance</button>
        </div>
      </div>
      <div class="content">
        <div class="grid-4" style="margin-bottom:24px;">
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--gold);"></div>
            <div class="label">Present Today</div>
            <div class="value"><?= $attendance_stats['present'] ?></div>
            <div class="change" style="color:var(--success);">64% of members</div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--deep);"></div>
            <div class="label">Absent</div>
            <div class="value"><?= $attendance_stats['absent'] ?></div>
            <div class="change" style="color:var(--danger);">36% absent</div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:#2E7D57;"></div>
            <div class="label">New Visitors</div>
            <div class="value"><?= $attendance_stats['visitors'] ?></div>
            <div class="change" style="color:var(--success);">+3 vs last week</div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--deep3);"></div>
            <div class="label">Avg This Month</div>
            <div class="value"><?= $attendance_stats['avg_month'] ?></div>
            <div class="change" style="color:var(--deep3);">Weekly average</div>
          </div>
        </div>

        <div class="grid-2" style="gap:24px;">
          <div class="card">
            <div class="card-header">
              <h3>Today's Register — Sunday Service</h3>
              <div class="tabs">
                <button class="tab active">All</button>
                <button class="tab">Present</button>
                <button class="tab">Absent</button>
              </div>
            </div>
            <div class="table-responsive">
              <table>
                <thead>
                  <tr>
                    <th>Member</th>
                    <th>Ministry</th>
                    <th>Status</th>
                    <th>Time</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($today_register as $reg): ?>
                  <tr>
                    <td>
                      <div style="font-weight:500;"><?= htmlspecialchars($reg['name']) ?></div>
                    </td>
                    <td><span class="badge <?= $reg['ministry_badge'] ?>"><?= htmlspecialchars($reg['ministry']) ?></span></td>
                    <td><span class="badge <?= $reg['status_badge'] ?>"><?= $reg['status'] ?></span></td>
                    <td style="font-size:12px;color:var(--muted);"><?= $reg['time'] ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3>Sessions This Week</h3>
            </div>
            <div class="card-body">
              <div style="display:flex;flex-direction:column;gap:14px;">
                <?php foreach ($sessions_week as $sess): ?>
                <div style="background:#F1F5F9;border-radius:10px;padding:16px;">
                  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                    <div>
                      <div style="font-weight:600;font-size:14px;color:var(--deep2);"><?= htmlspecialchars($sess['type']) ?></div>
                      <div style="font-size:12px;color:var(--muted);"><?= $sess['date'] ?> · <?= $sess['time'] ?></div>
                    </div>
                    <span class="badge <?= $sess['status_badge'] ?>"><?= $sess['status'] ?></span>
                  </div>
                  <?php if ($sess['status'] === 'Done'): ?>
                  <div style="display:flex;gap:8px;align-items:center;font-size:12px;color:var(--mid);">
                    <div style="flex:1;height:6px;border-radius:10px;background:#EDE8DF;overflow:hidden;">
                      <div style="height:100%;width:<?= $sess['percent'] ?>%;background:var(--gold);border-radius:10px;"></div>
                    </div>
                    <span><?= $sess['count_present'] ?> / <?= $sess['count_total'] ?></span>
                  </div>
                  <?php else: ?>
                  <div style="font-size:12px;color:var(--muted);">Not yet recorded</div>
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </main>

  <?php require_once 'includes/modals/attendance_modal.php'; ?>

  <script src="assets/js/main.js"></script>
  <script>
    function filterAttendance() {
      const q = document.getElementById('attSearch').value.toLowerCase();
      document.querySelectorAll('#attList .att-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? 'flex' : 'none';
      });
      // Optionally reset Select All state when filtering changes
      document.getElementById('markAllChk').checked = false;
    }

    function toggleMarkAll(chk) {
      const isChecked = chk.checked;
      document.querySelectorAll('#attList .att-row').forEach(row => {
        if (row.style.display !== 'none') {
          const checkbox = row.querySelector('.att-member');
          if (checkbox) checkbox.checked = isChecked;
        }
      });
    }
  </script>
</body>

</html>
