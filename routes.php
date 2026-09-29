<?php
/**
 * Clean URL <-> internal page-id map.
 * Home is the site root "/". Every other page has its own readable path,
 * so links are shareable and browser back/forward work with real history.
 *
 * Used by index.php (routing + link building) and submit.php (redirect back).
 */

const ROUTES = [
    // path (no leading slash)      => internal page id
    ''                              => 'home',
    'about'                         => 'about',
    'sell'                          => 'sell',
    'home-value'                    => 'homevalue',
    'buy'                           => 'buy',
    'speaking'                      => 'speaking',
    'collaborations'                => 'collaborations',
    'lifestyle'                     => 'lifestyle',
    'media'                         => 'media',
    'testimonials'                  => 'testimonials',
    'resources'                     => 'resources',
    'digital-products'              => 'products',
    'erika-explains'                => 'explains',
    'mentorship'                    => 'mentorship',
    'investing'                     => 'investing',
    'property-management'           => 'pm',
    'transportation-logistics'      => 'transportation',
    'escaluxe-living'               => 'living',
    'atlanta-metro'                 => 'loc-atlanta',
    'gwinnett-lawrenceville'        => 'loc-gwinnett',
    'fayette-peachtree-city'        => 'loc-fayette',
    'sandy-springs-roswell-alpharetta'=> 'loc-northfulton',
    'east-cobb-marietta'            => 'loc-cobb',
    'brookhaven-decatur-tucker'     => 'loc-dekalb',
    'mcdonough-henry'               => 'loc-henry',
    'blog'                          => 'blog',
    'gallery'                       => 'gallery',
    'contact'                       => 'contact',
];

/** internal id -> clean path beginning with "/" */
function path_for(string $id): string {
    static $flip;
    $flip ??= array_flip(ROUTES);
    if (!isset($flip[$id])) return '/';
    return '/' . $flip[$id];
}

/** request path (e.g. "/home-value") -> internal id, or '' if unknown */
function id_for_path(string $path): string {
    $path = trim(parse_url($path, PHP_URL_PATH) ?? '', '/');
    return ROUTES[$path] ?? '';
}

/**
 * A post URL ("/blog/why-closing-costs-vary") -> its slug, or '' when the path
 * is not a post URL at all.
 *
 * Posts are the one part of the site with no entry in ROUTES: there is an
 * unknown number of them and they are stored rather than coded, so the path is
 * matched by shape here and the slug is looked up against the stored list.
 */
function blog_slug_for_path(string $path): string {
    $path = trim(parse_url($path, PHP_URL_PATH) ?? '', '/');
    return preg_match('#^blog/([a-z0-9][a-z0-9\-]*)$#', $path, $m) ? $m[1] : '';
}

/** slug -> the post's clean path, beginning with "/". */
function post_path(string $slug): string {
    return '/blog/' . $slug;
}
