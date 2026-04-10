<?php
/**
 * Ministries & Groups Page
 * 
 * BACKEND CONTRACT:
 * Expected variables:
 * @var array $ministries [{ id, icon, bg_color, title, desc, members_count, badge_class, attendance_avg, bar_color }]
 * @var array $ministry_data { id => { icon, bg, title, desc, count, att, sessions, members: [{n, r, d}], history: [{e, d}] } }
 */

$pageTitle = 'Ministries';
$activePage = 'ministries';

// Mock data for initial refactor (Backend team will replace these)
$ministries = $ministries ?? [
    ['id' => 'music', 'icon' => '🎵', 'bg_color' => 'var(--gold-pale)', 'title' => 'Music Ministry', 'desc' => 'Worship & praise team', 'members_count' => 28, 'badge_class' => 'badge-purple', 'attendance_avg' => 78, 'bar_color' => 'var(--gold)'],
    ['id' => 'intercessory', 'icon' => '🙏', 'bg_color' => '#EEF2FF', 'title' => 'Intercessory', 'desc' => 'Prayer warriors team', 'members_count' => 34, 'badge_class' => 'badge-blue', 'attendance_avg' => 85, 'bar_color' => 'var(--deep)'],
    ['id' => 'evangelism', 'icon' => '🌍', 'bg_color' => '#FEF9EC', 'title' => 'Evangelism', 'desc' => 'Outreach & missions', 'members_count' => 22, 'badge_class' => 'badge-yellow', 'attendance_avg' => 65, 'bar_color' => 'var(--gold)'],
    ['id' => 'youth', 'icon' => '⚡', 'bg_color' => '#ECFDF5', 'title' => 'Youth Wing', 'desc' => 'Young adults 13–35', 'members_count' => 48, 'badge_class' => 'badge-green', 'attendance_avg' => 72, 'bar_color' => 'var(--success)'],
    ['id' => 'prayer', 'icon' => '✝️', 'bg_color' => 'var(--gold-pale)', 'title' => 'Prayer Group', 'desc' => 'General prayer cell', 'members_count' => 30, 'badge_class' => 'badge-gray', 'attendance_avg' => 68, 'bar_color' => 'var(--gold)'],
    ['id' => 'execs', 'icon' => '👑', 'bg_color' => '#F5F3FF', 'title' => 'Executives', 'desc' => 'Leadership & governance', 'members_count' => 20, 'badge_class' => 'badge-purple', 'attendance_avg' => 92, 'bar_color' => 'var(--deep3)']
];

// Detail data for the "Manage" modal
$ministry_data = $ministry_data ?? [
    'music' => [
        'icon' => '🎵', 'bg' => 'var(--gold-pale)', 'title' => 'Music Ministry', 'desc' => 'Worship & praise team', 'count' => 28, 'att' => '78%', 'sessions' => 12, 
        'members' => [['n' => 'John Smith', 'r' => 'Lead Singer', 'd' => 'Jan 2023'], ['n' => 'Sarah Mensah', 'r' => 'Pianist', 'd' => 'Mar 2023']], 
        'history' => [['e' => 'Annual Concert', 'd' => '2 weeks ago'], ['e' => 'New Member Induction', 'd' => '1 month ago']]
    ],
    // ... other ministries logic would go here in actual backend
];
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
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <div class="topbar-title">Ministries & Groups</div>
        </div>
        <div class="topbar-actions">
          <button class="btn btn-primary btn-sm" onclick="openModal('addMinistryModal')">+ New Ministry</button>
        </div>
      </div>
      <div class="content">
        <div class="grid-3" style="margin-bottom:24px;">
          <?php foreach ($ministries as $m): ?>
          <div class="ministry-card">
            <div class="ministry-icon" style="background:<?= $m['bg_color'] ?>;"><?= $m['icon'] ?></div>
            <div
              style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--deep2);margin-bottom:4px;">
              <?= htmlspecialchars($m['title']) ?></div>
            <div style="font-size:12px;color:var(--muted);margin-bottom:14px;"><?= htmlspecialchars($m['desc']) ?></div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <span class="badge <?= $m['badge_class'] ?>"><?= $m['members_count'] ?> members</span>
              <button class="btn btn-outline btn-sm" onclick="manageMinistry('<?= $m['id'] ?>')">Manage</button>
            </div>
            <div style="margin-top:12px;height:5px;border-radius:10px;background:#EDE8DF;overflow:hidden;">
              <div style="height:100%;width:<?= $m['attendance_avg'] ?>%;background:<?= $m['bar_color'] ?>;border-radius:10px;"></div>
            </div>
            <div style="font-size:11px;color:var(--muted);margin-top:4px;"><?= $m['attendance_avg'] ?>% attendance avg</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </main>

  <?php require_once 'includes/modals/ministry_modals.php'; ?>

  <script src="assets/js/main.js"></script>
  <script>
    // Mock data for ministries detail view (Backend team should ideally pass this or handle via AJAX)
    const mData = <?php echo json_encode($ministry_data); ?>;
    
    // Fallback for non-music ministries in this mock (since I only defined music detail above)
    const defaultData = { icon: '✝️', bg: 'var(--gold-pale)', title: 'Ministry', desc: 'Description', count: 0, att: '0%', sessions: 0, members: [], history: [] };

    function manageMinistry(id) {
      const m = mData[id] || { ...defaultData, title: id.charAt(0).toUpperCase() + id.slice(1) };
      if (!m) return;

      document.getElementById('mIcon').textContent = m.icon;
      document.getElementById('mIcon').style.background = m.bg;
      document.getElementById('mTitle').textContent = m.title;
      document.getElementById('mSubtitle').textContent = m.desc;
      document.getElementById('mCount').textContent = m.count;
      document.getElementById('mAttendance').textContent = m.att;
      document.getElementById('mSessions').textContent = m.sessions;

      const list = document.getElementById('mMembersList');
      list.innerHTML = m.members.map(mem => `
        <tr style="border-bottom:1px solid var(--border);">
          <td style="padding:8px;font-weight:500;">${mem.n}</td>
          <td style="padding:8px;color:var(--muted);">${mem.r}</td>
          <td style="padding:8px;color:var(--muted);">${mem.d}</td>
        </tr>
      `).join('');

      const timeline = document.getElementById('mTimeline');
      timeline.innerHTML = m.history.map(ev => `
        <div style="display:flex;gap:12px;align-items:flex-start;">
          <div style="width:8px;height:8px;border-radius:50%;background:var(--primary);margin-top:6px;flex-shrink:0;"></div>
          <div>
            <div style="font-size:13px;font-weight:500;">${ev.e}</div>
            <div style="font-size:11px;color:var(--muted);">${ev.d}</div>
          </div>
        </div>
      `).join('');

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
  </script>
</body>

</html>
