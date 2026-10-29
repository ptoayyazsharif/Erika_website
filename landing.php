<?php
/**
 * The public side of a landing page: /page/<address>, /page/<address>/thanks
 * and /page/<address>/download.
 *
 * index.php hands over here (with $lp = [slug, action] from landing_route())
 * before it builds the main site, because a landing page is its own small
 * document: the site's palette and type, one job, no menu to wander off into.
 *
 *   GET  /page/<address>            the page and its form
 *   POST /page/<address>            check the answers; keep the lead, email it,
 *                                   send it to Lofty; then 303 to /thanks
 *   GET  /page/<address>/thanks     the download button (needs the signed link)
 *   GET  /page/<address>/download   the file itself (needs the signed link)
 *   GET  /page/<address>/paid       back from Stripe Checkout (paid pages; lib/stripe.php)
 *
 * Drafts are shown to a logged-in admin only, with a banner.
 */

if (!isset($lp)) { http_response_code(404); exit; }   // only ever run from index.php
[$lpSlug, $lpAction] = $lp;
$lpAdmin = is_admin();
$page = landing_one($lpSlug, $lpAdmin);
if ($page && !$lpAdmin && !landing_live($page)) $page = null;

header('Cache-Control: private, no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

if (!$page) {
    http_response_code(404);
    lp_document(null, 'Page not found', function () { ?>
      <div class="lp-wrap lp-404">
        <h1>This page isn't available.</h1>
        <p>The link may have changed, or the offer has ended.</p>
        <a class="lp-back" href="/">Go to erikakpage.com</a>
      </div>
    <?php });
    exit;
}

$isDraft = !landing_live($page);
$token = (string) ($_GET['t'] ?? '');

/* ---------- the file ---------- */
if ($lpAction === 'download') {
    $ok = landing_token_check($token, $page['slug']);
    if (!$ok) { header('Location: ' . landing_path($page['slug']) . '?expired=1', true, 303); exit; }
    if ($page['deliver'] === 'url' && $page['url'] !== '') {
        lead_downloaded($ok['lead']);
        header('Location: ' . $page['url'], true, 303);
        exit;
    }
    $abs = $page['file'] !== '' ? landing_abs($page['file']) : '';
    if ($abs === '' || !is_file($abs)) {
        http_response_code(410);
        lp_document($page, 'File unavailable', function () use ($page) { ?>
          <div class="lp-wrap lp-404">
            <h1>The file isn't available right now.</h1>
            <p>Please get in touch and we'll send it to you.</p>
            <a class="lp-back" href="/contact">Contact Erika</a>
          </div>
        <?php });
        exit;
    }
    lead_downloaded($ok['lead']);
    $name = $page['file_name'] !== '' ? $page['file_name'] : basename($abs);
    $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
    if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== $ext) $name .= '.' . $ext;
    $ascii = preg_replace('/[^A-Za-z0-9 ._()\-]/', '', $name) ?: ('download.' . $ext);
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: ' . ((new finfo(FILEINFO_MIME_TYPE))->file($abs) ?: 'application/octet-stream'));
    header('Content-Length: ' . filesize($abs));
    header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($name));
    header('Cache-Control: private, no-store');
    readfile($abs);
    exit;
}

/* ---------- back from Stripe Checkout ----------
 * The session id in the address is only a reference: the session itself is
 * fetched from Stripe, and only Stripe's answer decides whether it was paid. */
if ($lpAction === 'paid') {
    $sid = (string) ($_GET['session_id'] ?? '');
    $sess = null;
    if (preg_match('/^cs_[A-Za-z0-9_]+$/', $sid)) {
        [$sok, , $sdata] = stripe_http('GET', '/v1/checkout/sessions/' . $sid);
        if ($sok && (string) ($sdata['metadata']['page'] ?? '') === $page['slug']) $sess = $sdata;
    }
    if (!$sess) { header('Location: ' . landing_path($page['slug']) . '?expired=1#form', true, 303); exit; }
    $r = stripe_complete($sess);
    if ($r['ok']) {
        header('Location: ' . landing_path($page['slug'], 'thanks') . '?t=' . rawurlencode($r['token']) . ($r['emailed'] || !$r['first'] ? '&e=1' : ''), true, 303);
        exit;
    }
    if (($sess['status'] ?? '') !== 'complete') { header('Location: ' . landing_path($page['slug']) . '?cancelled=1#form', true, 303); exit; }
    // Complete but not yet paid: a bank payment that takes a few days. The webhook finishes it.
    lp_document($page, 'Payment processing', function () use ($page, $isDraft) {
        lp_intro($page, $isDraft); ?>
        <aside class="lp-card lp-done" aria-live="polite">
          <h2>Thank you. Your payment is processing.</h2>
          <div class="lp-rule" style="margin:16px auto 18px"></div>
          <p>Some payment methods take a little while to clear. As soon as it does, we'll email you the link.</p>
          <a class="lp-back" href="/">Visit erikakpage.com</a>
        </aside>
    <?php });
    exit;
}

