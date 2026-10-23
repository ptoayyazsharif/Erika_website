<?php
/**
 * Stripe paywall for landing pages, kept as small as Stan's: a page has a price
 * (or none), the visitor answers the questions, pays on Stripe's own Checkout
 * page, and comes back to the download.
 *
 * The owner only pastes two things in the admin (Payments / Stripe): the secret
 * key and the webhook's signing secret. No Stripe library: Checkout needs three
 * calls, made here with curl.
 *
 *   POST /page/<address>          answers kept as a lead marked "payment pending",
 *                                 then a Checkout Session, then 303 to Stripe
 *   GET  /page/<address>/paid     Stripe sends the visitor back here; the session
 *                                 is fetched from Stripe (never trusted from the
 *                                 URL) and, once paid, the lead is completed
 *   POST /stripe-webhook.php      the same completion when the visitor never comes
 *                                 back (closed the tab, slow bank), and the
 *                                 subscription's later life (cancelled, past due)
 *
 * Completion (emails to the visitor and the owner, Lofty) happens exactly once,
 * whichever of the two arrives first: see stripe_complete().
 *
 * Subscriptions: a page can bill monthly or yearly instead of once. Checkout
 * creates the subscription; the webhook keeps its status on the lead. Access is
 * the same signed download link as a one-off purchase.
 */

/** How long a checkout stays open. Stripe's default is 24 hours; closing it after one hour
    means an abandoned checkout reaches Lofty (tagged "Abandoned checkout") while it's fresh.
    Stripe accepts 30 minutes to 24 hours. */
const STRIPE_CHECKOUT_MINUTES = 60;

/** Stripe's smallest charge in US dollars, in cents. */
const STRIPE_MIN_CENTS = 50;

/** How a page can bill: key => label. */
const STRIPE_BILLING = ['once' => 'One-time payment', 'month' => 'Monthly subscription', 'year' => 'Yearly subscription'];

function stripe_key(): string { return trim(setting('stripe.secret')); }
function stripe_whsec(): string { return trim(setting('stripe.whsec')); }

/** Can paid pages take money? (A secret key is enough to sell; the webhook makes it robust.) */
function stripe_ready(): bool {
    return (bool) preg_match('/^(sk|rk)_(test|live)_[A-Za-z0-9]+$/', stripe_key());
}

function stripe_mode(): string {
    return preg_match('/^(sk|rk)_live_/', stripe_key()) ? 'live' : 'test';
}

/** "$99.99", "$9.99 / month". */
function stripe_price_label(int $cents, string $billing = 'once'): string {
    $s = '$' . number_format($cents / 100, $cents % 100 ? 2 : 0);
    return $s . ($billing === 'month' ? ' / month' : ($billing === 'year' ? ' / year' : ''));
}

/** "99.99" or "$1,200" -> cents; anything unreadable -> 0 (free). */
function stripe_parse_price(string $s): int {
    $s = preg_replace('/[^0-9.]/', '', $s);
    if ($s === '' || !is_numeric($s)) return 0;
    return (int) round((float) $s * 100);
}

/**
 * One call to Stripe's API. Returns [ok, http code, decoded body or null, error text].
 * The base URL is a setting only so the QA suite can point it at a local fake.
 */
function stripe_http(string $method, string $path, array $params = []): array {
    $key = stripe_key();
    if ($key === '') return [false, 0, null, 'No Stripe secret key is set (Payments / Stripe in the admin).'];
    $url = rtrim(setting('stripe.api_base', '') ?: 'https://api.stripe.com', '/') . $path;
    $body = http_build_query($params, '', '&');
    if ($method === 'GET' && $body !== '') { $url .= '?' . $body; $body = ''; }
    if (!function_exists('curl_init')) return [false, 0, null, 'The server has no curl extension.'];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key, 'Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 20,
    ]);
    if ($method === 'POST') curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cErr = curl_error($ch);
    curl_close($ch);
    if ($resp === false) return [false, 0, null, 'Could not reach Stripe: ' . $cErr];
    $data = json_decode((string) $resp, true);
    if ($code < 200 || $code >= 300) {
        $msg = is_array($data) ? (string) ($data['error']['message'] ?? '') : '';
        return [false, $code, is_array($data) ? $data : null, 'Stripe said (HTTP ' . $code . '): ' . ($msg !== '' ? $msg : substr((string) $resp, 0, 200))];
    }
    return [true, $code, is_array($data) ? $data : [], ''];
}

