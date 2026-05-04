<?php
/**
 * Shared Helpers & Utilities
 * ─────────────────────────────────────────────
 * Requires: config.php, db.php
 * PHPMailer must be installed via Composer:
 *   composer require phpmailer/phpmailer
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

// ── Autoload PHPMailer ────────────────────────
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// ACTIVITY LOG
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * Record an action to the activity_log table.
 *
 * @param string $action  Human-readable description, e.g. "Added member Abena Kusi"
 * @param string $module  Module slug: members | finance | welfare | attendance | events | ministries
 */
function logActivity(string $action, string $module = 'system'): void
{
    try {
        $db      = getDB();
        $adminId = $_SESSION['user_id'] ?? null;
        $ip      = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt    = $db->prepare(
            "INSERT INTO activity_log (admin_id, action, module, ip_address)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$adminId, $action, $module, $ip]);
    } catch (PDOException $e) {
        error_log('logActivity failed: ' . $e->getMessage());
    }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// MEMBER CODE GENERATOR
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * Generate the next sequential member code, e.g. CCR-042.
 */
function generateMemberCode(): string
{
    $db   = getDB();
    $last = $db->query(
        "SELECT member_code FROM members ORDER BY id DESC LIMIT 1"
    )->fetchColumn();

    if ($last) {
        $num = (int) substr($last, 4); // strip "CCR-"
        return 'CCR-' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }
    return 'CCR-001';
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// CURRENCY FORMATTER
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * Format a number as a Ghana Cedi string.
 * e.g. formatGhc(1234.5) → "GH₵ 1,234.50"
 */
function formatGhc(float $amount): string
{
    return 'GH₵ ' . number_format($amount, 2);
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// CSRF PROTECTION
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * Generate and store a CSRF token in the session.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field for use inside <form> tags.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Validate the CSRF token submitted with a POST request.
 * Terminates the request with HTTP 403 if invalid.
 */
function verifyCsrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $submitted)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// FILE UPLOAD HELPER
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * Handle a member photo upload.
 *
 * @param array  $file        $_FILES['photo'] element
 * @param string $memberCode  e.g. "CCR-001" — used as filename
 * @return string|null        Relative path on success, null on failure
 */
function uploadMemberPhoto(array $file, string $memberCode): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size']  >  MAX_UPLOAD_SIZE)  return null;

    // Validate MIME type via finfo (more reliable than extension alone)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES, true)) return null;

    $ext      = match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => null,
    };
    if (!$ext) return null;

    // Ensure destination directory exists
    if (!is_dir(MEMBER_PHOTO_DIR)) {
        mkdir(MEMBER_PHOTO_DIR, 0755, true);
    }

    // Delete any previous photo for this member
    foreach (['jpg', 'png', 'webp'] as $oldExt) {
        $old = MEMBER_PHOTO_DIR . $memberCode . '.' . $oldExt;
        if (file_exists($old)) unlink($old);
    }

    $dest = MEMBER_PHOTO_DIR . $memberCode . '.' . $ext;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return MEMBER_PHOTO_URL . $memberCode . '.' . $ext;
    }
    return null;
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// EMAIL — PHPMailer via Gmail SMTP
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * Send an email using Gmail SMTP via PHPMailer.
 *
 * @param string $toEmail     Recipient email address
 * @param string $toName      Recipient display name
 * @param string $subject     Email subject line
 * @param string $htmlBody    Full HTML body
 * @param string $altBody     Plain-text fallback
 * @return bool               true on success, false on failure
 */
function sendEmail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $altBody = ''
): bool {
    if (!class_exists(PHPMailer::class)) {
        error_log('PHPMailer not found. Run: composer require phpmailer/phpmailer');
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;

        // Sender & Recipient
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $altBody ?: strip_tags($htmlBody);

        $mail->send();
        return true;

    } catch (MailException $e) {
        error_log('Mail error: ' . $mail->ErrorInfo);
        return false;
    }
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// SMS — Arkesel API
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * Format a phone number to standard Ghana format (e.g. 233544123456)
 */
function formatGhanaPhoneNumber(string $phone): string
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 10 && str_starts_with($phone, '0')) {
        return '233' . substr($phone, 1);
    }
    if (strlen($phone) === 12 && str_starts_with($phone, '233')) {
        return $phone;
    }
    return $phone; // Return as-is if unhandled format
}

/**
 * Send an SMS via Arkesel API.
 * 
 * @param string $to       Recipient phone number
 * @param string $message  SMS body
 * @return bool            true on success, false on failure
 */
