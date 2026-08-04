<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response hardening.
 *
 * The CSP is strict because it can afford to be: every asset this app loads —
 * fonts, GSAP, CSS, JS — is served from our own origin. There is no CDN, no
 * analytics, no font host. So 'self' is genuinely enough, and anything that
 * shows up in the console as a CSP violation is either a mistake or an attack.
 *
 * 'unsafe-inline' is absent from script-src on purpose. Inline handlers and
 * inline <script> blocks will not run — pass data to JS through data-*
 * attributes or a JSON <script type="application/json"> block instead.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Media blobs are streamed by a controller; don't touch their headers
        // beyond the essentials, and never cache them in a shared cache.
        $directives = [
            "default-src 'self'",
            "script-src 'self'",
            "style-src 'self' 'unsafe-inline'",   // Blade sets a few inline custom properties
            "img-src 'self' data: blob:",
            "media-src 'self' blob:",
            "font-src 'self'",
            "connect-src 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
        ];

        /*
         * Only when the request already arrived over TLS — the same condition
         * HSTS uses below, and for the same reason.
         *
         * Sent unconditionally, this directive bricks a plain-HTTP deployment
         * completely. asset() builds absolute URLs from APP_URL, so on an
         * http:// origin the browser is handed http://host/css/app.css and
         * told to rewrite it to https://host/css/app.css — where nothing is
         * listening. Every stylesheet, script and font fails, and the page
         * renders as raw unstyled HTML.
         *
         * The failure is worth describing because it is so misleading: the
         * files are served correctly, 200 with the right content type, and
         * curl fetches them happily. Only a real browser applies the CSP, so
         * it looks like broken assets rather than a header. It cost a live
         * deploy to find on http:// before TLS was in place.
         */
        if ($request->secure()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        $csp = implode('; ', $directives);

        $headers = [
            'Content-Security-Policy'   => $csp,
            'X-Content-Type-Options'    => 'nosniff',
            'X-Frame-Options'           => 'DENY',
            'Referrer-Policy'           => 'strict-origin-when-cross-origin',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            // No camera, no mic, no location. This app has no use for any of them.
            'Permissions-Policy'        => 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()',
        ];

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        /*
        | HTML must not be stored, and this is not the same as not being reused.
        |
        | Symfony's default for a response nobody configured is "no-cache,
        | private". `private` does keep it out of shared caches, and `no-cache`
        | does force revalidation — so the back button goes through the auth
        | middleware and correctly lands on /login. But `no-cache` explicitly
        | *permits storage*: every desire, reading and My World page gets written
        | to the browser's disk cache in plaintext and stays there after logout.
        |
        | Worse, the back/forward cache is not governed by these values at all.
        | `no-store` is the specific directive that makes Chrome and Firefox
        | refuse to put a page in bfcache. Without it, the fully rendered DOM of
        | the last page viewed is retained in memory, and pressing Back after
        | signing out restores somebody's journal on a shared device — session
        | invalidation cannot help, because nothing is re-fetched.
        |
        | JSON counts too, and did not used to. /stories/{story}/state returns
        | the complete text of a reading and the reveal screen polls it every
        | couple of seconds — every one of those responses was landing in the
        | browser's on-disk cache in plaintext under Symfony's "no-cache,
        | private" default.
        |
        | Media is excluded because it sets its own header in MediaController
        | and because stamping this on a partial response would interfere with
        | the range requests the player scrubs with.
        */
        $type = (string) $response->headers->get('Content-Type');

        if (str_contains($type, 'text/html') || str_contains($type, 'application/json')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
