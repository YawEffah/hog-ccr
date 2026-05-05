<?php
/**
 * Ministries & Groups Page
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

$pageTitle  = 'Ministries';
$activePage = 'ministries';

$successMsg = flash('success');
$errorMsg   = flash('error');

$db = getDB();

// ── Ministries List with Member Counts ───────────────────────────────────────
$minStmt = $db->query(
    "SELECT min.*, 
            h.id as head_member_id,
            CONCAT(h.first_name, ' ', h.last_name) as head_name,
            h.member_code as head_code,
            (SELECT COUNT(*) FROM members WHERE ministry_id = min.id) as total_count,
            (SELECT COUNT(*) FROM members WHERE ministry_id = min.id AND status='Active') as active_count
     FROM ministries min
     LEFT JOIN members h ON min.head_id = h.id
     ORDER BY min.name ASC"
);
$rawMinistries = $minStmt->fetchAll();

// ── All Members for Search suggestions ───────────────────────────────────────
$allMembers = $db->query("SELECT id, first_name, last_name, member_code FROM members ORDER BY last_name ASC")->fetchAll();

$ministries = array_map(function($m) use ($db) {
    // Get average attendance for this ministry
    $attStmt = $db->prepare("
        SELECT AVG(present_count / total_possible * 100) as avg_att
        FROM (
            SELECT s.id, 
                   SUM(CASE WHEN r.status = 'Present' THEN 1 ELSE 0 END) as present_count,
                   COUNT(r.id) as total_possible
            FROM attendance_sessions s
            JOIN attendance_records r ON s.id = r.session_id
            JOIN members m ON r.member_id = m.id
            WHERE m.ministry_id = ?
            GROUP BY s.id
        ) as session_stats
    ");
    $attStmt->execute([$m['id']]);
    $avgAtt = (float)$attStmt->fetchColumn() ?: 0;

    return [
        'id'             => $m['id'],
        'slug'           => $m['slug'],
        'name'           => $m['name'],
        'description'    => $m['description'],
        'icon'           => $m['icon'],
        'bg_color'       => $m['bg_color'],
        'count'          => $m['total_count'],
        'active_count'   => $m['active_count'],
        'attendance_avg' => round($avgAtt)
    ];
}, $rawMinistries);

// ── Detail data for the "Manage" modal ───────────────────────────────────────
$ministry_details = [];
foreach ($rawMinistries as $m) {
    // Fetch members
    $memStmt = $db->prepare("SELECT first_name, last_name, joined_date, status FROM members WHERE ministry_id = ? ORDER BY joined_date DESC LIMIT 20");
    $memStmt->execute([$m['id']]);
    $members = $memStmt->fetchAll();

    $formattedMembers = array_map(function($mem) {
        return [
            'n' => $mem['first_name'] . ' ' . $mem['last_name'],
            'r' => $mem['status'],
            'd' => $mem['joined_date'] ? date('M Y', strtotime($mem['joined_date'])) : 'N/A'
        ];
    }, $members);

    // Fetch sessions
    $sessStmt = $db->prepare("SELECT COUNT(DISTINCT s.id) FROM attendance_sessions s JOIN attendance_records r ON s.id = r.session_id JOIN members mem ON r.member_id = mem.id WHERE mem.ministry_id = ?");
    $sessStmt->execute([$m['id']]);
    $sessionCount = (int)$sessStmt->fetchColumn();

    // Fetch trend (last 6 sessions)
    $trendStmt = $db->prepare("
        SELECT (SUM(CASE WHEN r.status = 'Present' THEN 1 ELSE 0 END) / COUNT(r.id) * 100) as pct
        FROM attendance_sessions s
        JOIN attendance_records r ON s.id = r.session_id
        JOIN members mem ON r.member_id = mem.id
        WHERE mem.ministry_id = ?
        GROUP BY s.id
        ORDER BY s.session_date DESC
        LIMIT 6
    ");
    $trendStmt->execute([$m['id']]);
    $trendRows = $trendStmt->fetchAll(PDO::FETCH_COLUMN);
    $chartData = array_reverse(array_map('round', $trendRows));

    // Calculate avg attendance for modal
    $attStmt = $db->prepare("
        SELECT AVG(present_count / total_possible * 100) as avg_att
        FROM (
            SELECT s.id, 
                   SUM(CASE WHEN r.status = 'Present' THEN 1 ELSE 0 END) as present_count,
                   COUNT(r.id) as total_possible
            FROM attendance_sessions s
            JOIN attendance_records r ON s.id = r.session_id
            JOIN members m ON r.member_id = m.id
            WHERE m.ministry_id = ?
            GROUP BY s.id
        ) as session_stats
    ");
    $attStmt->execute([$m['id']]);
    $avgAtt = round((float)$attStmt->fetchColumn() ?: 0);

    $ministry_details[$m['id']] = [
        'id'       => $m['id'],
        'icon'     => $m['icon'],
        'bg'       => $m['bg_color'],
        'title'    => $m['name'],
        'desc'     => $m['description'],
        'head_id'  => $m['head_member_id'],
        'head_name'=> $m['head_name'] ? $m['head_name'] . " (" . $m['head_code'] . ")" : '',
        'count'    => $m['total_count'],
        'att'      => $avgAtt . '%',
        'sessions' => $sessionCount,
        'members'  => $formattedMembers,
        'history'  => [],
        'chart_data' => $chartData
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<?php require_once 'includes/head.php'; ?>

<body>

  <?php require_once 'includes/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main id="main">

    <div id="page-ministries" class="page">
      <div class="topbar">
        <div style="display:flex;align-items:center;">
          <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="ph ph-list"></i>
          </button>
          <div class="topbar-title">Ministries & Groups</div>
        </div>
        <div class="topbar-actions">
          <button class="btn btn-outline btn-sm" id="notifBtn" onclick="toggleNotifications()">
            <i class="ph ph-bell"></i>
          </button>
          <?php include 'includes/notifications.php'; ?>
          <button class="btn btn-primary btn-sm" onclick="openModal('addMinistryModal')">+ New Ministry</button>
        </div>
      </div>

      <?php renderToastAlerts($successMsg, $errorMsg); ?>

      <div class="content">
        <div class="grid-3" style="margin-bottom:24px;">
          <?php foreach ($ministries as $m): ?>
          <div class="ministry-card">
            <div class="ministry-icon" style="background:<?= $m['bg_color'] ?>;"><?= $m['icon'] ?></div>
            <div
              style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--deep2);margin-bottom:4px;">
              <?= htmlspecialchars($m['name']) ?></div>
            <div style="font-size:12px;color:var(--muted);margin-bottom:14px;"><?= htmlspecialchars($m['description']) ?></div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <span class="badge badge-blue"><?= $m['count'] ?> members</span>
              <div style="display:flex;gap:6px;">
                <button class="btn btn-outline btn-sm" onclick="openMinistryBulkMessage('<?= $m['id'] ?>', '<?= htmlspecialchars(addslashes($m['name'])) ?>', '<?= $m['icon'] ?>', <?= $m['count'] ?>)" title="Message Ministry"><i class="ph ph-chat-centered-dots"></i></button>
                <button class="btn btn-outline btn-sm" onclick="manageMinistry('<?= $m['id'] ?>')">Manage</button>
                <button class="btn btn-outline btn-sm" onclick="confirmDeleteMinistry('<?= $m['id'] ?>', '<?= htmlspecialchars(addslashes($m['name'])) ?>')" style="color:#DC2626;border-color:#FECACA;background:#FEF2F2;" title="Delete Ministry">
                  <i class="ph ph-trash"></i>
                </button>
              </div>
            </div>
            <div style="margin-top:12px;height:5px;border-radius:10px;background:#EDE8DF;overflow:hidden;">
              <div style="height:100%;width:<?= $m['attendance_avg'] ?>%;background:var(--primary);border-radius:10px;"></div>
            </div>
            <div style="font-size:11px;color:var(--muted);margin-top:4px;"><?= $m['attendance_avg'] ?>% attendance avg</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </main>

  <!-- Hidden delete form -->
  <form method="POST" action="handlers/ministry_handler.php" id="deleteMinistryForm" style="display:none;">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="delete_ministry">
    <input type="hidden" name="ministry_id" id="deleteMinistryId">
  </form>

  <?php require_once 'includes/modals/ministry_modals.php'; ?>

  <script src="assets/js/main.js"></script>
  <script>
    const mData = <?php echo json_encode($ministry_details); ?>;
    const allMembersData = <?php echo json_encode(array_map(function($m) {
        return [
            'id' => $m['id'],
            'member_code' => $m['member_code'],
            'name' => htmlspecialchars($m['first_name'] . ' ' . $m['last_name'])
        ];
    }, $allMembers)); ?>;

    const defaultData = { id: 0, icon: '✝️', bg: 'var(--gold-pale)', title: 'Ministry', desc: 'Description', head_id: '', head_name: '', count: 0, att: '0%', sessions: 0, members: [], history: [] };

    function manageMinistry(id) {
      const m = mData[id] || { ...defaultData, title: 'Ministry' };

      document.getElementById('mIcon').textContent = m.icon;
      document.getElementById('mIcon').style.background = m.bg;
      document.getElementById('mTitle').textContent = m.title;
      document.getElementById('mSubtitle').textContent = m.desc;
      document.getElementById('mCount').textContent = m.count;
      document.getElementById('mAttendance').textContent = m.att;
      document.getElementById('mSessions').textContent = m.sessions;

      // Populate Chart
      const chart = document.getElementById('mChart');
      if (m.chart_data && m.chart_data.length > 0) {
        chart.innerHTML = m.chart_data.map((pct, idx) => {
          const isLast = idx === m.chart_data.length - 1;
          const bg = isLast ? 'var(--deep)' : 'var(--primary)';
          return `<div style="flex:1;background:${bg};height:${Math.max(10, pct)}%;border-radius:4px 4px 0 0;" title="${pct}% Attendance"></div>`;
        }).join('');
      } else {
        chart.innerHTML = '<div style="color:var(--muted);font-size:12px;width:100%;text-align:center;padding-bottom:20px;">No attendance data available</div>';
      }

      // Populate Edit Form
      document.getElementById('edit_mId').value = id;
      document.getElementById('edit_mName').value = m.title;
      document.getElementById('edit_mDesc').value = m.desc;
      document.getElementById('edit_mHeadId').value = m.head_id || '';
      document.getElementById('edit_mHeadDisplay').value = m.head_name || '';

      // Populate Members List
      const list = document.getElementById('mMembersList');
      list.innerHTML = m.members.length ? m.members.map(mem => `
        <tr style="border-bottom:1px solid var(--border);">
          <td style="padding:8px;font-weight:500;">${mem.n}</td>
          <td style="padding:8px;color:var(--muted);">${mem.r}</td>
          <td style="padding:8px;color:var(--muted);">${mem.d}</td>
        </tr>
      `).join('') : '<tr><td colspan="3" style="padding:20px;text-align:center;color:var(--muted);">No members assigned</td></tr>';

      // Reset Tabs
      document.querySelectorAll('#manageMinistryModal .tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('#manageMinistryModal .tab-pane').forEach(p => {
        p.style.display = 'none';
        p.classList.remove('active');
      });
      document.querySelector('#manageMinistryModal .tab').classList.add('active');
      document.getElementById('mOverview').style.display = 'block';
      document.getElementById('mOverview').classList.add('active');

      openModal('manageMinistryModal');
    }

    function openMinistryBulkMessage(id, name, icon, count) {
      document.getElementById('bulkMsgMinId').value = id;
      document.getElementById('bulkMsgMinName').textContent = name;
      document.getElementById('bulkMsgIcon').textContent = icon;
      document.getElementById('bulkMsgCount').textContent = count;
      
      openModal('sendMinistryMessageModal');
    }

    function switchMTab(el, paneId) {
      const modal = document.getElementById('manageMinistryModal');
      modal.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      modal.querySelectorAll('.tab-pane').forEach(p => {
        p.style.display = 'none';
        p.classList.remove('active');
      });

      el.classList.add('active');
      const pane = document.getElementById(paneId);
      pane.style.display = 'block';
      setTimeout(() => pane.classList.add('active'), 10);
    }

    function confirmDeleteMinistry(id, name) {
      showConfirmModal(
        'Delete Ministry',
        'Are you sure you want to delete the "' + name + '" ministry? This cannot be undone, and will only succeed if the ministry has 0 members assigned.',
        'Delete',
        function() {
          document.getElementById('deleteMinistryId').value = id;
          document.getElementById('deleteMinistryForm').submit();
        },
        'danger'
      );
    }
  </script>
</body>
</html>
