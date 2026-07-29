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

/* A logout should leave nothing behind. The app posts this on sign-out. */
self.addEventListener('message', event => {
  if (event.data === 'escalate:purge') {
    event.waitUntil(caches.keys().then(keys => Promise.all(keys.map(k => caches.delete(k)))));
  }
});

const isShellAsset = url =>
  SHELL_ASSETS.includes(url.pathname) ||
  url.pathname.startsWith('/fonts/') ||
  url.pathname.startsWith('/icons/') ||
  url.pathname.startsWith('/css/') ||
  url.pathname.startsWith('/js/');

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
    // Cache-first: these are versioned by the cache name and change on deploy.
    event.respondWith(
      caches.match(request).then(hit => hit || fetch(request).then(res => {
        if (res.ok) {
          const copy = res.clone();
          caches.open(SHELL).then(c => c.put(request, copy));
        }
        return res;
      }))
    );
    return;
  }

  // Navigations: network only, offline page as the fallback. No content cached.
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => caches.match('/offline'))
    );
  }
});
