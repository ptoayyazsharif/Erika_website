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

const VERSION = 'escalate-v1';
const SHELL = `${VERSION}-shell`;

/* Only these. Adding a page here would be a privacy bug. */
const SHELL_ASSETS = [
  '/offline',
  '/css/app.css',
  '/js/app.js',
  '/js/gsap.min.js',
  '/fonts/lora-var.woff2',
  '/fonts/raleway-var.woff2',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
  '/manifest.webmanifest',
];

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

self.addEventListener('fetch', event => {
  const { request } = event;

  // Only ever touch same-origin GETs. Anything else — POSTs, audio range
  // requests, anything cross-origin — goes straight to the network untouched.
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  // Narration is authenticated, private, and large. Never intercepted.
  if (url.pathname.startsWith('/media/')) return;

  if (isShellAsset(url)) {
    /*
     * ignoreSearch matters. asset_v() appends ?v=<content-hash> to CSS and JS,
     * and caches.match() compares the full URL including the query by default —
     * so the entries precached on install as '/css/app.css' were never once a
     * hit, and every deploy added a new query-keyed copy that nothing evicted.
     * Freshness was never the problem (the hash guarantees it); the precache
     * was simply dead weight.
     *
     * Scoped to the one cache rather than caches.match(), which searches every
     * cache in the origin.
     */
    event.respondWith(
      caches.open(SHELL).then(cache =>
        cache.match(request, { ignoreSearch: true }).then(hit =>
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
