import pw from '/opt/node22/lib/node_modules/playwright/index.js'; const { chromium } = pw;
import fs from 'fs';
const S = process.env.QA_DIR;
const B = process.env.QA_BASE || 'http://127.0.0.1:8000';
const shots = S + '/shots';
let fails = 0;
const ok = (c, m, d = '') => { console.log((c ? 'PASS ' : 'FAIL ') + m + (d ? '  — ' + d : '')); if (!c) fails++; };
const lines = f => fs.existsSync(f) ? fs.readFileSync(f, 'utf8').trim().split('\n').filter(Boolean).map(JSON.parse) : [];

const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' });
const ctx = await browser.newContext({ viewport: { width: 1440, height: 1000 }, acceptDownloads: true });
const a = await ctx.newPage();
const jsErrors = []; a.on('pageerror', e => jsErrors.push(e.message));
a.on('dialog', d => d.accept());
await a.goto(B + '/admin.php');
await a.fill('input[name=username]', process.env.QA_USER); await a.fill('input[name=password]', process.env.QA_PASS); await a.click('button'); await a.waitForLoadState();

/* ---------- Leads screen ---------- */
await a.goto(B + '/admin.php?page=leads');
ok(await a.locator('text=Jordan Q. Investor').count() === 1, 'lead listed');
ok(await a.locator('.chip:has-text("In Lofty")').count() >= 1 && await a.locator('.chip:has-text("Emailed")').count() >= 1 && await a.locator('.chip:has-text("Downloaded")').count() >= 1, 'lead chips: emailed, in Lofty, downloaded');
await a.screenshot({ path: shots + '/admin-leads.png', fullPage: true });
await a.click('text=Jordan Q. Investor');
await a.waitForLoadState();
ok(await a.locator('dd:has-text("DSCR")').count() === 1, 'detail shows answers');
ok(await a.locator('a[href="tel:4045550134"]').count() === 1, 'phone is a call link');
await a.screenshot({ path: shots + '/admin-lead.png', fullPage: true });

// CSV
const [csv] = await Promise.all([a.waitForEvent('download'), a.goto(B + '/admin.php?page=leads&export=csv').catch(() => {})]);
const csvText = fs.readFileSync(await csv.path(), 'utf8');
ok(csvText.startsWith('﻿Received,Source,Name') && csvText.includes('Jordan Q. Investor') && csvText.includes('How do you plan to fund the purchase?: DSCR'), 'CSV export has the lead and answers');

/* ---------- Lofty fails, then retry ---------- */
fs.writeFileSync(S + '/lofty.jsonl.fail', '1');
const v = await (await browser.newContext()).newPage();
async function submitMariners(name, email) {
  await v.goto(B + '/page/1039-mariners-court');
  await v.fill('#lp-name', name); await v.fill('#lp-email', email); await v.fill('#lp-mobile', '770-555-0199');
  for (const [id, val] of [['strategy', 'Flip'], ['funding', 'Cash'], ['timeline', '7–14 days'], ['pof', 'Yes, immediately']]) await v.selectOption('#lp-' + id, val);
  await v.check('input[name="a[access][]"]');
  await v.evaluate(() => { document.querySelector('input[name=_t]').value = String(Math.floor(Date.now() / 1000) - 30); });
  await v.click('#lp-send'); await v.waitForLoadState();
}
await submitMariners('Casey Cashbuyer', 'casey@example.test');
ok(/\/thanks\?t=/.test(v.url()), 'visitor still gets the file when Lofty is down');
fs.unlinkSync(S + '/lofty.jsonl.fail');
await a.goto(B + '/admin.php?page=leads');
ok(await a.locator('tr:has-text("Casey Cashbuyer") .chip:has-text("Lofty failed")').count() === 1, 'failed Lofty push is flagged');
await a.click('text=Casey Cashbuyer'); await a.waitForLoadState();
await a.click('button:has-text("Send to Lofty again")'); await a.waitForLoadState();
ok(await a.locator('.flash:has-text("Sent to Lofty")').count() === 1, 'retry succeeds', await a.locator('.flash,.err').first().textContent().catch(() => ''));
ok(await a.locator('.chip:has-text("In Lofty")').count() === 1, 'chip now In Lofty');

