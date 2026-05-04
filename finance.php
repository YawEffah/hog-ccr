<?php
/**
 * Finance Management Page
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

$pageTitle  = 'Finance';
$activePage = 'finance';

// Flash messages
$successMsg = flash('success');
$errorMsg   = flash('error');
if (!$successMsg && !$errorMsg) {
    $successLabels = [
        'transaction_added' => 'Transaction recorded successfully.',
        'transaction_deleted'=> 'Transaction deleted.',
        'target_set'        => 'Monthly target updated.',
        'receipt_resent'    => 'Receipt resent successfully.',
    ];
    $errorLabels = [
        'invalid_data'   => 'Invalid data. Please check the form and try again.',
        'db_error'       => 'A database error occurred.',
        'send_failed'    => 'Failed to send receipt.',
        'not_found'      => 'Transaction not found.',
    ];
    $successMsg = $successLabels[$_GET['success'] ?? ''] ?? '';
    $errorMsg   = $errorLabels[$_GET['error']   ?? ''] ?? '';
}

$db = getDB();
$filterMonth = $_GET['month'] ?? date('Y-m');
// Ensure format is valid (YYYY-MM), else fallback
if (!preg_match('/^\d{4}-\d{2}$/', $filterMonth)) {
    $filterMonth = date('Y-m');
}

// ── Finance Statistics ───────────────────────────────────────────────────────
$statsStmt = $db->prepare(
    "SELECT 
        SUM(CASE WHEN type='Tithe' THEN amount ELSE 0 END) as tithes,
        SUM(CASE WHEN type='Offering' THEN amount ELSE 0 END) as offerings,
        SUM(CASE WHEN type='Donation' THEN amount ELSE 0 END) as donations,
        SUM(amount) as total
     FROM finance_transactions 
     WHERE DATE_FORMAT(transaction_date, '%Y-%m') = ?"
);
$statsStmt->execute([$filterMonth]);
$rawStats = $statsStmt->fetch();

$targetStmt = $db->prepare("SELECT target_amount FROM finance_targets WHERE DATE_FORMAT(target_month, '%Y-%m') = ?");
$targetStmt->execute([$filterMonth]);
$monthlyTarget = (float)$targetStmt->fetchColumn() ?: 10000;

$totalIncome = (float)($rawStats['total'] ?? 0);
$finance_stats = [
    'tithes'         => number_format((float)($rawStats['tithes'] ?? 0), 2),
    'offerings'      => number_format((float)($rawStats['offerings'] ?? 0), 2),
    'donations'      => number_format((float)($rawStats['donations'] ?? 0), 2),
    'total'          => number_format($totalIncome, 2),
    'monthly_target' => number_format($monthlyTarget, 0),
    'target_percent' => $monthlyTarget > 0 ? round(($totalIncome / $monthlyTarget) * 100) : 0
];

// ── Recent Transactions ──────────────────────────────────────────────────────
$txnStmt = $db->prepare(
    "SELECT t.*, m.first_name, m.last_name 
     FROM finance_transactions t
     LEFT JOIN members m ON t.member_id = m.id
     WHERE DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
     ORDER BY t.transaction_date DESC, t.created_at DESC
     LIMIT 100"
);
$txnStmt->execute([$filterMonth]);
$rawTxns = $txnStmt->fetchAll();

$typeBadges = [
    'Tithe'     => 'badge-yellow',
    'Offering'  => 'badge-green',
    'Donation'  => 'badge-blue',
    'Welfare'   => 'badge-purple',
    'Pledge'    => 'badge-gray'
];

$transactions = array_map(function($t) use ($typeBadges) {
    $memberName = $t['first_name'] ? ($t['first_name'] . ' ' . $t['last_name']) : ($t['member_name'] ?: 'Guest');
    return [
        'id'         => $t['id'],
        'member'     => $memberName,
        'type'       => $t['type'],
        'type_badge' => $typeBadges[$t['type']] ?? 'badge-gray',
        'amount'     => number_format($t['amount'], 2),
        'method'     => $t['payment_method'],
        'reference'  => $t['reference_no'] ?: 'N/A',
        'date'       => date('M j', strtotime($t['transaction_date']))
    ];
}, $rawTxns);

// ── Income Breakdown ─────────────────────────────────────────────────────────
$breakdownStmt = $db->prepare(
    "SELECT type, SUM(amount) as total 
     FROM finance_transactions 
     WHERE DATE_FORMAT(transaction_date, '%Y-%m') = ?
     GROUP BY type"
);
$breakdownStmt->execute([$filterMonth]);
$rawBreakdown = $breakdownStmt->fetchAll();

$breakdownColors = [
    'Tithe'    => 'var(--gold)',
    'Offering' => 'var(--deep)',
    'Donation' => '#2E7D57',
    'Welfare'  => 'var(--deep3)',
    'Pledge'   => '#7C3AED'
];

$income_breakdown = array_map(function($b) use ($totalIncome, $breakdownColors) {
    return [
        'label'     => $b['type'],
        'amount'    => number_format($b['total'], 0),
        'percent'   => $totalIncome > 0 ? round(($b['total'] / $totalIncome) * 100) : 0,
        'bar_class' => $breakdownColors[$b['type']] ?? 'var(--gold)'
    ];
}, $rawBreakdown);

// ── All Members (for Finance Search) ─────────────────────────────────────────
$allMembersStmt = $db->query(
    "SELECT id, first_name, last_name, member_code 
     FROM members 
     WHERE status = 'Active' 
     ORDER BY last_name ASC"
);
$allMembers = $allMembersStmt->fetchAll();

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
          <?php
            $currentY = explode('-', $filterMonth)[0];
            $currentM = explode('-', $filterMonth)[1];
            $monthsList = [
                '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
                '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
                '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
            ];
            $thisYear = date('Y');
          ?>
          <div style="display:flex;gap:8px;">
            <select class="form-control" style="width:120px;padding:8px 12px;" id="financeMonthSelect" onchange="updateFinanceFilter()">
              <?php foreach($monthsList as $num => $name): ?>
                <option value="<?= $num ?>" <?= $currentM === $num ? 'selected' : '' ?>><?= $name ?></option>
              <?php endforeach; ?>
            </select>
            <select class="form-control" style="width:90px;padding:8px 12px;" id="financeYearSelect" onchange="updateFinanceFilter()">
              <?php for($y = $thisYear; $y >= $thisYear - 5; $y--): ?>
                <option value="<?= $y ?>" <?= (string)$currentY === (string)$y ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
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

      <?php if ($successMsg): ?>
      <div class="alert alert-success" style="margin:20px 20px 0;">
        <i class="ph ph-check-circle"></i> <?= htmlspecialchars($successMsg) ?>
      </div>
      <?php endif; ?>
      <?php if ($errorMsg): ?>
      <div class="alert alert-error" style="margin:20px 20px 0;">
        <i class="ph ph-warning-circle"></i> <?= htmlspecialchars($errorMsg) ?>
      </div>
      <?php endif; ?>
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
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
              <h3>Recent Transactions</h3>
              <div style="display:flex; gap:8px;">
                <button class="btn btn-outline btn-sm">Export CSV</button>
                <a href="finance_history.php" class="btn btn-outline btn-sm">View All</a>
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
                    <td>
                      <div style="display:flex;gap:4px;">
                        <button class="btn btn-outline btn-sm" title="View Receipt" onclick='openReceiptModal(<?= json_encode($tx) ?>)'><i class="ph ph-receipt"></i></button>
                        <button class="btn btn-sm" title="Delete"
                          style="background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;"
                          onclick="confirmDeleteTxn(<?= $tx['id'] ?>)">
                          <i class="ph ph-trash"></i>
                        </button>
                      </div>
                    </td>
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

  <?php include 'includes/modals/finance_modal.php'; ?>
  <?php include 'includes/modals/receipt_modal.php'; ?>

  <!-- Hidden delete-transaction form -->
  <form method="POST" action="handlers/finance_handler.php" id="deleteTxnForm" style="display:none;">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="delete_transaction">
    <input type="hidden" name="txn_id" id="deleteTxnId">
  </form>

  <script src="assets/js/main.js"></script>
  <script>
    function confirmDeleteTxn(id) {
      showConfirmModal(
        'Delete Transaction',
        'Are you sure you want to delete this transaction?',
        'Delete',
        function() {
          document.getElementById('deleteTxnId').value = id;
          document.getElementById('deleteTxnForm').submit();
        },
        'danger'
      );
    }

    function openReceiptModal(tx) {
      document.getElementById('receiptId').textContent     = '#' + tx.id;
      document.getElementById('receiptDate').textContent   = tx.date;
      document.getElementById('receiptMember').textContent = tx.member;
      document.getElementById('receiptType').textContent   = tx.type;
      document.getElementById('receiptAmount').textContent = tx.amount;
      document.getElementById('receiptMethod').textContent = tx.method;
      document.getElementById('receiptRef').textContent    = tx.reference && tx.reference !== 'N/A' ? `(Ref: ${tx.reference})` : '';
      
      document.getElementById('resendTxnId').value = tx.id;
      
      openModal('viewReceiptModal');
    }

    const allFinanceMembers = <?= json_encode($allMembers) ?>;

    function filterFinanceMember(query) {
      const q = query.toLowerCase();
      const sugDiv = document.getElementById('financeSuggestions');
      const hiddenId = document.getElementById('financeMemberId');
      
      // Clear ID if they keep typing after selection
      hiddenId.value = '';

      if (!q) {
        sugDiv.style.display = 'none';
        return;
      }
      
      const matches = allFinanceMembers.filter(m => {
        const full = (m.first_name + ' ' + m.last_name).toLowerCase();
        return full.includes(q) || m.member_code.toLowerCase().includes(q);
      });
      
      if (matches.length > 0) {
        sugDiv.innerHTML = '';
        matches.forEach(m => {
          const div = document.createElement('div');
          div.style.padding = '10px 14px';
          div.style.cursor = 'pointer';
          div.style.borderBottom = '1px solid #EDE8DF';
          div.style.fontSize = '13px';
          div.innerHTML = `<strong>${m.first_name} ${m.last_name}</strong> <span style="color:var(--muted);font-size:11px;margin-left:6px;">${m.member_code}</span>`;
          div.onclick = () => selectFinanceMember(m.id, `${m.first_name} ${m.last_name}`);
          sugDiv.appendChild(div);
        });
        sugDiv.style.display = 'block';
      } else {
        sugDiv.style.display = 'none';
      }
    }

    function selectFinanceMember(id, name) {
      document.getElementById('financeMemberSearch').value = name;
      document.getElementById('financeMemberId').value = id;
      document.getElementById('financeSuggestions').style.display = 'none';
    }

    function updateFinanceFilter() {
      const y = document.getElementById('financeYearSelect').value;
      const m = document.getElementById('financeMonthSelect').value;
      window.location.href = `finance.php?month=${y}-${m}`;
    }
  </script>
</body>

</html>
