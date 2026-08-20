<?php
/**
 * Building the scaled-down WebP copies of a picture.
 *
 * Shared by tools/build-images.php (bulk pass over the photo library) and by
 * handle_upload() in cms.php, so a picture the client uploads from the admin
 * gets exactly the same treatment as one shipped with the site.
 *
 * Copies live at assets/rimg/<width>/<original path>.webp; the original is
 * never touched, and cms_img() falls back to it whenever a copy is missing.
 */

/**
 * Widths to build, and the WebP quality for each. Bigger copies carry more
 * pixels per inch of screen, so they can be compressed harder before anything
 * shows — a phone-sized copy needs the higher setting, a desktop one does not.
 */
const IMG_WIDTHS = [400 => 82, 800 => 79, 1200 => 73];
const IMG_OUT_ROOT = 'assets/rimg';

/**
 * Write the scaled copies for one picture, relative to the site root.
 *
 * Returns the number of files written. Missing GD support, an unreadable file
 * or a read-only directory are all treated as "no copies" — the site still
 * works, it just serves the original.
 */
function img_build_variants(string $root, string $rel, bool $force = false): int {
    if (!function_exists('imagewebp')) return 0;

    $src = $root . '/' . ltrim($rel, '/');
    $info = @getimagesize($src);
    if (!$info) return 0;
    [$w0, $h0] = $info;

    // Never upscale: every step is capped at the picture's own width, and a step
    // that collapses onto one already built is dropped.
    $steps = [];
    foreach (IMG_WIDTHS as $w => $q) $steps[min($w, $w0)] = $q;

    $written = 0;
    foreach ($steps as $w => $quality) {
        $out = sprintf('%s/%s/%d/%s.webp', $root, IMG_OUT_ROOT, $w, ltrim($rel, '/'));
        if (!$force && is_file($out) && filemtime($out) >= filemtime($src)) continue;

        $im = img_load($src, $info['mime']);
        if (!$im) return $written;

        $h = max(1, (int) round($h0 * ($w / $w0)));
        $dst = imagecreatetruecolor($w, $h);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $im, 0, 0, 0, 0, $w, $h, $w0, $h0);
        imagedestroy($im);

        if (!is_dir(dirname($out))) @mkdir(dirname($out), 0775, true);
        if (@imagewebp($dst, $out, $quality)) $written++;
        imagedestroy($dst);
    }
    return $written;
}

function img_load(string $path, string $mime) {
    return match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($path),
        'image/png'  => @imagecreatefrompng($path),
        'image/webp' => @imagecreatefromwebp($path),
        default      => false,
    };
}
