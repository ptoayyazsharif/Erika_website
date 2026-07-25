<?php
/* ============================================================
   Manifest — shared helpers
   cURL, prompting, caching, rate limiting, offline fallback.
   No framework, no dependencies. PHP 8.1+.
   ============================================================ */

require_once __DIR__ . '/config.php';

/* ---------- responses ---------------------------------------------------- */

function json_out(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(string $message, int $status = 400, array $extra = []): void {
    json_out(array_merge(['error' => $message], $extra), $status);
}

/** Read + decode a JSON request body. Rejects anything oversized or malformed. */
function read_json_input(int $maxBytes = 65536): array {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        fail('POST only.', 405);
    }
    $raw = file_get_contents('php://input', false, null, 0, $maxBytes + 1);
    if ($raw === false || $raw === '') fail('Empty request body.');
    if (strlen($raw) > $maxBytes) fail('Request body too large.', 413);
    $data = json_decode($raw, true);
    if (!is_array($data)) fail('Malformed JSON.');
    return $data;
}

/* ---------- rate limiting (file based — no DB on shared hosting) --------- */

function rate_limit(string $bucket): void {
    if (RATE_LIMIT <= 0) return;
    $ip   = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $file = sys_get_temp_dir() . '/manifest_rl_' . md5($bucket . '|' . $ip) . '.json';
    $now  = time();
    $hits = [];
    if (is_readable($file)) {
        $hits = json_decode((string)@file_get_contents($file), true) ?: [];
    }
    $hits = array_values(array_filter($hits, fn($t) => $t > $now - RATE_WINDOW));
    if (count($hits) >= RATE_LIMIT) {
        fail('Slow down a moment — too many requests.', 429);
    }
    $hits[] = $now;
    @file_put_contents($file, json_encode($hits), LOCK_EX);
}

/* ---------- profile handling -------------------------------------------- */

/** The fifteen slots the story is built from. */
function profile_keys(): array {
    return [
        'name','city','spot','partner','people','desire','area','milestone',
        'milestone_location','proof_number','work_vision','success_place',
        'sensory_detail','good_news_caller','scarcity_habit',
    ];
}

/** Trim to known keys, strip control chars, cap length. Never trust the client. */
function clean_profile(array $in): array {
    $out = [];
    foreach (profile_keys() as $k) {
        $v = $in[$k] ?? '';
        if (!is_string($v)) $v = '';
        $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v);
        $v = trim(preg_replace('/\s+/u', ' ', $v));
        if (mb_strlen($v) > MAX_FIELD_LEN) $v = mb_substr($v, 0, MAX_FIELD_LEN);
        $out[$k] = $v;
    }
    return $out;
}

function pv(array $p, string $key, string $fallback = ''): string {
    $v = trim($p[$key] ?? '');
    return $v !== '' ? $v : $fallback;
}

/** Capitalise an answer that has landed at the start of a sentence. */
function up(string $s): string {
    if ($s === '') return $s;
    return mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
}

/* ---------- the prompt (the crown jewel) -------------------------------- */

function story_system_prompt(): string {
    $min = STORY_MIN_WORDS; $max = STORY_MAX_WORDS;
    return <<<SYS
You write a single first-person, present-tense narrative in which the reader is ALREADY living the life they described — it has happened, it is happening now, today. Not a prediction, not a fantasy. A quiet, real morning in their achieved life.

Rules:
- First person, present tense throughout. Never "you will," never "imagine," never "one day."
- Weave the real names, places, and numbers from their profile in naturally, the way a person thinks about their own life. Don't list them — let them surface in an ordinary moment.
- Include one CONTRAST beat: a short reflection on how they used to act from scarcity ("Three years ago, I would have…"), then how effortless it is now. This is the emotional center. Do not skip it.
- Ground everything in small mundane sensory details — a phone buzzing, a glass of wine, a framed photo, a parking text. The ordinariness makes it real.
- Use concrete numbers where given. Counted success feels true; vague abundance feels fake.
- Short, calm cadence suitable for slow narration. No mysticism, no exclamation marks, no advice, no addressing the reader as "you."
- Write in plain paragraphs separated by a blank line. No headings, no lists, no quotation marks around the whole piece, no title.
- {$min}–{$max} words. End on a quiet, grounded, human moment — not a triumphant one.

You'll receive the profile as JSON. Return ONLY the story text.
SYS;
}

/** Tone nudge per life area — small, but it changes the texture a lot. */
function area_note(string $area): string {
    $map = [
        'money'   => 'Tone: steady and unbothered. Money appears as ease and margin, never as bragging.',
        'career'  => 'Tone: competent and calm. Work is craft that finally fits.',
        'love'    => 'Tone: warm and close. Small domestic gestures carry the weight.',
        'home'    => 'Tone: rooted and sensory. The rooms themselves do the talking.',
        'freedom' => 'Tone: spacious and unhurried. Time is the luxury on display.',
        'health'  => 'Tone: physical and clear. The body feels different than it used to.',
    ];
    return $map[strtolower($area)] ?? 'Tone: steady, warm, unhurried.';
}

