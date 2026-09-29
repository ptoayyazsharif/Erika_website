<?php
/**
 * XML sitemap, generated rather than kept by hand.
 *
 * Served at /sitemap.xml by a rewrite in .htaccess, so the URL search engines
 * expect is the URL they get. Every clean route plus every published post is
 * listed, which means adding an article in the admin puts it in the sitemap
 * with no extra step.
 */
require __DIR__ . '/cms.php';
require __DIR__ . '/routes.php';

/* Pages that are useful to a person but not worth a search result of their own. */
const SITEMAP_SKIP = ['404'];

$posts = blog_posts();
$newestPost = $posts ? $posts[0]['updated'] : '';

$urls = [];
foreach (ROUTES as $path => $id) {
    if (in_array($id, SITEMAP_SKIP, true)) continue;
    $urls[] = [
        'loc'  => abs_url('/' . $path),
        'pri'  => $id === 'home' ? '1.0' : ($id === 'blog' ? '0.8' : '0.7'),
        'freq' => $id === 'blog' ? 'weekly' : 'monthly',
        'mod'  => $id === 'blog' ? $newestPost : '',
    ];
}
foreach ($posts as $p) {
    $urls[] = [
        'loc'  => abs_url(post_path($p['slug'])),
        'pri'  => '0.9',
        'freq' => 'monthly',
        'mod'  => $p['updated'],
    ];
}

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . esc($u['loc']) . "</loc>\n";
    if ($u['mod'] !== '') echo '    <lastmod>' . esc($u['mod']) . "</lastmod>\n";
    echo '    <changefreq>' . $u['freq'] . "</changefreq>\n";
    echo '    <priority>' . $u['pri'] . "</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
