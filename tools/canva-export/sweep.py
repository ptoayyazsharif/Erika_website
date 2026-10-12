"""Find every _assets/ file a Canva export references and download any not yet captured.

    python3 -I sweep.py <siteDir> <origin>      e.g.  sweep.py capture/site https://escaluxe.com

The browser only asks for what one viewport needs (a photo only in the phone layout, one
font format of three), so the HTML and CSS are read for every `_assets/...` path and
every CSS url(), relative to the site root (Canva sets <base href="/">). Downloads use
curl with TLS verified against the proxy CA bundle, retried, and checked for length.
"""
import os, re, subprocess, sys, urllib.parse

SITE, ORIGIN = sys.argv[1], sys.argv[2].rstrip('/')
CA = '/root/.ccr/ca-bundle.crt'
UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36'

def refs():
    found = set()
    for root, _, files in os.walk(SITE):
        if '_external' in root:
            continue
        for f in files:
            p = os.path.join(root, f)
            if not (f.endswith(('.html', '.css', '.js')) or f == 'index.html'):
                continue
            t = open(p, encoding='utf-8', errors='ignore').read()
            for m in re.finditer(r'_assets/[A-Za-z0-9_./%-]+', t):
                found.add(m.group(0).rstrip('.'))
            if f.endswith('.css'):
                base = os.path.dirname(os.path.relpath(p, SITE))
                for m in re.finditer(r'url\(\s*["\']?([^"\')]+)', t):
                    u = m.group(1)
                    if u.startswith(('data:', 'http', '#')):
                        continue
                    found.add(os.path.normpath(os.path.join(base, u)).replace('\\', '/'))
    return found

def get(rel):
    dest = os.path.join(SITE, urllib.parse.unquote(rel))
    if os.path.isfile(dest) and os.path.getsize(dest) > 0:
        return 'have'
    os.makedirs(os.path.dirname(dest), exist_ok=True)
    for _ in range(4):
        r = subprocess.run(['curl', '-sS', '--cacert', CA, '-A', UA, '-D', '-', '-o', dest + '.part',
                            '-w', '%{http_code} %{size_download}', f'{ORIGIN}/{rel}'], capture_output=True, text=True)
        tail = r.stdout.strip().split('\n')[-1].split()
        code = tail[0] if tail else '000'
        clen = re.search(r'(?im)^content-length:\s*(\d+)', r.stdout)
        size = os.path.getsize(dest + '.part') if os.path.exists(dest + '.part') else 0
        if code == '200' and (not clen or int(clen.group(1)) == size):
            os.replace(dest + '.part', dest)
            return 'got'
        if code in ('404', '403'):
            break
    if os.path.exists(dest + '.part'):
        os.remove(dest + '.part')
    return f'FAIL {code}'

rounds = 0
while True:
    rounds += 1
    todo = sorted(refs())
    results = {r: get(r) for r in todo}
    got = [r for r, v in results.items() if v == 'got']
    fails = {r: v for r, v in results.items() if v.startswith('FAIL')}
    print(f'round {rounds}: referenced {len(todo)}, downloaded {len(got)}, failed {len(fails)}')
    for r, v in fails.items():
        print('  ', v, r)
    if not got or rounds > 5:
        break