/** The admin's "Save & test": a read that any working secret key may make. */
function stripe_test(): array {
    [$ok, , $data, $err] = stripe_http('GET', '/v1/balance');
    if (!$ok) return [false, $err];
    return [true, 'Connected to Stripe (' . stripe_mode() . ' mode).'];
}

/**
 * Start Checkout for a lead on a paid page. Returns [url, error].
 * The lead id travels as client_reference_id and in the metadata, so both the
 * return trip and the webhook find the lead without trusting the visitor.
 */
function stripe_checkout(array $p, int $leadId, string $email): array {
    $sub = $p['billing'] !== 'once';
    $meta = ['page' => $p['slug'], 'lead' => (string) $leadId];
    $params = [
        'mode' => $sub ? 'subscription' : 'payment',
        'line_items' => [[
            'quantity' => 1,
            'price_data' => array_filter([
                'currency' => 'usd',
                'unit_amount' => $p['price_cents'],
                'product_data' => ['name' => mb_substr($p['title'], 0, 250)],
                'recurring' => $sub ? ['interval' => $p['billing']] : null,
            ]),
        ]],
        'customer_email' => $email,
        'client_reference_id' => (string) $leadId,
        'metadata' => $meta,
        // {CHECKOUT_SESSION_ID} is filled in by Stripe; the braces must reach it unencoded-in-meaning.
        'success_url' => abs_url(landing_path($p['slug'], 'paid')) . '?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => abs_url(landing_path($p['slug'])) . '?cancelled=1#form',
        'expires_at' => time() + STRIPE_CHECKOUT_MINUTES * 60,
    ];
    $params[$sub ? 'subscription_data' : 'payment_intent_data'] = ['metadata' => $meta];
    [$ok, , $data, $err] = stripe_http('POST', '/v1/checkout/sessions', $params);
    if (!$ok || empty($data['url'])) return ['', $err ?: 'Stripe gave no checkout address.'];
    stripe_lead_pay($leadId, ['session' => (string) ($data['id'] ?? '')]);
    return [(string) $data['url'], ''];
}

/* ---------- the payment record on a lead (kept in its meta, under "pay") ---------- */

