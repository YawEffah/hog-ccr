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
    $weekNumber    = $_POST['week_number']           ?? 'Week 1';
    $type          = $_POST['transaction_type']      ?? '';
    $amount        = (float)($_POST['amount']        ?? 0);
    $method        = $_POST['payment_method']        ?? 'Cash';
    $reference     = trim($_POST['reference_no']     ?? '');
    $notes         = trim($_POST['notes']            ?? '');
    $date          = $_POST['date']                  ?? date('Y-m-d');
    $sendReceipt   = isset($_POST['generate_receipt']);

    $allowedTypes  = ['Tithe','Offering','Donation','Pledge','Project Contribution','Welfare'];
    $allowedMethods = ['Cash','MoMo','Bank Transfer','Cheque'];

    if ($amount <= 0 || !in_array($type, $allowedTypes, true) || !in_array($method, $allowedMethods, true)) {
        redirect($redirect . '?error=invalid_data');
    }

    try {
        $stmt = $db->prepare(
            "INSERT INTO finance_transactions
             (week_number, type, amount, payment_method, reference_no,
              notes, transaction_date, receipt_sent, recorded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $receiptSentFlag = 0;
        $stmt->execute([
            $weekNumber, $type, $amount, $method, $reference,
            $notes ?: null,
            $date, $receiptSentFlag, $_SESSION['user_id']
        ]);
        $txnId = (int)$db->lastInsertId();

        // Send receipt email / SMS to admins
        if ($sendReceipt) {
            $txnData = [
                'type'             => $type,
                'amount'           => $amount,
                'payment_method'   => $method,
                'reference_no'     => $reference,
                'transaction_date' => $date,
                'week_number'      => $weekNumber,
            ];
            
            // Get admins
            $adminsStmt = $db->query("SELECT name, email, phone FROM admins WHERE role IN ('Administrator', 'Finance Secretary')");
            $admins = $adminsStmt->fetchAll();
            $anySent = false;
            foreach ($admins as $admin) {
                if ($admin['email'] || $admin['phone']) {
                    $sent = sendFinanceReceipt(['name' => $admin['name'], 'email' => $admin['email'], 'phone' => $admin['phone']], $txnData);
                    if ($sent) $anySent = true;
                }
            }
            
            if ($anySent) {
                $db->prepare("UPDATE finance_transactions SET receipt_sent = 1 WHERE id = ?")
                   ->execute([$txnId]);
            }
        }

        logActivity("Recorded {$type} of " . formatGhc($amount) . " for {$weekNumber}", 'finance');
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
    $returnTo = $_POST['return_to'] ?? $redirect;

    if (!$txnId) {
        redirect($returnTo . '?error=invalid_data');
    }

    try {
        $row = $db->prepare("SELECT type, amount FROM finance_transactions WHERE id = ?");
        $row->execute([$txnId]);
        $txn = $row->fetch();

        $db->prepare("DELETE FROM finance_transactions WHERE id = ?")->execute([$txnId]);

        if ($txn) {
            logActivity('Deleted ' . $txn['type'] . ' transaction of ' . formatGhc($txn['amount']), 'finance');
        }
        redirect($returnTo . '?success=transaction_deleted');

    } catch (PDOException $e) {
        error_log('delete_transaction error: ' . $e->getMessage());
        redirect($returnTo . '?error=db_error');
    }
}

// ── RESEND RECEIPT ────────────────────────────────────────────────────────────
if ($action === 'resend_receipt') {
    $txnId    = (int)($_POST['txn_id'] ?? 0);
    $returnTo = $_POST['return_to'] ?? $redirect;

    if (!$txnId) {
        redirect($returnTo . '?error=invalid_data');
    }

    try {
        $stmt = $db->prepare(
            "SELECT t.* 
             FROM finance_transactions t
             WHERE t.id = ?"
        );
        $stmt->execute([$txnId]);
        $tx = $stmt->fetch();

        if (!$tx) {
            redirect($returnTo . '?error=not_found');
        }

        $weekNumber = $tx['week_number'];
        
        $txnData = [
            'type'             => $tx['type'],
            'amount'           => $tx['amount'],
            'payment_method'   => $tx['payment_method'],
            'reference_no'     => $tx['reference_no'],
            'transaction_date' => $tx['transaction_date'],
            'week_number'      => $weekNumber,
        ];

        // Send receipt email / SMS to admins
        $adminsStmt = $db->query("SELECT name, email, phone FROM admins WHERE role IN ('Administrator', 'Finance Secretary')");
        $admins = $adminsStmt->fetchAll();
        $anySent = false;
        foreach ($admins as $admin) {
            if ($admin['email'] || $admin['phone']) {
                $sent = sendFinanceReceipt(['name' => $admin['name'], 'email' => $admin['email'], 'phone' => $admin['phone']], $txnData);
                if ($sent) $anySent = true;
            }
        }

        if ($anySent) {
            $db->prepare("UPDATE finance_transactions SET receipt_sent = 1 WHERE id = ?")
               ->execute([$txnId]);
            logActivity("Resent notification for {$tx['type']} for {$weekNumber}", 'finance');
            redirect($returnTo . '&success=receipt_resent');
        } else {
            redirect($returnTo . '&error=send_failed');
        }

    } catch (PDOException $e) {
        error_log('resend_receipt error: ' . $e->getMessage());
        redirect($returnTo . '&error=db_error');
    }
}

redirect($redirect . '?error=unknown_action');
