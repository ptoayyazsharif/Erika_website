<?php
/**
 * Public form handler. Every website form posts here; this emails the
 * submission to the address configured in admin (Email / Forms), then
 * redirects back to the originating page with a success/error flag.
 */
require __DIR__ . '/cms.php';
require __DIR__ . '/routes.php';

function back_to(string $pageId, string $flag): void {
    $path = path_for($pageId ?: 'home');
    header('Location: ' . $path . '?' . $flag);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { back_to('home', 'senterr=1'); }

$pageId = preg_replace('/[^a-z-]/', '', $_POST['_page'] ?? 'home');
$formName = trim($_POST['_form'] ?? 'Website form');

// --- spam guards ---
if (!empty($_POST['website'])) { back_to($pageId, 'sent=1'); }      // honeypot filled → pretend success, drop it
$t = (int) ($_POST['_t'] ?? 0);
if ($t > 0 && (time() - $t) < 2) { back_to($pageId, 'sent=1'); }    // submitted impossibly fast → bot

// --- collect the human fields (name="f_*") ---
$pretty = function (string $k): string {
    $k = preg_replace('/^f_/', '', $k);
    return ucwords(trim(str_replace('_', ' ', $k)));
};
$rows = [];
$raw = [];
$replyEmail = '';
$replyName = '';
foreach ($_POST as $k => $v) {
    if (strpos($k, 'f_') !== 0) continue;
    if (is_array($v)) $v = implode(', ', $v);
    $v = trim((string) $v);
    if ($v === '') continue;
    $short = preg_replace('/^f_/', '', $k);
    $raw[$short] = $v;
    $label = $pretty($k);
    $rows[$label] = $v;
    if ($replyEmail === '' && filter_var($v, FILTER_VALIDATE_EMAIL)) $replyEmail = $v;
    if ($replyName === '' && stripos($k, 'name') !== false) $replyName = $v;
}

if (!$rows) { back_to($pageId, 'senterr=1'); }

// --- build the email ---
$pageLabel = $pageId ?: 'home';
$subject = 'New ' . $formName . ' — ErikaKPage (' . $pageLabel . ')';

$textLines = ["New submission from the ErikaKPage website", str_repeat('-', 40),
              'Form: ' . $formName, 'Page: ' . $pageLabel, ''];
$htmlRows = '';
foreach ($rows as $label => $val) {
    $textLines[] = $label . ': ' . $val;
    $htmlRows .= '<tr><td style="padding:6px 14px 6px 0;color:#8a746f;vertical-align:top;white-space:nowrap"><b>'
              . esc($label) . '</b></td><td style="padding:6px 0">' . nl2br(esc($val)) . '</td></tr>';
}
$textLines[] = '';
$textLines[] = 'Sent ' . date('M j, Y g:i a');
$textBody = implode("\n", $textLines);

$htmlBody = '<div style="font-family:system-ui,Arial,sans-serif;color:#453230">'
    . '<h2 style="font-family:Georgia,serif;color:#9C4A3E;margin:0 0 4px">New ' . esc($formName) . '</h2>'
    . '<p style="margin:0 0 14px;font-size:13px;color:#8a746f">From the ' . esc($pageLabel) . ' page · ' . esc(date('M j, Y g:i a')) . '</p>'
    . '<table style="border-collapse:collapse;font-size:15px">' . $htmlRows . '</table>'
    . ($replyEmail ? '<p style="margin-top:16px;font-size:13px">Reply directly to this email to reach ' . esc($replyName ?: $replyEmail) . '.</p>' : '')
    . '</div>';

[$ok, $err] = send_mail($subject, $htmlBody, $textBody, $replyEmail, $replyName);

// --- also create a lead in Lofty CRM (secondary; never blocks the visitor) ---
if (lofty_enabled()) {
    $map = lofty_mapping($pageId, $formName);
    if ($map['on']) {
        // name: prefer explicit first/last, else split a single "name" field
        $first = $raw['first_name'] ?? '';
        $last  = $raw['last_name'] ?? '';
        if ($first === '' && $last === '') {
            $whole = $raw['your_name'] ?? $raw['name'] ?? $replyName;
            if ($whole !== '') {
                $parts = preg_split('/\s+/', trim($whole), 2);
                $first = $parts[0];
                $last  = $parts[1] ?? '';
            }
        }
        $phone = $raw['phone'] ?? '';

        // note = every submitted field + which form/page, so nothing is lost
        $noteLines = ['Website form: ' . $formName . ' (' . $pageLabel . ' page)'];
        foreach ($rows as $label => $val) $noteLines[] = $label . ': ' . $val;
        $noteLines[] = 'Submitted ' . date('M j, Y g:i a');

        [$lok, $ldetail] = send_to_lofty([
            'firstName' => $first,
            'lastName'  => $last,
            'email'     => $replyEmail,
            'phone'     => $phone,
            'source'    => $map['source'],
            'tags'      => $map['tags'],
            'note'      => implode("\n", $noteLines),
        ]);
        crm_log_add($formName . ' (' . $pageLabel . ')', $replyEmail, $lok, $ldetail);
    }
}

if ($ok) back_to($pageId, 'sent=1');
back_to($pageId, 'senterr=' . (stripos($err, 'not configured') !== false ? 'config' : '1'));
