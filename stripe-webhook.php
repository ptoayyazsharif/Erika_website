<?php
/**
 * Stripe webhook: https://erikakpage.com/stripe-webhook.php
 *
 * Add this address in Stripe (Developers → Webhooks → Add endpoint) with these
 * events, then paste its signing secret (whsec_…) in the admin under
 * Payments / Stripe:
 *   checkout.session.completed, checkout.session.async_payment_succeeded,
 *   checkout.session.async_payment_failed, checkout.session.expired,
 *   customer.subscription.updated, customer.subscription.deleted
 *
 * Every request is checked against the signing secret before anything is read
 * from it. What each event does is in lib/stripe.php (stripe_handle_event).
 */
require __DIR__ . '/cms.php';

header('Content-Type: text/plain; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit("POST only.\n"); }

$payload = (string) file_get_contents('php://input');
if (!stripe_verify($payload, (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? ''), stripe_whsec())) {
    http_response_code(400);
    exit("Signature check failed.\n");
}
$ev = json_decode($payload, true);
if (!is_array($ev) || ($ev['object'] ?? '') !== 'event') { http_response_code(400); exit("Not an event.\n"); }

set_setting('stripe.last_event', date('Y-m-d H:i:s') . ' · ' . (string) ($ev['type'] ?? '') . ' · ' . (empty($ev['livemode']) ? 'test' : 'live'));
echo stripe_handle_event($ev), "\n";
