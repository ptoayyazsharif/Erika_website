/**
 * The four things reported broken, clicked the way a person clicks them.
 *
 * This file exists because the suite was green, the deployed JavaScript was
 * correct, and the buttons still did nothing. Every check here is a real click
 * on a real element followed by "did the screen actually change", because that
 * is the only question that was ever being asked.
 *
 * Run it:  php artisan serve --port=8123 &  node tests/browser/journey.mjs
 * Against production:  BASE_URL=https://… node tests/browser/journey.mjs
 */
import { chromium } from 'playwright';
import fs from 'fs';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8123';
const email = `qa${Date.now()}@escalate.test`;
const password = 'correct-horse-battery-1';

const results = [];
const check = (name, pass, detail = '') => results.push([name, pass, detail]);

const executablePath = fs.existsSync('/opt/pw-browsers/chromium')
  ? '/opt/pw-browsers/chromium'
  : undefined;

const browser = await chromium.launch({ executablePath });
// A phone, because that is where it was reported.
const page = await (await browser.newContext({
  viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true,
})).newPage();

const jsErrors = [];
page.on('pageerror', e => jsErrors.push(String(e.message)));
page.on('console', m => { if (m.type() === 'error') jsErrors.push(m.text()); });

try {
  // ── sign up ────────────────────────────────────────────────────────────
  await page.goto(BASE + '/register');
  await page.fill('#name', 'Qa Tester');
  await page.fill('#email', email);
  await page.fill('#password', password);
  await page.fill('#password_confirmation', password);
  // The consent boxes are opacity:0 behind their labels; a person clicks the
  // label, so force past Playwright's visibility check rather than the markup.
  for (const box of await page.locator('input[type=checkbox]').all()) {
    await box.check({ force: true }).catch(() => {});
  }
  await page.click('button[type=submit]');
  await page.waitForLoadState('networkidle');

  check('signing up lands in the app', !page.url().includes('/register'), page.url());

  // ── My World ───────────────────────────────────────────────────────────
  await page.goto(BASE + '/world');
  await page.waitForLoadState('networkidle');

  check('app.js is running at all', await page.evaluate(() => typeof window.Escalate?.toast === 'function'),
    'nothing on the page that needs JavaScript can work');

  // "Add someone"
  const before = await page.locator('[data-circle] [data-person]').count();
  await page.locator('[data-add-person]').click();
  await page.waitForTimeout(250);
  const afterPerson = await page.locator('[data-circle] [data-person]').count();
  check('"Add someone" adds a person', afterPerson > before, `${before} -> ${afterPerson} rows`);

  // "Add a detail"
  const person = page.locator('[data-circle] [data-person]').first();
  const notesBefore = await person.locator('[data-notes] input').count();
  await person.locator('[data-add-note]').click();
  await page.waitForTimeout(250);
  const notesAfter = await person.locator('[data-notes] input').count();
  check('"Add a detail" adds a field', notesAfter > notesBefore, `${notesBefore} -> ${notesAfter} fields`);

  // "What matters most" — typed, and nothing covering it
  const value = page.locator('input[name="values[]"]').first();
  await value.click();
  await page.keyboard.type('Freedom');
  check('"What matters most" accepts typing', (await value.inputValue()) === 'Freedom');
  const covered = await page.evaluate(() => {
    const el = document.querySelector('input[name="values[]"]');
    const r = el.getBoundingClientRect();
    return document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2) !== el;
  });
  check('"What matters most" is not covered by anything', !covered);
  check('"What matters most" reads as fields, not as choices',
    await page.locator('input[name="values[]"][placeholder^="e.g."]').count() > 0,
    'placeholders show bare words, which look like preset options');

  // ── save, and confirm it came back ─────────────────────────────────────
  await page.fill('[data-circle] [data-person] input[name$="[name]"]', 'Maya');
  await page.fill('[data-circle] [data-person] input[name$="[relationship]"]', 'my daughter');
  await page.locator('form button[type=submit]').first().click();
  await page.waitForLoadState('networkidle');

  check('the circle persisted',
    (await page.locator('[data-circle] [data-person] input[name$="[name]"]').first().inputValue()) === 'Maya');
  check('the value persisted',
    (await page.locator('input[name="values[]"]').first().inputValue()) === 'Freedom');

  // ── the desire form ────────────────────────────────────────────────────
  await page.goto(BASE + '/desires/create');
  await page.waitForLoadState('networkidle');

  // Feelings chips: on, and — the part that was reported — off again.
  const chip = page.locator('.chips .chip').first();
  await chip.click();
  await page.waitForTimeout(200);
  const on = await chip.evaluate(el => ({
    shown: el.classList.contains('is-on'), checked: !!el.querySelector('input:checked'),
  }));
  check('a feeling turns on', on.checked);
  check('a feeling LOOKS on when it is on', on.shown, 'the box is ticked but the chip looks untouched');

  await chip.click();
  await page.waitForTimeout(200);
  const off = await chip.evaluate(el => ({
    shown: el.classList.contains('is-on'), checked: !!el.querySelector('input:checked'),
  }));
  check('a feeling turns back off', !off.checked && !off.shown,
    `checked=${off.checked} shown=${off.shown}`);

  // Tagging people
  const people = page.locator('.option:has(input[name="people_involved[]"])');
  check('someone from the circle is offered to tag', await people.count() > 0,
    'the circle has Maya, but nobody is listed');

  if (await people.count() > 0) {
    const tag = people.first();
    await tag.click();
    await page.waitForTimeout(200);
    const tagged = await tag.evaluate(el => ({
      shown: el.classList.contains('is-on') || !!el.querySelector('input:checked'),
      checked: !!el.querySelector('input:checked'),
    }));
    check('tagging a person works', tagged.checked);
    check('a tagged person LOOKS tagged', tagged.shown);

    await tag.click();
    await page.waitForTimeout(200);
    check('a tagged person can be untagged',
      !(await tag.evaluate(el => !!el.querySelector('input:checked'))));
  }

  check('no JavaScript errors anywhere in that journey', jsErrors.length === 0, jsErrors.join(' | '));
} finally {
  await browser.close();
}

let failed = 0;
for (const [name, pass, detail] of results) {
  console.log(`${pass ? 'PASS' : 'FAIL'}  ${name}`);
  if (!pass && detail) { console.log(`      ${detail}`); }
  if (!pass) failed++;
}
console.log(`\n${results.length - failed}/${results.length} passed`);
process.exit(failed ? 1 : 0);
