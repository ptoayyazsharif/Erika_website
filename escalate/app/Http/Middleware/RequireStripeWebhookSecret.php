<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refuse Stripe webhooks when there is no signing secret to check them against.
 *
 * Cashier's WebhookController applies its signature middleware conditionally:
 *
 *     if (config('cashier.webhook.secret')) {
 *         $this->middleware(VerifyWebhookSignature::class);
 *     }
 *
 * So an unset STRIPE_WEBHOOK_SECRET does not disable the endpoint — it disables
 * the *authentication* on the endpoint, and leaves it processing whatever
 * anyone posts to it. That fails open, and it is the wrong way round: the
 * handlers write to the subscriptions table this app reads entitlement from, so
 * an unauthenticated caller could grant or revoke access by describing a
 * subscription that never existed.
 *
 * The blast radius today is small — with billing disabled nothing consults
 * those rows — but "small until someone flips a flag" is not a security
 * property. This makes the missing secret mean what an operator reading
 * .env.example would assume it means: the endpoint is shut, not open.
 *
 * 403 rather than 404: Stripe's delivery log shows the failure and the reason
 * is discoverable, which is what an operator debugging a silent subscription
 * needs. There is nothing to conceal here — the endpoint's existence is
 * implied by the integration.
 */
class RequireStripeWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('stripe/webhook')) {
            return $next($request);
        }

        if (blank(config('cashier.webhook.secret'))) {
            abort(403, 'This endpoint is not configured. Set STRIPE_WEBHOOK_SECRET.');
        }

        return $next($request);
    }
}
