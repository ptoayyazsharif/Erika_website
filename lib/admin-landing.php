<?php
/**
 * Admin screens for landing pages and leads (included by admin.php once the
 * user is logged in).
 *
 *   admin.php?page=landing-pages               the list
 *   admin.php?page=landing-pages&edit=<slug>   the editor (edit=new for a new page)
 *   admin.php?page=leads                       every lead, filterable, exportable
 *   admin.php?page=leads&lead=<id>             one lead
 *
 * The editor follows Stan's steps — image, description, the questions, the file,
 * then the options — with a live phone preview drawn from the same stylesheet the
 * page itself uses (assets/css/landing.css).
 */

/** Flash messages survive the redirect after a save. */
function lpa_flash(string $msg, bool $isErr = false): void {
    $_SESSION['lpa_flash'] = [$msg, $isErr];
}

function lpa_take_flash(): array {
    $f = $_SESSION['lpa_flash'] ?? null;
    unset($_SESSION['lpa_flash']);
    return is_array($f) ? $f : ['', false];
}

/** Is another page still using this private file? (A duplicate shares its file.) */
function lpa_file_in_use(string $path, string $exceptSlug): bool {
    foreach (landing_all(true) as $p) {
        if ($p['slug'] !== $exceptSlug && $p['file'] === $path) return true;
    }
    return false;
}

function lpa_drop_file(string $path, string $exceptSlug): void {
    if ($path !== '' && landing_private_path_ok($path) && !lpa_file_in_use($path, $exceptSlug)) @unlink(landing_abs($path));
}

/** Stream a file to the browser as a download. */
function lpa_send_file(string $abs, string $name): void {
    while (ob_get_level()) ob_end_clean();
    $ascii = preg_replace('/[^A-Za-z0-9 ._()\-]/', '', $name) ?: 'download';
    header('Content-Type: ' . ((new finfo(FILEINFO_MIME_TYPE))->file($abs) ?: 'application/octet-stream'));
    header('Content-Length: ' . filesize($abs));
    header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($name));
    header('Cache-Control: private, no-store');
    readfile($abs);
    exit;
}

/** A spreadsheet cell that can't be read as a formula. */
function lpa_csv_cell(string $v): string {
    return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
}

/**
 * Everything that must happen before admin.php prints a byte: file downloads,
 * the CSV export, and the save buttons (which redirect when they succeed).
 */
