<?php
/**
 * Finance Transaction History Page
 */
require_once 'includes/auth.php';
requireAuth();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

$pageTitle  = 'Transaction History';
$activePage = 'finance';

$successMsg = flash('success');
$errorMsg   = flash('error');

if (!$successMsg && !$errorMsg) {
    $successLabels = [
        'transaction_deleted'=> 'Transaction deleted successfully.',
        'receipt_resent'     => 'Receipt resent successfully.',
    ];
    $errorLabels = [
        'db_error' => 'A database error occurred.',
        'send_failed' => 'Failed to send receipt. Please check API settings.',
        'not_found'   => 'Transaction not found.',
    ];
    $successMsg = $successLabels[$_GET['success'] ?? ''] ?? '';
    $errorMsg   = $errorLabels[$_GET['error']   ?? ''] ?? '';
}

$db = getDB();

// ── Process Filters ──────────────────────────────────────────────────────────
$whereClauses = [];
$params = [];

$search    = trim($_GET['search'] ?? '');
$type      = trim($_GET['type'] ?? '');
$method    = trim($_GET['method'] ?? '');
$fromDate  = trim($_GET['from_date'] ?? '');
$toDate    = trim($_GET['to_date'] ?? '');

if ($search) {
    // Search by member_name in finance_transactions OR first_name/last_name/code in members table
    $whereClauses[] = "(t.member_name LIKE ? OR m.first_name LIKE ? OR m.last_name LIKE ? OR m.member_code LIKE ?)";
    $searchWildcard = "%{$search}%";
    array_push($params, $searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard);
}

if ($type) {
    $whereClauses[] = "t.type = ?";
    $params[] = $type;
}

if ($method) {
    $whereClauses[] = "t.payment_method = ?";
    $params[] = $method;
}

if ($fromDate) {
    $whereClauses[] = "t.transaction_date >= ?";
    $params[] = $fromDate;
}

if ($toDate) {
    $whereClauses[] = "t.transaction_date <= ?";
    $params[] = $toDate;
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = "WHERE " . implode(' AND ', $whereClauses);
}

$limit = 100; // Limit to prevent massive loads

// ── Query Records ────────────────────────────────────────────────────────────
$query = "
    SELECT 
        t.*, 
        m.first_name, 
        m.last_name, 
        m.member_code
    FROM finance_transactions t
    LEFT JOIN members m ON t.member_id = m.id
    $whereSql
    ORDER BY t.transaction_date DESC, t.created_at DESC
    LIMIT $limit
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$rawTxns = $stmt->fetchAll();

$typeBadges = [
    'Tithe'     => 'badge-yellow',
    'Offering'  => 'badge-green',
    'Donation'  => 'badge-blue',
    'Welfare'   => 'badge-purple',
    'Pledge'    => 'badge-gray',
    'Project Contribution' => 'badge-blue'
];

$transactions = array_map(function($t) use ($typeBadges) {
    $memberName = $t['first_name'] ? ($t['first_name'] . ' ' . $t['last_name']) : ($t['member_name'] ?: 'Guest');
    $memberCode = $t['member_code'] ? $t['member_code'] : 'External';
    return [
        'id'           => $t['id'],
        'member'       => $memberName,
        'member_code'  => $memberCode,
        'type'         => $t['type'],
        'type_badge'   => $typeBadges[$t['type']] ?? 'badge-gray',
        'amount'       => number_format($t['amount'], 2),
        'method'       => $t['payment_method'],
        'reference'    => $t['reference_no'] ?: 'N/A',
        'date'         => date('M j, Y', strtotime($t['transaction_date']))
    ];
}, $rawTxns);

?>
<!DOCTYPE html>
<html lang="en">

<?php require_once 'includes/head.php'; ?>

