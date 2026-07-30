<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\RejectSuspended;
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
        ]);

        /*
        | Behind Cloudflare or a host that terminates TLS upstream, the origin
        | sees plain HTTP. Without this, $request->secure() is false, so
        | SecurityHeaders never emits HSTS and generated URLs can come out as
        | http://. Trusting the forwarded headers is safe here because the app
        | is only ever reached through that proxy.
        */
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
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
