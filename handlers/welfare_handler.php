<?php
/**
 * Welfare Handler — Enrol, Record Payment, Bulk Message
 * POST actions: enrol_welfare | record_welfare_payment | send_welfare_messages
 */
require_once '../includes/auth.php';
requireAuth();
require_once '../includes/db.php';
require_once '../includes/helpers.php';
require_once '../includes/welfare_notify.php';

verifyCsrf();

$action   = $_POST['action'] ?? $_GET['action'] ?? '';
$db       = getDB();
$redirect = '../welfare.php';

// ── FETCH PAYERS FOR DATE (AJAX — legacy, kept for backward compat) ──────────
if ($action === 'fetch_payers_for_date') {
    $date = $_GET['date'] ?? date('Y-m-d');
    try {
        $stmt = $db->prepare(
            "SELECT m.first_name, m.last_name, m.phone, wc.amount, m.email
             FROM welfare_contributions wc
             JOIN welfare_members wm ON wc.welfare_id = wm.id
             JOIN members m ON wm.member_id = m.id
             WHERE wc.payment_date = ?"
        );
        $stmt->execute([$date]);
        $payers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'recipients' => $payers]);
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// ── FETCH WELFARE RECIPIENTS BY AUDIENCE (AJAX) ───────────────────────────────
if ($action === 'fetch_welfare_recipients') {
    $audience     = $_GET['audience'] ?? 'all';
    $date         = $_GET['date']     ?? date('Y-m-d');
    $currentMonth = date('Y-m');

    try {
        if ($audience === 'all') {
            $stmt = $db->prepare(
                "SELECT m.first_name, m.last_name, m.phone, m.email
                 FROM welfare_members wm
                 JOIN members m ON wm.member_id = m.id
                 WHERE m.status = 'Active'
                 ORDER BY m.last_name ASC"
            );
            $stmt->execute();
        } elseif ($audience === 'arrears') {
            $stmt = $db->prepare(
                "SELECT m.first_name, m.last_name, m.phone, m.email
                 FROM welfare_members wm
                 JOIN members m ON wm.member_id = m.id
                 WHERE m.status = 'Active'
                   AND wm.id NOT IN (
                       SELECT DISTINCT welfare_id
                       FROM welfare_contributions
                       WHERE DATE_FORMAT(payment_date, '%Y-%m') = ?
                   )
                 ORDER BY m.last_name ASC"
            );
            $stmt->execute([$currentMonth]);
        } else {
            $stmt = $db->prepare(
                "SELECT m.first_name, m.last_name, m.phone, m.email, wc.amount
                 FROM welfare_contributions wc
                 JOIN welfare_members wm ON wc.welfare_id = wm.id
                 JOIN members m ON wm.member_id = m.id
                 WHERE wc.payment_date = ?
                 ORDER BY m.last_name ASC"
            );
            $stmt->execute([$date]);
        }

        $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'recipients' => $recipients]);
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// ── ENROL MEMBER ──────────────────────────────────────────────────────────────
if ($action === 'enrol_welfare') {
    $memberId      = (int)($_POST['member_id']      ?? 0);
    $enrolDate     = $_POST['enrol_date']           ?? date('Y-m-d');
    $familyGroup   = $_POST['family_group']         ?? null;
    $monthlyAmount = (float)($_POST['monthly_amount'] ?? 0);
    $notes         = trim($_POST['notes']           ?? '');

    if (!$memberId) {
        redirect($redirect . '?error=no_member');
    }

    try {
        $stmt = $db->prepare(
            "INSERT INTO welfare_members (member_id, enrol_date, family_group, monthly_amount, notes, enrolled_by)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $memberId, $enrolDate, $familyGroup, $monthlyAmount, $notes ?: null, $_SESSION['user_id']
        ]);

        // Fetch name for log
        $name = $db->prepare("SELECT CONCAT(first_name,' ',last_name) FROM members WHERE id=?");
        $name->execute([$memberId]);
        $fullName = $name->fetchColumn() ?: 'Unknown';

        logActivity("Enrolled {$fullName} into Welfare", 'welfare');

        // Send Welcome Message
        if (isset($_POST['send_welcome'])) {
            // Fetch member details if we don't have them (we only have the ID)
            $mStmt = $db->prepare("SELECT first_name, last_name, phone, email FROM members WHERE id = ?");
            $mStmt->execute([$memberId]);
            $member = $mStmt->fetch();
            if ($member) {
                sendWelfareWelcomeMessage(
                    [
                        'name'  => $member['first_name'] . ' ' . $member['last_name'],
                        'phone' => $member['phone'],
                        'email' => $member['email'] ?? '',
                    ],
                    $monthlyAmount
                );
            }
        }

        redirect($redirect . '?success=enrolled');

    } catch (PDOException $e) {
        // Duplicate entry = already enrolled
        if ($e->getCode() === '23000') {
            redirect($redirect . '?error=already_enrolled');
        }
        error_log('enrol_welfare error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── RECORD PAYMENT ────────────────────────────────────────────────────────────
if ($action === 'record_welfare_payment') {
    $welfareId    = (int)($_POST['welfare_member_id'] ?? 0);
    $amount       = (float)($_POST['amount']          ?? 0);
    $method       = $_POST['payment_method']          ?? 'Cash';
    $reference    = trim($_POST['reference']          ?? '');
    $payDate      = $_POST['payment_date']            ?? date('Y-m-d');
    $notes        = trim($_POST['notes']              ?? '');
    
    // Always send notification since the UI toggle was removed
    $sendNotif    = true;

    if (!$welfareId || $amount <= 0) {
        redirect($redirect . '?error=invalid_data');
    }

    try {
        $stmt = $db->prepare(
            "INSERT INTO welfare_contributions
             (welfare_id, amount, payment_method, reference_no, payment_date, notes, recorded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $welfareId, $amount, $method, $reference ?: null,
            $payDate, $notes ?: null, $_SESSION['user_id']
        ]);
        $contribId = (int)$db->lastInsertId();

        // Fetch member details for notification
        $mStmt = $db->prepare(
            "SELECT m.first_name, m.last_name, m.phone, m.email
             FROM welfare_members wm JOIN members m ON wm.member_id = m.id
             WHERE wm.id = ?"
        );
        $mStmt->execute([$welfareId]);
        $member = $mStmt->fetch();
        $name = $member ? $member['first_name'] . ' ' . $member['last_name'] : 'Unknown';

        // Auto-post to Ledger
        $assetCode = ($method === 'Cash') ? '1010' : '1000';
        $assetAccountId = $db->query("SELECT id FROM welfare_accounts WHERE code = '$assetCode'")->fetchColumn();
        $revenueAccountId = $db->query("SELECT id FROM welfare_accounts WHERE code = '4000'")->fetchColumn();
        $desc = "Subscription from {$name}";

        if ($assetAccountId && $revenueAccountId) {
            // Debit Asset (Cash/Bank)
            $db->prepare("INSERT INTO welfare_ledger (transaction_date, account_id, description, debit, credit, reference_no, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")
               ->execute([$payDate, $assetAccountId, $desc, $amount, 0, $reference ?: null, $_SESSION['user_id']]);
            // Credit Revenue (Subscription)
            $db->prepare("INSERT INTO welfare_ledger (transaction_date, account_id, description, debit, credit, reference_no, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")
               ->execute([$payDate, $revenueAccountId, $desc, 0, $amount, $reference ?: null, $_SESSION['user_id']]);
        }

        $notifSent = 0;
        if ($sendNotif && $member) {
            $displayDate = date('j F Y', strtotime($payDate));
            $sent = sendWelfareNotification(
                [
                    'id'    => $welfareId,
                    'name'  => $member['first_name'] . ' ' . $member['last_name'],
                    'phone' => $member['phone'],
                    'email' => $member['email'] ?? '',
                ],
                $amount, $displayDate, $reference
            );
            if ($sent) {
                $notifSent = 1;
                $db->prepare("UPDATE welfare_contributions SET notif_sent = 1 WHERE id = ?")
                   ->execute([$contribId]);
            }
        }

        logActivity("Recorded welfare payment of " . formatGhc($amount) . " from {$name}", 'welfare');
        redirect($redirect . '?success=payment_recorded');

    } catch (PDOException $e) {
        error_log('record_welfare_payment error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── SEND BULK MESSAGES ────────────────────────────────────────────────────────
if ($action === 'send_welfare_messages') {
    $audience    = $_POST['audience']     ?? 'all';
    $payDate     = $_POST['payment_date'] ?? date('Y-m-d');
    $channel     = $_POST['channel']      ?? 'both';
    $messageBody = trim($_POST['message_body'] ?? '');
    $currentMonth = date('Y-m');

    if (!$messageBody) {
        redirect($redirect . '?error=missing_fields');
    }

    try {
        if ($audience === 'all') {
            $stmt = $db->prepare(
                "SELECT m.first_name, m.last_name, m.phone, m.email, NULL as amount
                 FROM welfare_members wm
                 JOIN members m ON wm.member_id = m.id
                 WHERE m.status = 'Active'
                 ORDER BY m.last_name ASC"
            );
            $stmt->execute();
        } elseif ($audience === 'arrears') {
            $stmt = $db->prepare(
                "SELECT m.first_name, m.last_name, m.phone, m.email, NULL as amount
                 FROM welfare_members wm
                 JOIN members m ON wm.member_id = m.id
                 WHERE m.status = 'Active'
                   AND wm.id NOT IN (
                       SELECT DISTINCT welfare_id
                       FROM welfare_contributions
                       WHERE DATE_FORMAT(payment_date, '%Y-%m') = ?
                   )
                 ORDER BY m.last_name ASC"
            );
            $stmt->execute([$currentMonth]);
        } else {
            $stmt = $db->prepare(
                "SELECT m.first_name, m.last_name, m.phone, m.email, wc.amount
                 FROM welfare_contributions wc
                 JOIN welfare_members wm ON wc.welfare_id = wm.id
                 JOIN members m ON wm.member_id = m.id
                 WHERE wc.payment_date = ?
                 ORDER BY m.last_name ASC"
            );
            $stmt->execute([$payDate]);
        }

        $recipients = $stmt->fetchAll();
        $sent = $failed = 0;

        foreach ($recipients as $r) {
            $name      = $r['first_name'] . ' ' . $r['last_name'];
            $ok        = false;

            // Personalise — replace [Name] and optionally [Amount]
            $personalised = str_replace('[Name]', $r['first_name'], $messageBody);
            if (!empty($r['amount'])) {
                $personalised = str_replace('[Amount]', 'GH₵ ' . number_format((float)$r['amount'], 2), $personalised);
            }

            if (($channel === 'sms' || $channel === 'both') && !empty($r['phone'])) {
                if (sendSMS($r['phone'], $personalised)) $ok = true;
            }
            if (($channel === 'email' || $channel === 'both') && !empty($r['email'])) {
                $html = buildWelfareBulkEmailHtml($name, $personalised);
                if (sendEmail($r['email'], $name, 'Adom Fie CCR Community — Welfare Message', $html)) $ok = true;
            }

            $ok ? $sent++ : $failed++;
        }

        $audienceLabel = match($audience) {
            'all'    => 'all welfare members',
            'arrears'=> 'members in arrears',
            default  => "payers on {$payDate}",
        };
        logActivity("Sent bulk welfare message to {$audienceLabel}: {$sent} sent, {$failed} failed", 'welfare');
        redirect($redirect . "?success=messages_sent&sent={$sent}&failed={$failed}");

    } catch (PDOException $e) {
        error_log('send_welfare_messages error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── REMOVE WELFARE MEMBER ─────────────────────────────────────────────────────
if ($action === 'remove_welfare_member') {
    $welfareId = (int)($_POST['welfare_member_id'] ?? 0);

    if (!$welfareId) {
        redirect($redirect . '?error=invalid_data');
    }

    try {
        // Fetch name for log before deletion
        $mStmt = $db->prepare(
            "SELECT CONCAT(m.first_name,' ',m.last_name)
             FROM welfare_members wm JOIN members m ON wm.member_id = m.id
             WHERE wm.id = ?"
        );
        $mStmt->execute([$welfareId]);
        $fullName = $mStmt->fetchColumn() ?: 'Unknown';

        // Contributions cascade-delete via FK ON DELETE CASCADE
        $db->prepare("DELETE FROM welfare_members WHERE id = ?")->execute([$welfareId]);

        logActivity("Removed {$fullName} from Welfare", 'welfare');
        redirect($redirect . '?success=welfare_removed');

    } catch (PDOException $e) {
        error_log('remove_welfare_member error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}
// ── DELETE WELFARE CONTRIBUTION ────────────────────────────────────────────────
if ($action === 'delete_contribution') {
    $contribId = (int)($_POST['contribution_id'] ?? 0);

    if (!$contribId) {
        redirect($redirect . '?error=invalid_data');
    }

    try {
        $db->prepare("DELETE FROM welfare_contributions WHERE id = ?")->execute([$contribId]);
        logActivity("Deleted welfare contribution ID: {$contribId}", 'welfare');
        redirect($redirect . '?success=contribution_deleted');
    } catch (PDOException $e) {
        error_log('delete_contribution error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── RESEND RECEIPT ────────────────────────────────────────────────────────────
if ($action === 'resend_welfare_receipt') {
    $contribId = (int)($_POST['contrib_id'] ?? 0);
    $returnTo  = $_POST['return_to'] ?? $redirect;

    if (!$contribId) {
        redirect($returnTo . '?error=invalid_data');
    }

    try {
        $stmt = $db->prepare(
            "SELECT wc.*, m.first_name, m.last_name, m.phone, m.email
             FROM welfare_contributions wc
             JOIN welfare_members wm ON wc.welfare_id = wm.id
             JOIN members m ON wm.member_id = m.id
             WHERE wc.id = ?"
        );
        $stmt->execute([$contribId]);
        $tx = $stmt->fetch();

        if (!$tx) {
            redirect($returnTo . '?error=not_found');
        }

        $memberName  = $tx['first_name'] . ' ' . $tx['last_name'];
        $displayDate = date('j F Y', strtotime($tx['payment_date']));

        $memberData = [
            'id'    => $tx['welfare_id'],
            'name'  => $memberName,
            'phone' => $tx['phone'],
            'email' => $tx['email'] ?? '',
        ];

        $sent = sendWelfareNotification($memberData, (float)$tx['amount'], $displayDate, $tx['reference_no'] ?? '', 'both');

        if ($sent) {
            $db->prepare("UPDATE welfare_contributions SET notif_sent = 1 WHERE id = ?")->execute([$contribId]);
            logActivity("Resent welfare receipt for GH₵ {$tx['amount']} to {$memberName}", 'welfare');
            redirect($returnTo . '&success=receipt_resent');
        } else {
            redirect($returnTo . '&error=send_failed');
        }

    } catch (PDOException $e) {
        error_log('resend_welfare_receipt error: ' . $e->getMessage());
        redirect($returnTo . '&error=db_error');
    }
}
// ── RECORD WELFARE EXPENSE ──────────────────────────────────────────────────────
if ($action === 'record_welfare_expense') {
    $date           = $_POST['expense_date'] ?? date('Y-m-d');
    $expenseCode    = $_POST['expense_account'] ?? '';
    $assetCode      = $_POST['asset_account'] ?? '1000';
    $amount         = (float)($_POST['amount'] ?? 0);
    $recipientType  = $_POST['recipient_type'] ?? 'Member';
    $recipientMemId = !empty($_POST['recipient_member_id']) ? (int)$_POST['recipient_member_id'] : null;
    $recipientName  = trim($_POST['recipient_name'] ?? '');
    $desc           = trim($_POST['description'] ?? '');

    if (!$expenseCode || !$assetCode || $amount <= 0 || !$desc) {
        redirect($redirect . '?error=missing_fields');
    }

    try {
        $expenseId = $db->query("SELECT id FROM welfare_accounts WHERE code = '$expenseCode'")->fetchColumn();
        $assetId   = $db->query("SELECT id FROM welfare_accounts WHERE code = '$assetCode'")->fetchColumn();

        if (!$expenseId || !$assetId) {
            redirect($redirect . '?error=invalid_data');
        }

        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO welfare_expenses (expense_date, amount, category_id, asset_account_id, recipient_type, recipient_member_id, recipient_name, description, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$date, $amount, $expenseId, $assetId, $recipientType, $recipientMemId, $recipientName, $desc, $_SESSION['user_id']]);
        
        $expId = $db->lastInsertId();
        $refNo = 'WEXP-' . $expId;

        $db->prepare("UPDATE welfare_expenses SET reference_no = ? WHERE id = ?")->execute([$refNo, $expId]);

        // Debit Expense
        $db->prepare("INSERT INTO welfare_ledger (transaction_date, account_id, description, debit, credit, reference_no, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$date, $expenseId, $desc, $amount, 0, $refNo, $_SESSION['user_id']]);

        // Credit Asset
        $db->prepare("INSERT INTO welfare_ledger (transaction_date, account_id, description, debit, credit, reference_no, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$date, $assetId, $desc, 0, $amount, $refNo, $_SESSION['user_id']]);

        $db->commit();

        logActivity("Recorded welfare expense: GH₵ " . number_format($amount, 2) . " for '{$desc}'", 'welfare');
        redirect($redirect . '?success=expense_recorded');

    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('record_welfare_expense error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── EDIT WELFARE EXPENSE ────────────────────────────────────────────────────────
if ($action === 'edit_welfare_expense') {
    // Role Check
    if (!in_array($_SESSION['user_data']['role'], ['Administrator', 'Finance Secretary'])) {
        redirect($redirect . '?error=unauthorized');
    }

    $id             = (int)$_POST['expense_id'];
    $date           = $_POST['expense_date'] ?? date('Y-m-d');
    $expenseCode    = $_POST['expense_account'] ?? '';
    $assetCode      = $_POST['asset_account'] ?? '';
    $amount         = (float)($_POST['amount'] ?? 0);
    $recipientType  = $_POST['recipient_type'] ?? 'Member';
    $recipientMemId = !empty($_POST['recipient_member_id']) ? (int)$_POST['recipient_member_id'] : null;
    $recipientName  = trim($_POST['recipient_name'] ?? '');
    $desc           = trim($_POST['description'] ?? '');

    if (!$id || !$expenseCode || !$assetCode || $amount <= 0 || !$desc) {
        redirect($redirect . '?error=missing_fields');
    }

    try {
        $expenseId = $db->query("SELECT id FROM welfare_accounts WHERE code = '$expenseCode'")->fetchColumn();
        $assetId   = $db->query("SELECT id FROM welfare_accounts WHERE code = '$assetCode'")->fetchColumn();

        if (!$expenseId || !$assetId) {
            redirect($redirect . '?error=invalid_data');
        }

        $db->beginTransaction();

        $stmt = $db->prepare("UPDATE welfare_expenses SET expense_date = ?, amount = ?, category_id = ?, asset_account_id = ?, recipient_type = ?, recipient_member_id = ?, recipient_name = ?, description = ? WHERE id = ?");
        $stmt->execute([$date, $amount, $expenseId, $assetId, $recipientType, $recipientMemId, $recipientName, $desc, $id]);
        
        $refNo = 'WEXP-' . $id;

        // Delete old ledger entries and recreate
        $db->prepare("DELETE FROM welfare_ledger WHERE reference_no = ?")->execute([$refNo]);

        // Debit Expense
        $db->prepare("INSERT INTO welfare_ledger (transaction_date, account_id, description, debit, credit, reference_no, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$date, $expenseId, $desc, $amount, 0, $refNo, $_SESSION['user_id']]);

        // Credit Asset
        $db->prepare("INSERT INTO welfare_ledger (transaction_date, account_id, description, debit, credit, reference_no, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$date, $assetId, $desc, 0, $amount, $refNo, $_SESSION['user_id']]);

        $db->commit();

        logActivity("Updated welfare expense #{$id}: GH₵ " . number_format($amount, 2), 'welfare');
        redirect($redirect . '?success=expense_updated');

    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('edit_welfare_expense error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── DELETE WELFARE EXPENSE ──────────────────────────────────────────────────────
if ($action === 'delete_welfare_expense') {
    // Role Check
    if (!in_array($_SESSION['user_data']['role'], ['Administrator', 'Finance Secretary'])) {
        redirect($redirect . '?error=unauthorized');
    }

    $id = (int)$_POST['expense_id'];

    try {
        $db->beginTransaction();
        
        $db->prepare("DELETE FROM welfare_expenses WHERE id = ?")->execute([$id]);
        $refNo = 'WEXP-' . $id;
        $db->prepare("DELETE FROM welfare_ledger WHERE reference_no = ?")->execute([$refNo]);

        $db->commit();

        logActivity("Deleted welfare expense #{$id}", 'welfare');
        redirect($redirect . '?success=expense_deleted');
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('delete_welfare_expense error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

redirect($redirect . '?error=unknown_action');
