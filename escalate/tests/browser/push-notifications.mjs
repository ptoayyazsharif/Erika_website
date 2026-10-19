/**
 * Does the worker actually show an announcement, and does a second one sit
 * beside the first rather than replacing it?
 *
 * The tag is the whole question. The daily reminder deliberately reuses one tag
 * so three unread nudges never stack; an announcement passes its own so news
 * does not silently swallow the news before it. Both halves are asserted here,
 * because getting the tag right for one and wrong for the other is the failure.
 *
 * Run: php artisan serve --port=8123 &  node <this>
 */
import { chromium } from 'playwright';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8123';
const ORIGIN = new URL(BASE).origin;

const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
const context = await browser.newContext();
await context.grantPermissions(['notifications'], { origin: ORIGIN });
const page = await context.newPage();

await page.goto(BASE + '/login');
await page.waitForFunction(() => navigator.serviceWorker.controller !== null, null, { timeout: 20000 });

const cdp = await context.newCDPSession(page);
await cdp.send('ServiceWorker.enable');

// The registration id CDP wants comes from its own worker registry, not the page.
const registrationId = await new Promise((resolve, reject) => {
  const timer = setTimeout(() => reject(new Error('no registration reported')), 10000);
  cdp.on('ServiceWorker.workerRegistrationUpdated', ({ registrations }) => {
    const mine = registrations.find(r => r.scopeURL.startsWith(ORIGIN));
    if (mine) { clearTimeout(timer); resolve(mine.registrationId); }
  });
  cdp.send('ServiceWorker.enable');
});

const deliver = data => cdp.send('ServiceWorker.deliverPushMessage', {
  origin: ORIGIN, registrationId, data: JSON.stringify(data),
});

const shown = () => page.evaluate(() => navigator.serviceWorker.ready
  .then(r => r.getNotifications())
  .then(list => list.map(n => ({ title: n.title, body: n.body, tag: n.tag, url: n.data && n.data.url }))));

const close = () => page.evaluate(() => navigator.serviceWorker.ready
  .then(r => r.getNotifications()).then(l => l.forEach(n => n.close())));

const settle = () => page.waitForTimeout(400);

let failures = 0;
const check = (name, ok, detail) => {
  console.log(`${ok ? 'PASS' : 'FAIL'}  ${name}${ok ? '' : '\n      ' + detail}`);
  if (!ok) failures++;
};

/* 1. An announcement is shown, with its real words. */
await deliver({
  title: 'The beta ends Friday',
  body: 'Thank you — the survey is on Today. Please fill it in before you go.',
  url: BASE + '/today',
  tag: 'escalate-announcement-7',
});
await settle();
let list = await shown();
check('an announcement is shown, with its title and body',
  list.length === 1 && list[0].title === 'The beta ends Friday' && list[0].body.startsWith('Thank you'),
  JSON.stringify(list));

/* 2. A second announcement sits beside it. */
await deliver({
  title: 'Affirmation cards are live',
  body: 'Open My Cards to see today’s.',
  url: BASE + '/today',
  tag: 'escalate-announcement-8',
});
await settle();
list = await shown();
check('a second announcement does not replace the first', list.length === 2, JSON.stringify(list));

/* 3. The same announcement twice replaces itself. */
await deliver({ title: 'Affirmation cards are live', body: 'Open My Cards to see today’s.', url: BASE + '/today', tag: 'escalate-announcement-8' });
await settle();
list = await shown();
check('re-sending the same announcement does not stack it', list.length === 2, JSON.stringify(list));

await close();
await settle();

/* 4. The daily reminder still collapses. */
for (let i = 0; i < 3; i++) {
  await deliver({ title: 'Escalate', body: 'A few minutes for today?', url: BASE + '/today', tag: 'escalate-reminder' });
  await settle();
}
list = await shown();
check('three daily reminders are still one notification', list.length === 1, JSON.stringify(list));

/* 5. A payload with no tag at all — an older send — still shows. */
await close();
await settle();
await deliver({ title: 'Escalate', body: 'A few minutes for today?', url: BASE + '/today' });
await settle();
list = await shown();
check('a payload with no tag still shows, under the reminder tag',
  list.length === 1 && list[0].tag === 'escalate-reminder', JSON.stringify(list));

/* 6. Nonsense is not a crash. */
await close();
await settle();
await cdp.send('ServiceWorker.deliverPushMessage', { origin: ORIGIN, registrationId, data: 'not json at all' });
await settle();
list = await shown();
check('a malformed payload still shows the fallback', list.length === 1 && list[0].title === 'Escalate', JSON.stringify(list));

await browser.close();
console.log(failures === 0 ? '\nAll checks passed.' : `\n${failures} failed.`);
process.exit(failures === 0 ? 0 : 1);
