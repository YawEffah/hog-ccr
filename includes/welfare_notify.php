<?php
/**
 * Welfare Notification Utility
 * TODO (Backend): Replace stub with real SMS/email gateway (Arkesel, Twilio, SMTP).
 */

function buildWelfareMessage(string $name, float $amount, string $date, string $reference): string
{
    $formatted = number_format($amount, 2);
    $ref       = $reference ?: 'N/A';
    return "Dear {$name}, your welfare contribution of GH\u{20B5} {$formatted} on {$date} (Ref: {$ref}) has been received. God bless you. \u{2014} House of Grace CCR";
}

function sendWelfareNotification(array $member, float $amount, string $date, string $reference): bool
{
    $message = buildWelfareMessage($member['name'], $amount, $date, $reference);
    $logLine = date('[Y-m-d H:i:s]') . " TO: {$member['name']} | {$member['phone']} | {$member['email']}\n  MSG: {$message}\n";
    $logFile = __DIR__ . '/../assets/docs/welfare_notifications.log';
    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    return true; // stub always succeeds
}

function sendBulkWelfareNotifications(array $members, string $date): array
{
    $sent = $failed = 0;
    foreach ($members as $m) {
        sendWelfareNotification(
            ['name' => $m['name'], 'phone' => $m['phone'], 'email' => $m['email']],
            (float)$m['amount'], $date, $m['reference'] ?? ''
        ) ? $sent++ : $failed++;
    }
    return ['sent' => $sent, 'failed' => $failed];
}
