import pw from '/opt/node22/lib/node_modules/playwright/index.js'; const { chromium } = pw;
import fs from 'fs';
const S = process.env.QA_DIR;
const B = process.env.QA_BASE || 'http://127.0.0.1:8000';
const shots = S + '/shots'; fs.mkdirSync(shots, { recursive: true });
let fails = 0;
const ok = (c, m, d = '') => { console.log((c ? 'PASS ' : 'FAIL ') + m + (d ? '  — ' + d : '')); if (!c) fails++; };

const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' });
const admin = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
const a = await admin.newPage();
const jsErrors = [];
a.on('pageerror', e => jsErrors.push(e.message));

// log in
await a.goto(B + '/admin.php');
await a.fill('input[name=username]', process.env.QA_USER);
await a.fill('input[name=password]', process.env.QA_PASS);
await a.click('button');
await a.waitForLoadState();
ok(await a.locator('text=Landing pages').count() > 0, 'admin sidebar has Landing pages');

// the list
await a.goto(B + '/admin.php?page=landing-pages');
ok(await a.locator('text=Get the 1039 Mariners Court Investor Package').count() === 1, 'Mariners page listed');
ok(await a.locator('tr:has-text("1039 Mariners") >> text=Draft · needs its file').count() === 1, 'Mariners shows "needs its file"');
await a.screenshot({ path: shots + '/admin-list.png', fullPage: true });

// the editor
await a.click('text=Get the 1039 Mariners Court Investor Package');
await a.waitForSelector('#fb-list .fb-card');
ok(await a.locator('#fb-list .fb-card').count() === 6, 'six questions loaded', String(await a.locator('#fb-list .fb-card').count()));
ok((await a.locator('#pv-title').textContent()) === 'Get the 1039 Mariners Court Investor Package', 'preview title');
ok((await a.locator('#pv-fields select').count()) === 4, 'preview has the 4 dropdowns');
ok((await a.locator('#pv-cta').textContent()) === 'Send Me the Investor Package', 'preview CTA');
await a.screenshot({ path: shots + '/admin-editor.png', fullPage: true });

// publish without the file -> stays a draft, with a warning
await a.click('button[value=publish]');
await a.waitForLoadState();
ok(await a.locator('.err:has-text("Saved as a draft: add the file")').count() === 1, 'publish without file is refused with a warning');

// add picture + PDF, publish
await a.setInputFiles('#lp_image', S + '/house.jpg');
await a.setInputFiles('#lp_file', S + '/1039 Mariners Merged.pdf');
ok(await a.locator('#pv-hero').isVisible(), 'picture appears in the preview before saving');
await a.click('button[value=publish]');
await a.waitForLoadState();
const flash = await a.locator('.flash').textContent().catch(() => '');
ok(/Published/.test(flash), 'published', flash.trim());
ok(await a.locator('text=1039 Mariners Merged.pdf').count() >= 1, 'file name shown after upload');
// admin download of the private file
const [dl] = await Promise.all([a.waitForEvent('download'), a.click('a:has-text("Download")')]);
const dlPath = await dl.path();
ok(fs.readFileSync(dlPath).equals(fs.readFileSync(S + '/1039 Mariners Merged.pdf')), 'admin download is the same file');
ok(jsErrors.length === 0, 'no JavaScript errors in the admin', jsErrors.join(' | '));

// visitor
const vis = await browser.newContext({ viewport: { width: 1440, height: 1000 }, acceptDownloads: true });
const v = await vis.newPage();
const vErr = []; v.on('pageerror', e => vErr.push(e.message));
let r = await v.goto(B + '/page/1039-mariners-court');
ok(r.status() === 200, 'public page 200 once live', String(r.status()));
ok(await v.locator('h1').textContent() === 'Get the 1039 Mariners Court Investor Package', 'public title');
ok(await v.locator('select').count() === 4 && await v.locator('input[type=checkbox]').count() === 1, '4 dropdowns + agreement checkbox');
ok(await v.locator('meta[name=robots][content*=noindex]').count() === 1, 'noindex by default');
await v.waitForTimeout(500);
await v.screenshot({ path: shots + '/public-desktop.png', fullPage: true });

// server-side validation (bypass the browser's own required checks)
await v.evaluate(() => { document.getElementById('lp-form').noValidate = true; document.querySelector('input[name=_t]').value = String(Math.floor(Date.now() / 1000) - 30); });
await v.click('#lp-send');
await v.waitForLoadState();
ok(await v.locator('.lp-bad').count() === 8, 'empty submit: 8 fields marked', String(await v.locator('.lp-bad').count()));
ok(await v.locator('.lp-errors').count() === 1, 'error summary shown');

// fill it in properly
await v.fill('#lp-name', 'Jordan Q. Investor');
await v.fill('#lp-email', 'jordan@example.test');
await v.fill('#lp-mobile', '(404) 555-0134');
await v.selectOption('#lp-strategy', 'Buy & Hold');
await v.selectOption('#lp-funding', 'DSCR');
await v.selectOption('#lp-timeline', '15–21 days');
await v.selectOption('#lp-pof', 'Yes, within 24 hours');
await v.check('input[name="a[access][]"]');
await v.evaluate(() => { document.querySelector('input[name=_t]').value = String(Math.floor(Date.now() / 1000) - 30); });
await v.click('#lp-send');
await v.waitForURL(/\/page\/1039-mariners-court\/thanks\?t=/);
ok(true, 'redirected to the thank-you page');
ok(await v.locator('text=Your investor package is ready.').count() === 1, 'thank-you heading');
ok(await v.locator('text=We\'ve also emailed you this link').count() === 1, 'says the link was emailed');
await v.screenshot({ path: shots + '/public-thanks.png', fullPage: true });
const [vd] = await Promise.all([v.waitForEvent('download'), v.click('#lp-download')]);
ok(vd.suggestedFilename() === '1039 Mariners Merged.pdf', 'download filename', vd.suggestedFilename());
ok(fs.readFileSync(await vd.path()).equals(fs.readFileSync(S + '/1039 Mariners Merged.pdf')), 'visitor download is byte-identical');
fs.writeFileSync(S + '/thanks-url.txt', v.url());
ok(vErr.length === 0, 'no JavaScript errors on the public page', vErr.join(' | '));

// phone
const ph = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true });
const p = await ph.newPage();
await p.goto(B + '/page/1039-mariners-court');
await p.waitForTimeout(500);
const overflow = await p.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
ok(overflow <= 0, 'no sideways scroll on a phone', String(overflow));
await p.screenshot({ path: shots + '/public-phone.png', fullPage: true });

await browser.close();
console.log(fails ? `\n${fails} FAILED` : '\nALL PASS');
