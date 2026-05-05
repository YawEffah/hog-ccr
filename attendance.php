<?php
/**
 * Attendance Tracking Page
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

$pageTitle = 'Attendance';
$activePage = 'attendance';

$successMsg = flash('success');
$errorMsg = flash('error');
if (!$successMsg && !$errorMsg) {
  $successLabels = ['attendance_recorded' => 'Attendance recorded successfully.'];
  $errorLabels = [
    'missing_fields' => 'Please select session type and date.',
    'db_error' => 'A database error occurred. Please try again.'
  ];
  $successMsg = $successLabels[$_GET['success'] ?? ''] ?? '';
  $errorMsg = $errorLabels[$_GET['error'] ?? ''] ?? '';
}

$db = getDB();
$today = date('Y-m-d');

// ── Attendance Statistics ────────────────────────────────────────────────────
$statsStmt = $db->prepare(
  "SELECT 
        SUM(CASE WHEN ar.status='Present' THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN ar.status='Absent' THEN 1 ELSE 0 END) AS absent,
        SUM(CASE WHEN ar.status='Visitor' THEN 1 ELSE 0 END) AS visitors
     FROM attendance_records ar
     JOIN attendance_sessions s ON ar.session_id = s.id
     WHERE s.session_date = ?"
);
$statsStmt->execute([$today]);
$attendance_stats = $statsStmt->fetch();
$attendance_stats = [
  'present' => (int) ($attendance_stats['present'] ?? 0),
  'absent' => (int) ($attendance_stats['absent'] ?? 0),
  'visitors' => (int) ($attendance_stats['visitors'] ?? 0),
  'avg_month' => (int) $db->query("SELECT AVG(present_count) FROM (SELECT session_id, COUNT(*) as present_count FROM attendance_records WHERE status='Present' GROUP BY session_id) as daily_counts")->fetchColumn()
];

// ── Today's Register ─────────────────────────────────────────────────────────
$regStmt = $db->prepare(
  "SELECT m.first_name, m.last_name, s.session_type, ar.status, ar.check_in_time
     FROM attendance_records ar
     JOIN members m ON ar.member_id = m.id
     JOIN attendance_sessions s ON ar.session_id = s.id
     WHERE s.session_date = ?
     ORDER BY ar.check_in_time DESC
     LIMIT 7"
);
$regStmt->execute([$today]);
$rawRegister = $regStmt->fetchAll();

$today_register = array_map(function ($r) {
  return [
    'name' => $r['first_name'] . ' ' . $r['last_name'],
    'session' => $r['session_type'],
    'session_badge' => 'badge-gray',
    'status' => ($r['status'] === 'Present' ? '✓ Present' : ($r['status'] === 'Absent' ? '✗ Absent' : 'Visitor')),
    'status_badge' => ($r['status'] === 'Present' ? 'badge-green' : ($r['status'] === 'Absent' ? 'badge-red' : 'badge-yellow')),
    'time' => $r['check_in_time'] ? date('g:ia', strtotime($r['check_in_time'])) : '—'
  ];
}, $rawRegister);

// ── Recent Sessions ────────────────────────────────────────────────────────────
$sessionsStmt = $db->query(
  "SELECT s.*, 
            (SELECT COUNT(*) FROM attendance_records WHERE session_id = s.id AND status='Present') as present_count,
            (SELECT COUNT(*) FROM attendance_records WHERE session_id = s.id) as total_count
     FROM attendance_sessions s
     ORDER BY s.session_date DESC, s.session_time DESC
     LIMIT 3"
);
$rawSessions = $sessionsStmt->fetchAll();

$sessions_week = array_map(function ($s) {
  $percent = $s['total_count'] > 0 ? round(($s['present_count'] / $s['total_count']) * 100) : 0;
  return [
    'type' => $s['session_type'],
    'date' => date('M j', strtotime($s['session_date'])),
    'time' => $s['session_time'] ? date('g:ia', strtotime($s['session_time'])) : '—',
    'status' => 'Done',
    'status_badge' => 'badge-green',
    'count_present' => $s['present_count'],
    'count_total' => $s['total_count'],
    'percent' => $percent
  ];
}, $rawSessions);

// ── All Members (for Attendance Modal) ───────────────────────────────────────
$allMembers = $db->query(
  "SELECT id, first_name, last_name, member_code 
     FROM members 
     WHERE status = 'Active' 
     ORDER BY last_name ASC"
)->fetchAll();
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
            <i class="ph ph-list"></i>
          </button>
          <div class="topbar-title">Attendance Tracking</div>
        </div>
        <div class="topbar-actions">
          <input type="date" class="form-control" value="<?= date('Y-m-d') ?>" style="width:160px;padding:8px 12px;">
          <button class="btn btn-outline btn-sm" id="notifBtn" onclick="toggleNotifications()">
            <i class="ph ph-bell"></i>
          </button>
          <?php include 'includes/notifications.php'; ?>
          <button class="btn btn-primary btn-sm" onclick="openModal('recordAttModal')">+ Record Attendance</button>
        </div>
      </div>
      <div class="content">
        <?php renderToastAlerts($successMsg, $errorMsg); ?>

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
              <h3>Today's Register</h3>
              <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
                <div class="tabs" id="registerTabs">
                  <button class="tab active" onclick="filterRegister('All', this)">All</button>
                  <button class="tab" onclick="filterRegister('Present', this)">Present</button>
                  <button class="tab" onclick="filterRegister('Absent', this)">Absent</button>
                </div>
                <a href="attendance_history.php" class="btn btn-outline btn-sm" style="font-size:12px;">View All</a>
              </div>
            </div>
            <div class="table-responsive">
              <table>
                <thead>
                  <tr>
                    <th>Member</th>
                    <th>Session</th>
                    <th>Status</th>
                    <th>Time</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($today_register as $reg): ?>
                    <tr class="register-row" data-status="<?= htmlspecialchars($reg['status']) ?>">
                      <td>
                        <div style="font-weight:500;"><?= htmlspecialchars($reg['name']) ?></div>
                      </td>
                      <td><span
                          class="badge <?= $reg['session_badge'] ?>"><?= htmlspecialchars($reg['session']) ?></span>
                      </td>
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
              <h3>Recent Sessions</h3>
            </div>
            <div class="card-body">
              <div style="display:flex;flex-direction:column;gap:14px;">
                <?php foreach ($sessions_week as $sess): ?>
                  <div style="background:#F1F5F9;border-radius:10px;padding:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                      <div>
                        <div style="font-weight:600;font-size:14px;color:var(--deep2);">
                          <?= htmlspecialchars($sess['type']) ?></div>
                        <div style="font-size:12px;color:var(--muted);"><?= $sess['date'] ?> · <?= $sess['time'] ?></div>
                      </div>
                      <span class="badge <?= $sess['status_badge'] ?>"><?= $sess['status'] ?></span>
                    </div>
                    <?php if ($sess['status'] === 'Done'): ?>
                      <div style="display:flex;gap:8px;align-items:center;font-size:12px;color:var(--mid);">
                        <div style="flex:1;height:6px;border-radius:10px;background:#EDE8DF;overflow:hidden;">
                          <div
                            style="height:100%;width:<?= $sess['percent'] ?>%;background:var(--gold);border-radius:10px;">
                          </div>
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

    function filterRegister(status, btn) {
      document.querySelectorAll('#registerTabs .tab').forEach(t => t.classList.remove('active'));
      btn.classList.add('active');

      document.querySelectorAll('.register-row').forEach(row => {
        if (status === 'All') {
          row.style.display = '';
        } else {
          const rowStatus = row.getAttribute('data-status');
          if (rowStatus.includes(status)) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        }
      });
    }
  </script>
</body>

</html>