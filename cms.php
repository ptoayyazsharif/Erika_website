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

/* ---------- curated photo library (photos.php) ---------- */

function photo_library(): array {
    static $p;
    return $p ??= require __DIR__ . '/photos.php';
}

/**
 * The photo set offered for an image field, or [] when the slot has no curated set.
 * Returns ['id' => '03', 'title' => 'Keynote & stage', 'photos' => [...]].
 */
function photo_set(string $k): array {
    $lib = photo_library();
    $slot = $lib['slots'][$k] ?? null;
    if (!$slot) return [];
    $set = $lib['libraries'][$slot['lib']] ?? null;
    if (!$set) return [];
    return ['id' => $slot['lib'], 'title' => $set['title'], 'photos' => $set['photos']];
}

/**
 * Focal point of a curated photo as "X Y" percentages, or '' when it has none.
 * This is where the subject's face is, so cropped slots keep the head in frame.
 */
function photo_focal(string $path): string {
    static $map;
    if ($map === null) {
        $map = [];
        foreach (photo_library()['libraries'] as $set) {
            foreach ($set['photos'] as $p) {
                if (($p['pos'] ?? '') !== '') $map[$p['f']] = $p['pos'];
            }
        }
    }
    return $map[$path] ?? '';
}

/**
 * The curated pick for a slot: the picture shown when nothing has been chosen.
 *
 * This is what lets photos.php be the one place a slot's default picture is
 * decided — fields.php no longer has to repeat the path.
 */
function photo_pick(string $k): string {
    $lib = photo_library();
    $slot = $lib['slots'][$k] ?? null;
    if (!$slot) return '';
    foreach ($lib['libraries'][$slot['lib']]['photos'] ?? [] as $p) {
        if (($p['r'] ?? '') === ($slot['pick'] ?? '')) return $p['f'];
    }
    return '';
}

