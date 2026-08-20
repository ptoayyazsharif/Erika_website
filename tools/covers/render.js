/**
 * Renders the site's guide, product and collection cover art.
 *
 * These slots hold designed pieces rather than photographs — a guide cover, a
 * product jacket, a collection card — so they are laid out here in the site's
 * own type and palette and rendered to real image files. Run with Playwright
 * available; the output is committed, so the live server never needs Node.
 *
 *   node tools/covers/render.js
 */
const { chromium } = require('playwright-core');
const fs = require('fs');
const path = require('path');

const CHROME = process.env.CHROME || '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';
const specs = JSON.parse(fs.readFileSync(path.join(__dirname, 'covers.json'), 'utf8'));
const OUT = path.join(__dirname, '..', '..', 'assets', 'photos', '15');

const ACCENTS = {
  gold:   { deep: '#8F6B2E', mid: '#C9A15E', soft: '#DCC08C', ground: 'linear-gradient(155deg,#FBF3EC 0%,#F1E0C9 58%,#E6CFAE 100%)' },
  merlot: { deep: '#8E3E33', mid: '#B05E51', soft: '#D49388', ground: 'linear-gradient(155deg,#FBF1EF 0%,#F1D6D0 58%,#E4B9B0 100%)' },
  blush:  { deep: '#9C4A3E', mid: '#D49388', soft: '#EFD3CC', ground: 'linear-gradient(155deg,#FDF6F4 0%,#F6E1DB 58%,#EDCBC2 100%)' },
  ink:    { deep: '#332423', mid: '#6B4F4A', soft: '#C9A15E', ground: 'linear-gradient(155deg,#463331 0%,#382726 58%,#2C1E1D 100%)' },
};

