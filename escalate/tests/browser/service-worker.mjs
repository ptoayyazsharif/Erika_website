/**
 * Proof that a deploy actually reaches an installed browser.
 *
 * The PHPUnit guard in tests/Feature/ServiceWorkerTest.php can only read the
 * worker's source. This runs the real thing: a real browser, a real service
 * worker registration, a real deploy simulated by changing app.js on disk so
 * asset_v() stamps it with a new content hash.
 *
 * Two scenarios, because they fail independently:
 *
 *   fresh    — a new visitor installs the worker, a deploy happens, do they
 *              get the new build?
 *   poisoned — a visitor who already installed the BROKEN worker (the state of
 *              every phone before this fix): do they heal on their own, or do
 *              they need to clear site data by hand?
 *
 * Run it:  php artisan serve --port=8123 &  node tests/browser/service-worker.mjs
 *
 * 127.0.0.1 is treated as a secure origin, so service workers run without TLS.
 */
import { chromium } from 'playwright';
import { execSync } from 'child_process';
import fs from 'fs';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8123';
const APP_JS = 'public/js/app.js';
const SW = 'public/sw.js';

const fixedSw = fs.readFileSync(SW, 'utf8');
const appJs = fs.readFileSync(APP_JS, 'utf8');

/** The worker as it was before the fix, straight out of git history. */
function brokenWorker() {
  const source = execSync('git show HEAD:escalate/public/sw.js').toString();
  if (!source.includes('ignoreSearch')) {
    throw new Error(
      'HEAD:escalate/public/sw.js no longer contains the bug, so the poisoned-client\n' +
      'scenario cannot be staged from it. Point this at the commit before the fix.'
    );
  }
  return source;
}

/** window.__SHIPPED, tolerating the self-heal reload firing mid-read. */
async function shipped(page) {
  for (let attempt = 0; attempt < 3; attempt++) {
    try {
      return await page.evaluate(() => window.__SHIPPED ?? null);
    } catch {
      await page.waitForLoadState('load').catch(() => {});
    }
  }
  return null;
}

const results = [];

async function fresh(browser) {
  const page = await (await browser.newContext()).newPage();

  await page.goto(BASE + '/login');
  await page.waitForFunction(() => navigator.serviceWorker.controller !== null, null, { timeout: 15000 });

  fs.writeFileSync(APP_JS, appJs + "\nwindow.__SHIPPED = 'new-build';\n");

  await page.goto(BASE + '/login');
  await page.reload();
  await page.waitForTimeout(1500);

  const got = await shipped(page);
  results.push([
    'a deploy reaches a browser that installed the app',
    got === 'new-build',
    got ?? 'the browser ran the OLD build',
  ]);
}

async function poisoned(browser) {
  const page = await (await browser.newContext()).newPage();

  // Be a phone that installed the broken worker.
  fs.writeFileSync(SW, brokenWorker());
  await page.goto(BASE + '/login');
  await page.waitForFunction(() => navigator.serviceWorker.controller !== null, null, { timeout: 15000 });

  // Confirm it really is stuck, so a pass below means something.
  fs.writeFileSync(APP_JS, appJs + "\nwindow.__SHIPPED = 'fix-one';\n");
  await page.goto(BASE + '/login');
  await page.reload();
  await page.waitForTimeout(1200);
  results.push([
    'the old worker really does pin the client (staging check)',
    (await shipped(page)) === null,
    'the client was not stuck, so this scenario proves nothing',
  ]);

  // Now deploy the repaired worker. The user does nothing but keep tapping.
  fs.writeFileSync(SW, fixedSw);
  fs.writeFileSync(APP_JS, appJs + "\nwindow.__SHIPPED = 'fix-two';\n");

  let got = null;
  for (let visit = 1; visit <= 4 && got !== 'fix-two'; visit++) {
    await page.goto(BASE + '/login');
    await page.waitForTimeout(1500);
    await page.waitForLoadState('load').catch(() => {});
    got = await shipped(page);
  }

  results.push([
    'a pinned client heals itself through ordinary use',
    got === 'fix-two',
    got ?? 'still pinned — users would have to clear site data by hand',
  ]);

  const caches = await page.evaluate(() => caches.keys());
  results.push([
    'the poisoned escalate-v1-shell cache is deleted',
    !caches.includes('escalate-v1-shell'),
    JSON.stringify(caches),
  ]);
}

const browser = await chromium.launch();

try {
  await fresh(browser);
  await poisoned(browser);
} finally {
  fs.writeFileSync(SW, fixedSw);
  fs.writeFileSync(APP_JS, appJs);
  await browser.close();
}

let failed = 0;
for (const [name, pass, detail] of results) {
  console.log(`${pass ? 'PASS' : 'FAIL'}  ${name}`);
  if (!pass) { console.log(`      ${detail}`); failed++; }
}
console.log(`\n${results.length - failed}/${results.length} passed`);
process.exit(failed ? 1 : 0);
