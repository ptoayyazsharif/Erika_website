<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Config an administrator can change from a browser.
 *
 * Values live in the `settings` table and are overlaid onto the config
 * repository once per request at boot. Everything downstream — Quota, Ceiling,
 * Plan, Anthropic, ElevenLabs, Cashier — keeps reading config() and needs to
 * know nothing about this file. That is the point: no call site changes, so
 * nothing can read the setting through one path and the config through another.
 *
 * Three rules hold this together.
 *
 * 1. THE ALLOWLIST IS THE SECURITY BOUNDARY. Only keys in EDITABLE can be
 *    written, and the form posts an index into that list rather than a config
 *    path. Without it, "save these settings" is an arbitrary config write, and
 *    `app.key` or `database.connections.sqlite.database` are one crafted field
 *    name away.
 *
 * 2. SECRETS GO IN AND DO NOT COME BACK. `secret` marks values that are never
 *    rendered to a browser — the form shows the last four characters and
 *    accepts a replacement or nothing. An administrator can rotate a key
 *    without ever being shown one.
 *
 * 3. AN EMPTY OVERRIDE IS NOT AN OVERRIDE. Clearing a field deletes the row and
 *    falls back to the environment, rather than storing "" and silently
 *    switching off whatever it configured.
 */
class Settings
{
    private const CACHE_KEY = 'escalate.settings';

