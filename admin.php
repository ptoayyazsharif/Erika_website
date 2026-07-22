<?php
/**
 * Admin dashboard — login, edit content (grouped by page → section), upload images,
 * change password. One file, no framework.
 */
require __DIR__ . '/cms.php';

if (!installed()) { header('Location: setup.php'); exit; }

$flash = '';
$err = '';

/* ---------- logout ---------- */
if (isset($_POST['do_logout'])) {
    csrf_check();
    session_destroy();
    header('Location: admin.php');
    exit;
}

/* ---------- login ---------- */
if (!is_admin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_login'])) {
    csrf_check();
    $row = db()->prepare('SELECT * FROM users WHERE username = ?');
    $row->execute([trim($_POST['username'] ?? '')]);
    $u = $row->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['pass_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $u['id'];
        header('Location: admin.php');
        exit;
    }
    sleep(1); // slow down guessing
    $err = 'Wrong username or password.';
}

/* ---------- login screen ---------- */
if (!is_admin()) { ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log in · ErikaKPage CMS</title>
<style>
body{font-family:system-ui,sans-serif;background:#FBF7F3;color:#453230;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
.card{background:#fff;border:1px solid rgba(69,50,48,.14);border-top:3px solid #C9A15E;padding:36px;max-width:380px;width:92%;box-shadow:0 14px 30px rgba(69,50,48,.1)}
h1{font-size:20px;margin:0 0 4px;font-weight:600}h1 span{color:#B05E51}
p{font-size:13px;color:#5C4B47;margin:0 0 10px}
label{display:block;font-size:11px;letter-spacing:.12em;text-transform:uppercase;font-weight:700;margin:16px 0 6px}
input{width:100%;padding:12px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3;font-size:15px;box-sizing:border-box}
button{margin-top:22px;width:100%;padding:14px;background:#B05E51;color:#fff;border:none;font-size:13px;letter-spacing:.12em;text-transform:uppercase;font-weight:600;cursor:pointer}
button:hover{background:#9C4A3E}
.err{background:#FBEAEA;border:1px solid #D9908B;color:#8C3B32;padding:10px 12px;font-size:13px;margin-top:14px}
.ok{background:#EEF6EC;border:1px solid #9CC493;color:#3E6B36;padding:10px 12px;font-size:13px;margin-top:14px}
</style>
</head>
<body>
<div class="card">
  <h1>Erika<span>K</span>Page · Admin</h1>
  <p>Log in to edit the website's text and pictures.</p>
  <?php if (isset($_GET['installed'])): ?><div class="ok">Setup complete — log in with your new account.</div><?php endif; ?>
  <?php if ($err): ?><div class="err"><?= esc($err) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="do_login" value="1">
    <label>Username</label><input name="username" required autocomplete="username" autofocus>
    <label>Password</label><input name="password" type="password" required autocomplete="current-password">
    <button>Log in</button>
  </form>
</div>
</body>
</html>
<?php exit; }

/* ---------- logged in from here on ---------- */
$pages = manifest();
$pageIds = array_keys($pages);
$cur = $_GET['page'] ?? 'home';
if (!isset($pages[$cur]) && !in_array($cur, ['settings', 'email', 'lofty'], true)) $cur = 'home';

/* ---------- change password ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_password'])) {
    csrf_check();
    $row = db()->prepare('SELECT * FROM users WHERE id = ?');
    $row->execute([$_SESSION['admin_id']]);
    $u = $row->fetch();
    if (!$u || !password_verify($_POST['current'] ?? '', $u['pass_hash'])) $err = 'Current password is wrong.';
    elseif (strlen($_POST['new'] ?? '') < 8)                                $err = 'New password must be at least 8 characters.';
    elseif (($_POST['new'] ?? '') !== ($_POST['new2'] ?? ''))               $err = 'New passwords do not match.';
    else {
        db()->prepare('UPDATE users SET pass_hash = ? WHERE id = ?')
            ->execute([password_hash($_POST['new'], PASSWORD_DEFAULT), $u['id']]);
        $flash = 'Password changed.';
    }
}

/* ---------- save SMTP / email settings ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_smtp'])) {
    csrf_check();
    $keys = ['smtp.host', 'smtp.port', 'smtp.secure', 'smtp.user', 'smtp.from', 'smtp.from_name', 'smtp.to'];
    foreach ($keys as $sk) set_setting($sk, trim((string) ($_POST[str_replace('smtp.', 's_', $sk)] ?? '')));
    // only overwrite the password when a new one is typed (blank leaves the stored one)
    if (($_POST['s_pass'] ?? '') !== '') set_setting('smtp.pass', (string) $_POST['s_pass']);
    $flash = 'Email settings saved.';
    if (($_POST['do_smtp'] ?? '') === 'test') {
        $rcpt = setting('smtp.to');
        [$ok, $mErr] = send_mail(
            'Test email — ErikaKPage CMS',
            '<p>This is a test email from your ErikaKPage website admin. If you can read this, form submissions will be delivered here.</p>',
            'This is a test email from your ErikaKPage website admin. If you can read this, form submissions will be delivered here.'
        );
        if ($ok) $flash = 'Settings saved and a test email was sent to ' . $rcpt . '.';
        else { $err = 'Settings saved, but the test email failed: ' . $mErr; $flash = ''; }
    }
    $cur = 'email';
}

/* ---------- save Lofty CRM settings ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_lofty'])) {
    csrf_check();
    set_setting('lofty.enabled', isset($_POST['lofty_enabled']) ? '1' : '0');
    set_setting('lofty.source_default', trim((string) ($_POST['lofty_source_default'] ?? 'Website')));
    set_setting('lofty.tags_default', trim((string) ($_POST['lofty_tags_default'] ?? 'Website')));
    if (($_POST['lofty_key'] ?? '') !== '') set_setting('lofty.key', trim((string) $_POST['lofty_key']));
    // per-form source/tags/on
    foreach (all_forms() as $f) {
        $fk = $f['key'];
        if (isset($_POST['f_source'][$fk])) set_setting('lofty.form.' . $fk . '.source', trim((string) $_POST['f_source'][$fk]));
        if (isset($_POST['f_tags'][$fk]))   set_setting('lofty.form.' . $fk . '.tags', trim((string) $_POST['f_tags'][$fk]));
        set_setting('lofty.form.' . $fk . '.on', isset($_POST['f_on'][$fk]) ? '1' : '0');
    }
    $flash = 'Lofty settings saved.';
    if (($_POST['do_lofty'] ?? '') === 'test') {
        if (!lofty_enabled()) {
            $err = 'Settings saved, but enable Lofty and enter an API key first to send a test lead.'; $flash = '';
        } else {
            [$lok, $ld] = send_to_lofty([
                'firstName' => 'Website', 'lastName' => 'Test Lead',
                'email' => 'test+website@erikakpage.com', 'phone' => '000-000-0000',
                'source' => setting('lofty.source_default', 'Website'),
                'tags' => ['Website', 'Test'],
                'note' => 'Test lead from the ErikaKPage website admin, sent ' . date('M j, Y g:i a') . '. Safe to delete.',
            ]);
            crm_log_add('Admin test lead', 'test+website@erikakpage.com', $lok, $ld);
            if ($lok) $flash = 'Settings saved and a test lead was sent to Lofty (' . $ld . '). Check your Lofty leads.';
            else { $err = 'Settings saved, but the test lead failed: ' . $ld; $flash = ''; }
        }
    }
    $cur = 'lofty';
}

/* ---------- save content ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_save'])) {
    csrf_check();
    $page = $_POST['do_save'];
    if (isset($pages[$page])) {
        $saved = 0;
        foreach ($pages[$page]['sections'] as $sec) {
            foreach ($sec['fields'] as $f) {
                $k = $f['k'];
                if ($f['t'] === 'image') {
                    // uploaded replacement?
                    if (isset($_FILES['up']['name'][$k]) && $_FILES['up']['error'][$k] !== UPLOAD_ERR_NO_FILE) {
                        $file = [
                            'name' => $_FILES['up']['name'][$k],
                            'tmp_name' => $_FILES['up']['tmp_name'][$k],
                            'error' => $_FILES['up']['error'][$k],
                            'size' => $_FILES['up']['size'][$k],
                        ];
                        [$path, $uerr] = handle_upload($file);
                        if ($uerr) { $err = $f['label'] . ': ' . $uerr; }
                        elseif ($path) { content_set($k, $path); $saved++; }
                    } elseif (!empty($_POST['clear'][$k])) {
                        content_set($k, ''); // back to placeholder
                        content_set($k . '__adj', '');
                        $saved++;
                    }
                    // reposition / zoom adjustment (posX posY zoom)
                    if (isset($_POST['adj'][$k])) {
                        $adj = trim((string) $_POST['adj'][$k]);
                        $currentAdj = setting($k . '__adj', '');
                        if (preg_match('/^\d{1,3}(\.\d+)?\s+\d{1,3}(\.\d+)?\s+\d{1,3}(\.\d+)?$/', $adj)) {
                            $normalized = ($adj === '50 50 100') ? '' : $adj;
                            if ($normalized !== $currentAdj) {
                                content_set($k . '__adj', $normalized);
                                $saved++;
                            }
                        }
                    }
                    continue;
                }
                if (!isset($_POST['f'][$k])) continue;
                $v = (string) $_POST['f'][$k];
                if ($f['t'] === 'rich') $v = unwrap_quill(strip_bad($v));
                if ($v !== cms($k)) { content_set($k, $v); $saved++; }
            }
        }
        if (!$err) $flash = $saved ? "Saved $saved change" . ($saved > 1 ? 's' : '') . '.' : 'No changes to save.';
        $cur = $page;
    }
}

$curTitle = $cur === 'settings' ? 'Settings' : ($cur === 'email' ? 'Email / Forms' : ($cur === 'lofty' ? 'CRM / Lofty' : $pages[$cur]['title']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($curTitle) ?> · ErikaKPage CMS</title>
<link rel="stylesheet" href="assets/quill/quill.snow.css">
<style>
*{box-sizing:border-box}
body{font-family:system-ui,sans-serif;background:#F5EBE4;color:#453230;margin:0}
a{color:#B05E51;text-decoration:none}
.top{background:#3D2B29;color:#FBF7F3;display:flex;align-items:center;justify-content:space-between;padding:12px 20px;position:sticky;top:0;z-index:20}
.top .brand{font-weight:600}.top .brand span{color:#DCC08C}
.top form{margin:0}
.top button,.top a.view{background:none;border:1px solid rgba(251,247,243,.4);color:#FBF7F3;padding:7px 14px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;margin-left:8px}
.top button:hover,.top a.view:hover{border-color:#DCC08C;color:#DCC08C}
.layout{display:grid;grid-template-columns:220px 1fr;min-height:calc(100vh - 54px)}
.side{background:#fff;border-right:1px solid rgba(69,50,48,.12);padding:18px 0}
.side a{display:block;padding:9px 20px;font-size:13.5px;color:#453230;border-left:3px solid transparent}
.side a:hover{background:#FBF7F3}
.side a.on{border-color:#C9A15E;background:#FBF7F3;font-weight:600;color:#9C4A3E}
.side .grp{font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#8a746f;padding:16px 20px 6px}
.main{padding:26px;max-width:900px}
h1{font-size:22px;margin:0 0 4px;font-weight:600}
.hint{font-size:13px;color:#5C4B47;margin:0 0 20px}
.flash{background:#EEF6EC;border:1px solid #9CC493;color:#3E6B36;padding:11px 14px;font-size:14px;margin-bottom:18px}
.err{background:#FBEAEA;border:1px solid #D9908B;color:#8C3B32;padding:11px 14px;font-size:14px;margin-bottom:18px}
details.sec{background:#fff;border:1px solid rgba(69,50,48,.12);margin-bottom:14px}
details.sec>summary{cursor:pointer;list-style:none;padding:15px 18px;font-weight:600;font-size:15px;display:flex;justify-content:space-between;align-items:center}
details.sec>summary::-webkit-details-marker{display:none}
details.sec>summary::after{content:"+";color:#C9A15E;font-size:20px;font-weight:400}
details.sec[open]>summary::after{content:"–"}
details.sec>summary small{color:#8a746f;font-weight:400;font-size:12px;margin-left:10px}
.fields{padding:4px 18px 18px;border-top:1px solid rgba(69,50,48,.08)}
.fld{margin:14px 0}
.fld label{display:block;font-size:11px;letter-spacing:.1em;text-transform:uppercase;font-weight:700;color:#6b5550;margin-bottom:6px}
.fld input[type=text]{width:100%;padding:11px 12px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3;font-size:14.5px}
.fld input[type=text]:focus{outline:2px solid #C9A15E}
.rte-box{background:#FBF7F3;border:1px solid rgba(69,50,48,.2)}
.rte-box .ql-toolbar{border:none;border-bottom:1px solid rgba(69,50,48,.15)}
.rte-box .ql-container{border:none;font-size:14.5px;font-family:system-ui,sans-serif;min-height:70px}
.imgrow{display:flex;gap:14px;align-items:center;flex-wrap:wrap}
.imgrow .thumb{width:84px;height:64px;object-fit:cover;border:1px solid rgba(69,50,48,.2);background:linear-gradient(145deg,#EBD2CA,#D49388);display:flex;align-items:center;justify-content:center;font-size:9px;color:#7a5c55;text-align:center}
.imgrow input[type=file]{font-size:13px}
.imgrow .rm{font-size:12.5px;display:flex;gap:6px;align-items:center;color:#8C3B32}
.adjust{margin-top:12px;max-width:340px}
.adjust-head{font-size:11.5px;color:#8a746f;margin-bottom:8px}
.adjust-stage{position:relative;width:100%;aspect-ratio:4/3;overflow:hidden;border:1px solid rgba(69,50,48,.2);background:#EBD2CA;cursor:grab;touch-action:none}
.adjust-stage.grabbing{cursor:grabbing}
.adjust-media{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;user-select:none;-webkit-user-drag:none;pointer-events:none}
.adjust-ctrls{display:flex;align-items:center;gap:10px;margin-top:8px;font-size:12px;color:#6b5550}
.adjust-ctrls .zoom{flex:1;accent-color:#B05E51}
.adjust-ctrls .reset{background:none;border:1px solid rgba(69,50,48,.25);color:#6b5550;font-size:11px;padding:5px 10px;cursor:pointer;text-transform:none;letter-spacing:0}
.adjust-ctrls .reset:hover{border-color:#B05E51;color:#9C4A3E}
.savebar{position:sticky;bottom:0;background:#fff;border:1px solid rgba(69,50,48,.12);padding:12px 18px;display:flex;gap:12px;align-items:center;box-shadow:0 -8px 24px rgba(69,50,48,.07)}
.savebar button{background:#B05E51;color:#fff;border:none;padding:13px 30px;font-size:13px;letter-spacing:.1em;text-transform:uppercase;font-weight:600;cursor:pointer}
.savebar button:hover{background:#9C4A3E}
.savebar span{font-size:12.5px;color:#8a746f}
.setcard{background:#fff;border:1px solid rgba(69,50,48,.12);padding:22px;max-width:420px}
@media(max-width:800px){.layout{grid-template-columns:1fr}.side{display:flex;overflow-x:auto;padding:0}.side a{white-space:nowrap;border-left:none;border-bottom:3px solid transparent}.side .grp{display:none}}
</style>
</head>
<body>
<div class="top">
  <div class="brand">Erika<span>K</span>Page · CMS</div>
  <div style="display:flex;align-items:center">
    <a class="view" href="index.php" target="_blank">View site</a>
    <form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><button name="do_logout" value="1">Log out</button></form>
  </div>
</div>
<div class="layout">
  <nav class="side">
    <div class="grp">Pages</div>
    <?php foreach ($pages as $pid => $p): ?>
      <a href="admin.php?page=<?= esc($pid) ?>" class="<?= $pid === $cur ? 'on' : '' ?>"><?= esc($p['title']) ?></a>
    <?php endforeach; ?>
    <div class="grp">Account</div>
    <a href="admin.php?page=email" class="<?= $cur === 'email' ? 'on' : '' ?>">Email / Forms</a>
    <a href="admin.php?page=lofty" class="<?= $cur === 'lofty' ? 'on' : '' ?>">CRM / Lofty</a>
    <a href="admin.php?page=settings" class="<?= $cur === 'settings' ? 'on' : '' ?>">Settings</a>
  </nav>

  <main class="main">
    <?php if ($flash): ?><div class="flash"><?= esc($flash) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="err"><?= esc($err) ?></div><?php endif; ?>

    <?php if ($cur === 'settings'): ?>
      <h1>Settings</h1>
      <p class="hint">Change your admin password.</p>
      <div class="setcard">
        <form method="post">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <div class="fld"><label>Current password</label><input type="text" style="display:none"><input name="current" type="password" autocomplete="current-password" style="width:100%;padding:11px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3"></div>
          <div class="fld"><label>New password (min 8)</label><input name="new" type="password" autocomplete="new-password" style="width:100%;padding:11px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3"></div>
          <div class="fld"><label>Repeat new password</label><input name="new2" type="password" autocomplete="new-password" style="width:100%;padding:11px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3"></div>
          <div class="savebar" style="position:static;box-shadow:none;border:none;padding:0"><button name="do_password" value="1">Change password</button></div>
        </form>
      </div>

    <?php elseif ($cur === 'email'): ?>
      <h1>Email / Forms</h1>
      <p class="hint">All website forms (seller, home value, speaking, mentorship, contact, and the rest) are emailed to the recipient below using these SMTP settings. Ask your email/host provider for the SMTP details.</p>
      <div class="setcard" style="max-width:560px">
        <form method="post">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <div class="fld"><label>Send all form submissions to</label><input type="text" name="s_to" value="<?= esc(setting('smtp.to')) ?>" placeholder="you@yourdomain.com" style="width:100%;padding:11px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3"></div>
          <div style="height:1px;background:rgba(69,50,48,.1);margin:20px 0"></div>
          <div class="fld"><label>SMTP host</label><input type="text" name="s_host" value="<?= esc(setting('smtp.host')) ?>" placeholder="smtp.yourhost.com" style="width:100%;padding:11px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3"></div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="fld"><label>Port</label><input type="text" name="s_port" value="<?= esc(setting('smtp.port', '587')) ?>" placeholder="587" style="width:100%;padding:11px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3"></div>
            <div class="fld"><label>Security</label>
              <select name="s_secure" style="width:100%;padding:11px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3">
                <?php $sec = setting('smtp.secure', 'tls'); foreach (['tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => 'None'] as $val => $lbl): ?>
                  <option value="<?= $val ?>" <?= $sec === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
              </select></div>
          </div>
          <div class="fld"><label>SMTP username</label><input type="text" name="s_user" value="<?= esc(setting('smtp.user')) ?>" placeholder="usually your email address" autocomplete="off" style="width:100%;padding:11px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3"></div>
          <div class="fld"><label>SMTP password <?= setting('smtp.pass') !== '' ? '<span style="color:#3E6B36;font-weight:400;text-transform:none;letter-spacing:0">(saved — leave blank to keep)</span>' : '' ?></label><input type="password" name="s_pass" value="" placeholder="<?= setting('smtp.pass') !== '' ? '••••••••' : '' ?>" autocomplete="new-password" style="width:100%;padding:11px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3"></div>
          <div style="height:1px;background:rgba(69,50,48,.1);margin:20px 0"></div>
          <div class="fld"><label>From email (shown as sender)</label><input type="text" name="s_from" value="<?= esc(setting('smtp.from')) ?>" placeholder="noreply@yourdomain.com" style="width:100%;padding:11px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3"></div>
          <div class="fld"><label>From name</label><input type="text" name="s_from_name" value="<?= esc(setting('smtp.from_name', 'ErikaKPage Website')) ?>" style="width:100%;padding:11px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3"></div>
          <div class="savebar" style="position:static;box-shadow:none;border:none;padding:0;gap:10px">
            <button name="do_smtp" value="save">Save settings</button>
            <button name="do_smtp" value="test" style="background:#8F6B2E">Save &amp; send test</button>
          </div>
        </form>
      </div>

    <?php elseif ($cur === 'lofty'): ?>
      <h1>CRM / Lofty</h1>
      <p class="hint">When a website form is submitted it's emailed to you <b>and</b> added to Lofty as a lead — with the person's details, tags, and a note containing everything they entered. Get your API key in Lofty under <b>Settings → Integrations → API</b>, paste it below, and turn this on.</p>
      <div class="setcard" style="max-width:720px">
        <form method="post">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <label class="rm" style="font-size:14px;color:#453230;margin-bottom:16px"><input type="checkbox" name="lofty_enabled" value="1" <?= setting('lofty.enabled') === '1' ? 'checked' : '' ?>> &nbsp;Send website leads to Lofty</label>
          <div class="fld"><label>Lofty API key <?= setting('lofty.key') !== '' ? '<span style="color:#3E6B36;font-weight:400;text-transform:none;letter-spacing:0">(saved — leave blank to keep)</span>' : '' ?></label><input type="password" name="lofty_key" value="" placeholder="<?= setting('lofty.key') !== '' ? '••••••••••••' : 'paste your Lofty API key' ?>" autocomplete="off" style="width:100%;padding:11px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3"></div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="fld"><label>Default source</label><input type="text" name="lofty_source_default" value="<?= esc(setting('lofty.source_default', 'Website')) ?>" style="width:100%;padding:11px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3"></div>
            <div class="fld"><label>Default tags (comma-separated)</label><input type="text" name="lofty_tags_default" value="<?= esc(setting('lofty.tags_default', 'Website')) ?>" style="width:100%;padding:11px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3"></div>
          </div>

          <p class="hint" style="margin:22px 0 8px"><b>Per-form source &amp; tags</b> — customize how each form's leads are labeled in Lofty. Uncheck a form to keep it email-only.</p>
          <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:13px">
              <thead><tr style="text-align:left;color:#8a746f">
                <th style="padding:6px 8px 6px 0;font-weight:600">On</th>
                <th style="padding:6px 8px;font-weight:600">Form</th>
                <th style="padding:6px 8px;font-weight:600">Source</th>
                <th style="padding:6px 8px;font-weight:600">Tags (comma-separated)</th>
              </tr></thead>
              <tbody>
              <?php foreach (all_forms() as $f): $fk = $f['key']; $map = lofty_mapping($f['page'], $f['name']); ?>
                <tr style="border-top:1px solid rgba(69,50,48,.1)">
                  <td style="padding:8px 8px 8px 0"><input type="checkbox" name="f_on[<?= esc($fk) ?>]" value="1" <?= $map['on'] ? 'checked' : '' ?>></td>
                  <td style="padding:8px 8px;white-space:nowrap"><?= esc($f['name']) ?><br><span style="color:#9a8a86;font-size:11px"><?= esc($f['page']) ?></span></td>
                  <td style="padding:8px 8px"><input type="text" name="f_source[<?= esc($fk) ?>]" value="<?= esc($map['source']) ?>" style="width:150px;padding:8px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3"></td>
                  <td style="padding:8px 8px"><input type="text" name="f_tags[<?= esc($fk) ?>]" value="<?= esc(implode(', ', $map['tags'])) ?>" style="width:220px;padding:8px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3"></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="savebar" style="position:static;box-shadow:none;border:none;padding:0;gap:10px;margin-top:20px">
            <button name="do_lofty" value="save">Save settings</button>
            <button name="do_lofty" value="test" style="background:#8F6B2E">Save &amp; send test lead</button>
          </div>
        </form>
      </div>

      <?php $log = crm_log_recent(25); if ($log): ?>
      <div class="setcard" style="max-width:720px;margin-top:22px">
        <h3 style="margin:0 0 10px;font-size:15px">Recent CRM syncs</h3>
        <table style="width:100%;border-collapse:collapse;font-size:12.5px">
          <?php foreach ($log as $r): ?>
          <tr style="border-top:1px solid rgba(69,50,48,.08)">
            <td style="padding:6px 8px;white-space:nowrap;color:#8a746f"><?= esc($r['created']) ?></td>
            <td style="padding:6px 8px"><?= esc($r['form']) ?></td>
            <td style="padding:6px 8px;color:#8a746f"><?= esc($r['email']) ?></td>
            <td style="padding:6px 8px;white-space:nowrap"><?= $r['ok'] ? '<span style="color:#3E6B36">✓ synced</span>' : '<span style="color:#8C3B32">✗ failed</span>' ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <?php endif; ?>

    <?php else: ?>
      <h1><?= esc($pages[$cur]['title']) ?></h1>
      <p class="hint">Open a section, edit the text or replace pictures, then hit <b>Save</b>. Leaving a picture empty shows the designed placeholder.</p>

      <form method="post" enctype="multipart/form-data" id="pageform">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <?php $si = 0; foreach ($pages[$cur]['sections'] as $sec): $si++; ?>
        <details class="sec" <?= $si === 1 ? 'open' : '' ?>>
          <summary><?= esc($sec['title']) ?><small><?= count($sec['fields']) ?> field<?= count($sec['fields']) > 1 ? 's' : '' ?></small></summary>
          <div class="fields">
            <?php foreach ($sec['fields'] as $f): $k = $f['k']; $v = cms($k); $id = 'x' . substr(md5($k), 0, 10); ?>
              <div class="fld">
                <label><?= esc($f['label']) ?></label>
                <?php if ($f['t'] === 'image'): $adj = setting($k . '__adj', '50 50 100'); $ap = preg_split('/\s+/', $adj); ?>
                  <div class="imgrow">
                    <?php if ($v !== ''): $ext = strtolower(pathinfo($v, PATHINFO_EXTENSION)); ?>
                      <?php if (in_array($ext, ['mp4','webm'])): ?><video class="thumb" src="<?= esc($v) ?>" muted></video>
                      <?php else: ?><img class="thumb" src="<?= esc($v) ?>" alt=""><?php endif; ?>
                    <?php else: ?><div class="thumb">placeholder</div><?php endif; ?>
                    <input type="file" name="up[<?= esc($k) ?>]" accept=".jpg,.jpeg,.png,.webp,.gif,.mp4,.webm">
                    <?php if ($v !== ''): ?><label class="rm"><input type="checkbox" name="clear[<?= esc($k) ?>]" value="1"> remove (back to placeholder)</label><?php endif; ?>
                  </div>
                  <?php if ($v !== ''): $ext = strtolower(pathinfo($v, PATHINFO_EXTENSION)); ?>
                  <div class="adjust" data-key="<?= esc($k) ?>">
                    <div class="adjust-head">Adjust — drag the picture to choose what shows, then zoom.</div>
                    <div class="adjust-stage">
                      <?php if (in_array($ext, ['mp4','webm'])): ?>
                        <video class="adjust-media" src="<?= esc($v) ?>" muted autoplay loop playsinline></video>
                      <?php else: ?>
                        <img class="adjust-media" src="<?= esc($v) ?>" alt="">
                      <?php endif; ?>
                    </div>
                    <div class="adjust-ctrls">
                      <span>Zoom</span>
                      <input type="range" class="zoom" min="100" max="300" step="1" value="<?= esc($ap[2] ?? '100') ?>">
                      <button type="button" class="reset">Reset</button>
                    </div>
                    <input type="hidden" name="adj[<?= esc($k) ?>]" class="adjval" value="<?= esc($adj) ?>">
                  </div>
                  <?php endif; ?>
                <?php elseif ($f['t'] === 'rich'): ?>
                  <div class="rte-box"><div class="rte" id="<?= $id ?>"><?= strip_bad($v) ?></div></div>
                  <input type="hidden" name="f[<?= esc($k) ?>]" id="in_<?= $id ?>">
                <?php else: ?>
                  <input type="text" name="f[<?= esc($k) ?>]" value="<?= esc($v) ?>">
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </details>
        <?php endforeach; ?>
        <div class="savebar">
          <button name="do_save" value="<?= esc($cur) ?>">Save <?= esc($pages[$cur]['title']) ?></button>
          <span>Saves every section on this page at once.</span>
        </div>
      </form>
    <?php endif; ?>
  </main>
</div>

<script src="assets/quill/quill.js"></script>
<script>
(function(){
  var editors={};
  function initIn(scope){
    scope.querySelectorAll('.rte').forEach(function(el){
      if(editors[el.id])return;
      editors[el.id]=new Quill(el,{theme:'snow',modules:{toolbar:[['bold','italic','underline'],['link'],['clean']]}});
    });
  }
  document.querySelectorAll('details.sec').forEach(function(d){
    if(d.open)initIn(d);
    d.addEventListener('toggle',function(){ if(d.open)initIn(d); });
  });
  var form=document.getElementById('pageform');
  if(form)form.addEventListener('submit',function(){
    // open all sections so hidden inputs of never-opened Quill fields keep their original values
    document.querySelectorAll('.rte').forEach(function(el){
      var input=document.getElementById('in_'+el.id);
      if(!input)return;
      if(editors[el.id])input.value=editors[el.id].root.innerHTML;
      else input.value=el.innerHTML; // untouched section — submit original content unchanged
    });
  });

  /* ---------- image reposition + zoom ---------- */
  document.querySelectorAll('.adjust').forEach(function(box){
    var media=box.querySelector('.adjust-media'),
        stage=box.querySelector('.adjust-stage'),
        zoom=box.querySelector('.zoom'),
        reset=box.querySelector('.reset'),
        hidden=box.querySelector('.adjval');
    var parts=(hidden.value||'50 50 100').split(/\s+/);
    var posX=clamp(parseFloat(parts[0]),0,100), posY=clamp(parseFloat(parts[1]),0,100), z=clamp(parseFloat(parts[2]),100,300);
    function clamp(n,a,b){n=isNaN(n)?a:n;return Math.max(a,Math.min(b,n));}
    function apply(){
      media.style.objectPosition=posX+'% '+posY+'%';
      media.style.transform='scale('+(z/100)+')';
      media.style.transformOrigin=posX+'% '+posY+'%';
      hidden.value=Math.round(posX)+' '+Math.round(posY)+' '+Math.round(z);
    }
    apply();
    zoom.addEventListener('input',function(){z=parseFloat(zoom.value);apply();});
    reset.addEventListener('click',function(){posX=50;posY=50;z=100;zoom.value=100;apply();});
    var dragging=false,lastX,lastY;
    stage.addEventListener('pointerdown',function(e){dragging=true;lastX=e.clientX;lastY=e.clientY;stage.classList.add('grabbing');stage.setPointerCapture(e.pointerId);});
    stage.addEventListener('pointermove',function(e){
      if(!dragging)return;
      var r=stage.getBoundingClientRect();
      // dragging right should reveal the left of the image -> position decreases
      posX=clamp(posX-(e.clientX-lastX)/r.width*100,0,100);
      posY=clamp(posY-(e.clientY-lastY)/r.height*100,0,100);
      lastX=e.clientX;lastY=e.clientY;apply();
    });
    function end(){dragging=false;stage.classList.remove('grabbing');}
    stage.addEventListener('pointerup',end);
    stage.addEventListener('pointercancel',end);
  });
})();
</script>
</body>
</html>
