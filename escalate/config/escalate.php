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
    /*
    |---------------------------------------------------------------------------
    | The words on the public pages
    |---------------------------------------------------------------------------
    |
    | Erika's wording, and the defaults are the source of truth: a fresh install
    | and an untouched database both say exactly what she wrote. Every one of
    | these is editable from Admin → Settings, because copy that needs a deploy
    | to change is copy that stays wrong during the week it matters most.
    |
    | Rendered with {{ }} everywhere, never {!! !!}. An administrator's typing is
    | still untrusted by the time it reaches a public page.
    */
    'copy' => [
        'intro' => env('COPY_INTRO', 'A private AI-powered personal growth experience that turns your goals into personalized stories you can read and listen to—while helping you reflect on your progress, gratitude and wins along the way.'),

        'not_launched' => env('COPY_NOT_LAUNCHED', 'Escalate hasn’t been publicly launched yet. We’re inviting a small group of founding testers to experience it first and help shape what it becomes.'),

        'questions_intro' => env('COPY_QUESTIONS_INTRO', 'Five quick questions. There are no right answers—we’re looking for a diverse group of thoughtful testers who will actually use Escalate and tell us what they think.'),

        /*
        | The five questions, and the helper line under each.
        |
        | Keyed by the column the answer lands in, so a question and the label
        | above that answer in Admin → Applications cannot drift apart.
        |
        | Editing a question after answers exist re-labels answers that were
        | written to different words. For twenty-five testers that is worth
        | accepting rather than building a versioning table for — but the admin
        | field says so, because finding it out from confusing data later is
        | worse.
        */
        'q_changing'      => env('COPY_Q1', 'What area of your life are you currently focused on changing, improving or creating something new in?'),
        'q_changing_help' => env('COPY_Q1_HELP', 'Career, money, relationships, health, family, lifestyle, personal growth—or anything else that matters to you.'),

        'q_practice'      => env('COPY_Q2', 'Do you currently use any reflection or personal-growth practices—such as journaling, visualization, prayer, meditation or affirmations?'),
        'q_practice_help' => env('COPY_Q2_HELP', 'If yes, tell us what you use and what you like about it. If not, that’s useful for us to know too.'),

        'q_tried_apps'      => env('COPY_Q3', 'Have you ever used a manifestation, visualization, journaling or personal-development app? If so, which one—and what did you like or dislike about it?'),
        'q_tried_apps_help' => env('COPY_Q3_HELP', ''),

        'q_will_use'      => env('COPY_Q4', 'Would you realistically use Escalate at least 4 times during a 7-day test?'),
        'q_will_use_help' => env('COPY_Q4_HELP', 'A truthful no here is more useful to us than an optimistic yes.'),

        'q_will_feedback'      => env('COPY_Q5', 'Are you willing to provide candid feedback after the test?'),
        'q_will_feedback_help' => env('COPY_Q5_HELP', ''),

        /*
        | Not a page in the app — the message Erika pastes into DMs all week.
        | It lives here so there is one canonical copy of it, and it is shown on
        | Admin → Invites, which is where somebody already is when they need it.
        */
        'outreach' => env('COPY_OUTREACH', 'I’ve been quietly 🤫 developing a new AI personal-growth app called Escalate, and we’re now in private beta testing. It turns your goals into personalized stories you can read and listen to, while helping you reflect on your progress and wins. I haven’t publicly announced it yet, 🤐 but I’m inviting a small group of people to test it. Want in?'),
    ],

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

        // The Founding 25. A number whose whole point is that it runs out, so
        // the admin screen can say how many seats are left rather than letting
        // somebody hand out a twenty-sixth by accident.
        'founding_seats' => (int) env('FOUNDING_SEATS', 25),
    ],

    /*
    | Billing.
    |
    | OFF by default, and that default is load-bearing rather than cautious:
    | with it off, Quota::limit() returns the flat 'quotas' numbers below and
    | every user gets exactly what they get today. Turning it on is what makes
    | quotas depend on a subscription — so shipping this code changes nothing
    | for anyone until someone decides, on purpose, that it should.
    |
    | Do not switch it on before the Stripe keys are set and the plan price ids
    | below resolve; a plan whose price id is empty cannot be checked out, and
    | the picker hides it rather than offering a button that 500s.
    */
    /*
    | Stripe has two entirely separate worlds. Test and live have their own
    | keys, their own webhook signing secrets, their own customers and their own
    | price ids — an id minted in one is meaningless in the other. So the mode
    | selects a whole credential set, and plans carry a price id per mode. See
    | App\Support\Stripe.
    */
    'stripe' => [
        'mode' => env('STRIPE_MODE', 'live'),

        'live' => [
            'key'            => env('STRIPE_KEY'),
            'secret'         => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],

        'test' => [
            'key'            => env('STRIPE_TEST_KEY'),
            'secret'         => env('STRIPE_TEST_SECRET'),
            'webhook_secret' => env('STRIPE_TEST_WEBHOOK_SECRET'),
        ],
    ],

    'billing' => [
        'enabled' => (bool) env('BILLING_ENABLED', false),

        // Shown on the plan picker. Not legal advice and not a substitute for
        // terms — see docs/LEGAL-PACK.md on auto-renewal disclosure, which in
        // the US is strict and enforced per-state.
        'currency' => env('CASHIER_CURRENCY', 'usd'),

        // Days of full access on signing up for a paid plan, before the first
        // charge. 0 for none.
        'trial_days' => (int) env('BILLING_TRIAL_DAYS', 0),
    ],

    /*
    | Plans.
    |
    | 'free' must exist and must have no price id — it is what everyone is on
    | before they pay and what they fall back to when a subscription lapses.
    |
    | Everything else needs a Stripe price id from the environment. Prices live
    | in Stripe, never here: a number in this file and a number in the dashboard
    | are two sources of truth for the same fact, and the one the customer is
    | actually charged is Stripe's. 'display' is a label for the page, and if it
    | disagrees with Stripe then Stripe wins and the label is a bug.
    |
    | Quotas are per day, and they are the whole product difference between the
    | tiers. Adding a tier means adding a key here and a price id in the
    | environment — no code change.
    */
    'plans' => [
        'free' => [
            'label'  => 'Free',
            'blurb'  => 'Enough to see whether this is for you.',
            'price'  => null,
            'display' => 'Free',
            'interval' => null,
            'quotas' => ['story' => 1, 'narration' => 1, 'rewind' => 1, 'affirmation' => 1],
        ],

        'monthly' => [
            'label'  => 'Escalate',
            'blurb'  => 'The whole thing, billed monthly.',
            'price'  => env('STRIPE_PRICE_MONTHLY'),
            'display' => env('STRIPE_PRICE_MONTHLY_LABEL', '$12 / month'),
            'interval' => 'month',
            'quotas' => ['story' => 5, 'narration' => 8, 'rewind' => 3, 'affirmation' => 2],
        ],

        'yearly' => [
            'label'  => 'Escalate, yearly',
            'blurb'  => 'The same, with two months back.',
            'price'  => env('STRIPE_PRICE_YEARLY'),
            'display' => env('STRIPE_PRICE_YEARLY_LABEL', '$120 / year'),
            'interval' => 'year',
            'quotas' => ['story' => 5, 'narration' => 8, 'rewind' => 3, 'affirmation' => 2],
        ],
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
        'affirmations_per_day' => (int) env('CEILING_AFFIRMATIONS_PER_DAY', 300),
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
