<?php
/**
 * Reports & Analytics Page
 */
require_once 'includes/auth.php';
requireAuth();

$pageTitle = 'Reports';
$activePage = 'reports';


// Mock data for initial refactor (Backend team will replace these)
$growth_stats = $growth_stats ?? [
    'total' => 487,
    'active' => 442,
    'visitors' => 45,
    'q1_height' => 62,
    'q2_height' => 74,
    'q3_height' => 88,
    'q4_height' => 100,
    'percent_yoy' => '+12%'
];

$annual_finance = $annual_finance ?? [
    ['month' => 'Jan', 'amount' => '21,000', 'target_percent' => 70, 'bar_width_percent' => 70, 'is_success' => false],
    ['month' => 'Feb', 'amount' => '22,500', 'target_percent' => 75, 'bar_width_percent' => 75, 'is_success' => false],
    ['month' => 'Mar', 'amount' => '22,780', 'target_percent' => 76, 'bar_width_percent' => 76, 'is_success' => false],
    ['month' => 'Apr', 'amount' => '24,550', 'target_percent' => 82, 'bar_width_percent' => 82, 'is_success' => true]
];

$ytd_total = $ytd_total ?? '90,830';

$ministry_performance = $ministry_performance ?? [
    ['label' => 'Executives', 'percent' => 92, 'bar_class' => 'var(--deep3)'],
    ['label' => 'Intercessory', 'percent' => 85, 'bar_class' => 'var(--deep)'],
    ['label' => 'Music Ministry', 'percent' => 78, 'bar_class' => 'var(--gold)'],
    ['label' => 'Youth Wing', 'percent' => 72, 'bar_class' => '#2E7D57'],
    ['label' => 'Prayer Group', 'percent' => 68, 'bar_class' => 'var(--gold)'],
    ['label' => 'Evangelism', 'percent' => 65, 'bar_class' => 'var(--gold)']
];

$recent_activity = $recent_activity ?? [
    ['title' => 'Attendance recorded — Sunday Service', 'details' => '312 present · Apr 6 at 10:34am · by Secretary', 'dot_color' => 'var(--gold)', 'is_last' => false],
    ['title' => 'New member registered', 'details' => 'Michael Boateng · Apr 3 · by Secretary', 'dot_color' => 'var(--deep)', 'is_last' => false],
    ['title' => 'Tithe received — GH₵ 350', 'details' => 'Abena Kusi · Apr 6 · by Finance Sec.', 'dot_color' => '#2E7D57', 'is_last' => false],
    ['title' => 'Event created — Easter Convention', 'details' => 'Apr 19 · by Pastor Adu', 'dot_color' => 'var(--deep3)', 'is_last' => false],
    ['title' => 'Announcement published', 'details' => 'Building Fund Drive · Apr 1 · by Admin', 'dot_color' => 'var(--gold)', 'is_last' => true]
];
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
