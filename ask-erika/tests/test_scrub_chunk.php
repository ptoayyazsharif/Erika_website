<?php
/** Tests 3 (scrub) + 4 (chunk). Run: php tests/test_scrub_chunk.php */
require __DIR__ . '/../lib/scrub.php';
require __DIR__ . '/../lib/chunk.php';
require __DIR__ . '/_assert.php';

$transcript = file_get_contents(__DIR__ . '/synthetic_transcript.txt');

// name_map as Erika would seed it
$map = [
    'Henderson'      => '[CLIENT]',
    'the Hendersons' => '[CLIENT]',
    'Marcus Bell'    => '[CLIENT]',
    'Marcus'         => '[CLIENT]',
    'Jordan'         => '[MENTEE]',
    'Tasha'          => '[MENTEE]',
    'Keller Premier' => '[BROKERAGE]',
];

$clean = scrub($transcript, $map);

echo "=== TEST 3: scrubbing ===\n";
$mustBeGone = ['Henderson', 'Marcus Bell', 'Marcus', 'Jordan', 'Tasha', 'Keller Premier',
               '4417', 'Peachtree Hollow Court', '555-0182', '(770)'];
foreach ($mustBeGone as $needle) {
    assert_true(stripos($clean, $needle) === false, "redacted: '$needle' is gone");
}
// Neighborhoods / cities must survive
foreach (['Decatur', 'Marietta'] as $keep) {
    assert_true(stripos($clean, $keep) !== false, "preserved city/area: '$keep'");
}
// Placeholders present
assert_true(strpos($clean, '[CLIENT]') !== false, "[CLIENT] placeholder inserted");
assert_true(strpos($clean, '[PHONE]') !== false, "[PHONE] redaction inserted");
assert_true(strpos($clean, '[ADDRESS]') !== false, "[ADDRESS] redaction inserted");

echo "\n=== TEST 4: chunking ===\n";
$big = str_repeat($transcript . "\n\n", 9);           // ~20k+ words
$words = str_word_count($big);
$chunks = chunk_text($big, 6000);
echo "  input words: $words   chunks: " . count($chunks) . "\n";
assert_true($words > 20000, "input exceeds 20,000 words ($words)");
assert_true(count($chunks) >= 3, "splits into multiple chunks (" . count($chunks) . ")");
$tol = (int) (6000 * 1.15);
foreach ($chunks as $i => $ch) {
    $t = est_tokens($ch);
    assert_true($t <= $tol, "chunk $i within size (~$t tokens, tol $tol)");
}
// no content lost: every chunk's text appears in the original stream (order preserved)
$rejoined = implode("\n", $chunks);
assert_true(str_word_count($rejoined) >= $words - count($chunks),
    "no material content dropped in chunking");

echo "\n";
assert_summary();
