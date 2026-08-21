<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The 'verified' gate, but decided per request rather than per deploy.
 *
 * The obvious way to write this was to build the middleware list in
 * routes/web.php:
 *
 *     $paid = config('escalate.beta.require_verification') ? ['verified'] : [];
 *
 * which reads the flag once, while routes are being registered — and
 * docker/entrypoint.sh runs `php artisan route:cache` at boot. The decision
 * therefore gets frozen into the route cache, and turning the flag off is only
 * honoured if something rebuilds that cache afterwards. A setting that appears
 * to do nothing until you run an unrelated artisan command is the kind of thing
 * someone debugs at midnight during a beta.
 *
 * Reading it here costs one config lookup per request and cannot go stale.
 * Delegating to the framework's own EnsureEmailIsVerified rather than
 * reimplementing it keeps the redirect target, the JSON behaviour and the
 * MustVerifyEmail check identical to every other Laravel app.
 */
class RequireVerifiedEmail
{
    public function __construct(private EnsureEmailIsVerified $verified) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('escalate.beta.require_verification')) {
            return $next($request);
        }

        return $this->verified->handle($request, $next);
    }
}
