/**
 * The YouTube channel's own images (Studio → Customization → Profile), in the look of the
 * show's art on RSS.com — her preferred image (the owner, 20 Oct 2026):
 * tools/podcast/art/show.jpg, brown backdrop, pink suit, "Erika Explains… with Erika Page"
 * in Playfair Display (her brand system's title face) and gold.
 *
 *   NODE_PATH=/opt/node22/lib/node_modules node tools/youtube/channel.js [outDir]
 *
 * Her picture is the full-size cut-out (tools/podcast/art/erika-cutout.png, made by
 * cutout.py) set on a backdrop drawn here, not a crop of the show art: blending a crop
 * into a drawn background left visible edges and a muddy patch (owner, 20 Oct 2026). The
 * backdrop is one smooth gradient with a fine grain so the dark browns don't band.
 *
 * Writes to exports/youtube/channel/ (git-ignored) unless given a folder:
 *   banner.jpg       2048x1152. YouTube shows all of it only on a TV; a computer shows the
 *                    middle 2048x423 and a phone the middle 1546x423, so the words and her
 *                    face sit inside the 1235x338 safe area in the centre.
 *   picture.jpg      800x800, her face on the brown. YouTube crops it to a circle.
 *   picture-pink.jpg 700x700, the alternative: her face in the original photo, on its pink.
 *   watermark.png    150x150, transparent outside the circle; shown in the corner of
 *                    every video, and a click on it subscribes.
 *   podcast-square.jpg the show art itself, for the "Erika Explains" playlist set as a podcast.
 *   safe-area.jpg    the banner with the phone / computer crops drawn on, to check by eye.
 *   qa-*.jpg         QA sheets: the phone and computer crops, and her edges at 2x.
 *   channel-setup.md every Profile field to paste (kept in tools/youtube/channel-setup.md).
 *
 * Fails rather than write a picture that is upscaled or text that overflows its box.
 */
const fs = require('fs');
const os = require('os');
const path = require('path');

let pw;
try { pw = require('playwright'); } catch (e) { pw = require('playwright-core'); }

const ROOT = path.join(__dirname, '..', '..');
const CHROME = process.env.CHROME || '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';
const OUT = process.argv[2] || path.join(ROOT, 'exports', 'youtube', 'channel');
const SHOW = path.join(ROOT, 'tools', 'podcast', 'art', 'show.jpg');
const FONT = 'file://' + path.join(__dirname, 'fonts', 'PlayfairDisplay-latin.woff2');

const CUT = path.join(ROOT, 'tools', 'podcast', 'art', 'erika-cutout.png');   // 1022x1536

/* Sampled from show.jpg: the backdrop is lit behind her head and falls off to the corners. */
const C = {
  lit: '#4c2b24', mid: '#391d17', edge: '#21100b',
  cream: '#FBF4EA', gold: '#E9CD8A', goldDeep: '#C29A55',
};

/* The backdrop: one smooth radial light around (cx, cy), plus fine grain. */
const GRAIN = 'data:image/svg+xml,' + encodeURIComponent(
  '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300"><filter id="n"><feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="2" stitchTiles="stitch"/>' +
  '<feColorMatrix values="0 0 0 0 0.5  0 0 0 0 0.5  0 0 0 0 0.5  0 0 0 0.09 0"/></filter><rect width="300" height="300" filter="url(#n)"/></svg>');
const backdrop = (cx, cy, rx, ry) => `
  <div style="position:absolute;inset:0;background:radial-gradient(ellipse ${rx}px ${ry}px at ${cx}px ${cy}px,${C.lit} 0%,#43251e 25%,${C.mid} 52%,#2c1610 78%,${C.edge} 100%)"></div>
  <div style="position:absolute;inset:0;background:url('${GRAIN}');mix-blend-mode:overlay"></div>`;

/* The cut-out at scale k, her face (460, 300 in the photo) placed at (fx, fy). */
const erika = (k, fx, fy) => `
  <img src="file://${CUT}" style="position:absolute;left:${fx - 460 * k}px;top:${fy - 300 * k}px;width:${1022 * k}px;height:${1536 * k}px;
    filter:drop-shadow(0 30px 60px rgba(0,0,0,.35))">`;

const gold = `background:linear-gradient(180deg,${C.gold} 10%,${C.goldDeep} 95%);-webkit-background-clip:text;background-clip:text;color:transparent`;

const BASE = (w, h, bg) => `
  @font-face{font-family:'Playfair Display';src:url(${FONT}) format('woff2');font-weight:400 900}
  *{margin:0;padding:0;box-sizing:border-box}
  html,body{width:${w}px;height:${h}px;overflow:hidden;background:${bg}}
  .pf{font-family:'Playfair Display',serif;font-weight:500;letter-spacing:-.015em}
  .fit{overflow:hidden}`;

