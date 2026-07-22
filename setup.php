<?php
/**
 * One-time installer: creates the two tables and the admin account.
 * Locks itself once an admin exists — safe to leave on the server,
 * but you can also delete it after installing.
 */
require __DIR__ . '/cms.php';

$err = '';
$done = false;

try {
    db();
} catch (Throwable $e) {
    $err = 'Cannot connect to the database. Edit config.php with your MySQL credentials first. (' . esc($e->getMessage()) . ')';
}

if (!$err && installed()) {
    $done = true; // already installed → show link to admin only
}

if (!$err && !$done && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    $p2 = $_POST['password2'] ?? '';
    if ($u === '' || strlen($u) < 3)          $err = 'Username must be at least 3 characters.';
    elseif (strlen($p) < 8)                   $err = 'Password must be at least 8 characters.';
    elseif ($p !== $p2)                       $err = 'Passwords do not match.';
    else {
        $driver = cfg()['driver'] ?? 'mysql';
        if ($driver === 'sqlite') {
            db()->exec('CREATE TABLE IF NOT EXISTS content (k TEXT PRIMARY KEY, v TEXT)');
            db()->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, pass_hash TEXT)');
            db()->exec('CREATE TABLE IF NOT EXISTS crm_log (id INTEGER PRIMARY KEY AUTOINCREMENT, created TEXT, form TEXT, email TEXT, ok INTEGER, detail TEXT)');
        } else {
            db()->exec('CREATE TABLE IF NOT EXISTS content (k VARCHAR(160) PRIMARY KEY, v MEDIUMTEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            db()->exec('CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(60) UNIQUE, pass_hash VARCHAR(255)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            db()->exec('CREATE TABLE IF NOT EXISTS crm_log (id INT AUTO_INCREMENT PRIMARY KEY, created DATETIME, form VARCHAR(160), email VARCHAR(160), ok TINYINT, detail TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }
        db()->prepare('INSERT INTO users (username, pass_hash) VALUES (?, ?)')
            ->execute([$u, password_hash($p, PASSWORD_DEFAULT)]);
        header('Location: admin.php?installed=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup · ErikaKPage CMS</title>
<style>
body{font-family:system-ui,sans-serif;background:#FBF7F3;color:#453230;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
.card{background:#fff;border:1px solid rgba(69,50,48,.14);border-top:3px solid #C9A15E;padding:36px;max-width:420px;width:92%;box-shadow:0 14px 30px rgba(69,50,48,.1)}
h1{font-size:22px;margin:0 0 6px}p{font-size:14px;color:#5C4B47}
label{display:block;font-size:11px;letter-spacing:.12em;text-transform:uppercase;font-weight:700;margin:16px 0 6px}
input{width:100%;padding:12px;border:1px solid rgba(69,50,48,.2);background:#FBF7F3;font-size:15px;box-sizing:border-box}
button{margin-top:22px;width:100%;padding:14px;background:#B05E51;color:#fff;border:none;font-size:13px;letter-spacing:.12em;text-transform:uppercase;font-weight:600;cursor:pointer}
button:hover{background:#9C4A3E}
.err{background:#FBEAEA;border:1px solid #D9908B;color:#8C3B32;padding:10px 12px;font-size:13px;margin-top:14px}
a{color:#B05E51}
</style>
</head>
<body>
<div class="card">
<?php if ($done): ?>
  <h1>Already installed</h1>
  <p>The CMS is set up. <a href="admin.php">Go to the admin login →</a></p>
<?php else: ?>
  <h1>ErikaKPage CMS setup</h1>
  <p>Create the one admin account. This runs once.</p>
  <?php if ($err): ?><div class="err"><?= esc($err) ?></div><?php endif; ?>
  <?php if (!$err || installed() === false): ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <label>Admin username</label><input name="username" required minlength="3" autocomplete="username">
    <label>Password (min 8 characters)</label><input name="password" type="password" required minlength="8" autocomplete="new-password">
    <label>Repeat password</label><input name="password2" type="password" required minlength="8" autocomplete="new-password">
    <button>Install</button>
  </form>
  <?php endif; ?>
<?php endif; ?>
</div>
</body>
</html>
