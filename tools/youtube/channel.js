/**
 * The YouTube channel's own images (Studio → Customization → Profile), made from the
 * show's art on RSS.com — her preferred image (the owner, 20 Oct 2026):
 * tools/podcast/art/show.jpg, 1400x1400, brown backdrop, pink suit, "Erika Explains…
 * with Erika Page" in Playfair Display (her brand system's title face) and gold.
 *
 *   NODE_PATH=/opt/node22/lib/node_modules node tools/youtube/channel.js [outDir]
 *
 * Writes to exports/youtube/channel/ (git-ignored) unless given a folder:
 *   banner.jpg       2048x1152. YouTube shows all of it only on a TV; a computer shows the
 *                    middle 2048x423 and a phone the middle 1546x423, so the words and her
 *                    face sit inside the 1235x338 safe area in the centre.
 *   picture.jpg      585x585, her face from the show art, right of its title. YouTube crops it
 *                    to a circle; 585 is as large as that allows without upscaling (minimum 98).
 *   picture-pink.jpg 700x700, the alternative: her face from the original photo, on its pink.
 *   watermark.png    150x150, transparent outside the circle; shown in the corner of
 *                    every video, and a click on it subscribes.
 *   podcast-square.jpg the show art itself, for the "Erika Explains" playlist set as a podcast.
 *   safe-area.jpg    the banner with the phone / computer crops drawn on, to check by eye.
 *
 * The show art has its title beside her hair; every crop keeps clear of it.
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

/* Sampled from show.jpg: the backdrop is lit behind her head (#4c2b24) and falls off to
   the corners; the banner's gradient below repeats those values around her. */
const C = {
  lit: '#45261e', mid: '#391d17', edge: '#25120d',
  cream: '#FBF4EA', gold: '#E9CD8A', goldDeep: '#C29A55',
};

/* show.jpg drawn at scale k with its top-left at (x, y), through a soft mask. The title
   sits left of her: above y=565 her hair starts at x≈820 (the dots of "Explains..." end at
   810); from 565 to 700 her hair starts at x≈630 ("with Erika Page" ends at 612); below
   that her sleeve reaches x≈470. The mask keeps her side of those lines, blurred so the
   edges melt into the banner's brown; the top edge fades over ~50 px and the right (where
   the art cuts her blazer) over the last 16%. */
const MASK = 'data:image/svg+xml,' + encodeURIComponent(
  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1400 1400"><filter id="b" x="-20%" y="-20%" width="140%" height="140%">' +
  '<feGaussianBlur stdDeviation="16"/></filter>' +
  '<linearGradient id="r"><stop offset="0.84" stop-color="#fff"/><stop offset="1" stop-color="#000"/></linearGradient>' +
  '<mask id="m"><rect width="1400" height="1400" fill="url(#r)"/></mask>' +
  '<g mask="url(#m)"><polygon filter="url(#b)" fill="#fff" points="' +
  '855,55 1400,55 1400,1500 480,1500 480,735 665,735 665,575 855,575"/></g></svg>');
const show = (k, x, y) => `
  <img src="file://${SHOW}" style="position:absolute;left:${x}px;top:${y}px;width:${1400 * k}px;height:${1400 * k}px;
    -webkit-mask-image:url('${MASK}');-webkit-mask-size:100% 100%;-webkit-mask-mode:luminance;mask-mode:luminance">`;

const gold = `background:linear-gradient(180deg,${C.gold} 10%,${C.goldDeep} 95%);-webkit-background-clip:text;background-clip:text;color:transparent`;

const BASE = (w, h, bg) => `
  @font-face{font-family:'Playfair Display';src:url(${FONT}) format('woff2');font-weight:400 900}
  *{margin:0;padding:0;box-sizing:border-box}
  html,body{width:${w}px;height:${h}px;overflow:hidden;background:${bg}}
  .pf{font-family:'Playfair Display',serif;font-weight:500;letter-spacing:-.015em}
  .fit{overflow:hidden}`;

const PAGES = {
  /* Her face at (1500, 580): inside the safe area, and in the phone crop. */
  banner: { w: 2048, h: 1152, file: 'banner.jpg', bg: C.mid, html: `
    <div style="position:absolute;inset:0;background:radial-gradient(ellipse 900px 800px at 1470px 470px,#4c2b24 0%,#45261f 22%,#3b1f19 45%,#321b15 68%,#2a150f 85%,#22100c 100%)"></div>
    ${show(0.62, 818, 300)}
    <div class="fit" style="position:absolute;left:430px;top:407px;width:800px;height:338px;display:flex;flex-direction:column;justify-content:center">
      <div class="pf" style="font-size:120px;line-height:.98;color:${C.cream}">Erika</div>
      <div class="pf" style="font-size:120px;line-height:1.02;${gold};padding-bottom:6px">Explains...</div>
      <div class="pf" style="font-size:46px;font-weight:400;line-height:1.2;color:${C.cream};margin-top:6px">with Erika Page</div>
    </div>` },

  /* Right of x=815 the art has no title, only her and the backdrop. */
  picture: { w: 585, h: 585, file: 'picture.jpg', bg: C.mid, html: `<img src="file://${SHOW}" style="position:absolute;left:-815px;top:-150px;width:1400px;height:1400px">` },

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
  await page.screenshot(p.png ? { path: file, type: 'png', omitBackground: true } : { path: file, type: 'jpeg', quality: 90 });
  if (name === 'banner') {
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
  console.log('  ok   ' + path.relative(ROOT, path.join(OUT, 'podcast-square.jpg')) + ' (the show art as it is)');
  await browser.close();
  process.exit(failed ? 1 : 0);
})();
