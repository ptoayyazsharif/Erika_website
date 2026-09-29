#!/usr/bin/env node
/**
 * Build a downloadable guide PDF from its HTML source.
 *
 *     node tools/build-guide.mjs [name]      (default: closing-costs-101)
 *
 * Source  : tools/guides/<name>.html
 * Output  : assets/guides/<name>.pdf
 *
 * WHY THIS IS NOT JUST "print the page"
 *
 * Chromium's print path only sees fonts through fontconfig — web fonts that load
 * perfectly on screen are silently dropped from the PDF and replaced with a
 * system serif. Worse, the brand fonts ship from Google as *variable* fonts, and
 * a variable font installed into fontconfig collapses to a single instance: 400
 * and 700 come out at identical widths. So this script cuts real static weight
 * files out of the variable originals with fontTools and instals those.
 *
 * Everything here is verified rather than assumed. The PDF is written to a
 * temporary file, checked, and only then moved into place — a failing build
 * leaves the previous good PDF alone.
 *
 * Requires: playwright (node), and a python env with fonttools + pdfplumber.
 *   python3 -m venv /tmp/pdfvenv && /tmp/pdfvenv/bin/pip install fonttools brotli pdfplumber
 */

import { chromium } from '/opt/node22/lib/node_modules/playwright/index.mjs';
import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync, writeFileSync, renameSync, unlinkSync, statSync } from 'node:fs';
import { homedir } from 'node:os';
import path from 'node:path';

const ROOT = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..');
const NAME = process.argv[2] || 'closing-costs-101';
const SRC  = path.join(ROOT, 'tools', 'guides', `${NAME}.html`);
const OUT  = path.join(ROOT, 'assets', 'guides', `${NAME}.pdf`);
const WORK = path.join(ROOT, 'tools', 'guides', '.fontcache');
const PY   = '/tmp/pdfvenv/bin/python';

const PAGE_W_IN = 8.5, PAGE_H_IN = 11;
const MAX_BYTES = 2 * 1024 * 1024;

const fail = (msg) => { console.error(`\n  BUILD FAILED — ${msg}\n`); process.exit(1); };
const ok   = (msg) => console.log(`  ok    ${msg}`);

/* ---------- 1. brand fonts as real static weights ---------- */

// Variable sources, and the static instances we cut from them. Fraunces needs
// opsz pinned high or it renders at its small-text optical size; SOFT/WONK off
// keeps the letterforms the same as the website's.
const VARIABLE = {
  Fraunces: 'https://raw.githubusercontent.com/google/fonts/main/ofl/fraunces/Fraunces%5BSOFT%2CWONK%2Copsz%2Cwght%5D.ttf',
  Archivo:  'https://raw.githubusercontent.com/google/fonts/main/ofl/archivo/Archivo%5Bwdth%2Cwght%5D.ttf',
};
const INSTANCES = [
  ['Fraunces', { wght: 600, opsz: 96, SOFT: 0, WONK: 0 }, 'SemiBold', 600],
  ['Fraunces', { wght: 400, opsz: 96, SOFT: 0, WONK: 0 }, 'Regular',  400],
  ['Archivo',  { wght: 400, wdth: 100 },                  'Regular',  400],
  ['Archivo',  { wght: 600, wdth: 100 },                  'SemiBold', 600],
  ['Archivo',  { wght: 700, wdth: 100 },                  'Bold',     700],
];

