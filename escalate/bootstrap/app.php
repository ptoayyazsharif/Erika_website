<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\RejectSuspended;
use App\Http\Middleware\RequireStripeWebhookSecret;
use App\Http\Middleware\RequireVerifiedEmail;
use App\Http\Middleware\RecordActivityDay;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
            // Which days somebody was here — the only thing that can answer
            // "did they come back". See the class: a date and a person, never
            // a path or a time.
            RecordActivityDay::class,
        ]);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'not-suspended' => RejectSuspended::class,
            // Not Laravel's own 'verified': this one honours the config flag at
            // request time, so it survives route:cache. See the class.
            'verified-email' => RequireVerifiedEmail::class,
        ]);

        /*
        | Proxy trust — configured, never wildcarded.
        |
        | This used to be `at: '*'`, which was wrong and dangerous. Trusting
        | every proxy means $request->ip() becomes whatever the caller puts in
        | X-Forwarded-For, and every IP-keyed limit in the app collapses with
        | it: the five-attempt login lockout, the register throttle, the
        | generation throttles, and the password confirmation guarding data
        | export and account deletion. Rotate the header per request and none
        | of them ever fire.
        |
        | "But it is only reachable through Cloudflare" does not save it.
        | Symfony takes the LEFTMOST X-Forwarded-For entry when everything is
        | trusted, and Cloudflare appends rather than replaces — so a forged
        | left-hand entry still wins. And on shared hosting the origin is
        | usually still reachable directly by IP.
        |
        | Default is null: trust nothing, use the real REMOTE_ADDR. That is
        | correct for a directly-served cPanel origin. If a CDN is put in
        | front, set TRUSTED_PROXIES to its ranges — not to '*'.
        */
        $middleware->trustProxies(
            at: array_filter(explode(',', (string) env('TRUSTED_PROXIES', ''))) ?: null,
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        /*
        | The one route that must not require a CSRF token.
        |
        | Stripe posts to /stripe/webhook from its own servers with no session
        | and no token, so the CSRF check would reject every event — and the
        | symptom is subscriptions that look fine in Stripe and never appear in
        | this app, because the row Cashier writes comes from the webhook.
        |
        | Dropping CSRF is only safe because something else authenticates the
        | caller: Stripe's signature header, checked against
        | STRIPE_WEBHOOK_SECRET. That is strictly stronger than a CSRF token
        | here — a token proves the request came from our own form, and this
        | request legitimately does not.
        |
        | RequireStripeWebhookSecret below is what makes that true in the case
        | nobody plans for. Cashier applies its signature check ONLY when the
        | secret is configured, so an unset secret leaves the endpoint open
        | rather than shut. Read the class; it is the opposite of what the
        | setting looks like it does.
        */
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);

        $middleware->append(RequireStripeWebhookSecret::class);

        // Sessions are cookie-bound and same-site; nothing in this app is
        // meant to be embedded or called cross-origin.
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
