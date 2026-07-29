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
        $csp = implode('; ', [
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
            'upgrade-insecure-requests',
        ]);

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

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