    /**
     * Every setting an administrator may change, grouped for the page.
     *
     * `type` drives both validation and the input rendered:
     *   secret — write-only, masked on display
     *   bool   — checkbox
     *   int    — number input, validated as a non-negative integer
     *   string — plain text
     */
    public static function editable(): array
    {
        return [
            'Writing (Anthropic)' => [
                'escalate.anthropic.key'   => ['type' => 'secret', 'label' => 'API key', 'help' => 'Without this no reading can be written. Nothing else in the app depends on it.'],
                'escalate.story.model'     => ['type' => 'string', 'label' => 'Model', 'help' => 'The model id readings are written with.'],
                'escalate.story.max_tokens' => ['type' => 'int', 'label' => 'Max tokens', 'help' => 'Upper bound on a single reading. Raising it raises the cost of every generation.'],
            ],

            'Voice (ElevenLabs)' => [
                'escalate.elevenlabs.key' => ['type' => 'secret', 'label' => 'API key', 'help' => 'Without this the words still work; only the audio stops.'],
                'escalate.voice.model'    => ['type' => 'string', 'label' => 'Model', 'help' => 'eleven_multilingual_v2 reads long-form far better than the flash models, at roughly twice the cost per character.'],
            ],

            'Billing' => [
                'escalate.billing.enabled' => ['type' => 'bool', 'label' => 'Billing enabled', 'help' => 'Off means quotas ignore plans entirely and everyone gets the flat limits below — exactly how the app behaved before Stripe. Do not turn this on until the keys below are real and the plans carry price ids.'],
                'escalate.billing.trial_days' => ['type' => 'int', 'label' => 'Free trial days', 'help' => 'Days of full access before the first charge. 0 for none.'],
            ],

            'Stripe — mode' => [
                'escalate.stripe.mode' => ['type' => 'mode', 'label' => 'Use test mode', 'help' => 'Test mode swaps in the test keys below AND the test price id on every plan. Nothing is charged. Stripe keeps the two worlds entirely separate, so a price id from one is meaningless in the other — which is why each plan carries both.'],
            ],

            'Stripe — test keys' => [
                'escalate.stripe.test.key'            => ['type' => 'string', 'label' => 'Test publishable key', 'help' => 'pk_test_…'],
                'escalate.stripe.test.secret'         => ['type' => 'secret', 'label' => 'Test secret key', 'help' => 'sk_test_… Cannot move real money.'],
                'escalate.stripe.test.webhook_secret' => ['type' => 'secret', 'label' => 'Test webhook signing secret', 'help' => 'whsec_… from `stripe listen` or a test-mode endpoint.'],
            ],

            'Stripe — live keys' => [
                'escalate.stripe.live.key'            => ['type' => 'string', 'label' => 'Live publishable key', 'help' => 'pk_live_… — not a secret, but it identifies the account.'],
                'escalate.stripe.live.secret'         => ['type' => 'secret', 'label' => 'Live secret key', 'help' => 'sk_live_… This one moves real money. Treat it accordingly.'],
                'escalate.stripe.live.webhook_secret' => ['type' => 'secret', 'label' => 'Live webhook signing secret', 'help' => 'whsec_… Without it the webhook endpoint returns 403 and no subscription ever reaches the app.'],
            ],

            /*
            | Mail.
            |
            | Editable here so pointing at a provider is a paste and a save
            | rather than a redeploy — and so the same screen that tells you
            | verification is switched off can be the one where you fix it.
            |
            | Password reset and email verification are transactional mail: they
            | have to arrive, in the inbox, within a minute. That rules out
            | sending them from this server's own IP — see the note in DEPLOY.md
            | on why self-hosting outbound SMTP is the wrong tool for this job.
            */
            'Mail' => [
                'mail.default'          => ['type' => 'string', 'label' => 'Mailer', 'help' => 'smtp for a provider, log to write mail into the log instead of sending it. Nothing is delivered while this says log — password reset reports success and sends nothing.'],
                'mail.mailers.smtp.host' => ['type' => 'string', 'label' => 'SMTP host', 'help' => 'e.g. smtp.resend.com, smtp.postmarkapp.com, smtp-relay.brevo.com'],
                'mail.mailers.smtp.port' => ['type' => 'int', 'label' => 'Port', 'help' => '587 for STARTTLS, 465 for TLS. Port 25 is blocked outbound by most hosts, this one included until proven otherwise.'],
                'mail.mailers.smtp.username' => ['type' => 'string', 'label' => 'Username'],
                'mail.mailers.smtp.password' => ['type' => 'secret', 'label' => 'Password or API key'],
                'mail.mailers.smtp.scheme'   => ['type' => 'string', 'label' => 'Scheme', 'help' => 'smtps for port 465. Leave blank for 587.'],
                'mail.from.address'     => ['type' => 'string', 'label' => 'From address', 'help' => 'Must be on a domain the provider has verified, or everything is rejected or filed as spam.'],
                'mail.from.name'        => ['type' => 'string', 'label' => 'From name'],
            ],

            'Who can sign up' => [
                'escalate.beta.invite_only'          => ['type' => 'bool', 'label' => 'Invite only', 'help' => 'Registration requires an unclaimed code. This is the main thing standing between a public URL and your provider bill.'],
                'escalate.beta.require_verification' => ['type' => 'bool', 'label' => 'Require a confirmed email', 'help' => 'Gates the four routes that cost money. Needs working mail — with none, nobody can generate anything.'],
            ],

            'Daily limits, per person' => [
                'escalate.quotas.stories_per_day'    => ['type' => 'int', 'label' => 'Readings', 'help' => 'Used when billing is off. With billing on, the per-plan numbers below apply instead.'],
                'escalate.quotas.narrations_per_day' => ['type' => 'int', 'label' => 'Narrations'],
                'escalate.quotas.rewinds_per_day'    => ['type' => 'int', 'label' => 'Rewinds'],
                'escalate.quotas.regenerations_per_story' => ['type' => 'int', 'label' => 'Rewrites per reading'],
            ],

            'Daily limits, per plan' => [
                'escalate.plans.free.quotas.story'        => ['type' => 'int', 'label' => 'Free — readings'],
                'escalate.plans.free.quotas.narration'    => ['type' => 'int', 'label' => 'Free — narrations'],
                'escalate.plans.free.quotas.rewind'       => ['type' => 'int', 'label' => 'Free — rewinds'],
                'escalate.plans.monthly.quotas.story'     => ['type' => 'int', 'label' => 'Paid — readings'],
                'escalate.plans.monthly.quotas.narration' => ['type' => 'int', 'label' => 'Paid — narrations'],
                'escalate.plans.monthly.quotas.rewind'    => ['type' => 'int', 'label' => 'Paid — rewinds'],
            ],

            'The ceiling, across everyone' => [
                'escalate.ceiling.stories_per_day'    => ['type' => 'int', 'label' => 'Readings a day', 'help' => 'A whole-application cap. The per-person limit multiplies by the number of accounts; this one does not. 0 means unlimited.'],
                'escalate.ceiling.narrations_per_day' => ['type' => 'int', 'label' => 'Narrations a day'],
                'escalate.ceiling.rewinds_per_day'    => ['type' => 'int', 'label' => 'Rewinds a day'],
            ],
        ];
    }

    /** Flattened: config key => metadata. */
    public static function schema(): array
    {
        return array_merge(...array_values(self::editable()));
    }

    public static function isEditable(string $key): bool
    {
        return array_key_exists($key, self::schema());
    }

    public static function isSecret(string $key): bool
    {
        return (self::schema()[$key]['type'] ?? null) === 'secret';
    }

