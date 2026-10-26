/**
 * A social post pack for one article, in the Bold cover style.
 *
 *   node tools/social/pack.js <slug>
 *
 * Reads tools/social/<slug>.json (the words: slides, three captions, the Google
 * Business Profile post) and the article's tools/covers/blog.json entry (headline,
 * line, portrait). Writes, under exports/social/<slug>/ (git-ignored; the owner posts):
 *   slide-1.jpg … slide-5.jpg   the carousel, 1080x1350 (4:5, the tallest Instagram
 *                               and Facebook show in the feed)
 *   carousel.pdf                the same five as one PDF: LinkedIn shows a "document"
 *                               post as a swipeable carousel
 *   google-business.jpg         1200x900 (4:3, the shape Google Business Profile asks for)
 *   pack.md                     captions, alt text, the profile post and how to post each
 *
 * The words in the JSON come from the article; nothing about Erika is added there
 * (see CLAUDE.md, "Social post pack"). Like tools/covers/blog.js, a render fails
 * rather than write a picture that is upscaled or has text overflowing its box.
 */
const fs = require('fs');
const os = require('os');
const path = require('path');

let pw;
try { pw = require('playwright'); } catch (e) { pw = require('playwright-core'); }

const ROOT = path.join(__dirname, '..', '..');
const CHROME = process.env.CHROME || '/opt/pw-browsers/chromium';
const C = {
  ink: '#453230', cream: '#FBF7F3', gold: '#C9A15E', merlotDeep: '#B05E51',
  pinkTop: '#DCA79D', pinkLow: '#C48A80',
};
const esc = s => String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
const url = p => 'file://' + (path.isAbsolute(p) ? p : path.join(ROOT, p));
const pink = `background:linear-gradient(180deg,${C.pinkTop} 0%,${C.pinkLow} 100%)`;

const BASE = (w, h) => `
  *{margin:0;padding:0;box-sizing:border-box}
  html,body{width:${w}px;height:${h}px;overflow:hidden}
  .c{position:relative;width:${w}px;height:${h}px;overflow:hidden}
  .eyebrow{font-family:'Archivo',sans-serif;font-weight:700;text-transform:uppercase;letter-spacing:.22em}
  .hl{font-family:'Fraunces',serif;font-weight:600;letter-spacing:-.012em;text-wrap:balance;padding-bottom:.14em}
  .sub{font-family:'Archivo',sans-serif;font-weight:400}
  .mark{font-family:'Fraunces',serif;font-weight:600;letter-spacing:-.01em}
  .rule{height:4px;background:${C.gold}}
  img{display:block}`;

const portrait = (s, css) => `
  <img src="${url(s.portrait)}" alt="" style="${css}
    -webkit-mask-image:linear-gradient(to right,transparent 0,#000 14%,#000 92%,transparent 100%);
            mask-image:linear-gradient(to right,transparent 0,#000 14%,#000 92%,transparent 100%)">`;
const mark = (fs, on = C.cream) => `<div class="mark" style="font-size:${fs}px;color:${on}">Erika <span style="color:${C.gold}">Explains</span></div>`;