function landing_admin_early(string $cur): void {
    /* ---------- the admin's own copy of a page's file ---------- */
    if ($cur === 'landing-pages' && isset($_GET['getfile'])) {
        $p = landing_one((string) $_GET['getfile'], true);
        if ($p && $p['file'] !== '' && is_file(landing_abs($p['file']))) lpa_send_file(landing_abs($p['file']), $p['file_name'] ?: basename($p['file']));
        http_response_code(404);
        exit('That file is not there.');
    }

    /* ---------- leads as a spreadsheet ---------- */
    if ($cur === 'leads' && ($_GET['export'] ?? '') === 'csv') {
        $f = lpa_filters();
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="erikakpage-leads-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");   // so Excel reads the accents and dashes
        fputcsv($out, ['Received', 'Source', 'Name', 'Email', 'Phone', 'Answers', 'Emailed to you', 'Lofty', 'Lofty detail', 'Downloads', 'Payment', 'Page']);
        for ($off = 0; ; $off += 500) {
            $rows = leads_list($f, 500, $off);
            foreach ($rows as $l) {
                $ans = [];
                foreach ($l['answers'] as $a) if (is_array($a) && count($a) === 2 && !in_array($a[0], ['Name', 'Email'], true)) $ans[] = $a[0] . ': ' . str_replace("\n", '; ', (string) $a[1]);
                fputcsv($out, array_map('lpa_csv_cell', [
                    (string) $l['created'], (string) $l['source_label'], (string) $l['name'], (string) $l['email'], (string) $l['phone'],
                    implode(' | ', $ans), $l['email_ok'] === null ? '' : ((int) $l['email_ok'] ? 'yes' : 'NO'),
                    $l['crm_ok'] === null ? 'not sent' : ((int) $l['crm_ok'] ? 'yes' : 'FAILED'), (string) $l['crm_detail'],
                    (string) (int) $l['downloads'], ($pc = stripe_lead_chip($l)) ? $pc[1] : '', abs_url((string) $l['page_url']),
                ]));
            }
            if (count($rows) < 500) break;
        }
        fclose($out);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    /* ---------- a lead: retry Lofty, or delete ---------- */
    if (isset($_POST['do_lead'])) {
        csrf_check();
        $id = (int) ($_POST['lead_id'] ?? 0);
        if ($_POST['do_lead'] === 'retry') {
            [$ok, $detail] = lead_retry_lofty($id);
            lpa_flash($ok ? 'Sent to Lofty: ' . $detail : ($ok === null ? 'Not sent: ' . $detail : 'Lofty refused it again: ' . $detail), !$ok);
            header('Location: admin.php?page=leads&lead=' . $id);
            exit;
        }
        if ($_POST['do_lead'] === 'delete') {
            lead_delete($id);
            lpa_flash('Lead deleted.');
            header('Location: admin.php?page=leads');
            exit;
        }
    }

    /* ---------- a landing page ---------- */
    if (!isset($_POST['do_landing'])) return;
    csrf_check();
    $act = (string) $_POST['do_landing'];
    $all = [];
    foreach (landing_all(true) as $p) $all[$p['slug']] = $p;
    $orig = blog_slugify((string) ($_POST['lp_orig'] ?? ''));
    $old = $orig !== '' ? ($all[$orig] ?? null) : null;
    $removed = landing_removed();

    if ($act === 'delete' && $old) {
        unset($all[$orig]);
        $removed[] = $orig;
        landing_save_all($all);
        landing_removed_save($removed);
        lpa_drop_file($old['file'], $orig);
        lpa_flash('"' . $old['title'] . '" was deleted. Its leads are still under Leads.');
        header('Location: admin.php?page=landing-pages');
        exit;
    }

    if ($act === 'duplicate' && $old) {
        $copy = $old;
        $n = 2;
        $copy['slug'] = $old['slug'] . '-copy';
        while (isset($all[$copy['slug']])) $copy['slug'] = $old['slug'] . '-copy-' . $n++;
        $copy['title'] = $old['title'] . ' (copy)';
        $copy['published'] = false;
        $copy['updated'] = date('Y-m-d');
        $all[$copy['slug']] = $copy;
        landing_save_all($all);
        lpa_flash('Duplicated as a draft. Change its address and details, then publish.');
        header('Location: admin.php?page=landing-pages&edit=' . rawurlencode($copy['slug']));
        exit;
    }

    $errs = [];
    $title = trim((string) ($_POST['lp_title'] ?? ''));
    if ($title === '') $errs[] = 'Give the page a title.';
    $slug = blog_slugify((string) ($_POST['lp_slug'] ?? '') ?: $title);
    if ($slug === '') $errs[] = 'Give the page an address.';
    elseif ($slug !== $orig && isset($all[$slug])) $errs[] = 'Another page already uses the address /page/' . $slug . '.';

    $rawFields = json_decode((string) ($_POST['lp_fields'] ?? '[]'), true);
    if (!is_array($rawFields)) $rawFields = [];
    foreach ($rawFields as $i => $f) {
        if (!is_array($f)) continue;
        $label = trim((string) ($f['label'] ?? ''));
        $type = (string) ($f['type'] ?? '');
        $opts = array_filter(array_map('trim', (array) ($f['options'] ?? [])), 'strlen');
        if ($label === '') $errs[] = 'Question ' . ($i + 1) . ' has no wording. Type the question, or delete it.';
        elseif (in_array($type, ['select', 'checkbox'], true) && !$opts) $errs[] = '"' . $label . '" needs at least one option.';
    }

    // the price (blank or 0 = a free page)
    $priceCents = stripe_parse_price((string) ($_POST['lp_price'] ?? ''));
    if ($priceCents > 0 && $priceCents < STRIPE_MIN_CENTS) $errs[] = 'A price has to be at least $0.50 (Stripe\'s minimum). Leave it empty for a free page.';
    if ($priceCents > 99999999) $errs[] = 'That price is too large.';

    // the picture
    $image = (string) ($old['image'] ?? '');
    if (!empty($_POST['lp_image_clear'])) $image = '';
    if (isset($_FILES['lp_image']) && $_FILES['lp_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        [$path, $uerr] = handle_upload($_FILES['lp_image']);
        if ($uerr) $errs[] = 'Picture: ' . $uerr;
        elseif ($path) $image = $path;
    }

    // the file to hand over
    $file = (string) ($old['file'] ?? '');
    $fileName = trim((string) ($_POST['lp_file_name'] ?? '')) ?: (string) ($old['file_name'] ?? '');
    $dropOld = '';
    if (!empty($_POST['lp_file_clear'])) { $dropOld = $file; $file = ''; $fileName = ''; }
    if (!$errs && isset($_FILES['lp_file']) && $_FILES['lp_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        [$path, $orig_name, $uerr] = landing_private_upload($_FILES['lp_file']);
        if ($uerr) $errs[] = 'File: ' . $uerr;
        elseif ($path) { if ($file !== '') $dropOld = $file; $file = $path; $fileName = $orig_name; }
    }

    $p = landing_clean([
        'slug' => $slug, 'title' => $title, 'desc' => (string) ($_POST['lp_desc'] ?? ''),
        'image' => $image, 'image_alt' => $_POST['lp_image_alt'] ?? '',
        'bottom_title' => $_POST['lp_bottom_title'] ?? '', 'cta' => $_POST['lp_cta'] ?? '',
        'fields' => $rawFields, 'deliver' => $_POST['lp_deliver'] ?? 'file',
        'file' => $file, 'file_name' => $fileName, 'url' => $_POST['lp_url'] ?? '',
        'thanks_title' => $_POST['lp_thanks_title'] ?? '', 'thanks_text' => $_POST['lp_thanks_text'] ?? '',
        'consent' => $_POST['lp_consent'] ?? '',
        'email_lead' => !empty($_POST['lp_email_lead']), 'crm_on' => !empty($_POST['lp_crm_on']),
        'crm_source' => $_POST['lp_crm_source'] ?? '', 'crm_tags' => $_POST['lp_crm_tags'] ?? '',
        'crm_type' => (int) ($_POST['lp_crm_type'] ?? 0), 'indexable' => !empty($_POST['lp_indexable']),
        'price_cents' => $priceCents, 'billing' => $_POST['lp_billing'] ?? 'once',
        'published' => $act === 'publish', 'updated' => date('Y-m-d'),
    ]);
    if (($_POST['lp_deliver'] ?? '') === 'url' && trim((string) ($_POST['lp_url'] ?? '')) !== '' && $p && $p['url'] === '') {
        $errs[] = 'The link must start with https:// (or http://).';
    }

    if ($errs || !$p) {
        // Keep what was typed: the editor is drawn again from the posted values,
        // including a half-finished question (cleaning would drop it, and the
        // owner would lose it while being told to fix it).
        if ($p) {
            $p['fields'] = [];
            foreach ($rawFields as $f) {
                if (!is_array($f)) continue;
                $p['fields'][] = [
                    'id' => preg_match('/^[a-z][a-z0-9_]{1,30}$/', (string) ($f['id'] ?? '')) ? (string) $f['id'] : landing_field_id(),
                    'type' => isset(LANDING_FIELD_TYPES[$f['type'] ?? '']) ? (string) $f['type'] : 'text',
                    'label' => strip_tags((string) ($f['label'] ?? '')),
                    'options' => array_values(array_map(fn($o) => strip_tags((string) $o), (array) ($f['options'] ?? []))),
                    'required' => !empty($f['required']),
                ];
            }
        }
        $GLOBALS['lpa_posted'] = $p ?: null;
        $GLOBALS['lpa_errs'] = $errs ?: ['The page could not be saved.'];
        if ($file !== '' && $file !== ($old['file'] ?? '')) lpa_drop_file($file, '');   // a new upload on a failed save
        return;
    }

    $warn = '';
    if ($act === 'publish' && !landing_deliverable($p)) {
        $p['published'] = false;
        $warn = 'Saved as a draft: add the file (or a link) before it can go live.';
    } elseif ($act === 'publish' && !landing_payable($p)) {
        $p['published'] = false;
        $warn = 'Saved as a draft: this page has a price, so add your Stripe keys under Payments / Stripe before it can go live.';
    }
    if ($orig !== '' && $orig !== $slug) {
        unset($all[$orig]);
        $removed[] = $orig;       // so a page that shipped in landing-pages.php doesn't come back
    }
    $removed = array_values(array_diff($removed, [$slug]));
    $all[$slug] = $p;
    landing_save_all($all);
    landing_removed_save($removed);
    if ($dropOld !== '' && $dropOld !== $p['file']) lpa_drop_file($dropOld, $slug);

    if ($warn) lpa_flash($warn, true);
    elseif ($p['published']) lpa_flash('Published. It is live at ' . abs_url(landing_path($slug)));
    else lpa_flash('Saved as a draft. Only you can see it while you are logged in.');
    header('Location: admin.php?page=landing-pages&edit=' . rawurlencode($slug));
    exit;
}

function landing_admin_title(string $cur): string {
    return $cur === 'leads' ? 'Leads' : 'Landing pages';
}

/** The Leads screen's filters, from the query string. */
function lpa_filters(): array {
    return [
        'source' => (string) ($_GET['source'] ?? ''),
        'q'      => trim((string) ($_GET['q'] ?? '')),
        'days'   => in_array((int) ($_GET['days'] ?? 0), [1, 7, 30, 90], true) ? (int) $_GET['days'] : 0,
    ];
}

function lpa_css(): void { ?>
<link rel="stylesheet" href="assets/css/landing.css?v=<?= @filemtime(__DIR__ . '/../assets/css/landing.css') ?>">
<style>
.main.wide{max-width:1340px}
.lpa-bar{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin-bottom:18px}
.lpa-btn{display:inline-block;background:#B05E51;color:#fff;border:none;padding:11px 20px;font-size:12px;letter-spacing:.1em;text-transform:uppercase;font-weight:600;cursor:pointer;text-decoration:none}
.lpa-btn:hover{background:#9C4A3E;color:#fff}
.lpa-btn.ghost{background:#fff;color:#9C4A3E;border:1px solid rgba(176,94,81,.5)}
.lpa-btn.ghost:hover{background:#FBF7F3}
.lpa-btn.gold{background:#8F6B2E}.lpa-btn.gold:hover{background:#7a5a24}
.lpa-btn.danger{background:#fff;color:#A33A2F;border:1px solid #D9908B}.lpa-btn.danger:hover{background:#FBEAEA}
.lpa-table{width:100%;border-collapse:collapse;background:#fff;border:1px solid rgba(69,50,48,.12);font-size:13.5px}
.lpa-table th{text-align:left;font-size:10.5px;letter-spacing:.12em;text-transform:uppercase;color:#8a746f;font-weight:700;padding:11px 14px;border-bottom:1px solid rgba(69,50,48,.12);background:#FBF7F3}
.lpa-table td{padding:12px 14px;border-top:1px solid rgba(69,50,48,.08);vertical-align:top}
.lpa-table tr.click{cursor:pointer}.lpa-table tr.click:hover td{background:#FBF7F3}
.lpa-table small{color:#8a746f}
.chip{display:inline-block;font-size:11px;font-weight:600;padding:3px 8px;border-radius:20px;white-space:nowrap;margin:0 4px 4px 0}
.chip.ok{background:#EEF6EC;color:#3E6B36}.chip.bad{background:#FBEAEA;color:#A33A2F}.chip.off{background:#F1ECE9;color:#8a746f}.chip.gold{background:#F7EEDC;color:#7a5a24}
.lpa-stats{display:flex;gap:12px;flex-wrap:wrap;margin:0 0 18px}
.lpa-stat{background:#fff;border:1px solid rgba(69,50,48,.12);padding:12px 18px;min-width:130px}
.lpa-stat b{display:block;font-size:24px;color:#453230;font-weight:600}.lpa-stat span{font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:#8a746f}
.lpa-filter{display:flex;gap:10px;flex-wrap:wrap;align-items:center;background:#fff;border:1px solid rgba(69,50,48,.12);padding:12px 14px;margin-bottom:14px}
.lpa-filter select,.lpa-filter input{padding:9px 10px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3;font-size:13.5px}
.lpa-filter input[type=search]{flex:1;min-width:180px}
.lpa-pager{display:flex;gap:10px;align-items:center;justify-content:flex-end;margin-top:12px;font-size:13px}
.lpa-card{background:#fff;border:1px solid rgba(69,50,48,.12);padding:22px 24px;margin-bottom:16px}
.lpa-card h2{font-size:15px;margin:0 0 14px;font-weight:700;display:flex;align-items:center;gap:10px}
.lpa-num{width:24px;height:24px;border-radius:50%;background:#F5EBE4;color:#9C4A3E;font-size:12px;display:inline-flex;align-items:center;justify-content:center;flex:none}
.lpa-kv{display:grid;grid-template-columns:170px 1fr;gap:8px 18px;font-size:14px}
.lpa-kv dt{color:#8a746f;font-size:12px;letter-spacing:.06em;text-transform:uppercase;font-weight:700;padding-top:2px}
.lpa-kv dd{margin:0;white-space:pre-wrap;word-break:break-word}
/* editor */
.lpe{display:grid;grid-template-columns:minmax(0,1fr) 390px;gap:26px;align-items:start}
.lpe .fld input[type=text],.lpe .fld input[type=url],.lpe .fld textarea,.lpe .fld select{width:100%;padding:11px 12px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3;font-size:14.5px;font-family:inherit}
.lpe .fld textarea{min-height:70px;resize:vertical}
.lpe .fld .note{font-size:12px;color:#8a746f;margin-top:5px}
.lpe .two{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.lpe .fixed{display:flex;gap:10px;align-items:center;padding:10px 12px;background:#F7F2EF;border:1px solid rgba(69,50,48,.1);color:#8a746f;font-size:13.5px;margin-bottom:8px}
.lpe .drop{border:1.5px dashed rgba(176,94,81,.45);background:#FBF7F3;padding:18px;display:flex;gap:16px;align-items:center;flex-wrap:wrap}
.lpe .drop img{width:150px;height:84px;object-fit:cover;border:1px solid rgba(69,50,48,.15);background:#EBD2CA}
.lpe .drop .ph{width:150px;height:84px;background:linear-gradient(145deg,#EBD2CA,#D49388);display:flex;align-items:center;justify-content:center;font-size:10px;color:#7a5c55;text-transform:uppercase;letter-spacing:.12em}
.lpe .addr{display:flex;align-items:stretch}.lpe .addr span{padding:11px 10px;background:#F1ECE9;border:1px solid rgba(69,50,48,.2);border-right:0;font-size:13.5px;color:#6b5550;white-space:nowrap}
.lpe .tog{display:flex;gap:9px;align-items:flex-start;font-size:14px;color:#453230;margin:12px 0;cursor:pointer}
.lpe .tog input{margin-top:3px;accent-color:#B05E51;width:16px;height:16px}
.lpe .seg{display:inline-flex;border:1px solid rgba(69,50,48,.2);margin-bottom:14px}
.lpe .seg label{padding:8px 16px;font-size:13px;cursor:pointer;margin:0;display:flex;gap:6px;align-items:center}
.lpe .seg input{accent-color:#B05E51}
.fb-card{border:1px solid rgba(69,50,48,.14);background:#FCFAF8;padding:12px 14px;margin-bottom:10px}
.fb-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:8px}
.fb-head select{padding:5px 8px;font-size:12px;border:1px solid rgba(69,50,48,.2);background:#fff;font-weight:600;color:#6b5550}
.fb-tools{display:flex;gap:4px}
.fb-tools button,.fb-opt button{background:#fff;border:1px solid rgba(69,50,48,.18);color:#6b5550;width:30px;height:30px;cursor:pointer;font-size:14px;line-height:1}
.fb-tools button:hover,.fb-opt button:hover{border-color:#B05E51;color:#9C4A3E}
.fb-tools button.del:hover,.fb-opt button:hover{background:#FBEAEA}
.fb-card input.fb-label{width:100%;padding:10px 11px;border:1px solid rgba(69,50,48,.2);background:#fff;font-size:14px;font-weight:600}
.fb-opts{margin:8px 0 0 14px}
.fb-opt{display:flex;gap:6px;margin-bottom:6px}
.fb-opt input,.fb-opt textarea{flex:1;padding:8px 10px;border:1px solid rgba(69,50,48,.18);background:#fff;font-size:13.5px;font-family:inherit;resize:vertical}
.fb-addopt{background:none;border:none;color:#B05E51;font-size:13px;font-weight:600;cursor:pointer;padding:4px 0}
.fb-foot{display:flex;justify-content:flex-end;margin-top:8px}
.fb-foot label{display:flex;gap:7px;align-items:center;font-size:12.5px;color:#6b5550;cursor:pointer}
.fb-foot input{accent-color:#B05E51}
.fb-add{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.fb-add button{background:#fff;border:1px solid rgba(176,94,81,.45);color:#9C4A3E;padding:8px 12px;font-size:12.5px;font-weight:600;cursor:pointer}
.fb-add button:hover{background:#FBF7F3}
.lpe-side{position:sticky;top:74px}
.lpe-side .ph-head{display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#8a746f;margin-bottom:10px}
.phone{width:375px;max-width:100%;height:740px;border:12px solid #2c201f;border-radius:44px;overflow:hidden;background:#FBF7F3;box-shadow:0 24px 50px rgba(69,50,48,.25);margin:0 auto}
.phone .scr{height:100%;overflow-y:auto;overflow-x:hidden}
.phone .lp{font-size:15px}
.phone .lp-hero{margin:0 0 16px;border:0;box-shadow:none}
.phone .pv-pad{padding:4px 18px 26px}
.phone .lp-title{font-size:25px}
.phone .lp-desc{font-size:15px}
.phone .lp-card{position:static;padding:20px 16px 18px;margin-top:20px;box-shadow:none}
.phone .lp-card h2{font-size:21px}
.phone .lp-top .lp-wrap{padding:12px 16px}.phone .lp-brand{font-size:18px}.phone .lp-phone{font-size:11px}
.lpe .savebar{justify-content:space-between}
.lpe .savebar .r{display:flex;gap:10px;align-items:center}
.lpe .savebar button.ghost{background:#fff;color:#9C4A3E;border:1px solid rgba(176,94,81,.5)}
.lpe .savebar button.danger{background:#fff;color:#A33A2F;border:1px solid #D9908B;padding:13px 18px}
.lp-rte{background:#FBF7F3;border:1px solid rgba(69,50,48,.2)}
.lp-rte .ql-toolbar{border:none;border-bottom:1px solid rgba(69,50,48,.15)}
.lp-rte .ql-container{border:none;font-size:14.5px;font-family:system-ui,sans-serif;min-height:110px}
@media(max-width:1180px){.lpe{grid-template-columns:1fr}.lpe-side{position:static}}
</style>
<?php }

function landing_admin_view(string $cur): void {
    lpa_css();
    [$fmsg, $ferr] = lpa_take_flash();
    if ($fmsg !== '') echo '<div class="' . ($ferr ? 'err' : 'flash') . '">' . esc($fmsg) . '</div>';
    if ($cur === 'leads') {
        isset($_GET['lead']) ? lpa_view_lead((int) $_GET['lead']) : lpa_view_leads();
        return;
    }
    if (isset($_GET['edit'])) { lpa_view_editor((string) $_GET['edit']); return; }
    lpa_view_list();
}

/* ---------- Landing pages: the list ---------- */

function lpa_view_list(): void {
    $pages = landing_all(true);
    $counts = leads_sources(); ?>
    <div class="lpa-bar">
      <div><h1>Landing pages</h1>
        <p class="hint" style="margin:4px 0 0">Lead capture pages at <b><?= esc(preg_replace('#^https?://#', '', site_url())) ?>/page/<i>address</i></b>. A visitor answers your questions, gets the file, and the lead goes to Leads, your email and Lofty.</p></div>
      <a class="lpa-btn" href="admin.php?page=landing-pages&amp;edit=new">+ New landing page</a>
    </div>
    <?php if (!$pages): ?>
      <div class="lpa-card">No landing pages yet. <a href="admin.php?page=landing-pages&amp;edit=new">Make the first one</a>.</div>
    <?php else: ?>
    <table class="lpa-table">
      <thead><tr><th>Page</th><th>Status</th><th>Leads</th><th>Updated</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($pages as $p):
          $live = landing_live($p);
          $n = $counts['page:' . $p['slug']]['count'] ?? 0; ?>
        <tr>
          <td><a href="admin.php?page=landing-pages&amp;edit=<?= esc(rawurlencode($p['slug'])) ?>"><b><?= esc($p['title']) ?></b></a><br><small>/page/<?= esc($p['slug']) ?> · <?= $p['price_cents'] > 0 ? esc(stripe_price_label($p['price_cents'], $p['billing'])) : 'free' ?></small></td>
          <td><?php if ($live): ?><span class="chip ok">Live</span><?php elseif (!landing_deliverable($p)): ?><span class="chip gold">Draft · needs its file</span><?php elseif ($p['published'] && !landing_payable($p)): ?><span class="chip gold">Hidden · needs Stripe</span><?php else: ?><span class="chip off">Draft</span><?php endif; ?></td>
          <td><?php if ($n): ?><a href="admin.php?page=leads&amp;source=<?= esc(rawurlencode('page:' . $p['slug'])) ?>"><?= $n ?> lead<?= $n === 1 ? '' : 's' ?></a><?php else: ?><small>none yet</small><?php endif; ?></td>
          <td><small><?= esc($p['updated']) ?></small></td>
          <td style="white-space:nowrap;text-align:right">
            <a href="admin.php?page=landing-pages&amp;edit=<?= esc(rawurlencode($p['slug'])) ?>">Edit</a> ·
            <a href="<?= esc(landing_path($p['slug'])) ?>" target="_blank"><?= $live ? 'View' : 'Preview' ?></a> ·
            <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="lp_orig" value="<?= esc($p['slug']) ?>"><button name="do_landing" value="duplicate" style="background:none;border:0;color:#B05E51;cursor:pointer;font-size:13.5px;padding:0">Duplicate</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif;
}

/* ---------- Landing pages: the editor ---------- */

function lpa_view_editor(string $slug): void {
    $isNew = $slug === 'new';
    $saved = $isNew ? null : landing_one($slug, true);
    if (!$isNew && !$saved) { echo '<div class="err">There is no landing page at that address. <a href="admin.php?page=landing-pages">Back to the list</a></div>'; return; }
    $p = $GLOBALS['lpa_posted'] ?? $saved ?? landing_clean([
        'slug' => 'new-page', 'title' => '', 'cta' => 'Send it to me',
        'consent' => 'By sending this form you agree that Erika K. Page of Axen Realty may contact you by phone, text or email.',
        'fields' => [['id' => 'mobile', 'type' => 'phone', 'label' => 'Mobile Number', 'required' => true]],
    ]);
    if ($isNew && !isset($GLOBALS['lpa_posted'])) { $p['slug'] = ''; $p['title'] = ''; }
    $errs = $GLOBALS['lpa_errs'] ?? [];
    $live = $saved && landing_live($saved);
    $fileOk = $p['file'] !== '' && is_file(landing_abs($p['file']));
    $host = preg_replace('#^https?://#', '', site_url());
    $json = fn($v) => json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    ?>
    <div class="lpa-bar">
      <div><a href="admin.php?page=landing-pages" style="font-size:13px">← Landing pages</a>
        <h1 style="margin-top:6px"><?= $isNew ? 'New landing page' : esc($saved['title']) ?></h1>
        <p class="hint" style="margin:4px 0 0"><?php if ($live): ?><span class="chip ok">Live</span> <a href="<?= esc(landing_path($saved['slug'])) ?>" target="_blank"><?= esc($host . landing_path($saved['slug'])) ?></a><?php elseif ($saved): ?><span class="chip off">Draft</span> <a href="<?= esc(landing_path($saved['slug'])) ?>" target="_blank">Preview it as visitors will see it</a><?php else: ?>Fill in the steps, then save it as a draft or publish it.<?php endif; ?></p>
      </div>
    </div>
    <?php if ($errs): ?><div class="err"><b>Not saved yet.</b><br><?= implode('<br>', array_map('esc', $errs)) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data" id="lpe-form" class="lpe">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="lp_orig" value="<?= esc($isNew ? '' : $saved['slug']) ?>">
      <input type="hidden" name="lp_fields" id="lp_fields" value="">
      <input type="hidden" name="lp_desc" id="lp_desc" value="<?= esc($p['desc']) ?>">
      <div>
        <div class="lpa-card">
          <h2><span class="lpa-num">1</span> Select image</h2>
          <div class="drop">
            <?php if ($p['image'] !== ''): ?><img id="lpe-thumb" src="<?= esc(asset_url($p['image'])) ?>" alt=""><?php else: ?><div class="ph" id="lpe-thumb-ph">No picture</div><img id="lpe-thumb" alt="" style="display:none"><?php endif; ?>
            <div>
              <input type="file" name="lp_image" id="lp_image" accept="image/jpeg,image/png,image/webp">
              <div class="note" style="font-size:12px;color:#8a746f;margin-top:6px">1920 × 1080 works best (16:9). JPG, PNG or WEBP.</div>
              <?php if ($p['image'] !== ''): ?><label class="tog" style="margin:8px 0 0;font-size:13px;color:#8C3B32"><input type="checkbox" name="lp_image_clear" value="1" id="lp_image_clear"> Remove the picture</label><?php endif; ?>
            </div>
          </div>
          <div class="fld"><label>Picture description <span style="text-transform:none;letter-spacing:0;font-weight:400;color:#8a746f">(for screen readers and search)</span></label><input type="text" name="lp_image_alt" value="<?= esc($p['image_alt']) ?>"></div>
        </div>

        <div class="lpa-card">
          <h2><span class="lpa-num">2</span> Write description</h2>
          <div class="fld"><label>Title *</label><input type="text" name="lp_title" id="lp_title" value="<?= esc($p['title']) ?>" required maxlength="160"></div>
          <div class="fld"><label>Description body</label><div class="lp-rte"><div id="lp-desc-editor"><?= $p['desc'] ?></div></div></div>
          <div class="two">
            <div class="fld"><label>Bottom title</label><input type="text" name="lp_bottom_title" id="lp_bottom_title" value="<?= esc($p['bottom_title']) ?>" maxlength="120"><div class="note">The heading over the form.</div></div>
            <div class="fld"><label>Call-to-action button *</label><input type="text" name="lp_cta" id="lp_cta" value="<?= esc($p['cta']) ?>" required maxlength="80"></div>
          </div>
        </div>

        <div class="lpa-card">
          <h2><span class="lpa-num">3</span> Set price</h2>
          <div class="two">
            <div class="fld"><label>Price ($)</label><input type="text" name="lp_price" id="lp_price" inputmode="decimal" value="<?= $p['price_cents'] > 0 ? esc(number_format($p['price_cents'] / 100, 2, '.', '')) : '' ?>" placeholder="Free" maxlength="12"><div class="note">Leave empty for a free page. Visitors pay on Stripe's secure checkout, then get the file.</div></div>
            <div class="fld"><label>Billing</label><select name="lp_billing" id="lp_billing"><?php foreach (STRIPE_BILLING as $k => $lbl): ?><option value="<?= esc($k) ?>" <?= $p['billing'] === $k ? 'selected' : '' ?>><?= esc($lbl) ?></option><?php endforeach; ?></select><div class="note">A subscription charges again every month or year until the buyer cancels.</div></div>
          </div>
          <?php if (!stripe_ready()): ?><p style="font-size:13px;color:#7a5a24;background:#F7EEDC;padding:10px 12px;margin:4px 0 0">Stripe isn't set up yet. A page with a price stays hidden until you add your keys under <a href="admin.php?page=stripe">Payments / Stripe</a>.</p><?php endif; ?>
        </div>

        <div class="lpa-card">
          <h2><span class="lpa-num">4</span> Collect info</h2>
          <div class="note" style="font-size:12.5px;color:#8a746f;margin-bottom:8px">Basic info fields can't be edited: every page asks for these.</div>
          <div class="fixed">👤 Name <span style="margin-left:auto">required</span></div>
          <div class="fixed">✉ Email <span style="margin-left:auto">required</span></div>
          <div style="font-size:12.5px;color:#6b5550;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin:16px 0 8px">Your questions</div>
          <div id="fb-list"></div>
          <div class="fb-add" id="fb-add">
            <?php foreach (LANDING_FIELD_TYPES as $k => $lbl): ?><button type="button" data-add="<?= esc($k) ?>">+ <?= esc($lbl) ?></button><?php endforeach; ?>
          </div>
        </div>

        <div class="lpa-card">
          <h2><span class="lpa-num">5</span> The file they get</h2>
          <div class="seg">
            <label><input type="radio" name="lp_deliver" value="file" <?= $p['deliver'] === 'file' ? 'checked' : '' ?>> Upload file</label>
            <label style="border-left:1px solid rgba(69,50,48,.2)"><input type="radio" name="lp_deliver" value="url" <?= $p['deliver'] === 'url' ? 'checked' : '' ?>> Add URL</label>
          </div>
          <div id="lpe-file" <?= $p['deliver'] === 'url' ? 'hidden' : '' ?>>
            <div class="drop" style="display:block">
              <input type="file" name="lp_file" id="lp_file" accept=".pdf,.zip,.docx,.xlsx,.pptx,.jpg,.jpeg,.png,.mp4,.mp3">
              <div class="note" style="font-size:12px;color:#8a746f;margin-top:6px">PDF, ZIP, Word, Excel, PowerPoint, picture, MP4 or MP3, up to 300 MB. It is kept private: people only get it through the form.</div>
            </div>
            <?php if ($p['file'] !== ''): ?>
              <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-top:12px;padding:12px 14px;border:1px solid rgba(69,50,48,.12);background:#fff">
                <?php if ($fileOk): ?><span style="color:#3E6B36">✔</span> <b style="font-size:14px"><?= esc($p['file_name']) ?></b> <small style="color:#8a746f"><?= esc(landing_size((int) filesize(landing_abs($p['file'])))) ?></small>
                  <?php if ($saved && $saved['file'] === $p['file']): ?><a class="lpa-btn ghost" style="padding:6px 12px" href="admin.php?page=landing-pages&amp;getfile=<?= esc(rawurlencode($saved['slug'])) ?>">Download</a><?php endif; ?>
                <?php else: ?><span style="color:#A33A2F">The file is missing on the server. Upload it again.</span><?php endif; ?>
                <label class="tog" style="margin:0 0 0 auto;font-size:13px;color:#8C3B32"><input type="checkbox" name="lp_file_clear" value="1"> Remove</label>
              </div>
            <?php elseif ($p['file_name'] !== ''): ?>
              <p style="font-size:13px;color:#7a5a24;background:#F7EEDC;padding:10px 12px;margin:12px 0 0">Upload <b><?= esc($p['file_name']) ?></b> here. The page can't go live without it.</p>
            <?php endif; ?>
            <div class="fld"><label>File name visitors see</label><input type="text" name="lp_file_name" value="<?= esc($p['file_name']) ?>" placeholder="taken from the upload"></div>
          </div>
          <div id="lpe-url" <?= $p['deliver'] === 'url' ? '' : 'hidden' ?>>
            <div class="fld"><label>Link they get</label><input type="url" name="lp_url" value="<?= esc($p['url']) ?>" placeholder="https://"><div class="note">A Google Drive file, a video, a booking page… Visitors are sent there after the form.</div></div>
          </div>
        </div>

        <div class="lpa-card">
          <h2><span class="lpa-num">6</span> Options</h2>
          <div class="fld"><label>Page address *</label><div class="addr"><span><?= esc($host) ?>/page/</span><input type="text" name="lp_slug" id="lp_slug" value="<?= esc($p['slug']) ?>" pattern="[a-z0-9][a-z0-9\-]*" title="Lowercase letters, numbers and dashes" maxlength="80" style="flex:1"></div><div class="note">Lowercase letters, numbers and dashes. Changing it breaks links already shared.</div></div>
          <div class="two">
            <div class="fld"><label>After the form: heading</label><input type="text" name="lp_thanks_title" value="<?= esc($p['thanks_title']) ?>" maxlength="120"></div>
            <div class="fld"><label>After the form: message</label><input type="text" name="lp_thanks_text" value="<?= esc($p['thanks_text']) ?>" maxlength="400"></div>
          </div>
          <div class="fld"><label>Consent line under the button</label><textarea name="lp_consent" id="lp_consent" maxlength="400"><?= esc($p['consent']) ?></textarea></div>
          <label class="tog"><input type="checkbox" name="lp_email_lead" value="1" <?= $p['email_lead'] ? 'checked' : '' ?>> <span>Email the visitor their download link too<br><small style="color:#8a746f">So they can find it again later. Uses the Email / Forms settings.</small></span></label>
          <label class="tog"><input type="checkbox" name="lp_indexable" value="1" <?= $p['indexable'] ? 'checked' : '' ?>> <span>Let Google list this page<br><small style="color:#8a746f">Off by default: campaign pages are usually shared by link, not found by search.</small></span></label>
          <div style="height:1px;background:rgba(69,50,48,.1);margin:16px 0"></div>
          <label class="tog"><input type="checkbox" name="lp_crm_on" value="1" <?= $p['crm_on'] ? 'checked' : '' ?>> <span>Send these leads to Lofty<?= lofty_enabled() ? '' : ' <small style="color:#A33A2F">(Lofty is switched off under CRM / Lofty, so nothing is sent yet)</small>' ?></span></label>
          <div class="two">
            <div class="fld"><label>Lofty source</label><input type="text" name="lp_crm_source" value="<?= esc($p['crm_source']) ?>"></div>
            <div class="fld"><label>Lofty lead type</label><select name="lp_crm_type"><?php foreach (LANDING_LEAD_TYPES as $id => $lbl): ?><option value="<?= $id ?>" <?= $p['crm_type'] === $id ? 'selected' : '' ?>><?= esc($lbl) ?></option><?php endforeach; ?></select></div>
          </div>
          <div class="fld"><label>Lofty tags (comma-separated)</label><input type="text" name="lp_crm_tags" value="<?= esc($p['crm_tags']) ?>"></div>
        </div>

        <div class="savebar">
          <div><?php if (!$isNew): ?><button type="submit" name="do_landing" value="delete" class="danger" id="lpe-del">Delete</button><?php endif; ?></div>
          <div class="r">
            <span id="lpe-dirty" style="display:none">Unsaved changes</span>
            <?php if ($live): ?><button type="submit" name="do_landing" value="draft" class="ghost">Unpublish</button><?php else: ?><button type="submit" name="do_landing" value="draft" class="ghost">Save as draft</button><?php endif; ?>
            <button type="submit" name="do_landing" value="publish"><?= $live ? 'Update' : 'Publish' ?></button>
          </div>
        </div>
      </div>

      <aside class="lpe-side">
        <div class="ph-head"><span>Phone preview · updates as you type</span><?php if ($saved): ?><a href="<?= esc(landing_path($saved['slug'])) ?>" target="_blank">Open the page ↗</a><?php endif; ?></div>
        <div class="phone"><div class="scr lp" id="pv">
          <header class="lp-top"><div class="lp-wrap"><span class="lp-brand">Erika<span>K</span>Page</span><span class="lp-phone"><?= esc(cms('global.phone')) ?></span></div></header>
          <figure class="lp-hero" id="pv-hero" <?= $p['image'] === '' ? 'hidden' : '' ?>><img id="pv-img" src="<?= esc($p['image'] !== '' ? asset_url($p['image']) : '') ?>" alt=""></figure>
          <div class="pv-pad">
            <div class="lp-eyebrow">Erika K. Page · Axen Realty</div>
            <h1 class="lp-title" id="pv-title"></h1>
            <div class="lp-rule"></div>
            <div class="lp-desc" id="pv-desc"></div>
            <div class="lp-card">
              <h2 id="pv-bt"></h2><div class="lp-rule" id="pv-btr"></div>
              <div class="lp-field"><label class="lp-label">Name<span class="lp-req">*</span></label><input class="lp-input" placeholder="" tabindex="-1"></div>
              <div class="lp-field"><label class="lp-label">Email<span class="lp-req">*</span></label><input class="lp-input" tabindex="-1"></div>
              <div id="pv-fields"></div>
              <div class="lp-total" id="pv-total" hidden><span>Total</span><span class="lp-dots"></span><b id="pv-price"></b></div>
              <button type="button" class="lp-btn" id="pv-cta" tabindex="-1"></button>
              <p class="lp-consent" id="pv-consent"></p>
            </div>
          </div>
        </div></div>
      </aside>
    </form>

    <script>
    window.addEventListener('load', function(){
      var TYPES=<?= $json(LANDING_FIELD_TYPES) ?>;
      var fields=<?= $json(array_values($p['fields'])) ?>;
      var isNew=<?= $isNew ? 'true' : 'false' ?>;
      var $=function(id){return document.getElementById(id);};
      var form=$('lpe-form'), list=$('fb-list'), hidden=$('lp_fields');
      var dirty=false;
      function markDirty(){ if(!dirty){dirty=true;$('lpe-dirty').style.display='';} }
      function el(tag,attrs,kids){var e=document.createElement(tag);for(var k in (attrs||{})){if(k==='text')e.textContent=attrs[k];else if(k==='cls')e.className=attrs[k];else e.setAttribute(k,attrs[k]);}(kids||[]).forEach(function(c){e.appendChild(c);});return e;}
      function uid(){return 'q'+Math.random().toString(16).slice(2,10);}
      function sync(){ hidden.value=JSON.stringify(fields); preview(); }

      /* ---------- the question builder ---------- */
      function draw(){
        list.innerHTML='';
        if(!fields.length) list.appendChild(el('p',{cls:'note',text:'No questions yet. Name and email are always asked; add more below.',style:'font-size:13px;color:#8a746f'}));
        fields.forEach(function(f,i){
          var card=el('div',{cls:'fb-card'});
          var sel=el('select',{'aria-label':'Question type'});
          Object.keys(TYPES).forEach(function(k){var o=el('option',{value:k,text:TYPES[k]});if(k===f.type)o.selected=true;sel.appendChild(o);});
          sel.onchange=function(){f.type=sel.value;if((f.type==='select'||f.type==='checkbox')&&!(f.options&&f.options.length))f.options=['Option 1'];markDirty();draw();sync();};
          var tools=el('div',{cls:'fb-tools'});
          [['↑','Move up',function(){if(i>0){fields.splice(i-1,0,fields.splice(i,1)[0]);}}],['↓','Move down',function(){if(i<fields.length-1){fields.splice(i+1,0,fields.splice(i,1)[0]);}}],['✕','Delete question',function(){if(confirm('Delete this question?'))fields.splice(i,1);}]].forEach(function(t){
            var b=el('button',{type:'button',title:t[1],'aria-label':t[1],text:t[0]}); if(t[0]==='✕')b.className='del';
            b.onclick=function(){t[2]();markDirty();draw();sync();}; tools.appendChild(b);
          });
          card.appendChild(el('div',{cls:'fb-head'},[sel,tools]));
          var lab=el('input',{cls:'fb-label',type:'text',placeholder:'Type the question',value:f.label||'',maxlength:'300'});
          lab.oninput=function(){f.label=lab.value;markDirty();sync();};
          card.appendChild(lab);
          if(f.type==='select'||f.type==='checkbox'){
            var opts=el('div',{cls:'fb-opts'});
            (f.options||[]).forEach(function(o,j){
              // a checkbox's text is often a whole agreement: give it room
              var inp=f.type==='checkbox'?el('textarea',{placeholder:'Option',maxlength:'1000',rows:String(Math.min(5,Math.max(1,Math.ceil(o.length/70))))}):el('input',{type:'text',value:o,placeholder:'Option',maxlength:'1000'});
              if(f.type==='checkbox')inp.value=o;
              inp.oninput=function(){f.options[j]=inp.value;markDirty();sync();};
              inp.onkeydown=function(e){if(e.key==='Enter'){e.preventDefault();f.options.splice(j+1,0,'');markDirty();draw();sync();var n=list.querySelectorAll('.fb-card')[i].querySelectorAll('.fb-opt input')[j+1];if(n)n.focus();}};
              var x=el('button',{type:'button',title:'Remove option','aria-label':'Remove option',text:'×'});
              x.onclick=function(){f.options.splice(j,1);markDirty();draw();sync();};
              opts.appendChild(el('div',{cls:'fb-opt'},[inp,x]));
            });
            var add=el('button',{type:'button',cls:'fb-addopt',text:'+ Add option'});
            add.onclick=function(){f.options=f.options||[];f.options.push('');markDirty();draw();sync();var ins=list.querySelectorAll('.fb-card')[i].querySelectorAll('.fb-opt input');if(ins.length)ins[ins.length-1].focus();};
            opts.appendChild(add);
            card.appendChild(opts);
          }
          var req=el('input',{type:'checkbox'}); req.checked=!!f.required;
          req.onchange=function(){f.required=req.checked;markDirty();sync();};
          card.appendChild(el('div',{cls:'fb-foot'},[el('label',{},[req,document.createTextNode(' Required')])]));
          list.appendChild(card);
        });
      }
      $('fb-add').addEventListener('click',function(e){
        var t=e.target.getAttribute('data-add'); if(!t)return;
        var f={id:uid(),type:t,label:'',required:true,options:(t==='select'||t==='checkbox')?['Option 1']:[]};
        fields.push(f); markDirty(); draw(); sync();
        var cards=list.querySelectorAll('.fb-card'); cards[cards.length-1].querySelector('.fb-label').focus();
      });

      /* ---------- description editor ---------- */
      var q=new Quill('#lp-desc-editor',{theme:'snow',modules:{toolbar:[['bold','italic','underline'],[{list:'ordered'},{list:'bullet'}],['link'],['clean']]}});
      q.on('text-change',function(){ $('lp_desc').value=q.root.innerHTML; markDirty(); preview(); });
      $('lp_desc').value=q.root.innerHTML;

      /* ---------- the phone preview: same markup and stylesheet as the page ---------- */
      function txt(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML;}
      function preview(){
        $('pv-title').textContent=$('lp_title').value||'Your title';
        $('pv-desc').innerHTML=q.root.innerHTML;
        var bt=$('lp_bottom_title').value; $('pv-bt').textContent=bt; $('pv-bt').hidden=!bt; $('pv-btr').hidden=!bt;
        $('pv-cta').textContent=$('lp_cta').value||'Send it to me';
        $('pv-consent').textContent=$('lp_consent').value;
        var h='';
        fields.forEach(function(f){
          var star=f.required?'<span class="lp-req">*</span>':'', L=txt(f.label||'Your question');
          if(f.type==='checkbox'){ h+='<div class="lp-field"><div class="lp-label">'+L+star+'</div>'; (f.options||[]).forEach(function(o){h+='<label class="lp-check"><input type="checkbox" tabindex="-1"><span>'+txt(o)+'</span></label>';}); h+='</div>'; }
          else if(f.type==='select'){ h+='<div class="lp-field"><label class="lp-label">'+L+star+'</label><select class="lp-input" tabindex="-1"><option>Choose one</option>'; (f.options||[]).forEach(function(o){h+='<option>'+txt(o)+'</option>';}); h+='</select></div>'; }
          else if(f.type==='textarea'){ h+='<div class="lp-field"><label class="lp-label">'+L+star+'</label><textarea class="lp-input" tabindex="-1"></textarea></div>'; }
          else { h+='<div class="lp-field"><label class="lp-label">'+L+star+'</label><input class="lp-input" tabindex="-1"></div>'; }
        });
        $('pv-fields').innerHTML=h;
        var pr=parseFloat(($('lp_price').value||'').replace(/[^0-9.]/g,''));
        $('pv-total').hidden=!(pr>0);
        if(pr>0){ var b=$('lp_billing').value; $('pv-price').textContent='$'+(pr%1?pr.toFixed(2):pr.toFixed(0)).replace(/\B(?=(\d{3})+(?!\d))/g,',')+(b==='month'?' / month':b==='year'?' / year':''); }
      }
      ['lp_title','lp_bottom_title','lp_cta','lp_consent','lp_price'].forEach(function(id){ $(id).addEventListener('input',function(){markDirty();preview();}); });
      $('lp_billing').addEventListener('change',function(){markDirty();preview();});

      /* new pages take their address from the title until it is typed in */
      var slugTouched=!isNew;
      $('lp_slug').addEventListener('input',function(){slugTouched=true;});
      $('lp_title').addEventListener('input',function(){
        if(slugTouched)return;
        $('lp_slug').value=$('lp_title').value.replace(/<[^>]*>/g,'').toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g,'').replace(/&/g,' and ').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,80);
      });

      /* picture: show the new one straight away */
      $('lp_image').addEventListener('change',function(){
        var f=this.files&&this.files[0]; if(!f)return; markDirty();
        var r=new FileReader(); r.onload=function(){ $('pv-img').src=r.result; $('pv-hero').hidden=false; var t=$('lpe-thumb'); t.src=r.result; t.style.display=''; var ph=$('lpe-thumb-ph'); if(ph)ph.style.display='none'; }; r.readAsDataURL(f);
      });
      var clr=$('lp_image_clear'); if(clr)clr.addEventListener('change',function(){ $('pv-hero').hidden=clr.checked; markDirty(); });

      /* file or link */
      form.querySelectorAll('input[name=lp_deliver]').forEach(function(r){ r.addEventListener('change',function(){ var isFile=form.querySelector('input[name=lp_deliver]:checked').value==='file'; $('lpe-file').hidden=!isFile; $('lpe-url').hidden=isFile; markDirty(); }); });
      form.querySelectorAll('input,select,textarea').forEach(function(i){ if(!i.closest('#fb-list'))i.addEventListener('change',markDirty); });

      var del=$('lpe-del');
      if(del)del.addEventListener('click',function(e){ if(!confirm('Delete this landing page? Its address will stop working. Leads it collected stay under Leads.'))e.preventDefault(); });
      form.addEventListener('submit',function(){ hidden.value=JSON.stringify(fields); $('lp_desc').value=q.root.innerHTML; dirty=false; });
      window.addEventListener('beforeunload',function(e){ if(dirty){e.preventDefault();e.returnValue='';} });

      draw(); sync();
    });
    </script>
    <?php
}

/* ---------- Leads ---------- */

function lpa_chips(array $l): string {
    $h = '';
    if ($pc = stripe_lead_chip($l)) $h .= '<span class="chip ' . $pc[0] . '">' . esc($pc[1]) . '</span>';
    if ($l['email_ok'] !== null) $h .= (int) $l['email_ok'] ? '<span class="chip ok" title="' . esc((string) $l['email_detail']) . '">Emailed</span>' : '<span class="chip bad" title="' . esc((string) $l['email_detail']) . '">Email failed</span>';
    $h .= $l['crm_ok'] === null ? '<span class="chip off" title="' . esc((string) $l['crm_detail']) . '">Lofty: not sent</span>'
        : ((int) $l['crm_ok'] ? '<span class="chip ok" title="' . esc((string) $l['crm_detail']) . '">In Lofty</span>' : '<span class="chip bad" title="' . esc((string) $l['crm_detail']) . '">Lofty failed</span>');
    if ($l['kind'] === 'landing') $h .= (int) $l['downloads'] ? '<span class="chip gold">Downloaded' . ((int) $l['downloads'] > 1 ? ' ×' . (int) $l['downloads'] : '') . '</span>' : '<span class="chip off">Not downloaded</span>';
    return $h;
}

function lpa_when(string $dt): string {
    $t = strtotime($dt);
    if (!$t) return esc($dt);
    $d = date('Y-m-d', $t);
    $label = $d === date('Y-m-d') ? 'Today' : ($d === date('Y-m-d', time() - 86400) ? 'Yesterday' : date('M j, Y', $t));
    return esc($label) . '<br><small>' . esc(date('g:i a', $t)) . '</small>';
}

function lpa_view_leads(): void {
    $f = lpa_filters();
    $per = 50;
    $pg = max(1, (int) ($_GET['p'] ?? 1));
    $total = leads_count($f);
    $rows = leads_list($f, $per, ($pg - 1) * $per);
    $sources = leads_sources();
    $all = leads_count();
    $week = leads_count(['days' => 7]);
    $today = leads_count(['days' => 1]);
    $qs = fn(array $o) => 'admin.php?' . http_build_query(array_filter(['page' => 'leads'] + $o + $f, fn($v) => $v !== '' && $v !== 0));
    ?>
    <div class="lpa-bar">
      <div><h1>Leads</h1><p class="hint" style="margin:4px 0 0">Everyone who has filled in a form on the site or a landing page, newest first. Each one is also emailed to you<?= lofty_enabled() ? ' and sent to Lofty' : '' ?>.</p></div>
      <a class="lpa-btn ghost" href="<?= esc($qs(['export' => 'csv'])) ?>">Export CSV</a>
    </div>
    <div class="lpa-stats">
      <div class="lpa-stat"><b><?= $all ?></b><span>All leads</span></div>
      <div class="lpa-stat"><b><?= $week ?></b><span>Last 7 days</span></div>
      <div class="lpa-stat"><b><?= $today ?></b><span>Last 24 hours</span></div>
      <div class="lpa-stat"><b><?= count($sources) ?></b><span>Sources</span></div>
    </div>
    <?php if (!setting('smtp.host') || !setting('smtp.to')): ?><div class="err">Email isn't set up yet, so leads are only being kept here. Set it up under <a href="admin.php?page=email">Email / Forms</a>.</div><?php endif; ?>
    <form class="lpa-filter" method="get">
      <input type="hidden" name="page" value="leads">
      <select name="source" aria-label="Source"><option value="">Every source</option>
        <?php foreach ($sources as $k => $s): ?><option value="<?= esc($k) ?>" <?= $f['source'] === $k ? 'selected' : '' ?>><?= esc(($s['kind'] === 'landing' ? 'Landing page: ' : 'Form: ') . $s['label']) ?> (<?= $s['count'] ?>)</option><?php endforeach; ?>
      </select>
      <select name="days" aria-label="Period"><?php foreach ([0 => 'Any time', 1 => 'Last 24 hours', 7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'] as $d => $lbl): ?><option value="<?= $d ?>" <?= $f['days'] === $d ? 'selected' : '' ?>><?= $lbl ?></option><?php endforeach; ?></select>
      <input type="search" name="q" value="<?= esc($f['q']) ?>" placeholder="Search name, email, phone or answers">
      <button class="lpa-btn" type="submit">Filter</button>
      <?php if ($f['source'] !== '' || $f['q'] !== '' || $f['days']): ?><a href="admin.php?page=leads" style="font-size:13px">Clear</a><?php endif; ?>
    </form>
    <?php if (!$rows): ?>
      <div class="lpa-card"><?= $all ? 'No leads match that filter.' : 'No leads yet. Every form on the site and every landing page sends its leads here.' ?></div>
    <?php else: ?>
      <table class="lpa-table">
        <thead><tr><th style="width:110px">Received</th><th>Name</th><th>Contact</th><th>Source</th><th>Delivered</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $l): $href = 'admin.php?page=leads&lead=' . (int) $l['id']; ?>
          <tr class="click" onclick="location.href='<?= esc($href) ?>'">
            <td><?= lpa_when((string) $l['created']) ?></td>
            <td><a href="<?= esc($href) ?>"><b><?= esc((string) $l['name'] ?: '(no name)') ?></b></a></td>
            <td><?= esc((string) $l['email']) ?><?php if ($l['phone']): ?><br><small><?= esc((string) $l['phone']) ?></small><?php endif; ?></td>
            <td><?= esc((string) $l['source_label']) ?><br><small><?= $l['kind'] === 'landing' ? 'Landing page' : 'Website form' ?></small></td>
            <td><?= lpa_chips($l) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php $pages = (int) ceil($total / $per); if ($pages > 1): ?>
        <div class="lpa-pager"><?php if ($pg > 1): ?><a href="<?= esc($qs(['p' => $pg - 1])) ?>">← Newer</a><?php endif; ?><span>Page <?= $pg ?> of <?= $pages ?> · <?= $total ?> leads</span><?php if ($pg < $pages): ?><a href="<?= esc($qs(['p' => $pg + 1])) ?>">Older →</a><?php endif; ?></div>
      <?php endif; ?>
    <?php endif;
}

function lpa_view_lead(int $id): void {
    $l = lead_get($id);
    if (!$l) { echo '<div class="err">That lead isn\'t there any more. <a href="admin.php?page=leads">Back to Leads</a></div>'; return; } ?>
    <a href="admin.php?page=leads" style="font-size:13px">← Leads</a>
    <div class="lpa-bar" style="margin-top:6px">
      <div><h1><?= esc((string) $l['name'] ?: '(no name)') ?></h1><p class="hint" style="margin:4px 0 0"><?= lpa_chips($l) ?></p></div>
    </div>
    <div class="lpa-card">
      <dl class="lpa-kv">
        <dt>Email</dt><dd><?= $l['email'] ? '<a href="mailto:' . esc((string) $l['email']) . '">' . esc((string) $l['email']) . '</a>' : '—' ?></dd>
        <dt>Phone</dt><dd><?= $l['phone'] ? '<a href="tel:' . esc(preg_replace('/[^0-9+]/', '', (string) $l['phone'])) . '">' . esc((string) $l['phone']) . '</a>' : '—' ?></dd>
        <dt>Source</dt><dd><?= esc((string) $l['source_label']) ?> · <a href="<?= esc((string) $l['page_url']) ?>" target="_blank"><?= esc((string) $l['page_url']) ?></a></dd>
        <dt>Received</dt><dd><?= esc(date('l, F j, Y \a\t g:i a', strtotime((string) $l['created']) ?: time())) ?></dd>
      </dl>
    </div>
    <div class="lpa-card">
      <h2>Their answers</h2>
      <dl class="lpa-kv">
        <?php foreach ($l['answers'] as $a): if (!is_array($a) || count($a) !== 2 || in_array($a[0], ['Name', 'Email'], true)) continue; ?>
          <dt><?= esc((string) $a[0]) ?></dt><dd><?= esc((string) $a[1]) ?></dd>
        <?php endforeach; ?>
      </dl>
    </div>
    <div class="lpa-card">
      <h2>Where it went</h2>
      <dl class="lpa-kv">
        <dt>Email to you</dt><dd><?= $l['email_ok'] === null ? '—' : ((int) $l['email_ok'] ? 'Sent' : '<b style="color:#A33A2F">Failed</b>') ?><?= $l['email_detail'] ? '<br><small style="color:#8a746f">' . esc((string) $l['email_detail']) . '</small>' : '' ?></dd>
        <dt>Lofty</dt><dd><?= $l['crm_ok'] === null ? 'Not sent' : ((int) $l['crm_ok'] ? 'Lead created' : '<b style="color:#A33A2F">Failed</b>') ?><?= $l['crm_detail'] ? '<br><small style="color:#8a746f">' . esc((string) $l['crm_detail']) . '</small>' : '' ?></dd>
        <?php $pay = (array) ($l['meta']['pay'] ?? []); if ($pay): $dash = 'https://dashboard.stripe.com/' . (empty($pay['live']) ? 'test/' : ''); ?>
          <dt>Payment</dt><dd><?= esc(($pc = stripe_lead_chip($l)) ? $pc[1] : (string) ($pay['state'] ?? '')) ?><?= !empty($pay['paid_at']) ? ' · ' . esc(date('M j, Y g:i a', strtotime((string) $pay['paid_at']))) : '' ?>
            <?php if (!empty($pay['intent'])): ?><br><small><a href="<?= esc($dash . 'payments/' . $pay['intent']) ?>" target="_blank">See it in Stripe ↗</a></small><?php endif; ?>
            <?php if (!empty($pay['sub'])): ?><br><small><a href="<?= esc($dash . 'subscriptions/' . $pay['sub']) ?>" target="_blank">Subscription in Stripe ↗</a><?= !empty($pay['sub_period_end']) ? ' · current period ends ' . esc((string) $pay['sub_period_end']) : '' ?><?= ($pay['sub_cancel_at_end'] ?? '') === '1' ? ' · cancels then' : '' ?></small><?php endif; ?>
            <?php if (!empty($pay['error'])): ?><br><small style="color:#A33A2F"><?= esc((string) $pay['error']) ?></small><?php endif; ?></dd>
        <?php endif; ?>
        <?php if ($l['kind'] === 'landing'): ?><dt>Downloads</dt><dd><?= (int) $l['downloads'] ?><?= $l['last_download'] ? ' · last ' . esc(date('M j, g:i a', strtotime((string) $l['last_download']))) : '' ?></dd><?php endif; ?>
      </dl>
      <form method="post" style="display:flex;gap:10px;margin-top:18px;flex-wrap:wrap">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="lead_id" value="<?= (int) $l['id'] ?>">
        <?php if ((int) $l['crm_ok'] !== 1): ?><button class="lpa-btn gold" name="do_lead" value="retry" <?= lofty_enabled() ? '' : 'disabled title="Switch Lofty on under CRM / Lofty first"' ?>>Send to Lofty<?= $l['crm_ok'] !== null ? ' again' : '' ?></button><?php endif; ?>
        <button class="lpa-btn danger" name="do_lead" value="delete" onclick="return confirm('Delete this lead from the website? It stays in your email and in Lofty.')">Delete lead</button>
      </form>
    </div>
<?php }
