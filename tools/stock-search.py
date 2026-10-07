"""Find stock photos on Pexels: search, save thumbnails, and make a numbered contact sheet.

    PEXELS_KEY=... python3 -I tools/stock-search.py <scratch-dir> "<query>" [count]

Then LOOK at <scratch-dir>/sheet.jpg and pick by number; results.json in the same folder
has each photo's photographer, profile URL (for the credit) and original URL. Download
the pick (append "?auto=compress&cs=tinysrgb&w=2400" to the original URL), crop to
1600x1000 with ffmpeg (never upscale), register it in photos.php library 17 with its
credit, then run php tools/build-images.php.

Why it exists: for a week the blog reused the same few library photos across articles
(one signing photo appeared four times on two articles) because there was no key and
no quick way to look. Every article now gets its own pictures; check-article.mjs fails
if any picture is on two articles. The key is the owner's: pass it in the environment,
never write it to a file. Keep the scratch dir outside the repo.
"""
import json, os, ssl, subprocess, sys, urllib.parse, urllib.request
out, q = sys.argv[1], sys.argv[2]; n = int(sys.argv[3]) if len(sys.argv) > 3 else 12
os.makedirs(out, exist_ok=True)
ctx = ssl.create_default_context(cafile="/root/.ccr/ca-bundle.crt")
proxy = os.environ.get("HTTPS_PROXY") or os.environ.get("https_proxy")
op = urllib.request.build_opener(urllib.request.ProxyHandler({"https": proxy} if proxy else {}), urllib.request.HTTPSHandler(context=ctx))
req = urllib.request.Request("https://api.pexels.com/v1/search?" + urllib.parse.urlencode({"query": q, "per_page": n, "orientation": "landscape"}),
                             headers={"Authorization": os.environ["PEXELS_KEY"], "User-Agent": "Mozilla/5.0"})
data = json.load(op.open(req, timeout=60))
rows = []
for i, p in enumerate(data.get("photos", [])):
    t = os.path.join(out, f"{i:02d}.jpg")
    with open(t, "wb") as f:
        f.write(op.open(urllib.request.Request(p["src"]["medium"], headers={"User-Agent": "Mozilla/5.0"}), timeout=60).read())
    rows.append({"i": i, "id": p["id"], "w": p["width"], "h": p["height"], "by": p["photographer"], "by_url": p["photographer_url"],
                 "page": p["url"], "alt": p.get("alt", ""), "orig": p["src"]["original"]})
json.dump(rows, open(os.path.join(out, "results.json"), "w"), indent=1)
for r in rows: print(f'{r["i"]:2d} {r["id"]} {r["w"]}x{r["h"]} {r["by"]} | {r["alt"][:90]}')
# contact sheet: 4 columns, numbered
files = [os.path.join(out, f'{r["i"]:02d}.jpg') for r in rows]
args = []
for f in files: args += ["-i", f]
k = len(files); cols = 4; rows_n = (k + cols - 1) // cols
fl = ";".join(f"[{j}:v]scale=360:240:force_original_aspect_ratio=increase,crop=360:240,drawtext=text='{j}':fontcolor=white:fontsize=36:box=1:boxcolor=black@0.6:x=8:y=8[v{j}]" for j in range(k))
layout = "|".join(f"{(j%cols)*360}_{(j//cols)*240}" for j in range(k))
fl += ";" + "".join(f"[v{j}]" for j in range(k)) + f"xstack=inputs={k}:layout={layout}:fill=black[out]"
subprocess.run(["ffmpeg", "-v", "error", "-y", *args, "-filter_complex", fl, "-map", "[out]", "-q:v", "4", os.path.join(out, "sheet.jpg")], check=True)
print("sheet:", os.path.join(out, "sheet.jpg"))
