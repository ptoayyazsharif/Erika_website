"""Assemble the downloadable copy of a multi-page Canva site (escaluxe.com).

    python3 -I build-site.py <captureSite> <formDir> <outDir>
    (formDir for escaluxe.com: tools/canva-export/escaluxe-form)

<outDir>/site/ is the web root: Canva's pages (index.html, <slug>/index.html) and
_assets/ byte-for-byte, plus <formDir>'s files on top (the rebuilt contact form at
_website-element-widget/ and its PHP mailer at contact-form/). Things only Canva can
serve are left out: _external/ (Canva-hosted code for its own form widget).
"""
import hashlib, os, shutil, sys

CAP, FORM, OUT = sys.argv[1:4]
SITE = os.path.join(OUT, 'site')
if os.path.exists(SITE):
    shutil.rmtree(SITE)
shutil.copytree(CAP, SITE, ignore=shutil.ignore_patterns('_external', '*.part'))
shutil.rmtree(os.path.join(SITE, '_website-element-widget'), ignore_errors=True)
shutil.copytree(FORM, SITE, dirs_exist_ok=True, ignore=shutil.ignore_patterns('PHPMAILER.txt'))
# the form's mailer: PHPMailer from this repo's lib/, web access denied
pm = os.path.join(SITE, 'contact-form', 'phpmailer')
if os.path.isdir(os.path.join(SITE, 'contact-form')) and not os.path.isdir(pm):
    lib = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..', 'lib', 'phpmailer')
    shutil.copytree(lib, pm)
    open(os.path.join(pm, '.htaccess'), 'w').write('Require all denied\n')

rows = []
for root, _, files in os.walk(OUT):
    for f in sorted(files):
        p = os.path.join(root, f)
        if f == 'FILES.txt' or p.endswith('.zip'):
            continue
        rows.append(f'{hashlib.sha256(open(p, "rb").read()).hexdigest()[:16]}  {os.path.getsize(p):>9}  {os.path.relpath(p, OUT)}')
open(os.path.join(OUT, 'FILES.txt'), 'w').write('\n'.join(sorted(rows, key=lambda r: r.split()[-1])) + '\n')
print('files:', len(rows))