<body>

  <?php require_once 'includes/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main id="main">

    <div id="page-finance-history" class="page">
      <div class="topbar">
        <div style="display:flex;align-items:center;">
          <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="ph ph-list"></i>
          </button>
          <div class="topbar-title">Transaction History</div>
        </div>
        <div class="topbar-actions">
          <a href="finance.php" class="btn btn-outline btn-sm">
            <i class="ph ph-arrow-left"></i> Back to Finance
          </a>
          <button class="btn btn-outline btn-sm" id="notifBtn" onclick="toggleNotifications()">
            <i class="ph ph-bell"></i>
          </button>
          <?php include 'includes/notifications.php'; ?>
        </div>
      </div>

      <div class="content">
        
        <?php renderToastAlerts($successMsg, $errorMsg); ?>

        <!-- Filters Card -->
        <div class="card" style="margin-bottom: 24px;">
          <div class="card-header" style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="toggleFilters()">
            <h3>Filter Transactions</h3>
            <button class="btn btn-outline btn-sm" style="border: none; padding: 4px;" id="filterToggleBtn">
              <i class="ph ph-caret-down"></i>
            </button>
          </div>
          <div class="card-body" id="filterCardBody" style="display: none;">
            <form method="GET" action="finance_history.php" class="grid-4" style="gap:16px;">
              
              <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Search Payer</label>
                <input type="text" name="search" class="form-control" placeholder="Name or ID..." value="<?= htmlspecialchars($search) ?>">
              </div>

              <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Transaction Type</label>
                <select name="type" class="form-control">
                  <option value="">All Types</option>
                  <option value="Tithe" <?= $type === 'Tithe' ? 'selected' : '' ?>>Tithe</option>
                  <option value="Offering" <?= $type === 'Offering' ? 'selected' : '' ?>>Offering</option>
                  <option value="Donation" <?= $type === 'Donation' ? 'selected' : '' ?>>Donation</option>
                  <option value="Pledge" <?= $type === 'Pledge' ? 'selected' : '' ?>>Pledge</option>
                  <option value="Project Contribution" <?= $type === 'Project Contribution' ? 'selected' : '' ?>>Project Contribution</option>
                </select>
              </div>

              <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Payment Method</label>
                <select name="method" class="form-control">
                  <option value="">All Methods</option>
                  <option value="Cash" <?= $method === 'Cash' ? 'selected' : '' ?>>Cash</option>
                  <option value="MoMo" <?= $method === 'MoMo' ? 'selected' : '' ?>>MoMo</option>
                  <option value="Bank Transfer" <?= $method === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                  <option value="Cheque" <?= $method === 'Cheque' ? 'selected' : '' ?>>Cheque</option>
                </select>
              </div>

              <div class="form-group" style="margin-bottom:0;"></div>

              <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($fromDate) ?>">
              </div>

              <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($toDate) ?>">
              </div>

              <div class="form-group" style="margin-bottom:0; display:flex; align-items:flex-end;">
                <button type="submit" class="btn btn-primary" style="width:100%;">
                  <i class="ph ph-funnel"></i> Apply Filters
                </button>
              </div>

              <div class="form-group" style="margin-bottom:0; display:flex; align-items:flex-end;">
                <a href="finance_history.php" class="btn btn-outline" style="width:100%; text-align:center;">
                  Clear
                </a>
              </div>
            </form>
          </div>
        </div>

        <div class="card">
          <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
              <h3 style="margin:0;">Transaction Ledger</h3>
              <div style="font-size:13px; color:var(--muted); margin-top:4px;">
                Showing <?= count($transactions) ?> result(s) <?= count($transactions) === $limit ? '(Limit reached)' : '' ?>
              </div>
            </div>
            <button class="btn btn-outline btn-sm">
              <i class="ph ph-download-simple"></i> Export CSV
            </button>
          </div>
          <div class="table-responsive">
            <table>
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Payer</th>
                  <th>Type</th>
                  <th>Method & Ref</th>
                  <th>Amount</th>
                  <th style="text-align:right;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($transactions)): ?>
                <tr>
                  <td colspan="6" style="text-align:center; padding: 40px; color:var(--muted);">
                    No transactions found matching your criteria.
                  </td>
                </tr>
                <?php else: ?>
                  <?php foreach ($transactions as $tx): ?>
                  <tr>
                    <td>
                      <div style="font-weight:500; color:var(--deep);"><?= $tx['date'] ?></div>
                    </td>
                    <td>
                      <div style="font-weight:500;"><?= htmlspecialchars($tx['member']) ?></div>
                      <div style="font-size:12px; color:var(--muted);"><?= htmlspecialchars($tx['member_code']) ?></div>
                    </td>
                    <td>
                      <span class="badge <?= $tx['type_badge'] ?>"><?= $tx['type'] ?></span>
                    </td>
                    <td>
                      <div style="font-weight:500;"><?= htmlspecialchars($tx['method']) ?></div>
                      <div style="font-size:12px; color:var(--muted);"><?= htmlspecialchars($tx['reference']) ?></div>
                    </td>
                    <td>
                      <div style="font-weight:600;color:var(--success);">GH₵ <?= $tx['amount'] ?></div>
                    </td>
                    <td style="text-align:right;">
                      <div style="display:flex; justify-content:flex-end; gap:4px;">
                        <button class="btn btn-outline btn-sm" title="View Receipt" onclick='openReceiptModal(<?= json_encode($tx) ?>)'>
                          <i class="ph ph-receipt"></i>
                        </button>
                        <button class="btn btn-sm" title="Delete"
                          style="background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;"
                          onclick="confirmDeleteTxn(<?= $tx['id'] ?>)">
                          <i class="ph ph-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
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
    <!-- Include a return_to parameter so handler redirects back to history -->
    <input type="hidden" name="return_to" value="../finance_history.php">
  </form>

  <script src="assets/js/main.js"></script>
  <script>
    function toggleFilters() {
      const body = document.getElementById('filterCardBody');
      const btn = document.getElementById('filterToggleBtn');
      const icon = btn.querySelector('i');
      
      if (body.style.display === 'none') {
        body.style.display = 'block';
        icon.classList.remove('ph-caret-down');
        icon.classList.add('ph-caret-up');
      } else {
        body.style.display = 'none';
        icon.classList.remove('ph-caret-up');
        icon.classList.add('ph-caret-down');
      }
    }

    function confirmDeleteTxn(id) {
      showConfirmModal(
        'Delete Transaction',
        'Are you sure you want to delete this transaction? This action cannot be undone.',
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
  </script>
</body>
</html>