function stripe_lead_pay(int $leadId, array $changes): void {
    $l = lead_get($leadId);
    if (!$l) return;
    $meta = $l['meta'];
    $meta['pay'] = array_merge((array) ($meta['pay'] ?? []), $changes);
    lead_update($leadId, ['meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
}

/**
 * Mark the lead paid and send everything a free page sends at once, exactly once.
 * The switch from "pending" to "paid" is one guarded UPDATE: whichever of the
 * return trip and the webhook gets there second changes no row and sends nothing.
 * Returns ['ok' => bool, 'token' => download token, 'emailed' => bool, 'first' => bool].
 */
function stripe_complete(array $session): array {
    $leadId = (int) ($session['client_reference_id'] ?? ($session['metadata']['lead'] ?? 0));
    $slug = (string) ($session['metadata']['page'] ?? '');
    $paid = in_array($session['payment_status'] ?? '', ['paid', 'no_payment_required'], true);
    $l = $leadId > 0 ? lead_get($leadId) : null;
    if (!$paid || !$l || $l['source_key'] !== 'page:' . $slug) return ['ok' => false, 'token' => '', 'emailed' => false, 'first' => false];

    $pay = (array) ($l['meta']['pay'] ?? []);
    $now = array_merge($pay, array_filter([
        'state' => 'paid', 'paid_at' => date('Y-m-d H:i:s'), 'session' => (string) ($session['id'] ?? ''),
        'amount' => (int) ($session['amount_total'] ?? ($pay['amount'] ?? 0)),
        'currency' => (string) ($session['currency'] ?? 'usd'),
        'intent' => is_string($session['payment_intent'] ?? null) ? $session['payment_intent'] : '',
        'sub' => is_string($session['subscription'] ?? null) ? $session['subscription'] : '',
        'sub_status' => is_string($session['subscription'] ?? null) ? 'active' : '',
        'customer' => is_string($session['customer'] ?? null) ? $session['customer'] : '',
        'live' => !empty($session['livemode']),
    ], fn($v) => $v !== '' && $v !== null));
    $meta = $l['meta'];
    $meta['pay'] = $now;
    $first = false;
    try {
        $st = db()->prepare('UPDATE leads SET meta = ? WHERE id = ? AND meta LIKE ?');
        $st->execute([json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $leadId, '%"state":"pending"%']);
        $first = $st->rowCount() === 1;
    } catch (Throwable $e) {}

    $token = landing_token($slug, $leadId);
    $emailed = false;
    if ($first) {
        // What the buyer's receipt needs (card, Stripe's receipt, the invoice): best effort,
        // so a slow answer from Stripe never holds back the download.
        $det = stripe_receipt_details($session);
        if ($det) stripe_lead_pay($leadId, $det);
        $p = landing_one($slug, true);
        if ($p) $emailed = landing_notify($p, lead_get($leadId) ?: $l, $token, true, stripe_sale_line(array_merge($now, $det)))['emailed'];
    }
    return ['ok' => true, 'token' => $token, 'emailed' => $emailed, 'first' => $first];
}

/* ---------- the receipt ---------- */

/** The order number a buyer can quote: EKP-00042 (the lead's id). */
function stripe_order_no(int $leadId): string {
    return 'EKP-' . str_pad((string) $leadId, 5, '0', STR_PAD_LEFT);
}

/** "Paid $99.99 · Visa •••• 4242" for the owner's email and Lofty. */
function stripe_sale_line(array $pay): string {
    return 'Paid ' . stripe_price_label((int) ($pay['amount'] ?? 0), (string) ($pay['billing'] ?? 'once'))
        . (!empty($pay['card']) ? ' · ' . $pay['card'] : '')
        . (empty($pay['live']) ? ' · TEST MODE, no real money' : '');
}

/**
 * Ask Stripe for what a receipt shows: the card, Stripe's own receipt page, how the
 * charge reads on a card statement and, for a subscription, the invoice. Only fields
 * that every Stripe API version has. Returns the pieces it found ([] if none).
 */
function stripe_receipt_details(array $session): array {
    $charge = null;
    $out = [];
    $pi = is_string($session['payment_intent'] ?? null) ? $session['payment_intent'] : '';
    if (preg_match('/^pi_[A-Za-z0-9]+$/', $pi)) {
        [$ok, , $d] = stripe_http('GET', '/v1/payment_intents/' . $pi, ['expand' => ['latest_charge']]);
        if ($ok && is_array($d['latest_charge'] ?? null)) $charge = $d['latest_charge'];
    }
    $cus = is_string($session['customer'] ?? null) ? $session['customer'] : '';
    if (!$charge && preg_match('/^cus_[A-Za-z0-9]+$/', $cus)) {
        // A subscription's first payment: the customer's newest charge.
        [$ok, , $d] = stripe_http('GET', '/v1/charges', ['customer' => $cus, 'limit' => 1]);
        if ($ok && is_array($d['data'][0] ?? null)) $charge = $d['data'][0];
    }
    if ($charge) {
        $card = (array) ($charge['payment_method_details']['card'] ?? []);
        if (!empty($card['last4'])) {
            $brands = ['visa' => 'Visa', 'mastercard' => 'Mastercard', 'amex' => 'American Express', 'discover' => 'Discover', 'jcb' => 'JCB', 'diners' => 'Diners Club', 'unionpay' => 'UnionPay'];
            $b = (string) ($card['brand'] ?? '');
            $wallet = (string) ($card['wallet']['type'] ?? '');
            $out['card'] = ($brands[$b] ?? ucfirst($b ?: 'Card')) . ' •••• ' . $card['last4']
                . ($wallet === 'apple_pay' ? ' (Apple Pay)' : ($wallet === 'google_pay' ? ' (Google Pay)' : ''));
        } elseif (!empty($charge['payment_method_details']['type'])) {
            $out['card'] = ucwords(str_replace('_', ' ', (string) $charge['payment_method_details']['type']));
        }
        foreach (['receipt_url' => 'receipt_url', 'calculated_statement_descriptor' => 'descriptor'] as $k => $as) {
            if (!empty($charge[$k]) && is_string($charge[$k])) $out[$as] = $charge[$k];
        }
    }
    $inv = is_string($session['invoice'] ?? null) ? $session['invoice'] : '';
    if (preg_match('/^in_[A-Za-z0-9]+$/', $inv)) {
        [$ok, , $d] = stripe_http('GET', '/v1/invoices/' . $inv);
        if ($ok) {
            foreach (['number' => 'invoice_no', 'hosted_invoice_url' => 'invoice_url', 'invoice_pdf' => 'invoice_pdf'] as $k => $as) {
                if (!empty($d[$k]) && is_string($d[$k])) $out[$as] = $d[$k];
            }
        }
    }
    return $out;
}

/* ---------- without the webhook: check pending payments with Stripe ---------- */

/**
 * Ask Stripe about checkouts still marked "payment pending": a buyer who paid and
 * closed the tab before coming back gets their receipt and download, and an expired
 * checkout goes to Lofty as abandoned, just as the webhook would do. Run from the
 * Leads screen, at most once every ten minutes per lead; the webhook makes it
 * unnecessary, but it costs nothing when there's nothing pending.
 * Returns how many leads changed.
 */
function stripe_reconcile(int $max = 10): int {
    if (!stripe_ready()) return 0;
    try {
        leads_table();
        $st = db()->prepare('SELECT * FROM leads WHERE meta LIKE ? AND created >= ? ORDER BY id DESC LIMIT 40');
        $st->execute(['%"state":"pending"%', date('Y-m-d H:i:s', time() - 3 * 86400)]);
        $rows = $st->fetchAll();
    } catch (Throwable $e) { return 0; }
    $changed = 0;
    foreach ($rows as $r) {
        if ($max-- <= 0) break;
        $l = lead_decode($r);
        $pay = (array) ($l['meta']['pay'] ?? []);
        $sid = (string) ($pay['session'] ?? '');
        if (!preg_match('/^cs_[A-Za-z0-9_]+$/', $sid)) continue;
        if (!empty($pay['checked']) && strtotime((string) $pay['checked']) > time() - 600) continue;
        stripe_lead_pay((int) $l['id'], ['checked' => date('Y-m-d H:i:s')]);
        [$ok, , $sess] = stripe_http('GET', '/v1/checkout/sessions/' . $sid);
        if (!$ok || !is_array($sess)) continue;
        if (stripe_complete($sess)['first']) { $changed++; continue; }
        if (($sess['status'] ?? '') === 'expired' && stripe_flip((int) $l['id'], ['state' => 'abandoned', 'ended_at' => date('Y-m-d H:i:s')])) {
            stripe_lofty_unpaid((int) $l['id']);
            $changed++;
        }
    }
    return $changed;
}

/**
 * Move a pending lead to another payment state, once: true only for the call that
 * made the change (Stripe retries events, and two may race).
 */
function stripe_flip(int $leadId, array $changes): bool {
    $l = lead_get($leadId);
    if (!$l || (($l['meta']['pay']['state'] ?? '') !== 'pending')) return false;
    $meta = $l['meta'];
    $meta['pay'] = array_merge((array) $meta['pay'], $changes);
    try {
        $st = db()->prepare('UPDATE leads SET meta = ? WHERE id = ? AND meta LIKE ?');
        $st->execute([json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $leadId, '%"state":"pending"%']);
        return $st->rowCount() === 1;
    } catch (Throwable $e) { return false; }
}

/**
 * Send a checkout that never got paid to Lofty, so it can be followed up: the
 * page's own Lofty settings, plus the tag "Abandoned checkout" (or "Payment
 * failed") and a line saying what happened. Returns [ok|null, detail].
 * Respects the page's "Send these leads to Lofty" switch unless $force.
 */
function stripe_lofty_unpaid(int $leadId, bool $force = false): array {
    $l = lead_get($leadId);
    if (!$l) return [false, 'No such lead.'];
    $pay = (array) ($l['meta']['pay'] ?? []);
    $p = landing_one(substr((string) $l['source_key'], 5), true);
    $crm = $p ? landing_crm($p) : (array) ($l['meta']['crm'] ?? []);
    if ($force) $crm['on'] = true;   // the Leads screen's "Send to Lofty again"
    $tag = ($pay['state'] ?? '') === 'failed' ? 'Payment failed' : 'Abandoned checkout';
    $crm['tags'] = array_values(array_unique(array_merge((array) ($crm['tags'] ?? []), [$tag])));
    $rows = [];
    foreach ((array) $l['answers'] as $a) if (is_array($a) && count($a) === 2) $rows[$a[0]] = $a[1];
    $rows['Checkout'] = ($tag === 'Payment failed' ? 'Payment failed: ' : 'Left without paying: ')
        . stripe_price_label((int) ($pay['amount'] ?? 0), (string) ($pay['billing'] ?? 'once'))
        . ' (started ' . date('M j, g:i a', strtotime((string) ($pay['started'] ?? 'now')) ?: time()) . ')';
    $label = (string) ($l['meta']['form'] ?? $l['source_label']) . ' (' . strtolower($tag) . ')';
    [$ok, $detail] = lead_push_lofty($l, $crm, $label, abs_url((string) $l['page_url']), $rows);
    lead_update($leadId, ['crm_ok' => $ok, 'crm_detail' => $detail . ' · tagged ' . $tag]);
    return [$ok, $detail];
}

/* ---------- the webhook ---------- */

/** Verify Stripe's signature header against the raw body. Five minutes' tolerance, as Stripe's own libraries use. */
function stripe_verify(string $payload, string $header, string $secret, int $tolerance = 300): bool {
    if ($secret === '' || $header === '') return false;
    $t = 0;
    $sigs = [];
    foreach (explode(',', $header) as $part) {
        [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($k === 't') $t = (int) $v;
        if ($k === 'v1') $sigs[] = $v;
    }
    if (!$t || !$sigs || abs(time() - $t) > $tolerance) return false;
    $want = hash_hmac('sha256', $t . '.' . $payload, $secret);
    foreach ($sigs as $s) if (hash_equals($want, $s)) return true;
    return false;
}

/** Find the lead a subscription belongs to. */
function stripe_lead_by_sub(string $subId): ?array {
    if (!preg_match('/^sub_[A-Za-z0-9]+$/', $subId)) return null;
    try {
        leads_table();
        $st = db()->prepare('SELECT * FROM leads WHERE meta LIKE ? ORDER BY id DESC LIMIT 1');
        $st->execute(['%"sub":"' . $subId . '"%']);
        $r = $st->fetch();
        return $r ? lead_decode($r) : null;
    } catch (Throwable $e) { return null; }
}

/** Handle one verified event. Returns a short note for the response body (and the logs). */
function stripe_handle_event(array $ev): string {
    $type = (string) ($ev['type'] ?? '');
    $obj = (array) ($ev['data']['object'] ?? []);
    switch ($type) {
        case 'checkout.session.completed':
        case 'checkout.session.async_payment_succeeded':
            $r = stripe_complete($obj);
            return $r['ok'] ? ($r['first'] ? 'completed' : 'already completed') : 'not paid yet, or not one of ours';
        case 'checkout.session.async_payment_failed':
        case 'checkout.session.expired':
            $id = (int) ($obj['client_reference_id'] ?? 0);
            $state = $type === 'checkout.session.expired' ? 'abandoned' : 'failed';
            if (!$id || !stripe_flip($id, ['state' => $state, 'ended_at' => date('Y-m-d H:i:s')])) return 'nothing to change';
            [$ok] = stripe_lofty_unpaid($id);
            return 'marked ' . $state . ($ok ? ', sent to Lofty' : ($ok === null ? ', Lofty not sent' : ', Lofty failed'));
        case 'customer.subscription.updated':
        case 'customer.subscription.deleted':
            $l = stripe_lead_by_sub((string) ($obj['id'] ?? ''));
            if (!$l) return 'subscription not found';
            $status = $type === 'customer.subscription.deleted' ? 'canceled' : (string) ($obj['status'] ?? '');
            stripe_lead_pay((int) $l['id'], array_filter(['sub_status' => $status, 'sub_cancel_at_end' => !empty($obj['cancel_at_period_end']) ? '1' : '0',
                'sub_period_end' => isset($obj['current_period_end']) ? date('Y-m-d', (int) $obj['current_period_end']) : ''], fn($v) => $v !== ''));
            return 'subscription ' . $status;
    }
    return 'ignored';
}

/** A short line for the Leads screen: [chip class, text] or null for a free lead. */
function stripe_lead_chip(array $l): ?array {
    $pay = (array) ($l['meta']['pay'] ?? []);
    if (!$pay) return null;
    $amt = stripe_price_label((int) ($pay['amount'] ?? 0), (string) ($pay['billing'] ?? 'once'));
    switch ($pay['state'] ?? '') {
        case 'paid':
            if (($pay['sub'] ?? '') !== '') {
                $s = (string) ($pay['sub_status'] ?? 'active');
                return [in_array($s, ['active', 'trialing'], true) ? 'ok' : 'bad', 'Subscribed ' . $amt . ($s !== 'active' ? ' · ' . $s : '')];
            }
            return ['ok', 'Paid ' . $amt];
        case 'pending':   return ['off', 'Payment pending'];
        case 'abandoned': return ['off', 'Left checkout'];
        case 'failed':    return ['bad', 'Payment failed'];
    }
    return null;
}
