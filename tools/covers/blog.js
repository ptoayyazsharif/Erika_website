/**
 * Blog article covers, in one of five house templates.
 *
 * Every article used to get a one-off cover. These keep them looking like one
 * blog: the same type, palette and portrait of Erika on every cover, with the
 * article's own headline and, where a template has room for one, the article's
 * own picture. Like render.js beside it, this lays the cover out in HTML in the
 * site's own fonts and screenshots it, so the output is an ordinary image file
 * and the live server never needs Node.
 *
 *   node tools/covers/blog.js                       every entry in blog.json
 *   node tools/covers/blog.js <slug>                one entry
 *   node tools/covers/blog.js <slug> --all --out d  that entry in all five templates
 *
 * Output is 1600x1000, the ratio of every other editorial picture on the site, so
 * the /blog card, the article hero and the share preview all crop it the same way.
 *
 * Two checks run on every render and fail it rather than write a bad file:
 *   - no picture is shown larger than its real size (an upscale looks soft);
 *   - no text box overflows (a headline that is too long gets cut off silently).
 */
const fs = require('fs');
const os = require('os');
const path = require('path');

let pw;
try { pw = require('playwright'); } catch (e) { pw = require('playwright-core'); }

const ROOT = path.join(__dirname, '..', '..');
const CHROME = process.env.CHROME || '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';
const W = 1600, H = 1000;

const C = {
  ink: '#453230', cream: '#FBF7F3', gold: '#C9A15E', goldDeep: '#8F6B2E',
  merlot: '#C67F72', merlotDeep: '#B05E51', merlotInk: '#9C4A3E',
  // The portrait's own studio backdrop, sampled from its edges: lighter at the
  // top, deeper at the bottom. Templates that set her on pink continue it, so
  // the photo has no visible edge.
  pinkTop: '#DCA79D', pinkLow: '#C48A80',
};

const esc = s => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
const url = p => 'file://' + (path.isAbsolute(p) ? p : path.join(ROOT, p));

const BASE = `
  *{margin:0;padding:0;box-sizing:border-box}
  html,body{width:${W}px;height:${H}px;overflow:hidden}
  .c{position:relative;width:${W}px;height:${H}px;overflow:hidden}
  .eyebrow{font-family:'Archivo',sans-serif;font-weight:700;text-transform:uppercase;letter-spacing:.22em}
  /* Tight headline leading lets descenders (the g in "thing") hang below the
     last line box; the padding keeps them inside it, so the overflow check
     measures real overflow rather than ink. */
  .hl{font-family:'Fraunces',serif;font-weight:600;letter-spacing:-.012em;text-wrap:balance;padding-bottom:.14em}
  .sub{font-family:'Archivo',sans-serif;font-weight:400}
  .rule{height:4px;background:${C.gold}}
  .mark{font-family:'Fraunces',serif;font-weight:600;letter-spacing:-.01em}
  img{display:block}`;

/* The portrait, feathered at the sides so it melts into the pink it sits on. */
const portrait = (s, css) => `
  <img class="portrait" src="${url(s.portrait)}" alt="" style="${css}
    -webkit-mask-image:linear-gradient(to right,transparent 0,#000 14%,#000 92%,transparent 100%);
            mask-image:linear-gradient(to right,transparent 0,#000 14%,#000 92%,transparent 100%)">`;

const pink = `background:linear-gradient(180deg,${C.pinkTop} 0%,${C.pinkLow} 100%)`;

