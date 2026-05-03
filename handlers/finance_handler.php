<?php
/**
 * Finance Handler — Record Transaction & Set Target
 * POST actions: add_transaction | set_target
 */
require_once '../includes/auth.php';
requireAuth();
require_once '../includes/db.php';
require_once '../includes/helpers.php';

verifyCsrf();

$action   = $_POST['action'] ?? '';
$db       = getDB();
$redirect = '../finance.php';

// ── RECORD TRANSACTION ────────────────────────────────────────────────────────
if ($action === 'add_transaction') {
    $memberSearch  = trim($_POST['member_search']    ?? '');
    $type          = $_POST['transaction_type']      ?? '';
    $amount        = (float)($_POST['amount']        ?? 0);
    $method        = $_POST['payment_method']        ?? 'Cash';
    $reference     = trim($_POST['reference_no']     ?? '');
    $phone         = trim($_POST['phone']            ?? '');
    $email         = trim($_POST['email']            ?? '');
    $notes         = trim($_POST['notes']            ?? '');
    $date          = $_POST['date']                  ?? date('Y-m-d');
    $sendReceipt   = isset($_POST['generate_receipt']);

    $allowedTypes  = ['Tithe','Offering','Donation','Pledge','Project Contribution','Welfare'];
    $allowedMethods = ['Cash','MoMo','Bank Transfer','Cheque'];

    if ($amount <= 0 || !in_array($type, $allowedTypes, true) || !in_array($method, $allowedMethods, true)) {
        redirect($redirect . '?error=invalid_data');
    }

    // Try to match an existing member by name or code
    $memberId   = null;
    $memberName = $memberSearch;
    if ($memberSearch) {
        $mStmt = $db->prepare(
            "SELECT id, CONCAT(first_name,' ',last_name) AS full_name
             FROM members
             WHERE member_code = ? OR CONCAT(first_name,' ',last_name) LIKE ?
             LIMIT 1"
        );
        $mStmt->execute([$memberSearch, '%' . $memberSearch . '%']);
        $found = $mStmt->fetch();
        if ($found) {
            $memberId   = $found['id'];
            $memberName = $found['full_name'];
        }
    }

    try {
        $stmt = $db->prepare(
            "INSERT INTO finance_transactions
             (member_id, member_name, type, amount, payment_method, reference_no,
              phone, email, notes, transaction_date, receipt_sent, recorded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $receiptSentFlag = 0;
        $stmt->execute([
            $memberId, $memberName, $type, $amount, $method, $reference,
            $phone ?: null, $email ?: null, $notes ?: null,
            $date, $receiptSentFlag, $_SESSION['user_id']
        ]);
        $txnId = (int)$db->lastInsertId();

        // Send receipt email
        if ($sendReceipt && $email) {
            $txnData = [
                'type'             => $type,
                'amount'           => $amount,
                'payment_method'   => $method,
                'reference_no'     => $reference,
                'transaction_date' => $date,
            ];
            $sent = sendFinanceReceipt(['name' => $memberName, 'email' => $email], $txnData);
            if ($sent) {
                $db->prepare("UPDATE finance_transactions SET receipt_sent = 1 WHERE id = ?")
                   ->execute([$txnId]);
            }
        }

        logActivity("Recorded {$type} of " . formatGhc($amount) . " from {$memberName}", 'finance');
        redirect($redirect . '?success=transaction_added');

    } catch (PDOException $e) {
        error_log('add_transaction error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── SET MONTHLY TARGET ────────────────────────────────────────────────────────
if ($action === 'set_target') {
    $targetMonth  = $_POST['target_month'] ?? '';   // format YYYY-MM
    $targetAmount = (float)($_POST['monthly_target'] ?? 0);
    $notes        = trim($_POST['notes'] ?? '');

    if (!$targetMonth || $targetAmount <= 0) {
        redirect($redirect . '?error=invalid_data');
    }

    // Normalise to first of month
    $monthDate = $targetMonth . '-01';

    try {
        $stmt = $db->prepare(
            "INSERT INTO finance_targets (target_month, target_amount, notes, set_by)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE target_amount = VALUES(target_amount), notes = VALUES(notes)"
        );
        $stmt->execute([$monthDate, $targetAmount, $notes ?: null, $_SESSION['user_id']]);

        logActivity('Set finance target for ' . date('F Y', strtotime($monthDate)) . ' to ' . formatGhc($targetAmount), 'finance');
        redirect($redirect . '?success=target_set');

    } catch (PDOException $e) {
        error_log('set_target error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── DELETE TRANSACTION ────────────────────────────────────────────────────────
if ($action === 'delete_transaction') {
    $txnId = (int)($_POST['txn_id'] ?? 0);

    if (!$txnId) {
        redirect($redirect . '?error=invalid_data');
    }

    try {
        $row = $db->prepare("SELECT type, amount FROM finance_transactions WHERE id = ?");
        $row->execute([$txnId]);
        $txn = $row->fetch();

        $db->prepare("DELETE FROM finance_transactions WHERE id = ?")->execute([$txnId]);

        if ($txn) {
            logActivity('Deleted ' . $txn['type'] . ' transaction of ' . formatGhc($txn['amount']), 'finance');
        }
        redirect($redirect . '?success=transaction_deleted');

    } catch (PDOException $e) {
        error_log('delete_transaction error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

redirect($redirect . '?error=unknown_action');
