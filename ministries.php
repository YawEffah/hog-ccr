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

if (!hasPermission('perm_manage_members')) {
    redirect('index.php');
}

$successMsg = flash('success');
$errorMsg   = flash('error');

$db = getDB();

// ── Ministries List with Member Counts ───────────────────────────────────────
$minStmt = $db->query(
    "SELECT min.*, 
            (SELECT COUNT(*) FROM member_ministries WHERE ministry_id = min.id) as total_count,
            (SELECT COUNT(*) FROM member_ministries mm JOIN members m ON mm.member_id = m.id WHERE mm.ministry_id = min.id AND m.status='Active') as active_count
     FROM ministries min
     ORDER BY min.name ASC"
);
$rawMinistries = $minStmt->fetchAll();

// ── All Members for Search suggestions ───────────────────────────────────────
$allMembers = $db->query("SELECT id, first_name, last_name, member_code FROM members ORDER BY last_name ASC")->fetchAll();

$ministries = array_map(function($m) use ($db) {
    // Get average attendance for this ministry (from ministry-scoped sessions)
    $attStmt = $db->prepare("
        SELECT AVG(present_count / total_possible * 100) as avg_att
        FROM (
            SELECT s.id, 
                   SUM(CASE WHEN r.status = 'Present' THEN 1 ELSE 0 END) as present_count,
                   COUNT(r.id) as total_possible
            FROM attendance_sessions s
            JOIN attendance_records r ON s.id = r.session_id
            WHERE s.ministry_id = ?
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
    // Fetch members (for Members tab)
    $memStmt = $db->prepare("SELECT m.id, m.first_name, m.last_name, mm.enrol_date, mm.role, mm.notes, m.status FROM members m JOIN member_ministries mm ON m.id = mm.member_id WHERE mm.ministry_id = ? ORDER BY mm.enrol_date DESC, m.last_name ASC LIMIT 100");
    $memStmt->execute([$m['id']]);
    $members = $memStmt->fetchAll();

    $formattedMembers = array_map(function($mem) use ($m) {
        return [
            'mId' => $mem['id'],
            'minId' => $m['id'],
            'n' => $mem['first_name'] . ' ' . $mem['last_name'],
            'r' => $mem['role'] ?? 'Member',
            'd' => $mem['enrol_date'] ? date('M Y', strtotime($mem['enrol_date'])) : 'N/A',
            'raw_d' => $mem['enrol_date'] ?? '',
            'notes' => $mem['notes'] ?? ''
        ];
    }, $members);

    // Fetch ministry members for attendance checklist (id, name, code)
    $attMemStmt = $db->prepare(
        "SELECT m.id, m.first_name, m.last_name, m.member_code
         FROM members m
         JOIN member_ministries mm ON m.id = mm.member_id
         WHERE mm.ministry_id = ? AND m.status != 'Affiliate Community Member'
         ORDER BY m.last_name ASC"
    );
    $attMemStmt->execute([$m['id']]);
    $ministryMembers = array_map(function($mem) {
        return [
            'id'   => $mem['id'],
            'name' => $mem['first_name'] . ' ' . $mem['last_name'],
            'code' => $mem['member_code']
        ];
    }, $attMemStmt->fetchAll());

    // Fetch session count (ministry-scoped)
    $sessStmt = $db->prepare("SELECT COUNT(*) FROM attendance_sessions WHERE ministry_id = ?");
    $sessStmt->execute([$m['id']]);
    $sessionCount = (int)$sessStmt->fetchColumn();

    // Fetch recent sessions (last 5, ministry-scoped)
    $recentStmt = $db->prepare("
        SELECT s.id, s.session_type, s.session_date, s.session_time,
               SUM(CASE WHEN r.status = 'Present' THEN 1 ELSE 0 END) as present_count,
               SUM(CASE WHEN r.status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
               COUNT(r.id) as total_count
        FROM attendance_sessions s
        LEFT JOIN attendance_records r ON s.id = r.session_id
        WHERE s.ministry_id = ?
        GROUP BY s.id
        ORDER BY s.session_date DESC, s.session_time DESC
        LIMIT 5
    ");
    $recentStmt->execute([$m['id']]);
    $recentSessions = array_map(function($s) {
        $pct = $s['total_count'] > 0 ? round(($s['present_count'] / $s['total_count']) * 100) : 0;
        return [
            'type'    => $s['session_type'],
            'date'    => date('M j, Y', strtotime($s['session_date'])),
            'time'    => $s['session_time'] ? date('g:ia', strtotime($s['session_time'])) : '—',
            'present' => (int)$s['present_count'],
            'absent'  => (int)$s['absent_count'],
            'total'   => (int)$s['total_count'],
            'pct'     => $pct
        ];
    }, $recentStmt->fetchAll());

    // Fetch trend (last 6 sessions, ministry-scoped)
    $trendStmt = $db->prepare("
        SELECT s.session_type,
               (SUM(CASE WHEN r.status = 'Present' THEN 1 ELSE 0 END) / COUNT(r.id) * 100) as pct
        FROM attendance_sessions s
        JOIN attendance_records r ON s.id = r.session_id
        WHERE s.ministry_id = ?
        GROUP BY s.id
        ORDER BY s.session_date DESC
        LIMIT 6
    ");
    $trendStmt->execute([$m['id']]);
    $trendRows = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
    $chartData = array_reverse(array_map(function($r) {
        return ['pct' => round($r['pct']), 'type' => $r['session_type']];
    }, $trendRows));

    // Calculate avg attendance for modal (ministry-scoped)
    $attStmt = $db->prepare("
        SELECT AVG(present_count / total_possible * 100) as avg_att
        FROM (
            SELECT s.id, 
                   SUM(CASE WHEN r.status = 'Present' THEN 1 ELSE 0 END) as present_count,
                   COUNT(r.id) as total_possible
            FROM attendance_sessions s
            JOIN attendance_records r ON s.id = r.session_id
            WHERE s.ministry_id = ?
            GROUP BY s.id
        ) as session_stats
    ");
    $attStmt->execute([$m['id']]);
    $avgAtt = round((float)$attStmt->fetchColumn() ?: 0);

    // Total aggregated attendance stats
    $aggStmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN r.status = 'Present' THEN 1 ELSE 0 END) as total_present,
            SUM(CASE WHEN r.status = 'Absent' THEN 1 ELSE 0 END) as total_absent
        FROM attendance_sessions s
        JOIN attendance_records r ON s.id = r.session_id
        WHERE s.ministry_id = ?
    ");
    $aggStmt->execute([$m['id']]);
    $aggRow = $aggStmt->fetch(PDO::FETCH_ASSOC);
    $totalPresent = (int)$aggRow['total_present'];
    $totalAbsent = (int)$aggRow['total_absent'];

    $ministry_details[$m['id']] = [
        'id'               => $m['id'],
        'icon'             => $m['icon'],
        'bg'               => $m['bg_color'],
        'title'            => $m['name'],
        'desc'             => $m['description'],
        'meeting_time'     => $m['meeting_time'],
        'count'            => $m['total_count'],
        'att'              => $avgAtt . '%',
        'total_present'    => $totalPresent,
        'total_absent'     => $totalAbsent,
        'sessions'         => $sessionCount,
        'members'          => $formattedMembers,
        'ministry_members' => $ministryMembers,
        'recent_sessions'  => $recentSessions,
        'history'          => [],
        'chart_data'       => $chartData
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
                <button class="btn btn-outline btn-sm" onclick="openMinistryAttendance('<?= $m['id'] ?>')" title="Mark Attendance"><i class="ph ph-clipboard-text"></i></button>
                <button class="btn btn-outline btn-sm" onclick="openMinistryBulkMessage('<?= $m['id'] ?>', '<?= htmlspecialchars(addslashes($m['name'])) ?>', '<?= $m['icon'] ?>', <?= $m['count'] ?>)" title="Message Ministry"><i class="ph ph-chat-centered-dots"></i></button>
                <button class="btn btn-outline btn-sm" onclick="manageMinistry('<?= $m['id'] ?>')">Manage</button>
                <button class="btn btn-danger-soft btn-sm" onclick="confirmDeleteMinistry('<?= $m['id'] ?>', '<?= htmlspecialchars(addslashes($m['name'])) ?>')" title="Delete Ministry">
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

  <form method="POST" action="handlers/ministry_handler.php" id="removeMinistryMemberForm" style="display:none;">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="remove_ministry_member">
    <input type="hidden" name="ministry_id" id="removeMinId">
    <input type="hidden" name="member_id" id="removeMemId">
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

    const defaultData = { id: 0, icon: '✝️', bg: 'var(--gold-pale)', title: 'Ministry', desc: 'Description', meeting_time: '', count: 0, att: '0%', sessions: 0, members: [], ministry_members: [], recent_sessions: [], history: [], chart_data: [] };

    function manageMinistry(id, openTab) {
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
        chart.innerHTML = m.chart_data.map((data) => {
          const bg = data.type === 'Ministry Meeting' ? '#1E40AF' : '#F87171';
          return `<div style="flex:1;background:${bg};height:${Math.max(10, data.pct)}%;border-radius:4px 4px 0 0;" title="${data.pct}% Attendance (${data.type})"></div>`;
        }).join('');
      } else {
        chart.innerHTML = '<div style="color:var(--muted);font-size:12px;width:100%;text-align:center;padding-bottom:20px;">No attendance data available</div>';
      }

      // Populate Edit Form
      document.getElementById('edit_mId').value = id;
      document.getElementById('edit_mName').value = m.title;
      document.getElementById('edit_mDesc').value = m.desc;
      document.getElementById('edit_mMeetingTime').value = m.meeting_time || '';

      // Populate Members List
      const list = document.getElementById('mMembersList');
      list.innerHTML = m.members.length ? m.members.map(mem => `
        <tr style="border-bottom:1px solid var(--border);">
          <td style="padding:8px;font-weight:500;">${mem.n}</td>
          <td style="padding:8px;color:var(--muted);">${mem.r}</td>
          <td style="padding:8px;color:var(--muted);">${mem.d}</td>
          <td style="padding:8px;text-align:right;">
             <button type="button" class="btn-icon" style="color:var(--gold);" onclick="openEditMinistryMember('${mem.minId}', '${mem.mId}')" title="Edit Role"><i class="ph ph-pencil-simple"></i></button>
             <button type="button" class="btn-icon" style="color:#DC2626;" onclick="confirmRemoveMinistryMember('${mem.minId}', '${mem.mId}', '${mem.n}')" title="Remove Member"><i class="ph ph-trash"></i></button>
          </td>
        </tr>
      `).join('') : '<tr><td colspan="4" style="padding:20px;text-align:center;color:var(--muted);">No members assigned</td></tr>';

      // Populate Attendance Tab
      populateAttendanceTab(id, m);

      // Reset Tabs — open the specified tab or default to Overview
      const targetPane = openTab || 'mOverview';
      document.querySelectorAll('#manageMinistryModal .tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('#manageMinistryModal .tab-pane').forEach(p => {
        p.style.display = 'none';
        p.classList.remove('active');
      });

      // Activate correct tab button
      const tabs = document.querySelectorAll('#manageMinistryModal .tab');
      const paneIds = ['mOverview', 'mMembers', 'mAttendancePane', 'mHistory', 'mEdit'];
      const tabIdx = paneIds.indexOf(targetPane);
      if (tabIdx >= 0 && tabs[tabIdx]) tabs[tabIdx].classList.add('active');
      else tabs[0].classList.add('active');

      const pane = document.getElementById(targetPane) || document.getElementById('mOverview');
      pane.style.display = 'block';
      pane.classList.add('active');

      openModal('manageMinistryModal');
    }

    function openMinistryAttendance(id) {
      manageMinistry(id, 'mAttendancePane');
    }

    function populateAttendanceTab(id, m) {
      // Set ministry_id in the attendance form
      document.getElementById('att_ministryId').value = id;

      // Stats
      document.getElementById('attPresent').textContent = m.total_present || '0';
      document.getElementById('attAbsent').textContent = m.total_absent || '0';
      document.getElementById('attRate').textContent = m.att;

      // Member checklist
      const attList = document.getElementById('attMemberList');
      const members = m.ministry_members || [];
      if (members.length > 0) {
        attList.innerHTML = members.map(mem => `
          <label class="att-row" style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#F1F5F9;border-radius:8px;cursor:pointer;">
            <span style="font-size:13px;font-weight:500;">${mem.name} (${mem.code})</span>
            <input type="checkbox" name="present_members[]" value="${mem.id}" class="att-member">
          </label>
        `).join('');
      } else {
        attList.innerHTML = '<div style="padding:20px;text-align:center;color:var(--muted);font-size:13px;">No members assigned to this ministry</div>';
      }

      // Reset select all checkbox
      const markAllChk = document.getElementById('attMarkAllChk');
      if (markAllChk) markAllChk.checked = false;

      // Hide record form by default
      document.getElementById('attRecordForm').style.display = 'none';
      document.getElementById('attShowFormBtn').style.display = 'inline-flex';

      // Populate member filter dropdown
      const filterMember = document.getElementById('filterAttMember');
      if (members.length > 0) {
        filterMember.innerHTML = '<option value="">All Members</option>' + members.map(mem => `<option value="${mem.id}">${mem.name} (${mem.code})</option>`).join('');
      } else {
        filterMember.innerHTML = '<option value="">All Members</option>';
      }

      // Initial fetch of records
      clearAttFilters(true); // true = skip fetching, just clear
      fetchAttendanceRecords();
    }

    async function fetchAttendanceRecords() {
      const ministryId = document.getElementById('att_ministryId').value;
      const date = document.getElementById('filterAttDate').value;
      const type = document.getElementById('filterAttType').value;
      const memberId = document.getElementById('filterAttMember').value;

      const tbody = document.getElementById('attRecordsTable');
      tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--muted);">Loading records...</td></tr>';

      try {
        const res = await fetch(`ajax/get_attendance_records.php?ministry_id=${ministryId}&date=${date}&session_type=${encodeURIComponent(type)}&member_id=${memberId}`);
        const data = await res.json();
        
        // Update stats
        if (data.stats) {
          document.getElementById('attPresent').textContent = data.stats.present;
          document.getElementById('attAbsent').textContent = data.stats.absent;
          document.getElementById('attRate').textContent = data.stats.rate;
        }

        const records = data.records || [];
        if (records.length > 0) {
          tbody.innerHTML = records.map(r => `
            <tr>
              <td style="padding:10px 12px;border-bottom:1px solid var(--border);">${r.date}</td>
              <td style="padding:10px 12px;border-bottom:1px solid var(--border);">${r.type}</td>
              <td style="padding:10px 12px;border-bottom:1px solid var(--border);font-weight:500;">${r.member} <span style="color:var(--muted);font-size:11px;">(${r.code})</span></td>
              <td style="padding:10px 12px;border-bottom:1px solid var(--border);">
                <span class="badge ${r.status === 'Present' ? 'badge-green' : 'badge-red'}">${r.status}</span>
              </td>
            </tr>
          `).join('');
        } else {
          tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--muted);">No records found matching filters.</td></tr>';
        }
      } catch (err) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--muted);">Error loading records.</td></tr>';
      }
    }

    function clearAttFilters(skipFetch = false) {
      document.getElementById('filterAttDate').value = '';
      document.getElementById('filterAttType').value = '';
      document.getElementById('filterAttMember').value = '';
      if (!skipFetch) fetchAttendanceRecords();
    }

    function toggleAttRecordForm() {
      const form = document.getElementById('attRecordForm');
      const btn = document.getElementById('attShowFormBtn');
      if (form.style.display === 'none') {
        form.style.display = 'block';
        btn.style.display = 'none';
      } else {
        form.style.display = 'none';
        btn.style.display = 'inline-flex';
      }
    }

    function filterAttMembers() {
      const q = document.getElementById('attMemberSearch').value.toLowerCase();
      document.querySelectorAll('#attMemberList .att-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? 'flex' : 'none';
      });
      document.getElementById('attMarkAllChk').checked = false;
    }

    function toggleAttMarkAll(chk) {
      const isChecked = chk.checked;
      document.querySelectorAll('#attMemberList .att-row').forEach(row => {
        if (row.style.display !== 'none') {
          const checkbox = row.querySelector('.att-member');
          if (checkbox) checkbox.checked = isChecked;
        }
      });
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

    function confirmRemoveMinistryMember(minId, memId, name) {
      if(confirm('Are you sure you want to remove ' + name + ' from this ministry?')) {
         document.getElementById('removeMinId').value = minId;
         document.getElementById('removeMemId').value = memId;
         document.getElementById('removeMinistryMemberForm').submit();
      }
    }

    function openEnrolMinistryMember() {
      const minId = document.getElementById('edit_mId').value;
      if (!minId) return;
      document.getElementById('enrol_ministryId').value = minId;
      document.getElementById('enrol_mHeadDisplay').value = '';
      document.getElementById('enrol_mHeadId').value = '';
      openModal('enrolMinistryMemberModal');
    }

    function openEditMinistryMember(minId, mId) {
      const minData = mData[minId];
      if (!minData) return;
      const mem = minData.members.find(x => x.mId == mId);
      if (!mem) return;

      document.getElementById('edit_min_ministryId').value = minId;
      document.getElementById('edit_min_memberId').value = mId;
      document.getElementById('edit_min_memberName').value = mem.n;
      document.getElementById('edit_min_role').value = mem.r;
      document.getElementById('edit_min_notes').value = mem.notes || '';

      openModal('editMinistryMemberModal');
    }

    function downloadMinistryReport() {
      const id = document.getElementById('edit_mId').value;
      if (id) {
        window.location.href = `export_ministry_report.php?id=${id}`;
      }
    }
  </script>
</body>
</html>
