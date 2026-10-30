<?php
/**
 * Landing pages (/page/<address>) and the leads every form on the site produces.
 *
 * A landing page is a lead capture page in the Stan Store mould: a picture, a
 * title, a description, a short form, and a file the visitor can download once
 * they have filled it in. The owner builds them in the admin (Landing pages),
 * with the same steps Stan uses: image, description, the questions to ask, the
 * file to deliver, then Save as draft or Publish.
 *
 * Every lead — from a landing page or from any other form on the site — is kept
 * in the `leads` table, emailed to the owner and, when Lofty is switched on,
 * created as a lead there. Until 20 Oct 2026 the site only emailed submissions,
 * so a missed or deleted email was a lost lead; the Leads screen is the record.
 *
 * Same storage arrangement as the blog and the products: landing-pages.php ships
 * the defaults, and whatever the admin saves (the `landing.pages` setting) wins.
 */

/* The rest of the site never needed the mbstring extension, so the live host may
   not have it. This module trims user text to a length without cutting a
   character in half; this stands in when the extension is missing. */
if (!function_exists('mb_substr')) {
    function mb_substr(string $s, int $start, ?int $length = null, ?string $encoding = null): string {
        preg_match_all('/./us', $s, $m);
        return implode('', array_slice($m[0], $start, $length));
    }
}

/** Question types the builder offers, and how each is labelled there. */
const LANDING_FIELD_TYPES = [
    'text'     => 'Short answer',
    'phone'    => 'Phone number',
    'textarea' => 'Long answer',
    'select'   => 'Dropdown',
    'checkbox' => 'Checkboxes',
];

/** Lofty's lead type ids (as submit.php uses them), offered per landing page. */
const LANDING_LEAD_TYPES = [0 => 'Not set', 2 => 'Buyer', 1 => 'Seller', 6 => 'Investor', 7 => 'Agent', 9 => 'Landlord'];