const TEMPLATES = {

  /* 1. Studio — Erika on her own pink, headline beside her. Needs no picture. */
  studio: s => `
    <div class="c" style="${pink}">
      ${portrait(s, 'position:absolute;right:-30px;top:-30px;height:1260px;width:auto;')}
      <div class="fit" style="position:absolute;left:96px;top:112px;width:690px;height:776px;display:flex;flex-direction:column">
        <div class="eyebrow" style="font-size:22px;color:${C.ink};opacity:.8">${esc(s.eyebrow)}</div>
        <div class="hl fit" data-min="60" data-max="104" style="line-height:1.06;color:${C.ink};margin-top:26px;max-height:440px">${esc(s.line)}</div>
        <div class="rule" style="width:110px;margin:38px 0 26px"></div>
        ${s.sub ? `<div class="sub fit" style="font-size:27px;line-height:1.45;color:${C.ink};opacity:.82;max-height:120px">${esc(s.sub)}</div>` : ''}
        <div class="mark" style="margin-top:auto;font-size:34px;color:${C.cream}">Erika <span style="color:${C.ink}">Explains</span></div>
      </div>
    </div>`,

  /* 2. Studio + scene — as Studio, with the article's own picture as a framed
        print beside the headline. */
  scene: s => `
    <div class="c" style="${pink}">
      ${portrait(s, 'position:absolute;right:-60px;top:-20px;height:1200px;width:auto;')}
      <div class="fit" style="position:absolute;left:88px;top:84px;width:800px;height:840px;display:flex;flex-direction:column">
        <div class="eyebrow" style="font-size:20px;color:${C.ink};opacity:.8">${esc(s.eyebrow)}</div>
        <div class="hl fit" data-min="50" data-max="86" style="line-height:1.07;color:${C.ink};margin-top:20px;max-height:340px">${esc(s.line)}</div>
        <div style="margin-top:40px;width:560px;padding:14px 14px 18px;background:${C.cream};box-shadow:0 22px 50px rgba(69,50,48,.28);transform:rotate(-2.2deg)">
          <div style="width:532px;height:333px;overflow:hidden"><img class="scene" src="${url(s.scene)}" alt="" style="width:100%;height:100%;object-fit:cover;object-position:${s.scenePos || '50% 50%'}"></div>
        </div>
        <div class="mark" style="margin-top:auto;font-size:32px;color:${C.cream}">Erika <span style="color:${C.ink}">Explains</span></div>
      </div>
    </div>`,

  /* 3. Editorial — a magazine page: cream, a large headline, the portrait in an
        arched window. */
  editorial: s => `
    <div class="c" style="background:${C.cream}">
      <div style="position:absolute;left:84px;right:84px;top:62px;display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid ${C.gold};padding-bottom:18px">
        <div class="eyebrow" style="font-size:20px;color:${C.merlotDeep}">${esc(s.eyebrow)}</div>
        <div class="eyebrow" style="font-size:20px;color:${C.goldDeep}">Erika Explains</div>
      </div>
      <div style="position:absolute;right:96px;top:150px;width:520px;height:850px;border-radius:260px 260px 0 0;overflow:hidden;${pink};box-shadow:inset 0 0 0 3px ${C.gold}">
        <img class="portrait" src="${url(s.portrait)}" alt="" style="position:absolute;left:50%;transform:translateX(-50%);top:0;height:1040px;width:auto">
      </div>
      <div class="fit" style="position:absolute;left:84px;top:178px;width:840px;height:760px;display:flex;flex-direction:column">
        <div class="hl fit" data-min="64" data-max="126" style="line-height:1.03;color:${C.ink};max-height:600px">${esc(s.line)}</div>
        <div class="rule" style="width:120px;margin:40px 0 26px"></div>
        ${s.sub ? `<div class="sub fit" style="font-size:28px;line-height:1.45;color:${C.ink};opacity:.8;max-height:130px">${esc(s.sub)}</div>` : ''}
      </div>
    </div>`,

  /* 4. Scene + panel — the article's own picture leads; the panel carries the
        headline and a round portrait, so she is on every cover even when the
        topic picture is the star. */
  panel: s => `
    <div class="c" style="background:${C.ink}">
      <div style="position:absolute;left:0;top:0;width:1000px;height:1000px;overflow:hidden">
        <img class="scene" src="${url(s.scene)}" alt="" style="width:100%;height:100%;object-fit:cover;object-position:${s.scenePos || '50% 50%'}">
      </div>
      <div class="fit" style="position:absolute;left:1000px;top:0;width:600px;height:1000px;padding:86px 64px 70px;display:flex;flex-direction:column">
        <div class="eyebrow" style="font-size:19px;color:${C.gold}">${esc(s.eyebrow)}</div>
        <div class="hl fit" data-min="46" data-max="82" style="line-height:1.08;color:${C.cream};margin-top:24px;max-height:560px">${esc(s.line)}</div>
        <div class="rule" style="width:96px;margin:34px 0 0"></div>
        <div style="margin-top:auto;display:flex;align-items:center;gap:24px">
          <div style="width:150px;height:150px;border-radius:50%;overflow:hidden;border:4px solid ${C.gold};${pink};flex:none">
            <img class="portrait" src="${url(s.portrait)}" alt="" style="width:100%;height:auto;margin-top:-2px">
          </div>
          <div class="mark" style="font-size:38px;line-height:1.05;color:${C.cream}">Erika<br><span style="color:${C.gold}">Explains</span></div>
        </div>
      </div>
    </div>`,

  /* 5. Bold block — a merlot block with a large headline beside the portrait.
        The highest contrast of the five; the strongest at thumbnail size. */
  bold: s => `
    <div class="c" style="${pink}">
      ${portrait(s, 'position:absolute;right:-120px;top:-24px;height:1240px;width:auto;')}
      <div class="fit" style="position:absolute;left:0;top:0;width:900px;height:1000px;background:${C.merlotDeep};padding:96px 80px 80px 90px;display:flex;flex-direction:column">
        <div class="eyebrow" style="font-size:21px;color:${C.gold}">${esc(s.eyebrow)}</div>
        <div class="hl fit" data-min="64" data-max="122" style="line-height:1.04;color:${C.cream};margin-top:28px;max-height:620px">${esc(s.line)}</div>
        <div class="rule" style="width:110px;margin:40px 0 0"></div>
        <div class="mark" style="margin-top:auto;font-size:36px;color:${C.cream}">Erika <span style="color:${C.gold}">Explains</span></div>
      </div>
    </div>`,
};

