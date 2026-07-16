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
