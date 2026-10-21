// Round 3: the Stripe paywall, against stripe_mock.py on 127.0.0.1:8798 (run by run.sh).
// Setup screen, a paid page from the editor, a sale end to end, a cancelled checkout,
// a replayed return link, the closed-tab case finished by the webhook (once), bad
// signatures, forged return links, a monthly subscription and its cancellation, and
// what happens when the keys are removed.
import pw from '/opt/node22/lib/node_modules/playwright/index.js'; const { chromium } = pw;
import fs from 'fs';
import crypto from 'crypto';
const S = process.env.QA_DIR;
const B = process.env.QA_BASE || 'http://127.0.0.1:8000';
const shots = S + '/shots'; fs.mkdirSync(shots, { recursive: true });
const WHSEC = 'whsec_qa123';
let fails = 0;
const ok = (c, m, d = '') => { console.log((c ? 'PASS ' : 'FAIL ') + m + (d ? '  — ' + d : '')); if (!c) fails++; };
const lines = f => fs.existsSync(f) ? fs.readFileSync(f, 'utf8').trim().split('\n').filter(Boolean).map(JSON.parse) : [];
const mails = () => lines(S + '/mails.jsonl');
const stripeLog = () => lines(S + '/stripe.jsonl');
const sign = (body, t = Math.floor(Date.now() / 1000), secret = WHSEC) =>
  `t=${t},v1=${crypto.createHmac('sha256', secret).update(`${t}.${body}`).digest('hex')}`;

const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' });
const a = await (await browser.newContext({ viewport: { width: 1440, height: 1000 } })).newPage();
const jsErrors = []; a.on('pageerror', e => jsErrors.push(e.message));
a.on('dialog', d => d.accept());
await a.goto(B + '/admin.php');
await a.fill('input[name=username]', process.env.QA_USER); await a.fill('input[name=password]', process.env.QA_PASS);
await a.click('button'); await a.waitForLoadState();

/* ---------- Payments / Stripe ---------- */
await a.goto(B + '/admin.php?page=stripe');
ok(await a.locator('text=Not set up').count() === 1, 'Stripe screen says "Not set up" before keys');
ok(await a.locator('input[readonly][value$="/stripe-webhook.php"]').count() === 1, 'webhook address shown to copy');
await a.fill('input[name=stripe_secret]', 'pk_test_abc'); await a.click('button[value=save]'); await a.waitForLoadState();
ok(await a.locator('.err:has-text("publishable key")').count() === 1, 'a publishable key is refused with an explanation');
await a.fill('input[name=stripe_secret]', 'sk_test_wrong'); await a.click('button[value=test]'); await a.waitForLoadState();
ok(await a.locator('.err:has-text("test failed")').count() === 1, 'a wrong key fails the connection test', await a.locator('.err').first().textContent().catch(() => ''));
await a.fill('input[name=stripe_secret]', 'sk_test_qa123'); await a.fill('input[name=stripe_whsec]', WHSEC);
await a.click('button[value=test]'); await a.waitForLoadState();
ok(await a.locator('.flash:has-text("Connected to Stripe (test mode)")').count() === 1, 'the right key connects (test mode)');
ok(await a.locator('text=Ready to take payments').count() === 1 && await a.locator('text=Test mode').count() >= 1, 'status: ready, test mode');
await a.screenshot({ path: shots + '/stripe-settings.png', fullPage: true });

/* ---------- a paid page, from the editor ---------- */
async function makePage(title, slug, price, billing) {
  await a.goto(B + '/admin.php?page=landing-pages&edit=new');
  await a.fill('#lp_title', title); await a.fill('#lp_slug', slug);
  await a.locator('#lp-desc-editor .ql-editor').fill('Everything a new agent needs.');
  await a.fill('#lp_price', price); await a.selectOption('#lp_billing', billing);
  await a.click('input[value=url]'); await a.fill('input[name=lp_url]', 'https://example.com/' + slug);
  await a.click('button[value=publish]'); await a.waitForLoadState();
}
await a.goto(B + '/admin.php?page=landing-pages&edit=new');
await a.fill('#lp_title', 'Too Cheap'); await a.fill('#lp_slug', 'too-cheap'); await a.fill('#lp_price', '0.30');
await a.click('input[value=url]'); await a.fill('input[name=lp_url]', 'https://example.com/x');
await a.click('button[value=publish]'); await a.waitForLoadState();
ok(await a.locator('.err:has-text("at least $0.50")').count() === 1, 'a price under Stripe\'s minimum is refused');