/* ---------- after the form ---------- */
if ($lpAction === 'thanks') {
    $ok = landing_token_check($token, $page['slug']);
    if (!$ok) { header('Location: ' . landing_path($page['slug']) . '?expired=1', true, 303); exit; }
    $dl = landing_path($page['slug'], 'download') . '?t=' . rawurlencode($token);
    $size = ($page['deliver'] === 'file' && $page['file'] !== '' && is_file(landing_abs($page['file']))) ? filesize(landing_abs($page['file'])) : 0;
    $emailed = isset($_GET['e']);
    $lead = lead_get((int) $ok['lead']);
    $pay = $lead ? (array) ($lead['meta']['pay'] ?? []) : [];
    $paid = ($pay['state'] ?? '') === 'paid';
    lp_document($page, $page['thanks_title'], function () use ($page, $dl, $size, $emailed, $isDraft, $lead, $pay, $paid) {
        lp_intro($page, $isDraft); ?>
        <aside class="lp-card lp-done" aria-live="polite">
          <div class="lp-tick" aria-hidden="true"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></div>
          <h2><?= esc($page['thanks_title']) ?></h2>
          <div class="lp-rule" style="margin:16px auto 18px"></div>
          <?php if ($paid): ?><p class="lp-paid">Payment received: <b><?= esc(stripe_price_label((int) ($pay['amount'] ?? 0), (string) ($pay['billing'] ?? 'once'))) ?></b> · <span style="white-space:nowrap">Order <?= esc(stripe_order_no((int) $lead['id'])) ?></span><?= empty($pay['live']) ? ' · <span style="white-space:nowrap">test mode</span>' : '' ?></p><?php endif; ?>
          <?php if ($page['thanks_text'] !== ''): ?><p><?= esc($page['thanks_text']) ?></p><?php endif; ?>
          <p style="margin-top:22px"><a class="lp-btn lp-btn-gold" href="<?= esc($dl) ?>" id="lp-download"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m0 0-5-5m5 5 5-5M5 21h14"/></svg><?= $page['deliver'] === 'url' ? 'Open it now' : 'Download now' ?></a></p>
          <?php if ($page['deliver'] === 'file' && $page['file_name'] !== ''): ?>
            <div class="lp-file"><?= esc($page['file_name']) ?><?= $size ? ' · ' . esc(landing_size($size)) : '' ?></div>
          <?php endif; ?>
          <p class="lp-note"><?= $emailed ? ($paid ? 'Your receipt and this link are in your email. ' : 'We\'ve also emailed you this link. ') : '' ?>The link works for <?= LANDING_LINK_DAYS ?> days.</p>
          <a class="lp-back" href="/">Visit erikakpage.com</a>
        </aside>
    <?php });
    exit;
}

