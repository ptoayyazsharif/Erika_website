<?php
/**
 * Lite CMS core helpers — no framework, no dependencies.
 * Used by index.php (front site), admin.php (dashboard) and setup.php (installer).
 */

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

function cfg(): array {
    static $c;
    return $c ??= require __DIR__ . '/config.php';
}

function db(): PDO {
    static $pdo;
    if ($pdo) return $pdo;
    $c = cfg();
    if (($c['driver'] ?? 'mysql') === 'sqlite') {
        $pdo = new PDO('sqlite:' . $c['sqlite_path']);
    } else {
        $pdo = new PDO(
            "mysql:host={$c['host']};dbname={$c['name']};charset=utf8mb4",
            $c['user'],
            $c['pass']
        );
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

/* ---------- field manifest (pages → sections → fields, with defaults) ---------- */

function manifest(): array {
    static $m;
    return $m ??= require __DIR__ . '/fields.php';
}

/** Flat map of key => field definition (type, label, default). */
function fields_flat(): array {
    static $flat;
    if ($flat === null) {
        $flat = [];
        foreach (manifest() as $page) {
            foreach ($page['sections'] as $sec) {
                foreach ($sec['fields'] as $f) $flat[$f['k']] = $f;
            }
        }
    }
    return $flat;
}

/* ---------- content ---------- */

/** All DB overrides, loaded once per request. */
function content_all(): array {
    static $all;
    if ($all === null) {
        $all = [];
        try {
            foreach (db()->query('SELECT k, v FROM content') as $r) $all[$r['k']] = $r['v'];
        } catch (Throwable $e) {
            /* not installed yet — fall back to defaults */
        }
    }
    return $all;
}

/** Value for a key: DB override if present, else the manifest default. */
function cms(string $k): string {
    $all = content_all();
    if (array_key_exists($k, $all)) return $all[$k];
    return fields_flat()[$k]['d'] ?? '';
}

function content_set(string $k, string $v): void {
    $driver = cfg()['driver'] ?? 'mysql';
    $sql = $driver === 'sqlite'
        ? 'INSERT OR REPLACE INTO content (k, v) VALUES (?, ?)'
        : 'INSERT INTO content (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)';
    db()->prepare($sql)->execute([$k, $v]);
}

/* ---------- output helpers used by the template ---------- */

function esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** Escaped plain-text field. */
function cms_e(string $k): string {
    return esc(cms($k));
}

/** Rich (WYSIWYG) field — sanitized HTML allowed through. */
function cms_rich(string $k): string {
    return strip_bad(cms($k));
}

/**
 * Quill wraps everything in <p> blocks, but our rich fields live inside
 * existing tags (headlines, paragraphs). Unwrap single blocks and join
 * multiple blocks with double line breaks.
 */
function unwrap_quill(string $html): string {
    $html = trim($html);
    if (preg_match('#^(?:<p[^>]*>.*?</p>\s*)+$#is', $html)) {
        preg_match_all('#<p[^>]*>(.*?)</p>#is', $html, $m);
        $parts = array_map('trim', $m[1]);
        $parts = array_values(array_filter($parts, fn($x) => $x !== '' && $x !== '<br>'));
        return implode('<br><br>', $parts);
    }
    return $html;
}

/** Remove script-ish content from stored HTML. */
function strip_bad(string $html): string {
    $html = preg_replace('#<\s*(script|iframe|object|embed|style)[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);
    $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/(href|src)\s*=\s*(["\']?)\s*javascript:[^"\'>\s]*\2/i', '$1=$2#$2', $html);
    return $html;
}

/** Parse a stored "posX posY zoom" adjustment into an inline style, or '' for defaults. */
function adjust_style(string $k): string {
    $raw = content_all()[$k . '__adj'] ?? '';
    if ($raw === '') return '';
    $p = preg_split('/\s+/', trim($raw));
    $x = isset($p[0]) ? (float) $p[0] : 50;
    $y = isset($p[1]) ? (float) $p[1] : 50;
    $z = isset($p[2]) ? (float) $p[2] : 100;
    $x = max(0, min(100, $x));
    $y = max(0, min(100, $y));
    $z = max(100, min(400, $z));
    if ($x == 50 && $y == 50 && $z == 100) return '';
    $scale = number_format($z / 100, 3);
    return sprintf('object-position:%s%% %s%%;transform:scale(%s);transform-origin:%s%% %s%%;', $x, $y, $scale, $x, $y);
}

/** Image/video slot: outputs media tag when an upload exists, empty string otherwise. */
function cms_img(string $k): string {
    $v = cms($k);
    if ($v === '') return '';
    $style = adjust_style($k);
    $styleAttr = $style !== '' ? ' style="' . esc($style) . '"' : '';
    $ext = strtolower(pathinfo($v, PATHINFO_EXTENSION));
    if (in_array($ext, ['mp4', 'webm'], true)) {
        return '<video src="' . esc($v) . '" autoplay muted loop playsinline' . $styleAttr . '></video>';
    }
    return '<img src="' . esc($v) . '" alt="' . esc(fields_flat()[$k]['label'] ?? '') . '"' . $styleAttr . '>';
}

/* ---------- app settings (SMTP etc.) — stored in the content table ---------- */

function setting(string $k, string $default = ''): string {
    $all = content_all();
    return array_key_exists($k, $all) ? $all[$k] : $default;
}

function set_setting(string $k, string $v): void {
    content_set($k, $v);
}

/* ---------- email via SMTP (PHPMailer, self-hosted) ---------- */

/** Send an email using the admin-configured SMTP settings. Returns [ok, error]. */
function send_mail(string $subject, string $htmlBody, string $textBody, string $replyTo = '', string $replyName = ''): array {
    $host = setting('smtp.host');
    $to   = setting('smtp.to');
    if ($host === '' || $to === '') {
        return [false, 'Email is not configured yet. Set it up under Email / Forms in the admin.'];
    }
    require_once __DIR__ . '/lib/phpmailer/Exception.php';
    require_once __DIR__ . '/lib/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/lib/phpmailer/SMTP.php';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = (int) (setting('smtp.port', '587') ?: 587);
        $secure = setting('smtp.secure', 'tls');
        if ($secure === 'ssl')      $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        elseif ($secure === 'tls')  $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        else                        { $mail->SMTPSecure = false; $mail->SMTPAutoTLS = false; }
        $user = setting('smtp.user');
        if ($user !== '') {
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = setting('smtp.pass');
        }
        $fromEmail = setting('smtp.from', $user ?: $to);
        $mail->setFrom($fromEmail, setting('smtp.from_name', 'ErikaKPage Website'));
        foreach (array_filter(array_map('trim', explode(',', $to))) as $rcpt) $mail->addAddress($rcpt);
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) $mail->addReplyTo($replyTo, $replyName);
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody;
        $mail->send();
        return [true, ''];
    } catch (\Throwable $e) {
        return [false, $mail->ErrorInfo ?: $e->getMessage()];
    }
}

/* ---------- auth & CSRF ---------- */

function installed(): bool {
    try {
        return (bool) db()->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
    } catch (Throwable $e) {
        return false;
    }
}

function is_admin(): bool {
    return !empty($_SESSION['admin_id']);
}

function csrf_token(): string {
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}

function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Bad request (invalid CSRF token). Go back and try again.');
    }
}