function story_user_prompt(array $profile): string {
    $json = json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $note = area_note($profile['area'] ?? '');
    return "{$json}\n\n{$note}";
}

/* ---------- LLM ---------------------------------------------------------- */

function active_provider(): string {
    $p = LLM_PROVIDER;
    if ($p === 'anthropic' && ANTHROPIC_API_KEY !== '') return 'anthropic';
    if ($p === 'openai'    && OPENAI_API_KEY    !== '') return 'openai';
    if ($p === 'auto') {
        if (ANTHROPIC_API_KEY !== '') return 'anthropic';
        if (OPENAI_API_KEY    !== '') return 'openai';
    }
    return 'none';
}

/**
 * @return array{ok:bool, text:string, error:string, model:string}
 */
function llm_story(array $profile): array {
    $provider = active_provider();
    $system   = story_system_prompt();
    $user     = story_user_prompt($profile);

    if ($provider === 'anthropic') {
        return anthropic_call($system, $user);
    }
    if ($provider === 'openai') {
        return openai_call($system, $user);
    }
    return ['ok' => false, 'text' => '', 'error' => 'no_api_key', 'model' => ''];
}

function curl_json(string $url, array $headers, array $payload): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT        => HTTP_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    return ['body' => $body, 'code' => $code, 'curl_error' => $err];
}

function anthropic_call(string $system, string $user): array {
    $r = curl_json('https://api.anthropic.com/v1/messages', [
        'x-api-key: ' . ANTHROPIC_API_KEY,
        'anthropic-version: 2023-06-01',
        'Content-Type: application/json',
    ], [
        'model'       => ANTHROPIC_MODEL,
        'max_tokens'  => 1400,
        'temperature' => LLM_TEMPERATURE,
        'system'      => $system,
        'messages'    => [['role' => 'user', 'content' => $user]],
    ]);

    if ($r['body'] === false || $r['code'] !== 200) {
        return ['ok' => false, 'text' => '', 'model' => ANTHROPIC_MODEL,
                'error' => 'anthropic_http_' . $r['code'] . ($r['curl_error'] ? ':' . $r['curl_error'] : '')];
    }
    $data = json_decode($r['body'], true);
    $text = '';
    foreach (($data['content'] ?? []) as $block) {
        if (($block['type'] ?? '') === 'text') $text .= $block['text'];
    }
    $text = trim($text);
    if ($text === '') return ['ok' => false, 'text' => '', 'error' => 'anthropic_empty', 'model' => ANTHROPIC_MODEL];
    return ['ok' => true, 'text' => $text, 'error' => '', 'model' => ANTHROPIC_MODEL];
}

function openai_call(string $system, string $user): array {
    $r = curl_json('https://api.openai.com/v1/chat/completions', [
        'Authorization: Bearer ' . OPENAI_API_KEY,
        'Content-Type: application/json',
    ], [
        'model'       => OPENAI_MODEL,
        'temperature' => LLM_TEMPERATURE,
        'max_tokens'  => 1400,
        'messages'    => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user',   'content' => $user],
        ],
    ]);

    if ($r['body'] === false || $r['code'] !== 200) {
        return ['ok' => false, 'text' => '', 'model' => OPENAI_MODEL,
                'error' => 'openai_http_' . $r['code'] . ($r['curl_error'] ? ':' . $r['curl_error'] : '')];
    }
    $data = json_decode($r['body'], true);
    $text = trim($data['choices'][0]['message']['content'] ?? '');
    if ($text === '') return ['ok' => false, 'text' => '', 'error' => 'openai_empty', 'model' => OPENAI_MODEL];
    return ['ok' => true, 'text' => $text, 'error' => '', 'model' => OPENAI_MODEL];
}

/* ---------- offline fallback story --------------------------------------
   Runs when no LLM key is present (or the API fails mid-demo). Same voice,
   same contrast beat, built from the same fifteen answers. It is a template,
   so it will never surprise you — which is exactly what you want as a
   safety net during a live demo.
   ------------------------------------------------------------------------ */

