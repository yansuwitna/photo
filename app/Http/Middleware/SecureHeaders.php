<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecureHeaders Middleware
 *
 * Menambahkan HTTP response headers yang diperlukan agar:
 * 1. Kamera (getUserMedia) dapat diakses melalui HTTPS / Cloudflare Zero Trust
 * 2. Browser menganggap konteks sebagai "Secure Context" meski di belakang proxy/tunnel
 * 3. Permissions-Policy mengizinkan akses kamera, mikrofon, dan display capture
 */
class SecureHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // -----------------------------------------------------------------------
        // 1. Permissions-Policy: Izinkan kamera & mikrofon dari origin yang sama
        //    Penting untuk getUserMedia() agar tidak diblokir browser
        // -----------------------------------------------------------------------
        $response->headers->set(
            'Permissions-Policy',
            'camera=(*), microphone=(*), display-capture=(*), fullscreen=(self)'
        );

        // -----------------------------------------------------------------------
        // 2. Cross-Origin-Opener-Policy & Cross-Origin-Embedder-Policy
        //    Diperlukan untuk SharedArrayBuffer & MediaDevices di beberapa browser
        // -----------------------------------------------------------------------
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->headers->set('Cross-Origin-Resource-Policy', 'cross-origin');

        // -----------------------------------------------------------------------
        // 3. X-Frame-Options: izinkan embed dari Cloudflare Zero Trust portal
        // -----------------------------------------------------------------------
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // -----------------------------------------------------------------------
        // 4. Strict-Transport-Security: paksa HTTPS selalu (bantu isSecureContext)
        // -----------------------------------------------------------------------
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