const SLIDES = {
  /* The cover again, upright: merlot block over the portrait. */
  cover: (s, d, n, total) => `
    <div class="c" style="${pink}">
      ${portrait(d, 'position:absolute;right:-250px;top:-10px;height:1420px;width:auto;')}
      <div class="fit" style="position:absolute;left:0;top:0;width:640px;height:1350px;background:${C.merlotDeep};padding:84px 60px 70px 72px;display:flex;flex-direction:column">
        ${mark(40)}
        <div class="rule" style="width:96px;margin:28px 0 40px"></div>
        <div class="eyebrow" style="font-size:20px;color:${C.gold}">${esc(s.kicker || d.eyebrow)}</div>
        <div class="hl fit" data-min="56" data-max="118" style="line-height:1.04;color:${C.cream};margin-top:22px;max-height:640px">${esc(d.line)}</div>
        <div class="sub fit" style="font-size:28px;line-height:1.42;color:${C.cream};opacity:.9;margin-top:30px;max-height:250px">${esc(d.sub)}</div>
        <div class="eyebrow" style="margin-top:auto;font-size:20px;color:${C.gold}">Swipe &nbsp;→</div>
      </div>
    </div>`,

  /* One point per slide: a gold number, the point, two or three sentences of hers. */
  point: (s, d, n, total) => `
    <div class="c" style="background:${C.merlotDeep}">
      <div class="fit" style="position:absolute;inset:0;padding:84px 84px 76px;display:flex;flex-direction:column">
        <div style="display:flex;justify-content:space-between;align-items:center">${mark(34)}
          <div class="eyebrow" style="font-size:18px;color:${C.cream};opacity:.7">${n} / ${total}</div></div>
        <div class="hl" style="font-size:170px;line-height:1;color:${C.gold};margin-top:110px">${esc(s.n)}</div>
        <div class="hl fit" data-min="54" data-max="112" style="line-height:1.05;color:${C.cream};margin-top:30px;max-height:400px">${esc(s.head)}</div>
        <div class="rule" style="width:96px;margin:34px 0 30px"></div>
        <div class="sub fit" style="font-size:40px;line-height:1.42;color:${C.cream};opacity:.92;max-height:400px">${esc(s.body)}</div>
        <div class="sub" style="margin-top:auto;font-size:24px;color:${C.cream};opacity:.8">${esc(d.link)}</div>
      </div>
    </div>`,

  /* Her line, where to read the rest, and who she is. */
  end: (s, d, n, total) => `
    <div class="c" style="${pink}">
      ${portrait(d, 'position:absolute;right:-300px;top:120px;height:1240px;width:auto;')}
      <div class="fit" style="position:absolute;left:0;top:0;width:700px;height:1350px;background:${C.merlotDeep};padding:84px 64px 70px 76px;display:flex;flex-direction:column">
        ${mark(40)}
        <div class="rule" style="width:96px;margin:28px 0 70px"></div>
        <div class="hl fit" data-min="52" data-max="92" style="line-height:1.08;color:${C.cream};max-height:520px">“${esc(s.head)}”</div>
        <div class="sub fit" style="font-size:30px;line-height:1.42;color:${C.cream};opacity:.9;margin-top:46px;max-height:140px">${esc(s.body)}</div>
        <div class="sub fit" style="font-size:29px;font-weight:700;color:${C.gold};margin-top:16px;white-space:nowrap">${esc(d.link)}</div>
        <div style="margin-top:auto">
          <div class="sub" style="font-size:26px;color:${C.cream}">Erika K. Page · Axen Realty</div>
          <div class="sub" style="font-size:19px;color:${C.cream};opacity:.7;margin-top:10px">Explanatory only, not tax or legal advice. Equal Housing Opportunity.</div>
        </div>
      </div>
    </div>`,

  /* Google Business Profile picture, 4:3: the cover, squarer. */
  gbp: (s, d) => `
    <div class="c" style="${pink}">
      ${portrait(d, 'position:absolute;right:-190px;top:-12px;height:1100px;width:auto;')}
      <div class="fit" style="position:absolute;left:0;top:0;width:700px;height:900px;background:${C.merlotDeep};padding:70px 60px 60px 72px;display:flex;flex-direction:column">
        <div class="eyebrow" style="font-size:20px;color:${C.gold}">${esc(d.eyebrow)}</div>
        <div class="hl fit" data-min="52" data-max="120" style="line-height:1.04;color:${C.cream};margin-top:22px;max-height:520px">${esc(d.line)}</div>
        <div style="margin-top:auto">${mark(36)}</div>
      </div>
    </div>`,
};

