/**
 * The still frame behind a YouTube episode video, 1920x1080.
 *
 *   node tools/youtube/frame.js <spec.json> <out.png>
 *
 * spec: { art, eyebrow, footer, note }. The episode's square art (show name and
 * title already on it) fills the left 1080x1080; the right is the Bold cover's
 * merlot panel, with the episode number at the top and the link at the foot. The
 * middle is left empty: tools/youtube/episode.py burns the captions into it.
 * Like tools/covers/blog.js, it fails rather than write a frame whose text overflows.
 */
const fs = require('fs');
const os = require('os');
const path = require('path');

let pw;
try { pw = require('playwright'); } catch (e) { pw = require('playwright-core'); }

const ROOT = path.join(__dirname, '..', '..');
const CHROME = process.env.CHROME || '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';
const C = { ink: '#453230', cream: '#FBF7F3', gold: '#C9A15E', merlotDeep: '#B05E51' };
const esc = s => String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
const url = p => 'file://' + (path.isAbsolute(p) ? p : path.join(ROOT, p));

(async () => {
  const [specFile, out] = process.argv.slice(2);
  if (!out) { console.error('usage: frame.js <spec.json> <out.png>'); process.exit(2); }
  const s = JSON.parse(fs.readFileSync(specFile, 'utf8'));
  const html = `<!doctype html><html><head><meta charset="utf-8"><style>
    *{margin:0;padding:0;box-sizing:border-box}
    html,body{width:1920px;height:1080px;overflow:hidden;background:${C.merlotDeep}}
    .eyebrow{font-family:'Archivo',sans-serif;font-weight:700;text-transform:uppercase;letter-spacing:.22em}
    .sub{font-family:'Archivo',sans-serif;font-weight:400}
    img{display:block}
  </style></head><body>
    <img src="${url(s.art)}" alt="" style="position:absolute;left:0;top:0;width:1080px;height:1080px">
    <div style="position:absolute;left:1080px;top:0;width:840px;height:1080px;padding:64px 70px 56px">
      <div class="eyebrow" style="font-size:20px;color:${C.gold}">${esc(s.eyebrow)}</div>
      <div style="height:4px;width:96px;background:${C.gold};margin-top:22px"></div>
      <div class="fit" style="position:absolute;left:70px;right:70px;bottom:56px;max-height:110px;overflow:hidden">
        ${s.note ? `<div class="sub" style="font-size:20px;line-height:1.4;color:${C.cream};opacity:.75;margin-bottom:10px">${esc(s.note)}</div>` : ''}
        <div class="sub" style="font-size:24px;color:${C.cream};opacity:.92">${esc(s.footer)}</div>
      </div>
    </div>
  </body></html>`;
  const tmp = path.join(os.tmpdir(), `yt-frame-${process.pid}.html`);
  fs.writeFileSync(tmp, html);
  const browser = await pw.chromium.launch({ executablePath: fs.existsSync(CHROME) ? CHROME : undefined });
  const page = await browser.newPage({ viewport: { width: 1920, height: 1080 }, deviceScaleFactor: 1 });
  await page.goto('file://' + tmp, { waitUntil: 'load' });
  await page.evaluate(() => document.fonts.ready);
  const problems = await page.evaluate(() => {
    const p = [];
    for (const img of document.images) if (!img.naturalWidth) p.push('picture failed to load: ' + img.src);
    for (const el of document.querySelectorAll('.fit'))
      if (el.scrollHeight > el.clientHeight + 1) p.push(`text overflows: "${el.textContent.trim().slice(0, 40)}…"`);
    return p;
  });
  fs.unlinkSync(tmp);
  if (problems.length) { console.error('FAIL ' + problems.join('\n  ')); await browser.close(); process.exit(1); }
  await page.screenshot({ path: out, type: 'png' });
  await browser.close();
  console.log('  ok   ' + out);
})();
