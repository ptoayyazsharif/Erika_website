<?php
/**
 * Deterministic privacy scrub. ALWAYS runs before any AI call.
 * Layer 1: name dictionary (name_map) — case-insensitive replace.
 * Layer 2: regex redaction of emails, phone numbers, and street addresses.
 * City / neighborhood names are intentionally preserved (they carry meaning).
 */
require_once __DIR__ . '/db.php';

/** Load the name map as [real_name => placeholder]. */
function scrub_load_map(): array {
    $map = [];
    foreach (db()->query('SELECT real_name, placeholder FROM name_map') as $r) {
        $map[$r['real_name']] = $r['placeholder'];
    }
    return $map;
}

/** Apply the name dictionary. Longest names first so "John Smith" beats "John". */
function scrub_names(string $text, array $map): string {
    if (!$map) return $text;
    $names = array_keys($map);
    usort($names, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
    foreach ($names as $name) {
        if ($name === '') continue;
        $text = str_ireplace($name, $map[$name], $text);
    }
    return $text;
}

/** Redact emails, phones, and street addresses (keeps city/neighborhood). */
function scrub_regex(string $text): string {
    // Emails
    $text = preg_replace('/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/i', '[EMAIL]', $text);

    // Phone numbers: optional +1, area code (paren or not), 7-digit body
    $text = preg_replace('/(?<!\d)(\+?1[\s.\-]?)?\(?\d{3}\)?[\s.\-]?\d{3}[\s.\-]?\d{4}(?!\d)/', '[PHONE]', $text);

    // Street addresses: number + up to 4 words + a street suffix (+ optional unit)
    $suffix = 'Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Drive|Dr|Lane|Ln|Court|Ct|Way|Place|Pl|Terrace|Ter|Circle|Cir|Trail|Trl|Parkway|Pkwy|Highway|Hwy|Loop|Run|Path|Hollow|Cove|Point|Pt';
    $text = preg_replace(
        '/\b\d{1,6}\s+(?:[A-Z][A-Za-z\'\.]+\s+){1,4}(?:' . $suffix . ')\b\.?(?:\s+(?:Apt|Unit|Suite|Ste|#)\s*\.?\s*\w+)?/',
        '[ADDRESS]',
        $text
    );

    return $text;
}

/**
 * Full scrub. If $map is null the name_map is loaded from the DB.
 * Tests inject an explicit map so they can run without a database.
 */
function scrub(string $text, ?array $map = null): string {
    if ($map === null) $map = scrub_load_map();
    $text = scrub_names($text, $map);
    $text = scrub_regex($text);
    return $text;
}
