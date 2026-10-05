// Check a published article the way a reader, a share preview and the inbox see it.
//
//   NODE_PATH=/opt/node22/lib/node_modules node tools/check-article.mjs <slug> [<slug>…]
//   … --all                                   every published article
//   … --base http://127.0.0.1:8000            local server (php -S 127.0.0.1:8000 router.php) — the default
//   … --base http://127.0.0.1:8731 --origin https://erikakpage.com
//                                             the live site, through tools/live-mirror.py
//
// Prints PASS/FAIL per check and exits non-zero on any FAIL. What is expected
// comes from this repo (blog_post() and product_one() in cms.php), so on the live
// site a FAIL can also mean the files there are not the files here — or that the
// admin has saved its own copy of the post (see "Content model gotcha" in CLAUDE.md).
//
// Each check exists because the thing it checks once went wrong:
//   * pictures shown at their own shape  — body pictures shipped stretched 712×1000
//   * the cover on every surface         — og:image is what a shared link shows
//   * the offer box is this article's    — it was hard-coded to Closing Costs 101
//   * form named after the article       — an article with no guide sent "Guide request: free guide"
//   * audio length matches the file      — typed durations drift
//   * the short link 301s here           — saving the admin once blanked it and 404'd the link
//   * the page is complete               — the host's "One moment, please…" page returns 200
//   * no "&rsquo;" showing as text       — entities in posts.php were escaped twice on /blog and in titles
//   * the line under the player is true  — it said "same words, read aloud" for every audio,
//                                          wrong once an episode had its own script
//   * every source link answers          — a researched article is only as good as its references
import { createRequire } from 'module';
import { execFileSync } from 'child_process';
import fs from 'fs';
import os from 'os';
import path from 'path';
import { fileURLToPath } from 'url';

const require = createRequire(import.meta.url);
let pw;
try { pw = require('playwright'); } catch { pw = require('playwright-core'); }
const ROOT = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const CHROME = process.env.CHROME || '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';
const CA = process.env.CA_BUNDLE || '/root/.ccr/ca-bundle.crt';

const args = process.argv.slice(2);
const opt = (name, dflt) => { const i = args.indexOf(name); if (i < 0) return dflt; const v = args[i + 1]; args.splice(i, 2); return v; };
const BASE = opt('--base', 'http://127.0.0.1:8000').replace(/\/$/, '');
const ORIGIN = opt('--origin', BASE).replace(/\/$/, '');
const all = args.includes('--all');
const php = (code, ...a) => JSON.parse(execFileSync('php', ['-r', 'require "cms.php"; ' + code, ...a], { cwd: ROOT, encoding: 'utf8' }));
const slugs = all ? php('echo json_encode(array_column(blog_posts(), "slug"));') : args.filter(a => !a.startsWith('--'));
if (!slugs.length) { console.error('usage: node tools/check-article.mjs <slug>… | --all [--base URL] [--origin URL]'); process.exit(2); }

let fails = 0;
const report = (ok, what, detail = '') => { if (!ok) fails++; console.log(`  ${ok ? 'PASS' : 'FAIL'}  ${what}${detail ? '  — ' + detail : ''}`); };
const warn = (what, detail = '') => console.log(`  WARN  ${what}${detail ? '  — ' + detail : ''}`);
const DEFAULT_NOTE = 'An AI narration of the article above, in Erika’s voice. Same words, read aloud.';

// Outside links in the article body (its references). 2xx/3xx pass; 403 is only a
// warning — some government sites refuse anything that isn't a browser — and
// 404, 5xx or no answer fail. Retried, because one failed request proves nothing.
function sourceLinks(body) {
  return [...new Set([...body.matchAll(/href="(https?:\/\/[^"]+)"/g)].map(m => m[1].replace(/&amp;/g, '&')))]
    .filter(u => !/erikakpage\.com|pexels\.com|pixabay\.com/.test(u));
}
function linkStatus(u) {
  let code = '000';
  for (let i = 0; i < 3 && !/^[23]/.test(code); i++) {
    const a = ['-sS', '-L', '--max-time', '40', '-A', 'Mozilla/5.0 (link check)', '-o', '/dev/null', '-w', '%{http_code}', u];
    if (fs.existsSync(CA)) a.unshift('--cacert', CA);
    try { code = execFileSync('curl', a, { encoding: 'utf8' }).trim(); } catch { code = '000'; }
  }
  return code;
}
const base = f => path.basename(f || '');
const sleep = ms => new Promise(r => setTimeout(r, ms));

// The host's holding page comes back with 200, so "loaded" means the real page:
// complete HTML, of a real page's size, without the holder's text.
async function realPage(page, url, mustSee) {
  for (let i = 0; i < 8; i++) {
    try {
      const res = await page.goto(url, { waitUntil: 'load', timeout: 90000 });
      const html = await page.content();
      if (res && res.status() === 200 && html.includes('</html>') && html.length > 150000 &&
          !/One moment, please/i.test(html) && (!mustSee || await page.$(mustSee))) return html;
    } catch (e) { /* retry */ }
    await sleep(2000 + i * 1500);
  }
  return null;
}

async function loadLazy(page) {
  await page.evaluate(async () => {
    for (let y = 0; y < document.body.scrollHeight; y += 500) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 120)); }
    window.scrollTo(0, 0);
  });
  await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
}