/** File types a landing page can deliver: extension => the MIME types that genuinely belong to it. */
const LANDING_FILE_TYPES = [
    'pdf'  => ['application/pdf'],
    'zip'  => ['application/zip', 'application/x-zip-compressed'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    'jpg'  => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'],
    'mp4'  => ['video/mp4'], 'mp3' => ['audio/mpeg', 'audio/mp3'],
];

/** How long a download link stays good after the form is sent. */
/** Page designs. '' is the standard page; 'va' is the long VA services page (29 Oct 2026). */
const LANDING_LAYOUTS = ['' => 'Standard (picture, text and form side by side)', 'va' => 'VA services (long page, three-step form)'];

const LANDING_LINK_DAYS = 30;

/** Submissions accepted from one address in an hour, before the form refuses more. */
const LANDING_RATE_PER_HOUR = 12;

/* ---------- the pages ---------- */

function landing_seed(): array {
    static $seed;
    if ($seed === null) {
        $file = __DIR__ . '/../landing-pages.php';
        $seed = is_file($file) ? (array) require $file : [];
    }
    return $seed;
}

/** A short random id for a question, so answers keep their meaning when questions are reordered. */
function landing_field_id(): string {
    return 'q' . bin2hex(random_bytes(4));
}

function landing_field_clean(array $f): ?array {
    $type = isset(LANDING_FIELD_TYPES[$f['type'] ?? '']) ? (string) $f['type'] : 'text';
    $label = trim(strip_tags((string) ($f['label'] ?? '')));
    if ($label === '') return null;
    $id = preg_match('/^[a-z][a-z0-9_]{1,30}$/', (string) ($f['id'] ?? '')) ? (string) $f['id'] : landing_field_id();
    $options = [];
    if ($type === 'select' || $type === 'checkbox') {
        foreach ((array) ($f['options'] ?? []) as $o) {
            $o = trim(strip_tags((string) $o));
            if ($o !== '' && !in_array($o, $options, true)) $options[] = $o;
        }
        if (!$options) return null;          // a dropdown with nothing to choose is not a question
    }
    return [
        'id'       => $id,
        'type'     => $type,
        'label'    => mb_substr($label, 0, 300),
        'options'  => $options,
        'required' => !empty($f['required']),
    ];
}

function landing_clean(array $p): ?array {
    $t = fn($v) => trim(html_entity_decode(strip_tags((string) $v), ENT_QUOTES, 'UTF-8'));

    $slug = blog_slugify((string) ($p['slug'] ?? ''));
    if ($slug === '') $slug = blog_slugify((string) ($p['title'] ?? ''));
    if ($slug === '') return null;

    $fields = [];
    $seen = [];
    foreach ((array) ($p['fields'] ?? []) as $f) {
        if (!is_array($f)) continue;
        $c = landing_field_clean($f);
        if (!$c) continue;
        while (isset($seen[$c['id']])) $c['id'] = landing_field_id();
        $seen[$c['id']] = true;
        $fields[] = $c;
    }

    $file = (string) ($p['file'] ?? '');
    if (!landing_private_path_ok($file)) $file = '';
    $url = trim((string) ($p['url'] ?? ''));
    if ($url !== '' && !preg_match('#^https?://#i', $url)) $url = '';

    $deliver = ($p['deliver'] ?? '') === 'url' ? 'url' : 'file';
    $type = (int) ($p['crm_type'] ?? 0);

    return [
        'slug'         => $slug,
        'title'        => $t($p['title'] ?? $slug),
        'desc'         => strip_bad((string) ($p['desc'] ?? '')),
        'image'        => gallery_safe_src((string) ($p['image'] ?? '')),
        'image_alt'    => $t($p['image_alt'] ?? ''),
        'bottom_title' => $t($p['bottom_title'] ?? ''),
        // The line under her name (eyebrow, emails, receipts). Axen Realty for real estate pages;
        // something else for an offer that isn't brokerage business (VA services, 29 Oct 2026).
        'brand'        => $t($p['brand'] ?? '') ?: 'Axen Realty',
        // 'va' = the long VA services page (lib/landing-va.php); '' = the standard two-column page.
        'layout'       => isset(LANDING_LAYOUTS[(string) ($p['layout'] ?? '')]) ? (string) ($p['layout'] ?? '') : '',
        // Its own domain (optional): the page is then served at that domain's "/", and nothing else of
        // the site is reachable there (Erika, 30 Oct 2026: the VA company stands apart from her real estate).
        'domain'       => landing_domain_clean((string) ($p['domain'] ?? '')),
        'cta'          => $t($p['cta'] ?? '') ?: 'Send it to me',
        'fields'       => $fields,
        'deliver'      => $deliver,
        'file'         => $file,
        'file_name'    => $t($p['file_name'] ?? '') ?: ($file !== '' ? basename($file) : ''),
        'url'          => $url,
        'thanks_title' => $t($p['thanks_title'] ?? '') ?: 'Thank you. Your download is ready.',
        'thanks_text'  => $t($p['thanks_text'] ?? ''),
        'consent'      => $t($p['consent'] ?? ''),
        'email_lead'   => !array_key_exists('email_lead', $p) || !empty($p['email_lead']),
        'crm_on'       => !array_key_exists('crm_on', $p) || !empty($p['crm_on']),
        'crm_source'   => $t($p['crm_source'] ?? '') ?: 'Website – Landing Page',
        'crm_tags'     => $t($p['crm_tags'] ?? '') ?: 'Website, Landing Page',
        'crm_type'     => isset(LANDING_LEAD_TYPES[$type]) ? $type : 0,
        'price_cents'  => max(0, (int) ($p['price_cents'] ?? 0)),
        'billing'      => isset(STRIPE_BILLING[$p['billing'] ?? '']) ? (string) $p['billing'] : 'once',
        'indexable'    => !empty($p['indexable']),
        'published'    => !empty($p['published']),
        'updated'      => preg_match('/^\d{4}-\d{2}-\d{2}/', (string) ($p['updated'] ?? '')) ? substr((string) $p['updated'], 0, 10) : '',
    ];
}

/** Has this page got something to hand over? A page is only ever live when it has. */
function landing_deliverable(array $p): bool {
    return $p['deliver'] === 'url' ? $p['url'] !== '' : ($p['file'] !== '' && is_file(landing_abs($p['file'])));
}

/** Is a paid page able to take money? A free page always is. */
function landing_payable(array $p): bool {
    return $p['price_cents'] === 0 || stripe_ready();
}

/** Can visitors see it: published, with something to hand over, and (if it has a price) Stripe set up. */
function landing_live(array $p): bool {
    return $p['published'] && landing_deliverable($p) && landing_payable($p);
}

function landing_removed(): array {
    $raw = setting('landing.removed', '');
    $list = $raw === '' ? [] : json_decode($raw, true);
    return is_array($list) ? array_map('strval', $list) : [];
}

/**
 * Every landing page, admin-saved ones first in their saved order.
 *
 * @param bool $drafts include unpublished pages and pages with nothing to deliver
 */
function landing_all(bool $drafts = false): array {
    $out = [];
    $raw = setting('landing.pages', '');
    if ($raw !== '') {
        $list = json_decode($raw, true);
        if (is_array($list)) {
            foreach ($list as $p) {
                if (!is_array($p)) continue;
                $c = landing_clean($p);
                if ($c) $out[$c['slug']] = $c;
            }
        }
    }
    $removed = landing_removed();
    foreach (landing_seed() as $p) {
        if (!is_array($p)) continue;
        $c = landing_clean($p);
        if (!$c || isset($out[$c['slug']]) || in_array($c['slug'], $removed, true)) continue;
        $out[$c['slug']] = $c;
    }
    // A published page with no file behind it would collect details for nothing.
    if (!$drafts) $out = array_filter($out, 'landing_live');
    return array_values($out);
}

function landing_one(string $slug, bool $drafts = false): ?array {
    foreach (landing_all($drafts) as $p) {
        if ($p['slug'] === $slug) return $p;
    }
    return null;
}

function landing_save_all(array $pages): void {
    set_setting('landing.pages', json_encode(array_values($pages), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function landing_removed_save(array $slugs): void {
    set_setting('landing.removed', json_encode(array_values(array_unique($slugs)), JSON_UNESCAPED_SLASHES));
}

function landing_path(string $slug, string $action = ''): string {
    // On a page's own domain its addresses are "/", "/thanks", "/download" and "/paid".
    if (landing_own_domain_slug() === $slug) return '/' . $action;
    return '/page/' . $slug . ($action !== '' ? '/' . $action : '');
}

/** A lead's page as a full address (a page on its own domain stores its full address). */
function lead_page_url(array $l): string {
    $u = (string) ($l['page_url'] ?? '');
    return str_starts_with($u, 'http') ? $u : abs_url($u);
}

/** "https://www.Example.com/x" -> "example.com"; anything that isn't a plain host name -> ''. */
function landing_domain_clean(string $d): string {
    $d = strtolower(trim($d));
    $d = preg_replace('#^[a-z]+://#', '', $d);
    $d = preg_replace('#[/?\#:].*$#', '', $d);
    $d = preg_replace('/^www\./', '', $d);
    return preg_match('/^(?=.{4,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $d) ? $d : '';
}

/** The main site's host, from the site-address setting (never from the request). */
function landing_main_host(): string {
    return landing_domain_clean(cms('global.site-url')) ?: 'erikakpage.com';
}

/** The slug of the page whose own domain this request came in on, or '' on the main site. */
function landing_own_domain_slug(): string {
    static $slug = null;
    if ($slug !== null) return $slug;
    $host = landing_domain_clean((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $slug = '';
    if ($host === '' || $host === landing_main_host()) return $slug;
    foreach (landing_all(true) as $p) {
        if ($p['domain'] !== '' && $p['domain'] === $host) { $slug = $p['slug']; break; }
    }
    return $slug;
}

/** Where a page's addresses live: its own domain when it has one, else the site. */
function landing_base(array $p): string {
    return $p['domain'] !== '' ? 'https://' . $p['domain'] : site_url();
}

/** A full address for a page (canonical, share tags, emailed links). */
function landing_url(array $p, string $action = ''): string {
    return $p['domain'] !== '' ? landing_base($p) . '/' . $action : abs_url('/page/' . $p['slug'] . ($action !== '' ? '/' . $action : ''));
}

/** The name a page signs with: the VA company signs as itself, the rest as Erika. */
function landing_sender(array $p): string {
    return $p['layout'] === 'va' ? $p['brand'] : 'Erika K. Page';
}

/** "/page/<slug>[/thanks|/download|/paid]" -> [slug, action], or null for any other path. */
function landing_route(string $path): ?array {
    $path = trim(parse_url($path, PHP_URL_PATH) ?? '', '/');
    if (!preg_match('#^page/([a-z0-9][a-z0-9\-]*)(?:/(thanks|download|paid))?$#', $path, $m)) return null;
    return [$m[1], $m[2] ?? ''];
}

/* ---------- the private files a page hands over ----------
 * Uploads elsewhere on the site are public by design (pictures, guides). A
 * landing page's file is the thing the form is for, so it lives in
 * uploads/private, which the web server refuses to serve (its own .htaccess,
 * and router.php for the local server); the only way out is landing.php, after
 * a valid signed link.
 */

function landing_private_dir(): string {
    $dir = __DIR__ . '/../uploads/private';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $ht = $dir . '/.htaccess';
    if (is_dir($dir) && !is_file($ht)) {
        @file_put_contents($ht, "# Files handed over by landing pages: never served directly.\nRequire all denied\n<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n");
    }
    if (is_dir($dir) && !is_file($dir . '/index.html')) @file_put_contents($dir . '/index.html', '');
    return $dir;
}

/** Only names this module wrote itself: uploads/private/<hex>.<ext>. Nothing can climb out. */
function landing_private_path_ok(string $p): bool {
    return (bool) preg_match('#^uploads/private/[a-f0-9]{16,64}\.[a-z0-9]{2,5}$#', $p);
}

function landing_abs(string $p): string {
    return __DIR__ . '/../' . $p;
}

/** Save an uploaded file privately. Returns [path, original name, error]. */
function landing_private_upload(array $file): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return ['', '', ''];
    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
        return ['', '', 'The file is too large for the server limit.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) return ['', '', 'Upload failed (code ' . $file['error'] . ').'];
    if ($file['size'] > MAX_UPLOAD_BYTES) return ['', '', 'The file is larger than 300 MB.'];

    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!isset(LANDING_FILE_TYPES[$ext])) {
        return ['', '', 'That kind of file can\'t be delivered. Use PDF, ZIP, Word, Excel, PowerPoint, JPG, PNG, MP4 or MP3.'];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, LANDING_FILE_TYPES[$ext], true)) return ['', '', 'The file\'s content does not match its extension.'];

    $dir = landing_private_dir();
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    $ok = is_uploaded_file($file['tmp_name']) ? move_uploaded_file($file['tmp_name'], "$dir/$name") : @rename($file['tmp_name'], "$dir/$name");
    if (!$ok) return ['', '', 'Could not save the file.'];
    $orig = trim(preg_replace('/[^\w .()\-]+/u', '', basename((string) $file['name']))) ?: $name;
    return ['uploads/private/' . $name, $orig, ''];
}

/** Bytes, as a person reads them. */
function landing_size(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024) . ' KB';
    return $bytes . ' bytes';
}

/* ---------- signed download links ----------
 * The link is signed rather than looked up, so a download still works if the
 * database write for the lead failed: the visitor did their part.
 */

function landing_secret(): string {
    $s = setting('landing.secret');
    if (strlen($s) < 32) {
        $s = bin2hex(random_bytes(32));
        set_setting('landing.secret', $s);
    }
    return $s;
}

function landing_b64(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }

function landing_token(string $slug, int $leadId): string {
    $body = landing_b64($slug . '|' . $leadId . '|' . (time() + LANDING_LINK_DAYS * 86400));
    return $body . '.' . substr(hash_hmac('sha256', $body, landing_secret()), 0, 40);
}

/** ['lead' => id, 'exp' => time] for a good link to this page, or null. */
function landing_token_check(string $token, string $slug): ?array {
    if (!preg_match('/^([A-Za-z0-9_-]+)\.([a-f0-9]{40})$/', $token, $m)) return null;
    if (!hash_equals(substr(hash_hmac('sha256', $m[1], landing_secret()), 0, 40), $m[2])) return null;
    $parts = explode('|', (string) base64_decode(strtr($m[1], '-_', '+/')));
    if (count($parts) !== 3 || $parts[0] !== $slug) return null;
    $exp = (int) $parts[2];
    if ($exp < time()) return null;
    return ['lead' => (int) $parts[1], 'exp' => $exp];
}

/* ---------- leads ---------- */

function leads_table(): void {
    static $done = false;
    if ($done) return;
    if ((cfg()['driver'] ?? 'mysql') === 'sqlite') {
        db()->exec('CREATE TABLE IF NOT EXISTS leads (id INTEGER PRIMARY KEY AUTOINCREMENT, created TEXT, kind TEXT, source_key TEXT, source_label TEXT, page_url TEXT, name TEXT, email TEXT, phone TEXT, answers TEXT, meta TEXT, ip_hash TEXT, email_ok INTEGER, email_detail TEXT, crm_ok INTEGER, crm_detail TEXT, downloads INTEGER DEFAULT 0, last_download TEXT)');
        db()->exec('CREATE INDEX IF NOT EXISTS leads_created ON leads (created)');
        db()->exec('CREATE INDEX IF NOT EXISTS leads_source ON leads (source_key)');
    } else {
        db()->exec('CREATE TABLE IF NOT EXISTS leads (id INT AUTO_INCREMENT PRIMARY KEY, created DATETIME, kind VARCHAR(20), source_key VARCHAR(160), source_label VARCHAR(255), page_url VARCHAR(255), name VARCHAR(190), email VARCHAR(190), phone VARCHAR(60), answers MEDIUMTEXT, meta TEXT, ip_hash VARCHAR(64), email_ok TINYINT, email_detail TEXT, crm_ok TINYINT NULL, crm_detail TEXT, downloads INT NOT NULL DEFAULT 0, last_download DATETIME NULL, KEY leads_created (created), KEY leads_source (source_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
    $done = true;
}

/** A visitor's address, hashed with the site secret: enough to count, not to identify. */
function lead_ip_hash(): string {
    return hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . landing_secret());
}

/** Insert a lead. Returns its id, or 0 when the database refused (the lead still goes out by email). */
function lead_insert(array $l): int {
    try {
        leads_table();
        $cols = ['created', 'kind', 'source_key', 'source_label', 'page_url', 'name', 'email', 'phone', 'answers', 'meta', 'ip_hash', 'email_ok', 'email_detail', 'crm_ok', 'crm_detail'];
        $vals = [
            date('Y-m-d H:i:s'), (string) ($l['kind'] ?? 'form'), mb_substr((string) ($l['source_key'] ?? ''), 0, 160),
            mb_substr((string) ($l['source_label'] ?? ''), 0, 255), mb_substr((string) ($l['page_url'] ?? ''), 0, 255),
            mb_substr((string) ($l['name'] ?? ''), 0, 190), mb_substr((string) ($l['email'] ?? ''), 0, 190),
            mb_substr((string) ($l['phone'] ?? ''), 0, 60),
            json_encode(array_values($l['answers'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($l['meta'] ?? new stdClass, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            (string) ($l['ip_hash'] ?? ''),
            array_key_exists('email_ok', $l) && $l['email_ok'] !== null ? (int) (bool) $l['email_ok'] : null,
            mb_substr((string) ($l['email_detail'] ?? ''), 0, 500),
            array_key_exists('crm_ok', $l) && $l['crm_ok'] !== null ? (int) (bool) $l['crm_ok'] : null,
            mb_substr((string) ($l['crm_detail'] ?? ''), 0, 500),
        ];
        db()->prepare('INSERT INTO leads (' . implode(',', $cols) . ') VALUES (' . rtrim(str_repeat('?,', count($cols)), ',') . ')')->execute($vals);
        return (int) db()->lastInsertId();
    } catch (Throwable $e) {
        return 0;
    }
}

function lead_update(int $id, array $cols): void {
    if ($id <= 0 || !$cols) return;
    $allowed = ['email_ok', 'email_detail', 'crm_ok', 'crm_detail', 'meta'];
    $set = [];
    $vals = [];
    foreach ($cols as $k => $v) {
        if (!in_array($k, $allowed, true)) continue;
        $set[] = "$k = ?";
        $vals[] = is_bool($v) ? (int) $v : (is_string($v) && $k !== 'meta' ? mb_substr($v, 0, 500) : $v);
    }
    if (!$set) return;
    $vals[] = $id;
    try { db()->prepare('UPDATE leads SET ' . implode(', ', $set) . ' WHERE id = ?')->execute($vals); } catch (Throwable $e) {}
}

function lead_downloaded(int $id): void {
    if ($id <= 0) return;
    try {
        leads_table();
        db()->prepare('UPDATE leads SET downloads = downloads + 1, last_download = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $id]);
    } catch (Throwable $e) {}
}

function lead_get(int $id): ?array {
    try {
        leads_table();
        $st = db()->prepare('SELECT * FROM leads WHERE id = ?');
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ? lead_decode($r) : null;
    } catch (Throwable $e) { return null; }
}

function lead_decode(array $r): array {
    $r['answers'] = json_decode((string) ($r['answers'] ?? ''), true) ?: [];
    $r['meta'] = json_decode((string) ($r['meta'] ?? ''), true) ?: [];
    return $r;
}

function lead_delete(int $id): void {
    try { db()->prepare('DELETE FROM leads WHERE id = ?')->execute([$id]); } catch (Throwable $e) {}
}

/** WHERE clause and values for the Leads screen's filters. */
function leads_where(array $f): array {
    $w = [];
    $v = [];
    if (($f['source'] ?? '') !== '') { $w[] = 'source_key = ?'; $v[] = $f['source']; }
    if (($f['q'] ?? '') !== '') {
        $w[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR answers LIKE ?)';
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $f['q']) . '%';
        array_push($v, $like, $like, $like, $like);
    }
    if (!empty($f['days'])) { $w[] = 'created >= ?'; $v[] = date('Y-m-d H:i:s', time() - (int) $f['days'] * 86400); }
    return [$w ? ' WHERE ' . implode(' AND ', $w) : '', $v];
}

function leads_list(array $f = [], int $limit = 50, int $offset = 0): array {
    try {
        leads_table();
        [$where, $vals] = leads_where($f);
        $st = db()->prepare('SELECT * FROM leads' . $where . ' ORDER BY id DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset);
        $st->execute($vals);
        return array_map('lead_decode', $st->fetchAll());
    } catch (Throwable $e) { return []; }
}

function leads_count(array $f = []): int {
    try {
        leads_table();
        [$where, $vals] = leads_where($f);
        $st = db()->prepare('SELECT COUNT(*) AS c FROM leads' . $where);
        $st->execute($vals);
        return (int) $st->fetch()['c'];
    } catch (Throwable $e) { return 0; }
}

/** Every source that has sent a lead: key => [label, kind, count]. */
function leads_sources(): array {
    try {
        leads_table();
        $rows = db()->query('SELECT source_key, MAX(source_label) AS label, MAX(kind) AS kind, COUNT(*) AS c FROM leads GROUP BY source_key ORDER BY c DESC')->fetchAll();
        $out = [];
        foreach ($rows as $r) $out[$r['source_key']] = ['label' => $r['label'], 'kind' => $r['kind'], 'count' => (int) $r['c']];
        return $out;
    } catch (Throwable $e) { return []; }
}

function leads_recent_from(string $ipHash, int $secs): int {
    try {
        leads_table();
        $st = db()->prepare('SELECT COUNT(*) AS c FROM leads WHERE ip_hash = ? AND created >= ?');
        $st->execute([$ipHash, date('Y-m-d H:i:s', time() - $secs)]);
        return (int) $st->fetch()['c'];
    } catch (Throwable $e) { return 0; }
}

/** "Jane Q. Public" -> ["Jane", "Q. Public"]. */
function lead_split_name(string $name): array {
    $parts = preg_split('/\s+/', trim($name), 2);
    return [$parts[0] ?? '', $parts[1] ?? ''];
}

/**
 * Email the owner about a lead (to the Email / Forms recipient). Returns [ok, detail].
 * $rows: label => value, in the order to show them.
 */
function lead_notify_owner(string $headline, string $sourceLabel, string $pageUrl, array $rows, string $replyEmail, string $replyName, array $footer = [], string $subject = ''): array {
    if ($subject === '') $subject = 'New lead: ' . $sourceLabel . ($replyName !== '' ? ' — ' . $replyName : '');
    $text = [$headline, str_repeat('-', 40), 'From: ' . $sourceLabel, 'Page: ' . $pageUrl, ''];
    $html = '';
    foreach ($rows as $label => $val) {
        $text[] = $label . ': ' . $val;
        $html .= '<tr><td style="padding:7px 16px 7px 0;color:#8a746f;vertical-align:top;white-space:nowrap"><b>' . esc((string) $label)
               . '</b></td><td style="padding:7px 0">' . nl2br(esc((string) $val)) . '</td></tr>';
    }
    foreach ($footer as $line) $text[] = $line;
    $text[] = '';
    $text[] = 'Received ' . date('M j, Y g:i a');
    $body = '<div style="font-family:system-ui,Arial,sans-serif;color:#453230;max-width:640px">'
        . '<h2 style="font-family:Georgia,serif;color:#9C4A3E;margin:0 0 4px">' . esc($headline) . '</h2>'
        . '<p style="margin:0 0 16px;font-size:13px;color:#8a746f">From <a href="' . esc($pageUrl) . '" style="color:#9C4A3E">' . esc($sourceLabel) . '</a> · ' . esc(date('M j, Y g:i a')) . '</p>'
        . '<table style="border-collapse:collapse;font-size:15px">' . $html . '</table>'
        . ($footer ? '<p style="margin-top:16px;font-size:13px;color:#8a746f">' . implode('<br>', array_map('esc', $footer)) . '</p>' : '')
        . ($replyEmail !== '' ? '<p style="margin-top:16px;font-size:13px">Reply to this email to reach ' . esc($replyName ?: $replyEmail) . '.</p>' : '')
        . '<p style="margin-top:20px;font-size:12px;color:#8a746f">Every lead is also listed in the admin, under Leads.</p>'
        . '</div>';
    [$ok, $err] = send_mail($subject, $body, implode("\n", $text), $replyEmail, $replyName);
    return [$ok, $ok ? 'emailed to ' . setting('smtp.to') : $err];
}

/**
 * Create the lead in Lofty, when Lofty is on. Returns [ok|null, detail]; null = not sent.
 * $crm: on, source, tags[], types[].
 */
function lead_push_lofty(array $lead, array $crm, string $formLabel, string $pageUrl, array $rows): array {
    if (!lofty_enabled()) return [null, 'Lofty is off (CRM / Lofty in the admin).'];
    if (empty($crm['on'])) return [null, 'This form is set to email only.'];
    [$first, $last] = lead_split_name((string) ($lead['name'] ?? ''));
    $note = ['Website: ' . $formLabel, 'Page: ' . $pageUrl];
    foreach ($rows as $label => $val) $note[] = $label . ': ' . $val;
    $note[] = 'Submitted ' . date('M j, Y g:i a');
    [$ok, $detail] = send_to_lofty([
        'firstName' => $first, 'lastName' => $last,
        'email' => (string) ($lead['email'] ?? ''), 'phone' => (string) ($lead['phone'] ?? ''),
        'source' => (string) ($crm['source'] ?? 'Website'), 'tags' => (array) ($crm['tags'] ?? []),
        'leadTypes' => array_values(array_filter(array_map('intval', (array) ($crm['types'] ?? [])))),
        'note' => implode("\n", $note),
    ]);
    crm_log_add($formLabel, (string) ($lead['email'] ?? ''), $ok, $detail);
    return [$ok, $detail];
}

/** A landing page's Lofty settings, in the shape lead_push_lofty() takes. */
function landing_crm(array $p): array {
    return [
        'on'     => $p['crm_on'],
        'source' => $p['crm_source'],
        'tags'   => array_values(array_filter(array_map('trim', explode(',', $p['crm_tags'])))),
        'types'  => $p['crm_type'] ? [$p['crm_type']] : [],
    ];
}

/** Email the visitor their download link. Returns [ok, detail]. */
function landing_email_visitor(array $p, string $email, string $name, string $link): array {
    [$first] = lead_split_name($name);
    $hello = $first !== '' ? 'Hi ' . $first . ',' : 'Hello,';
    $what = $p['deliver'] === 'url' ? 'Here is your link' : 'Here is your download';
    $va = $p['layout'] === 'va';
    $sign = $va ? $p['brand'] . "\nFounded by Erika K. Page" : "Erika K. Page\n" . $p['brand'];
    $text = "$hello\n\n$what for \"{$p['title']}\":\n$link\n\nThe link works for " . LANDING_LINK_DAYS . " days.\n\n" . $sign;
    $html = '<div style="font-family:system-ui,Arial,sans-serif;color:#453230;max-width:560px;line-height:1.6">'
        . ($va ? '<p style="font-family:Georgia,serif;font-size:22px;color:#2A1B1A;margin:0 0 6px">' . esc($p['brand']) . '</p>'
               : '<p style="font-family:Georgia,serif;font-size:22px;color:#453230;margin:0 0 6px">Erika<span style="color:#B05E51">K</span>Page</p>')
        . '<p>' . esc($hello) . '</p><p>' . esc($what) . ' for <b>' . esc($p['title']) . '</b>.</p>'
        . '<p style="margin:26px 0"><a href="' . esc($link) . '" style="background:#B05E51;color:#fff;padding:14px 26px;text-decoration:none;font-weight:600;letter-spacing:.06em;text-transform:uppercase;font-size:13px">'
        . esc($p['deliver'] === 'url' ? 'Open it' : 'Download') . '</a></p>'
        . '<p style="font-size:13px;color:#8a746f">The link works for ' . LANDING_LINK_DAYS . ' days. If the button doesn\'t work, copy this address into your browser:<br>'
        . '<span style="word-break:break-all">' . esc($link) . '</span></p>'
        . ($va ? '<p style="margin-top:24px">' . esc($p['brand']) . '<br><span style="color:#8a746f">Founded by Erika K. Page</span></p></div>'
               : '<p style="margin-top:24px">Erika K. Page<br><span style="color:#8a746f">' . esc($p['brand']) . '</span></p></div>');
    // Replies go to the owner, not to the website's sending address.
    [$ok, $err] = send_mail('Your ' . ($p['deliver'] === 'url' ? 'link' : 'download') . ': ' . $p['title'], $html, $text, landing_reply_to(), landing_sender($p), $email, landing_sender($p));
    return [$ok, $ok ? 'link emailed to ' . $email : $err];
}

/** Where a visitor's reply goes: the owner's first notification address. */
function landing_reply_to(): string {
    return setting('smtp.to') !== '' ? trim(explode(',', setting('smtp.to'))[0]) : '';
}

/** "October 23, 2026" in Metro Atlanta's time, from a stored server timestamp. */
function landing_local_date(string $ts): string {
    try {
        $d = new DateTime($ts !== '' ? $ts : 'now');
        $d->setTimezone(new DateTimeZone('America/New_York'));
        return $d->format('F j, Y');
    } catch (Throwable $e) { return date('F j, Y'); }
}

/**
 * The buyer's email after a paid page: thank you, the download, and a receipt
 * (order number, item, amount, date, card, Stripe's own receipt or invoice).
 * Replies go to the owner. A test-mode purchase says so at the top.
 */
function landing_email_receipt(array $p, array $lead, string $link): array {
    $pay = (array) ($lead['meta']['pay'] ?? []);
    $email = (string) $lead['email'];
    [$first] = lead_split_name((string) $lead['name']);
    $test = empty($pay['live']);
    $billing = (string) ($pay['billing'] ?? 'once');
    $amount = stripe_price_label((int) ($pay['amount'] ?? 0));
    $order = stripe_order_no((int) $lead['id']);
    $date = landing_local_date((string) ($pay['paid_at'] ?? ''));
    $isUrl = $p['deliver'] === 'url';
    $every = $billing === 'month' ? 'month' : ($billing === 'year' ? 'year' : '');
    $reply = landing_reply_to();

    $rows = [
        ['Order number', $order],
        ['Date', $date],
        ['Item', $p['title']],
        ['Billing', $every ? 'Subscription, renews every ' . $every : 'One-time payment'],
        ['Amount paid', $amount . ' USD'],
    ];
    if (!empty($pay['card'])) $rows[] = ['Paid with', (string) $pay['card']];
    if (!empty($pay['invoice_no'])) $rows[] = ['Invoice', (string) $pay['invoice_no']];
    if (!empty($pay['descriptor'])) $rows[] = ['On your statement', (string) $pay['descriptor']];
    $official = (string) ($pay['invoice_url'] ?? '') ?: (string) ($pay['receipt_url'] ?? '');
    $officialLabel = !empty($pay['invoice_url']) ? 'View or download your invoice' : 'View your official receipt';

    $t = [];
    if ($test) $t[] = "TEST PURCHASE: this was made in Stripe's test mode. No real money was charged.\n";
    $t[] = ($first !== '' ? "Hi $first," : 'Hello,');
    $t[] = '';
    $t[] = "Thank you for your purchase. Your payment went through, and " . ($isUrl ? 'your link is' : 'your download is') . ' ready:';
    $t[] = $link;
    $t[] = 'The link works for ' . LANDING_LINK_DAYS . ' days. If it runs out, reply to this email and we will send you a fresh one.';
    $t[] = '';
    $t[] = 'RECEIPT';
    foreach ($rows as [$k, $v]) $t[] = $k . ': ' . $v;
    if ($official !== '') $t[] = $officialLabel . ': ' . $official;
    if ($every) { $t[] = ''; $t[] = "Your subscription renews automatically every $every at $amount until you cancel. To cancel, reply to this email before your next renewal date."; }
    $t[] = '';
    $t[] = 'Questions about your order? Just reply to this email.';
    $t[] = '';
    $t[] = 'Erika K. Page';
    $t[] = $p['brand'];
    $t[] = 'https://erikakpage.com';

    $c = 'font-family:system-ui,-apple-system,Segoe UI,Arial,sans-serif;color:#453230';
    $tr = '';
    foreach ($rows as $i => [$k, $v]) {
        $line = $i ? 'border-top:1px solid #EFE5DD;' : '';
        $tr .= '<tr><td style="' . $line . 'padding:9px 12px 9px 0;color:#8a746f;font-size:13px;white-space:nowrap;vertical-align:top">' . esc($k) . '</td>'
             . '<td style="' . $line . 'padding:9px 0;font-size:14px;text-align:right;' . ($k === 'Amount paid' ? 'font-weight:700' : '') . '">' . esc($v) . '</td></tr>';
    }
    $html = '<div style="' . $c . ';max-width:560px;line-height:1.6;margin:0 auto">'
        . ($test ? '<div style="background:#F7EEDC;color:#7a5a24;font-size:13px;padding:10px 14px;margin:0 0 18px;border-left:3px solid #C9A063"><b>Test purchase.</b> This was made in Stripe\'s test mode. No real money was charged.</div>' : '')
        . '<p style="font-family:Georgia,serif;font-size:22px;color:#453230;margin:0 0 18px">Erika<span style="color:#B05E51">K</span>Page</p>'
        . '<p style="margin:0 0 6px">' . esc($first !== '' ? "Hi $first," : 'Hello,') . '</p>'
        . '<p style="margin:0 0 4px">Thank you for your purchase. Your payment went through, and ' . ($isUrl ? 'your link is' : 'your download is') . ' ready.</p>'
        . '<p style="margin:24px 0"><a href="' . esc($link) . '" style="background:#B05E51;color:#fff;padding:14px 26px;text-decoration:none;font-weight:600;letter-spacing:.06em;text-transform:uppercase;font-size:13px;display:inline-block">'
        . ($isUrl ? 'Open it' : 'Download') . '</a></p>'
        . '<p style="font-size:13px;color:#8a746f;margin:0 0 26px">The link works for ' . LANDING_LINK_DAYS . ' days. If it runs out, reply to this email and we\'ll send you a fresh one. If the button doesn\'t work, copy this address into your browser:<br><span style="word-break:break-all">' . esc($link) . '</span></p>'
        . '<div style="border:1px solid #E6D8CE;border-top:3px solid #C9A063;padding:16px 20px;background:#FCFAF8">'
        . '<p style="margin:0 0 6px;font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:#9C4A3E;font-weight:700">Receipt</p>'
        . '<table role="presentation" style="width:100%;border-collapse:collapse">' . $tr . '</table>'
        . ($official !== '' ? '<p style="margin:12px 0 0;font-size:13px"><a href="' . esc($official) . '" style="color:#9C4A3E">' . esc($officialLabel) . ' →</a></p>' : '')
        . '</div>'
        . ($every ? '<p style="font-size:13px;margin:18px 0 0">Your subscription renews automatically every ' . $every . ' at ' . esc($amount) . ' until you cancel. To cancel, reply to this email before your next renewal date.</p>' : '')
        . '<p style="margin:22px 0 0">Questions about your order? Just reply to this email.</p>'
        . '<p style="margin-top:22px">Erika K. Page<br><span style="color:#8a746f">' . esc($p['brand']) . ' · <a href="https://erikakpage.com" style="color:#8a746f">erikakpage.com</a></span></p></div>';

    $subject = ($test ? '[TEST] ' : '') . 'Your receipt and ' . ($isUrl ? 'link' : 'download') . ': ' . $p['title'];
    [$ok, $err] = send_mail($subject, $html, implode("\n", $t), $reply, 'Erika K. Page', $email, 'Erika K. Page');
    return [$ok, $ok ? 'receipt and link emailed to ' . $email : $err];
}

/* ---------- a submission from a landing page ---------- */

/**
 * Check a submission against the page's questions.
 * Returns [values, errors]: values keyed by field id ('name', 'email' and each
 * question's id), errors keyed the same way.
 */
function landing_validate(array $p, array $post): array {
    $vals = [];
    $errs = [];
    $s = fn($v) => trim(preg_replace('/\s+/u', ' ', (string) $v));

    $vals['name'] = mb_substr($s($post['name'] ?? ''), 0, 120);
    if ($vals['name'] === '') $errs['name'] = 'Please enter your name.';
    $vals['email'] = mb_substr($s($post['email'] ?? ''), 0, 190);
    if (!filter_var($vals['email'], FILTER_VALIDATE_EMAIL)) $errs['email'] = 'Please enter a valid email address.';

    $answers = (array) ($post['a'] ?? []);
    foreach ($p['fields'] as $f) {
        $raw = $answers[$f['id']] ?? ($f['type'] === 'checkbox' ? [] : '');
        if ($f['type'] === 'checkbox') {
            $picked = array_values(array_filter((array) $raw, fn($o) => in_array((string) $o, $f['options'], true)));
            $vals[$f['id']] = $picked;
            if ($f['required'] && !$picked) {
                $errs[$f['id']] = count($f['options']) === 1 ? 'Please tick the box to continue.' : 'Please choose at least one.';
            }
            continue;
        }
        $v = $f['type'] === 'textarea' ? trim(mb_substr((string) (is_array($raw) ? '' : $raw), 0, 4000)) : mb_substr($s(is_array($raw) ? '' : $raw), 0, 500);
        if ($f['type'] === 'select' && $v !== '' && !in_array($v, $f['options'], true)) $v = '';
        $vals[$f['id']] = $v;
        if ($v === '') {
            if ($f['required']) $errs[$f['id']] = $f['type'] === 'select' ? 'Please choose one.' : 'Please fill this in.';
            continue;
        }
        if ($f['type'] === 'phone' && strlen(preg_replace('/\D/', '', $v)) < 7) $errs[$f['id']] = 'Please enter a phone number we can reach you on.';
    }
    return [$vals, $errs];
}

/** The questions and answers as label => text, in page order (name and email first). */
function landing_rows(array $p, array $vals): array {
    $rows = ['Name' => $vals['name'], 'Email' => $vals['email']];
    foreach ($p['fields'] as $f) {
        $v = $vals[$f['id']] ?? '';
        if (is_array($v)) $v = $v ? implode("\n", $v) : '';
        if ($f['type'] === 'checkbox' && count($f['options']) === 1 && $v !== '') $v = 'Agreed: ' . $v;
        if ($v === '') continue;
        $label = $f['label'];
        while (isset($rows[$label])) $label .= ' ';   // two questions with one wording keep both answers
        $rows[$label] = $v;
    }
    return $rows;
}

/** The first phone-type answer, or an answer to a question that asks for a phone. */
function landing_phone(array $p, array $vals): string {
    foreach ($p['fields'] as $f) {
        if ($f['type'] === 'phone' && ($vals[$f['id']] ?? '') !== '') return (string) $vals[$f['id']];
    }
    foreach ($p['fields'] as $f) {
        if ($f['type'] === 'text' && preg_match('/phone|mobile|cell/i', $f['label']) && ($vals[$f['id']] ?? '') !== '') return (string) $vals[$f['id']];
    }
    return '';
}

/**
 * Keep the lead; on a free page, also tell the owner, tell Lofty and send the
 * visitor their link. On a paid page ($pay set) the lead is only kept, marked
 * "payment pending": the rest happens once Stripe says it's paid (stripe_complete).
 * Returns ['id' => lead id, 'token' => download token ('' while unpaid), 'emailed' => bool].
 */
function landing_capture(array $p, array $vals, array $pay = []): array {
    $rows = landing_rows($p, $vals);
    $answers = [];
    foreach ($rows as $k => $v) $answers[] = [rtrim((string) $k), $v];
    $meta = ['crm' => landing_crm($p), 'form' => 'Landing page: ' . $p['title']];
    if ($pay) $meta['pay'] = $pay;
    $lead = [
        'kind' => 'landing', 'source_key' => 'page:' . $p['slug'], 'source_label' => $p['title'],
        'page_url' => $p['domain'] !== '' ? landing_url($p) : '/page/' . $p['slug'], 'name' => $vals['name'], 'email' => $vals['email'], 'phone' => landing_phone($p, $vals),
        'answers' => $answers, 'meta' => $meta, 'ip_hash' => lead_ip_hash(),
    ];
    $id = lead_insert($lead);
    if ($pay) return ['id' => $id, 'token' => '', 'emailed' => false];
    $token = landing_token($p['slug'], $id);
    $res = landing_notify($p, ['id' => $id] + $lead, $token, false);
    return ['id' => $id, 'token' => $token, 'emailed' => $res['emailed']];
}

/**
 * Send what a lead sets off: the visitor's link (always for a sale), Lofty, and
 * the owner's email; then record how each went. $sale is "Paid $99" for a sale.
 */
function landing_notify(array $p, array $lead, string $token, bool $isSale, string $sale = ''): array {
    $rows = [];
    foreach ((array) $lead['answers'] as $a) if (is_array($a) && count($a) === 2) $rows[$a[0]] = $a[1];
    if ($sale !== '') $rows['Payment'] = $sale;
    $url = landing_url($p);
    $label = 'Landing page: ' . $p['title'];
    $name = (string) $lead['name'];
    $email = (string) $lead['email'];
    $link = landing_url($p, 'download') . '?t=' . $token;

    // The visitor's copy first: it's what they asked for (and, for a sale, paid for).
    $emailed = false;
    $visitorNote = 'Download link: not emailed to the visitor (switched off for this page).';
    if ($isSale) {
        [$emailed, $vd] = landing_email_receipt($p, $lead, $link);
        $visitorNote = $emailed ? 'Receipt and download link emailed to the buyer.' : 'Receipt and link could not be emailed to the buyer: ' . $vd;
    } elseif ($p['email_lead']) {
        [$emailed, $vd] = landing_email_visitor($p, $email, $name, $link);
        $visitorNote = $emailed ? 'Download link emailed to the visitor.' : 'Download link could not be emailed to the visitor: ' . $vd;
    }

    $crm = landing_crm($p);
    [$crmOk, $crmDetail] = lead_push_lofty(['name' => $name, 'email' => $email, 'phone' => (string) $lead['phone']], $crm, $label, $url, $rows);
    $crmNote = $crmOk === null ? 'Lofty: not sent (' . rtrim($crmDetail, '.') . ').' : ($crmOk ? 'Lofty: lead created (' . $crmDetail . ').' : 'Lofty: FAILED (' . $crmDetail . '). Retry it from Leads in the admin.');

    $subject = '';
    if ($isSale) {
        $pay = (array) ($lead['meta']['pay'] ?? []);
        $rows = ['Order' => stripe_order_no((int) $lead['id'])] + $rows;
        $subject = (empty($pay['live']) ? '[TEST] ' : '') . 'New sale: ' . $p['title'] . ' (' . stripe_price_label((int) ($pay['amount'] ?? 0), (string) ($pay['billing'] ?? 'once')) . ')' . ($name !== '' ? ' — ' . $name : '');
    }
    [$mailOk, $mailDetail] = lead_notify_owner(($isSale ? 'New sale: ' : 'New lead from ') . $p['title'], $p['title'], $url, $rows, $email, $name, [$visitorNote, $crmNote], $subject);

    lead_update((int) $lead['id'], ['email_ok' => $mailOk, 'email_detail' => $mailDetail . ' · ' . $visitorNote, 'crm_ok' => $crmOk, 'crm_detail' => $crmDetail]);
    return ['emailed' => $emailed];
}

/* ---------- a submission from any other form on the site (submit.php) ---------- */

/** Record a lead that submit.php has already emailed and sent to Lofty. */
function lead_record_form(string $pageId, string $formName, string $pagePath, array $rows, array $raw, string $email, string $name, bool $mailOk, string $mailErr, ?bool $crmOk, string $crmDetail): int {
    $phone = '';
    foreach ($raw as $k => $v) {
        if ($phone === '' && preg_match('/phone|mobile|cell/i', (string) $k)) $phone = (string) $v;
    }
    if ($name === '') {
        $name = trim(($raw['first_name'] ?? '') . ' ' . ($raw['last_name'] ?? ''));
    }
    $answers = [];
    foreach ($rows as $k => $v) $answers[] = [$k, $v];
    return lead_insert([
        'kind' => 'form', 'source_key' => 'form:' . form_key($pageId, $formName),
        'source_label' => $formName . ' (' . ($pageId ?: 'home') . ')', 'page_url' => $pagePath,
        'name' => $name, 'email' => $email, 'phone' => $phone, 'answers' => $answers,
        'meta' => ['page' => $pageId, 'form' => $formName, 'crm' => lofty_mapping($pageId, $formName) + ['types' => []]],
        'ip_hash' => lead_ip_hash(),
        'email_ok' => $mailOk, 'email_detail' => $mailOk ? 'emailed to ' . setting('smtp.to') : $mailErr,
        'crm_ok' => $crmOk, 'crm_detail' => $crmDetail,
    ]);
}

/**
 * Email a landing-page lead their file again, with a fresh 30-day link: the receipt
 * for a sale, the plain download email otherwise. Returns [ok, detail].
 */
function lead_resend_visitor(int $id): array {
    $l = lead_get($id);
    if (!$l || $l['kind'] !== 'landing') return [false, 'Only landing-page leads have a file to send.'];
    $p = landing_one(substr((string) $l['source_key'], 5), true);
    if (!$p) return [false, 'Its landing page no longer exists.'];
    if (!landing_deliverable($p)) return [false, 'Its landing page has no file or link to hand over.'];
    $pay = (array) ($l['meta']['pay'] ?? []);
    if ($pay && ($pay['state'] ?? '') !== 'paid') return [false, 'This checkout was never paid.'];
    $link = landing_url($p, 'download') . '?t=' . landing_token($p['slug'], $id);
    return $pay ? landing_email_receipt($p, $l, $link) : landing_email_visitor($p, (string) $l['email'], (string) $l['name'], $link);
}

/** Send a stored lead to Lofty again (the Leads screen's Retry). Returns [ok|null, detail]. */
function lead_retry_lofty(int $id): array {
    $l = lead_get($id);
    if (!$l) return [false, 'No such lead.'];
    $crm = (array) ($l['meta']['crm'] ?? []);
    if ($l['kind'] === 'landing') {
        $p = landing_one(substr($l['source_key'], 5), true);
        if ($p) $crm = landing_crm($p);
    }
    $crm['on'] = true;   // asking to retry is asking to send
    if (in_array($l['meta']['pay']['state'] ?? '', ['abandoned', 'failed'], true)) return stripe_lofty_unpaid($id, true);
    $rows = [];
    foreach ($l['answers'] as $a) if (is_array($a) && count($a) === 2) $rows[$a[0]] = $a[1];
    [$ok, $detail] = lead_push_lofty($l, $crm, (string) ($l['meta']['form'] ?? $l['source_label']), lead_page_url($l), $rows);
    lead_update($id, ['crm_ok' => $ok, 'crm_detail' => $detail]);
    return [$ok, $detail];
}