await makePage('The Modern Agent Success Blueprint', 'agent-blueprint', '99.99', 'once');
ok(await a.locator('.flash:has-text("Published")').count() === 1, 'paid page published', await a.locator('.flash,.err').first().textContent().catch(() => ''));
ok((await a.locator('#pv-price').textContent()) === '$99.99' && await a.locator('#pv-total').isVisible(), 'phone preview shows the total');
await a.screenshot({ path: shots + '/stripe-editor.png', fullPage: true });

/* ---------- a visitor buys it ---------- */
const v = await (await browser.newContext({ viewport: { width: 390, height: 844 } })).newPage();
async function fillAndSend(name, email) {
  await v.goto(B + '/page/agent-blueprint');
  await v.fill('#lp-name', name); await v.fill('#lp-email', email); await v.fill('#lp-mobile', '404-555-0100');
  await v.evaluate(() => { document.querySelector('input[name=_t]').value = String(Math.floor(Date.now() / 1000) - 30); });
  await v.click('#lp-send'); await v.waitForLoadState();
}
await v.goto(B + '/page/agent-blueprint');
ok((await v.locator('.lp-total b').textContent()) === '$99.99', 'the page shows Total $99.99');
ok(await v.locator('.lp-secure:has-text("Stripe")').count() === 1, 'secure-payment note under the button');
await v.screenshot({ path: shots + '/stripe-page-phone.png', fullPage: true });
const sw = await v.evaluate(() => document.documentElement.scrollWidth); ok(sw <= 390, 'no sideways scroll on a phone', String(sw));

await fillAndSend('Casey Cancel', 'cancel@example.test');
ok(/127\.0\.0\.1:8798\/pay\/cs_test_/.test(v.url()), 'form sends the visitor to Stripe Checkout', v.url());
let req = stripeLog().at(-1);
ok(req && req.mode === 'payment' && req['line_items[0][price_data][unit_amount]'] === '9999' && req['line_items[0][price_data][currency]'] === 'usd', 'checkout: one-time, 9999 cents, usd');
ok(req && req.customer_email === 'cancel@example.test' && /^\d+$/.test(req.client_reference_id) && req['metadata[page]'] === 'agent-blueprint', 'checkout: email, lead id, page');
ok(req && req.success_url.endsWith('/page/agent-blueprint/paid?session_id={CHECKOUT_SESSION_ID}'), 'checkout: return address keeps {CHECKOUT_SESSION_ID}', req && req.success_url);
await v.click('#cancel'); await v.waitForLoadState();
ok(await v.locator('text=Payment cancelled. Nothing was charged').count() === 1, 'cancel brings them back with a clear note');
ok(!mails().some(m => /cancel@example\.test/.test(JSON.stringify(m))), 'no emails for an unpaid checkout');

const before = mails().length;
await fillAndSend('Pat Payer', 'pat@example.test');
const patSid = v.url().split('/pay/')[1];
await v.click('#pay'); await v.waitForLoadState();
ok(/\/page\/agent-blueprint\/thanks\?t=/.test(v.url()), 'after paying, the thank-you page with the download', v.url());
const dl = await v.locator('#lp-download').getAttribute('href');
const r = await v.request.get(B + dl, { maxRedirects: 0 });
ok(r.status() === 303 && r.headers()['location'] === 'https://example.com/agent-blueprint', 'the download opens the page\'s link');
await new Promise(res => setTimeout(res, 800));
let after = mails().slice(before);
ok(after.filter(m => m.subject.startsWith('Your link: The Modern Agent')).length === 1, 'buyer gets their link by email, once');
ok(after.filter(m => m.subject.includes('New lead: The Modern Agent') && /New sale/.test(m.text) && /Payment: Paid \$99\.99/.test(m.text)).length === 1, 'owner gets one "New sale" email with the payment');

// the return link again (a refresh, a bookmark): the download again, no second round of emails
await v.goto(B + '/page/agent-blueprint/paid?session_id=' + patSid);
ok(/thanks\?t=/.test(v.url()), 'the return link still opens the download');
await new Promise(res => setTimeout(res, 500));
ok(mails().length === before + after.length, 'opening the return link again sends nothing twice');

