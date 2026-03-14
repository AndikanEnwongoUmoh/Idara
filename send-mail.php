<?php
/**
 * =====================================================
 * Idara's Business Hub — Contact Form Mail Handler
 * =====================================================
 * Place this file in the same directory as your
 * idaras-business-hub.html (or .php) file on your
 * web server.
 *
 * Requirements: PHP 7.4+ with mail() enabled, OR
 * PHPMailer (recommended for SMTP — see bottom of file)
 * =====================================================
 */

// ✏️ EDIT HERE: Set the recipient email address
define('RECIPIENT_EMAIL', 'bigdane2k@gmail.com');
define('RECIPIENT_NAME',  "Emy — Idara's Business Hub");

// ✏️ EDIT HERE: "From" address shown in your inbox
// Many hosts require this to match a real domain mailbox
define('FROM_EMAIL', 'noreply@gmail.com');
define('FROM_NAME',  "Idara's Business Hub Website");

// ✏️ EDIT HERE: The page to redirect to after submission
// You can point this back to the main page with a hash,
// or create a dedicated thank-you page.
define('SUCCESS_URL', 'gmail.com');
define('ERROR_URL',   'idaras-business-hub.html?error=1#contact');

// =====================================================
// SECURITY: Only accept POST requests
// =====================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// =====================================================
// SECURITY: Basic rate-limiting via session
// (prevents rapid repeated submissions)
// =====================================================
session_start();
$now = time();

if (isset($_SESSION['last_submit']) && ($now - $_SESSION['last_submit']) < 60) {
    // Less than 60 seconds since last submit — redirect with error
    header('Location: ' . ERROR_URL . '&reason=rate');
    exit;
}

// =====================================================
// HONEYPOT: Invisible field to catch bots
// Add <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
// to your form. Bots fill it; humans won't.
// =====================================================
if (!empty($_POST['website'])) {
    // Bot detected — silently redirect to success (don't alert the bot)
    header('Location: ' . SUCCESS_URL);
    exit;
}

// =====================================================
// SANITIZE & VALIDATE INPUTS
// =====================================================

/**
 * Sanitizes a plain-text string field.
 * Strips tags, trims whitespace, removes control characters.
 */
function sanitize_text(string $value): string {
    $value = strip_tags($value);
    $value = trim($value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value); // strip control chars
    return $value;
}

/**
 * Sanitizes an email address.
 */
function sanitize_email(string $value): string {
    return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
}

// Collect and sanitize fields
$name    = sanitize_text($_POST['name']    ?? '');
$email   = sanitize_email($_POST['email']  ?? '');
$service = sanitize_text($_POST['service'] ?? 'Not specified');
$message = sanitize_text($_POST['message'] ?? '');

// Validation errors array
$errors = [];

// Name: required, 2–100 chars
if (strlen($name) < 2 || strlen($name) > 100) {
    $errors[] = 'Name must be between 2 and 100 characters.';
}

// Email: required, valid format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

// Message: required, 10–5000 chars
if (strlen($message) < 10 || strlen($message) > 5000) {
    $errors[] = 'Message must be between 10 and 5000 characters.';
}

// If any validation fails, redirect with error
if (!empty($errors)) {
    header('Location: ' . ERROR_URL . '&reason=validation');
    exit;
}

// =====================================================
// SECURITY: Prevent header injection
// Reject newlines in name/email (used in spam attacks)
// =====================================================
if (preg_match('/[\r\n]/', $name) || preg_match('/[\r\n]/', $email)) {
    http_response_code(400);
    exit('Invalid input.');
}

// =====================================================
// BUILD THE EMAIL
// =====================================================

$subject = "✦ New enquiry from {$name} — Idara's Business Hub";

// Plain-text body
$body_text = <<<TEXT
New contact form submission from Idara's Business Hub website.
======================================================================

Name:             {$name}
Email:            {$email}
Service Interest: {$service}

Message:
--------
{$message}

======================================================================
Submitted: {$_SERVER['HTTP_HOST']} — {$_SERVER['REMOTE_ADDR']}
Date/Time: {$_SERVER['REQUEST_TIME']}
======================================================================
TEXT;

// HTML body — styled to match the brand
$name_html    = htmlspecialchars($name,    ENT_QUOTES, 'UTF-8');
$email_html   = htmlspecialchars($email,   ENT_QUOTES, 'UTF-8');
$service_html = htmlspecialchars($service, ENT_QUOTES, 'UTF-8');
$message_html = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
$date_html    = date('F j, Y \a\t g:i A');