/** True when the path is one of the curated photos — the whitelist for saving a pick. */
function photo_is_library_path(string $path): bool {
    foreach (photo_library()['libraries'] as $set) {
        foreach ($set['photos'] as $p) if ($p['f'] === $path) return true;
    }
    return false;
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

/**
 * Value for a key: DB override if present, else the manifest default.
 *
 * A picture slot with no default in the manifest falls back to its curated pick
 * from photos.php, so a slot only renders empty when nothing anywhere has a
 * picture for it — not merely because fields.php was never filled in.
 */
function cms(string $k): string {
    $all = content_all();
    if (array_key_exists($k, $all)) return $all[$k];

    $field = fields_flat()[$k] ?? null;
    $d = $field['d'] ?? '';
    if ($d === '' && ($field['t'] ?? '') === 'image') return photo_pick($k);
    return $d;
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

/**
 * Parse a stored "posX posY zoom" adjustment into an inline style, or '' for defaults.
 *
 * With nothing saved for the slot, fall back to the photo's own focal point: slots
 * crop with object-fit:cover, so a portrait photo in a landscape slot would otherwise
 * be cropped through the middle and lose the head. A hand adjustment always wins.
 */
function adjust_style(string $k): string {
    $raw = content_all()[$k . '__adj'] ?? '';
    if ($raw === '') {
        $focal = photo_focal(cms($k));
        if ($focal === '') return '';
        $raw = $focal . ' 100';
    }
    $p = preg_split('/\s+/', trim($raw));
    $x = isset($p[0]) ? (float) $p[0] : 50;
    $y = isset($p[1]) ? (float) $p[1] : 50;
    $z = isset($p[2]) ? (float) $p[2] : 100;
    $x = max(0, min(100, $x));
    $y = max(0, min(100, $y));
    $z = max(100, min(400, $z));
    if ($x == 50 && $y == 50 && $z == 100) return '';
    if ($z <= 100) return sprintf('object-position:%s%% %s%%;', $x, $y);
    $scale = number_format($z / 100, 3);
    return sprintf('object-position:%s%% %s%%;transform:scale(%s);transform-origin:%s%% %s%%;', $x, $y, $scale, $x, $y);
}

/**
 * What each kind of picture box is actually worth downloading.
 *
 * A picture box is never the full width of the window — it is one column of a
 * three-up card grid, or one tile of a six-up strip. 'sizes' tells the browser
 * the real width so it can pick a small file instead of the biggest one.
 *
 * 'cap' is the widest file worth offering for that box: roughly twice the
 * largest size the box is ever laid out at. Without it a 3x phone screen pulls
 * a 1200px photo into a 350px card — sharper than the eye can resolve, and
 * three times the wait on a mobile connection.
 *
 * Keys match the slot's container class in index.php.
 */
const SLOT_SIZES = [
    'hero'   => ['(max-width:920px) 92vw, 40vw', 1200],  // landing headshot
    'media'  => ['(max-width:920px) 92vw, 44vw', 1200],  // two-column page hero
    'half'   => ['(max-width:920px) 92vw, 46vw', 1200],  // split / tall / wide / cutout
    'video'  => ['(max-width:920px) 92vw, 70vw', 1600],  // full-width video frame
    'card'   => ['(max-width:920px) 92vw, 30vw', 800],   // three-up resource & product cards
    'tile'   => ['(max-width:560px) 46vw, (max-width:920px) 31vw, 15vw', 800], // thumbnail strip
    'gal'    => ['(max-width:560px) 46vw, (max-width:920px) 46vw, 30vw', 800], // gallery masonry
];

/**
 * Pre-built WebP copies of a picture, as a srcset, plus its intrinsic size.
 *
 * tools/build-images.php writes assets/rimg/<width>/<path>.webp. When those
 * exist the browser is offered the whole ladder and downloads only the step it
 * needs; when they do not (a fresh upload, say) this returns nothing and the
 * original is served on its own, exactly as before.
 *
 * @return array{srcset:string, w:int, h:int}
 */
function img_variants(string $path, int $cap = 0): array {
    static $cache = [];
    $ck = $path . '|' . $cap;
    if (isset($cache[$ck])) return $cache[$ck];

    $out = ['srcset' => '', 'w' => 0, 'h' => 0];
    $abs = __DIR__ . '/' . ltrim($path, '/');
    if (is_file($abs) && ($size = @getimagesize($abs))) {
        $out['w'] = (int) $size[0];
        $out['h'] = (int) $size[1];
    }

    $rel = ltrim($path, '/');
    $set = [];
    foreach (glob(__DIR__ . '/assets/rimg/*/' . $rel . '.webp') as $file) {
        if (preg_match('#/assets/rimg/(\d+)/#', $file, $m)) {
            $set[(int) $m[1]] = 'assets/rimg/' . $m[1] . '/' . $rel . '.webp';
        }
    }
    if ($set) {
        ksort($set);
        if ($cap > 0) {
            // Keep every step up to the cap, plus the first one past it so the
            // ladder still has a rung when a picture is smaller than the cap.
            $kept = [];
            foreach ($set as $w => $f) {
                $kept[$w] = $f;
                if ($w >= $cap) break;
            }
            $set = $kept;
        }
        $out['srcset'] = implode(', ', array_map(fn($w, $f) => esc($f) . ' ' . $w . 'w', array_keys($set), $set));
    }
    return $cache[$ck] = $out;
}

/**
 * Image/video slot: outputs media tag when a picture exists, empty string otherwise.
 *
 * Every page lives in one document (hidden with display:none), so media is lazy by
 * default — the browser only fetches what the visitor actually navigates to. Pass
 * $eager for the one above-the-fold picture on the landing page.
 *
 * $slot names the kind of box the picture sits in (see SLOT_SIZES) so the browser
 * can pick a file scaled for that box rather than downloading the full-size photo.
 */
function cms_img(string $k, bool $eager = false, string $slot = 'half'): string {
    $v = cms($k);
    if ($v === '') return '';
    $style = adjust_style($k);
    $styleAttr = $style !== '' ? ' style="' . esc($style) . '"' : '';
    $ext = strtolower(pathinfo($v, PATHINFO_EXTENSION));
    if (in_array($ext, ['mp4', 'webm'], true)) {
        return '<video src="' . esc($v) . '" autoplay muted loop playsinline preload="metadata"' . $styleAttr . '></video>';
    }

    [$sizes, $cap] = SLOT_SIZES[$slot] ?? SLOT_SIZES['half'];
    $var = img_variants($v, $cap);
    $srcAttr = '';
    if ($var['srcset'] !== '') {
        $srcAttr = ' srcset="' . $var['srcset'] . '" sizes="' . esc($sizes) . '"';
    }
    // Intrinsic size keeps the picture's own proportions known to the browser, so a
    // slow-loading photo never shifts the text underneath it.
    $dimAttr = $var['w'] > 0 ? ' width="' . $var['w'] . '" height="' . $var['h'] . '"' : '';
    $loading = $eager ? ' fetchpriority="high" decoding="async"' : ' loading="lazy" decoding="async"';

    return '<img src="' . esc($v) . '"' . $srcAttr . $dimAttr
        . ' alt="' . esc(fields_flat()[$k]['label'] ?? '') . '"' . $loading . $styleAttr . '>';
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
    // A user-managed API Key authenticates as "Authorization: token <key>";
    // an OAuth access token uses "Bearer <token>". Try the API-key scheme first
    // (that's what the admin generates), then Bearer. A non-2xx never creates a
    // lead, so trying both schemes can't produce duplicates.
    $schemes = ['token ', 'Bearer '];
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
        // fall through to the next auth scheme on any non-2xx (safe: nothing was created)
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

    // Build the scaled-down copies straight away, so a picture uploaded here is
    // as light on a phone as the ones that ship with the site. A failure here is
    // not fatal: cms_img() serves the original when no copies exist.
    if (!in_array($ext, ['mp4', 'webm'], true)) {
        require_once __DIR__ . '/lib/imgvariants.php';
        img_build_variants(__DIR__, 'uploads/' . $name);
    }

    return ['uploads/' . $name, ''];
}
