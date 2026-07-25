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

/* Opened in a browser? Show a page, not a wall of JSON. Add ?json=1 for the
   raw payload (that's what the debug panel asks for). */
$wantsHtml = !$isCli
          && !isset($_GET['json'])
          && str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html');

if ($wantsHtml) {
    $ok      = $payload['ok'];
    $quota   = str_contains((string)($payload['note'] ?? ''), '429')
            || stripos((string)($payload['note'] ?? ''), 'quota') !== false;
    $esc     = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $warmed  = is_file(AUDIO_DIR . 'demo-reading.json');

    header('Content-Type: text/html; charset=utf-8');
    ?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pre-warm — Manifest</title>
<style>
  :root{color-scheme:dark}
  body{margin:0;padding:6vh 6vw;background:linear-gradient(168deg,#0B0E23,#2A1733);
       color:#F5F0E8;font:16px/1.6 system-ui,-apple-system,sans-serif;min-height:100vh}
  .wrap{max-width:44rem;margin:0 auto}
  h1{font:300 clamp(28px,5vw,42px)/1.1 Georgia,serif;margin:0 0 6px}
  .tag{font-size:11px;letter-spacing:.24em;text-transform:uppercase;color:#C9A961;margin-bottom:22px}
  .card{background:rgba(245,240,232,.05);border:1px solid rgba(245,240,232,.14);
        border-radius:16px;padding:24px;margin:24px 0}
  .ok{color:#9BD3A4}.warn{color:#E8C98A}
  dl{display:grid;grid-template-columns:auto 1fr;gap:8px 18px;margin:0;font-size:14.5px}
  dt{color:rgba(245,240,232,.5)}
  a.btn{display:inline-block;margin-top:8px;padding:15px 30px;border-radius:999px;
        background:linear-gradient(122deg,#C9A961,#E2C68B);color:#1C1406;text-decoration:none;
        font-size:13px;letter-spacing:.14em;text-transform:uppercase;font-weight:600}
  a.plain{color:#E2C68B}
  pre{white-space:pre-wrap;font:14px/1.7 Georgia,serif;color:rgba(245,240,232,.8);
      max-height:220px;overflow:auto;margin:0}
  p{max-width:36rem}
</style></head><body><div class="wrap">
  <p class="tag">Manifest &middot; behind the scenes</p>
  <h1><?= $ok ? 'Reading is warmed and cached.' : ($quota ? 'Voice quota is spent for today.' : 'Warm-up did not finish.') ?></h1>

  <p><strong>This page is not the app.</strong> It's the prep script that
  generates a reading ahead of time so the demo starts instantly.</p>

  <p><a class="btn" href="../index.html">Open the app &rarr;</a></p>

  <div class="card">
    <dl>
      <dt>Story</dt><dd class="ok"><?= $esc($payload['words']) ?> words via <?= $esc($payload['source']) ?></dd>
      <dt>Voice</dt><dd class="<?= $ok ? 'ok' : 'warn' ?>"><?= $ok ? $esc($payload['engine']) . ' &mdash; cached' : 'browser voice (' . $esc($payload['note'] ?? 'unavailable') . ')' ?></dd>
      <dt>Took</dt><dd><?= $esc($payload['seconds']) ?>s</dd>
      <dt>Demo ready</dt><dd class="<?= $warmed ? 'ok' : 'warn' ?>"><?= $warmed ? 'yes — "Play Erika\'s reading" uses the cached narration' : 'not yet' ?></dd>
    </dl>
  </div>

  <?php if (!$ok && $quota): ?>
  <p class="warn">Google's free tier allows about ten narrations a day, and
  they're used up. <?= $warmed
      ? 'The reading already cached on this server still plays perfectly — nothing was lost by running this.'
      : 'Until the quota resets (midnight US Pacific), readings are narrated by the browser voice.' ?></p>
  <?php endif; ?>

  <div class="card"><pre><?= $esc($payload['story']) ?></pre></div>
  <p><a class="plain" href="status.php">Check configuration</a> &middot;
     <a class="plain" href="?json=1">Raw JSON</a></p>
</div></body></html><?php
    exit;
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
