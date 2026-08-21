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
    | Narration.
    |
    | This used to run on Flash v2.5, chosen for being half the credit cost.
    | That was the wrong trade and it is the single biggest reason the audio
    | sounded cheap: Flash is built for real-time conversational agents, where
    | latency beats everything. Narration here is a queued job — nobody is
    | waiting on the open socket, and twenty seconds is perfectly acceptable —
    | so buying speed with quality bought something worth nothing.
    |
    | Multilingual v2 is ElevenLabs' best model for long-form reading. It costs
    | roughly twice as much per character; a whole reading is about 1,500
    | characters, which against a 363,000-character allowance is not the
    | constraint anyone thought it was.
    */
    'voice' => [
        'provider'      => env('ESCALATE_VOICE_PROVIDER', 'elevenlabs'),
        'model'         => env('ELEVENLABS_MODEL', 'eleven_multilingual_v2'),
        'output_format' => env('ELEVENLABS_FORMAT', 'mp3_44100_128'),
        'speed'         => (float) env('ELEVENLABS_SPEED', 0.92),

        // Higher stability than the default, and style at zero. Style is an
        // expressiveness dial — it adds performance, and performance is the
        // one thing this reading must not have. The text is someone describing
        // their own ordinary Tuesday; it should sound like it.
        'stability'     => 0.55,
        'similarity'    => 0.8,
        'style'         => 0.0,
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
    |
    | The previous three were all wrong for this, and not by a little: Sarah is
    | tagged entertainment_tv, Matilda and Lily informative_educational. Those
    | are explainer-video voices — bright, forward, selling you something. This
    | app is a person describing a quiet morning they already live in.
    |
    | 'still' must remain a valid key: Profile::voiceId() and NarrateStory both
    | fall back to it when a stored key no longer resolves.
    */
    'voices' => [
        'still'       => ['id' => env('VOICE_STILL', 'SAz9YHcvj6GT2YYXdXww'),    'label' => 'Still',       'note' => 'Relaxed and unplaceable. Reads like someone sitting beside you.'],
        'warm'        => ['id' => env('VOICE_WARM', 'pFZP5JQG7iQjIQuC4Bku'),     'label' => 'Warm',        'note' => 'Velvety, British. Closer and rounder — good for gratitude.'],
        'grounded'    => ['id' => env('VOICE_GROUNDED', 'nPczCjzI2devNBz1zQrb'), 'label' => 'Grounded',    'note' => 'Deep and resonant. No performance in it.'],
        'storyteller' => ['id' => env('VOICE_STORYTELLER', 'JBFqnCBsd6RMkjVDRZzb'), 'label' => 'Storyteller', 'note' => 'Warm and captivating. Built for reading aloud.'],
        'reassuring'  => ['id' => env('VOICE_REASSURING', 'EXAVITQu4vr4xnSDxMaL'), 'label' => 'Reassuring',  'note' => 'Mature and steady. Certain without pushing.'],

        /*
         * Erika's own voice.
         *
         * Worth naming plainly: this is a cloned voice on the account, so
         * every user who picks it hears their own private journal read back in
         * a real, identifiable person's voice. For a coaching app that is the
         * point — it is her reading it to you. It is also exactly the kind of
         * thing that needs her explicit say-so, and the consent for it belongs
         * in the terms, not in a config file.
         */
        'erika'       => ['id' => env('VOICE_ERIKA', 'A0cqQbKSKNvw6IyjpQ5n'),    'label' => 'Erika',       'note' => 'Erika reading it to you, in her own voice.'],
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
    | Beta.
    |
    | Both default to ON, and that is the deliberate choice: this app spends
    | real money on someone else's API every time a button is pressed, and the
    | safe default for a thing you are about to put on the internet is "closed".
    | Opening it is then a decision someone made on purpose and can point at,
    | rather than a default nobody noticed.
    */
    'beta' => [
        // Registration requires an unclaimed invite code. Turn off to open
        // signup to anyone who finds the URL.
        'invite_only' => (bool) env('INVITE_ONLY', true),

        // Generation requires a confirmed email address. Reading, writing
        // desires and filling in My World all still work unverified — this
        // gates the routes that cost money, not the app.
        //
        // Set INVITE_ONLY=false and REQUIRE_VERIFICATION=false together only if
        // you mean it: that combination is an open door to your provider bill.
        'require_verification' => (bool) env('REQUIRE_VERIFICATION', true),

        // How long a minted invite stays good for, in days. Null for forever.
        'invite_days' => (int) env('INVITE_DAYS', 30),
    ],

    /*
    | The ceiling — a whole-application daily limit, on top of the per-user one.
    |
    | 'quotas' above bounds what ONE person can spend. Nothing bounded what
    | EVERYONE could spend, which is the wrong shape of limit for the failure
    | that actually happens: not one greedy user, but a hundred accounts that
    | should not exist. The per-user quota multiplies by the number of accounts;
    | this does not.
    |
    | Counted as successful generations in the last 24 hours across every user,
    | out of the same ai_events ledger the per-user quota reads — deliberately
    | counts, not currency, because a count is exact and needs no pricing table
    | to be correct.
    |
    | Size it at roughly (beta users) × (per-user quota), with headroom. The
    | defaults below suit twenty to thirty people. Raise them as you grow; the
    | number existing at all is the point.
    */
    'ceiling' => [
        'stories_per_day'    => (int) env('CEILING_STORIES_PER_DAY', 200),
        'narrations_per_day' => (int) env('CEILING_NARRATIONS_PER_DAY', 300),
        'rewinds_per_day'    => (int) env('CEILING_REWINDS_PER_DAY', 100),
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