/* ---------- tampered and missing links ---------- */
const thanks = fs.readFileSync(S + '/thanks-url.txt', 'utf8');
const tok = new URL(thanks).searchParams.get('t');
const bad = tok.slice(0, -1) + (tok.endsWith('a') ? 'b' : 'a');
let r = await v.goto(B + '/page/1039-mariners-court/download?t=' + encodeURIComponent(bad));
ok(/expired=1/.test(v.url()) && await v.locator('text=That download link has expired').count() === 1, 'tampered link refused');
// a token for one page can't open another
const [body] = tok.split('.');
const forged = Buffer.from('other-page|1|9999999999').toString('base64url') + '.' + tok.split('.')[1];
r = await v.goto(B + '/page/1039-mariners-court/download?t=' + forged);
ok(/expired=1/.test(v.url()), 'forged payload refused');
r = await v.goto(B + '/page/1039-mariners-court/thanks');
ok(/expired=1/.test(v.url()), 'thanks page without a link sends back to the form');

/* ---------- private folder ---------- */
const files = fs.readdirSync(process.env.SITE + '/uploads/private').filter(f => /\.pdf$/.test(f));
r = await v.goto(B + '/uploads/private/' + files[0]);
ok(r.status() === 403, 'private file refused by direct URL', String(r.status()));
r = await v.goto(B + '/uploads/%70rivate/' + files[0]);
ok(r.status() === 403, 'private file refused with an encoded path', String(r.status()));

/* ---------- a new page, built from scratch ---------- */
await a.goto(B + '/admin.php?page=landing-pages&edit=new');
await a.fill('#lp_title', 'Seller Prep <b>Checklist</b>');
ok(await a.inputValue('#lp_slug') === 'seller-prep-checklist', 'address follows the title', await a.inputValue('#lp_slug'));
await a.fill('#lp_slug', 'seller-checklist');
await a.locator('#lp-desc-editor .ql-editor').fill('Everything to do before photos.');
// questions: delete the default phone, add a dropdown and a checkbox group and a long answer
await a.locator('#fb-list .fb-card').first().locator('button.del').click();
await a.click('button[data-add=select]');
await a.locator('#fb-list .fb-card').last().locator('.fb-label').fill('<script>alert(1)</script>When are you listing?');
await a.locator('#fb-list .fb-card').last().locator('.fb-opt input').first().fill('This month');
await a.locator('#fb-list .fb-card').last().locator('.fb-addopt').click();
await a.locator('#fb-list .fb-card').last().locator('.fb-opt input').last().fill('Later');
await a.click('button[data-add=checkbox]');
await a.locator('#fb-list .fb-card').last().locator('.fb-label').fill('Which rooms need work?');
await a.locator('#fb-list .fb-card').last().locator('.fb-opt textarea').first().fill('Kitchen');
await a.locator('#fb-list .fb-card').last().locator('.fb-addopt').click();
await a.locator('#fb-list .fb-card').last().locator('.fb-opt textarea').last().fill('Bathrooms');
await a.click('button[data-add=textarea]');
await a.locator('#fb-list .fb-card').last().locator('.fb-label').fill('Anything else?');
await a.locator('#fb-list .fb-card').last().locator('.fb-foot input').uncheck();
// move the long answer to the top
await a.locator('#fb-list .fb-card').last().locator('button[title="Move up"]').click();
await a.locator('#fb-list .fb-card').nth(1).locator('button[title="Move up"]').click();
ok((await a.locator('#fb-list .fb-card').first().locator('.fb-label').inputValue()) === 'Anything else?', 'reordering works');
ok(await a.locator('#pv-fields .lp-field').count() === 3, 'preview follows the builder');
// a dropdown with no options is caught
await a.click('button[data-add=select]');
await a.locator('#fb-list .fb-card').last().locator('.fb-label').fill('Empty one');
await a.locator('#fb-list .fb-card').last().locator('.fb-opt button').click();
await a.click('input[value=url]');
await a.fill('input[name=lp_url]', 'https://example.com/checklist');
await a.click('button[value=publish]'); await a.waitForLoadState();
ok(await a.locator('.err:has-text("needs at least one option")').count() === 1, 'empty dropdown refused, nothing saved');
ok((await a.inputValue('input[name=lp_title]')) === 'Seller Prep Checklist' && await a.locator('#fb-list .fb-card').count() === 4, 'what was typed is kept after an error');
await a.locator('#fb-list .fb-card').last().locator('button.del').click();
await a.click('button[value=publish]'); await a.waitForLoadState();
ok(await a.locator('.flash:has-text("Published")').count() === 1, 'new page published', await a.locator('.flash,.err').first().textContent().catch(() => ''));
ok(jsErrors.length === 0, 'no JavaScript errors in the editor', jsErrors.join(' | '));