    /* ── reading ─────────────────────────────────────────────────────────── */

    /**
     * Stored overrides, key => value. Cached; the table is tiny and hot.
     *
     * The try/catch is not defensive padding — without it this class can stop
     * the application from booting at all.
     *
     * apply() runs from AppServiceProvider::boot(), and both the cache lookup
     * and the query behind it hit the database (CACHE_STORE is `database` in
     * production). So any database problem here becomes a boot failure rather
     * than a page failure, and that includes artisan: docker/entrypoint.sh
     * runs `php artisan migrate --force` at container start, which on a fresh
     * volume happens BEFORE the cache and settings tables exist. Boot would
     * fail, migrate could never run, and the tables could never be created —
     * a deploy that cannot recover on its own.
     *
     * Falling back to no overrides is the right failure: config and the
     * environment still apply, so the app comes up with exactly what it was
     * deployed with instead of not coming up.
     */
    public static function stored(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, function () {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return Setting::all()->mapWithKeys(fn (Setting $s) => [$s->key => $s->value])->all();
            });
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * Overlay the stored values onto the config repository.
     *
     * Called once from AppServiceProvider::boot(), before anything reads a
     * setting. Keys not on the allowlist are skipped even if a row exists — so
     * shrinking the allowlist later disarms an old row rather than leaving it
     * quietly in force.
     */
    public static function apply(): void
    {
        foreach (self::stored() as $key => $value) {
            if (! self::isEditable($key)) {
                continue;
            }

            config([$key => self::cast($key, $value)]);
        }

        // Last, and it has to be last: it reads the mode and the key sets that
        // the loop above may just have changed, and copies the active set into
        // the cashier.* keys Cashier itself reads.
        Stripe::apply();
    }

    private static function cast(string $key, mixed $value): mixed
    {
        return match (self::schema()[$key]['type'] ?? 'string') {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int'  => (int) $value,
            /*
             * A two-value choice that arrives in two shapes.
             *
             * The settings form renders it as a checkbox and posts "1" or "0",
             * but Settings::put() is a public entry point and the obvious thing
             * to hand it is the word itself. Passing 'test' through
             * FILTER_VALIDATE_BOOLEAN yields false — so writing the mode by its
             * own name silently selected the opposite one. Accept both.
             */
            'mode' => in_array($value, [Stripe::TEST, Stripe::LIVE], true)
                ? $value
                : (filter_var($value, FILTER_VALIDATE_BOOLEAN) ? Stripe::TEST : Stripe::LIVE),
            default => $value,
        };
    }

    /* ── writing ─────────────────────────────────────────────────────────── */

    /**
     * Store an override, or remove it.
     *
     * A null or empty value deletes the row rather than storing "". Clearing a
     * field in the form therefore means "go back to whatever the server was
     * deployed with", which is the only reading of an empty box that lets an
     * administrator undo themselves.
     */
    public static function put(string $key, mixed $value, ?User $by = null): void
    {
        abort_unless(self::isEditable($key), 403);

        if ($value === null || $value === '') {
            Setting::where('key', $key)->delete();
            self::flush();

            return;
        }

        // A query rather than firstOrNew, for the reason spelled out on
        // Narration::queueFor(): firstOrNew fill()s the lookup attributes, and
        // nothing on this model is mass-assignable — by design, because `key`
        // is the field the allowlist exists to control.
        $setting = Setting::query()->where('key', $key)->first() ?? new Setting;

        $setting->forceFill([
            'key'        => $key,
            'value'      => (string) $value,
            'is_secret'  => self::isSecret($key),
            'updated_by' => $by?->id,
        ])->save();

        self::flush();
    }

    public static function flush(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Throwable $e) {
            // Same reasoning as stored(): never let the cache take the app down.
            report($e);
        }
    }

    /**
     * What the settings form should show for a key.
     *
     * Secrets come back as a hint, never a value: enough to tell one key from
     * another when checking which account is wired up, useless to anyone who
     * shoulder-surfs it.
     */
    public static function display(string $key): array
    {
        $live = config($key);

        if (! self::isSecret($key)) {
            return ['value' => $live, 'set' => filled($live), 'hint' => null];
        }

        return [
            'value' => null,
            'set'   => filled($live),
            'hint'  => filled($live) ? '••••'.substr((string) $live, -4) : null,
        ];
    }

    /** True when this key is currently overridden here rather than by the environment. */
    public static function isOverridden(string $key): bool
    {
        return array_key_exists($key, self::stored());
    }
}