/* ---------- forged and unpaid return links ---------- */
await v.goto(B + '/page/agent-blueprint/paid?session_id=cs_test_doesnotexist');
ok(/expired=1/.test(v.url()), 'an unknown session id is refused');
await v.goto(B + '/page/agent-blueprint/paid?session_id=<script>');
ok(/expired=1/.test(v.url()), 'a malformed session id is refused');
await fillAndSend('Una Unpaid', 'unpaid@example.test');
const openSid = v.url().split('/pay/')[1];
await v.goto(B + '/page/agent-blueprint/paid?session_id=' + openSid);
ok(/cancelled=1/.test(v.url()), 'an unpaid session does not unlock the file');
ok(!mails().some(m => m.to.includes('unpaid@example.test')), 'and sends nothing');

/* ---------- closed tab: the webhook finishes it ---------- */
await fillAndSend('Tab Closer', 'tab@example.test');
const tabSid = v.url().split('/pay/')[1];
const paidSession = await (await v.request.get('http://127.0.0.1:8798/pay/' + tabSid + '/paid-only')).json();
const ev = JSON.stringify({ id: 'evt_qa1', object: 'event', type: 'checkout.session.completed', livemode: false, data: { object: paidSession } });
let w = await v.request.post(B + '/stripe-webhook.php', { data: ev, headers: { 'Content-Type': 'application/json', 'Stripe-Signature': sign(ev) } });
ok(w.status() === 200 && (await w.text()).trim() === 'completed', 'webhook completes the sale', String(w.status()));
await new Promise(res => setTimeout(res, 500));
ok(mails().filter(m => m.to.includes('tab@example.test')).length === 1, 'buyer who closed the tab still gets the link by email');
w = await v.request.post(B + '/stripe-webhook.php', { data: ev, headers: { 'Content-Type': 'application/json', 'Stripe-Signature': sign(ev) } });
ok((await w.text()).trim() === 'already completed', 'Stripe retrying the event changes nothing');
await v.goto(B + '/page/agent-blueprint/paid?session_id=' + tabSid);
ok(/thanks\?t=/.test(v.url()), 'and if they do come back later, they get the download');
await new Promise(res => setTimeout(res, 300));
ok(mails().filter(m => m.to.includes('tab@example.test')).length === 1, '…without a second email');
w = await v.request.post(B + '/stripe-webhook.php', { data: ev, headers: { 'Content-Type': 'application/json', 'Stripe-Signature': sign(ev, undefined, 'whsec_wrong') } });
ok(w.status() === 400, 'a wrong signature is refused');
w = await v.request.post(B + '/stripe-webhook.php', { data: ev, headers: { 'Content-Type': 'application/json', 'Stripe-Signature': sign(ev, Math.floor(Date.now() / 1000) - 3600) } });
ok(w.status() === 400, 'an hour-old signature is refused (replay)');
w = await v.request.post(B + '/stripe-webhook.php', { data: ev.replace('"livemode":false', '"livemode":true'), headers: { 'Content-Type': 'application/json', 'Stripe-Signature': sign(ev) } });
ok(w.status() === 400, 'a changed body is refused');
w = await v.request.get(B + '/stripe-webhook.php');
ok(w.status() === 405, 'GET on the webhook is refused');