function fallback_story(array $p): string {
    $name      = pv($p, 'name', 'I');
    $city      = pv($p, 'city', 'this city');
    $spot      = pv($p, 'spot', 'the coffee place on the corner');
    $partner   = pv($p, 'partner');
    $people    = pv($p, 'people');
    $desire    = pv($p, 'desire', 'the life I kept describing to myself');
    $milestone = pv($p, 'milestone', 'the place we said we would buy one day');
    $where     = pv($p, 'milestone_location', $city);
    $number    = pv($p, 'proof_number', 'the number I used to check twice a day');
    $work      = pv($p, 'work_vision', 'work that finally looks like me');
    $place     = pv($p, 'success_place', 'my kitchen, early, before anyone else is up');
    $detail    = pv($p, 'sensory_detail', 'the light coming across the counter');
    $caller    = pv($p, 'good_news_caller', 'someone on my team, with news');
    $habit     = pv($p, 'scarcity_habit', 'check the balance before I let myself want anything');

    $peopleLine = '';
    if ($partner !== '' && $people !== '') {
        $peopleLine = up($partner) . " is still asleep. Later there will be {$people}, and the noise that comes with them.";
    } elseif ($partner !== '') {
        $peopleLine = up($partner) . ' is still asleep down the hall.';
    } elseif ($people !== '') {
        $peopleLine = "Later there will be {$people}, and the noise that comes with them.";
    } else {
        $peopleLine = 'The house is quiet in the way I used to wish it would be.';
    }

    $s = [];

    $s[] = "It is early. I am in {$place}, and the house has that particular quiet it gets before the day decides what it wants from me. I notice {$detail} the way you notice a thing you own, without ceremony. {$peopleLine} I make coffee. I do not check anything on my phone yet, and that alone would have been unthinkable a few years ago.";

    $s[] = "This is the part I want to say plainly: {$desire} is not a plan anymore. It is the address I live at. The work is {$work}, and it runs whether or not I am anxious about it. " . up($number) . ". I know the figure without looking, the way you know your own height.";

    $s[] = "The phone buzzes on the counter: {$caller}. I read it twice, not because I doubt it, but because I like the shape of it. I write back one line. Then I put the phone face down and finish the coffee while it is still hot, which is its own kind of proof.";

    $s[] = "Three years ago, I would have {$habit}. Every single time. I would have run the arithmetic in my head twice before saying yes to anything, and then felt the small tightening in my chest that came with saying it anyway. This morning I did not do that. It did not even occur to me to do it. That is the difference — not the number in the account, but the absence of that reflex.";

    $s[] = "In the fall we go back to {$where}, to {$milestone}, and the strangest part is how ordinary it feels to say so. There is a drawer there with our things in it. Someone has to remember to have the mail held. That is what having it actually looks like: logistics, and a key that lives on my ring next to the others.";

    $spotLine = ($spot !== '') ? "Later I will walk to {$spot}, because I can, because the middle of a Tuesday belongs to me now." : "Later I will walk, because I can, because the middle of a Tuesday belongs to me now.";
    $s[] = "{$spotLine} {$city} looks the way it always looked. I am the thing that changed.";

    $s[] = "I rinse the cup and set it in the rack. There is a whole day out there and none of it is urgent. I stand at the window for another minute, because nobody is timing me, and then I go and start.";

    return implode("\n\n", $s);
}

/* ---------- text hygiene for TTS ---------------------------------------- */

/** Strip anything the model might have wrapped the story in. */
function tidy_story(string $text): string {
    $text = trim($text);
    $text = preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $text);
    $text = preg_replace('/^(here(\'s| is)[^\n]*:|story:)\s*/i', '', $text);
    $text = preg_replace('/\R{3,}/', "\n\n", $text);
    return trim($text);
}

function word_count(string $text): int {
    return str_word_count(strip_tags($text));
}

/* ---------- TTS cache ---------------------------------------------------- */

function cache_hash(string $text, string $voice, string $model): string {
    return hash('sha256', $model . '|' . $voice . '|' . TTS_SPEED . '|' . trim($text));
}

function ensure_audio_dir(): bool {
    if (is_dir(AUDIO_DIR)) return is_writable(AUDIO_DIR);
    return @mkdir(AUDIO_DIR, 0775, true) && is_writable(AUDIO_DIR);
}

function resolve_voice_id(string $voice): ?string {
    global $VOICES;
    if (!is_array($VOICES)) return null;
    $key = array_key_exists($voice, $VOICES) ? $voice : DEFAULT_VOICE;
    $id  = trim((string)($VOICES[$key] ?? ''));
    if ($id === '' || str_starts_with($id, 'REPLACE')) return null;
    return $id;
}

/** ElevenLabs returns raw audio bytes, not JSON. */
function elevenlabs_tts(string $voiceId, string $text): array {
    $ch = curl_init("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'xi-api-key: ' . ELEVENLABS_API_KEY,
            'Content-Type: application/json',
            'Accept: audio/mpeg',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'text'     => $text,
            'model_id' => TTS_MODEL,
            'voice_settings' => [
                'stability'        => TTS_STABILITY,
                'similarity_boost' => TTS_SIMILARITY_BOOST,
                'style'            => TTS_STYLE,
                'speed'            => TTS_SPEED,
                'use_speaker_boost'=> true,
            ],
        ], JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT        => HTTP_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);
    $bytes = curl_exec($ch);
    $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err   = curl_error($ch);
    curl_close($ch);

    if ($bytes === false || $code !== 200) {
        // On error ElevenLabs sends JSON — surface its message, not the bytes.
        $msg = 'tts_http_' . $code;
        if (is_string($bytes)) {
            $j = json_decode($bytes, true);
            $detail = $j['detail']['message'] ?? ($j['detail']['status'] ?? null);
            if ($detail) $msg .= ': ' . (is_string($detail) ? $detail : json_encode($detail));
        }
        if ($err) $msg .= ' (' . $err . ')';
        return ['ok' => false, 'bytes' => '', 'error' => $msg];
    }
    if (strlen($bytes) < 1024) {
        return ['ok' => false, 'bytes' => '', 'error' => 'tts_empty_audio'];
    }
    return ['ok' => true, 'bytes' => $bytes, 'error' => ''];
}