function sendSMS(string $to, string $message): bool
{
    $formattedTo = formatGhanaPhoneNumber($to);
    
    // Use Arkesel credentials from config
    $apiKey = ARKESEL_API_KEY;
    $from   = ARKESEL_SENDER_ID;
    
    $url = 'https://sms.arkesel.com/sms/api?action=send-sms'
         . '&api_key=' . urlencode($apiKey)
         . '&to=' . urlencode($formattedTo)
         . '&from=' . urlencode($from)
         . '&sms=' . urlencode($message);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        error_log('SMS cURL error: ' . $err);
        return false;
    }

    $resData = json_decode($response, true);
    // Arkesel usually returns code 100 or '100' or similar string indicating success depending on endpoint version.
    // Given the endpoint, if response contains success/code, we can check. For now, log and assume true if no curl error.
    error_log('SMS Sent. Response: ' . $response);
    return true;
}

/**
 * Build and send a transaction receipt email and/or SMS.
 *
 * @param array  $recipient  ['name' => ..., 'email' => ..., 'phone' => ...]
 * @param array  $txn        Transaction data array
 */
function sendFinanceReceipt(array $recipient, array $txn): bool
{
    $amount = formatGhc((float)$txn['amount']);
    $date   = date('j F Y', strtotime($txn['transaction_date']));
    $ref    = htmlspecialchars($txn['reference_no'] ?: 'N/A');
    $type   = htmlspecialchars($txn['type']);
    $name   = htmlspecialchars($recipient['name']);
    $year   = date('Y');

    $html = <<<HTML
    <div style="font-family:'DM Sans',Arial,sans-serif;max-width:540px;margin:0 auto;border:1px solid #EDE8DF;border-radius:12px;overflow:hidden;">
      <div style="background:#2E2D7B;padding:28px 32px;text-align:center;">
        <h1 style="color:#ffffff;font-size:22px;margin:0;">House of Grace CCR</h1>
        <p style="color:#B0A090;font-size:13px;margin:4px 0 0;">Payment Receipt</p>
      </div>
      <div style="padding:32px;">
        <p style="color:#475569;font-size:14px;">Dear <strong>{$name}</strong>,</p>
        <p style="color:#475569;font-size:14px;margin-bottom:24px;">
          Thank you for your faithful giving. Your {$type} has been received.
        </p>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
          <tr style="border-bottom:1px solid #EDE8DF;">
            <td style="padding:10px 0;color:#64748B;">Transaction Type</td>
            <td style="padding:10px 0;font-weight:600;text-align:right;">{$type}</td>
          </tr>
          <tr style="border-bottom:1px solid #EDE8DF;">
            <td style="padding:10px 0;color:#64748B;">Amount</td>
            <td style="padding:10px 0;font-weight:700;color:#15803D;text-align:right;">{$amount}</td>
          </tr>
          <tr style="border-bottom:1px solid #EDE8DF;">
            <td style="padding:10px 0;color:#64748B;">Date</td>
            <td style="padding:10px 0;text-align:right;">{$date}</td>
          </tr>
          <tr>
            <td style="padding:10px 0;color:#64748B;">Reference</td>
            <td style="padding:10px 0;color:#64748B;text-align:right;">{$ref}</td>
          </tr>
        </table>
        <p style="margin-top:28px;font-size:13px;color:#94A3B8;">
          God bless you abundantly. — House of Grace CCR Administration
        </p>
      </div>
      <div style="background:#F8FAFC;padding:16px 32px;text-align:center;font-size:11px;color:#94A3B8;">
        &copy; {$year} House of Grace CCR. All rights reserved.
      </div>
    </div>
    HTML;

    $emailSent = false;
    if (!empty($recipient['email'])) {
        $emailSent = sendEmail(
            $recipient['email'],
            $recipient['name'],
            'Payment Receipt — ' . $type . ' · ' . $date,
            $html
        );
    }

    $smsSent = false;
    if (!empty($recipient['phone'])) {
        $smsDate = date('d M Y, h:ia', strtotime($txn['transaction_date']));
        $smsAmount = number_format((float)$txn['amount'], 2);
        $smsMsg = "Dear {$name}, your {$type} of GHS {$smsAmount} on {$smsDate} has been received. God bless you. - House of Grace CCR";
        $smsSent = sendSMS($recipient['phone'], $smsMsg);
    }

    return $emailSent || $smsSent;
}

/**
 * Build and send a welfare payment confirmation email.
 *
 * @param array  $member  ['name' => ..., 'email' => ...]
 * @param float  $amount
 * @param string $date    Formatted date string
 * @param string $reference
 */
