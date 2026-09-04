/* ============================================================================
   Escalate — service worker
   ----------------------------------------------------------------------------
   This worker exists to make the app installable and to survive a dead
   connection. It is deliberately NOT a content cache.

   The privacy rule, and the reason this file is short:

     Nothing a user writes or is told is ever written to the Cache API.

   Stories, gratitude entries, rewinds, affirmations and narration audio all
   stay out of it. Cache Storage is plaintext on disk, outlives logout, and is
   readable by anything with local access to the device — which makes it
   exactly the wrong place for a private journal. So the allowlist below is
   static assets only, matched by explicit path, and every navigation goes to
   the network. If the network is gone the user gets the offline page, not a
   stale copy of yesterday's reading.

   Consequence to be aware of: the app does not work offline beyond the shell.
   That is the intended trade. Reading your own journal requires a connection.
   ========================================================================= */

/* Bumped to v3 to evict every escalate-v2-shell cache still in the wild, which
   were holding the pre-brand app icons and the pre-brand manifest.

   The icons were replaced on disk without this constant moving, so activate()
   kept the old cache and every phone that had ever opened the app went on being
   handed the old artwork — and the old theme_color with it. That is the same
   failure the note on VERSIONED below describes for CSS and JS, arriving a
   second time by a different door: the lesson was applied to VERSIONED and not
   to SHELL_ASSETS.

   Bumping fixes the phones. Not precaching the icons at all, below, is what
   stops it happening a third time. */
const VERSION = 'escalate-v3';
const SHELL = `${VERSION}-shell`;

/*
 * Assets whose URL never changes, so precaching them by path is sound.
 * Only these. Adding a page here would be a privacy bug.
 */
const SHELL_ASSETS = [
  '/offline',
  '/fonts/lora-var.woff2',
  '/fonts/raleway-var.woff2',
  '/manifest.webmanifest',
  // The app icons used to be here and are deliberately not any more. Nothing in
  // the app renders them — the topbar mark comes from /brand/ — they are read
  // by the operating system at install time, from the network. Precaching them
  // bought nothing offline and created the one thing that can go wrong with a
  // fixed URL: serving a stale copy for ever.
];

/*
 * Assets asset_v() stamps with ?v=<content-hash>.
 *
 * These are cached too, but on first fetch and under their *full* URL — never
 * precached by bare path, and never matched with the query discarded. That
 * distinction is the whole reason this file was rewritten.
 *
 * What went wrong: '/js/app.js' and '/css/app.css' were precached by bare path
 * on install, and the fetch handler matched them with the query ignored. The
 * page always asks for '/js/app.js?v=<hash>', so that made every request a hit
 * against the bare-path entry — every time, for ever. The content hash, whose
 * entire job is to turn new bytes into a new URL, was being thrown away at the
 * one point where it mattered.
 *
 * The result was not a stale asset for an hour; it was permanent. install()
 * re-runs only when this file's bytes change, activate() kept any cache named
 * SHELL, and nothing else ever wrote these entries. So every browser that had
 * ever opened the app was pinned to the CSS and JS from its first visit and
 * stayed pinned through every deploy after it. The dead "Add someone" and "Add
 * a detail" buttons, and feelings chips that toggled without ever looking
 * toggled, were all this: fixes shipped weeks earlier that the phone was never
 * allowed to see. Nothing was wrong with the fixes. They simply never arrived.
 *
 * Keying on the full URL makes it self-correcting by construction: new bytes
 * mean a new hash, a new hash means a new key, a new key means a miss, and a
 * miss goes to the network.
 */
const VERSIONED = ['/css/app.css', '/js/app.js', '/js/gsap.min.js'];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(SHELL)
      .then(cache => cache.addAll(SHELL_ASSETS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(
        keys.filter(k => k !== SHELL).map(k => caches.delete(k))
      ))
      .then(() => self.clients.claim())
  );
});

/*
 * A logout should leave nothing behind.
 *
 * Sign-out now sends Clear-Site-Data, which is deterministic and does not
 * depend on a message from a page that is already unloading. This handler stays
 * for any client still calling it — but it re-precaches the shell afterwards.
 * The previous version deleted every cache including /offline, and nothing ever
 * put it back: install() only re-runs when this file's bytes change, and the
 * fetch handler never caches navigations. So offline mode died permanently
 * after the first sign-out on any installation.
 */
self.addEventListener('message', event => {
  if (event.data !== 'escalate:purge') return;

  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.map(k => caches.delete(k))))
      .then(() => caches.open(SHELL))
      .then(cache => cache.addAll(SHELL_ASSETS))
      .catch(() => {})
  );
});

