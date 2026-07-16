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
