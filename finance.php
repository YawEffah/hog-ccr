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

if (!hasPermission('perm_manage_finance')) {
    redirect('dashboard.php');
}

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
$filterMonth = $_GET['month'] ?? date('Y') . '-all';
// Ensure format is valid (YYYY-MM or YYYY-all), else fallback
if (!preg_match('/^\d{4}-(\d{2}|all)$/', $filterMonth)) {
    $filterMonth = date('Y') . '-all';
}

$isAllMonths = false;
$filterYear = '';
if (strpos($filterMonth, '-all') !== false) {
    $isAllMonths = true;
    $filterYear = explode('-', $filterMonth)[0];
}

// ── Finance Statistics ───────────────────────────────────────────────────────
if ($isAllMonths) {
    $statsStmt = $db->prepare(
        "SELECT 
            SUM(CASE WHEN type='Tithe' THEN amount ELSE 0 END) as tithes,
            SUM(CASE WHEN type='Offering' THEN amount ELSE 0 END) as offerings,
            SUM(CASE WHEN type='Donation' THEN amount ELSE 0 END) as donations,
            SUM(amount) as total
         FROM finance_transactions 
         WHERE DATE_FORMAT(transaction_date, '%Y') = ?"
    );
    $statsStmt->execute([$filterYear]);
    $rawStats = $statsStmt->fetch();

    $targetStmt = $db->prepare("SELECT SUM(target_amount) FROM finance_targets WHERE DATE_FORMAT(target_month, '%Y') = ?");
    $targetStmt->execute([$filterYear]);
} else {
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
}
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
if ($isAllMonths) {
    $txnStmt = $db->prepare(
        "SELECT t.* 
         FROM finance_transactions t
         WHERE DATE_FORMAT(t.transaction_date, '%Y') = ?
         ORDER BY t.transaction_date DESC, t.created_at DESC
         LIMIT 10"
    );
    $txnStmt->execute([$filterYear]);
} else {
    $txnStmt = $db->prepare(
        "SELECT t.* 
         FROM finance_transactions t
         WHERE DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
         ORDER BY t.transaction_date DESC, t.created_at DESC
         LIMIT 10"
    );
    $txnStmt->execute([$filterMonth]);
}
$rawTxns = $txnStmt->fetchAll();

$typeBadges = [
    'Tithe'     => 'badge-yellow',
    'Offering'  => 'badge-green',
    'Donation'  => 'badge-green',
    'Pledge'    => 'badge-purple',
    'Project Contribution' => 'badge-blue',
    'Half Year Thanks Giving' => 'badge-yellow',
    'End of Year Thanks Giving' => 'badge-purple'
];

$transactions = array_map(function($t) use ($typeBadges) {
    return [
        'id'         => $t['id'],
        'week'       => $t['week_number'],
        'type'       => $t['type'],
        'type_badge' => $typeBadges[$t['type']] ?? 'badge-gray',
        'amount'     => number_format($t['amount'], 2),
        'method'     => $t['payment_method'],
        'reference'  => $t['reference_no'] ?: 'N/A',
        'date'       => date('F j, Y', strtotime($t['transaction_date']))
    ];
}, $rawTxns);

// ── Expenses & Net Balance ───────────────────────────────────────────────────
if ($isAllMonths) {
    $expStmt = $db->prepare("SELECT SUM(amount) as total_expenses FROM finance_expenses WHERE DATE_FORMAT(expense_date, '%Y') = ?");
    $expStmt->execute([$filterYear]);
    
    $expListStmt = $db->prepare("SELECT e.*, a.name as asset_name FROM finance_expenses e LEFT JOIN finance_accounts a ON e.asset_account_id = a.id WHERE DATE_FORMAT(e.expense_date, '%Y') = ? ORDER BY e.expense_date DESC, e.created_at DESC LIMIT 10");
    $expListStmt->execute([$filterYear]);
} else {
    $expStmt = $db->prepare("SELECT SUM(amount) as total_expenses FROM finance_expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?");
    $expStmt->execute([$filterMonth]);
    
    $expListStmt = $db->prepare("SELECT e.*, a.name as asset_name FROM finance_expenses e LEFT JOIN finance_accounts a ON e.asset_account_id = a.id WHERE DATE_FORMAT(e.expense_date, '%Y-%m') = ? ORDER BY e.expense_date DESC, e.created_at DESC LIMIT 10");
    $expListStmt->execute([$filterMonth]);
}
$totalExpenses = (float)$expStmt->fetchColumn();
$finance_expenses = $expListStmt->fetchAll();