/* ---------- the form ---------- */
$vals = ['name' => '', 'email' => ''];
$errs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $t = (int) ($_POST['_t'] ?? 0);
    // A bot fills the hidden box or answers in under two seconds. It gets a
    // page that looks like success and nothing is kept.
    if (!empty($_POST['website']) || ($t > 0 && time() - $t < 2)) {
        header('Location: ' . landing_path($page['slug']) . '?sent=1', true, 303);
        exit;
    }
    [$vals, $errs] = landing_validate($page, $_POST);
    if (!$errs && !$lpAdmin && leads_recent_from(lead_ip_hash(), 3600) >= LANDING_RATE_PER_HOUR) {
        $errs['_'] = 'We\'ve had a lot of requests from your connection. Please try again in an hour, or call us.';
    }
    if (!$errs && $page['price_cents'] > 0) {
        // A paid page: keep the lead as "payment pending", then off to Stripe.
        if (!stripe_ready()) $errs['_'] = 'Payments aren\'t switched on for this page yet. Please get in touch instead.';
        else {
            $res = landing_capture($page, $vals, ['state' => 'pending', 'amount' => $page['price_cents'], 'billing' => $page['billing'], 'started' => date('Y-m-d H:i:s')]);
            [$go, $perr] = $res['id'] > 0 ? stripe_checkout($page, $res['id'], $vals['email']) : ['', 'the lead could not be saved'];
            if ($go !== '') { header('Location: ' . $go, true, 303); exit; }
            if ($res['id'] > 0) stripe_lead_pay($res['id'], ['state' => 'failed', 'error' => mb_substr($perr, 0, 300)]);
            $errs['_'] = 'We couldn\'t open the secure payment page just now. Please try again in a minute.';
        }
    }
    if (!$errs) {
        $res = landing_capture($page, $vals);
        header('Location: ' . landing_path($page['slug'], 'thanks') . '?t=' . rawurlencode($res['token']) . ($res['emailed'] ? '&e=1' : ''), true, 303);
        exit;
    }
    http_response_code(422);
}

lp_document($page, $page['title'], function () use ($page, $vals, $errs, $isDraft) {
    lp_intro($page, $isDraft); ?>
    <aside class="lp-card" id="form">
      <?php if ($page['bottom_title'] !== ''): ?><h2><?= esc($page['bottom_title']) ?></h2><div class="lp-rule"></div><?php endif; ?>
      <?php if (isset($_GET['expired'])): ?><div class="lp-errors">That download link has expired or isn't valid. Fill in the form again for a fresh one.</div><?php endif; ?>
      <?php if (isset($_GET['cancelled'])): ?><div class="lp-errors" style="background:#F7F2EF;border-color:rgba(69,50,48,.2);color:#6b5550">Payment cancelled. Nothing was charged; you can try again below.</div><?php endif; ?>
      <?php if (isset($_GET['sent'])): ?><div class="lp-errors" style="background:#EEF6EC;border-color:#9CC493;color:#3E6B36">Thank you, your request has been received.</div><?php endif; ?>
      <?php if ($errs): ?><div class="lp-errors" role="alert"><?= isset($errs['_']) ? esc($errs['_']) : 'Please check the ' . (count($errs) === 1 ? 'answer' : count($errs) . ' answers') . ' marked below.' ?></div><?php endif; ?>
      <form method="post" action="<?= esc(landing_path($page['slug'])) ?>" id="lp-form">
        <?= lp_text_field('name', 'Name', 'text', $vals['name'] ?? '', $errs['name'] ?? null, 'name', true) ?>
        <?= lp_text_field('email', 'Email', 'email', $vals['email'] ?? '', $errs['email'] ?? null, 'email', true) ?>
        <?php foreach ($page['fields'] as $f) echo lp_question($f, $vals[$f['id']] ?? ($f['type'] === 'checkbox' ? [] : ''), $errs[$f['id']] ?? null); ?>
        <div class="lp-hp" aria-hidden="true"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <input type="hidden" name="_t" value="<?= time() ?>">
        <?php if ($page['price_cents'] > 0): ?>
          <div class="lp-total"><span>Total</span><span class="lp-dots" aria-hidden="true"></span><b><?= esc(stripe_price_label($page['price_cents'], $page['billing'])) ?></b></div>
          <?php if ($page['billing'] !== 'once'): ?><p class="lp-billing">Billed every <?= $page['billing'] === 'month' ? 'month' : 'year' ?> until you cancel.</p><?php endif; ?>
        <?php endif; ?>
        <button class="lp-btn" type="submit" id="lp-send"><?= esc($page['cta']) ?></button>
        <?php if ($page['price_cents'] > 0): ?><p class="lp-secure"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg> Secure payment by Stripe. You'll get the download right after.</p><?php endif; ?>
        <?php if ($page['consent'] !== ''): ?><p class="lp-consent"><?= esc($page['consent']) ?></p><?php endif; ?>
      </form>
    </aside>
    <script>
    (function(){
      var f=document.getElementById('lp-form'),b=document.getElementById('lp-send');
      f.addEventListener('submit',function(){ setTimeout(function(){ b.disabled=true; b.textContent=<?= $page['price_cents'] > 0 ? "'Opening secure checkout…'" : "'Sending…'" ?>; },0); });
      var bad=f.querySelector('.lp-bad .lp-input, .lp-bad input');
      if(bad){ bad.focus({preventScroll:true}); bad.closest('.lp-field').scrollIntoView({block:'center'}); }
    })();
    </script>
<?php });