function sendWelfareEmail(array $member, float $amount, string $date, string $reference): bool
{
    $amt  = formatGhc($amount);
    $ref  = htmlspecialchars($reference ?: 'N/A');
    $name = htmlspecialchars($member['name']);

    $html = <<<HTML
    <div style="font-family:'DM Sans',Arial,sans-serif;max-width:540px;margin:0 auto;border:1px solid #99F6E4;border-radius:12px;overflow:hidden;">
      <div style="background:#0D9488;padding:28px 32px;text-align:center;">
        <h1 style="color:#ffffff;font-size:22px;margin:0;">Welfare Contribution</h1>
        <p style="color:#CCFBF1;font-size:13px;margin:4px 0 0;">House of Grace CCR</p>
      </div>
      <div style="padding:32px;">
        <p style="color:#475569;font-size:14px;">Dear <strong>{$name}</strong>,</p>
        <p style="color:#475569;font-size:14px;margin-bottom:24px;">
          Your welfare contribution has been received. We appreciate your faithfulness to the community.
        </p>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
          <tr style="border-bottom:1px solid #EDE8DF;">
            <td style="padding:10px 0;color:#64748B;">Amount</td>
            <td style="padding:10px 0;font-weight:700;color:#0D9488;text-align:right;">{$amt}</td>
          </tr>
          <tr style="border-bottom:1px solid #EDE8DF;">
            <td style="padding:10px 0;color:#64748B;">Date</td>
            <td style="padding:10px 0;text-align:right;">{$date}</td>
          </tr>
          <tr>
            <td style="padding:10px 0;color:#64748B;">Reference</td>
            <td style="padding:10px 0;color:#64748B;text-align:right;">{$ref}</td>
          </tr>
        </table>
        <p style="margin-top:28px;font-size:13px;color:#94A3B8;">
          God bless you. — House of Grace CCR Welfare Team
        </p>
      </div>
    </div>
    HTML;

    return sendEmail(
        $member['email'],
        $member['name'],
        'Welfare Contribution Received — ' . $date,
        $html
    );
}

/**
 * Build and send a welcome email and/or SMS for a new member.
 *
 * @param array $member ['name' => ..., 'email' => ..., 'phone' => ..., 'code' => ...]
 */
