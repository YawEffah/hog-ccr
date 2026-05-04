<?php
/**
 * Welfare Notification Utility
 * Now powered by Gmail SMTP via PHPMailer (see includes/helpers.php).
 */

require_once __DIR__ . '/helpers.php';

/**
 * Build the standard welfare SMS/plain-text message body.
 */
function buildWelfareMessage(string $name, float $amount, string $date, string $reference): string
{
    $formatted = number_format($amount, 2);
    $ref       = $reference ?: 'N/A';
    return "Dear {$name}, your welfare contribution of GH\u{20B5} {$formatted} on {$date} "
         . "(Ref: {$ref}) has been received. God bless you. — House of Grace CCR";
}

/**
 * Send a welfare payment notification to a single member via email.
 * Falls back to logging if email is not available.
 *
 * @param array  $member    ['name', 'phone', 'email']
 * @param float  $amount
 * @param string $date
 * @param string $reference
 * @return bool
 */
function sendWelfareNotification(array $member, float $amount, string $date, string $reference): bool
{
    $emailSent = false;
    // Send email if address is available
    if (!empty($member['email'])) {
        $emailSent = sendWelfareEmail($member, $amount, $date, $reference);
    }

    $smsSent = false;
    // Send SMS if phone is available
    if (!empty($member['phone'])) {
        $message = buildWelfareMessage($member['name'], $amount, $date, $reference);
        $smsSent = sendSMS($member['phone'], $message);
    }

    return $emailSent || $smsSent;
}

/**
 * Send bulk notifications to members who paid on a given date.
 *
 * @param array  $members  Each: ['name', 'phone', 'email', 'amount', 'reference']
 * @param string $date     Display date string e.g. "29 Apr 2026"
 * @return array           ['sent' => int, 'failed' => int]
 */
function sendBulkWelfareNotifications(array $members, string $date): array
{
    $sent = $failed = 0;
    foreach ($members as $m) {
        $ok = sendWelfareNotification(
            ['name' => $m['name'], 'phone' => $m['phone'], 'email' => $m['email'] ?? ''],
            (float)$m['amount'],
            $date,
            $m['reference'] ?? ''
        );
        $ok ? $sent++ : $failed++;
    }
    return ['sent' => $sent, 'failed' => $failed];
}