/* ---------- Lofty CRM (create a lead when a form is submitted) ---------- */

const LOFTY_ENDPOINT = 'https://api.lofty.com/v1.0/leads';

/** Per-form default Source + Tags (used until the admin overrides them). */
function lofty_form_defaults(): array {
    return [
        'sell'           => ['Website – Seller',        'Website,Seller'],
        'homevalue'      => ['Website – Home Value',    'Website,Seller,Home Value'],
        'buy'            => ['Website – Buyer',         'Website,Buyer'],
        'speaking'       => ['Website – Speaking',      'Website,Speaking'],
        'collaborations' => ['Website – Collaboration', 'Website,Collaboration'],
        'media'          => ['Website – Media',         'Website,Media'],
        'resources'      => ['Website – Checklist',     'Website,Lead Magnet'],
        'mentorship'     => ['Website – Mentorship',    'Website,Mentorship,Agent'],
        'investing'      => ['Website – Investing',     'Website,Investor'],
        'pm'             => ['Website – Property Mgmt', 'Website,Property Management'],
        'transportation' => ['Website – Transportation','Website,Transportation'],
        'contact'        => ['Website – Contact',       'Website,Contact'],
    ];
}

/** Stable per-form key shared by submit.php and the admin table. */
function form_key(string $page, string $formName): string {
    $s = strtolower($page . '-' . $formName);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function lofty_enabled(): bool {
    return setting('lofty.enabled') === '1' && trim(setting('lofty.key')) !== '';
}

/** Resolve the Source + Tags[] for a given form, honoring admin overrides. */
function lofty_mapping(string $page, string $formName): array {
    $fk = form_key($page, $formName);
    $defaults = lofty_form_defaults();
    [$dSource, $dTags] = $defaults[$page] ?? [setting('lofty.source_default', 'Website'), setting('lofty.tags_default', 'Website')];
    $source = setting('lofty.form.' . $fk . '.source', $dSource) ?: $dSource;
    $tagsRaw = setting('lofty.form.' . $fk . '.tags', $dTags);
    if ($tagsRaw === '') $tagsRaw = $dTags;
    $tags = array_values(array_filter(array_map('trim', explode(',', $tagsRaw))));
    $on = setting('lofty.form.' . $fk . '.on', '1') !== '0';
    return ['source' => $source, 'tags' => $tags, 'on' => $on];
}

/**
 * Create a lead in Lofty. Returns [ok, detail].
 * $lead: firstName, lastName, email, phone, source, tags[], note.
 * Never throws; short timeout so a slow API can't hang the thank-you page.
 */
/**
 * Low-level authenticated POST to Lofty. Returns [ok, httpCode, responseBody].
 * The Lofty API Reference documents `Authorization: Bearer <token>` on every
 * endpoint; we try Bearer first and fall back to the `token` scheme on a 401,
 * so it works whichever the account's key expects.
 */
function lofty_http(string $url, array $payload): array {
    $key = trim(setting('lofty.key'));
    $body = json_encode($payload);
    $schemes = ['Bearer ', 'token '];
    $last = [false, 0, ''];
    foreach ($schemes as $scheme) {
        $headers = ['Authorization: ' . $scheme . $key, 'Content-Type: application/json', 'Accept: application/json'];
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 6, CURLOPT_TIMEOUT => 10,
            ]);
            $resp = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $cErr = curl_error($ch);
            curl_close($ch);
            if ($resp === false) return [false, 0, 'Connection failed: ' . $cErr];
        } else {
            $ctx = stream_context_create(['http' => [
                'method' => 'POST', 'header' => implode("\r\n", $headers),
                'content' => $body, 'timeout' => 10, 'ignore_errors' => true,
            ]]);
            $resp = @file_get_contents($url, false, $ctx);
            $code = 0;
            if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $code = (int) $m[1];
            if ($resp === false) return [false, 0, 'Connection failed'];
        }
        if ($code >= 200 && $code < 300) return [true, $code, (string) $resp];
        $last = [false, $code, (string) $resp];
        if ($code !== 401) break; // only the auth scheme is worth retrying
    }
    return $last;
}