function page(s) {
  const a = ACCENTS[s.accent] || ACCENTS.gold;
  const dark = s.accent === 'ink';
  const ink = dark ? '#FBF7F3' : '#453230';
  const body = dark ? 'rgba(251,247,243,.72)' : '#5C4B47';
  const titleSize = s.title.length > 40 ? 62 : s.title.length > 26 ? 74 : 88;

  const shelf = `
    <div class="shelf art">
      ${[0, 1, 2].map(i => `
        <div class="jacket j${i}">
          <span class="jk"></span>
          <span class="jt"></span><span class="jt short"></span>
          <span class="jm"></span>
        </div>`).join('')}
    </div>`;

  const product = `
    <div class="vessels art">
      <div class="v v1"><i></i></div>
      <div class="v v2"><i></i></div>
      <div class="v v3"><i></i></div>
    </div>`;

  const cover = `
    <div class="mono">
      <span class="mk">E</span>
      <span class="mrule"></span>
      <span class="my">EST. 24+ YEARS</span>
    </div>`;

  return `<!doctype html><html><head><meta charset="utf-8">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500&family=Archivo:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  html,body{width:${s.w}px;height:${s.h}px}
  body{background:${a.ground};font-family:'Archivo',sans-serif;color:${body};position:relative;overflow:hidden}
  /* soft light that keeps a flat colour field from looking like a swatch */
  body::before{content:"";position:absolute;right:-14%;top:-26%;width:62%;aspect-ratio:1;border-radius:50%;
    background:radial-gradient(circle,${dark ? 'rgba(201,161,94,.24)' : 'rgba(255,255,255,.62)'},transparent 68%)}
  body::after{content:"";position:absolute;left:-16%;bottom:-30%;width:56%;aspect-ratio:1;border-radius:50%;
    background:radial-gradient(circle,${dark ? 'rgba(176,94,81,.22)' : 'rgba(201,161,94,.20)'},transparent 70%)}
  .frame{position:absolute;inset:${Math.round(s.h * 0.045)}px;border:1px solid ${dark ? 'rgba(220,192,140,.42)' : 'rgba(255,255,255,.72)'}}
  .inner{position:relative;z-index:2;height:100%;padding:${Math.round(s.h * 0.115)}px ${Math.round(s.w * 0.085)}px;
    display:flex;flex-direction:column;justify-content:space-between}
  /* Pieces that carry artwork read better as copy on the left, art on the right —
     it keeps the headline off the illustration and fills the empty half. */
  .inner.split{display:grid;grid-template-columns:1fr auto;grid-template-rows:1fr auto;
    column-gap:${Math.round(s.w * 0.05)}px;align-items:center}
  .inner.split .copy{grid-area:1 / 1}
  .inner.split .art{grid-area:1 / 2;justify-self:end}
  .inner.split .foot{grid-area:2 / 1 / 3 / -1}
  .kicker{font-size:19px;letter-spacing:.28em;text-transform:uppercase;font-weight:700;color:${dark ? a.soft : a.deep};
    display:flex;align-items:center;gap:16px}
  .kicker::before{content:"";width:38px;height:2px;background:${a.mid};flex:none}
  h1{font-family:'Fraunces',serif;font-weight:400;color:${ink};font-size:${titleSize}px;line-height:1.08;
    letter-spacing:-.01em;max-width:15ch;margin-top:${Math.round(s.h * 0.05)}px;text-wrap:balance}
  .inner.split h1{max-width:11ch;font-size:${Math.round(titleSize * 0.82)}px}
  h1 em{font-style:italic;color:${dark ? a.soft : a.deep}}
  .sub{margin-top:22px;font-size:22px;letter-spacing:.02em;color:${body}}
  .rule{width:96px;height:3px;background:linear-gradient(90deg,${a.mid},${a.soft});margin-top:30px}
  .foot{display:flex;align-items:flex-end;justify-content:space-between;gap:24px}
  .wordmark{font-family:'Fraunces',serif;font-size:30px;color:${ink}}
  .wordmark b{color:${a.deep};font-weight:400}
  .site{font-size:15px;letter-spacing:.2em;text-transform:uppercase;font-weight:600;color:${dark ? 'rgba(251,247,243,.55)' : 'rgba(92,75,71,.6)'}}

  /* monogram medallion on a plain guide cover */
  .mono{display:flex;align-items:center;gap:18px}
  .mk{font-family:'Fraunces',serif;font-size:44px;color:${a.deep};border:2px solid ${a.mid};width:78px;height:78px;
    display:flex;align-items:center;justify-content:center;border-radius:50%}
  .mrule{width:60px;height:1px;background:${a.mid}}
  .my{font-size:13px;letter-spacing:.24em;font-weight:700;color:${dark ? a.soft : a.deep}}

  /* a small stack of jackets, for the "what's inside" preview slots */
  .shelf{display:flex;align-items:flex-end;gap:${Math.round(s.w * 0.026)}px}
  .jacket{width:${Math.round(s.w * 0.132)}px;aspect-ratio:3/4;background:${dark ? '#F7EFE9' : '#fff'};
    box-shadow:0 26px 46px rgba(69,50,48,.20),0 8px 14px rgba(69,50,48,.10);
    padding:${Math.round(s.w * 0.018)}px;display:flex;flex-direction:column;gap:9px;position:relative}
  .jacket::after{content:"";position:absolute;left:0;top:0;bottom:0;width:7px;background:${a.mid}}
  .j0{transform:rotate(-4deg) translateY(10px)}
  .j1{transform:translateY(-14px) scale(1.06);z-index:2}
  .j2{transform:rotate(4deg) translateY(10px)}
  .jk{height:7px;width:44%;background:${a.deep};margin-left:14px}
  .jt{height:12px;width:76%;background:rgba(69,50,48,.30);margin-left:14px;margin-top:6px}
  .jt.short{width:52%}
  .jm{margin-top:auto;height:7px;width:38%;background:${a.soft};margin-left:14px}

  /* three vessels, for the Escaluxe Living collection cards */
  .vessels{display:flex;align-items:flex-end;justify-content:center;gap:${Math.round(s.w * 0.04)}px}
  .v{background:linear-gradient(160deg,#fff,${a.soft});box-shadow:0 24px 40px rgba(69,50,48,.18);position:relative}
  .v i{position:absolute;left:14%;right:14%;top:34%;height:22%;border-top:1px solid ${a.deep};border-bottom:1px solid ${a.deep};opacity:.45}
  .v1{width:${Math.round(s.w * 0.13)}px;height:${Math.round(s.h * 0.30)}px;border-radius:6px 6px 4px 4px}
  .v2{width:${Math.round(s.w * 0.16)}px;height:${Math.round(s.h * 0.40)}px;border-radius:50% 50% 6px 6px / 14% 14% 4px 4px}
  .v3{width:${Math.round(s.w * 0.11)}px;height:${Math.round(s.h * 0.25)}px;border-radius:4px}
</style></head><body>
  <div class="frame"></div>
  <div class="inner${s.kind === 'cover' ? '' : ' split'}">
    <div class="copy">
      <div class="kicker">${esc(s.kicker)}</div>
      <h1>${title(s.title)}</h1>
      ${s.sub ? `<div class="sub">${esc(s.sub)}</div>` : ''}
      <div class="rule"></div>
    </div>
    ${s.kind === 'shelf' ? shelf : s.kind === 'product' ? product : cover}
    <div class="foot">
      <div class="wordmark">Erika<b>K</b>Page</div>
      <div class="site">ErikaKPage.com</div>
    </div>
  </div>
</body></html>`;
}

const esc = t => t.replace(/&/g, '&amp;').replace(/</g, '&lt;');
// Italicise the closing phrase, the way the site's own headlines do.
function title(t) {
  const words = esc(t).split(' ');
  if (words.length < 4) return words.join(' ');
  const tail = words.splice(-2).join(' ');
  return `${words.join(' ')} <em>${tail}</em>`;
}

(async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const b = await chromium.launch({ executablePath: CHROME, args: ['--no-sandbox', '--disable-dev-shm-usage'] });
  const ctx = await b.newContext({ deviceScaleFactor: 1 });
  const p = await ctx.newPage();
  for (const s of specs) {
    await p.setViewportSize({ width: s.w, height: s.h });
    await p.setContent(page(s), { waitUntil: 'load', timeout: 30000 });
    // Type is the whole design here: never shoot before the webfonts are in.
    await p.waitForFunction(() => document.fonts.status === 'loaded', null, { timeout: 30000 })
           .catch(() => console.log('  ! webfonts did not settle, shooting anyway'));
    await p.waitForTimeout(200);
    const file = path.join(OUT, s.f + '.jpg');
    await p.screenshot({ path: file, type: 'jpeg', quality: 92 });
    console.log(`  ${s.f}.jpg  ${s.w}x${s.h}  ${(fs.statSync(file).size / 1024).toFixed(0)} KB`);
  }
  await b.close();
})();
