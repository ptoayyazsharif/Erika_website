<?php
/* ============================================================
   Manifest — configuration
   THIS IS THE ONLY FILE YOU NEED TO EDIT TO GO LIVE.
   Everything below works with empty keys (demo fallback mode),
   so you can upload first and add keys later.
   ============================================================ */

// ===== API KEYS — FILL IN =====================================
// Leave LLM_PROVIDER as 'auto' and it uses whichever key is filled.
// Force it with 'anthropic' or 'openai'.
define('LLM_PROVIDER',      'auto');

define('ANTHROPIC_API_KEY', '');
define('ANTHROPIC_MODEL',   'claude-sonnet-5');

define('OPENAI_API_KEY',    '');
define('OPENAI_MODEL',      'gpt-4o-mini');

define('ELEVENLABS_API_KEY', '');
define('TTS_MODEL',          'eleven_flash_v2_5');

// ===== ElevenLabs voices ======================================
// Paste voice IDs from https://elevenlabs.io/app/voice-library
// Defaults below are stock ElevenLabs voices — replace with the
// ones you actually audition. The voice is half the product.
$VOICES = [
  'calm'      => 'EXAVITQu4vr4xnSDxMaL', // Sarah  — soft, unhurried
  'warm'      => 'XrExE9yKIg1WjnnlVkGX', // Matilda — warm, close-mic
  'confident' => 'pFZP5JQG7iQjIQuC4Bku', // Lily   — clear, grounded
];
define('DEFAULT_VOICE', 'calm');

// ElevenLabs voice shaping. Lower stability = more expressive.
define('TTS_STABILITY',        0.50);
define('TTS_SIMILARITY_BOOST', 0.75);
define('TTS_STYLE',            0.10);
define('TTS_SPEED',            0.92); // slow, narrated cadence

// ===== story shape ============================================
define('STORY_MIN_WORDS',  400);
define('STORY_MAX_WORDS',  550);
define('LLM_TEMPERATURE',  0.85);

// ===== paths ==================================================
define('AUDIO_DIR', __DIR__ . '/../audio/generated/');
define('AUDIO_URL', 'audio/generated/');
define('CACHE_DIR', __DIR__ . '/../audio/generated/');

// ===== demo guardrails ========================================
define('DEMO_TOOLS',   true);  // set false in production: disables cache-clearing + prompt echo
define('RATE_LIMIT',   40);    // requests per IP per window
define('RATE_WINDOW',  600);   // seconds
define('MAX_FIELD_LEN', 400);  // per quiz answer
define('MAX_TTS_CHARS', 5000); // per synthesis call
define('HTTP_TIMEOUT',  90);   // seconds per upstream call
