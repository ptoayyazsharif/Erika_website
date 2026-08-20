<?php
/**
 * Responsive image builder.
 *
 * Every picture on the site is displayed in a box far smaller than the file it
 * came from — a 1200x1600 photo dropped into a 174px thumbnail is thirty times
 * more pixels than the screen can show, and on a phone that is the difference
 * between a page that snaps in and one that crawls.
 *
 * This writes scaled-down WebP copies of every picture into
 * assets/rimg/<width>/<original path>.webp. cms_img() picks them up
 * automatically and hands the browser a srcset, so each device downloads the
 * smallest file that still looks sharp. Originals are never modified.
 *
 * Run after adding or changing pictures:   php tools/build-images.php
 * Add --force to rebuild copies that already exist.
 *
 * Pictures uploaded through the admin are converted on upload, so this is only
 * needed for files dropped straight into assets/photos.
 */

require __DIR__ . '/../lib/imgvariants.php';

const SOURCES = ['assets/photos', 'uploads'];

$root  = dirname(__DIR__);
$force = in_array('--force', $argv, true);
$made = $files = 0;
$bytesIn = $bytesOut = 0;

foreach (SOURCES as $dir) {
    $abs = $root . '/' . $dir;
    if (!is_dir($abs)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($abs, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (!$file->isFile()) continue;
        $rel = ltrim(str_replace($root, '', $file->getPathname()), '/');
        if (str_starts_with($rel, IMG_OUT_ROOT)) continue;
        if (!preg_match('/\.(jpe?g|png|webp)$/i', $rel)) continue;

        $n = img_build_variants($root, $rel, $force);
        $files++;
        if ($n > 0) {
            $made += $n;
            $bytesIn += filesize($root . '/' . $rel);
            foreach (glob($root . '/' . IMG_OUT_ROOT . '/*/' . $rel . '.webp') as $copy) $bytesOut += filesize($copy);
            printf("  %-52s %d copies\n", $rel, $n);
        }
    }
}

printf("\n%d pictures scanned, %d copies written\n", $files, $made);
if ($bytesOut > 0) {
    printf("originals rebuilt: %.1f MB -> %.1f MB across every width (%.0f%% smaller per picture on average)\n",
        $bytesIn / 1048576, $bytesOut / 1048576, 100 - ($bytesOut / max(1, $bytesIn) * 100));
}