// Every visible picture: loaded, and — where it is drawn with object-fit:fill —
// drawn at its own shape within 3%.
async function shapes(page) {
  return page.evaluate(() => [...document.images].filter(i => i.offsetWidth > 0 && i.offsetHeight > 0).map(i => {
    const fit = getComputedStyle(i).objectFit;
    const nat = i.naturalWidth / (i.naturalHeight || 1), shown = i.clientWidth / (i.clientHeight || 1);
    return { src: (i.currentSrc || i.src).split('/').slice(-1)[0], loaded: i.complete && i.naturalWidth > 0,
             off: fit === 'fill' ? Math.abs(shown / nat - 1) : 0 };
  }));
}

// A picture the live host answers once with its holding page (or the proxy cuts
// short) shows as "not loaded" though the file is fine. Before failing, reload the
// page once and look again; only what is still wrong is reported.
async function badPictures(page, url, mustSee) {
  const bad = async () => (await shapes(page)).filter(s => !s.loaded || s.off > 0.03);
  let b = await bad();
  if (b.some(s => !s.loaded) && await realPage(page, url, mustSee)) { await loadLazy(page); b = await bad(); }
  return b;
}

function curl(url, out = '/dev/null') {
  const a = ['-sS', '--max-time', '120', '-o', out, '-w', '%{http_code} %{redirect_url}', url];
  if (url.startsWith('https:') && fs.existsSync(CA)) a.unshift('--cacert', CA);
  try { return execFileSync('curl', a, { encoding: 'utf8' }).trim().split(' '); } catch { return ['000', '']; }
}

const browser = await pw.chromium.launch({ executablePath: fs.existsSync(CHROME) ? CHROME : undefined });
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });

for (const slug of slugs) {
  const post = php('echo json_encode(blog_post($argv[1], true));', slug);
  if (!post) { report(false, `${slug}: no such post in posts.php or the admin copy`); continue; }
  const prod = post.product ? php('echo json_encode(product_one($argv[1]));', post.product) : null;
  const cover = base(post.cover);
  console.log(`\n${slug}  (${BASE})`);

  const html = await realPage(page, `${BASE}/blog/${slug}`, '.art-cover, article');
  report(!!html, 'article page is the real, complete page');
  if (!html) continue;
  await loadLazy(page);

  const bad = await badPictures(page, `${BASE}/blog/${slug}`, '.art-cover, article');
  report(!bad.length, 'every visible picture loaded and at its own shape', bad.map(b => `${b.src} ${b.loaded ? (b.off * 100).toFixed(1) + '% off' : 'not loaded'}`).join(', '));

  const literal = await page.evaluate(() => {
    const t = document.body.innerText + '\n' + document.title + '\n' +
      [...document.querySelectorAll('meta[name="description"], meta[property^="og:"], meta[name^="twitter:"]')].map(m => m.content).join('\n');
    return [...new Set(t.match(/&(#\d+|[a-z]+);/gi) || [])];
  });
  report(!literal.length, 'no HTML entities showing as text (page, title, share tags)', literal.join(' '));

  const meta = await page.evaluate(() => ({
    og: document.querySelector('meta[property="og:image"]')?.content || '',
    tw: document.querySelector('meta[name="twitter:image"]')?.content || '',
    hero: document.querySelector('.art-cover img')?.currentSrc || '',
    ld: [...document.querySelectorAll('script[type="application/ld+json"]')].map(s => s.textContent).join('\n'),
    audio: document.querySelector('.listen audio, .listen source')?.getAttribute('src') || document.querySelector('.listen audio source')?.getAttribute('src') || '',
    forms: [...document.querySelectorAll('form')].filter(f => f.querySelector('input[name="_post"]')).map(f => f.querySelector('input[name="_form"]').value),
    offer: !!document.querySelector('.offer'),
    offerName: document.querySelector('.offer h2')?.textContent.trim() || '',
    offerPic: document.querySelector('.offer-pic img')?.currentSrc || '',
    note: document.querySelector('.listen .note')?.textContent.trim() || '',
  }));
  const coverStem = cover.replace(/\.[a-z]+$/, '');
  report(meta.og.endsWith('/' + post.cover), 'og:image is the cover', meta.og);
  report(meta.tw.endsWith('/' + post.cover), 'twitter:image is the cover', meta.tw);
  report(meta.hero.includes(coverStem), 'article hero shows the cover', base(meta.hero));
  report(meta.ld.includes(post.cover), 'schema image is the cover');

  if (post.guide) {
    const name = prod ? prod.title : '';
    report(meta.offer, 'offer box is shown');
    if (prod) {
      report(meta.offerName === name, "offer box names this article's guide", meta.offerName);
      report(base(meta.offerPic).includes(base(prod.cover).replace(/\.[a-z]+$/, '')), "offer box shows this guide's cover", base(meta.offerPic));
    }
    const want = 'Guide request: ' + (name || 'free guide');
    report(meta.forms.length > 0 && meta.forms.every(f => f === want), `forms are named "${want}"`, meta.forms.join(' | '));
    const sent = await realPage(page, `${BASE}/blog/${slug}?sent=1`, '.offer');
    const dl = sent ? await page.evaluate(() => document.querySelector('.offer a[download]')?.getAttribute('href') || '') : '';
    report(dl.split('?')[0].endsWith('/' + post.guide) || dl.split('?')[0] === post.guide, '?sent=1 downloads this guide', dl);
  } else {
    report(!meta.offer, 'no offer box (this article has no guide)');
    const want = 'Blog enquiry: ' + post.title;
    report(meta.forms.length > 0 && meta.forms.every(f => f === want), `forms are named "${want}"`, meta.forms.join(' | '));
  }

  if (post.audio) {
    report(meta.audio.includes(base(post.audio)), 'audio player plays this article', meta.audio);
    const wantNote = post.audio_note || DEFAULT_NOTE;
    report(meta.note === wantNote, post.audio_note ? 'line under the player describes the episode (audio_note)' : 'line under the player says it is the article read aloud', meta.note);
    const tmp = path.join(os.tmpdir(), `check-${process.pid}.mp3`);
    const [code] = curl(`${BASE}/${post.audio}`, tmp);
    let secs = 0;
    try { secs = parseFloat(execFileSync('ffprobe', ['-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', tmp], { encoding: 'utf8' })); } catch {}
    fs.rmSync(tmp, { force: true });
    report(code === '200' && Math.abs(secs - post.audio_secs) <= 2, 'audio file is there and audio_secs matches it', `http ${code}, file ${secs.toFixed(1)}s, post ${post.audio_secs}s`);
  } else {
    report(true, 'no narration on this post (audio is empty)');
  }

  const links = sourceLinks(post.body);
  if (links.length) {
    const bad = [];
    for (const u of links) {
      const c = linkStatus(u);
      if (c === '403') warn('source link refuses non-browser requests (open it by hand once)', `${c} ${u}`);
      else if (!/^[23]/.test(c)) bad.push(`${c} ${u}`);
    }
    report(!bad.length, `every source link answers (${links.length} checked)`, bad.join(', '));
  }

  if (post.short) {
    let code = '', loc = '';
    for (let i = 0; i < 6 && code !== '301'; i++) { [code, loc] = curl(`${ORIGIN}/${post.short}`); if (code !== '301') await sleep(2000); }
    const want = `/blog/${slug}` + (post.guide ? '#guide' : '');
    report(code === '301' && loc.endsWith(want), `/${post.short} 301s to ${want}`, `${code} ${loc}`);
  }

  if (await realPage(page, `${BASE}/blog`, `a[href="/blog/${slug}"]`)) {
    await loadLazy(page);
    const card = await page.evaluate(s => [...document.querySelectorAll(`a[href="/blog/${s}"]`)].map(a => a.closest('article, .post-card, li, div')?.querySelector('img')?.currentSrc || '').find(Boolean) || '', slug);
    report(card.includes(coverStem), '/blog lists it with its cover', base(card));
    const listLit = await page.evaluate(() => [...new Set(document.body.innerText.match(/&(#\d+|[a-z]+);/gi) || [])]);
    report(!listLit.length, '/blog shows no HTML entities as text', listLit.join(' '));
    const badList = await badPictures(page, `${BASE}/blog`, `a[href="/blog/${slug}"]`);
    report(!badList.length, '/blog pictures loaded and at their own shape', badList.map(b => b.src).join(', '));
  } else report(false, '/blog lists it');

  if (prod) {
    const ok = await realPage(page, `${BASE}/digital-products/${prod.slug}`, `a[href="/blog/${slug}"]`);
    report(!!ok, `the guide's page (/digital-products/${prod.slug}) links back here`);
  }
}

await browser.close();
console.log(`\n${fails ? 'FAILURES: ' + fails : 'ALL PASS'}`);
process.exit(fails ? 1 : 0);
