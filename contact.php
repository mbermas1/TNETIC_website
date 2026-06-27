<?php
/**
 * TNETIC Contact Form Handler
 * Requires: PHPMailer (recommended) or PHP's built-in mail()
 *
 * SETUP INSTRUCTIONS:
 * 1. Upload this file to your server in the same directory as tnetic-website.html
 *    (or adjust the fetch URL in the HTML to match where you place it)
 * 2. If using PHPMailer, install via Composer:
 *    composer require phpmailer/phpmailer
 *    Then uncomment the PHPMailer section below and comment out the mail() section.
 * 3. If using shared hosting with PHP mail(), no extra setup needed.
 * 4. Update SMTP credentials in the PHPMailer section if you go that route.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Restrict to your domain in production: e.g. 'https://tnetic.com'
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// ── Collect & sanitize fields ──────────────────────────────────────────────
function clean($val) {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

$first_name = clean($_POST['first_name'] ?? '');
$last_name  = clean($_POST['last_name']  ?? '');
$email      = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone      = clean($_POST['phone']      ?? '');
$company    = clean($_POST['company']    ?? '');
$interest   = clean($_POST['interest']   ?? '');
$message    = clean($_POST['message']    ?? '');

// ── Validation ─────────────────────────────────────────────────────────────
$errors = [];

if (empty($first_name)) $errors[] = 'First name is required.';
if (empty($last_name))  $errors[] = 'Last name is required.';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
if (empty($phone))      $errors[] = 'Phone number is required.';
if (empty($company))    $errors[] = 'Company name is required.';
if (empty($interest))   $errors[] = 'Please select an area of interest.';
if (empty($message))    $errors[] = 'Please include a message.';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ── Recipient ──────────────────────────────────────────────────────────────
$to      = 'hello@tnetic.com';
$subject = 'New Contact Form Submission — TNETIC.com';

// ── Email body (plain text + HTML) ─────────────────────────────────────────
$plain = "New contact form submission from tnetic.com\n"
       . "================================================\n\n"
       . "Name:      {$first_name} {$last_name}\n"
       . "Email:     {$email}\n"
       . "Phone:     {$phone}\n"
       . "Company:   {$company}\n"
       . "Interest:  {$interest}\n\n"
       . "Message:\n{$message}\n\n"
       . "================================================\n"
       . "Sent from tnetic.com contact form\n";

$html = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body style='font-family:Arial,sans-serif;color:#1e2022;max-width:600px;margin:0 auto;padding:24px'>
  <div style='background:#1e2022;padding:20px 28px;border-radius:8px 8px 0 0'>
    <h1 style='color:#ffffff;font-size:20px;margin:0'>TNETIC<span style='color:#10b981'>.</span></h1>
    <p style='color:rgba(255,255,255,0.6);font-size:13px;margin:6px 0 0'>New contact form submission</p>
  </div>
  <div style='background:#f4f5f6;padding:28px;border-radius:0 0 8px 8px'>
    <table style='width:100%;border-collapse:collapse;font-size:15px'>
      <tr><td style='padding:10px 0;color:#7a8088;width:120px;vertical-align:top'>Name</td><td style='padding:10px 0;font-weight:600'>{$first_name} {$last_name}</td></tr>
      <tr><td style='padding:10px 0;color:#7a8088;border-top:1px solid #e8eaec;vertical-align:top'>Email</td><td style='padding:10px 0;border-top:1px solid #e8eaec'><a href='mailto:{$email}' style='color:#059669'>{$email}</a></td></tr>
      <tr><td style='padding:10px 0;color:#7a8088;border-top:1px solid #e8eaec;vertical-align:top'>Phone</td><td style='padding:10px 0;border-top:1px solid #e8eaec'>{$phone}</td></tr>
      <tr><td style='padding:10px 0;color:#7a8088;border-top:1px solid #e8eaec;vertical-align:top'>Company</td><td style='padding:10px 0;border-top:1px solid #e8eaec'>{$company}</td></tr>
      <tr><td style='padding:10px 0;color:#7a8088;border-top:1px solid #e8eaec;vertical-align:top'>Interest</td><td style='padding:10px 0;border-top:1px solid #e8eaec'><span style='background:#f0fdf6;color:#059669;padding:3px 10px;border-radius:20px;font-size:13px;font-weight:600'>{$interest}</span></td></tr>
      <tr><td style='padding:10px 0;color:#7a8088;border-top:1px solid #e8eaec;vertical-align:top'>Message</td><td style='padding:10px 0;border-top:1px solid #e8eaec;white-space:pre-line'>{$message}</td></tr>
    </table>
    <div style='margin-top:24px;padding-top:20px;border-top:1px solid #e8eaec;font-size:12px;color:#c2c6cb'>
      Sent from tnetic.com contact form &mdash; " . date('F j, Y \a\t g:i A T') . "
    </div>
  </div>
</body>
</html>";

// ══════════════════════════════════════════════════════════════════════════════
// OPTION A: PHP built-in mail() — works on most shared hosting out of the box
// ══════════════════════════════════════════════════════════════════════════════
$boundary = md5(time());
$headers  = "From: TNETIC Website <noreply@tnetic.com>\r\n"
          . "Reply-To: {$first_name} {$last_name} <{$email}>\r\n"
          . "MIME-Version: 1.0\r\n"
          . "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n"
          . "X-Mailer: PHP/" . phpversion();

$body = "--{$boundary}\r\n"
      . "Content-Type: text/plain; charset=UTF-8\r\n\r\n"
      . $plain . "\r\n"
      . "--{$boundary}\r\n"
      . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
      . $html . "\r\n"
      . "--{$boundary}--";

$sent = mail($to, $subject, $body, $headers);

// ══════════════════════════════════════════════════════════════════════════════
// OPTION B: PHPMailer (SMTP) — more reliable, avoids spam folders
// Uncomment this block and comment out the mail() block above once you have
// PHPMailer installed via Composer (composer require phpmailer/phpmailer).
// ══════════════════════════════════════════════════════════════════════════════
/*
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/vendor/autoload.php';

$mail = new PHPMailer(true);
try {
    // SMTP Configuration — replace with your mail server credentials
    $mail->isSMTP();
    $mail->Host       = 'smtp.yourprovider.com';  // e.g. smtp.gmail.com, smtp.sendgrid.net
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your-smtp-username';
    $mail->Password   = 'your-smtp-password';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('noreply@tnetic.com', 'TNETIC Website');
    $mail->addAddress($to, 'TNETIC Team');
    $mail->addReplyTo($email, "{$first_name} {$last_name}");

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $html;
    $mail->AltBody = $plain;

    $mail->send();
    $sent = true;
} catch (Exception $e) {
    $sent = false;
}
*/

// ── Response ───────────────────────────────────────────────────────────────
if ($sent) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => "Thanks {$first_name}, we'll be in touch within one business day."]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sorry, something went wrong sending your message. Please email us directly at hello@tnetic.com.']);
}