/* ---------- pieces ---------- */

/** Name and email: the two questions every page asks. */
function lp_text_field(string $name, string $label, string $type, string $val, ?string $err, string $auto, bool $req): string {
    $id = 'lp-' . $name;
    return '<div class="lp-field' . ($err ? ' lp-bad' : '') . '"><label class="lp-label" for="' . $id . '">' . esc($label) . ($req ? '<span class="lp-req" aria-hidden="true">*</span>' : '') . '</label>'
        . '<input class="lp-input" id="' . $id . '" name="' . $name . '" type="' . $type . '" value="' . esc($val) . '" autocomplete="' . $auto . '"' . ($req ? ' required' : '')
        . ($err ? ' aria-invalid="true" aria-describedby="' . $id . '-err"' : '') . ' maxlength="190">'
        . ($err ? '<span class="lp-msg" id="' . $id . '-err">' . esc($err) . '</span>' : '') . '</div>';
}

/** One of the page's own questions. */
function lp_question(array $f, $val, ?string $err): string {
    $id = 'lp-' . $f['id'];
    $name = 'a[' . $f['id'] . ']';
    $req = $f['required'];
    $star = $req ? '<span class="lp-req" aria-hidden="true">*</span>' : '';
    $aria = $err ? ' aria-invalid="true" aria-describedby="' . $id . '-err"' : '';
    $msg = $err ? '<span class="lp-msg" id="' . $id . '-err">' . esc($err) . '</span>' : '';
    $cls = 'lp-field' . ($err ? ' lp-bad' : '');

    if ($f['type'] === 'checkbox') {
        $picked = (array) $val;
        $single = count($f['options']) === 1;
        $h = '<fieldset class="' . $cls . '"><legend class="lp-label">' . esc($f['label']) . $star . '</legend>';
        foreach ($f['options'] as $i => $o) {
            $h .= '<label class="lp-check"><input type="checkbox" name="' . $name . '[]" value="' . esc($o) . '"'
                . (in_array($o, $picked, true) ? ' checked' : '') . ($req && $single ? ' required' : '') . ($i === 0 ? $aria : '') . '><span>' . esc($o) . '</span></label>';
        }
        return $h . $msg . '</fieldset>';
    }
    $h = '<div class="' . $cls . '"><label class="lp-label" for="' . $id . '">' . esc($f['label']) . $star . '</label>';
    if ($f['type'] === 'select') {
        $h .= '<select class="lp-input" id="' . $id . '" name="' . $name . '"' . ($req ? ' required' : '') . $aria . '><option value="">Choose one</option>';
        foreach ($f['options'] as $o) $h .= '<option' . ($val === $o ? ' selected' : '') . '>' . esc($o) . '</option>';
        $h .= '</select>';
    } elseif ($f['type'] === 'textarea') {
        $h .= '<textarea class="lp-input" id="' . $id . '" name="' . $name . '" maxlength="4000"' . ($req ? ' required' : '') . $aria . '>' . esc((string) $val) . '</textarea>';
    } else {
        $phone = $f['type'] === 'phone';
        $h .= '<input class="lp-input" id="' . $id . '" name="' . $name . '" type="' . ($phone ? 'tel' : 'text') . '" value="' . esc((string) $val) . '"'
            . ($phone ? ' autocomplete="tel" inputmode="tel"' : '') . ' maxlength="500"' . ($req ? ' required' : '') . $aria . '>';
    }
    return $h . $msg . '</div>';
}

