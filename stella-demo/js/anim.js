/* ============================================================
   anim.js — motion. Everything fades, nothing snaps.
   GSAP for screen choreography, canvas for the sky.
   ============================================================ */

const Anim = (() => {
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const g = window.gsap;
  const has = () => typeof g !== 'undefined';

  /* ---- screen transitions --------------------------------- */

  function inScreen(el, opts = {}) {
    if (!el) return;
    const items = el.querySelectorAll('.reveal-item');
    if (!has() || reduced) {
      el.style.opacity = 1;
      items.forEach(i => { i.style.opacity = 1; i.style.transform = 'none'; });
      return;
    }
    g.killTweensOf([el, ...items]);
    g.fromTo(el, { opacity: 0 }, { opacity: 1, duration: .7, ease: 'power2.out' });
    if (items.length) {
      g.fromTo(items,
        { opacity: 0, y: 18 },
        { opacity: 1, y: 0, duration: .9, ease: 'expo.out', stagger: .085, delay: .12 });
    }
  }

  function outScreen(el) {
    return new Promise(resolve => {
      if (!el || !has() || reduced) return resolve();
      g.to(el, { opacity: 0, duration: .28, ease: 'power1.inOut', onComplete: resolve });
    });
  }

  /* ---- quiz question swap --------------------------------- */

  function questionOut(node) {
    return new Promise(resolve => {
      if (!node || !has() || reduced) { node && node.remove(); return resolve(); }
      g.to(node, {
        opacity: 0, y: -26, duration: .34, ease: 'power2.in',
        onComplete: () => { node.remove(); resolve(); }
      });
    });
  }

  function questionIn(node, back = false) {
    if (!node) return;
    if (!has() || reduced) { node.style.opacity = 1; return; }
    const parts = node.querySelectorAll('.q-num, .q-title, .q-help, .q-field');
    g.fromTo(node, { opacity: 0 }, { opacity: 1, duration: .4, ease: 'power2.out' });
    g.fromTo(parts,
      { opacity: 0, y: back ? -16 : 22 },
      { opacity: 1, y: 0, duration: .7, ease: 'expo.out', stagger: .07 });
  }

  function progressTo(fill, pct) {
    if (!fill) return;
    if (!has() || reduced) { fill.style.width = pct + '%'; return; }
    g.to(fill, { width: pct + '%', duration: .6, ease: 'expo.out' });
  }

  /* ---- the reading theatre -------------------------------- */

  function readingLine(el, text) {
    if (!el) return;
    if (!has() || reduced) { el.textContent = text; return; }
    g.to(el, {
      opacity: 0, y: -10, duration: .45, ease: 'power2.in',
      onComplete: () => {
        el.textContent = text;
        g.fromTo(el, { opacity: 0, y: 12 }, { opacity: 1, y: 0, duration: .8, ease: 'expo.out' });
      }
    });
  }

  function readingProgress(fill, seconds) {
    if (!fill) return null;
    if (!has() || reduced) { fill.style.width = '100%'; return null; }
    g.set(fill, { width: '0%' });
    return g.to(fill, { width: '92%', duration: seconds, ease: 'power1.inOut' });
  }

  function readingDone(fill) {
    if (!fill || !has() || reduced) return;
    g.to(fill, { width: '100%', duration: .35, ease: 'power2.out' });
  }

  /* ---- the sacred reveal ---------------------------------- */

  function litLine(p) {
    if (!p) return;
    p.classList.add('is-lit', 'is-now');
    if (!has() || reduced) { p.style.opacity = 1; return; }
    g.fromTo(p,
      { opacity: 0, y: 16, filter: 'blur(6px)' },
      { opacity: 1, y: 0, filter: 'blur(0px)', duration: 1.5, ease: 'power2.out' });
  }

  function dimLine(p) {
    if (!p) return;
    p.classList.remove('is-now');
    p.classList.add('is-past');
    if (has() && !reduced) g.to(p, { opacity: .42, duration: 1.2, ease: 'power1.out' });
  }

  /* ---- starfield ------------------------------------------ */

  function starfield(canvas) {
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let stars = [], raf = null, w = 0, h = 0;

    function build() {
      const dpr = Math.min(window.devicePixelRatio || 1, 2);
      w = canvas.clientWidth; h = canvas.clientHeight;
      canvas.width = w * dpr; canvas.height = h * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      const count = Math.round(Math.min(200, (w * h) / 9000));
      stars = Array.from({ length: count }, () => ({
        x: Math.random() * w,
        y: Math.random() * h,
        r: Math.random() * 1.25 + .25,
        a: Math.random() * .5 + .12,
        tw: Math.random() * .012 + .003,
        dir: Math.random() > .5 ? 1 : -1,
        warm: Math.random() > .72
      }));
    }

    function paint() {
      ctx.clearRect(0, 0, w, h);
      for (const s of stars) {
        if (!reduced) {
          s.a += s.tw * s.dir;
          if (s.a > .72 || s.a < .08) s.dir *= -1;
        }
        ctx.beginPath();
        ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
        ctx.fillStyle = s.warm
          ? `rgba(226,198,139,${s.a})`
          : `rgba(245,240,232,${s.a * .8})`;
        ctx.fill();
      }
      if (!reduced) raf = requestAnimationFrame(paint);
    }

    function start() { cancelAnimationFrame(raf); build(); paint(); }

    let t;
    window.addEventListener('resize', () => { clearTimeout(t); t = setTimeout(start, 180); });
    start();
  }

  return {
    reduced, inScreen, outScreen, questionIn, questionOut, progressTo,
    readingLine, readingProgress, readingDone, litLine, dimLine, starfield
  };
})();