$body_html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>New Enquiry — Idara's Business Hub</title>
</head>
<body style="margin:0;padding:0;background:#FAF6F1;font-family:'DM Sans',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#FAF6F1;padding:40px 20px;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;border-radius:20px;overflow:hidden;box-shadow:0 8px 40px rgba(58,47,74,0.12);">

          <!-- Header -->
          <tr>
            <td style="background:#3A2F4A;padding:36px 40px;text-align:center;">
              <p style="margin:0 0 6px;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:rgba(212,175,114,0.7);">New Website Enquiry</p>
              <h1 style="margin:0;font-family:Georgia,serif;font-size:28px;font-weight:600;color:#D4AF72;letter-spacing:-0.01em;">Idara's Business Hub</h1>
              <p style="margin:8px 0 0;font-size:13px;font-style:italic;color:rgba(232,239,245,0.6);">Where ideas become radiant resources</p>
            </td>
          </tr>

          <!-- Gold divider -->
          <tr>
            <td style="height:3px;background:linear-gradient(90deg,#3A2F4A,#D4AF72,#F0D49A,#D4AF72,#3A2F4A);"></td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="background:#FFFFFF;padding:40px;">
              <p style="margin:0 0 24px;font-size:16px;color:#3A2F4A;">
                ✦ You have a new message from your website contact form.
              </p>

              <!-- Sender details -->
              <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid rgba(212,175,114,0.2);border-radius:12px;overflow:hidden;margin-bottom:28px;">
                <tr>
                  <td style="background:#E8EFF5;padding:10px 18px;font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:#6B5F7A;font-weight:600;">Sender Details</td>
                </tr>
                <tr>
                  <td style="padding:0 18px;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="padding:12px 0;border-bottom:1px solid rgba(58,47,74,0.06);font-size:13px;color:#6B5F7A;width:140px;">Full Name</td>
                        <td style="padding:12px 0;border-bottom:1px solid rgba(58,47,74,0.06);font-size:14px;font-weight:600;color:#3A2F4A;">{$name_html}</td>
                      </tr>
                      <tr>
                        <td style="padding:12px 0;border-bottom:1px solid rgba(58,47,74,0.06);font-size:13px;color:#6B5F7A;">Email Address</td>
                        <td style="padding:12px 0;border-bottom:1px solid rgba(58,47,74,0.06);font-size:14px;color:#3A2F4A;"><a href="mailto:{$email_html}" style="color:#D4AF72;text-decoration:none;">{$email_html}</a></td>
                      </tr>
                      <tr>
                        <td style="padding:12px 0;font-size:13px;color:#6B5F7A;">Service Interest</td>
                        <td style="padding:12px 0;font-size:14px;color:#3A2F4A;">{$service_html}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- Message -->
              <p style="margin:0 0 10px;font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:#6B5F7A;font-weight:600;">Message</p>
              <div style="background:#FAF6F1;border-left:3px solid #D4AF72;border-radius:0 12px 12px 0;padding:20px 22px;font-size:15px;color:#3A2F4A;line-height:1.75;">
                {$message_html}
              </div>

              <!-- Reply CTA -->
              <div style="text-align:center;margin-top:32px;">
                <a href="mailto:{$email_html}?subject=Re: Your enquiry to Idara's Business Hub"
                   style="display:inline-block;background:linear-gradient(135deg,#D4AF72,#F0D49A,#D4AF72);color:#3A2F4A;text-decoration:none;padding:13px 32px;border-radius:40px;font-size:14px;font-weight:600;letter-spacing:0.04em;">
                  ✦ Reply to {$name_html}
                </a>
              </div>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#3A2F4A;padding:24px 40px;text-align:center;">
              <p style="margin:0;font-size:12px;color:rgba(232,239,245,0.4);">
                This message was submitted via the contact form on your website on {$date_html}.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

// =====================================================
// SEND THE EMAIL (PHP mail())
// =====================================================

// Boundary for multipart/alternative (plain + HTML)
$boundary = '----=_Part_' . md5(uniqid('', true));

// Headers
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
$headers .= "From: " . mb_encode_mimeheader(FROM_NAME) . " <" . FROM_EMAIL . ">\r\n";
$headers .= "Reply-To: {$name} <{$email}>\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "X-Originating-IP: {$_SERVER['REMOTE_ADDR']}\r\n";

// Multipart body
$full_body  = "--{$boundary}\r\n";
$full_body .= "Content-Type: text/plain; charset=UTF-8\r\n";
$full_body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$full_body .= $body_text . "\r\n\r\n";
$full_body .= "--{$boundary}\r\n";
$full_body .= "Content-Type: text/html; charset=UTF-8\r\n";
$full_body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$full_body .= $body_html . "\r\n\r\n";
$full_body .= "--{$boundary}--";

// Encode the subject for non-ASCII characters
$encoded_subject = mb_encode_mimeheader($subject, 'UTF-8', 'B');

// Attempt to send
$sent = mail(
    RECIPIENT_EMAIL,
    $encoded_subject,
    $full_body,
    $headers
);

// =====================================================
// RECORD SUBMISSION TIME (rate limiting)
// =====================================================
if ($sent) {
    $_SESSION['last_submit'] = $now;
    header('Location: ' . SUCCESS_URL);
} else {
    // mail() returned false — log the error for debugging
    error_log("[Idara's Business Hub] mail() failed for submission from {$email} at " . date('Y-m-d H:i:s'));
    header('Location: ' . ERROR_URL . '&reason=server');
}

exit;


/*
 * =====================================================
 * OPTIONAL: USE PHPMAILER FOR SMTP (RECOMMENDED)
 * =====================================================
 * If your host doesn't support PHP's mail() or you
 * want reliable delivery via Gmail, SendGrid, etc.,
 * install PHPMailer via Composer:
 *
 *   composer require phpmailer/phpmailer
 *
 * Then replace the "SEND THE EMAIL" block above with:
 * =====================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);
try {
    // ✏️ EDIT HERE: Your SMTP credentials
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';      // or smtp.sendgrid.net, etc.
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your@gmail.com';       // ✏️ SMTP username
    $mail->Password   = 'your-app-password';    // ✏️ SMTP password / app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->CharSet = 'UTF-8';
    $mail->setFrom(FROM_EMAIL, FROM_NAME);
    $mail->addAddress(RECIPIENT_EMAIL, RECIPIENT_NAME);
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $body_html;
    $mail->AltBody = $body_text;

    $mail->send();
    $_SESSION['last_submit'] = $now;
    header('Location: ' . SUCCESS_URL);
} catch (Exception $e) {
    error_log("[Idara's Business Hub] PHPMailer error: " . $mail->ErrorInfo);
    header('Location: ' . ERROR_URL . '&reason=server');
}

 * =====================================================
 */