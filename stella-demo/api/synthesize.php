<?php
/* POST { text, voice } → { url, cached, mode }
   mode: 'file'    — an mp3 is ready at url
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

$voiceId = resolve_voice_id($voice);

if (ELEVENLABS_API_KEY === '' || $voiceId === null) {
    json_out([
        'url'    => null,
        'mode'   => 'browser',
        'cached' => false,
        'note'   => ELEVENLABS_API_KEY === '' ? 'no_tts_key' : 'no_voice_id',
    ]);
}

$hash = cache_hash($text, $voice, TTS_MODEL);
$file = AUDIO_DIR . $hash . '.mp3';

if (is_file($file) && filesize($file) > 1024) {
    json_out(['url' => AUDIO_URL . $hash . '.mp3', 'cached' => true, 'mode' => 'file']);
}

if (!ensure_audio_dir()) {
    json_out([
        'url'  => null,
        'mode' => 'browser',
        'note' => 'audio_dir_not_writable',   // chmod 775 audio/generated/
    ]);
}

$tts = elevenlabs_tts($voiceId, $text);

if (!$tts['ok']) {
    // Same principle as the story: degrade to the browser voice, keep the demo alive.
    json_out(['url' => null, 'mode' => 'browser', 'cached' => false, 'note' => $tts['error']]);
}

$tmp = $file . '.' . bin2hex(random_bytes(4)) . '.part';
if (@file_put_contents($tmp, $tts['bytes']) === false || !@rename($tmp, $file)) {
    @unlink($tmp);
    json_out(['url' => null, 'mode' => 'browser', 'cached' => false, 'note' => 'cache_write_failed']);
}
@chmod($file, 0664);

json_out(['url' => AUDIO_URL . $hash . '.mp3', 'cached' => false, 'mode' => 'file']);
