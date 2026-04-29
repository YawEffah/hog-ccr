<?php
/**
 * Welfare Management Page
 */
require_once 'includes/auth.php';
requireAuth();

$pageTitle  = 'Welfare';
$activePage = 'welfare';

// Mock data — Backend team will replace these with real DB queries
$welfare_stats = $welfare_stats ?? [
    'total_members'    => 24,
    'collected_month'  => '480.00',
    'active_payers'    => 18,
    'pending'          => 6,
];

$welfare_members = $welfare_members ?? [
    ['id' => 'W-001', 'member_id' => 'CCR-001', 'name' => 'Abena Kusi',      'initials' => 'AK', 'phone' => '0244-123-456', 'enrolled' => 'Jan 2025', 'last_pay' => 'Apr 15, 2026', 'total' => '480.00', 'status' => 'Active',  'avatar_bg' => 'var(--gold-pale)', 'avatar_color' => 'var(--gold)'],
    ['id' => 'W-002', 'member_id' => 'CCR-002', 'name' => 'Kwame Ofori',     'initials' => 'KO', 'phone' => '0200-987-654', 'enrolled' => 'Feb 2025', 'last_pay' => 'Apr 15, 2026', 'total' => '360.00', 'status' => 'Active',  'avatar_bg' => '#EEF2FF',          'avatar_color' => 'var(--deep)'],
    ['id' => 'W-003', 'member_id' => 'CCR-004', 'name' => 'Michael Boateng', 'initials' => 'MB', 'phone' => '0277-456-123', 'enrolled' => 'Mar 2025', 'last_pay' => 'Mar 1, 2026',  'total' => '140.00', 'status' => 'Arrears', 'avatar_bg' => '#ECFDF5',          'avatar_color' => '#2E7D57'],
    ['id' => 'W-004', 'member_id' => 'CCR-006', 'name' => 'Pastor Adu',      'initials' => 'PA', 'phone' => '0201-000-001', 'enrolled' => 'Jan 2025', 'last_pay' => 'Apr 28, 2026', 'total' => '600.00', 'status' => 'Active',  'avatar_bg' => '#EEF2FF',          'avatar_color' => 'var(--deep)'],
    ['id' => 'W-005', 'member_id' => 'CCR-005', 'name' => 'Efua Asare',      'initials' => 'EA', 'phone' => '0244-678-901', 'enrolled' => 'Apr 2025', 'last_pay' => 'Apr 10, 2026', 'total' => '240.00', 'status' => 'Active',  'avatar_bg' => 'var(--gold-pale)', 'avatar_color' => 'var(--gold)'],
];

$welfare_contributions = $welfare_contributions ?? [
    ['member' => 'Abena Kusi',      'member_id' => 'CCR-001', 'amount' => '20.00', 'method' => 'MoMo',          'date' => 'Apr 15, 2026', 'reference' => 'TXN8821', 'notif_sent' => true],
    ['member' => 'Kwame Ofori',     'member_id' => 'CCR-002', 'amount' => '20.00', 'method' => 'MoMo',          'date' => 'Apr 15, 2026', 'reference' => 'TXN8830', 'notif_sent' => true],
    ['member' => 'Pastor Adu',      'member_id' => 'CCR-006', 'amount' => '50.00', 'method' => 'Bank Transfer', 'date' => 'Apr 28, 2026', 'reference' => 'BNK0042', 'notif_sent' => true],
    ['member' => 'Efua Asare',      'member_id' => 'CCR-005', 'amount' => '20.00', 'method' => 'Cash',          'date' => 'Apr 10, 2026', 'reference' => '',        'notif_sent' => false],
    ['member' => 'Michael Boateng', 'member_id' => 'CCR-004', 'amount' => '20.00', 'method' => 'Cash',          'date' => 'Mar 1, 2026',  'reference' => '',        'notif_sent' => false],
    ['member' => 'Abena Kusi',      'member_id' => 'CCR-001', 'amount' => '20.00', 'method' => 'Cash',          'date' => 'Mar 12, 2026', 'reference' => '',        'notif_sent' => false],
];
?>
<!DOCTYPE html>
<html lang="en">