/** The left column: picture, title, description. */
function lp_intro(array $page, bool $isDraft): void { ?>
    <section class="lp-intro">
      <?php if ($page['image'] !== ''): ?>
        <?php // The WebP ladder, not the upload itself: 1039 Mariners Ct's picture is a
              // 1.86 MB PNG, and its 1200-wide copy is 56 KB (29 Oct 2026).
              $hv = img_variants($page['image']); ?>
        <figure class="lp-hero"><img src="<?= esc(asset_url($page['image'])) ?>"<?= $hv['srcset'] !== '' ? ' srcset="' . $hv['srcset'] . '" sizes="(max-width: 900px) 100vw, 620px"' : '' ?> alt="<?= esc($page['image_alt'] ?: $page['title']) ?>" width="<?= $hv['w'] ?: 1600 ?>" height="<?= $hv['h'] ?: 900 ?>" fetchpriority="high"></figure>
      <?php endif; ?>
      <div class="lp-eyebrow">Erika K. Page · <?= esc($page['brand']) ?></div>
      <h1 class="lp-title"><?= esc($page['title']) ?></h1>
      <div class="lp-rule"></div>
      <div class="lp-desc"><?= $page['desc'] ?></div>
    </section>
<?php }

/** The whole document around a page's content. */
function lp_document(?array $page, string $title, callable $body): void {
    $isDraft = $page && !landing_live($page);
    $index = $page && $page['indexable'] && !$isDraft;
    $desc = $page ? mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(['<br>', '</p>'], [' ', ' '], $page['desc'])))), 0, 155) : '';
    $url = $page ? abs_url(landing_path($page['slug'])) : '';
    // Facebook fetched the 1.86 MB original 368 times in October for link previews;
    // a 1200-wide JPEG copy, built once, does the same job.
    $img = '';
    if ($page && $page['image'] !== '') {
        require_once __DIR__ . '/lib/imgvariants.php';
        $img = abs_url(asset_url(img_share_copy(__DIR__, $page['image']) ?: $page['image']));
    }
    $phone = cms('global.phone');
    $css = '/assets/css/landing.css?v=' . @filemtime(__DIR__ . '/assets/css/landing.css');
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title) ?> · Erika Page</title>
<?php if ($desc !== ''): ?><meta name="description" content="<?= esc($desc) ?>"><?php endif; ?>
<meta name="robots" content="<?= $index ? 'index, follow' : 'noindex, nofollow' ?>">
<?php if ($url !== ''): ?><link rel="canonical" href="<?= esc($url) ?>">
<meta property="og:type" content="website"><meta property="og:url" content="<?= esc($url) ?>"><meta property="og:title" content="<?= esc($page['title']) ?>">
<?php if ($desc !== ''): ?><meta property="og:description" content="<?= esc($desc) ?>"><?php endif; ?>
<?php if ($img !== ''): ?><meta property="og:image" content="<?= esc($img) ?>"><meta name="twitter:card" content="summary_large_image"><?php endif; ?>
<?php endif; ?>
<link href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='6' fill='%23453230'/%3E%3Ctext x='16' y='22' font-family='Georgia,serif' font-size='17' font-weight='600' text-anchor='middle' fill='%23C9A15E'%3EE%3C/text%3E%3C/svg%3E" rel="icon" type="image/svg+xml">
<link href="https://fonts.googleapis.com" rel="preconnect"><link crossorigin href="https://fonts.gstatic.com" rel="preconnect">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&amp;family=Archivo:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
<link href="<?= esc($css) ?>" rel="stylesheet">
</head>
<body class="lp">
<header class="lp-top"><div class="lp-wrap">
  <a class="lp-brand" href="/">Erika<span>K</span>Page</a>
  <?php if ($phone !== ''): ?><a class="lp-phone" href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= esc($phone) ?></a><?php endif; ?>
</div></header>
<?php if ($isDraft): ?><div class="lp-draft"><b>Draft</b> · only you can see this page, because you're logged in to the admin.<?= $page && !landing_deliverable($page) ? ' It has no file to hand over yet.' : '' ?><?= $page && !landing_payable($page) ? ' It has a price, but Stripe isn\'t set up yet (Payments / Stripe).' : '' ?> <a href="/admin.php?page=landing-pages&amp;edit=<?= esc(rawurlencode($page['slug'])) ?>" style="text-decoration:underline">Edit it</a></div><?php endif; ?>
<main class="lp-main"><?php if ($page): ?><div class="lp-wrap lp-grid"><?php endif; ?>
<?php $body(); ?>
<?php if ($page): ?></div><?php endif; ?></main>
<footer class="lp-foot"><div class="lp-wrap">
  <span><b>Erika K. Page</b> · Axen Realty<?= $phone !== '' ? ' · ' . esc($phone) : '' ?></span>
  <span><?= cms_e('global.footer.bottom2') ?></span>
</div></footer>
</body>
</html>
<?php }
