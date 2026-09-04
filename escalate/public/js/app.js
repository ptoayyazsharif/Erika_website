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

  /* ── the daily reminder ────────────────────────────────────────────────── */

  /*
   * Asking to send notifications.
   *
   * Deliberately not on load. A permission prompt fired at somebody who just
   * arrived gets refused, and a browser refusal is close to permanent — most
   * people never find the setting to undo it. So the card is shown, and the
   * real prompt only opens when they press Yes. One press of theirs for one
   * prompt of ours.
   *
   * Shown only where it could work: a service worker, a PushManager, and a
   * permission still undecided. On an installed iPhone PWA all three are true;
   * in an iPhone browser tab PushManager is absent, so nothing is offered
   * rather than a button that silently does nothing.
   */
  function initPush() {
    const card = $('[data-push-prompt]');
    if (!card) return;

    const KEY = 'escalate.push-prompt';
    const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

    const remember = (value) => {
      try {
        localStorage.setItem(KEY, value);
      } catch {
        // Private mode. It offers once more next time, which is the gentler
        // of the two failures.
      }
    };

    let dismissed = false;
    try {
      dismissed = localStorage.getItem(KEY) === 'no';
    } catch { /* see above */ }

    // Before the early return: a device that already said yes never reaches the
    // card again, and it is exactly the device that needs repairing after a key
    // change.
    reconcilePush(card);

    if (!supported || dismissed || Notification.permission !== 'default') return;

    card.hidden = false;

    $('[data-push-no]')?.addEventListener('click', () => {
      card.hidden = true;
      remember('no');
    });

    $('[data-push-yes]')?.addEventListener('click', async () => {
      card.hidden = true;

      try {
        if (await Notification.requestPermission() !== 'granted') {
          remember('no');
          return;
        }

        const registration = await navigator.serviceWorker.ready;

        const subscription = await registration.pushManager.subscribe({
          // Required by every browser: a push that cannot show a notification
          // is not allowed, which suits an app that only ever sends visible
          // ones anyway.
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(card.dataset.pushKey),
        });

        const ok = await tellServer(card, subscription);

        toast(ok ? 'Reminders on.' : 'Could not turn reminders on.');
        remember(ok ? 'yes' : 'no');
      } catch {
        // A refusal, a browser that changed its mind, an offline moment. None
        // of it is worth an error in front of somebody who only wanted a
        // reminder.
        toast('Could not turn reminders on.');
      }
    });
  }

  /*
   * The VAPID key is base64url; subscribe() wants raw bytes. Standard
   * conversion — the padding and the two character swaps are the whole of it,
   * and getting either wrong fails inside the browser with an opaque error.
   */
  function urlBase64ToUint8Array(base64) {
    const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4))
      .replace(/-/g, '+')
      .replace(/_/g, '/');

    const raw = atob(padded);
    const output = new Uint8Array(raw.length);

    for (let i = 0; i < raw.length; i++) output[i] = raw.charCodeAt(i);

    return output;
  }

  /* Hand one subscription to the server. Returns whether it was accepted. */
  async function tellServer(card, subscription) {
    const json = subscription.toJSON();

    try {
      const response = await fetch(card.dataset.pushUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({
          endpoint: subscription.endpoint,
          p256dh: json.keys && json.keys.p256dh,
          auth: json.keys && json.keys.auth,
          // The device knows where it is; the server does not. This is what
          // lets the reminder arrive at nine in the morning wherever somebody
          // actually is.
          timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        }),
      });

      return response.ok;
    } catch {
      return false;
    }
  }

  /*
   * Re-subscribe a device whose server keys have changed under it.
   *
   * A subscription is bound to the public key it was created with, and the
   * push service rejects anything signed by a different pair. So if the keys on
   * the server ever change, every phone in the beta goes silently unreachable
   * and stays that way for ever: initPush() only offers itself where permission
   * is still undecided, and permission was granted long ago.
   *
   * There is no button that changes the keys any more — that was removed, and
   * the route refuses. But an administrator can still paste a new pair into
   * Settings → Reminders if one ever leaks, and this is what makes that
   * survivable rather than terminal. Permission is already granted, so
   * subscribe() shows nobody anything: it swaps the dead subscription for a
   * live one and tells the server, quietly, on the next page somebody opens.
   * It also puts back a row lost for any other reason.
   *
   * Deliberately does nothing when the browser will not say which key an
   * existing subscription carries. Unsubscribing and resubscribing blindly on
   * every page load would churn the table and re-register the same device for
   * ever; a device that cannot be checked keeps what it has.
   */
  async function reconcilePush(card) {
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) return;
    if (Notification.permission !== 'granted') return;
    if (!card.dataset.pushKey) return;

    try {
      const registration = await navigator.serviceWorker.ready;
      const wanted = urlBase64ToUint8Array(card.dataset.pushKey);
      const existing = await registration.pushManager.getSubscription();

      if (existing) {
        const held = existing.options && existing.options.applicationServerKey;
        if (!held) return;
        if (sameKey(held, wanted)) return;

        await existing.unsubscribe();
      }

      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: wanted,
      });

      await tellServer(card, subscription);
    } catch {
      // A repair that fails is exactly as bad as the state it found, and the
      // person did not ask for it. Nothing is said.
    }
  }

  function sameKey(held, wanted) {
    const a = new Uint8Array(held);
    if (a.length !== wanted.length) return false;

    for (let i = 0; i < a.length; i++) {
      if (a[i] !== wanted[i]) return false;
    }

    return true;
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

  /*
   * The topbar button only ever appeared when `beforeinstallprompt` fired,
   * which is Chrome and Android. iOS Safari never fires it, so on an iPhone
   * there was no way to discover the app is installable at all — and for a beta
   * handed round by DM that is most of the audience. Hence the tip, which is
   * shown once, is dismissible, and says something different on iOS because
   * the Share sheet is the only route there.
   */
  function initInstall() {
    const btn = $('[data-install]');
    const tip = $('[data-install-tip]');
    const tipText = $('[data-install-tip-text]');
    const tipGo = $('[data-install-tip-go]');
    const TIP_KEY = 'escalate.install-tip';

    let prompt = null;

    // An already-installed window has nothing to be told. Two checks, because
    // the standard one is not the one iOS answers to.
    const installed = () =>
      matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;

    // Chooses the wording only, never whether the feature runs, so a browser
    // that reports something unexpected still gets a usable message.
    const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent);

    // localStorage throws outright in some privacy modes, so every touch of it
    // is wrapped. Failing to remember a dismissal is a far gentler outcome than
    // a tip that cannot be dismissed at all.
    const dismissed = () => {
      try {
        return localStorage.getItem(TIP_KEY) === 'dismissed';
      } catch {
        return false;
      }
    };

    const dismiss = () => {
      if (tip) tip.hidden = true;
      try {
        localStorage.setItem(TIP_KEY, 'dismissed');
      } catch {
        // Then it offers once more next time. Acceptable.
      }
    };

    const showTip = (message, canPrompt) => {
      if (!tip || !tipText || dismissed() || installed()) return;
      tipText.textContent = message;
      if (tipGo) tipGo.hidden = !canPrompt;
      tip.hidden = false;
    };

    const openPrompt = async () => {
      if (!prompt) {
        toast('Use your browser menu → Add to Home Screen.');
        return;
      }
      prompt.prompt();
      const { outcome } = await prompt.userChoice;
      prompt = null;
      if (btn) btn.hidden = true;
      if (tip) tip.hidden = true;
      if (outcome === 'accepted') toast('Escalate is on your home screen.');
    };

    if (installed()) return;

    window.addEventListener('beforeinstallprompt', e => {
      e.preventDefault();
      prompt = e;
      if (btn) btn.hidden = false;
      showTip('Keep Escalate on your home screen — it opens like an app.', true);
    });

    if (isIos) {
      showTip('Add Escalate to your home screen: tap Share, then Add to Home Screen.', false);
    }

    btn?.addEventListener('click', openPrompt);
    tipGo?.addEventListener('click', openPrompt);
    $('[data-install-tip-close]')?.addEventListener('click', dismiss);

    window.addEventListener('appinstalled', () => {
      if (btn) btn.hidden = true;
      dismiss();
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
    initPush();
    initAutogrow();
    initOptions();
    initCircle();
    initConfirm();   // capture phase — must be able to cancel before initForms
    initForms();
    initInstall();
    initServiceWorker();
  });
})();