/*
 * The allowlist, and nothing else.
 *
 * This used to also match anything under /css/, /js/, /fonts/ and /icons/,
 * which turned the rule into "any same-origin GET below these four prefixes is
 * cached with credentials and survives logout". No route lives there today, so
 * nothing leaked — but /media/image/{id} already exists, and the day someone
 * adds /icons/{id} or a /js/config endpoint it would silently become a
 * plaintext, credentialed, logout-surviving cache entry with no change here.
 * An explicit list cannot drift that way.
 */
const isShellAsset = url => SHELL_ASSETS.includes(url.pathname);
const isVersioned  = url => VERSIONED.includes(url.pathname);

/*
 * Drop older builds of the same file once a new one is stored, so the cache
 * does not gain another copy of app.css on every deploy for the rest of time.
 */
async function keepOnly(cache, request) {
  const keep = new URL(request.url).pathname;

  for (const key of await cache.keys()) {
    const url = new URL(key.url);
    if (url.pathname === keep && key.url !== request.url) {
      await cache.delete(key);
    }
  }
}

/* ── push ──────────────────────────────────────────────────────────────────
   The payload is JSON built by App\Support\Push and deliberately carries
   nothing private: a notification lands on a lock screen, which anybody near
   the phone can read.

   The one thing that does arrive with real words in it is an announcement,
   and that is the same rule rather than an exception to it: an announcement is
   written by an administrator and sent to every user, so it is already known
   to everybody and there is nothing left for a passer-by to learn. See
   App\Models\Announcement::notificationBody().

   Every field is defaulted. A push with no payload, or a malformed one, still
   shows something rather than throwing inside the worker — where the error is
   invisible and the notification simply never appears.
   ------------------------------------------------------------------------ */
self.addEventListener('push', event => {
  let data = {};

  try {
    data = event.data ? event.data.json() : {};
  } catch {
    // Not JSON. Fall through to the defaults rather than showing nothing.
  }

  event.waitUntil(
    self.registration.showNotification(data.title || 'Escalate', {
      body: data.body || 'A few minutes for today?',
      icon: '/icons/icon-192.png',
      badge: '/icons/icon-192.png',
      // The tag decides what this does to a notification already on the lock
      // screen: same tag replaces, different tag sits beside it. The daily
      // reminder sends one fixed tag on purpose, so three unread nudges never
      // stack up. An announcement sends its own, because news must not
      // silently replace the news before it. Older payloads carry no tag at
      // all, so the reminder's is the fallback.
      tag: data.tag || 'escalate-reminder',
      data: { url: data.url || '/today' },
    })
  );
});

/* Tapping it should land in the app, and reuse a window that is already open
   rather than opening a second copy of the same page. */
self.addEventListener('notificationclick', event => {
  event.notification.close();

  const target = (event.notification.data && event.notification.data.url) || '/today';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windows => {
      for (const client of windows) {
        if ('focus' in client) {
          client.navigate(target);
          return client.focus();
        }
      }

      return self.clients.openWindow(target);
    })
  );
});

self.addEventListener('fetch', event => {
  const { request } = event;

  // Only ever touch same-origin GETs. Anything else — POSTs, audio range
  // requests, anything cross-origin — goes straight to the network untouched.
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  // Narration is authenticated, private, and large. Never intercepted.
  if (url.pathname.startsWith('/media/')) return;

  /*
   * Hashed assets: cache-first on the exact URL, query and all.
   *
   * Matching the full URL is the fix, and it is not a tuning choice — dropping
   * the query here is what pinned every installed client to its first-ever
   * build. A hit can only ever be the same bytes that were asked for, because
   * the hash in the key is derived from those bytes.
   *
   * Scoped to the one cache rather than caches.match(), which searches every
   * cache in the origin.
   */
  if (isVersioned(url)) {
    event.respondWith(
      caches.open(SHELL).then(cache =>
        cache.match(request).then(hit =>
          hit || fetch(request).then(res => {
            if (res.ok) {
              cache.put(request, res.clone()).then(() => keepOnly(cache, request));
            }
            return res;
          })
        )
      )
    );
    return;
  }

  /* Fixed-URL shell assets: fonts, icons, the manifest, the offline page. */
  if (isShellAsset(url)) {
    event.respondWith(
      caches.open(SHELL).then(cache =>
        cache.match(request).then(hit =>
          hit || fetch(request).then(res => {
            if (res.ok) cache.put(request, res.clone());
            return res;
          })
        )
      )
    );
    return;
  }

  // Navigations: network only, offline page as the fallback. No content cached.
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() =>
        // respondWith(undefined) rejects and shows the browser's own error
        // page, which is what happened whenever /offline was missing.
        caches.match('/offline').then(hit => hit || Response.error())
      )
    );
  }
});
