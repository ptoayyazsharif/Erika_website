<?php
/**
 * Copy this file to  lib/config.php  and fill in real values.
 * lib/config.php is git-ignored and lives inside /lib (Require all denied),
 * so it is never web-servable. The API key must never appear anywhere else.
 */
return [
    // --- database ---
    'db' => [
        'host' => 'localhost',
        'name' => 'YOUR_DB_NAME',
        'user' => 'YOUR_DB_USER',
        'pass' => 'YOUR_DB_PASSWORD',
    ],

    // --- Google Gemini API ---
    'gemini_key' => 'YOUR_GEMINI_API_KEY',

    // Primary model + its "-latest" fallback alias, per role.
    // Google retires models often; the wrapper falls back to the alias on 404.
    'models' => [
        'distill' => ['gemini-3.5-flash',      'gemini-flash-latest'],
        'router'  => ['gemini-3.5-flash-lite', 'gemini-flash-lite-latest'],
        'answer'  => ['gemini-3.5-flash-lite', 'gemini-flash-lite-latest'],
    ],

    // --- app settings ---
    'daily_question_limit' => 30,   // per mentee per day
    'chunk_token_target'   => 6000, // approx tokens per distillation chunk
];
