<?php
/* POST { text, voice } → { url, cached, mode, engine }
   mode: 'file'    — audio is ready at url
         'browser' — no TTS configured; the page should speak it itself */

require_once __DIR__ . '/lib.php';

rate_limit('tts');

$input = read_json_input(1024 * 64);
$text  = trim((string)($input['text'] ?? ''));
$voice = preg_replace('/[^a-z_]/', '', strtolower((string)($input['voice'] ?? DEFAULT_VOICE)));

if ($text === '') fail('Nothing to say.');
if (mb_strlen($text) > MAX_TTS_CHARS) {
    $text = mb_substr($text, 0, MAX_TTS_CHARS);
}

$engine = tts_provider();

if ($engine === 'none') {
    json_out([
        'url'    => null,
        'mode'   => 'browser',
        'engine' => 'browser',
        'cached' => false,
        'note'   => 'no_tts_key',
    ]);
}

$model = $engine === 'elevenlabs' ? TTS_MODEL : GEMINI_TTS_MODEL;
$ext   = $engine === 'elevenlabs' ? '.mp3'    : '.wav';
$hash  = cache_hash($text, $voice, $model);
$file  = AUDIO_DIR . $hash . $ext;

if (is_file($file) && filesize($file) > 1024) {
    json_out(['url' => AUDIO_URL . $hash . $ext, 'cached' => true, 'mode' => 'file', 'engine' => $engine]);
}

if (!ensure_audio_dir()) {
    json_out([
        'url'    => null,
        'mode'   => 'browser',
        'engine' => 'browser',
        'note'   => 'audio_dir_not_writable',   // chmod 775 audio/generated/
    ]);
}

if ($engine === 'elevenlabs') {
    $voiceId = resolve_voice_id($voice);
    $tts = elevenlabs_tts($voiceId, $text);
} else {
    @set_time_limit(HTTP_TIMEOUT + 30);          // chunked synthesis takes longer
    $tts = gemini_tts($voice, $text);
}

if (!$tts['ok']) {
    // Same principle as the story: degrade to the browser voice, keep the demo alive.
    json_out(['url' => null, 'mode' => 'browser', 'engine' => 'browser', 'cached' => false, 'note' => $tts['error']]);
}

$tmp = $file . '.' . bin2hex(random_bytes(4)) . '.part';
if (@file_put_contents($tmp, $tts['bytes']) === false || !@rename($tmp, $file)) {
    @unlink($tmp);
    json_out(['url' => null, 'mode' => 'browser', 'engine' => 'browser', 'cached' => false, 'note' => 'cache_write_failed']);
}
@chmod($file, 0664);

json_out([
    'url'    => AUDIO_URL . $hash . $ext,
    'cached' => false,
    'mode'   => 'file',
    'engine' => $engine,
    'bytes'  => strlen($tts['bytes']),
]);
