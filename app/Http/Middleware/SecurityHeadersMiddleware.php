<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecurityHeadersMiddleware
{
    // public const SRI = [

    // public const SRI = [

    //     // ─── Bootstrap CSS ────────────────────────────────────────────────────
    //     'bootstrap_css' => [
    //         'url'  => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    //         'hash' => 'sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH',
    //     ],

    //     // ─── Bootstrap JS (bundle = Popper مضمّن) ────────────────────────────
    //     'bootstrap_js' => [
    //         'url'  => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    //         'hash' => 'sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmFuHgQBmLXE8bFBVL0OFYaKk6p',
    //     ],

    //     // ─── Font Awesome 6.7.2 (CSS) ─────────────────────────────────────────
    //     'fontawesome_css' => [
    //         'url'  => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css',
    //         'hash' => 'sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==',
    //     ],
    // ];

    /*
    |==========================================================================
    | CDN Domains
    |==========================================================================
    */
    private const SCRIPT_CDN = [
        'https://cdn.jsdelivr.net',
        'https://cdnjs.cloudflare.com',

        'https://www.google.com',
        'https://www.gstatic.com',
        'https://www.recaptcha.net',
        'https://www.google.com/recaptcha/api.js',
        'https://cdn.moyasar.com',
    ];

    private const STYLE_CDN = [
        'https://cdn.jsdelivr.net',
        'https://cdnjs.cloudflare.com',
        'https://fonts.googleapis.com',
    ];

    private const FONT_CDN = [
        'https://fonts.gstatic.com',
        'https://cdnjs.cloudflare.com',
        'https://cdn.jsdelivr.net',
        'https://use.fontawesome.com',
        'https://ka-f.fontawesome.com',

        'https://www.google.com',        //  reCAPTCHA
        'https://www.gstatic.com',       //  reCAPTCHA
    ];

    /*
    |==========================================================================
    | Handle
    |==========================================================================
    */
    public function handle(Request $request, Closure $next)
    {
        /*
        |----------------------------------------------------------------------
        | Force HTTPS — production فقط
        |----------------------------------------------------------------------
        */
        if (app()->isProduction() && ! $request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        /*
        |----------------------------------------------------------------------
        | CSP Nonce
        | استخدمه في Blade هكذا:
        |   <script nonce="{{ app('csp_nonce') }}"> ... </script>
        |   <style  nonce="{{ app('csp_nonce') }}"> ... </style>
        |----------------------------------------------------------------------
        */
        $nonce = base64_encode(random_bytes(16));
        app()->instance('csp_nonce', $nonce);

        /*
        |----------------------------------------------------------------------
        | Process Request
        |----------------------------------------------------------------------
        */
        $response = $next($request);

        /*
        |----------------------------------------------------------------------
        | Content-Security-Policy
        |
        | ✅ default-src 'none'     — كل حاجة ممنوعة افتراضياً
        | ✅ بدون unsafe-inline      — nonce بدل unsafe في script و style
        | ✅ بدون wildcards          — domains صريحة
        | ✅ SRI                     — CDN files متحقق منها
        |----------------------------------------------------------------------
        */
        $scriptCdn = implode(' ', self::SCRIPT_CDN);
        $styleCdn = implode(' ', self::STYLE_CDN);
        $fontCdn = implode(' ', self::FONT_CDN);

        /*
         * connect-src: حدّد domains API / WebSocket بتاعتك هنا صراحةً
         * مثال: "'self' wss://ws.example.com https://api.example.com"
         */
        // $connectSrc = "'self'";
        // $connectSrc = "'self' https://cdn.jsdelivr.net https://www.google.com https://www.google.com/recaptcha/api.js";
        $connectSrc = "'self' http://127.0.0.1:5173 ws://127.0.0.1:5173 https://cdn.jsdelivr.net https://www.google.com https://www.google.com/recaptcha/api.js https://api.moyasar.com https://payment.moyasar.com";

        $csp = implode('; ', array_filter([
            // للسماح بإطارات reCAPTCHA
            "frame-src 'self' blob: https://www.google.com https://www.recaptcha.net https://www.google.com/recaptcha/api.js https://api.moyasar.com https://payment.moyasar.com",

            "default-src 'none'",

            // JavaScript: أضفنا 'unsafe-eval' لأن reCAPTCHA تعتمد عليها أحياناً لمعالجة الكود الديناميكي
            "script-src 'self' 'unsafe-eval' 'nonce-{$nonce}' 'strict-dynamic' {$scriptCdn}",

            "style-src 'self' 'unsafe-inline' {$styleCdn}",

            "img-src 'self' data: blob: https://www.gstatic.com https://server.arcgisonline.com",

            "font-src 'self' data: {$fontCdn}",

            // أضفنا gstatic هنا أيضاً لضمان جلب ملفات الدعم
            "connect-src 'self' {$connectSrc} https://www.gstatic.com",

            "form-action 'self' https://api.moyasar.com https://payment.moyasar.com",

            "object-src 'none'",
            "base-uri 'self'",
            app()->isProduction() ? 'upgrade-insecure-requests' : null,
        ]));

        $response->headers->set('Content-Security-Policy', $csp);

        /*
        |----------------------------------------------------------------------
        | Inject nonce تلقائياً في <script> و <style> اللي مش عندهم nonce
        | (للـ tags اللي Laravel أو packages بتولّدها تلقائياً)
        |----------------------------------------------------------------------
        */
        if ($this->isHtmlResponse($response)) {
            $content = $response->getContent();

            $content = preg_replace(
                '/<script(?![^>]*nonce=)([^>]*)>/i',
                '<script nonce="'.$nonce.'"$1>',
                $content
            );

            // $content = preg_replace(
            //     '/<style(?![^>]*nonce=)([^>]*)>/i',
            //     '<style nonce="' . $nonce . '"$1>',
            //     $content
            // );

            $response->setContent($content);
        }

        /*
        |----------------------------------------------------------------------
        | Cache
        |----------------------------------------------------------------------
        */
        $response->headers->set(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, private, max-age=0'
        );
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        /*
        |----------------------------------------------------------------------
        | HSTS — يتبعت لما الكونكشن HTTPS
        |----------------------------------------------------------------------
        */
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        /*
        |----------------------------------------------------------------------
        | Permissions Policy
        |----------------------------------------------------------------------
        */
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()'
        );

        /*
        |----------------------------------------------------------------------
        | Referrer Policy
        |----------------------------------------------------------------------
        */
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        /*
        |----------------------------------------------------------------------
        | MIME Sniffing
        |----------------------------------------------------------------------
        */
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        /*
        |----------------------------------------------------------------------
        | Clickjacking (backup لـ frame-ancestors)
        |----------------------------------------------------------------------
        */
        $response->headers->set('X-Frame-Options', 'DENY');

        /*
        |----------------------------------------------------------------------
        | XSS Protection (legacy)
        |----------------------------------------------------------------------
        */
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        /*
        |----------------------------------------------------------------------
        | Cross-Domain Policies
        |----------------------------------------------------------------------
        */
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        /*
        |----------------------------------------------------------------------
        | DNS Prefetch
        |----------------------------------------------------------------------
        */
        $response->headers->set('X-DNS-Prefetch-Control', 'off');

        /*
        |----------------------------------------------------------------------
        | إخفاء معلومات السيرفر
        |----------------------------------------------------------------------
        */
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }

    /*
    |==========================================================================
    | Helpers
    |==========================================================================
    */

    private function isHtmlResponse($response): bool
    {
        if (
            $response instanceof BinaryFileResponse ||
            $response instanceof StreamedResponse
        ) {
            return false;
        }

        return str_contains(
            $response->headers->get('Content-Type', ''),
            'text/html'
        );
    }
}
