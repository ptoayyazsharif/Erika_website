<?php
/**
 * Front controller for PHP's built-in server only: `php -S localhost:8000 router.php`
 *
 * The built-in server has no mod_rewrite, so without this every clean URL 404s and
 * local development stops matching the host. This mirrors the two rules in .htaccess:
 * /sitemap.xml is the generated script, real files are served as-is, and everything
 * else goes to index.php. Not used in production.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/sitemap.xml') { require __DIR__ . '/sitemap.php'; return; }

$file = __DIR__ . $path;
if ($path !== '/' && file_exists($file) && !is_dir($file)) return false;

require __DIR__ . '/index.php';