<?php require_once 'includes/head.php'; ?>

<body>

  <?php require_once 'includes/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main id="main">

    <div id="page-welfare" class="page">
      <div class="topbar">
        <div style="display:flex;align-items:center;">
          <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="ph ph-list"></i>
          </button>
          <div class="topbar-title">Welfare</div>
        </div>
        <div class="topbar-actions">
          <select class="form-control" style="width:140px;padding:8px 12px;" id="welfareMonthFilter">
            <option><?= date('F Y') ?></option>
            <option><?= date('F Y', strtotime('-1 month')) ?></option>
            <option><?= date('F Y', strtotime('-2 months')) ?></option>
          </select>
          <button class="btn btn-outline btn-sm" onclick="openSendWelfareMessage()">
            <i class="ph ph-paper-plane-tilt"></i> Send Message
          </button>
          <button class="btn btn-outline btn-sm" id="notifBtn" onclick="toggleNotifications()">
            <i class="ph ph-bell"></i>
            <span class="notif-dot"></span>
          </button>
          <?php include 'includes/notifications.php'; ?>
          <button class="btn btn-primary btn-sm" onclick="openModal('enrolWelfareModal')">
            <i class="ph ph-hand-heart"></i> Enrol Member
          </button>
        </div>
      </div>

      <div class="content">

        <!-- Stat Cards -->
        <div class="grid-4" style="margin-bottom:24px;">
          <div class="stat-card">
            <div class="accent-bar" style="background:#0D9488;"></div>
            <div class="label">Welfare Members</div>
            <div class="value"><?= $welfare_stats['total_members'] ?></div>
            <div class="change" style="color:#0D9488;">Enrolled members</div>
            <div class="icon-bg" style="background:#CCFBF1;">
              <i class="ph ph-hand-heart" style="color:#0D9488;font-size:20px;"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--success);"></div>
            <div class="label">Collected This Month</div>
            <div class="value" style="font-size:28px;">GH₵<?= $welfare_stats['collected_month'] ?></div>
            <div class="change" style="color:var(--success);">↑ from last month</div>
            <div class="icon-bg" style="background:#ECFDF5;">
              <i class="ph ph-money" style="color:var(--success);font-size:20px;"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--deep);"></div>
            <div class="label">Active Payers</div>
            <div class="value"><?= $welfare_stats['active_payers'] ?></div>
            <div class="change" style="color:var(--deep);">Paid this month</div>
            <div class="icon-bg" style="background:#EEF2FF;">
              <i class="ph ph-check-circle" style="color:var(--deep);font-size:20px;"></i>
            </div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:#F59E0B;"></div>
            <div class="label">Pending / Arrears</div>
            <div class="value"><?= $welfare_stats['pending'] ?></div>
            <div class="change" style="color:#F59E0B;">Haven't paid yet</div>
            <div class="icon-bg" style="background:#FFFBEB;">
              <i class="ph ph-warning" style="color:#F59E0B;font-size:20px;"></i>
            </div>
          </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tabs" id="welfareTabs" style="margin-bottom:20px;background:white;border:1px solid #EDE8DF;border-radius:10px;padding:4px;display:inline-flex;">
          <button class="tab active" id="tabMembersBtn" onclick="switchWelfareTab('members')" style="padding:7px 20px;font-size:13px;">
            <i class="ph ph-users"></i> Members
          </button>
          <button class="tab" id="tabContribBtn" onclick="switchWelfareTab('contributions')" style="padding:7px 20px;font-size:13px;">
            <i class="ph ph-receipt"></i> Contributions
          </button>
        </div>

        <!-- MEMBERS TAB -->
        <div id="welfareMembersTab">
          <div class="table-wrap">
            <div style="padding:16px 20px;border-bottom:1px solid #EDE8DF;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
              <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:280px;">
                <div class="search-wrap" style="flex:1;max-width:300px;">
                  <i class="ph ph-magnifying-glass"></i>
                  <input class="search-input" id="welfareSearch" placeholder="Search member…" oninput="filterWelfareMembers()" style="width:100%;">
                </div>
                <select class="form-control" id="welfareStatusFilter" style="width:130px;padding:9px 14px;" onchange="filterWelfareMembers()">
                  <option value="All">All Status</option>
                  <option value="Active">Active</option>
                  <option value="Arrears">Arrears</option>
                </select>
              </div>
              <div style="display:flex;align-items:center;gap:10px;">
                <button class="btn btn-outline btn-sm" onclick="openSendWelfareMessage()">
                  <i class="ph ph-paper-plane-tilt"></i> Message Payers
                </button>
                <span style="font-size:13px;color:var(--muted);">Total: <strong id="welfareMemberCount"><?= count($welfare_members) ?></strong></span>
              </div>
            </div>
            <div class="table-responsive">
              <table id="welfareMembersTable">
                <thead>
                  <tr>
                    <th>Member</th>
                    <th>Phone</th>
                    <th>Enrolled</th>
                    <th>Last Payment</th>
                    <th>Total Contributed</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="welfareMembersTbody">
                  <?php foreach ($welfare_members as $wm): ?>
                  <?php
                    $statusBadge = $wm['status'] === 'Active' ? 'badge-welfare' : 'badge-red';
                  ?>
                  <tr>
                    <td>
                      <div style="display:flex;align-items:center;gap:12px;">
                        <div class="avatar" style="background:<?= $wm['avatar_bg'] ?>;color:<?= $wm['avatar_color'] ?>;"><?= $wm['initials'] ?></div>
                        <div>
                          <div style="font-weight:500;"><?= htmlspecialchars($wm['name']) ?></div>
                          <div style="font-size:11px;color:var(--muted);"><?= $wm['member_id'] ?></div>
                        </div>
                      </div>
                    </td>
                    <td style="font-size:13px;"><?= $wm['phone'] ?></td>
                    <td style="font-size:12px;color:var(--muted);"><?= $wm['enrolled'] ?></td>
                    <td style="font-size:13px;"><?= $wm['last_pay'] ?></td>
                    <td style="font-weight:600;color:#0D9488;">GH₵ <?= $wm['total'] ?></td>
                    <td><span class="badge <?= $statusBadge ?>"><?= $wm['status'] ?></span></td>
                    <td>
                      <div style="display:flex;gap:6px;">
                        <button class="btn-icon" onclick="viewWelfareMember('<?= $wm['id'] ?>')" title="View details">
                          <i class="ph ph-eye"></i>
                        </button>
                        <button class="btn-icon" onclick="openRecordPaymentFor('<?= $wm['id'] ?>','<?= htmlspecialchars($wm['name']) ?>')" title="Record payment" style="background:#F0FDFA;color:#0D9488;">
                          <i class="ph ph-plus"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- CONTRIBUTIONS TAB -->
        <div id="welfareContribTab" style="display:none;">
          <div class="table-wrap">
            <div style="padding:16px 20px;border-bottom:1px solid #EDE8DF;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
              <div style="display:flex;align-items:center;gap:12px;">
                <div class="search-wrap" style="max-width:280px;">
                  <i class="ph ph-magnifying-glass"></i>
                  <input class="search-input" id="contribSearch" placeholder="Search contributions…" oninput="filterContribs()" style="width:280px;">
                </div>
              </div>
              <div style="display:flex;gap:8px;">
                <button class="btn btn-outline btn-sm"><i class="ph ph-export"></i> Export CSV</button>
                <button class="btn btn-outline btn-sm" onclick="openSendWelfareMessage()">
                  <i class="ph ph-paper-plane-tilt"></i> Bulk Message
                </button>
                <button class="btn btn-primary btn-sm" onclick="openModal('recordWelfarePaymentModal')">+ Record Contribution</button>
              </div>
            </div>
            <div class="table-responsive">
              <table id="contribTable">
                <thead>
                  <tr>
                    <th>Member</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Notif. Sent</th>
                    <th>Receipt</th>
                  </tr>
                </thead>
                <tbody id="contribTbody">
                  <?php foreach ($welfare_contributions as $c): ?>
                  <tr>
                    <td>
                      <div style="font-weight:500;"><?= htmlspecialchars($c['member']) ?></div>
                      <div style="font-size:11px;color:var(--muted);"><?= $c['member_id'] ?></div>
                    </td>
                    <td style="font-weight:600;color:#0D9488;">GH₵ <?= $c['amount'] ?></td>
                    <td><span class="badge badge-gray"><?= $c['method'] ?></span></td>
                    <td style="font-size:12px;color:var(--muted);"><?= $c['date'] ?></td>
                    <td style="font-size:12px;color:var(--muted);"><?= $c['reference'] ?: '—' ?></td>
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
                    <td>
                      <button class="btn btn-outline btn-sm" title="View Receipt"><i class="ph ph-receipt"></i></button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Breakdown card -->
          <div style="margin-top:20px;" class="grid-2" style="gap:24px;">
            <div class="card">
              <div class="card-header">
                <h3>Payment Method Breakdown</h3>
              </div>
              <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:14px;">
                  <?php
                    $methods = [];
                    foreach ($welfare_contributions as $c) {
                        $methods[$c['method']] = ($methods[$c['method']] ?? 0) + (float)str_replace(',', '', $c['amount']);
                    }
                    $total_c = array_sum($methods);
                    $method_colors = ['MoMo' => '#0D9488', 'Cash' => 'var(--gold)', 'Bank Transfer' => 'var(--deep)', 'Cheque' => 'var(--deep3)'];
                    foreach ($methods as $method => $sum):
                        $pct = $total_c > 0 ? round(($sum / $total_c) * 100) : 0;
                  ?>
                  <div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
                      <span style="font-weight:500;"><?= $method ?></span>
                      <span style="color:var(--mid);">GH₵ <?= number_format($sum, 2) ?> <span style="color:var(--muted);font-size:11px;">(<?= $pct ?>%)</span></span>
                    </div>
                    <div style="height:8px;border-radius:10px;background:#EDE8DF;overflow:hidden;">
                      <div style="height:100%;width:<?= $pct ?>%;background:<?= $method_colors[$method] ?? '#0D9488' ?>;border-radius:10px;"></div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /content -->
    </div><!-- /page -->

  </main>

  <?php require_once 'includes/modals/welfare_modals.php'; ?>

  <script src="assets/js/main.js"></script>
  <script>
  /* ---- Tab switcher ---- */
  function switchWelfareTab(tab) {
    const membersTab = document.getElementById('welfareMembersTab');
    const contribTab = document.getElementById('welfareContribTab');
    const btnM = document.getElementById('tabMembersBtn');
    const btnC = document.getElementById('tabContribBtn');

    if (tab === 'members') {
      membersTab.style.display = '';
      contribTab.style.display = 'none';
      btnM.classList.add('active');
      btnC.classList.remove('active');
    } else {
      membersTab.style.display = 'none';
      contribTab.style.display = '';
      btnM.classList.remove('active');
      btnC.classList.add('active');
    }
  }

  /* ---- Member search/filter ---- */
  function filterWelfareMembers() {
    const q      = document.getElementById('welfareSearch').value.toLowerCase();
    const status = document.getElementById('welfareStatusFilter').value;
    let count    = 0;
    document.querySelectorAll('#welfareMembersTbody tr').forEach(row => {
      const text      = row.textContent.toLowerCase();
      const rowStatus = row.querySelector('td:nth-child(6) .badge')?.textContent.trim();
      const matchQ    = text.includes(q);
      const matchS    = status === 'All' || rowStatus === status;
      row.style.display = (matchQ && matchS) ? '' : 'none';
      if (matchQ && matchS) count++;
    });
    document.getElementById('welfareMemberCount').textContent = count;
  }

  /* ---- Contributions search ---- */
  function filterContribs() {
    const q = document.getElementById('contribSearch').value.toLowerCase();
    document.querySelectorAll('#contribTbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  }
  </script>
</body>

</html>
