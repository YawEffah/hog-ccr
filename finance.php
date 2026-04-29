<?php
/**
 * Finance Management Page
 */
require_once 'includes/auth.php';
requireAuth();

$pageTitle = 'Finance';
$activePage = 'finance';


// Mock data for initial refactor (Backend team will replace these)
$finance_stats = $finance_stats ?? [
    'tithes' => '14,820',
    'offerings' => '5,450',
    'donations' => '2,300',
    'total' => '24,550',
    'monthly_target' => '30,000',
    'target_percent' => 82
];

$transactions = $transactions ?? [
    ['member' => 'Abena Kusi', 'type' => 'Tithe', 'type_badge' => 'badge-yellow', 'amount' => '350', 'date' => 'Apr 6'],
    ['member' => 'Kwame Ofori', 'type' => 'Offering', 'type_badge' => 'badge-blue', 'amount' => '50', 'date' => 'Apr 6'],
    ['member' => 'Michael Boateng', 'type' => 'Donation', 'type_badge' => 'badge-green', 'amount' => '500', 'date' => 'Apr 5'],
    ['member' => 'Pastor Adu', 'type' => 'Pledge', 'type_badge' => 'badge-purple', 'amount' => '1,000', 'date' => 'Apr 3'],
    ['member' => 'Efua Asare', 'type' => 'Tithe', 'type_badge' => 'badge-yellow', 'amount' => '280', 'date' => 'Mar 30']
];

$income_breakdown = $income_breakdown ?? [
    ['label' => 'Tithes', 'amount' => '14,820', 'percent' => 60, 'bar_class' => 'var(--gold)'],
    ['label' => 'Offerings', 'amount' => '5,450', 'percent' => 22, 'bar_class' => 'var(--deep)'],
    ['label' => 'Donations', 'amount' => '2,300', 'percent' => 9, 'bar_class' => '#2E7D57'],
    ['label' => 'Pledges', 'amount' => '1,980', 'percent' => 8, 'bar_class' => 'var(--deep3)']
];
?>
<!DOCTYPE html>
<html lang="en">

<?php require_once 'includes/head.php'; ?>

<body>

  <?php require_once 'includes/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main id="main">

    <div id="page-finance" class="page">
      <div class="topbar">
        <div style="display:flex;align-items:center;">
          <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="ph ph-list"></i>
          </button>
          <div class="topbar-title">Finance</div>
        </div>
        <div class="topbar-actions">
          <select class="form-control" style="width:140px;padding:8px 12px;">
            <option><?= date('F Y') ?></option>
            <option><?= date('F Y', strtotime('-1 month')) ?></option>
            <option><?= date('F Y', strtotime('-2 months')) ?></option>
          </select>
          <button class="btn btn-outline btn-sm" id="notifBtn" onclick="toggleNotifications()">
            <i class="ph ph-bell"></i>
          </button>
          <?php include 'includes/notifications.php'; ?>
          <button class="btn btn-outline btn-sm" onclick="openModal('setTargetModal')">
            <i class="ph ph-target"></i> Set Target
          </button>
          <button class="btn btn-primary btn-sm" onclick="openModal('addFinanceModal')">+ Record Transaction</button>
        </div>
      </div>
      <div class="content">
        <div class="grid-4" style="margin-bottom:24px;">
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--gold);"></div>
            <div class="label">Tithes</div>
            <div class="value" style="font-size:28px;">GH₵<?= $finance_stats['tithes'] ?></div>
            <div class="change" style="color:var(--success);">↑ 5% vs March</div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--deep);"></div>
            <div class="label">Offerings</div>
            <div class="value" style="font-size:28px;">GH₵<?= $finance_stats['offerings'] ?></div>
            <div class="change" style="color:var(--deep);">4 Sundays</div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:#2E7D57;"></div>
            <div class="label">Donations</div>
            <div class="value" style="font-size:28px;">GH₵<?= $finance_stats['donations'] ?></div>
            <div class="change" style="color:var(--muted);">3 donors</div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--deep3);"></div>
            <div class="label">Total Income vs Target</div>
            <div class="value" style="font-size:28px;">GH₵<?= $finance_stats['total'] ?></div>
            <div class="change" style="color:var(--success);">
              <strong><?= $finance_stats['target_percent'] ?>%</strong> of GH₵<?= $finance_stats['monthly_target'] ?> target
            </div>
          </div>
        </div>

        <div class="grid-2" style="gap:24px;">
          <div class="card">
            <div class="card-header">
              <h3>Recent Transactions</h3>
              <div>
                <button class="btn btn-outline btn-sm">Export CSV</button>
                <button class="btn btn-outline btn-sm">View All</button>
              </div>
            </div>
            <div class="table-responsive">
              <table>
                <thead>
                  <tr>
                    <th>Member</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Receipt</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($transactions as $tx): ?>
                  <tr>
                    <td style="font-weight:500;"><?= htmlspecialchars($tx['member']) ?></td>
                    <td><span class="badge <?= $tx['type_badge'] ?>"><?= $tx['type'] ?></span></td>
                    <td style="font-weight:600;color:var(--success);">GH₵ <?= $tx['amount'] ?></td>
                    <td style="font-size:12px;color:var(--muted);"><?= $tx['date'] ?></td>
                    <td><button class="btn btn-outline btn-sm" title="View Receipt"><i class="ph ph-receipt"></i></button></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3>Income Breakdown</h3>
            </div>
            <div class="card-body">
              <div style="display:flex;flex-direction:column;gap:16px;">
                <?php foreach ($income_breakdown as $item): ?>
                <div>
                  <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
                    <span style="font-weight:500;"><?= htmlspecialchars($item['label']) ?></span>
                    <span style="color:var(--mid);">GH₵ <?= $item['amount'] ?> <span style="color:var(--muted);font-size:11px;">(<?= $item['percent'] ?>%)</span></span>
                  </div>
                  <div style="height:10px;border-radius:10px;background:#EDE8DF;overflow:hidden;">
                    <div style="height:100%;width:<?= $item['percent'] ?>%;background:<?= $item['bar_class'] ?>;border-radius:10px;"></div>
                  </div>
                </div>
                <?php endforeach; ?>
                <div style="border-top:1px solid #EDE8DF;padding-top:16px;margin-top:4px;">
                  <div style="display:flex;justify-content:space-between;">
                    <span style="font-size:14px;font-weight:700;">Total</span>
                    <span style="font-size:16px;font-weight:700;color:var(--success);">GH₵ <?= $finance_stats['total'] ?></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </main>

  <?php require_once 'includes/modals/finance_modal.php'; ?>

  <script src="assets/js/main.js"></script>
</body>

</html>
