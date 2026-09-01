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
     The server renders the chosen theme into <html data-theme> and the
     theme-color meta tag, so there is no flash of the wrong palette on first
     paint — which matters here because the CSP forbids the inline <head>
     script that usually solves it.

     Everything below is therefore only about applying a change *without a
     reload*. The saved value lives on the account, not in localStorage, so it
     follows the user to a new device. */

  const CSRF = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  function applyTheme(key, meta = {}) {
    document.documentElement.setAttribute('data-theme', key);

    const colour = document.querySelector('meta[name="theme-color"]');
    if (colour && meta.chrome) colour.setAttribute('content', meta.chrome);

    const scheme = document.querySelector('meta[name="color-scheme"]');
    if (scheme && meta.scheme) scheme.setAttribute('content', meta.scheme);

    // Keep the quick switch pointing at the right counterpart.
    $$('[data-theme-toggle]').forEach(btn => {
      btn.dataset.themeCurrent = key;
      if (meta.counterpart) btn.dataset.themeCounterpart = meta.counterpart;
    });
  }

  async function saveTheme(key, url) {
    if (!url) return null;
    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': CSRF(),
          Accept: 'application/json',
        },
        body: JSON.stringify({ theme: key }),
      });
      if (!res.ok) throw new Error(String(res.status));
      return await res.json();
    } catch {
      // The theme is already applied on screen; only the saving failed. Say so
      // rather than reverting, which would be more confusing than a stale
      // preference.
      toast('Looks right, but the preference could not be saved.');
      return null;
    }
  }

  function initTheme() {
    // Quick light/dark switch in the top bar.
    document.addEventListener('click', async e => {
      const btn = e.target.closest('[data-theme-toggle]');
      if (!btn) return;

      const next = btn.dataset.themeCounterpart;
      if (!next) return;

      applyTheme(next);
      const meta = await saveTheme(next, btn.dataset.themeUrl);
      if (meta) applyTheme(meta.theme, meta);
    });

    // Full picker in My World — apply on selection, before the form is saved,
    // because choosing a theme you cannot see is not a choice.
    document.addEventListener('change', async e => {
      const input = e.target.closest('[data-theme-choice]');
      if (!input || !input.checked) return;

      const key = input.dataset.themeChoice;
      applyTheme(key, {
        chrome: input.dataset.themeChrome,
        scheme: input.dataset.themeScheme,
        counterpart: input.dataset.themeCounterpart,
      });

      const url = $('[data-theme-toggle]')?.dataset.themeUrl;
      await saveTheme(key, url);
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

  /* ── show the password you are typing ──────────────────────────────────── */

  /*
   * Asked for by users signing in. The buttons are rendered hidden and unhidden
   * here, so a browser with this file blocked gets a plain password field
   * rather than a control that does nothing when pressed.
   *
   * The choice is deliberately never remembered — not in localStorage, not in a
   * cookie, not across a page load. A password left revealed on a screen
   * somebody else can see is a worse failure than typing it twice, and this is
   * an app whose whole promise is that what is inside it stays private.
   */
  function initPasswordReveal() {
    $$('[data-reveal]').forEach(btn => {
      const input = document.getElementById(btn.dataset.reveal);
      if (!input) return;

      btn.hidden = false;

      btn.addEventListener('click', () => {
        const shown = input.type === 'text';
        input.type = shown ? 'password' : 'text';

        btn.setAttribute('aria-pressed', String(!shown));
        const label = shown ? 'Show password' : 'Hide password';
        btn.setAttribute('aria-label', label);
        btn.title = label;

        // Swapping an input's type moves the caret to position 0 in several
        // browsers, so somebody who reveals mid-password carries on typing at
        // the front of it. Put it back at the end.
        const end = input.value.length;
        input.focus();
        try {
          input.setSelectionRange(end, end);
        } catch {
          // Some input types refuse setSelectionRange. Focus alone is fine.
        }
      });
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

  /*
   * Keeps the visible state of the custom radio/checkbox patterns in step with
   * the real input hidden inside them.
   *
   * This used to match `.option input` only, and the feelings picker and the
   * gratitude tags use the `.chip` variant — so their checkboxes toggled
   * underneath while the chip never changed. Reported as "none of these
   * buttons worked, I could never take it off of calm", which is precisely
   * what it looks like: you press, nothing happens, you press again.
   *
   * It was worse than inert. The input really was toggling on every press, so
   * the state that got submitted could be the opposite of the state on screen
   * — and with an odd number of presses you would save a feeling you had just
   * spent four taps trying to turn off.
   */
  const PICKER = '.option, .chip';

  function initOptions() {
    document.addEventListener('change', e => {
      const input = e.target;
      if (!(input instanceof HTMLInputElement)) return;

      const wrapper = input.closest(PICKER);
      if (!wrapper) return;

      // A radio turning on turns its whole group off, and the browser fires
      // `change` only on the one that gained the checked state — so the group
      // has to be repainted rather than just this element.
      if (input.type === 'radio' && input.name) {
        const selector = `${PICKER}`.split(', ')
          .map(base => `${base} input[name="${CSS.escape(input.name)}"]`)
          .join(', ');

        $$(selector).forEach(other => {
          other.closest(PICKER)?.classList.toggle('is-on', other.checked);
        });

        return;
      }

      wrapper.classList.toggle('is-on', input.checked);
    });
  }

  /* ── guard against double submits ──────────────────────────────────────── */

  function initForms() {
    $$('form[data-once]').forEach(form => {
      form.addEventListener('submit', e => {
        // A data-confirm handler runs in the capture phase and may have
        // cancelled this submit. Without this check, choosing "Cancel" on a
        // confirmation still disabled and relabelled the button, leaving the
        // action permanently unavailable until a reload.
        if (e.defaultPrevented) return;

        // The button that was actually pressed, not the first one in the form.
        // "Save and name a desire" used to grey out "Save my world" instead.
        const btn = e.submitter ?? form.querySelector('[type="submit"]');
        if (!btn) return;

        // Disable on the next tick so the button's value still posts.
        setTimeout(() => {
          btn.setAttribute('aria-disabled', 'true');
          btn.disabled = true;

          if (btn.dataset.busy) {
            // Replace only the text, so a button containing an SVG icon does
            // not lose it. textContent on the button would wipe the icon out.
            const label = [...btn.childNodes].find(n => n.nodeType === Node.TEXT_NODE && n.textContent.trim());
            if (label) label.textContent = ' ' + btn.dataset.busy + ' ';
            else btn.textContent = btn.dataset.busy;
          }
        }, 0);
      });
    });
  }

  /* ── confirm destructive actions ───────────────────────────────────────── */

  function initConfirm() {
    // Capture phase, so this runs before the form's own submit listener and
    // initForms can see defaultPrevented.
    document.addEventListener('submit', e => {
      const form = e.target;

      // The button that was pressed is checked as well as the form. Every
      // confirmation in the app is meant to sit on the <form>, but one had
      // drifted onto the <button> inside it — where nothing read it, so an
      // irreversible delete went through on a single click and looked exactly
      // like a working confirmation to anyone reading the markup. Honouring
      // both means that misplacement degrades to a working prompt instead of
      // silently removing the guard.
      const msg = form.dataset.confirm ?? e.submitter?.dataset.confirm;
      if (msg && !window.confirm(msg)) e.preventDefault();
    }, true);
  }

  /* ── service worker + install ──────────────────────────────────────────── */

  function initServiceWorker() {
    if (!('serviceWorker' in navigator)) return;

    /*
     * When a new worker takes over, this page is still running the CSS and JS
     * the old one handed out — so reload once to pick up the new build.
     *
     * Guarded on there having been a controller already. On a first visit the
     * worker installs and claims an uncontrolled page, which fires this too,
     * and reloading someone the moment they arrive is its own bug.
     */
    const hadController = !!navigator.serviceWorker.controller;
    let reloading = false;

    navigator.serviceWorker.addEventListener('controllerchange', () => {
      if (!hadController || reloading) return;
      reloading = true;
      window.location.reload();
    });

    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js').catch(() => {});
    });

    /* The sign-out response sends Clear-Site-Data instead of messaging the
       worker. A postMessage from a page that is about to unload is best-effort,
       and the purge it triggered also deleted the precached /offline page with
       nothing to repopulate it — so offline mode died after the first logout. */
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


  /* ── My Circle ───────────────────────────────────────────────────────────
     A circle is not a form you fill in once. Someone met this month becomes a
     friend, a partner, or someone you no longer speak to — so people and the
     details about them are added whenever there is something to add, rather
     than into five fixed slots.

     Rows are cloned from a <template> in the markup rather than built from an
     HTML string, because the CSP forbids inline script and innerHTML of markup
     assembled here would be one more place for someone else's text to end up
     parsed as HTML. The field names are stamped on after cloning, so the
     server still receives a plain circle[i][notes][] array and needs no
     knowledge that any of this happened. */
  function initCircle() {
    const list = document.querySelector('[data-circle]');
    const template = document.querySelector('[data-person-template]');
    if (!list || !template) return;

    // Renumber from scratch after every change. Cheaper to reason about than
    // tracking a next-index counter, which drifts the moment a row is removed.
    const renumber = () => {
      list.querySelectorAll('[data-person]').forEach((person, i) => {
        const name = person.querySelector('[data-field="name"], input[name$="[name]"]');
        const rel  = person.querySelector('[data-field="relationship"], input[name$="[relationship]"]');
        if (name) { name.name = `circle[${i}][name]`; name.setAttribute('aria-label', `Person ${i + 1} name`); }
        if (rel)  { rel.name  = `circle[${i}][relationship]`; rel.setAttribute('aria-label', `Person ${i + 1} relationship`); }
        person.querySelectorAll('[data-notes] input').forEach((note, n) => {
          note.name = `circle[${i}][notes][]`;
          note.setAttribute('aria-label', `Detail ${n + 1} about person ${i + 1}`);
        });
      });
    };

    document.querySelector('[data-add-person]')?.addEventListener('click', () => {
      const row = template.content.firstElementChild.cloneNode(true);
      list.appendChild(row);
      renumber();
      row.querySelector('input')?.focus();
    });

    // Delegated, so it works for rows that did not exist when this ran.
    list.addEventListener('click', (event) => {
      const button = event.target.closest('[data-add-note]');
      if (!button) return;

      const notes = button.closest('[data-person]')?.querySelector('[data-notes]');
      if (!notes) return;

      const last = notes.querySelector('input:last-of-type');
      const input = last ? last.cloneNode(true) : null;
      if (!input) return;

      input.value = '';
      notes.appendChild(input);
      renumber();
      input.focus();
    });

    renumber();
  }

  /* ── boot ──────────────────────────────────────────────────────────────── */

  initTheme();
  document.addEventListener('DOMContentLoaded', () => {
    initFlash();
    initMotion();
    initCounters();
    initPasswordReveal();
    initAutogrow();
    initOptions();
    initCircle();
    initConfirm();   // capture phase — must be able to cancel before initForms
    initForms();
    initInstall();
    initServiceWorker();
  });
})();
