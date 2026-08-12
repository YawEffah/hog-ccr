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

    $allowedMethods = ['Cash','MoMo','Bank Transfer','Cheque'];

    if ($amount <= 0 || empty($type) || !in_array($method, $allowedMethods, true)) {
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

        // Double-entry accounting automation
        $typeToAccMap = [
            'Tithe' => '4200',
            'Offering' => '4300',
            'Donation' => '4400',
            'Pledge' => '4500',
            'Project Contribution' => '4600',
            'Half Year Thanks Giving' => '4300',
            'End of Year Thanks Giving' => '4300'
        ];
        $revenueCode = $typeToAccMap[$type] ?? '4300';
        $revenueId = $db->query("SELECT id FROM finance_accounts WHERE code = '$revenueCode'")->fetchColumn();
        $cashId = $db->query("SELECT id FROM finance_accounts WHERE code = '1000'")->fetchColumn();

        if ($revenueId && $cashId) {
            $ledgerRef = "FIN-TXN-$txnId";
            $desc = "Receipt: $type" . ($notes ? " - $notes" : "");
            
            // Debit Cash
            $db->prepare("INSERT INTO finance_ledger (transaction_date, account_id, description, debit, credit, reference_no, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")
               ->execute([$date, $cashId, $desc, $amount, 0, $ledgerRef, $_SESSION['user_id']]);
            
            // Credit Revenue
            $db->prepare("INSERT INTO finance_ledger (transaction_date, account_id, description, debit, credit, reference_no, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")
               ->execute([$date, $revenueId, $desc, 0, $amount, $ledgerRef, $_SESSION['user_id']]);
        }

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
            $adminsStmt = $db->query("SELECT name, email, phone FROM admins WHERE role IN (SELECT name FROM system_roles WHERE perm_manage_finance = 1)");
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
        
        // Trigger notification
        notifyRoles(
            ['Administrator', 'Finance Secretary', 'Head Pastor'],
            'finance_target',
            'Monthly Target Updated',
            "Target for " . date('F Y', strtotime($monthDate)) . " has been set to " . formatGhc($targetAmount) . ".",
            'finance.php',
            'ph ph-target',
            '#3B82F6'
        );

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
        
        // Also delete from ledger
        $db->prepare("DELETE FROM finance_ledger WHERE reference_no = ?")->execute(["FIN-TXN-$txnId"]);

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
        $adminsStmt = $db->query("SELECT name, email, phone FROM admins WHERE role IN (SELECT name FROM system_roles WHERE perm_manage_finance = 1)");
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

// ── RECORD FINANCE EXPENSE ───────────────────────────────────────────────────
if ($action === 'record_finance_expense') {
    $date         = $_POST['expense_date'] ?? date('Y-m-d');
    $amount       = (float)($_POST['amount'] ?? 0);
    $type         = trim($_POST['type'] ?? '');
    $assetId      = (int)($_POST['asset_account_id'] ?? 0);
    $description  = trim($_POST['description'] ?? '');
    $notes        = trim($_POST['notes'] ?? '');
    $returnTo     = $_POST['return_to'] ?? $redirect;

    if ($amount <= 0 || !$type || !$assetId || !$description) {
        redirect($returnTo . '?error=invalid_data');
    }

    try {
        // Look up category_id based on the type name
        $catStmt = $db->prepare("SELECT id FROM finance_accounts WHERE name = ? AND type = 'Expense' AND fund = 'General'");
        $catStmt->execute([$type]);
        $categoryId = (int)$catStmt->fetchColumn();

        if (!$categoryId) {
            redirect($returnTo . '?error=invalid_data');
        }

        $stmt = $db->prepare(
            "INSERT INTO finance_expenses
             (expense_date, amount, type, asset_account_id, description, notes, recorded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $date, $amount, $type, $assetId, $description,
            $notes ?: null, $_SESSION['user_id']
        ]);
        
        $expId = (int)$db->lastInsertId();
        $refNo = "FEXP-$expId";
        
        $db->prepare("UPDATE finance_expenses SET reference_no = ? WHERE id = ?")
           ->execute([$refNo, $expId]);

        // Double entry ledger
        $ledgerDesc = "Finance Expense: $description" . ($notes ? " - $notes" : "");
        
        // Debit Expense
        $db->prepare("INSERT INTO finance_ledger (transaction_date, account_id, description, debit, credit, reference_no, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$date, $categoryId, $ledgerDesc, $amount, 0, $refNo, $_SESSION['user_id']]);
           
        // Credit Asset
        $db->prepare("INSERT INTO finance_ledger (transaction_date, account_id, description, debit, credit, reference_no, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$date, $assetId, $ledgerDesc, 0, $amount, $refNo, $_SESSION['user_id']]);

        logActivity("Recorded finance expense of " . formatGhc($amount) . " for $description", 'finance');
        redirect($returnTo . '?success=expense_recorded');

    } catch (PDOException $e) {
        error_log('record_finance_expense error: ' . $e->getMessage());
        redirect($returnTo . '?error=db_error');
    }
}

// ── EDIT FINANCE EXPENSE ─────────────────────────────────────────────────────
if ($action === 'edit_finance_expense') {
    if (!hasPermission('perm_manage_finance')) redirect($redirect);

    $id           = (int)($_POST['expense_id'] ?? 0);
    $date         = $_POST['expense_date'] ?? date('Y-m-d');
    $amount       = (float)($_POST['amount'] ?? 0);
    $type         = trim($_POST['type'] ?? '');
    $assetId      = (int)($_POST['asset_account_id'] ?? 0);
    $description  = trim($_POST['description'] ?? '');
    $notes        = trim($_POST['notes'] ?? '');
    $returnTo     = $_POST['return_to'] ?? $redirect;

    if ($id <= 0 || $amount <= 0 || !$type || !$assetId || !$description) {
        redirect($returnTo . '?error=invalid_data');
    }

    try {
        // Look up category_id based on the type name
        $catStmt = $db->prepare("SELECT id FROM finance_accounts WHERE name = ? AND type = 'Expense' AND fund = 'General'");
        $catStmt->execute([$type]);
        $categoryId = (int)$catStmt->fetchColumn();

        if (!$categoryId) {
            redirect($returnTo . '?error=invalid_data');
        }

        $stmt = $db->prepare(
            "UPDATE finance_expenses
             SET expense_date = ?, amount = ?, type = ?, asset_account_id = ?, description = ?, notes = ?
             WHERE id = ?"
        );
        $stmt->execute([
            $date, $amount, $type, $assetId, $description,
            $notes ?: null, $id
        ]);
        
        $refNo = "FEXP-$id";

        // Re-do ledger entries
        $db->prepare("DELETE FROM finance_ledger WHERE reference_no = ?")->execute([$refNo]);

        $ledgerDesc = "Finance Expense: $description" . ($notes ? " - $notes" : "");
        
        // Debit Expense
        $db->prepare("INSERT INTO finance_ledger (transaction_date, account_id, description, debit, credit, reference_no, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$date, $categoryId, $ledgerDesc, $amount, 0, $refNo, $_SESSION['user_id']]);
           
        // Credit Asset
        $db->prepare("INSERT INTO finance_ledger (transaction_date, account_id, description, debit, credit, reference_no, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$date, $assetId, $ledgerDesc, 0, $amount, $refNo, $_SESSION['user_id']]);

        logActivity("Updated finance expense #$id", 'finance');
        redirect($returnTo . '?success=expense_updated');

    } catch (PDOException $e) {
        error_log('edit_finance_expense error: ' . $e->getMessage());
        redirect($returnTo . '?error=db_error');
    }
}

// ── DELETE FINANCE EXPENSE ───────────────────────────────────────────────────
if ($action === 'delete_finance_expense') {
    if (!hasPermission('perm_manage_finance')) redirect($redirect);
    
    $id = (int)($_POST['expense_id'] ?? 0);
    $returnTo = $_POST['return_to'] ?? $redirect;

    if (!$id) redirect($returnTo . '?error=invalid_data');

    try {
        $db->prepare("DELETE FROM finance_expenses WHERE id = ?")->execute([$id]);
        $db->prepare("DELETE FROM finance_ledger WHERE reference_no = ?")->execute(["FEXP-$id"]);

        logActivity("Deleted finance expense #$id", 'finance');
        redirect($returnTo . '?success=expense_deleted');

    } catch (PDOException $e) {
        error_log('delete_finance_expense error: ' . $e->getMessage());
        redirect($returnTo . '?error=db_error');
    }
}

redirect($redirect . '?error=unknown_action');
