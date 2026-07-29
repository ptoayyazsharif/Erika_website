/* ============================================================================
   Escalate — app shell behaviour
   ----------------------------------------------------------------------------
   Everything here is progressive: the app is fully usable with this file
   blocked. No inline script anywhere in the app (the CSP forbids it), so data
   reaches JS through data-* attributes and <script type="application/json">
   blocks, never through generated code.
   ========================================================================= */

(() => {
  'use strict';

  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const g = window.gsap;

  /* ── theme ─────────────────────────────────────────────────────────────
     Stored per-device, never server-side — a theme preference is still a
     preference, and this app keeps what it can on the device. */

  const THEME_KEY = 'escalate.theme';

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    const meta = $('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', theme === 'light' ? '#F4F1EA' : '#101521');
    $$('[data-theme-toggle]').forEach(b => {
      b.setAttribute('aria-label', theme === 'light' ? 'Switch to dark' : 'Switch to light');
      b.setAttribute('aria-pressed', String(theme === 'light'));
    });
  }

  function initTheme() {
    let stored = null;
    try { stored = localStorage.getItem(THEME_KEY); } catch {}
    applyTheme(stored || (matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark'));

    document.addEventListener('click', e => {
      if (!e.target.closest('[data-theme-toggle]')) return;
      const next = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
      applyTheme(next);
      try { localStorage.setItem(THEME_KEY, next); } catch {}
    });
  }

  /* ── toast ─────────────────────────────────────────────────────────────── */

  let toastTimer;

  function toast(message, ms = 3200) {
    let el = $('#toast');
    if (!el) {
      el = document.createElement('div');
      el.id = 'toast';
      el.className = 'toast';
      el.setAttribute('role', 'status');
      el.setAttribute('aria-live', 'polite');
      document.body.appendChild(el);
    }
    el.textContent = message;
    requestAnimationFrame(() => el.classList.add('is-shown'));
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove('is-shown'), ms);
  }

  window.Escalate = { toast };

  /* Server-rendered flash messages arrive as a data attribute on <body>. */
  function initFlash() {
    const msg = document.body.dataset.flash;
    if (msg) setTimeout(() => toast(msg, 4200), 420);
  }

  /* ── entrance motion ───────────────────────────────────────────────────
     Stagger List from the design system: 400ms, back.out(1.4), 60ms each. */

  function initMotion() {
    if (!g || reduced) return;

    const items = $$('[data-enter]');
    if (items.length) {
      g.from(items, {
        opacity: 0, y: 16, scale: 0.98,
        duration: 0.4,
        stagger: { each: 0.06, from: 'start' },
        ease: 'back.out(1.4)',
        clearProps: 'transform,opacity',
      });
    }

    const hero = $('[data-enter-hero]');
    if (hero) {
      g.from(hero.children, {
        opacity: 0, y: 20,
        duration: 0.75,
        stagger: 0.075,
        ease: 'expo.out',
        clearProps: 'transform,opacity',
      });
    }
  }

  /* ── character counters ────────────────────────────────────────────────── */

  function initCounters() {
    $$('[data-counter]').forEach(input => {
      const out = $(`#${input.dataset.counter}`);
      if (!out) return;
      const max = input.getAttribute('maxlength');
      const paint = () => {
        out.textContent = max ? `${input.value.length} / ${max}` : String(input.value.length);
        out.classList.toggle('faint', !input.value.length);
      };
      input.addEventListener('input', paint);
      paint();
    });
  }

  /* ── textareas that grow ───────────────────────────────────────────────── */

  function initAutogrow() {
    $$('textarea[data-autogrow]').forEach(el => {
      const grow = () => {
        el.style.height = 'auto';
        el.style.height = `${el.scrollHeight + 2}px`;
      };
      el.addEventListener('input', grow);
      grow();
    });
  }

  /* ── option groups (radio/checkbox cards) ──────────────────────────────── */

  function initOptions() {
    document.addEventListener('change', e => {
      const input = e.target;
      if (!input.matches('.option input')) return;
      if (input.type === 'radio' && input.name) {
        $$(`.option input[name="${CSS.escape(input.name)}"]`).forEach(other => {
          other.closest('.option')?.classList.toggle('is-on', other.checked);
        });
      } else {
        input.closest('.option')?.classList.toggle('is-on', input.checked);
      }
    });
  }

  /* ── guard against double submits ──────────────────────────────────────── */

  function initForms() {
    $$('form[data-once]').forEach(form => {
      form.addEventListener('submit', () => {
        const btn = form.querySelector('[type="submit"]');
        if (!btn) return;
        // Disable on the next tick so the button's value still posts.
        setTimeout(() => {
          btn.setAttribute('aria-disabled', 'true');
          btn.disabled = true;
          if (btn.dataset.busy) btn.textContent = btn.dataset.busy;
        }, 0);
      });
    });
  }

  /* ── confirm destructive actions ───────────────────────────────────────── */

  function initConfirm() {
    document.addEventListener('submit', e => {
      const form = e.target;
      const msg = form.dataset.confirm;
      if (msg && !window.confirm(msg)) e.preventDefault();
    });
  }

  /* ── service worker + install ──────────────────────────────────────────── */

  function initServiceWorker() {
    if (!('serviceWorker' in navigator)) return;
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js').catch(() => {});
    });

    // Signing out wipes the shell cache too, so a shared device keeps nothing.
    document.addEventListener('submit', e => {
      if (!e.target.matches('[data-logout]')) return;
      navigator.serviceWorker.controller?.postMessage('escalate:purge');
    });
  }

  function initInstall() {
    let prompt = null;
    const btn = $('[data-install]');

    window.addEventListener('beforeinstallprompt', e => {
      e.preventDefault();
      prompt = e;
      if (btn) btn.hidden = false;
    });

    if (btn) {
      btn.addEventListener('click', async () => {
        if (!prompt) {
          toast('Use your browser menu → Add to Home Screen.');
          return;
        }
        prompt.prompt();
        const { outcome } = await prompt.userChoice;
        prompt = null;
        btn.hidden = true;
        if (outcome === 'accepted') toast('Escalate is on your home screen.');
      });
    }

    window.addEventListener('appinstalled', () => {
      if (btn) btn.hidden = true;
    });
  }

  /* ── boot ──────────────────────────────────────────────────────────────── */

  initTheme();
  document.addEventListener('DOMContentLoaded', () => {
    initFlash();
    initMotion();
    initCounters();
    initAutogrow();
    initOptions();
    initForms();
    initConfirm();
    initInstall();
    initServiceWorker();
  });
})();