$netBalance = $totalIncome - $totalExpenses;

// ── Income Breakdown ─────────────────────────────────────────────────────────
if ($isAllMonths) {
    $breakdownStmt = $db->prepare(
        "SELECT type, SUM(amount) as total 
         FROM finance_transactions 
         WHERE DATE_FORMAT(transaction_date, '%Y') = ?
         GROUP BY type"
    );
    $breakdownStmt->execute([$filterYear]);
} else {
    $breakdownStmt = $db->prepare(
        "SELECT type, SUM(amount) as total 
         FROM finance_transactions 
         WHERE DATE_FORMAT(transaction_date, '%Y-%m') = ?
         GROUP BY type"
    );
    $breakdownStmt->execute([$filterMonth]);
}
$rawBreakdown = $breakdownStmt->fetchAll();

$breakdownColors = [
    'Tithe'    => 'var(--gold)',
    'Offering' => 'var(--deep)',
    'Donation' => '#2E7D57',
    'Pledge'   => '#7C3AED',
    'Project Contribution' => '#0EA5E9',
    'Half Year Thanks Giving' => '#F59E0B',
    'End of Year Thanks Giving' => '#9333EA'
];

$income_breakdown = array_map(function($b) use ($totalIncome, $breakdownColors) {
    return [
        'label'     => $b['type'],
        'amount'    => number_format($b['total'], 0),
        'percent'   => $totalIncome > 0 ? round(($b['total'] / $totalIncome) * 100) : 0,
        'bar_class' => $breakdownColors[$b['type']] ?? 'var(--gold)'
    ];
}, $rawBreakdown);



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
        <div class="topbar-actions">          <button class="btn btn-outline btn-sm" onclick="openModal('setTargetModal')">
            <i class="ph ph-target"></i> Set Target
          </button>
          <button class="btn btn-primary btn-sm" onclick="openModal('addFinanceModal')">+ Record Transaction</button>
        </div>
      </div>

      <?php renderToastAlerts($successMsg, $errorMsg); ?>
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

        <div class="grid-2" style="margin-bottom:24px; gap:24px;">
          <div class="stat-card">
            <div class="accent-bar" style="background:var(--danger);"></div>
            <div class="label">Total Expenses</div>
            <div class="value" style="font-size:28px;">GH₵<?= number_format($totalExpenses, 2) ?></div>
            <div class="change" style="color:var(--muted);"><?= count($finance_expenses) ?> records</div>
          </div>
          <div class="stat-card">
            <div class="accent-bar" style="background:<?= $netBalance >= 0 ? 'var(--success)' : 'var(--danger)' ?>;"></div>
            <div class="label">Net Balance</div>
            <div class="value" style="font-size:28px;">GH₵<?= number_format($netBalance, 2) ?></div>
            <div class="change" style="color:<?= $netBalance >= 0 ? 'var(--success)' : 'var(--danger)' ?>;"><?= $netBalance >= 0 ? 'Surplus' : 'Deficit' ?></div>
          </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tabs no-print" id="financeTabs" style="margin-bottom:20px;background:white;border:1px solid #EDE8DF;border-radius:10px;padding:4px;display:inline-flex;">
          <button class="tab active" id="tabIncomeBtn" onclick="switchFinanceTab('income')" style="padding:7px 20px;font-size:13px;">
            <i class="ph ph-trend-up"></i> Income
          </button>
          <button class="tab" id="tabExpensesBtn" onclick="switchFinanceTab('expenses')" style="padding:7px 20px;font-size:13px;">
            <i class="ph ph-trend-down"></i> Expenses
          </button>
        </div>

        <!-- INCOME TAB -->
        <div id="financeIncomeTab">
          <div class="grid-2" style="gap:24px;">
          <div class="card">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
              <h3>Recent Transactions</h3>
              <div style="display:flex; gap:8px;">
                <a href="finance_history.php" class="btn btn-outline btn-sm">View All</a>
              </div>
            </div>
            <div class="table-responsive">
              <table>
                <thead>
                  <tr>
                    <th>Week</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Receipt</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($transactions as $tx): ?>
                  <tr>
                    <td style="font-weight:500;"><?= htmlspecialchars($tx['week']) ?></td>
                    <td><span class="badge <?= $tx['type_badge'] ?>"><?= $tx['type'] ?></span></td>
                    <td style="font-weight:600;color:var(--success);">GH₵ <?= $tx['amount'] ?></td>
                    <td style="font-size:12px;color:var(--muted);"><?= $tx['date'] ?></td>
                    <td>
                      <div style="display:flex;gap:4px;">
                        <button class="btn btn-outline btn-sm" title="View Receipt" onclick='openReceiptModal(<?= json_encode($tx) ?>)'><i class="ph ph-receipt"></i></button>
                        <button class="btn btn-danger-soft btn-sm" title="Delete"
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

        <!-- EXPENSES TAB -->
        <div id="financeExpensesTab" style="display:none;">
          <div class="card">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
              <h3>Recent Expenses</h3>
              <div style="display:flex; gap:8px;">
                <button class="btn btn-danger-soft btn-sm" onclick="openModal('recordFinanceExpenseModal')">+ Record Expense</button>
                <a href="finance_history.php?tab=expenses" class="btn btn-outline btn-sm">View All</a>
              </div>
            </div>
            <div class="table-responsive">
              <table>
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Paid From</th>
                    <th>Description</th>
                    <th>Reference</th>
                    <th>Amount</th>
                    <th style="text-align:right;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($finance_expenses) > 0): ?>
                    <?php foreach ($finance_expenses as $ex): ?>
                    <tr>
                      <td style="font-size:13px;"><?= date('M j, Y', strtotime($ex['expense_date'])) ?></td>
                      <td style="font-weight:500;color:var(--deep2);"><?= htmlspecialchars($ex['type'] ?: 'Unknown') ?></td>
                      <td style="font-size:13px;color:var(--muted);"><?= htmlspecialchars($ex['asset_name'] ?: 'Unknown') ?></td>
                      <td style="font-size:13px;"><?= htmlspecialchars($ex['description']) ?></td>
                      <td style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($ex['reference_no']) ?></td>
                      <td style="font-weight:600;color:var(--danger);">GH₵ <?= number_format($ex['amount'], 2) ?></td>
                      <td style="text-align:right;">
                        <div style="display:flex; gap:4px; justify-content:flex-end;">
                          <button class="btn btn-outline btn-sm" title="Edit Expense" onclick='openEditFinanceExpenseModal(<?= json_encode($ex) ?>)'>
                            <i class="ph ph-pencil-simple"></i>
                          </button>
                          <button class="btn btn-danger-soft btn-sm" title="Delete Expense"
                            onclick="confirmDeleteExp(<?= $ex['id'] ?>)">
                            <i class="ph ph-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;padding:20px;color:var(--muted);">No expenses recorded for this period.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
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
    <input type="hidden" name="return_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
  </form>

  <!-- Hidden form to delete expense -->
  <form method="POST" action="handlers/finance_handler.php" id="deleteExpForm" style="display:none;">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="delete_finance_expense">
    <input type="hidden" name="expense_id" id="deleteExpId">
    <input type="hidden" name="return_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
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

    function confirmDeleteExp(id) {
      showConfirmModal(
        'Delete Expense',
        'Are you sure you want to delete this expense? This will also remove the ledger entry.',
        'Delete',
        function() {
          document.getElementById('deleteExpId').value = id;
          document.getElementById('deleteExpForm').submit();
        },
        'danger'
      );
    }

    function switchFinanceTab(tabId) {
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      
      document.getElementById('financeIncomeTab').style.display = 'none';
      document.getElementById('financeExpensesTab').style.display = 'none';
      
      if (tabId === 'income') {
        document.getElementById('tabIncomeBtn').classList.add('active');
        document.getElementById('financeIncomeTab').style.display = 'block';
      } else if (tabId === 'expenses') {
        document.getElementById('tabExpensesBtn').classList.add('active');
        document.getElementById('financeExpensesTab').style.display = 'block';
      }
    }

    function openReceiptModal(tx) {
      document.getElementById('receiptId').textContent     = '#' + tx.id;
      document.getElementById('receiptDate').textContent   = tx.date;
      document.getElementById('receiptMember').textContent = tx.week;
      document.getElementById('receiptType').textContent   = tx.type;
      document.getElementById('receiptAmount').textContent = tx.amount;
      document.getElementById('receiptMethod').textContent = tx.method;
      document.getElementById('receiptRef').textContent    = tx.reference && tx.reference !== 'N/A' ? `(Ref: ${tx.reference})` : '';
      
      document.getElementById('resendTxnId').value = tx.id;
      
      openModal('viewReceiptModal');
    }

  </script>
</body>

</html>
