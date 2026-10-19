<?php

namespace App\Providers;

use App\Listeners\RecordSubscriptionPeriod;
use App\Models\Subscription;
use Laravel\Cashier\Cashier;
use Illuminate\Support\Facades\Event;
use Laravel\Cashier\Events\WebhookReceived;

use App\Support\EmailTemplates;
use App\Support\Settings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * When the next charge falls due, kept locally.
         *
         * Cashier records what someone is on but not when it renews, and its
         * own accessor asks Stripe every time it is read — a network call on a
         * page render. This copies the date off the webhook instead.
         */
        Event::listen(WebhookReceived::class, RecordSubscriptionPeriod::class);

        // Cashier declares its own $casts, so the renewal date this app added
        // arrives as a string and ->format() fatals on the billing page.
        Cashier::useSubscriptionModel(Subscription::class);

        /*
         * Administrator overrides, laid over the config files.
         *
         * Here rather than at each call site so that nothing downstream — Quota,
         * Ceiling, Plan, the two AI clients, Cashier — has to know settings can
         * come from a database. They all keep reading config(), which means
         * there is no second path by which a value could be read unoverridden.
         *
         * Cached, so this is one array lookup per request rather than a query.
         * Settings::apply() no-ops safely when the table does not exist yet,
         * which is the state every `migrate` on a fresh database starts in.
         */
        Settings::apply();

        self::useEditableAuthEmails();
    }

    /**
     * Password reset and email confirmation, worded from the admin panel.
     *
     * These two are framework notifications with no template in this repo, so
     * they were the only emails whose wording could not be changed at all —
     * including the two most likely to be read by somebody who is stuck.
     *
     * `toMailUsing` is Laravel's own hook for this, so nothing about how auth
     * works changes: no notification subclasses, no route overrides. The button
     * and its URL stay here rather than in the editable body, because a reset
     * email with no working link is worse than no reset email at all.
     *
     * Registered after Settings::apply() so an override is already in config by
     * the time either closure reads one.
     */
    private static function useEditableAuthEmails(): void
    {
        ResetPassword::toMailUsing(function (mixed $notifiable, string $token): MailMessage {
            $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

            $tokens = ['minutes' => (string) $minutes];

            return (new MailMessage)
                ->subject(EmailTemplates::subject('password_reset', $tokens))
                ->markdown('mail.auth-action', [
                    'body'   => EmailTemplates::body('password_reset', $tokens),
                    'action' => 'Reset password',
                    'url'    => route('password.reset', [
                        'token' => $token,
                        'email' => $notifiable->getEmailForPasswordReset(),
                    ]),
                ]);
        });

        VerifyEmail::toMailUsing(fn (mixed $notifiable, string $url): MailMessage => (new MailMessage)
            ->subject(EmailTemplates::subject('verify_email'))
            ->markdown('mail.auth-action', [
                'body'   => EmailTemplates::body('verify_email'),
                'action' => 'Confirm this address',
                'url'    => $url,
            ]));
    }
}