function buildFonts() {
  mkdirSync(WORK, { recursive: true });
  for (const [fam, url] of Object.entries(VARIABLE)) {
    const f = path.join(WORK, `${fam}-VF.ttf`);
    if (!existsSync(f) || statSync(f).size < 50_000) {
      execFileSync('curl', ['-sSL', '--max-time', '120', url, '-o', f]);
      if (!existsSync(f) || statSync(f).size < 50_000) fail(`could not download the ${fam} variable font`);
    }
  }

  const jobs = INSTANCES.map(([fam, loc, style, wt]) =>
    ({ src: path.join(WORK, `${fam}-VF.ttf`), loc, out: path.join(WORK, `${fam}-${style}.ttf`), fam, style, wt }));

  const script = `
import json, sys
from fontTools.varLib import instancer
from fontTools.ttLib import TTFont
for j in json.loads(sys.argv[1]):
    f = instancer.instantiateVariableFont(TTFont(j['src']), j['loc'], inplace=False, updateFontNames=False)
    n = f['name']
    for rec in list(n.names):
        pid, pe, lid = rec.platformID, rec.platEncID, rec.langID
        if rec.nameID in (1, 16): n.setName(j['fam'], rec.nameID, pid, pe, lid)
        if rec.nameID in (2, 17): n.setName(j['style'], rec.nameID, pid, pe, lid)
        if rec.nameID == 4:       n.setName(j['fam'] + ' ' + j['style'], 4, pid, pe, lid)
        if rec.nameID == 6:       n.setName(j['fam'] + '-' + j['style'], 6, pid, pe, lid)
    f['OS/2'].usWeightClass = j['wt']
    f.save(j['out'])
`;
  execFileSync(PY, ['-c', script, JSON.stringify(jobs)], { stdio: ['ignore', 'ignore', 'pipe'] });

  // Chromium reads fonts through fontconfig, so they have to be installed, not linked.
  const dest = path.join(homedir(), '.local', 'share', 'fonts');
  mkdirSync(dest, { recursive: true });
  for (const j of jobs) writeFileSync(path.join(dest, path.basename(j.out)), readFileSync(j.out));
  execFileSync('fc-cache', ['-f'], { stdio: 'ignore' });

  for (const j of jobs) {
    const got = execFileSync('fc-match', [`${j.fam}:weight=${j.wt === 400 ? 80 : j.wt === 600 ? 180 : 200}`], { encoding: 'utf8' });
    if (!got.includes(`${j.fam}-${j.style}`)) fail(`fontconfig resolves ${j.fam} ${j.wt} to "${got.trim()}" instead of ${j.style}`);
  }
  ok(`fonts: ${jobs.length} static weights instanced and installed`);
}

/* ---------- 2. render ---------- */

async function render(tmp) {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 816, height: 1056 } });
  const problems = [];
  page.on('pageerror', e => problems.push(String(e)));
  await page.goto('file://' + SRC, { waitUntil: 'networkidle' });
  await page.evaluate(async () => { await document.fonts.ready; });

  // A page is a fixed-height box, so content that grew too long is clipped
  // rather than reflowed — invisible in the PDF and obvious to a reader.
  const overflow = await page.evaluate(() => {
    const bad = [];
    document.querySelectorAll('.pg').forEach((pg, i) => {
      const box = pg.getBoundingClientRect();
      let lowest = 0, culprit = '';
      pg.querySelectorAll('*').forEach(el => {
        if (el.classList.contains('pg-num') || el.classList.contains('legal') ||
            el.classList.contains('cover-foot')) return;
        const r = el.getBoundingClientRect();
        if (r.height && r.bottom > lowest) { lowest = r.bottom; culprit = el.tagName + '.' + (el.className || '').split(' ')[0]; }
      });
      const spill = Math.round(lowest - box.bottom);
      if (spill > 0) bad.push(`page ${i + 1} overflows by ${spill}px (${culprit})`);
      if (pg.scrollHeight - pg.clientHeight > 1) bad.push(`page ${i + 1} scrollHeight exceeds the box`);
    });
    return bad;
  });

  const pageCount = await page.evaluate(() => document.querySelectorAll('.pg').length);

  // Screenshots at exact print size, so the build can be reviewed by eye too.
  const shotDir = path.join(WORK, 'pages');
  mkdirSync(shotDir, { recursive: true });
  for (let i = 0; i < pageCount; i++) {
    await page.locator('.pg').nth(i).screenshot({ path: path.join(shotDir, `page-${String(i + 1).padStart(2, '0')}.png`) });
  }

  await page.pdf({ path: tmp, printBackground: true, preferCSSPageSize: true });
  await browser.close();

  if (problems.length) fail(`javascript errors in the source: ${problems.join('; ')}`);
  if (overflow.length) fail(`content is being clipped:\n        ${overflow.join('\n        ')}`);
  ok(`rendered ${pageCount} pages, no content clipped`);
  return { pageCount, shotDir };
}

/* ---------- 3. verify the PDF itself ---------- */

