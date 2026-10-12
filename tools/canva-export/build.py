"""Assemble the downloadable copy of escaluxeglobal.com/ai-clone-video-services.

    python3 -I build.py <capture-site-dir> <gfonts-dir> <out-dir>

Output:
  index.html            the page itself (Canva Code widget), standalone and editable
  vendor/               Tailwind, Lucide icons, Google Fonts (css + woff2), all local
  icons/                the page's favicon and app icons
  canva-original/       Canva's exported wrapper, byte-for-byte, with the widget placed
                        where its iframe expects it (backup; needs Canva's runtime JS)
"""
import os, re, shutil, sys, hashlib

CAP, GF, OUT = sys.argv[1:4]
WRAP = os.path.join(CAP, 'ai-clone-video-services')
WIDGET = os.path.join(WRAP, '_assets/remote/embed/codelet/cb51qb5re42exj7n/index.html')
EXT = os.path.join(CAP, '_external')
if os.path.exists(OUT):
    shutil.rmtree(OUT)
os.makedirs(OUT)

def one(pattern_dir, prefix):
    for root, _, files in os.walk(pattern_dir):
        for f in files:
            if f.startswith(prefix):
                return os.path.join(root, f)
    raise SystemExit(f'missing {prefix} in {pattern_dir}')

# vendor: tailwind + lucide, byte-identical to the CDN files
os.makedirs(f'{OUT}/vendor/fonts')
shutil.copy(os.path.join(EXT, 'cdn.tailwindcss.com/3.4.17'), f'{OUT}/vendor/tailwindcss-3.4.17.js')
shutil.copy(os.path.join(EXT, 'cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js'), f'{OUT}/vendor/lucide-0.263.0.min.js')

# vendor: Google Fonts css with every font file local
css = open(one(os.path.join(EXT, 'fonts.googleapis.com'), 'index.html__'), encoding='utf-8').read()
def local_font(m):
    u = m.group(1)
    name = u.replace('https://fonts.gstatic.com/s/', '').replace('/', '_')
    src = os.path.join(GF, name)
    if not os.path.getsize(src):
        raise SystemExit(f'empty font {name}')
    shutil.copy(src, f'{OUT}/vendor/fonts/{name}')
    return f'url({name})'
css = re.sub(r'url\((https://fonts\.gstatic\.com/[^)]+)\)', local_font, css)
assert 'fonts.gstatic.com' not in css
open(f'{OUT}/vendor/fonts/google-fonts.css', 'w', encoding='utf-8').write(css)

# icons
os.makedirs(f'{OUT}/icons')
for f in os.listdir(os.path.join(WRAP, '_assets/images')):
    if f.endswith('.png'):
        shutil.copy(os.path.join(WRAP, '_assets/images', f), f'{OUT}/icons/{f}')

# the standalone page: the widget, minus Canva's hosting scripts, with local vendor paths
html = open(WIDGET, encoding='utf-8').read()
n_sdk = len(re.findall(r'<script src="/codelet/[^"]*"[^>]*></script>\s*', html))
assert n_sdk == 3, n_sdk
html = re.sub(r'<script src="/codelet/[^"]*"[^>]*></script>\s*', '', html)
html = html.replace('https://cdn.tailwindcss.com/3.4.17', 'vendor/tailwindcss-3.4.17.js')
html = html.replace('https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js', 'vendor/lucide-0.263.0.min.js')
html = re.sub(r'<link rel="preconnect" href="https://fonts\.(googleapis|gstatic)\.com"[^>]*>\s*', '', html)
html = re.sub(r'<link href="https://fonts\.googleapis\.com/css2\?[^"]*" rel="stylesheet">', '<link href="vendor/fonts/google-fonts.css" rel="stylesheet">', html)
head_add = ('\n<!-- Copied from escaluxeglobal.com/ai-clone-video-services (a Canva Code page) on '
            '12 Oct 2026 for self-hosting. Libraries and fonts are local in vendor/. -->\n'
            '<meta property="og:title" content="AI Clone Video Services - EscaluxeGlobal">\n'
            '<meta property="og:type" content="website">\n'
            '<link rel="shortcut icon" href="icons/2d0b56e7e51cf11036ad8734bdb67e2d.png">\n'
            '<link rel="icon" href="icons/e53c4bd8da5e491d9ab09e7cf0daf874.png" sizes="192x192">\n'
            '<link rel="apple-touch-icon" href="icons/725b756a69a7d4c235070e51acd85560.png" sizes="180x180">\n')
html, k = re.subn(r'(<title>[^<]*</title>)', r'\1' + head_add.replace('\\', '\\\\'), html, count=1)
assert k == 1
for bad in ('/codelet/', 'cdn.tailwindcss.com', 'cdn.jsdelivr.net', 'fonts.googleapis.com'):
    assert bad not in html, bad
open(f'{OUT}/index.html', 'w', encoding='utf-8').write(html)

# canva-original: the wrapper exactly as Canva serves it, plus the widget where its
# iframe points (_assets/remote/embed/codelet/<id>/index.html), with vendor paths
# rewritten relative to that spot
CO = f'{OUT}/canva-original'
shutil.copytree(WRAP, f'{CO}/ai-clone-video-services')
wdir = f'{CO}/ai-clone-video-services/_assets/remote/embed/codelet/cb51qb5re42exj7n'
# the wrapper's <base href="/ai-clone-video-services/"> means canva-original/ must be the
# web root, so it gets its own copy of the libraries inside _assets/local/
LOCAL = f'{CO}/ai-clone-video-services/_assets/local'
shutil.copytree(f'{OUT}/vendor', f'{LOCAL}/vendor')
shutil.copytree(f'{OUT}/icons', f'{LOCAL}/icons')
rel = os.path.relpath(LOCAL, wdir)
w = html.replace('href="vendor/', f'href="{rel}/vendor/').replace('src="vendor/', f'src="{rel}/vendor/').replace('href="icons/', f'href="{rel}/icons/')
open(f'{wdir}/index.html', 'w', encoding='utf-8').write(w)

# file list with checksums
rows = []
for root, _, files in os.walk(OUT):
    for f in sorted(files):
        p = os.path.join(root, f)
        rows.append(f'{hashlib.sha256(open(p, "rb").read()).hexdigest()[:16]}  {os.path.getsize(p):>8}  {os.path.relpath(p, OUT)}')
open(f'{OUT}/FILES.txt', 'w').write('\n'.join(sorted(rows, key=lambda r: r.split()[-1])) + '\n')
print('files:', len(rows))
