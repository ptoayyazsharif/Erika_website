// Full-length screenshots of every page of a Canva site, live or from the local copy, and
// (local) a list of every request that failed or tried to leave the copy.
//   node verify-site.mjs live  https://escaluxe.com   <outDir> <page,page,...>
//   node verify-site.mjs local http://127.0.0.1:8770  <outDir> <page,page,...>
// Pages are paths ('' = home). Canva pages scroll inside one inner DIV, not the document,
// so fullPage screenshots only show the first screen: the scroller is scrolled through
// (lazy photos), then expanded to its full height for the shot.
import pw from '/opt/node22/lib/node_modules/playwright/index.js';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';

const [mode, BASE, OUT, pagesArg] = process.argv.slice(2);
const PAGES = pagesArg.split(',');
const CA = '/root/.ccr/ca-bundle.crt';
const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';
const TMP = fs.mkdtempSync(path.join(os.tmpdir(), 'vs-'));
fs.mkdirSync(OUT, { recursive: true });

function relay(u, method, body, hdrs) {
  const bodyF = path.join(TMP, 'b'), headF = path.join(TMP, 'h');
  const a = ['-sS', '-L', '--max-time', '120', '--cacert', CA, '-A', UA, '-D', headF, '-o', bodyF, '-w', '%{http_code}', '-X', method];
  for (const [k, v] of Object.entries(hdrs)) if (!/^(host|content-length|user-agent|accept-encoding|connection|cookie)$/i.test(k)) a.push('-H', `${k}: ${v}`);
  if (body) { fs.writeFileSync(path.join(TMP, 'q'), body); a.push('--data-binary', '@' + path.join(TMP, 'q')); }
  a.push(u);
  let code = '000';
  for (let i = 0; i < 3 && code === '000'; i++) { try { code = execFileSync('curl', a, { encoding: 'utf8' }).trim(); } catch { code = '000'; } }
  const raw = fs.existsSync(headF) ? fs.readFileSync(headF, 'utf8') : '';
  const last = raw.split(/\r?\n\r?\n/).filter(x => /^HTTP\//.test(x)).pop() || '';
  const ct = (last.match(/^content-type:\s*(.+)$/im) || [])[1];
  return { code: Number(code) || 502, ct, buf: fs.existsSync(bodyF) ? fs.readFileSync(bodyF) : Buffer.alloc(0) };
}

const report = {};
const b = await pw.chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
for (const [view, vp] of [['desktop', { width: 1440, height: 900 }], ['phone', { width: 390, height: 844 }]]) {
  const ctx = await b.newContext({ viewport: vp, userAgent: UA + (view === 'phone' ? ' Mobile' : '') });
  const cur = { failed: [], outside: [] };
  await ctx.route('**/*', async route => {
    const req = route.request(), u = req.url();
    if (!/^https?:/.test(u)) return route.continue();
    if (mode === 'local') {
      if (u.startsWith(BASE + '/')) return route.continue();
      cur.outside.push(u.slice(0, 150));
      return route.abort();
    }
    const r = relay(u, req.method(), req.postDataBuffer(), { ...(await req.allHeaders()), 'sec-fetch-site': 'same-origin',
      'sec-fetch-dest': req.isNavigationRequest() ? (req.frame() === req.frame().page().mainFrame() ? 'document' : 'iframe') : 'empty',
      'sec-fetch-mode': req.isNavigationRequest() ? 'navigate' : 'cors' });
    return route.fulfill({ status: r.code, headers: { 'content-type': r.ct || 'application/octet-stream', 'access-control-allow-origin': '*' }, body: r.buf });
  });
  for (const pg of PAGES) {
    const key = `${pg || 'home'}-${view}`;
    cur.failed = []; cur.outside = [];
    const page = await ctx.newPage();
    page.on('response', res => { if (res.status() >= 400) cur.failed.push(`${res.status()} ${res.url().slice(0, 150)}`); });
    page.on('requestfailed', req => { const u = req.url().slice(0, 150); if (!cur.outside.includes(u)) cur.failed.push(`FAILED ${u} ${req.failure()?.errorText}`); });
    await page.goto(`${BASE}/${pg}`, { waitUntil: 'networkidle', timeout: 180000 });
    await page.waitForTimeout(1500);
    const sh = await page.evaluate(() => {
      let best = null;
      for (const e of document.querySelectorAll('*')) {
        const cs = getComputedStyle(e);
        if (/(auto|scroll)/.test(cs.overflowY) && e.scrollHeight > e.clientHeight + 50 && (!best || e.scrollHeight > best.scrollHeight)) best = e;
      }
      if (best) best.setAttribute('data-vs-scroller', '1');
      return best ? best.scrollHeight : document.documentElement.scrollHeight;
    });
    for (let y = 0; y < sh; y += Math.floor(vp.height / 2)) {
      await page.evaluate(yy => { const s = document.querySelector('[data-vs-scroller]'); s ? s.scrollTo(0, yy) : window.scrollTo(0, yy); }, y);
      await page.waitForTimeout(250);
    }
    await page.waitForLoadState('networkidle').catch(() => {});
    await page.waitForTimeout(1500);
    const full = await page.evaluate(() => {
      const s = document.querySelector('[data-vs-scroller]');
      if (!s) return document.documentElement.scrollHeight;
      s.scrollTo(0, 0);
      const top = s.getBoundingClientRect().top;
      const h = s.scrollHeight;
      s.style.height = h + 'px'; s.style.maxHeight = 'none'; s.style.overflow = 'visible';
      document.documentElement.style.height = 'auto'; document.body.style.height = 'auto';
      return Math.ceil(top + h);
    });
    await page.setViewportSize({ width: vp.width, height: Math.min(full, 30000) });
    await page.waitForTimeout(1500);
    await page.screenshot({ path: `${OUT}/${key}.png` });
    report[key] = { title: await page.title(), height: full, failed: [...cur.failed], outside: [...new Set(cur.outside)] };
    await page.close();
  }
  await ctx.close();
}
await b.close();
fs.writeFileSync(`${OUT}/report.json`, JSON.stringify(report, null, 1));
for (const [k, r] of Object.entries(report)) {
  console.log(`${k}: "${r.title}" h=${r.height} failed=${r.failed.length} outside=${r.outside.length}`);
  for (const f of r.failed.slice(0, 6)) console.log('   FAIL', f);
  for (const f of r.outside.slice(0, 6)) console.log('   OUT ', f);
}
