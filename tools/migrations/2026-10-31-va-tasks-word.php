<?php
/**
 * One-off, 31 Oct 2026: on the admin's saved copy of the VA services page, the task option
 * "Household and personal errands" becomes "Household and personal tasks" (Erika, 30 Oct: "Since it's
 * virtual, you can't actually run errands. So change that word to tasks"). Nothing else changes.
 * Uploaded under a random name, run once with its token, deleted at once.
 */
require __DIR__ . '/cms.php';
if (!hash_equals('104a5fc85a3c8d4e45050d4d8d17e75cc683d79f2791dad3ed8beb798a0e9a63', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
header('Content-Type: application/json');
$list = json_decode(setting('landing.pages', ''), true);
if (!is_array($list)) { echo json_encode(['saved' => false]); exit; }
$n = 0;
foreach ($list as $i => $p) {
    if (!is_array($p) || ($p['slug'] ?? '') !== 'va-services') continue;
    foreach ((array) ($p['fields'] ?? []) as $fi => $f) {
        foreach ((array) ($f['options'] ?? []) as $oi => $o) {
            if ($o === 'Household and personal errands') { $list[$i]['fields'][$fi]['options'][$oi] = 'Household and personal tasks'; $n++; }
        }
    }
}
if ($n && empty($_GET['dry'])) landing_save_all(array_values($list));
echo json_encode(['changed' => $n, 'dry' => !empty($_GET['dry'])]);