/** Pull a lead id out of Lofty's response envelope, wherever it lives. */
function lofty_extract_lead_id(string $resp): string {
    $j = json_decode($resp, true);
    if (!is_array($j)) return '';
    $cands = [$j['data']['id'] ?? null, $j['data']['leadId'] ?? null, $j['data'] ?? null, $j['id'] ?? null, $j['leadId'] ?? null];
    foreach ($cands as $c) {
        if (is_int($c) || (is_string($c) && $c !== '')) return (string) $c;
    }
    return '';
}

/**
 * Create a lead in Lofty. Returns [ok, detail]. Never throws; short timeout.
 * $lead keys: firstName, lastName, email, phone, source, tags[], leadTypes[], note.
 *
 * Field names/shape match Lofty's Create Lead schema (POST /v1.0/leads):
 * emails/phones are arrays; the note is attached inline via `content` + `isPin`
 * (the create-lead body accepts a note), so no second request is needed.
 */
function send_to_lofty(array $lead, ?string $endpoint = null): array {
    $key = trim(setting('lofty.key'));
    if ($key === '') return [false, 'No Lofty API key set.'];
    $leadsUrl = $endpoint ?: (setting('lofty.endpoint', '') ?: LOFTY_ENDPOINT);

    $email = trim((string) ($lead['email'] ?? ''));
    $phone = trim((string) ($lead['phone'] ?? ''));
    // firstName is required by Lofty — fall back so email-only forms aren't rejected.
    $first = trim((string) ($lead['firstName'] ?? ''));
    if ($first === '') $first = $email !== '' ? explode('@', $email)[0] : 'Website Lead';
    $note = (string) ($lead['note'] ?? '');
    if (strlen($note) > 2000) $note = substr($note, 0, 1997) . '...';

    $payload = array_filter([
        'firstName' => $first,
        'lastName'  => $lead['lastName'] ?? '',
        'emails'    => $email !== '' ? [$email] : [],
        'phones'    => $phone !== '' ? [$phone] : [],
        'source'    => $lead['source'] ?? 'Website',
        'tags'      => $lead['tags'] ?? [],
        'leadTypes' => $lead['leadTypes'] ?? [],
    ], fn($v) => $v !== '' && $v !== []);
    if ($note !== '') {                 // inline note (create-lead accepts content + isPin)
        $payload['content'] = $note;
        $payload['isPin'] = false;
    }

    [$ok, $code, $resp] = lofty_http($leadsUrl, $payload);
    if (!$ok) return [false, ($code ? 'HTTP ' . $code . ': ' : '') . substr($resp, 0, 240)];

    $detail = 'lead HTTP ' . $code;
    $leadId = lofty_extract_lead_id($resp);
    if ($leadId !== '') $detail .= ' · lead #' . $leadId;
    if ($note !== '')   $detail .= ' · note attached';
    return [true, $detail];
}

