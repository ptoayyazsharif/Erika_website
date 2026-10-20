"""Erika cut out of her pink-suit photo at full size, for channel art.

    <venv with pillow numpy scipy>/bin/python -I tools/youtube/cutout.py

The owner supplied a clean cut-out of the photo (tools/podcast/art/erika-cutout-guide.png),
but at 407x612 it is too small to use: banner-sized it goes soft. The photo itself
(assets/photos/01/a7-pink-suit-ai.jpg) is 1022x1536, the same framing. So the small cut-out
is only the guide, and the edge is worked out again at full size:

  1. its outline, scaled up, splits the photo into her, the backdrop, and a band between;
  2. the backdrop's colour is measured from the photo itself (it is a gradient, so per pixel,
     spread into the band by a normalised blur), and so is her colour just inside the edge;
  3. in the band, each pixel's opacity is where its colour lies between those two. Where the
     two are too alike to tell apart (the pink blazer on the pink backdrop) the guide's
     opacity is used, snapped to the photo's own edges (guided filter) and sharpened;
  4. the backdrop's share is taken out of every edge pixel, so no pink fringe is left in
     her hair on a dark background.

Writes tools/podcast/art/erika-cutout.png (RGBA, 1022x1536).
"""
import os
import numpy as np
from PIL import Image
from scipy import ndimage

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
PHOTO = os.path.join(ROOT, "assets", "photos", "01", "a7-pink-suit-ai.jpg")
GUIDE = os.path.join(ROOT, "tools", "podcast", "art", "erika-cutout-guide.png")
OUT = os.path.join(ROOT, "tools", "podcast", "art", "erika-cutout.png")


def spread(values, known, sigma):
    """Fill unknown pixels with a blur of the known ones (normalised convolution)."""
    w = ndimage.gaussian_filter(known.astype(np.float64), sigma)
    out = np.empty_like(values)
    for c in range(values.shape[2]):
        out[..., c] = ndimage.gaussian_filter(values[..., c] * known, sigma) / np.maximum(w, 1e-6)
    return out


def guided(I, p, r, eps):
    """Colour guided filter (He, Sun, Tang): p smoothed so its edges follow the edges in I."""
    box = lambda x: ndimage.uniform_filter(x, 2 * r + 1, mode="reflect")
    mI = np.stack([box(I[..., c]) for c in range(3)], -1)
    mp = box(p)
    cov = np.stack([box(I[..., c] * p) for c in range(3)], -1) - mI * mp[..., None]
    var = np.empty(I.shape[:2] + (3, 3))
    for i in range(3):
        for j in range(3):
            var[..., i, j] = box(I[..., i] * I[..., j]) - mI[..., i] * mI[..., j]
    var += eps * np.eye(3)
    a = np.linalg.solve(var, cov[..., None])[..., 0]
    b = mp - (a * mI).sum(-1)
    ma = np.stack([box(a[..., c]) for c in range(3)], -1)
    return (ma * I).sum(-1) + box(b)


def main():
    img = np.asarray(Image.open(PHOTO).convert("RGB"), dtype=np.float64) / 255
    h, w, _ = img.shape
    guide = Image.open(GUIDE).convert("RGBA")
    a0 = np.asarray(guide.getchannel("A").resize((w, h), Image.BICUBIC), dtype=np.float64) / 255

    fg = ndimage.binary_erosion(a0 > 0.9, iterations=7)
    bg = ndimage.binary_erosion(a0 < 0.1, iterations=7)
    band = ~fg & ~bg

    B = spread(img, bg, 18)          # the backdrop behind each pixel
    F = spread(img, fg, 6)           # her colour just inside the edge
    d = F - B
    dd = (d * d).sum(2)
    a_col = np.clip(((img - B) * d).sum(2) / np.maximum(dd, 1e-6), 0, 1)
    # Trust the colour only where her colour and the backdrop's clearly differ.
    trust = np.clip((np.sqrt(dd) - 0.08) / 0.12, 0, 1)
    # The guide, scaled up 2.5x, has a soft edge; where it has to stand in, sharpen it back.
    # It is first snapped to the photo's own edges with a guided filter.
    a_snap = np.clip(guided(img, a0, 5, 2e-4), 0, 1)
    a_guide = np.clip((a_snap - 0.5) * 2.5 + 0.5, 0, 1)
    alpha = np.where(band, trust * a_col + (1 - trust) * a_guide, np.where(fg, 1.0, 0.0))
    # Stay inside the guide's outline: nothing it calls backdrop comes back.
    alpha = np.minimum(alpha, ndimage.maximum_filter(a0, size=9))
    alpha = ndimage.gaussian_filter(alpha, 0.6)
    # Choke the faintest edge pixels: they are mostly backdrop, and left a light rim on dark brown.
    alpha = np.clip((alpha - 0.12) / 0.88, 0, 1)

    # Take the backdrop's share out of each edge pixel: I = aF + (1-a)B  ->  F = (I - (1-a)B) / a
    a3 = alpha[..., None]
    col = np.where(a3 > 0.02, (img - (1 - a3) * B) / np.maximum(a3, 0.02), img)
    col = np.clip(col, 0, 1)
    rgba = np.dstack([col, alpha])
    Image.fromarray((rgba * 255 + 0.5).astype(np.uint8), "RGBA").save(OUT, optimize=True)
    print(f"ok   {os.path.relpath(OUT, ROOT)}  {w}x{h}  band {band.mean() * 100:.1f}% of pixels")


if __name__ == "__main__":
    main()
