<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\RejectSuspended;
use App\Http\Middleware\RequireVerifiedEmail;
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

        // Sessions are cookie-bound and same-site; nothing in this app is
        // meant to be embedded or called cross-origin.
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
