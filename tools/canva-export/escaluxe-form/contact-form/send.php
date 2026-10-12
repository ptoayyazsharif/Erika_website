<?php
/**
 * Escaluxe contact form handler: validates the Contact page form and emails it over SMTP
 * (PHPMailer). Settings live in config.php next to this file (copy config.sample.php).
 * Answers JSON: {"ok":true} or {"ok":false,"error":"…"}.
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function reply(bool $ok, string $error = '', int $status = 200): void {
    http_response_code($status);
    echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'error' => $error]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') reply(false, 'Method not allowed.', 405);

$cfgFile = getenv('ESCALUXE_FORM_CONFIG') ?: __DIR__ . '/config.php';
if (!is_file($cfgFile)) reply(false, 'The form is not set up yet. Please schedule a call below.', 500);
$cfg = require $cfgFile;

// --- spam guards: honeypot, too fast, and a per-IP limit ---
if (!empty($_POST['website'])) reply(true);                       // bots fill the hidden field: pretend success
$t = (int) ($_POST['_t'] ?? 0);
if ($t > 0 && (time() - $t) < 3) reply(true);                     // submitted impossibly fast
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$bucket = sys_get_temp_dir() . '/escaluxe-form-' . hash('sha256', $ip);
$hits = array_filter(is_file($bucket) ? (array) json_decode((string) file_get_contents($bucket), true) : [],
                     fn($ts) => is_int($ts) && $ts > time() - 600);
if (count($hits) >= (int) ($cfg['max_per_10_min'] ?? 5)) reply(false, 'Too many messages from this connection. Please try again later.', 429);

// --- fields (same as the Canva form) ---
$clean = fn(string $k, int $max) => mb_substr(trim(str_replace("\0", '', (string) ($_POST[$k] ?? ''))), 0, $max);
$f = [
    'Name'                              => $clean('name', 120),
    'Email address'                     => $clean('email', 160),
    'Property address'                  => $clean('property_address', 250),
    'Is the property currently rented?' => $clean('rented', 3),
    'Monthly Rent'                      => $clean('monthly_rent', 60),
    'Message'                           => $clean('message', 4000),
];
foreach (['Name', 'Email address', 'Property address', 'Is the property currently rented?', 'Monthly Rent'] as $req) {
    if ($f[$req] === '') reply(false, 'Please fill in all the fields marked *.', 422);
}
if (!filter_var($f['Email address'], FILTER_VALIDATE_EMAIL)) reply(false, 'Please enter a valid email address.', 422);
if (!in_array($f['Is the property currently rented?'], ['Yes', 'No'], true)) reply(false, 'Please choose Yes or No.', 422);
foreach (['Name', 'Email address'] as $oneLine) {                  // no header injection via name/email
    if (preg_match('/[\r\n]/', $f[$oneLine])) reply(false, 'Please check your details.', 422);
}

// --- the email ---
$e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$when = date('M j, Y g:i a');
$rows = '';
$text = "New contact form message from escaluxe.com\n" . str_repeat('-', 40) . "\n";
foreach ($f as $label => $val) {
    if ($val === '') continue;
    $rows .= '<tr><td style="padding:6px 14px 6px 0;color:#7a6a4f;vertical-align:top;white-space:nowrap"><b>' . $e($label)
           . '</b></td><td style="padding:6px 0">' . nl2br($e($val)) . '</td></tr>';
    $text .= "$label: $val\n";
}
$text .= "\nSent $when\n";
$html = '<div style="font-family:Arial,sans-serif;color:#2b2b2b">'
      . '<h2 style="margin:0 0 4px">New contact form message</h2>'
      . '<p style="margin:0 0 14px;font-size:13px;color:#7a6a4f">From the Contact page on escaluxe.com · ' . $e($when) . '</p>'
      . '<table style="border-collapse:collapse;font-size:15px">' . $rows . '</table>'
      . '<p style="margin-top:16px;font-size:13px">Reply to this email to answer ' . $e($f['Name']) . ' directly.</p></div>';

require __DIR__ . '/phpmailer/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';
$mail = new \PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host = (string) $cfg['smtp_host'];
    $mail->Port = (int) ($cfg['smtp_port'] ?? 587);
    $secure = $cfg['smtp_secure'] ?? 'tls';
    if ($secure === 'ssl')     $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    elseif ($secure === 'tls') $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    else                       { $mail->SMTPSecure = ''; $mail->SMTPAutoTLS = false; }
    if (!empty($cfg['smtp_user'])) {
        $mail->SMTPAuth = true;
        $mail->Username = (string) $cfg['smtp_user'];
        $mail->Password = (string) $cfg['smtp_pass'];
    }
    $mail->setFrom((string) ($cfg['from_email'] ?? $cfg['smtp_user']), (string) ($cfg['from_name'] ?? 'Escaluxe Website'));
    foreach ((array) $cfg['to'] as $to) $mail->addAddress((string) $to);
    $mail->addReplyTo($f['Email address'], $f['Name']);
    $mail->Subject = 'New contact form: ' . $f['Name'] . ' (' . $f['Property address'] . ')';
    $mail->isHTML(true);
    $mail->Body = $html;
    $mail->AltBody = $text;
    $mail->send();
} catch (\Throwable $ex) {
    error_log('escaluxe contact form: ' . $ex->getMessage());
    reply(false, 'Sorry, the message could not be sent. Please try again, or schedule a call below.', 502);
}

$hits[] = time();
@file_put_contents($bucket, json_encode(array_values($hits)), LOCK_EX);
reply(true);
