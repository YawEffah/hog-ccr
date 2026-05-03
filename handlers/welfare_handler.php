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

$action   = $_POST['action'] ?? '';
$db       = getDB();
$redirect = '../welfare.php';

// ── ENROL MEMBER ──────────────────────────────────────────────────────────────
if ($action === 'enrol_welfare') {
    $memberId      = (int)($_POST['member_id']      ?? 0);
    $enrolDate     = $_POST['enrol_date']           ?? date('Y-m-d');
    $monthlyAmount = (float)($_POST['monthly_amount'] ?? 0);
    $notes         = trim($_POST['notes']           ?? '');

    if (!$memberId) {
        redirect($redirect . '?error=no_member');
    }

    try {
        $stmt = $db->prepare(
            "INSERT INTO welfare_members (member_id, enrol_date, monthly_amount, notes, enrolled_by)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $memberId, $enrolDate, $monthlyAmount, $notes ?: null, $_SESSION['user_id']
        ]);

        // Fetch name for log
        $name = $db->prepare("SELECT CONCAT(first_name,' ',last_name) FROM members WHERE id=?");
        $name->execute([$memberId]);
        $fullName = $name->fetchColumn() ?: 'Unknown';

        logActivity("Enrolled {$fullName} into Welfare", 'welfare');
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
    $sendNotif    = isset($_POST['send_notification']);

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

        $notifSent = 0;
        if ($sendNotif && $member) {
            $displayDate = date('j F Y', strtotime($payDate));
            $sent = sendWelfareNotification(
                [
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

        $name = $member ? $member['first_name'] . ' ' . $member['last_name'] : 'Unknown';
        logActivity("Recorded welfare payment of " . formatGhc($amount) . " from {$name}", 'welfare');
        redirect($redirect . '?success=payment_recorded');

    } catch (PDOException $e) {
        error_log('record_welfare_payment error: ' . $e->getMessage());
        redirect($redirect . '?error=db_error');
    }
}

// ── SEND BULK MESSAGES ────────────────────────────────────────────────────────
if ($action === 'send_welfare_messages') {
    $payDate = $_POST['payment_date'] ?? date('Y-m-d');
    $channel = $_POST['channel']      ?? 'email';

    try {
        // Fetch all contributions on the given date with member details
        $stmt = $db->prepare(
            "SELECT wc.amount, wc.reference_no,
                    m.first_name, m.last_name, m.phone, m.email
             FROM welfare_contributions wc
             JOIN welfare_members wm ON wc.welfare_id = wm.id
             JOIN members m ON wm.member_id = m.id
             WHERE wc.payment_date = ?"
        );
        $stmt->execute([$payDate]);
        $payers = $stmt->fetchAll();

        $displayDate = date('j F Y', strtotime($payDate));

        $members = array_map(fn($p) => [
            'name'      => $p['first_name'] . ' ' . $p['last_name'],
            'phone'     => $p['phone'],
            'email'     => $p['email'] ?? '',
            'amount'    => $p['amount'],
            'reference' => $p['reference_no'] ?? '',
        ], $payers);

        $result = sendBulkWelfareNotifications($members, $displayDate);

        logActivity(
            "Sent bulk welfare messages for {$displayDate}: {$result['sent']} sent, {$result['failed']} failed",
            'welfare'
        );
        redirect($redirect . '?success=messages_sent&sent=' . $result['sent'] . '&failed=' . $result['failed']);

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

redirect($redirect . '?error=unknown_action');
