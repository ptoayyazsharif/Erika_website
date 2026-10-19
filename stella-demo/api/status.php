<?php
/* GET  → what's configured, what's writable (the pre-demo doctor)
   POST { action: "clear-cache" } → wipes the generated mp3s (DEMO_TOOLS only) */

require_once __DIR__ . '/lib.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    if (!DEMO_TOOLS) fail('Demo tools are disabled.', 403);
    $in = read_json_input(2048);
    if (($in['action'] ?? '') !== 'clear-cache') fail('Unknown action.');
    $n = 0;
    foreach (glob(AUDIO_DIR . '*.{mp3,wav}', GLOB_BRACE) ?: [] as $f) {
        if (@unlink($f)) $n++;
    }
    json_out(['cleared' => $n]);
}

$provider = active_provider();
$engine   = tts_provider();
$files    = glob(AUDIO_DIR . '*.{mp3,wav}', GLOB_BRACE) ?: [];
$bytes    = array_sum(array_map(fn($f) => (int)@filesize($f), $files));

$ambience = [];
foreach (['cafe', 'rain', 'waves'] as $bed) {
    $ambience[$bed] = is_file(__DIR__ . "/../audio/ambience/{$bed}.mp3");
}

json_out([
    'ok'            => true,
    'php'           => PHP_VERSION,
    'llm'           => $provider,                                  // anthropic | openai | gemini | none
    'llm_model'     => llm_model_name($provider) ?: null,
    'tts'           => $engine !== 'none',
    'tts_engine'    => $engine,                                    // elevenlabs | gemini | none
    'tts_model'     => $engine === 'gemini' ? GEMINI_TTS_MODEL : ($engine === 'elevenlabs' ? TTS_MODEL : null),
    'audio_writable'=> ensure_audio_dir(),
    'curl'          => function_exists('curl_init'),
    'cache_files'   => count($files),
    'cache_bytes'   => $bytes,
    'ambience_files'=> $ambience,                                  // false = procedural fallback used
    'demo_ready'    => is_file(AUDIO_DIR . 'demo-reading.json'),   // php api/prewarm.php
    'demo_tools'    => DEMO_TOOLS,
]);