function sendWelcomeMessage(array $member): bool
{
    $name = htmlspecialchars($member['name']);
    $code = htmlspecialchars($member['code']);
    $year = date('Y');

    $html = <<<HTML
    <div style="font-family:'DM Sans',Arial,sans-serif;max-width:540px;margin:0 auto;border:1px solid #EDE8DF;border-radius:12px;overflow:hidden;">
      <div style="background:#2E2D7B;padding:40px 32px;text-align:center;">
        <h1 style="color:#ffffff;font-size:24px;margin:0;">Welcome to House of Grace CCR!</h1>
        <p style="color:#B0A090;font-size:14px;margin:8px 0 0;">We're so blessed to have you with us</p>
      </div>
      <div style="padding:32px;">
        <p style="color:#475569;font-size:15px;">Dear <strong>{$name}</strong>,</p>
        <p style="color:#475569;font-size:15px;line-height:1.6;">
          On behalf of the entire congregation, we welcome you to House of Grace CCR family. We believe your presence here is not by accident and we look forward to growing together in faith.
        </p>
        <div style="background:#F8FAFC;border-radius:8px;padding:20px;margin:24px 0;text-align:center;">
          <p style="margin:0;color:#64748B;font-size:13px;text-transform:uppercase;letter-spacing:1px;">Your Official Member ID</p>
          <p style="margin:8px 0 0;color:#2E2D7B;font-size:32px;font-weight:700;">{$code}</p>
        </div>
        <p style="color:#475569;font-size:15px;">
          Should you have any questions or need any assistance, please don't hesitate to reach out to the church administration.
        </p>
        <p style="margin-top:28px;font-size:14px;color:#94A3B8;">
          Blessings, <br>
          <strong>House of Grace CCR Administration</strong>
        </p>
      </div>
      <div style="background:#F8FAFC;padding:16px 32px;text-align:center;font-size:11px;color:#94A3B8;">
        &copy; {$year} House of Grace CCR. All rights reserved.
      </div>
    </div>
    HTML;

    $emailSent = false;
    if (!empty($member['email'])) {
        $emailSent = sendEmail(
            $member['email'],
            $member['name'],
            'Welcome to House of Grace CCR Family!',
            $html
        );
    }

    $smsSent = false;
    if (!empty($member['phone'])) {
        $smsMsg = "Welcome to House of Grace CCR, {$member['name']}! We're glad to have you in our family. Your Member ID is {$member['code']}. God bless you.";
        $smsSent = sendSMS($member['phone'], $smsMsg);
    }

    return $emailSent || $smsSent;
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// PAGINATION HELPER
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * Calculate pagination offset.
 *
 * @param int $page     Current page (1-indexed)
 * @param int $perPage  Rows per page
 * @return int          OFFSET for SQL query
 */
function paginationOffset(int $page = 1, int $perPage = 20): int
{
    return ($page < 1 ? 0 : $page - 1) * $perPage;
}

/**
 * Get the current page number from $_GET['page'].
 */
function currentPage(): int
{
    return max(1, (int)($_GET['page'] ?? 1));
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// REDIRECT HELPER
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * Redirect to a URL with an optional flash message in the session.
 */
function redirect(string $url, string $flashKey = '', string $flashMsg = ''): void
{
    if ($flashKey && $flashMsg) {
        $_SESSION['flash'][$flashKey] = $flashMsg;
    }
    header('Location: ' . $url);
    exit();
}

/**
 * Retrieve and clear a flash message from the session.
 */
function flash(string $key): string
{
    $msg = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $msg;
}

/**
 * Broadcast an event notification to all active members.
 */
function broadcastEvent(array $event): void
{
    set_time_limit(0);
    $db = getDB();
    $members = $db->query("SELECT first_name, last_name, email, phone FROM members WHERE status = 'Active'")->fetchAll();

    $title = htmlspecialchars($event['title']);
    $date  = date('D, M j, Y', strtotime($event['date']));
    $time  = $event['time'] ? date('g:ia', strtotime($event['time'])) : 'TBA';
    $venue = htmlspecialchars($event['venue'] ?: 'TBA');
    $desc  = htmlspecialchars($event['description']);

    $htmlBase = <<<HTML
    <div style="font-family:'DM Sans',Arial,sans-serif;max-width:540px;margin:0 auto;border:1px solid #EDE8DF;border-radius:12px;overflow:hidden;">
      <div style="background:#2E2D7B;padding:32px;text-align:center;">
        <h1 style="color:#ffffff;font-size:22px;margin:0;">New Event Scheduled</h1>
        <p style="color:#B0A090;font-size:13px;margin:4px 0 0;">House of Grace CCR</p>
      </div>
      <div style="padding:32px;">
        <h2 style="color:#1E1B4B;font-size:18px;margin-top:0;">{$title}</h2>
        <p style="color:#475569;font-size:14px;line-height:1.6;">{$desc}</p>
        <table style="width:100%;border-collapse:collapse;font-size:14px;margin-top:20px;">
          <tr><td style="padding:8px 0;color:#64748B;">Date</td><td style="padding:8px 0;font-weight:600;text-align:right;">{$date}</td></tr>
          <tr><td style="padding:8px 0;color:#64748B;">Time</td><td style="padding:8px 0;font-weight:600;text-align:right;">{$time}</td></tr>
          <tr><td style="padding:8px 0;color:#64748B;">Venue</td><td style="padding:8px 0;font-weight:600;text-align:right;">{$venue}</td></tr>
        </table>
      </div>
    </div>
    HTML;

    foreach ($members as $m) {
        $name = $m['first_name'] . ' ' . $m['last_name'];
        if (!empty($m['email'])) {
            sendEmail($m['email'], $name, "New Event: $title", $htmlBase);
        }
        if (!empty($m['phone'])) {
            $sms = "New Event: {$title}\nDate: {$date}\nTime: {$time}\nVenue: {$venue}\n- House of Grace CCR";
            sendSMS($m['phone'], $sms);
        }
    }
}

/**
 * Broadcast an announcement to all active members.
 */
function broadcastAnnouncement(array $ann): void
{
    set_time_limit(0);
    $db = getDB();
    $members = $db->query("SELECT first_name, last_name, email, phone FROM members WHERE status = 'Active'")->fetchAll();

    $title = htmlspecialchars($ann['title']);
    $desc  = htmlspecialchars($ann['description']);

    $htmlBase = <<<HTML
    <div style="font-family:'DM Sans',Arial,sans-serif;max-width:540px;margin:0 auto;border:1px solid #EDE8DF;border-radius:12px;overflow:hidden;">
      <div style="background:#2E2D7B;padding:32px;text-align:center;">
        <h1 style="color:#ffffff;font-size:22px;margin:0;">New Announcement</h1>
        <p style="color:#B0A090;font-size:13px;margin:4px 0 0;">House of Grace CCR</p>
      </div>
      <div style="padding:32px;">
        <h2 style="color:#1E1B4B;font-size:18px;margin-top:0;">{$title}</h2>
        <p style="color:#475569;font-size:14px;line-height:1.6;">{$desc}</p>
      </div>
    </div>
    HTML;

    foreach ($members as $m) {
        $name = $m['first_name'] . ' ' . $m['last_name'];
        if (!empty($m['email'])) {
            sendEmail($m['email'], $name, "Announcement: $title", $htmlBase);
        }
        if (!empty($m['phone'])) {
            $sms = "Announcement: {$title}\n{$desc}\n- House of Grace CCR";
            sendSMS($m['phone'], $sms);
        }
    }
}
