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
    foreach (glob(AUDIO_DIR . '*.mp3') ?: [] as $f) {
        if (@unlink($f)) $n++;
    }
    json_out(['cleared' => $n]);
}

$provider = active_provider();
$files    = glob(AUDIO_DIR . '*.mp3') ?: [];
$bytes    = array_sum(array_map(fn($f) => (int)@filesize($f), $files));

$ambience = [];
foreach (['cafe', 'rain', 'waves'] as $bed) {
    $ambience[$bed] = is_file(__DIR__ . "/../audio/ambience/{$bed}.mp3");
}

json_out([
    'ok'            => true,
    'php'           => PHP_VERSION,
    'llm'           => $provider,                                  // anthropic | openai | none
    'llm_model'     => $provider === 'anthropic' ? ANTHROPIC_MODEL : ($provider === 'openai' ? OPENAI_MODEL : null),
    'tts'           => ELEVENLABS_API_KEY !== '' && resolve_voice_id(DEFAULT_VOICE) !== null,
    'tts_model'     => TTS_MODEL,
    'audio_writable'=> ensure_audio_dir(),
    'curl'          => function_exists('curl_init'),
    'cache_files'   => count($files),
    'cache_bytes'   => $bytes,
    'ambience_files'=> $ambience,                                  // false = procedural fallback used
    'demo_tools'    => DEMO_TOOLS,
]);
