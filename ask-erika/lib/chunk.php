<?php
/**
 * Transcript splitter. Produces ~N-token chunks, splitting on line/paragraph
 * boundaries so speaker turns stay intact. A single over-long line is hard-split
 * by sentences, then by characters, so no chunk exceeds the target by much.
 * Token estimate: ~4 characters per token (good enough for sizing).
 */

function est_tokens(string $s): int {
    return (int) ceil(mb_strlen($s) / 4);
}

/** @return string[] chunk bodies, in order */
function chunk_text(string $text, int $tokenTarget = 6000): array {
    $text = str_replace(["\r\n", "\r"], "\n", trim($text));
    if ($text === '') return [];

    $hardChars = $tokenTarget * 4;                 // ~token target in chars
    $lines = explode("\n", $text);

    // Break any single line that alone exceeds the target into safe pieces.
    $units = [];
    foreach ($lines as $line) {
        if (mb_strlen($line) <= $hardChars) { $units[] = $line; continue; }
        foreach (split_long($line, $hardChars) as $piece) $units[] = $piece;
    }

    $chunks = [];
    $buf = '';
    foreach ($units as $unit) {
        $candidate = $buf === '' ? $unit : $buf . "\n" . $unit;
        if ($buf !== '' && est_tokens($candidate) > $tokenTarget) {
            $chunks[] = $buf;
            $buf = $unit;
        } else {
            $buf = $candidate;
        }
    }
    if (trim($buf) !== '') $chunks[] = $buf;

    return $chunks;
}

/** Split one very long line by sentences, then by hard character cuts. */
function split_long(string $line, int $maxChars): array {
    $sentences = preg_split('/(?<=[.!?])\s+/', $line) ?: [$line];
    $out = [];
    $buf = '';
    foreach ($sentences as $s) {
        if (mb_strlen($s) > $maxChars) {
            if ($buf !== '') { $out[] = $buf; $buf = ''; }
            for ($i = 0, $len = mb_strlen($s); $i < $len; $i += $maxChars) {
                $out[] = mb_substr($s, $i, $maxChars);
            }
            continue;
        }
        $candidate = $buf === '' ? $s : $buf . ' ' . $s;
        if ($buf !== '' && mb_strlen($candidate) > $maxChars) { $out[] = $buf; $buf = $s; }
        else { $buf = $candidate; }
    }
    if ($buf !== '') $out[] = $buf;
    return $out;
}