const PAGES = {
  /* Her face at (1500, 580): inside the safe area, and in the phone crop. */
  /* Her face at (1490, 560): inside the safe area and the phone crop; her hair's top
     just inside the computer crop. */
  banner: { w: 2048, h: 1152, file: 'banner.jpg', bg: C.mid, html: `
    ${backdrop(1490, 520, 1000, 820)}
    ${erika(0.66, 1490, 560)}
    <div class="fit" style="position:absolute;left:430px;top:407px;width:800px;height:338px;display:flex;flex-direction:column;justify-content:center">
      <div class="pf" style="font-size:120px;line-height:.98;color:${C.cream}">Erika</div>
      <div class="pf" style="font-size:120px;line-height:1.02;${gold};padding-bottom:6px">Explains...</div>
      <div class="pf" style="font-size:46px;font-weight:400;line-height:1.2;color:${C.cream};margin-top:6px">with Erika Page</div>
    </div>` },

  /* Right of x=815 the art has no title, only her and the backdrop. */
  picture: { w: 800, h: 800, file: 'picture.jpg', bg: C.mid, html: `${backdrop(400, 330, 620, 620)}${erika(1, 400, 390)}` },

  /* The same photo before the show art's brown and title (photos.php 01/A7), for a pink
     alternative: 700x700 centred on her face. */
  'picture-pink': { w: 700, h: 700, file: 'picture-pink.jpg', bg: '#DCA79D', html: `<img src="file://${path.join(ROOT, 'assets', 'photos', '01', 'a7-pink-suit-ai.jpg')}" style="position:absolute;left:-110px;top:-20px;width:1022px;height:1536px">` },

  watermark: { w: 150, h: 150, file: 'watermark.png', bg: 'transparent', png: true, html: `
    <div style="width:150px;height:150px;border-radius:50%;background:radial-gradient(circle,${C.lit},${C.edge});border:4px solid ${C.goldDeep};display:flex;flex-direction:column;align-items:center;justify-content:center">
      <div class="pf" style="font-size:36px;line-height:1;color:${C.cream}">Erika</div>
      <div class="pf" style="font-size:27px;line-height:1.1;${gold}">Explains...</div>
    </div>` },
};

async function render(browser, name, p) {
  const html = `<!doctype html><html><head><meta charset="utf-8"><style>${BASE(p.w, p.h, p.bg)}</style></head><body><div style="position:relative;width:${p.w}px;height:${p.h}px;overflow:hidden">${p.html}</div></body></html>`;
  const tmp = path.join(os.tmpdir(), `yt-channel-${process.pid}.html`);
  fs.writeFileSync(tmp, html);
  const page = await browser.newPage({ viewport: { width: p.w, height: p.h }, deviceScaleFactor: 1 });
  await page.goto('file://' + tmp, { waitUntil: 'load' });
  await page.evaluate(() => document.fonts.ready);
  await page.waitForTimeout(300);
  const problems = await page.evaluate(() => {
    const out = [];
    for (const img of document.images) {
      if (!img.naturalWidth) { out.push(`picture failed to load: ${img.src}`); continue; }
      const k = img.getBoundingClientRect().width / img.naturalWidth;
      if (k > 1.001) out.push(`picture upscaled ${k.toFixed(2)}x`);
    }
    for (const el of document.querySelectorAll('.fit')) {
      if (el.scrollHeight > el.clientHeight + 1 || el.scrollWidth > el.clientWidth + 1)
        out.push(`text overflows its box: "${el.textContent.trim().slice(0, 40)}…"`);
    }
    return out;
  });
  if (problems.length) { await page.close(); throw new Error(`${name}:\n  ` + problems.join('\n  ')); }
  const file = path.join(OUT, p.file);
  await page.screenshot(p.png ? { path: file, type: 'png', omitBackground: true } : { path: file, type: 'jpeg', quality: 95 });
  if (name === 'banner') {
    // QA sheets: what a computer and a phone show, and her edges at twice the size.
    await page.screenshot({ path: path.join(OUT, 'qa-computer.jpg'), type: 'jpeg', quality: 90, clip: { x: 0, y: 364, width: 2048, height: 423 } });
    await page.screenshot({ path: path.join(OUT, 'qa-phone.jpg'), type: 'jpeg', quality: 90, clip: { x: 251, y: 364, width: 1546, height: 423 } });
    const zoom = await browser.newPage({ viewport: { width: p.w, height: p.h }, deviceScaleFactor: 2 });
    await zoom.goto('file://' + tmp, { waitUntil: 'load' });
    await zoom.evaluate(() => document.fonts.ready);
    await zoom.screenshot({ path: path.join(OUT, 'qa-edges-2x.jpg'), type: 'jpeg', quality: 90, clip: { x: 1180, y: 360, width: 560, height: 420 } });
    await zoom.close();
    // The same banner with the crops each device shows, for checking by eye.
    await page.evaluate(() => {
      const box = (l, t, w, h, c, label) => {
        const d = document.createElement('div');
        d.style.cssText = `position:absolute;left:${l}px;top:${t}px;width:${w}px;height:${h}px;outline:4px dashed ${c};font:700 22px Archivo;color:${c};padding:6px`;
        d.textContent = label; document.body.firstChild.appendChild(d);
      };
      box(0, 364, 2048, 423, '#7fffd4', 'computer');
      box(251, 364, 1546, 423, '#ffff66', 'phone');
      box(406, 407, 1235, 338, '#ffffff', 'safe area');
    });
    await page.screenshot({ path: path.join(OUT, 'safe-area.jpg'), type: 'jpeg', quality: 80 });
  }
  await page.close();
  fs.unlinkSync(tmp);
  return file;
}

(async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await pw.chromium.launch({ executablePath: fs.existsSync(CHROME) ? CHROME : undefined });
  let failed = 0;
  for (const [name, p] of Object.entries(PAGES)) {
    try { console.log('  ok   ' + path.relative(ROOT, await render(browser, name, p))); }
    catch (e) { console.error('  FAIL ' + e.message); failed++; }
  }
  fs.copyFileSync(SHOW, path.join(OUT, 'podcast-square.jpg'));
  fs.copyFileSync(path.join(__dirname, 'channel-setup.md'), path.join(OUT, 'channel-setup.md'));
  console.log('  ok   ' + path.relative(ROOT, path.join(OUT, 'podcast-square.jpg')) + ' (the show art as it is)');
  await browser.close();
  process.exit(failed ? 1 : 0);
})();
