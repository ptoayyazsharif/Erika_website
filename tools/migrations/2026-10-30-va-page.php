<?php
/**
 * One-off, 30 Oct 2026: bring the admin's saved copy of the VA services page up to Erika's 30 Oct
 * changes (ceo_erika business/va-company-separate): the description, the link-preview picture (a VA,
 * not her), the consent line, and the "who" and "tasks" options. Everything else the admin set, the
 * Published state included, is kept. The admin's saved copy wins over landing-pages.php, which is why
 * this was needed. It was uploaded to the site root, run once with its token, and deleted at once.
 */
require __DIR__ . '/cms.php';
if (!hash_equals('2284868baea138577201c63b3b4f36e9bf02eb20818ab0943a90fe2e11d239f8', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
header('Content-Type: application/json');
$raw = setting('landing.pages', '');
$list = $raw !== '' ? json_decode($raw, true) : null;
if (!is_array($list)) { echo json_encode(['saved' => false, 'note' => 'no saved copy: the defaults apply']); exit; }
$seed = null;
foreach (landing_seed() as $p) if (($p['slug'] ?? '') === 'va-services') $seed = landing_clean($p);
$done = [];
foreach ($list as $i => $p) {
    if (!is_array($p) || ($p['slug'] ?? '') !== 'va-services') continue;
    $before = $p;
    foreach (['desc', 'image', 'image_alt', 'consent'] as $k) $p[$k] = $seed[$k];
    foreach ((array) ($p['fields'] ?? []) as $fi => $f) {
        foreach ($seed['fields'] as $sf) {
            if (($sf['id'] ?? '') === ($f['id'] ?? '') || (($sf['label'] ?? '') === ($f['label'] ?? '') && ($sf['type'] ?? '') === ($f['type'] ?? ''))) {
                if (in_array($sf['id'], ['who', 'tasks'], true)) $p['fields'][$fi]['options'] = $sf['options'];
            }
        }
    }
    $p['updated'] = '2026-10-30';
    $list[$i] = $p;
    $changed = [];
    foreach ($p as $k => $v) if (($before[$k] ?? null) !== $v) $changed[] = $k;
    $done = ['found' => true, 'published' => !empty($p['published']), 'layout' => $p['layout'] ?? '', 'changed' => $changed];
}
if (!$done) { echo json_encode(['saved' => true, 'found' => false]); exit; }
if (empty($_GET['dry'])) landing_save_all(array_values($list));
echo json_encode($done + ['dry' => !empty($_GET['dry'])]);
