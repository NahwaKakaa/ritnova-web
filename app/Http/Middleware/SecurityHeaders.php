<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan untuk semua respons web. CSP sengaja longgar untuk skrip/gaya
 * (ada skrip tema inline dan embed YouTube), tetapi melarang halaman dibingkai situs lain,
 * plugin, dan pengiriman form ke domain lain.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (function_exists('header_remove')) {
            @header_remove('X-Powered-By');
        }
        $headers = $response->headers;
        $headers->remove('X-Powered-By');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Content-Security-Policy', "frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'");
        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        if ($request->user()) {
            // Halaman berisi data pribadi tidak boleh disimpan di cache browser/proxy bersama.
            $headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