/* ---------- a monthly subscription ---------- */
await makePage('Agent Mastermind Monthly', 'mastermind', '29', 'month');
ok(await a.locator('.flash:has-text("Published")').count() === 1, 'subscription page published');
await v.goto(B + '/page/mastermind');
ok((await v.locator('.lp-total b').textContent()) === '$29 / month' && await v.locator('text=Billed every month until you cancel').count() === 1, 'shows $29 / month and how billing works');
await v.fill('#lp-name', 'Sam Subscriber'); await v.fill('#lp-email', 'sam@example.test'); await v.fill('#lp-mobile', '404-555-0101');
await v.evaluate(() => { document.querySelector('input[name=_t]').value = String(Math.floor(Date.now() / 1000) - 30); });
await v.click('#lp-send'); await v.waitForLoadState();
req = stripeLog().at(-1);
ok(req.mode === 'subscription' && req['line_items[0][price_data][recurring][interval]'] === 'month' && req['line_items[0][price_data][unit_amount]'] === '2900', 'checkout: subscription, monthly, 2900 cents');
ok(req['subscription_data[metadata][lead]'] === req.client_reference_id && !('payment_intent_data[metadata][lead]' in req), 'subscription carries the lead in its own metadata');
await v.click('#pay'); await v.waitForLoadState();
ok(/\/page\/mastermind\/thanks\?t=/.test(v.url()), 'subscriber gets the download');
await a.goto(B + '/admin.php?page=leads');
ok(await a.locator('tr:has-text("Sam Subscriber") .chip.ok:has-text("Subscribed $29 / month")').count() === 1, 'Leads: "Subscribed $29 / month"');
await a.click('text=Sam Subscriber'); await a.waitForLoadState();
const subHref = await a.locator('a:has-text("Subscription in Stripe")').getAttribute('href');
const subId = subHref.split('/').pop();
ok(/^https:\/\/dashboard\.stripe\.com\/test\/subscriptions\/sub_/.test(subHref), 'lead links to the subscription in Stripe (test)');
const cancelEv = JSON.stringify({ id: 'evt_qa2', object: 'event', type: 'customer.subscription.deleted', livemode: false, data: { object: { id: subId, object: 'subscription', status: 'canceled' } } });
w = await v.request.post(B + '/stripe-webhook.php', { data: cancelEv, headers: { 'Content-Type': 'application/json', 'Stripe-Signature': sign(cancelEv) } });
ok((await w.text()).trim() === 'subscription canceled', 'webhook records the cancellation');
await a.goto(B + '/admin.php?page=leads');
ok(await a.locator('tr:has-text("Sam Subscriber") .chip.bad:has-text("canceled")').count() === 1, 'Leads: subscription shows canceled');

/* ---------- Leads ---------- */
ok(await a.locator('tr:has-text("Pat Payer") .chip.ok:has-text("Paid $99.99")').count() === 1, 'Leads: "Paid $99.99"');
ok(await a.locator('tr:has-text("Casey Cancel") .chip:has-text("Payment pending")').count() === 1, 'Leads: the cancelled checkout shows "Payment pending"');
await a.screenshot({ path: shots + '/stripe-leads.png', fullPage: true });
const expEv = JSON.stringify({ id: 'evt_qa3', object: 'event', type: 'checkout.session.expired', livemode: false, data: { object: { id: 'cs_x', client_reference_id: stripeLog().find(x => x.customer_email === 'cancel@example.test').client_reference_id } } });
w = await v.request.post(B + '/stripe-webhook.php', { data: expEv, headers: { 'Content-Type': 'application/json', 'Stripe-Signature': sign(expEv) } });
await a.reload();
ok(await a.locator('tr:has-text("Casey Cancel") .chip:has-text("Left checkout")').count() === 1, 'an expired checkout shows "Left checkout"');
const [csv] = await Promise.all([a.waitForEvent('download'), a.goto(B + '/admin.php?page=leads&export=csv').catch(() => {})]);
const csvText = fs.readFileSync(await csv.path(), 'utf8');
ok(csvText.includes(',Payment,') && csvText.includes('Paid $99.99'), 'CSV has a Payment column');

/* ---------- keys removed ---------- */
await a.goto(B + '/admin.php?page=stripe');
await a.check('input[name=stripe_clear]'); await a.click('button[value=save]'); await a.waitForLoadState();
ok(await a.locator('text=Not set up').count() === 1, 'keys removed');
let resp = await v.goto(B + '/page/agent-blueprint');
ok(resp.status() === 404, 'a paid page hides itself when Stripe is not set up', String(resp.status()));
await a.goto(B + '/admin.php?page=landing-pages');
ok(await a.locator('tr:has-text("Modern Agent") .chip:has-text("Hidden · needs Stripe")').count() === 1, 'the list says why: "Hidden · needs Stripe"');
resp = await v.goto(B + '/page/1039-mariners-court');
ok(resp.status() === 200, 'free pages are unaffected', String(resp.status()));
ok(jsErrors.length === 0, 'no JavaScript errors in the admin', jsErrors.join(' | '));

await browser.close();
console.log(fails ? `\n${fails} FAILED` : '\nALL PASS');
process.exit(fails ? 1 : 0);
