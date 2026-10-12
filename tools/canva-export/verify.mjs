// Open the local copies with all outside network blocked; report every request that
// fails or tries to leave, plus console errors; take screenshots.
// Usage: node verify.mjs <shotDir>   (expects servers on 8760 = export root, 8761 = canva-original)
import pw from '/opt/node22/lib/node_modules/playwright/index.js';
import fs from 'node:fs';
const { chromium } = pw;
const SHOT = process.argv[2];
const targets = [
  ['standalone', 'http://127.0.0.1:8760/index.html'],
  ['canva-original', 'http://127.0.0.1:8761/ai-clone-video-services/'],
];
const report = {};
const b = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
for (const [name, url] of targets) {
  for (const [vname, vp] of [['desktop', { width: 1440, height: 900 }], ['phone', { width: 390, height: 844 }]]) {
    const key = `${name}-${vname}`;
    const r = report[key] = { blockedOutside: [], failed: [], consoleErrors: [], served: 0 };
    const ctx = await b.newContext({ viewport: vp });
    await ctx.route('**/*', route => {
      const u = route.request().url();
      if (/^https?:\/\/127\.0\.0\.1:876[01]\//.test(u) || !/^https?:/.test(u)) return route.continue();
      r.blockedOutside.push(u.slice(0, 160));
      return route.abort();
    });
    const page = await ctx.newPage();
    page.on('console', m => { if (m.type() === 'error') r.consoleErrors.push(m.text().slice(0, 200)); });
    page.on('response', res => { r.served++; if (res.status() >= 400) r.failed.push(`${res.status()} ${res.url()}`); });
    page.on('requestfailed', req => { if (!r.blockedOutside.includes(req.url().slice(0, 160))) r.failed.push(`FAILED ${req.url()} ${req.failure()?.errorText}`); });
    await page.goto(url, { waitUntil: 'networkidle', timeout: 120000 });
    await page.waitForTimeout(1500);
    if (name === 'standalone') {
      // reveal-on-scroll sections: scroll through so they animate in, then shoot the whole page
      const h = await page.evaluate(() => document.documentElement.scrollHeight);
      for (let y = 0; y < h; y += 400) { await page.evaluate(yy => window.scrollTo(0, yy), y); await page.waitForTimeout(120); }
      await page.waitForTimeout(800);
      await page.evaluate(() => window.scrollTo(0, 0));
      await page.waitForTimeout(500);
      await page.screenshot({ path: `${SHOT}/${key}-full.png`, fullPage: true });
      r.textSample = (await page.evaluate(() => document.body.innerText)).replace(/\s+/g, ' ').slice(0, 300);
      r.fontsLoaded = await page.evaluate(async () => { await document.fonts.ready; return [...document.fonts].filter(f => f.status === 'loaded').map(f => f.family + ' ' + f.weight + ' ' + f.style); });
      r.iconsDrawn = await page.evaluate(() => document.querySelectorAll('svg.lucide, svg[class*="lucide"]').length);
      r.tailwindStyled = await page.evaluate(() => getComputedStyle(document.body).margin);
    }
    await page.screenshot({ path: `${SHOT}/${key}-top.png` });
    await ctx.close();
  }
}
await b.close();
fs.writeFileSync(`${SHOT}/verify-report.json`, JSON.stringify(report, null, 1));
for (const [k, r] of Object.entries(report)) {
  console.log(`${k}: served=${r.served} failed=${r.failed.length} outside=${r.blockedOutside.length} consoleErrors=${r.consoleErrors.length}`);
  for (const f of r.failed) console.log('   FAIL', f.slice(0, 180));
  for (const f of [...new Set(r.blockedOutside)]) console.log('   OUT ', f);
  for (const f of [...new Set(r.consoleErrors)].slice(0, 5)) console.log('   ERR ', f);
  if (r.fontsLoaded) console.log('   fonts', [...new Set(r.fontsLoaded)].length, 'icons', r.iconsDrawn, '|', r.textSample.slice(0, 120));
}
