<?php

/*
|--------------------------------------------------------------------------
| Escalate
|--------------------------------------------------------------------------
|
| Everything here is read server-side only. No value in this file is ever
| sent to a browser — the whole AI surface is reached through authenticated
| routes in app/Http/Controllers, never from client JavaScript. If you find
| yourself wanting to expose one of these to Blade, that is the bug.
|
*/

return [

    /*
    | Story generation. Anthropic is the only provider wired up; the shape is
    | deliberately provider-shaped so a second one can slot in without
    | touching the jobs that call it.
    */
    'story' => [
        'provider'  => env('ESCALATE_STORY_PROVIDER', 'anthropic'),
        'model'     => env('ANTHROPIC_MODEL', 'claude-sonnet-5'),
        'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 2000),
        'timeout'   => (int) env('ANTHROPIC_TIMEOUT', 120),
    ],

    'anthropic' => [
        'key'     => env('ANTHROPIC_API_KEY'),
        'base'    => env('ANTHROPIC_BASE', 'https://api.anthropic.com/v1'),
        'version' => '2023-06-01',
    ],

    /*
    | Narration. Flash v2.5 is half the credit cost of multilingual v2 and the
    | right cadence for slow reading — see docs/COSTS.md before changing it.
    */
    'voice' => [
        'provider'      => env('ESCALATE_VOICE_PROVIDER', 'elevenlabs'),
        'model'         => env('ELEVENLABS_MODEL', 'eleven_flash_v2_5'),
        'output_format' => env('ELEVENLABS_FORMAT', 'mp3_44100_128'),
        'speed'         => (float) env('ELEVENLABS_SPEED', 0.92),
        'stability'     => 0.5,
        'similarity'    => 0.75,
        'style'         => 0.1,
        'timeout'       => (int) env('ELEVENLABS_TIMEOUT', 180),
    ],

    'elevenlabs' => [
        'key'  => env('ELEVENLABS_API_KEY'),
        'base' => env('ELEVENLABS_BASE', 'https://api.elevenlabs.io/v1'),
    ],

    /*
    | The voices a user may choose between. Keys are stored on the profile;
    | the ids never leave the server. Audition replacements at
    | https://elevenlabs.io/app/voice-library before shipping.
    */
    'voices' => [
        'still'    => ['id' => env('VOICE_STILL', 'EXAVITQu4vr4xnSDxMaL'), 'label' => 'Still',    'note' => 'Low, unhurried. Reads like someone sitting beside you.'],
        'warm'     => ['id' => env('VOICE_WARM', 'XrExE9yKIg1WjnnlVkGX'),  'label' => 'Warm',     'note' => 'Rounder and closer. Good for gratitude and rewinds.'],
        'grounded' => ['id' => env('VOICE_GROUNDED', 'pFZP5JQG7iQjIQuC4Bku'), 'label' => 'Grounded', 'note' => 'Even and steady. No performance in it.'],
    ],

    /*
    | Per-user spend limits. Generation is the only expensive thing this app
    | does, so it is the only thing worth rationing. Counted per rolling
    | period in app/Support/Quota.php.
    */
    'quotas' => [
        'stories_per_day'      => (int) env('QUOTA_STORIES_PER_DAY', 5),
        'narrations_per_day'   => (int) env('QUOTA_NARRATIONS_PER_DAY', 8),
        'regenerations_per_story' => (int) env('QUOTA_REGENERATIONS', 4),
        'affirmations_per_day' => (int) env('QUOTA_AFFIRMATION_SETS_PER_DAY', 2),
        'rewinds_per_day'      => (int) env('QUOTA_REWINDS_PER_DAY', 3),
    ],

    /*
    | Story shape. The brief asks for user-chosen length; these are the word
    | targets behind each choice.
    */
    'lengths' => [
        'short'  => ['label' => 'Short',  'words' => [220, 300], 'note' => 'Two minutes. A single scene.'],
        'medium' => ['label' => 'Medium', 'words' => [400, 550], 'note' => 'Three to four minutes. A morning.'],
        'long'   => ['label' => 'Long',   'words' => [700, 900], 'note' => 'Six minutes. A whole day, with room in it.'],
    ],

    /*
    | Manifestation Archive statuses, in the order they appear in the UI.
    | 'terminal' marks the ones that unlock a Rewind.
    */
    'statuses' => [
        'desired'        => ['label' => 'Desired',        'terminal' => false, 'note' => 'Named, and waiting.'],
        'unfolding'      => ['label' => 'Unfolding',      'terminal' => false, 'note' => 'Something has started moving.'],
        'manifested'     => ['label' => 'Manifested',     'terminal' => true,  'note' => 'It arrived.'],
        'answered'       => ['label' => 'Answered Prayer','terminal' => true,  'note' => 'It arrived, and you know who to thank.'],
        'achieved'       => ['label' => 'Achieved',       'terminal' => true,  'note' => 'You built it.'],
        'redirected'     => ['label' => 'Redirected',     'terminal' => true,  'note' => 'Something else came instead, and it was right.'],
        'evolved'        => ['label' => 'Evolved',        'terminal' => true,  'note' => 'What you wanted changed shape.'],
        'released'       => ['label' => 'Released',       'terminal' => true,  'note' => 'You set it down on purpose.'],
        'paused'         => ['label' => 'Paused',         'terminal' => false, 'note' => 'Not now. Not never.'],
    ],

    /*
    | Themes.
    |
    | Each is a full palette, not a hue shift, and each declares whether it is
    | light or dark so `color-scheme` and the browser chrome colour follow. The
    | swatches are what the picker draws — three colours is enough to recognise
    | a theme without rendering the whole app.
    |
    | All six stay inside the brief: premium, calm, gender-neutral. No pinks, no
    | wellness pastels, nothing that reads as aimed at one gender. Adding a
    | theme means adding a key here and a matching [data-theme='key'] block in
    | public/css/app.css — nothing else.
    */
    'themes' => [
        'midnight' => [
            'counterpart' => 'parchment',
            'label'  => 'Midnight',
            'note'   => 'Ink navy and sage. The default, and the quietest.',
            'scheme' => 'dark',
            'chrome' => '#101521',
            'swatch' => ['#101521', '#7FA898', '#BFA173'],
        ],
        'ember' => [
            'counterpart' => 'linen',
            'label'  => 'Ember',
            'note'   => 'Near-black and brass. Candlelit rather than lit.',
            'scheme' => 'dark',
            'chrome' => '#16110D',
            'swatch' => ['#16110D', '#C79A5C', '#B9705A'],
        ],
        'tide' => [
            'counterpart' => 'parchment',
            'label'  => 'Tide',
            'note'   => 'Deep water and pale aqua. Cool and awake.',
            'scheme' => 'dark',
            'chrome' => '#0B1A1F',
            'swatch' => ['#0B1A1F', '#7FB8BE', '#C4A98A'],
        ],
        'graphite' => [
            'counterpart' => 'parchment',
            'label'  => 'Graphite',
            'note'   => 'Neutral charcoal. Nothing decorative at all.',
            'scheme' => 'dark',
            'chrome' => '#141517',
            'swatch' => ['#141517', '#9AA3AD', '#C2B49A'],
        ],
        'parchment' => [
            'counterpart' => 'midnight',
            'label'  => 'Parchment',
            'note'   => 'Cream and sage. Daylight, and easy on tired eyes.',
            'scheme' => 'light',
            'chrome' => '#F4F1EA',
            'swatch' => ['#F4F1EA', '#567C6F', '#A28757'],
        ],
        'linen' => [
            'counterpart' => 'ember',
            'label'  => 'Linen',
            'note'   => 'Warm oat and clay. The softest of the light ones.',
            'scheme' => 'light',
            'chrome' => '#F5EFE6',
            'swatch' => ['#F5EFE6', '#9A6A55', '#7C7A63'],
        ],
    ],

    /*
    | Faith language. Shapes vocabulary in every generated text. 'none' is the
    | default on purpose — the app is gender-neutral and belief-neutral until
    | the user says otherwise.
    */
    'faith_languages' => [
        'none'      => 'Secular — no spiritual vocabulary at all',
        'universe'  => 'The universe, energy, alignment',
        'god'       => 'God, prayer, blessing',
        'spirit'    => 'Spirit, ancestors, guidance',
        'higher'    => 'A higher power, unnamed',
    ],

    /*
    | Uploads. The brief caps photos hard, and so does this.
    */
    'images' => [
        'max_per_desire' => 2,
        'max_kb'         => 4096,
        'mimes'          => ['jpg', 'jpeg', 'png', 'webp'],
    ],
];
