<?php
/**
 * XML sitemap, generated rather than kept by hand.
 *
 * Served at /sitemap.xml by a rewrite in .htaccess, so the URL search engines
 * expect is the URL they get. Every clean route, every published post and every
 * published product is listed, which means adding an article or a guide in the
 * admin puts it in the sitemap with no extra step.
 *
 * Only the short links are left out on purpose: they are 301s to a page that is
 * already here, so listing them would offer search engines two URLs for one page.
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

/* products_all() already drops anything unpublished or without a file, so this
   cannot list a page that 404s. Products carry no edit date, and <lastmod> is
   optional, so it is simply left off. */
foreach (products_all() as $pr) {
    $urls[] = [
        'loc'  => abs_url(product_path($pr['slug'])),
        'pri'  => '0.8',
        'freq' => 'monthly',
        'mod'  => '',
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