/** Record one CRM sync attempt (best-effort; ignores errors). */
function crm_log_add(string $form, string $email, bool $ok, string $detail): void {
    try {
        $driver = cfg()['driver'] ?? 'mysql';
        if ($driver === 'sqlite') {
            db()->exec('CREATE TABLE IF NOT EXISTS crm_log (id INTEGER PRIMARY KEY AUTOINCREMENT, created TEXT, form TEXT, email TEXT, ok INTEGER, detail TEXT)');
        } else {
            db()->exec('CREATE TABLE IF NOT EXISTS crm_log (id INT AUTO_INCREMENT PRIMARY KEY, created DATETIME, form VARCHAR(160), email VARCHAR(160), ok TINYINT, detail TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }
        db()->prepare('INSERT INTO crm_log (created, form, email, ok, detail) VALUES (?,?,?,?,?)')
            ->execute([date('Y-m-d H:i:s'), $form, $email, $ok ? 1 : 0, substr($detail, 0, 500)]);
    } catch (Throwable $e) { /* logging must never break a submission */ }
}

function crm_log_recent(int $limit = 25): array {
    try {
        $stmt = db()->query('SELECT * FROM crm_log ORDER BY id DESC LIMIT ' . (int) $limit);
        return $stmt->fetchAll();
    } catch (Throwable $e) { return []; }
}

/** Every website form as [page, name, key], read from index.php so it stays in sync. */
function all_forms(): array {
    static $forms;
    if ($forms !== null) return $forms;
    $forms = [];
    $seen = [];
    $html = @file_get_contents(__DIR__ . '/index.php') ?: '';
    if (preg_match_all('/name="_form" value="([^"]*)">\s*<input type="hidden" name="_page" value="([^"]*)"/', $html, $m, PREG_SET_ORDER)) {
        foreach ($m as $row) {
            $name = html_entity_decode($row[1], ENT_QUOTES);
            $page = $row[2];
            $fk = form_key($page, $name);
            if (isset($seen[$fk])) continue;
            $seen[$fk] = true;
            $forms[] = ['page' => $page, 'name' => $name, 'key' => $fk];
        }
    }
    return $forms;
}

/* ---------- uploads ---------- */

const MAX_UPLOAD_BYTES = 300 * 1024 * 1024; // 300 MB

function handle_upload(array $file): array {
    // returns [path, error]
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return ['', ''];
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        return ['', 'File is too large for the server limit. Ask the host to raise upload_max_filesize / post_max_size.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) return ['', 'Upload failed (code ' . $file['error'] . ').'];
    if ($file['size'] > MAX_UPLOAD_BYTES) return ['', 'File is larger than 300 MB.'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'webp' => 'image/webp', 'gif' => 'image/gif',
        'mp4' => 'video/mp4', 'webm' => 'video/webm',
    ];
    if (!isset($allowed[$ext])) return ['', 'Only JPG, PNG, WEBP, GIF, MP4 or WEBM files are allowed.'];

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if ($mime !== $allowed[$ext]) return ['', 'File content does not match its extension.'];

    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $dir = __DIR__ . '/uploads';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) return ['', 'Could not save the file.'];
    return ['uploads/' . $name, ''];
}
