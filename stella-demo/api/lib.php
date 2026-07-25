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
    $min  = STORY_MIN_WORDS; $max = STORY_MAX_WORDS;
    // Smaller models consistently undershoot unless the length is restated
    // here, in the turn they actually answer.
    return "{$json}\n\n{$note}\n\nLength: {$min}–{$max} words — six or seven full paragraphs. Do not stop short of {$min} words. Remember the contrast beat.";
}

/* ---------- LLM ---------------------------------------------------------- */

function active_provider(): string {
    $p = LLM_PROVIDER;
    if ($p === 'anthropic' && ANTHROPIC_API_KEY !== '') return 'anthropic';
    if ($p === 'openai'    && OPENAI_API_KEY    !== '') return 'openai';
    if ($p === 'gemini'    && GEMINI_API_KEY    !== '') return 'gemini';
    if ($p === 'auto') {
        if (ANTHROPIC_API_KEY !== '') return 'anthropic';
        if (OPENAI_API_KEY    !== '') return 'openai';
        if (GEMINI_API_KEY    !== '') return 'gemini';
    }
    return 'none';
}

function llm_model_name(string $provider): string {
    return match ($provider) {
        'anthropic' => ANTHROPIC_MODEL,
        'openai'    => OPENAI_MODEL,
        'gemini'    => GEMINI_MODEL,
        default     => '',
    };
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
    if ($provider === 'gemini') {
        return gemini_call($system, $user);
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

function gemini_call(string $system, string $user): array {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode(GEMINI_MODEL) . ':generateContent';
    $r = curl_json($url, [
        'x-goog-api-key: ' . GEMINI_API_KEY,
        'Content-Type: application/json',
    ], [
        'system_instruction' => ['parts' => [['text' => $system]]],
        'contents' => [['role' => 'user', 'parts' => [['text' => $user]]]],
        'generationConfig' => [
            'temperature'     => LLM_TEMPERATURE,
            'maxOutputTokens' => 2400,
        ],
    ]);

    if ($r['body'] === false || $r['code'] !== 200) {
        $msg = 'gemini_http_' . $r['code'];
        if (is_string($r['body'])) {
            $j = json_decode($r['body'], true);
            if (!empty($j['error']['message'])) $msg .= ': ' . mb_substr($j['error']['message'], 0, 160);
        }
        if ($r['curl_error']) $msg .= ' (' . $r['curl_error'] . ')';
        return ['ok' => false, 'text' => '', 'error' => $msg, 'model' => GEMINI_MODEL];
    }

    $data = json_decode($r['body'], true);
    $text = '';
    foreach (($data['candidates'][0]['content']['parts'] ?? []) as $part) {
        $text .= $part['text'] ?? '';
    }
    $text = trim($text);
    if ($text === '') {
        // Usually a safety block, or a thinking model that spent the whole
        // budget before writing anything.
        $why = $data['candidates'][0]['finishReason'] ?? ($data['promptFeedback']['blockReason'] ?? 'empty');
        return ['ok' => false, 'text' => '', 'error' => 'gemini_' . strtolower($why), 'model' => GEMINI_MODEL];
    }
    return ['ok' => true, 'text' => $text, 'error' => '', 'model' => GEMINI_MODEL];
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

/** Which voice engine is actually usable right now. */
function tts_provider(): string {
    $want = TTS_PROVIDER;
    $hasEleven = ELEVENLABS_API_KEY !== '' && resolve_voice_id(DEFAULT_VOICE) !== null;
    $hasGemini = GEMINI_API_KEY !== '';

    if ($want === 'browser')                 return 'none';
    if ($want === 'elevenlabs')              return $hasEleven ? 'elevenlabs' : 'none';
    if ($want === 'gemini')                  return $hasGemini ? 'gemini' : 'none';
    if ($hasEleven)                          return 'elevenlabs';   // auto
    if ($hasGemini)                          return 'gemini';
    return 'none';
}

function resolve_gemini_voice(string $voice): string {
    global $GEMINI_VOICES;
    if (!is_array($GEMINI_VOICES)) return 'Aoede';
    return $GEMINI_VOICES[$voice] ?? ($GEMINI_VOICES[DEFAULT_VOICE] ?? 'Aoede');
}

/* ---------- Gemini speech ------------------------------------------------
   Gemini returns raw 16-bit PCM, one call at a time, and a full reading takes
   over a minute if you ask for it in one go. So the story is cut at paragraph
   boundaries and the chunks are requested in parallel, then stitched back
   together with a beat of silence between them and wrapped in a WAV header.
   Wall time ends up close to the slowest chunk instead of the sum.
   ------------------------------------------------------------------------ */

/** Split into TTS-sized pieces, preferring paragraph then sentence breaks. */
function chunk_for_tts(string $text, ?int $maxChars = null): array {
    $maxChars = $maxChars ?? TTS_CHUNK_CHARS;
    $paras  = preg_split('/\R\s*\R/', trim($text)) ?: [];
    $chunks = [];

    foreach ($paras as $para) {
        $para = trim($para);
        if ($para === '') continue;

        if (mb_strlen($para) <= $maxChars) { $chunks[] = $para; continue; }

        $sentences = preg_split('/(?<=[.!?])\s+/', $para) ?: [$para];
        $buf = '';
        foreach ($sentences as $s) {
            if ($buf !== '' && mb_strlen($buf . ' ' . $s) > $maxChars) { $chunks[] = $buf; $buf = $s; }
            else $buf = $buf === '' ? $s : $buf . ' ' . $s;
        }
        if (trim($buf) !== '') $chunks[] = trim($buf);
    }
    return $chunks;
}

/** Run several POSTs at once. Returns results in the order given. */
function curl_multi_post(array $requests, int $concurrency = 4): array {
    $results = [];
    foreach (array_chunk($requests, max(1, $concurrency), true) as $batch) {
        $mh      = curl_multi_init();
        $handles = [];
        foreach ($batch as $i => $req) {
            $ch = curl_init($req['url']);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => $req['headers'],
                CURLOPT_POSTFIELDS     => json_encode($req['payload'], JSON_UNESCAPED_UNICODE),
                CURLOPT_TIMEOUT        => HTTP_TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => 15,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$i] = $ch;
        }
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) curl_multi_select($mh, 1.0);
        } while ($running && $status === CURLM_OK);

        foreach ($handles as $i => $ch) {
            $results[$i] = [
                'body'       => curl_multi_getcontent($ch),
                'code'       => curl_getinfo($ch, CURLINFO_HTTP_CODE),
                'curl_error' => curl_error($ch),
            ];
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
    }
    ksort($results);
    return $results;
}

/** Configured model first, then the spares — each has its own daily quota. */
function gemini_tts_models(): array {
    global $GEMINI_TTS_FALLBACKS;
    $models = array_merge([GEMINI_TTS_MODEL], is_array($GEMINI_TTS_FALLBACKS) ? $GEMINI_TTS_FALLBACKS : []);
    return array_values(array_unique(array_filter($models)));
}

/** True for "you've spent this model's quota" — worth trying another model. */
function is_quota_error(string $error): bool {
    return str_contains($error, '_429') || stripos($error, 'quota') !== false;
}

function gemini_tts_request(string $voiceName, string $chunk, string $model): array {
    return [
        'url'     => 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent',
        'headers' => ['x-goog-api-key: ' . GEMINI_API_KEY, 'Content-Type: application/json'],
        'payload' => [
            'contents' => [['parts' => [['text' => GEMINI_TTS_STYLE . "\n\n" . $chunk]]]],
            'generationConfig' => [
                'responseModalities' => ['AUDIO'],
                'speechConfig' => [
                    'voiceConfig' => ['prebuiltVoiceConfig' => ['voiceName' => $voiceName]],
                ],
            ],
        ],
    ];
}

/** @return array{pcm:string, rate:int, error:string} */
function gemini_tts_decode(array $r): array {
    if ($r['body'] === false || $r['code'] !== 200) {
        $msg = 'gemini_tts_http_' . $r['code'];
        if (is_string($r['body'])) {
            $j = json_decode($r['body'], true);
            if (!empty($j['error']['message'])) $msg .= ': ' . mb_substr($j['error']['message'], 0, 120);
        }
        return ['pcm' => '', 'rate' => 24000, 'error' => $msg];
    }
    $data = json_decode($r['body'], true);
    $inline = $data['candidates'][0]['content']['parts'][0]['inlineData'] ?? null;
    if (!$inline || empty($inline['data'])) {
        return ['pcm' => '', 'rate' => 24000, 'error' => 'gemini_tts_no_audio'];
    }
    $rate = 24000;
    if (preg_match('/rate=(\d+)/', $inline['mimeType'] ?? '', $m)) $rate = (int)$m[1];
    $pcm = base64_decode($inline['data'], true);
    if ($pcm === false || $pcm === '') return ['pcm' => '', 'rate' => $rate, 'error' => 'gemini_tts_bad_base64'];
    return ['pcm' => $pcm, 'rate' => $rate, 'error' => ''];
}

/** 16-bit mono PCM → a .wav browsers will play. */
function pcm_to_wav(string $pcm, int $rate = 24000, int $channels = 1, int $bits = 16): string {
    $byteRate   = $rate * $channels * ($bits / 8);
    $blockAlign = $channels * ($bits / 8);
    return 'RIFF' . pack('V', 36 + strlen($pcm)) . 'WAVE'
         . 'fmt ' . pack('V', 16) . pack('v', 1) . pack('v', $channels)
         . pack('V', $rate) . pack('V', $byteRate) . pack('v', $blockAlign) . pack('v', $bits)
         . 'data' . pack('V', strlen($pcm)) . $pcm;
}

function silence_pcm(int $rate, float $seconds): string {
    return str_repeat("\x00\x00", max(0, (int)round($rate * $seconds)));
}

/**
 * @param bool $paced  Space the chunk requests out instead of bursting them.
 *                     Slower, but it survives the free tier's per-minute cap.
 * @return array{ok:bool, bytes:string, error:string, chunks:int}
 */
function gemini_tts(string $voice, string $text, bool $paced = false): array {
    $voiceName = resolve_gemini_voice($voice);
    $chunks    = chunk_for_tts($text);
    if (!$chunks) return ['ok' => false, 'bytes' => '', 'error' => 'tts_empty_text', 'chunks' => 0];

    $lastError = 'gemini_tts_failed';

    foreach (gemini_tts_models() as $model) {
        $attempt = gemini_tts_attempt($voiceName, $chunks, $model, $paced);
        if ($attempt['ok']) return $attempt + ['model' => $model];
        $lastError = $attempt['error'];
        // Only worth moving to the next model if this one is out of quota;
        // anything else would fail there too.
        if (!is_quota_error($lastError)) break;
    }

    return ['ok' => false, 'bytes' => '', 'error' => $lastError, 'chunks' => count($chunks), 'model' => ''];
}

/** One full pass over the chunks with a single model. */
function gemini_tts_attempt(string $voiceName, array $chunks, string $model, bool $paced): array {
    $requests = [];
    foreach ($chunks as $i => $chunk) $requests[$i] = gemini_tts_request($voiceName, $chunk, $model);

    if ($paced || count($requests) === 1) {
        $results = [];
        foreach ($requests as $i => $req) {
            if ($i > 0 && $paced && TTS_PACE_MS > 0) usleep(TTS_PACE_MS * 1000);
            $results[$i] = curl_json($req['url'], $req['headers'], $req['payload']);
        }
    } else {
        $results = curl_multi_post($requests, TTS_CONCURRENCY);
    }

    // Parts are kept in slots, never appended — a chunk recovered by the retry
    // below has to land back in its own place or the story plays out of order.
    $parts  = [];
    $rate   = 24000;
    $failed = [];

    foreach ($chunks as $i => $chunk) {
        $d = gemini_tts_decode($results[$i] ?? ['body' => false, 'code' => 0, 'curl_error' => 'missing']);
        if ($d['error'] !== '') { $failed[$i] = $d['error']; continue; }
        $rate      = $d['rate'];
        $parts[$i] = $d['pcm'];
    }

    // Retry the stragglers with backoff — but not when the model is simply out
    // of quota for the day, because waiting will not fix that.
    if ($failed && !is_quota_error((string)reset($failed))) {
        foreach ([600000, 2000000, 4000000] as $wait) {
            if (!$failed) break;
            foreach (array_keys($failed) as $i) {
                usleep($wait);
                $d = gemini_tts_decode(curl_json($requests[$i]['url'], $requests[$i]['headers'], $requests[$i]['payload']));
                if ($d['error'] === '') {
                    $rate      = $d['rate'];
                    $parts[$i] = $d['pcm'];
                    unset($failed[$i]);
                }
            }
        }
    }

    // A missing chunk is a hole in the middle of someone's story. Never cache
    // that — report failure and let the caller fall back to the browser voice.
    if ($failed) {
        return [
            'ok'     => false,
            'bytes'  => '',
            'error'  => count($chunks) === 1
                ? (string)reset($failed)
                : sprintf('gemini_tts_incomplete_%d_of_%d (%s)', count($failed), count($chunks), reset($failed)),
            'chunks' => count($chunks),
        ];
    }

    ksort($parts);
    $pcm = implode(silence_pcm($rate, 0.45), $parts);   // a beat between paragraphs
    return ['ok' => true, 'bytes' => pcm_to_wav($pcm, $rate), 'error' => '', 'chunks' => count($chunks)];
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
