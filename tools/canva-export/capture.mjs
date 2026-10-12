// Capture a live page and everything it loads, in headless Chromium (used for the
// Canva page export, 12 Oct 2026 — see CLAUDE.md 'Exports').
// Every request is relayed through curl (TLS verified against the proxy CA bundle),
// saved under OUT (same-site paths as-is; other hosts under _external/<host>/), and
// served back to the browser. Usage: node capture.mjs <url> <outDir> <shotPrefix>
import pw from '/opt/node22/lib/node_modules/playwright/index.js';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';

const { chromium } = pw;
const [url, OUT, SHOT] = process.argv.slice(2);
const SITE = new URL(url).host;
const CA = '/root/.ccr/ca-bundle.crt';
const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';
const TMP = fs.mkdtempSync(path.join(process.env.TMPDIR || '/tmp', 'cap-'));
const log = [];
const redirected = {};

function localPath(u) {
  const x = new URL(u);
  let p = decodeURIComponent(x.pathname);
  // signed tokens make path pieces too long for a filename: shorten them to a hash
  p = p.split('/').map(seg => seg.length > 100 ? 'tok-' + crypto.createHash('md5').update(seg).digest('hex').slice(0, 10) : seg).join('/');
  if (p.endsWith('/')) p += 'index.html';
  else if (!path.extname(p)) p += '/index.html';
  const rel = x.host === SITE ? p : `/_external/${x.host}${p}`;
  return path.join(OUT, rel + (x.search && x.host !== SITE ? '__' + crypto.createHash('md5').update(x.search).digest('hex').slice(0, 8) : ''));
}

