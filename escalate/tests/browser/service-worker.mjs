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

/**
 * The worker as it was before the fix, found in git history.
 *
 * Walks back through commits that touched sw.js and takes the newest one whose
 * copy still has the version-blind match, rather than naming a commit hash that
 * would rot. If history is ever rewritten past that point the scenario cannot
 * be staged, and saying so is better than silently testing nothing.
 */
function brokenWorker() {
  // :(top) anchors the pathspec at the repository root. Without it the path is
  // read relative to the working directory, matches nothing from inside
  // escalate/, and the walk below silently finds no revisions at all.
  const revisions = execSync('git rev-list HEAD -- ":(top)escalate/public/sw.js"')
    .toString().trim().split('\n').filter(Boolean);

  for (const rev of revisions) {
    const source = execSync(`git show ${rev}:escalate/public/sw.js`).toString();
    if (source.includes('ignoreSearch')) return source;
  }

  throw new Error(
    'No commit in history has a sw.js with the version-blind match, so the\n' +
    'poisoned-client scenario cannot be staged. Skipping it would be a false pass.'
  );
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

  // Be a phone that installed the broken worker, against a pristine app.js —
  // the previous scenario leaves its own marker in the file.
  fs.writeFileSync(APP_JS, appJs);
  fs.writeFileSync(SW, brokenWorker());
  await page.goto(BASE + '/login');
  await page.waitForFunction(() => navigator.serviceWorker.controller !== null, null, { timeout: 15000 });

  // Confirm it really is pinned, so a pass below means something. "Pinned" is
  // "the build just shipped did not arrive" — not "nothing arrived", since a
  // pinned client happily serves whatever stale build it already holds.
  fs.writeFileSync(APP_JS, appJs + "\nwindow.__SHIPPED = 'fix-one';\n");
  await page.goto(BASE + '/login');
  await page.reload();
  await page.waitForTimeout(1200);
  const duringOutage = await shipped(page);
  results.push([
    'the old worker really does pin the client (staging check)',
    duringOutage !== 'fix-one',
    `the client received the new build (${duringOutage}), so this scenario proves nothing`,
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

/* Use the browser the environment already ships when it has one, so this does
   not depend on the installed playwright package pinning the same build. */
const executablePath = fs.existsSync('/opt/pw-browsers/chromium')
  ? '/opt/pw-browsers/chromium'
  : undefined;

const browser = await chromium.launch({ executablePath });

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
