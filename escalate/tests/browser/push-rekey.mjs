/**
 * Does a device repair itself after the keys are changed from the admin panel?
 *
 * This is the half of "Generate a new pair" that makes the button safe. A
 * subscription is bound to the public key it was made with; change the pair and
 * every phone is silently unreachable, and stays that way, because the
 * permission card only ever offers itself where permission is still undecided.
 *
 * ── The honest limit ────────────────────────────────────────────────────────
 *
 * pushManager.subscribe() talks to a real push service, which this sandbox
 * cannot reach. So the PushManager is stubbed and what is asserted is the
 * decision app.js makes: with which key, in which of the three states, and
 * whether it tells the server. The bytes going to FCM are not exercised here —
 * that is the hop a human confirms on a real phone.
 *
 * Run: php artisan serve --port=8123 &  node tests/browser/push-rekey.mjs
 */
import { chromium } from 'playwright';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8123';
const EMAIL = process.env.QA_EMAIL ?? 'shot@escalate.test';
const PASSWORD = process.env.QA_PASSWORD ?? 'a-long-enough-password-1';

/** The stub, installed before any page script runs. */
function stub({ permission, heldKey }) {
  const calls = { subscribed: [], unsubscribed: 0, posted: [] };
  window.__push = calls;

  const bytes = (b64) => {
    const padded = (b64 + '='.repeat((4 - (b64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(padded);
    const out = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
    return out;
  };

  // heldKey: null = no subscription; false = one whose key the browser will
  // not report, which some engines do; a string = a subscription on that key.
  let existing = heldKey === null ? null : {
    endpoint: 'https://fcm.googleapis.com/wp/stub-old',
    options: heldKey === false ? {} : { applicationServerKey: bytes(heldKey).buffer },
    toJSON: () => ({ keys: { p256dh: 'old-p', auth: 'old-a' } }),
    unsubscribe: async () => { calls.unsubscribed++; existing = null; return true; },
  };

  const registration = {
    pushManager: {
      getSubscription: async () => existing,
      subscribe: async ({ applicationServerKey }) => {
        calls.subscribed.push(btoa(String.fromCharCode(...new Uint8Array(applicationServerKey)))
          .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, ''));
        existing = {
          endpoint: 'https://fcm.googleapis.com/wp/stub-new',
          options: { applicationServerKey },
          toJSON: () => ({ keys: { p256dh: 'new-p', auth: 'new-a' } }),
          unsubscribe: async () => true,
        };
        return existing;
      },
    },
  };

  Object.defineProperty(navigator, 'serviceWorker', {
    configurable: true,
    get: () => ({
      ready: Promise.resolve(registration),
      register: async () => registration,
      controller: {},
      addEventListener: () => {},
    }),
  });

  window.Notification = class {
    static permission = permission;
    static async requestPermission() { return permission; }
  };

  const realFetch = window.fetch.bind(window);
  window.fetch = async (url, init) => {
    if (String(url).includes('/push/subscribe') || (init && init.method === 'POST' && String(url).includes('push'))) {
      calls.posted.push(JSON.parse(init.body));
      return new Response('{}', { status: 200, headers: { 'Content-Type': 'application/json' } });
    }
    return realFetch(url, init);
  };
}

const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });

async function signedInPage(state) {
  const context = await browser.newContext();
  const page = await context.newPage();

  await page.goto(BASE + '/login');
  await page.fill('#email', EMAIL);
  await page.fill('#password', PASSWORD);
  await page.click('button[type=submit]');
  await page.waitForLoadState('networkidle');

  await context.addInitScript(stub, state);
  await page.goto(BASE + '/today');
  await page.waitForTimeout(700);

  return page;
}

let failures = 0;
const check = (name, ok, detail) => {
  console.log(`${ok ? 'PASS' : 'FAIL'}  ${name}${ok ? '' : '\n      ' + detail}`);
  if (!ok) failures++;
};

// The key the server is currently handing out.
const probe = await signedInPage({ permission: 'default', heldKey: null });
const serverKey = await probe.evaluate(() => document.querySelector('[data-push-prompt]')?.dataset.pushKey ?? null);
if (!serverKey) {
  console.error('No push prompt on /today — is push configured and are reminders on for this account?');
  process.exit(2);
}

/* 1. A device holding a stale key re-subscribes with the current one. */
{
  const page = await signedInPage({ permission: 'granted', heldKey: 'BOoldkeyoldkeyoldkeyoldkeyoldkeyoldkey' });
  const calls = await page.evaluate(() => window.__push);
  check('a device on an old key unsubscribes and re-subscribes with the new one',
    calls.unsubscribed === 1 && calls.subscribed.length === 1 && calls.subscribed[0] === serverKey,
    JSON.stringify(calls));
  check('and the server is told about the new subscription',
    calls.posted.length === 1 && calls.posted[0].endpoint.endsWith('stub-new') && !!calls.posted[0].timezone,
    JSON.stringify(calls.posted));
}

/* 2. A device already on the current key is left completely alone. */
{
  const page = await signedInPage({ permission: 'granted', heldKey: serverKey });
  const calls = await page.evaluate(() => window.__push);
  check('a device already on the current key is not churned',
    calls.unsubscribed === 0 && calls.subscribed.length === 0 && calls.posted.length === 0,
    JSON.stringify(calls));
}

/* 3. Permission never granted: nothing is subscribed behind their back. */
{
  const page = await signedInPage({ permission: 'default', heldKey: null });
  const calls = await page.evaluate(() => window.__push);
  const offered = await page.isVisible('[data-push-prompt]');
  check('nobody who has not agreed is subscribed silently',
    calls.subscribed.length === 0 && calls.posted.length === 0, JSON.stringify(calls));
  check('and they are still offered the card instead', offered, 'the prompt did not show');
}

/* 4. Permission refused: still nothing, and no card. */
{
  const page = await signedInPage({ permission: 'denied', heldKey: null });
  const calls = await page.evaluate(() => window.__push);
  check('a refusal is left alone', calls.subscribed.length === 0 && calls.posted.length === 0, JSON.stringify(calls));
}

/* 5. Granted, but the browser will not say which key the subscription holds.
      Leave it alone rather than churning the table on every page load. */
{
  const page = await signedInPage({ permission: 'granted', heldKey: false });
  const calls = await page.evaluate(() => window.__push);
  check('a subscription whose key cannot be read is left alone, not churned',
    calls.subscribed.length === 0 && calls.unsubscribed === 0 && calls.posted.length === 0,
    JSON.stringify(calls));
}

await browser.close();
console.log(failures === 0 ? '\nAll checks passed.' : `\n${failures} failed.`);
process.exit(failures === 0 ? 0 : 1);
