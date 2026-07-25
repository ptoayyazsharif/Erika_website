<?php
/* Pre-generate a reading and put it in the cache, before anyone is watching.
 *
 * Why this exists: Google's free TTS tier only allows a handful of requests
 * per minute, so synthesising a whole reading live gets rate-limited halfway
 * through. This paces the chunks out instead — it takes a couple of minutes,
 * it only has to run once, and afterwards the demo plays instantly from cache.
 *
 * Run it from the browser:  /stella-demo/api/prewarm.php
 * or from the CLI:          php api/prewarm.php
 *
 * POST { profile?, story?, voice? } to warm a specific reading.
 */

require_once __DIR__ . '/lib.php';

$isCli = PHP_SAPI === 'cli';
if (!DEMO_TOOLS && !$isCli) fail('Demo tools are disabled.', 403);

@set_time_limit(0);
ignore_user_abort(true);

/* Erika's answers — mirrors DEMO_PROFILE in js/quiz.js. Keep them in step. */
$DEMO_PROFILE = [
    'name'               => 'Erika',
    'city'               => 'Atlanta',
    'spot'               => 'a little wine shop in Buckhead',
    'partner'            => 'C Arnez B',
    'people'             => 'my daughter Maya, and Tyler and Liam',
    'desire'             => 'financial freedom and a thriving real estate business',
    'area'               => 'money',
    'milestone'          => 'a beachfront villa in Costa Rica',
    'milestone_location' => 'Costa Rica',
    'proof_number'       => '47 agents signed this quarter',
    'work_vision'        => 'an AI recruiting arm and a real estate acquisitions business',
    'success_place'      => 'my home office, laptop open',
    'sensory_detail'     => 'a framed photo from last Thanksgiving',
    'good_news_caller'   => 'my assistant, telling me the new deal video hit 50k views',
    'scarcity_habit'     => 'open three tabs comparing prices before I buy anything',
];

$input = [];
if (!$isCli && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $input = read_json_input();
}

$profile = clean_profile(($input['profile'] ?? []) ?: $DEMO_PROFILE);
$voice   = preg_replace('/[^a-z_]/', '', strtolower((string)($input['voice'] ?? DEFAULT_VOICE)));
$story   = trim((string)($input['story'] ?? ''));
$began   = microtime(true);

/* 1. the words */
if ($story === '') {
    $r = llm_story($profile);
    $story = $r['ok'] ? tidy_story($r['text']) : fallback_story($profile);
    $storySource = $r['ok'] ? active_provider() : 'template';
} else {
    $storySource = 'given';
}

/* 2. the voice, paced so the free tier can keep up */
$engine = tts_provider();
$result = ['url' => null, 'engine' => 'browser', 'note' => 'no_tts_key'];

if ($engine !== 'none' && ensure_audio_dir()) {
    $model = $engine === 'elevenlabs' ? TTS_MODEL : GEMINI_TTS_MODEL;
    $ext   = $engine === 'elevenlabs' ? '.mp3'    : '.wav';
    $hash  = cache_hash($story, $voice, $model);
    $file  = AUDIO_DIR . $hash . $ext;

    if (is_file($file) && filesize($file) > 1024) {
        $result = ['url' => AUDIO_URL . $hash . $ext, 'engine' => $engine, 'note' => 'already_cached'];
    } else {
        $tts = $engine === 'elevenlabs'
            ? elevenlabs_tts(resolve_voice_id($voice), $story)
            : gemini_tts($voice, $story, true);          // paced

        if ($tts['ok'] && @file_put_contents($file, $tts['bytes']) !== false) {
            @chmod($file, 0664);
            $result = [
                'url'    => AUDIO_URL . $hash . $ext,
                'engine' => $engine,
                'note'   => 'generated',
                'bytes'  => strlen($tts['bytes']),
            ];
        } else {
            $result = ['url' => null, 'engine' => 'browser', 'note' => $tts['error'] ?: 'write_failed'];
        }
    }
}

$payload = [
    'ok'      => $result['url'] !== null,
    'story'   => $story,
    'source'  => $storySource,
    'words'   => word_count($story),
    'voice'   => $voice,
    'seconds' => round(microtime(true) - $began, 1),
] + $result;

/* Leave the warmed reading where the landing page can find it, so
   "Play Erika's reading" plays this one instantly — from any browser,
   on any laptop, not just the one that ran the warm-up. */
if ($payload['ok']) {
    @file_put_contents(AUDIO_DIR . 'demo-reading.json', json_encode([
        'story'   => $story,
        'url'     => $result['url'],
        'voice'   => $voice,
        'engine'  => $result['engine'],
        'words'   => $payload['words'],
        'created' => gmdate('c'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

if ($isCli) {
    echo "Manifest pre-warm\n";
    echo str_repeat('-', 46), "\n";
    echo "story   : {$payload['words']} words via {$payload['source']}\n";
    echo "voice   : {$payload['voice']} via {$payload['engine']}\n";
    echo "audio   : ", ($payload['url'] ?? '— (' . $payload['note'] . ')'), "\n";
    echo "took    : {$payload['seconds']}s\n";
    echo str_repeat('-', 46), "\n";
    echo $payload['ok']
        ? "Ready. The demo will play this instantly from cache.\n"
        : "No audio cached — the demo will use the browser voice.\n";
    exit($payload['ok'] ? 0 : 1);
}

json_out($payload);
