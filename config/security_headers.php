<?php

return [
    // Master switch for the SecurityHeaders middleware (SECURITY_HEADERS_ENABLED).
    'enabled' => filter_var(env('SECURITY_HEADERS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    // HSTS is only sent on secure requests in the production environment. No preload.
    'hsts' => 'max-age=31536000; includeSubDomains',

    'referrer_policy' => 'strict-origin-when-cross-origin',

    // The app does not use these browser features (checked by grep), so they are denied.
    'permissions_policy' => 'camera=(), microphone=(), geolocation=(), payment=()',

    // Sent as Content-Security-Policy-Report-Only: it never blocks anything.
    'csp_report_only' => implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https:",
        "style-src 'self' 'unsafe-inline' https:",
        "img-src 'self' data: blob: https:",
        "font-src 'self' data: https:",
        "connect-src 'self' https: wss:",
        "frame-src 'self' https:",
        "frame-ancestors 'self'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self' https:",
    ]),
];