async function shoot(browser, html, w, h, out, label) {
  const tmp = path.join(os.tmpdir(), `social-${process.pid}.html`);
  fs.writeFileSync(tmp, `<!doctype html><html><head><meta charset="utf-8"><style>${BASE(w, h)}</style></head><body>${html}</body></html>`);
  const page = await browser.newPage({ viewport: { width: w, height: h }, deviceScaleFactor: 1 });
  await page.goto('file://' + tmp, { waitUntil: 'load' });
  await page.evaluate(() => document.fonts.ready);
  await page.evaluate(() => {      // the largest headline that fits, as on the covers
    for (const el of document.querySelectorAll('.hl[data-max]')) {
      const box = parseFloat(getComputedStyle(el).maxHeight);
      let lo = +el.dataset.min, hi = +el.dataset.max, best = lo;
      while (lo <= hi) {
        const mid = (lo + hi) >> 1;
        el.style.fontSize = mid + 'px';
        if (el.scrollHeight <= box && el.scrollWidth <= el.clientWidth + 1) { best = mid; lo = mid + 1; } else hi = mid - 1;
      }
      el.style.fontSize = best + 'px';
    }
  });
  const problems = await page.evaluate(() => {
    const out = [];
    for (const img of document.images) {
      if (!img.naturalWidth) { out.push('picture failed to load: ' + img.src); continue; }
      const scale = img.getBoundingClientRect().width / img.naturalWidth;
      if (scale > 1.01) out.push(`upscaled ${scale.toFixed(2)}x: ${img.src.split('/').pop()}`);
    }
    for (const el of document.querySelectorAll('.fit'))
      if (el.scrollHeight > el.clientHeight + 1 || el.scrollWidth > el.clientWidth + 1)
        out.push(`text overflows its box: "${el.textContent.trim().slice(0, 40)}…"`);
    return out;
  });
  fs.unlinkSync(tmp);
  if (problems.length) { await page.close(); throw new Error(`${label}:\n  ` + problems.join('\n  ')); }
  await page.screenshot({ path: out, type: 'jpeg', quality: 88 });
  await page.close();
  console.log('  ok   ' + path.relative(ROOT, out));
}

(async () => {
  const slug = process.argv[2];
  if (!slug) { console.error('usage: node tools/social/pack.js <slug>'); process.exit(2); }
  const pack = JSON.parse(fs.readFileSync(path.join(__dirname, `${slug}.json`), 'utf8'));
  const cover = JSON.parse(fs.readFileSync(path.join(ROOT, 'tools', 'covers', 'blog.json'), 'utf8')).find(s => s.slug === slug);
  if (!cover) { console.error(`no entry in tools/covers/blog.json for "${slug}"`); process.exit(1); }
  const d = { ...cover, link: pack.link };
  const out = path.join(ROOT, 'exports', 'social', slug);
  fs.mkdirSync(out, { recursive: true });

  const browser = await pw.chromium.launch({ executablePath: fs.existsSync(CHROME) ? CHROME : undefined });
  const total = pack.slides.length;
  const files = [];
  try {
    for (const [i, s] of pack.slides.entries()) {
      const f = path.join(out, `slide-${i + 1}.jpg`);
      await shoot(browser, SLIDES[s.kind](s, d, i + 1, total), 1080, 1350, f, `slide ${i + 1}`);
      files.push(f);
    }
    await shoot(browser, SLIDES.gbp({}, d), 1200, 900, path.join(out, 'google-business.jpg'), 'google-business');

    // LinkedIn's carousel is a PDF "document" post: one slide per page.
    const page = await browser.newPage();
    await page.setContent(`<style>@page{size:1080px 1350px;margin:0}body{margin:0}img{display:block;width:1080px;height:1350px;page-break-after:always}</style>`
      + files.map(f => `<img src="${url(f)}">`).join(''));
    const tmp = path.join(os.tmpdir(), `social-pdf-${process.pid}.html`);
    fs.writeFileSync(tmp, await page.content());
    await page.goto('file://' + tmp, { waitUntil: 'load' });
    await page.pdf({ path: path.join(out, 'carousel.pdf'), width: '1080px', height: '1350px', printBackground: true });
    fs.unlinkSync(tmp);
    console.log('  ok   ' + path.relative(ROOT, path.join(out, 'carousel.pdf')));
  } catch (e) {
    console.error('  FAIL ' + e.message); await browser.close(); process.exit(1);
  }
  await browser.close();

  const md = [`# Social post pack: ${cover.line}`, '',
    `Article: ${pack.url}  ·  short link: ${pack.link}`, '',
    '## Carousel (Instagram, Facebook; LinkedIn as carousel.pdf)', '',
    ...pack.slides.map((s, i) => `- slide-${i + 1}.jpg, alt text: ${s.alt}`), '',
    ...Object.entries(pack.captions).flatMap(([k, v]) => [`## Caption: ${k}`, '', '```', v, '```', '']),
    '## Google Business Profile post', '',
    `Type: Update (“Add update”). Picture: google-business.jpg. Button: **${pack.gbp.button}** → ${pack.url}`, '',
    '```', pack.gbp.text, '```', '',
    `(${pack.gbp.text.length} of 1,500 characters. Google rejects posts with a phone number in the text: keep it out.)`, ''];
  fs.writeFileSync(path.join(out, 'pack.md'), md.join('\n'));
  console.log('  ok   ' + path.relative(ROOT, path.join(out, 'pack.md')));
})();