function fetchViaCurl(u, method, body, reqHeaders = {}) {
  const bodyF = path.join(TMP, 'b'), headF = path.join(TMP, 'h');
  const jar = path.join(TMP, 'jar');
  const args = ['-sS', '-L', '--max-time', '120', '--cacert', CA, '-A', UA, '-c', jar, '-b', jar, '-D', headF, '-o', bodyF, '-w', '%{http_code} %{url_effective}', '-X', method];
  // forward what the browser sent (Sec-Fetch-*, Referer, Accept…): Canva's codelet answers
  // 404 to a bare request and 200 to an iframe navigation
  for (const [k, v] of Object.entries(reqHeaders)) {
    if (/^(host|content-length|user-agent|accept-encoding|connection|cookie)$/i.test(k)) continue;
    args.push('-H', `${k}: ${v}`);
  }
  if (body) { fs.writeFileSync(path.join(TMP, 'req'), body); args.push('--data-binary', '@' + path.join(TMP, 'req')); }
  args.push(u);
  let code = '000';
  for (let i = 0; i < 3; i++) {
    try { code = execFileSync('curl', args, { encoding: 'utf8' }).trim(); } catch { code = '000'; }
    if (!code.startsWith('000')) break;
  }
  const raw = fs.existsSync(headF) ? fs.readFileSync(headF, 'utf8') : '';
  const blocks = raw.split(/\r?\n\r?\n/).filter(b => /^HTTP\//.test(b));
  const last = blocks[blocks.length - 1] || '';
  const headers = {};
  for (const line of last.split(/\r?\n/).slice(1)) {
    const i = line.indexOf(':'); if (i > 0) headers[line.slice(0, i).toLowerCase()] = line.slice(i + 1).trim();
  }
  const buf = fs.existsSync(bodyF) ? fs.readFileSync(bodyF) : Buffer.alloc(0);
  const [c, eff] = code.split(' ');
  return { code: Number(c), finalUrl: eff || u, headers, buf };
}

const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
for (const [name, vp] of [['desktop', { width: 1440, height: 900 }], ['phone', { width: 390, height: 844 }]]) {
  const ctx = await browser.newContext({ viewport: vp, userAgent: UA + (name === 'phone' ? ' Mobile' : '') });
  await ctx.route('**/*', async route => {
    const req = route.request();
    const u = req.url();
    if (!/^https?:/.test(u)) return route.continue();
    const hdrs = { ...(await req.allHeaders()) };
    // Chromium adds Sec-Fetch-* after interception, so the relay never sees them; Canva's
    // codelet answers 404 without them. Rebuild them from what Playwright tells us.
    const rt = req.resourceType();
    const isFrameNav = req.isNavigationRequest() && req.frame() !== req.frame().page().mainFrame();
    const dest = req.isNavigationRequest() ? (isFrameNav ? 'iframe' : 'document')
      : ({ script: 'script', stylesheet: 'style', image: 'image', font: 'font', media: 'video' }[rt] || 'empty');
    hdrs['sec-fetch-dest'] = dest;
    hdrs['sec-fetch-mode'] = req.isNavigationRequest() ? 'navigate' : (dest === 'empty' || dest === 'font' ? 'cors' : 'no-cors');
    hdrs['sec-fetch-site'] = 'same-origin';
    const r = fetchViaCurl(u, req.method(), req.postDataBuffer(), hdrs);
    log.push({ view: name, method: req.method(), url: u, final: r.finalUrl, code: r.code, type: r.headers['content-type'] || '', bytes: r.buf.length });
    if (req.method() === 'GET' && r.code >= 200 && r.code < 300) {
      const f = localPath(u);
      fs.mkdirSync(path.dirname(f), { recursive: true });
      fs.writeFileSync(f, r.buf);
    }
    const keep = {};
    // pass redirects to the browser (no curl -L), so relative URLs resolve against the
    // page they really came from (the codelet redirects to <id>.canvacode.com)
    for (const k of ['content-type', 'cache-control', 'access-control-allow-origin']) if (r.headers[k]) keep[k] = r.headers[k];
    if (!keep['access-control-allow-origin']) keep['access-control-allow-origin'] = '*';
    // A redirected HTML page is served at the URL the browser asked for, so give it a
    // <base> at its real address; otherwise its root-relative links ("/codelet/…") hit
    // the wrong host. The saved file (above) stays byte-identical to what was served.
    let body = r.buf;
    if (r.finalUrl && r.finalUrl !== u && /text\/html/.test(keep['content-type'] || '')) {
      const html = r.buf.toString('utf8');
      body = Buffer.from(html.replace(/<head([^>]*)>/i, `<head$1><base href="${r.finalUrl}">`), 'utf8');
      redirected[u] = r.finalUrl;
    }
    return route.fulfill({ status: r.code || 502, headers: keep, body });
  });
  const page = await ctx.newPage();
  await page.goto(url, { waitUntil: 'networkidle', timeout: 180000 });
  // scroll the whole page so lazy media loads
  const h = await page.evaluate(() => document.documentElement.scrollHeight);
  for (let y = 0; y < h + vp.height; y += Math.floor(vp.height / 2)) {
    await page.evaluate(yy => window.scrollTo(0, yy), y);
    await page.waitForTimeout(400);
  }
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(2000);
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForTimeout(800);
  await page.screenshot({ path: `${SHOT}-${name}.png`, fullPage: true });
  const outLinks = await page.evaluate(() => [...document.querySelectorAll('a[href], iframe[src]')].map(e => (e.tagName + ' ' + (e.href || e.src) + ' | ' + (e.innerText || e.title || '').trim().slice(0, 60))));
  fs.writeFileSync(`${SHOT}-${name}-links.txt`, [...new Set(outLinks)].join('\n'));
  await ctx.close();
}
await browser.close();
fs.writeFileSync(path.join(OUT, '..', 'capture-log.json'), JSON.stringify(log, null, 1));
fs.writeFileSync(path.join(OUT, '..', 'redirects.json'), JSON.stringify(redirected, null, 1));
console.log('requests:', log.length);
const byHost = {};
for (const l of log) { const k = new URL(l.url).host + ' ' + l.code; byHost[k] = (byHost[k] || 0) + 1; }
console.log(byHost);