function verify(tmp, expectPages) {
  const script = `
import json, sys, collections, pdfplumber
out = {"pages": [], "fonts": {}}
with pdfplumber.open(sys.argv[1]) as pdf:
    for pg in pdf.pages:
        out["pages"].append({"w": round(pg.width / 72, 3), "h": round(pg.height / 72, 3),
                             "text": pg.extract_text() or "", "chars": len(pg.chars)})
    c = collections.Counter(ch["fontname"] for p in pdf.pages for ch in p.chars)
    out["fonts"] = dict(c)
print(json.dumps(out))
`;
  let raw;
  try {
    raw = execFileSync(PY, ['-c', script, tmp], { encoding: 'utf8', maxBuffer: 32 * 1024 * 1024, stdio: ['ignore', 'pipe', 'ignore'] });
  } catch (e) { fail(`could not read the generated PDF back: ${e.message}`); }
  const r = JSON.parse(raw);

  if (r.pages.length !== expectPages) fail(`PDF has ${r.pages.length} pages, the source has ${expectPages}`);
  for (const [i, p] of r.pages.entries()) {
    if (Math.abs(p.w - PAGE_W_IN) > 0.02 || Math.abs(p.h - PAGE_H_IN) > 0.02)
      fail(`page ${i + 1} is ${p.w}x${p.h}in, expected ${PAGE_W_IN}x${PAGE_H_IN}in`);
    if (p.chars < 20) fail(`page ${i + 1} has almost no text (${p.chars} characters) — it probably failed to render`);
  }
  ok(`geometry: ${r.pages.length} pages, all ${PAGE_W_IN}x${PAGE_H_IN}in`);

  // The check that matters: what actually rendered, per character. Grepping the
  // PDF for a font name gives a false positive — the CSS family name is in there
  // whether the font embedded or not.
  const names = Object.keys(r.fonts);
  const fallback = names.filter(n => /Liberation|DejaVu|Free(Sans|Serif|Mono)|Nimbus|Times|Helvetica|Courier/i.test(n));
  if (fallback.length) fail(`fell back to system fonts: ${fallback.join(', ')}\n        (all of: ${names.join(', ')})`);
  const brand = names.filter(n => /Fraunces|Archivo/i.test(n));
  if (!brand.length) fail(`no brand fonts in the PDF at all (found: ${names.join(', ') || 'none'})`);
  ok(`fonts: ${brand.map(n => n.replace(/^[A-Z]{6}\+/, '')).join(', ')} — no fallbacks`);

  // Content integrity — every page must carry the words it was written with.
  const must = [
    [1, ['Closing Costs 101', 'Axen Realty']],
    [2, ['not your down payment', 'preventable']],
    [3, ['four buckets', 'Prepaids are not fees']],
    [4, ['who pays what', 'negotiable every single time']],
    [5, ['transfer tax', 'intangible recording tax', '62 months']],
    [6, ['Earnest money', 'Carfax', "Owner"]],
    [7, ['Your numbers', 'Estimated cash to close']],
    [8, ['Axen Realty', 'not tax advice', 'Equal Housing']],
  ];
  for (const [n, phrases] of must) {
    const text = (r.pages[n - 1]?.text || '').replace(/\s+/g, ' ');
    for (const ph of phrases) {
      if (!text.toLowerCase().includes(ph.toLowerCase()))
        fail(`page ${n} is missing expected content: "${ph}"`);
    }
  }
  ok(`content: every page carries its expected text`);

  const bytes = statSync(tmp).size;
  if (bytes > MAX_BYTES) fail(`PDF is ${(bytes / 1048576).toFixed(1)} MB, over the ${MAX_BYTES / 1048576} MB budget`);
  ok(`size: ${(bytes / 1024).toFixed(0)} KB`);
  return r;
}

/* ---------- run ---------- */

if (!existsSync(SRC)) fail(`no source at ${path.relative(ROOT, SRC)}`);
console.log(`\n  Building ${NAME}\n`);
buildFonts();
mkdirSync(path.dirname(OUT), { recursive: true });
const tmp = OUT + '.tmp';
const { pageCount, shotDir } = await render(tmp);
verify(tmp, pageCount);
renameSync(tmp, OUT);
console.log(`\n  -> ${path.relative(ROOT, OUT)}`);
console.log(`     page images for review: ${path.relative(ROOT, shotDir)}\n`);
