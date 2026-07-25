<?php
/* ============================================================
   Manifest — configuration
   THIS IS THE ONLY FILE YOU NEED TO EDIT TO GO LIVE.
   Everything below works with empty keys (demo fallback mode),
   so you can upload first and add keys later.

   Keys can also live in an untracked api/config.local.php, which
   is loaded first and wins over everything here. That is where a
   key belongs if this folder is in a git repository.
   ============================================================ */


if (is_file(__DIR__ . '/config.local.php')) require_once __DIR__ . '/config.local.php';
// ===== API KEYS — FILL IN =====================================
// Leave LLM_PROVIDER as 'auto' and it uses whichever key is filled
// (preference order: anthropic → openai → gemini).
// Force it with 'anthropic', 'openai' or 'gemini'.
defined('LLM_PROVIDER') or define('LLM_PROVIDER',      'auto');

defined('ANTHROPIC_API_KEY') or define('ANTHROPIC_API_KEY', '');
defined('ANTHROPIC_MODEL') or define('ANTHROPIC_MODEL',   'claude-sonnet-5');

defined('OPENAI_API_KEY') or define('OPENAI_API_KEY',    '');
defined('OPENAI_MODEL') or define('OPENAI_MODEL',      'gpt-4o-mini');

// Google AI Studio (free tier). One key covers BOTH the story and the voice.
defined('GEMINI_API_KEY') or define('GEMINI_API_KEY',    '');
defined('GEMINI_MODEL') or define('GEMINI_MODEL',      'gemini-3.5-flash-lite');   // ~3s for a full reading, no thinking overhead
defined('GEMINI_TTS_MODEL') or define('GEMINI_TTS_MODEL',  'gemini-2.5-flash-preview-tts');

defined('ELEVENLABS_API_KEY') or define('ELEVENLABS_API_KEY', '');
defined('TTS_MODEL') or define('TTS_MODEL',          'eleven_flash_v2_5');

// Which voice engine to use: 'auto' prefers ElevenLabs, falls back to Gemini,
// then to the browser. Force it with 'elevenlabs', 'gemini' or 'browser'.
defined('TTS_PROVIDER') or define('TTS_PROVIDER', 'auto');

// ===== ElevenLabs voices ======================================
// Paste voice IDs from https://elevenlabs.io/app/voice-library
// Defaults below are stock ElevenLabs voices — replace with the
// ones you actually audition. The voice is half the product.
$VOICES = $VOICES ?? [
  'calm'      => 'EXAVITQu4vr4xnSDxMaL', // Sarah  — soft, unhurried
  'warm'      => 'XrExE9yKIg1WjnnlVkGX', // Matilda — warm, close-mic
  'confident' => 'pFZP5JQG7iQjIQuC4Bku', // Lily   — clear, grounded
];
defined('DEFAULT_VOICE') or define('DEFAULT_VOICE', 'calm');

// Gemini's prebuilt voices, same three slots. Full list:
// https://ai.google.dev/gemini-api/docs/speech-generation
$GEMINI_VOICES = $GEMINI_VOICES ?? [
  'calm'      => 'Aoede',      // breathy, unhurried
  'warm'      => 'Sulafat',    // warm, close
  'confident' => 'Kore',       // firm, grounded
];
// Prepended to every TTS chunk — this is how you direct Gemini's delivery.
// Google's free tier allows 10 TTS requests PER DAY PER MODEL, so a whole
// reading goes in ONE request whenever it fits — chunking would spend five
// days' worth of quota on a single story. Chunks only kick in above this.
defined('TTS_CHUNK_CHARS') or define('TTS_CHUNK_CHARS', 2600);
defined('TTS_CONCURRENCY') or define('TTS_CONCURRENCY', 3);
defined('TTS_PACE_MS')     or define('TTS_PACE_MS', 21000);   // used by prewarm.php

// The daily cap is per model, so when one is spent we simply move to the next.
// Three models ≈ 30 free readings a day.
$GEMINI_TTS_FALLBACKS = $GEMINI_TTS_FALLBACKS ?? [
  'gemini-3.1-flash-tts-preview',
  'gemini-2.5-pro-preview-tts',
];
defined('GEMINI_TTS_STYLE') or define('GEMINI_TTS_STYLE', 'Read this aloud slowly and calmly, like a quiet narrator reading someone their own life back to them. Unhurried, warm, no drama:');

// ElevenLabs voice shaping. Lower stability = more expressive.
defined('TTS_STABILITY') or define('TTS_STABILITY',        0.50);
defined('TTS_SIMILARITY_BOOST') or define('TTS_SIMILARITY_BOOST', 0.75);
defined('TTS_STYLE') or define('TTS_STYLE',            0.10);
defined('TTS_SPEED') or define('TTS_SPEED',            0.92); // slow, narrated cadence

// ===== story shape ============================================
defined('STORY_MIN_WORDS') or define('STORY_MIN_WORDS',  400);
defined('STORY_MAX_WORDS') or define('STORY_MAX_WORDS',  550);
defined('LLM_TEMPERATURE') or define('LLM_TEMPERATURE',  0.85);

// ===== paths ==================================================
defined('AUDIO_DIR') or define('AUDIO_DIR', __DIR__ . '/../audio/generated/');
defined('AUDIO_URL') or define('AUDIO_URL', 'audio/generated/');
defined('CACHE_DIR') or define('CACHE_DIR', __DIR__ . '/../audio/generated/');

// ===== demo guardrails ========================================
defined('DEMO_TOOLS') or define('DEMO_TOOLS',   true);  // set false in production: disables cache-clearing + prompt echo
defined('RATE_LIMIT') or define('RATE_LIMIT',   40);    // requests per IP per window
defined('RATE_WINDOW') or define('RATE_WINDOW',  600);   // seconds
defined('MAX_FIELD_LEN') or define('MAX_FIELD_LEN', 400);  // per quiz answer
defined('MAX_TTS_CHARS') or define('MAX_TTS_CHARS', 5000); // per synthesis call
defined('HTTP_TIMEOUT') or define('HTTP_TIMEOUT',  90);   // seconds per upstream call
