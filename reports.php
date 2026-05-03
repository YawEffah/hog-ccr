<?php
/**
 * Reports & Analytics Page
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

$pageTitle  = 'Reports';
$activePage = 'reports';

$db = getDB();

// ── Membership Growth Stats ──────────────────────────────────────────────────
$total_members = (int)$db->query("SELECT COUNT(*) FROM members")->fetchColumn();
$active_members = (int)$db->query("SELECT COUNT(*) FROM members WHERE status='Active'")->fetchColumn();

// Heights for Q1-Q4 (Based on join dates this year)
$currentYear = date('Y');
$growth_stats = [
    'total'       => $total_members,
    'active'      => $active_members,
    'visitors'    => (int)$db->query("SELECT COUNT(*) FROM members WHERE status='Visitor'")->fetchColumn(),
    'percent_yoy' => '+0%', // Placeholder for complex logic
];

for($q=1; $q<=4; $q++) {
    $mStart = ($q-1)*3 + 1;
    $mEnd   = $q*3;
    $count = (int)$db->query("SELECT COUNT(*) FROM members WHERE YEAR(created_at) = $currentYear AND MONTH(created_at) BETWEEN $mStart AND $mEnd")->fetchColumn();
    $growth_stats["q{$q}_height"] = min(100, $count * 5 + 20); // Scaled for display
}

// ── Annual Finance Summary ───────────────────────────────────────────────────
$annual_finance = [];
$ytd_total_val = 0;
for ($m = 1; $m <= 12; $m++) {
    if ($m > date('n')) break;
    $mDate = date("Y-$m-01");
    $mName = date('M', strtotime($mDate));
    
    $stmt = $db->prepare("SELECT SUM(amount) FROM finance_transactions WHERE MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?");
    $stmt->execute([$m, $currentYear]);
    $amt = (float)$stmt->fetchColumn();
    
    $targetStmt = $db->prepare("SELECT target_amount FROM finance_targets WHERE target_month = ?");
    $targetStmt->execute([date('Y-m-01', strtotime($mDate))]);
    $target = (float)$targetStmt->fetchColumn() ?: 10000;
    
    $percent = round(($amt / $target) * 100);
    $annual_finance[] = [
        'month'             => $mName,
        'amount'            => number_format($amt, 0),
        'target_percent'    => $percent,
        'bar_width_percent' => min(100, $percent),
        'is_success'        => $amt >= $target
    ];
    $ytd_total_val += $amt;
}
$ytd_total = number_format($ytd_total_val);

// ── Ministry Performance ─────────────────────────────────────────────────────
$minStmt = $db->query(
    "SELECT name, 
            (SELECT COUNT(*) FROM attendance_records ar 
             JOIN members m ON ar.member_id = m.id 
             WHERE m.ministry_id = min.id AND ar.status='Present') as present_count,
            (SELECT COUNT(*) FROM attendance_records ar 
             JOIN members m ON ar.member_id = m.id 
             WHERE m.ministry_id = min.id) as total_count,
            bg_color
     FROM ministries min
     LIMIT 6"
);
$ministry_performance = array_map(function($m) {
    $percent = $m['total_count'] > 0 ? round(($m['present_count'] / $m['total_count']) * 100) : 0;
    return [
        'label'     => $m['name'],
        'percent'   => $percent,
        'bar_class' => $m['bg_color']
    ];
}, $minStmt->fetchAll());

// ── Recent Activity ──────────────────────────────────────────────────────────
$activityStmt = $db->query(
    "SELECT l.*, a.name as admin_name 
     FROM activity_log l
     LEFT JOIN admins a ON l.admin_id = a.id
     ORDER BY l.created_at DESC 
     LIMIT 5"
);
$rawActs = $activityStmt->fetchAll();
$recent_activity = array_map(function($a, $index) use ($rawActs) {
    return [
        'title'     => $a['action'],
        'details'   => date('M j \a\t g:ia', strtotime($a['created_at'])) . ' · by ' . ($a['admin_name'] ?? 'System'),
        'dot_color' => 'var(--gold)',
        'is_last'   => $index === count($rawActs) - 1
    ];
}, $rawActs, array_keys($rawActs));

?>
<!DOCTYPE html>
<html lang="en">

<?php require_once 'includes/head.php'; ?>

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
          <select class="form-control" style="width:140px;padding:8px 12px;">
            <option><?= date('Y') ?></option>
            <option><?= date('Y', strtotime('-1 year')) ?></option>
          </select>
          <button class="btn btn-outline btn-sm"><i class="ph ph-file-pdf"></i> Export PDF</button>
          <button class="btn btn-primary btn-sm"><i class="ph ph-file-xls"></i> Export Excel</button>
        </div>
      </div>
      <div class="content">
        <div class="grid-2" style="gap:24px; margin-bottom:24px;">
          <!-- Membership Growth -->
          <div class="card">
            <div class="card-header">
              <h3>Membership Growth</h3><span class="badge badge-green"><?= $growth_stats['percent_yoy'] ?> YoY</span>
            </div>
            <div class="card-body">
              <div style="display:flex;gap:4px;align-items:flex-end;height:100px;margin-bottom:10px;">
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
                  <div style="width:100%;background:var(--gold);border-radius:3px 3px 0 0;height:<?= $growth_stats['q1_height'] ?>px;"></div>
                  <div style="font-size:10px;color:var(--muted);">Q1</div>
                </div>
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
                  <div style="width:100%;background:var(--gold);border-radius:3px 3px 0 0;height:<?= $growth_stats['q2_height'] ?>px;opacity:0.8;"></div>
                  <div style="font-size:10px;color:var(--muted);">Q2</div>
                </div>
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
                  <div style="width:100%;background:var(--gold);border-radius:3px 3px 0 0;height:<?= $growth_stats['q3_height'] ?>px;opacity:0.85;"></div>
                  <div style="font-size:10px;color:var(--muted);">Q3</div>
                </div>
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
                  <div style="width:100%;background:var(--gold);border-radius:3px 3px 0 0;height:<?= $growth_stats['q4_height'] ?>px;"></div>
                  <div style="font-size:10px;color:var(--muted);">Q4</div>
                </div>
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
                  <div style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--gold);">
                    <?= $growth_stats['visitors'] ?></div>
                  <div style="font-size:11px;color:var(--muted);">Visitors</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Financial Summary -->
          <div class="card">
            <div class="card-header">
              <h3>Annual Finance</h3><span class="badge badge-green">On Track</span>
            </div>
            <div class="card-body">
              <div style="display:flex;flex-direction:column;gap:12px;">
                <?php foreach ($annual_finance as $fi): ?>
                <div class="summary-row" style="<?= $fi === end($annual_finance) ? 'border-bottom: none;' : '' ?>">
                  <span style="font-size:13px;color:var(--mid);"><?= $fi['month'] ?></span>
                  <div style="display:flex;align-items:center;gap:12px;flex:1;margin-left:16px;">
                    <div style="flex:1;height:8px;border-radius:10px;background:#EDE8DF;overflow:hidden;">
                      <div style="height:100%;width:<?= $fi['bar_width_percent'] ?>%;background:var(--gold);border-radius:10px;"></div>
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
        </div>

        <div class="grid-2" style="gap:24px;">
          <!-- Ministry Performance -->
          <div class="card">
            <div class="card-header">
              <h3>Ministry Performance</h3>
            </div>
            <div class="card-body">
              <div style="display:flex;flex-direction:column;gap:14px;">
                <?php foreach ($ministry_performance as $mp): ?>
                <div>
                  <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;"><span
                      style="font-weight:500;"><?= htmlspecialchars($mp['label']) ?></span><span style="color:var(--muted);"><?= $mp['percent'] ?>%</span></div>
                  <div style="height:8px;border-radius:10px;background:#EDE8DF;">
                    <div style="height:100%;width:<?= $mp['percent'] ?>%;background:<?= $mp['bar_class'] ?>;border-radius:10px;"></div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Activity log -->
          <div class="card">
            <div class="card-header">
              <h3>Recent Activity</h3>
            </div>
            <div class="card-body">
              <div style="display:flex;flex-direction:column;">
                <?php foreach ($recent_activity as $act): ?>
                <div class="timeline-item" style="<?= $act['is_last'] ? 'padding-bottom:0;' : '' ?>">
                  <div class="timeline-dot" style="<?= isset($act['dot_color']) ? "background:{$act['dot_color']};" : '' ?>"></div>
                  <div>
                    <div style="font-size:13px;font-weight:500;color:var(--deep2);"><?= htmlspecialchars($act['title']) ?></div>
                    <div style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($act['details']) ?></div>
                  </div>
                </div>
                <?php endforeach; ?>
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
