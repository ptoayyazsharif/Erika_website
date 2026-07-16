<?php
/**
 * Database configuration.
 * On your host (cPanel → MySQL Databases): create a database and a user,
 * grant the user ALL privileges on the database, then fill in the values below.
 *
 * For local development you can create a config.local.php returning the same
 * array shape — it overrides these values and is ignored by git.
 */

$cfg = [
    'driver' => 'mysql',                    // 'mysql' (production) or 'sqlite' (local testing)
    'host'   => 'localhost',
    'name'   => 'YOUR_DATABASE_NAME',
    'user'   => 'YOUR_DATABASE_USER',
    'pass'   => 'YOUR_DATABASE_PASSWORD',
    'sqlite_path' => __DIR__ . '/cms.sqlite', // used only when driver = sqlite
];

if (file_exists(__DIR__ . '/config.local.php')) {
    $cfg = array_merge($cfg, require __DIR__ . '/config.local.php');
}

return $cfg;