async function render(browser, spec, template, outFile) {
  const html = `<!doctype html><html><head><meta charset="utf-8"><style>${BASE}</style></head><body>${TEMPLATES[template](spec)}</body></html>`;
  const tmp = path.join(os.tmpdir(), `blog-cover-${process.pid}.html`);
  fs.writeFileSync(tmp, html);
  const page = await browser.newPage({ viewport: { width: W, height: H }, deviceScaleFactor: 1 });
  await page.goto('file://' + tmp, { waitUntil: 'load' });
  await page.evaluate(() => document.fonts.ready);
  await page.waitForTimeout(300);

  /* Grow each headline to the largest size that still fits its box. A template
     then suits any title: short ones get big type, long ones wrap further. */
  await page.evaluate(() => {
    for (const el of document.querySelectorAll('.hl[data-max]')) {
      const box = parseFloat(getComputedStyle(el).maxHeight);
      let lo = +el.dataset.min, hi = +el.dataset.max, best = lo;
      while (lo <= hi) {
        const mid = (lo + hi) >> 1;
        el.style.fontSize = mid + 'px';
        if (el.scrollHeight <= box && el.scrollWidth <= el.clientWidth + 1) { best = mid; lo = mid + 1; }
        else hi = mid - 1;
      }
      el.style.fontSize = best + 'px';
      el.dataset.fitted = best;
    }
  });

  const problems = await page.evaluate(() => {
    const out = [];
    for (const img of document.images) {
      if (!img.naturalWidth) { out.push(`picture failed to load: ${img.src}`); continue; }
      const r = img.getBoundingClientRect();
      // object-fit:cover draws at the larger of the two scales.
      const fit = getComputedStyle(img).objectFit;
      const scale = fit === 'cover' ? Math.max(r.width / img.naturalWidth, r.height / img.naturalHeight)
                                    : r.width / img.naturalWidth;
      if (scale > 1.01) out.push(`upscaled ${scale.toFixed(2)}x: ${img.src.split('/').pop()}`);
    }
    for (const el of document.querySelectorAll('.fit')) {
      if (el.scrollHeight > el.clientHeight + 1 || el.scrollWidth > el.clientWidth + 1) {
        out.push(`text overflows its box by ${el.scrollHeight - el.clientHeight}px: "${el.textContent.trim().slice(0, 40)}…"`);
      }
    }
    return out;
  });
  if (problems.length) {
    await page.close();
    throw new Error(`${spec.slug} / ${template}:\n  ` + problems.join('\n  '));
  }
  await page.screenshot({ path: outFile, type: 'jpeg', quality: 86 });
  await page.close();
  fs.unlinkSync(tmp);
  return outFile;
}

(async () => {
  const args = process.argv.slice(2);
  const all = args.includes('--all');
  const oi = args.indexOf('--out');
  const outDir = oi >= 0 ? args[oi + 1] : path.join(ROOT, 'assets', 'photos', '17');
  const slug = args.find((a, i) => !a.startsWith('--') && args[i - 1] !== '--out');

  const specs = JSON.parse(fs.readFileSync(path.join(__dirname, 'blog.json'), 'utf8'))
    .filter(s => !slug || s.slug === slug);
  if (!specs.length) { console.error(`no entry in blog.json for "${slug}"`); process.exit(1); }

  const browser = await pw.chromium.launch({ executablePath: fs.existsSync(CHROME) ? CHROME : undefined });
  let failed = 0;
  for (const s of specs) {
    const templates = all ? Object.keys(TEMPLATES) : [s.template];
    for (const t of templates) {
      if (!TEMPLATES[t]) { console.error(`unknown template "${t}"`); failed++; continue; }
      if ((t === 'scene' || t === 'panel') && !s.scene) { console.log(`  skip ${s.slug} / ${t}: needs a scene picture`); continue; }
      const file = path.join(outDir, all ? `${s.slug}--${t}.jpg` : `${s.slug}-cover.jpg`);
      try { await render(browser, s, t, file); console.log(`  ok   ${path.relative(ROOT, file) || file}`); }
      catch (e) { console.error('  FAIL ' + e.message); failed++; }
    }
  }
  await browser.close();
  process.exit(failed ? 1 : 0);
})();
