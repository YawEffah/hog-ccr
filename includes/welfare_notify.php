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
 * @param string $channel   'sms', 'email', or 'both'
 * @return bool
 */
function sendWelfareNotification(array $member, float $amount, string $date, string $reference, string $channel = 'both'): bool
{
    $emailSent = false;
    // Send email if channel allows and address is available
    if (($channel === 'email' || $channel === 'both') && !empty($member['email'])) {
        $emailSent = sendWelfareEmail($member, $amount, $date, $reference);
    }

    $smsSent = false;
    // Send SMS if channel allows and phone is available
    if (($channel === 'sms' || $channel === 'both') && !empty($member['phone'])) {
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
 * @param string $channel  'sms', 'email', or 'both'
 * @return array           ['sent' => int, 'failed' => int]
 */
function sendBulkWelfareNotifications(array $members, string $date, string $channel = 'both'): array
{
    $sent = $failed = 0;
    foreach ($members as $m) {
        $ok = sendWelfareNotification(
            ['name' => $m['name'], 'phone' => $m['phone'], 'email' => $m['email'] ?? ''],
            (float)$m['amount'],
            $date,
            $m['reference'] ?? '',
            $channel
        );
        $ok ? $sent++ : $failed++;
    }
    return ['sent' => $sent, 'failed' => $failed];
}

/**
 * Send a welcome notification to a newly enrolled welfare member.
 *
 * @param array $member         ['name', 'phone', 'email']
 * @param float $monthlyAmount
 * @return bool
 */
function sendWelfareWelcomeMessage(array $member, float $monthlyAmount): bool
{
    $name = htmlspecialchars($member['name']);
    $amt  = number_format($monthlyAmount, 2);
    $year = date('Y');

    $html = <<<HTML
    <div style="font-family:'DM Sans',Arial,sans-serif;max-width:540px;margin:0 auto;border:1px solid #99F6E4;border-radius:12px;overflow:hidden;">
      <div style="background:#0D9488;padding:40px 32px;text-align:center;">
        <h1 style="color:#ffffff;font-size:24px;margin:0;">Welcome to Welfare Scheme</h1>
        <p style="color:#CCFBF1;font-size:14px;margin:8px 0 0;">House of Grace CCR</p>
      </div>
      <div style="padding:32px;">
        <p style="color:#475569;font-size:15px;">Dear <strong>{$name}</strong>,</p>
        <p style="color:#475569;font-size:15px;line-height:1.6;">
          We are pleased to inform you that you have been successfully enrolled in the House of Grace CCR Welfare Scheme. This scheme is designed to support our members in times of need.
        </p>
        <div style="background:#F0FDFA;border-radius:8px;padding:20px;margin:24px 0;text-align:center;border:1px solid #99F6E4;">
          <p style="margin:0;color:#0F766E;font-size:13px;text-transform:uppercase;letter-spacing:1px;">Monthly Contribution</p>
          <p style="margin:8px 0 0;color:#0D9488;font-size:32px;font-weight:700;">GH₵ {$amt}</p>
        </div>
        <p style="color:#475569;font-size:15px;">
          Your contributions will go a long way in supporting the community. God bless you for your commitment.
        </p>
        <p style="margin-top:28px;font-size:14px;color:#94A3B8;">
          Blessings, <br>
          <strong>House of Grace CCR Welfare Team</strong>
        </p>
      </div>
      <div style="background:#F8FAFC;padding:16px 32px;text-align:center;font-size:11px;color:#94A3B8;">
        &copy; {$year} House of Grace CCR Welfare. All rights reserved.
      </div>
    </div>
    HTML;

    $emailSent = false;
    if (!empty($member['email'])) {
        $emailSent = sendEmail(
            $member['email'],
            $member['name'],
            'Welcome to House of Grace CCR Welfare Scheme!',
            $html
        );
    }

    $smsSent = false;
    if (!empty($member['phone'])) {
        $smsMsg = "Welcome to House of Grace CCR Welfare Scheme, {$member['name']}! Your monthly contribution is set to GH₵ {$amt}. God bless you.";
        $smsSent = sendSMS($member['phone'], $smsMsg);
    }

    return $emailSent || $smsSent;
}

/**
 * Build a branded HTML email body for custom bulk welfare messages.
 *
 * @param string $name    Recipient full name
 * @param string $message The personalised plain-text message body
 * @return string         HTML email string
 */
function buildWelfareBulkEmailHtml(string $name, string $message): string
{
    $safeName    = htmlspecialchars($name);
    $safeMessage = nl2br(htmlspecialchars($message));
    $year        = date('Y');

    return <<<HTML
    <div style="font-family:'DM Sans',Arial,sans-serif;max-width:540px;margin:0 auto;border:1px solid #99F6E4;border-radius:12px;overflow:hidden;">
      <div style="background:#0D9488;padding:28px 32px;text-align:center;">
        <h1 style="color:#ffffff;font-size:22px;margin:0;">Welfare Message</h1>
        <p style="color:#CCFBF1;font-size:13px;margin:4px 0 0;">House of Grace CCR</p>
      </div>
      <div style="padding:32px;">
        <p style="color:#475569;font-size:14px;">Dear <strong>{$safeName}</strong>,</p>
        <p style="color:#475569;font-size:15px;line-height:1.7;">{$safeMessage}</p>
        <p style="margin-top:28px;font-size:13px;color:#94A3B8;">
          Blessings,<br>
          <strong>House of Grace CCR Welfare Team</strong>
        </p>
      </div>
      <div style="background:#F8FAFC;padding:16px 32px;text-align:center;font-size:11px;color:#94A3B8;">
        &copy; {$year} House of Grace CCR Welfare. All rights reserved.
      </div>
    </div>
    HTML;
}