// it renders safely and works with a link
r = await v.goto(B + '/page/seller-checklist');
ok(r.status() === 200, 'new page live');
ok(await v.locator('h1').textContent() === 'Seller Prep <b>Checklist</b>' || await v.locator('h1').textContent() === 'Seller Prep Checklist', 'title shown as text, not markup', await v.locator('h1').textContent());
ok(await v.locator('script:has-text("alert(1)")').count() === 0 && await v.locator('label:has-text("When are you listing?")').count() === 1, 'script in a question is not run');
// multi-checkbox required: none ticked -> server says choose one
await v.fill('#lp-name', 'Sam Seller'); await v.fill('#lp-email', 'sam@example.test');
await v.selectOption('select', 'Later');
await v.evaluate(() => { document.getElementById('lp-form').noValidate = true; document.querySelector('input[name=_t]').value = String(Math.floor(Date.now() / 1000) - 30); });
await v.click('#lp-send'); await v.waitForLoadState();
ok(await v.locator('.lp-msg:has-text("Please choose at least one.")').count() === 1, 'checkbox group needs one ticked');
ok(await v.inputValue('#lp-name') === 'Sam Seller', 'answers kept after an error');
await v.check('text=Bathrooms');
await v.evaluate(() => { document.querySelector('input[name=_t]').value = String(Math.floor(Date.now() / 1000) - 30); });
await v.click('#lp-send'); await v.waitForLoadState();
const dl = await v.locator('#lp-download').getAttribute('href');
const resp = await v.request.get(B + dl, { maxRedirects: 0 });
ok(resp.status() === 303 && resp.headers()['location'] === 'https://example.com/checklist', 'link page sends the visitor to the link');

/* ---------- unpublish, duplicate, delete ---------- */
await a.goto(B + '/admin.php?page=landing-pages&edit=seller-checklist');
await a.click('button:has-text("Unpublish")'); await a.waitForLoadState();
r = await v.goto(B + '/page/seller-checklist');
ok(r.status() === 404, 'unpublished page is gone for visitors', String(r.status()));
r = await a.goto(B + '/page/seller-checklist');
ok(r.status() === 200 && await a.locator('.lp-draft').count() === 1, 'admin still sees it, with the draft banner');
await a.goto(B + '/admin.php?page=landing-pages');
await a.locator('tr:has-text("Seller Prep") button:has-text("Duplicate")').click(); await a.waitForLoadState();
ok(/edit=seller-checklist-copy/.test(a.url()), 'duplicate opens the copy');
await a.click('#lpe-del'); await a.waitForLoadState();
await a.goto(B + '/admin.php?page=landing-pages&edit=seller-checklist');
await a.click('#lpe-del'); await a.waitForLoadState();
ok(await a.locator('.lpa-table tr:has-text("Seller Prep")').count() === 0, 'both deleted');
await a.goto(B + '/admin.php?page=leads');
ok(await a.locator('text=Sam Seller').count() === 1, 'its leads stay after the page is deleted');

/* ---------- XSS through the admin's own list ---------- */
ok(!(await a.content()).includes('<script>alert(1)'), 'no raw script anywhere in Leads');

await browser.close();
console.log(fails ? `\n${fails} FAILED` : '\nALL PASS');
