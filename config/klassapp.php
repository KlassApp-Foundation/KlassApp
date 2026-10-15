<?php

/*
| KlassApp launch settings.
|
| signup_enabled: the single sign-up switch (env SIGNUP_ENABLED, default
| true). When false, no new school or school admin can be created through
| self-serve sign-up (register form, Google sign-in for an unknown email,
| the sign-up bootstrap service, Toshi create mode for non-platform users).
| GET /register shows the early access page instead. Parsed with
| FILTER_VALIDATE_BOOLEAN so the text "false", "0", "off" or an empty value
| closes sign-up ((bool) "false" would be true).
|
| The privacy notice link is read from services.whatsapp.privacy_notice_url
| (env PRIVACY_NOTICE_URL), the same value the demo WhatsApp welcome uses.
| There is no default URL: when it is empty, no link is shown.
|
| launch_at: fixed UTC instant used by the early access countdown.
|
| countries: fallback list for the early access country select, used when
| the app's countries table has no active rows.
|
| demo: the public follow-along page at /demo and the WhatsApp BIND code.
|   whatsapp_number   env WHATSAPP_DEMO_NUMBER, the number the page links to
|                     with wa.me. Empty hides the WhatsApp buttons.
|   bind_ttl_minutes  env DEMO_BIND_TTL_MINUTES (default 45). A number bound
|                     with BIND <code> is unlinked by demo:expire-chats after
|                     this many minutes.
|   bind_requires_approval  env DEMO_BIND_REQUIRES_APPROVAL (default false).
|                     When true, BIND <code> records a request on the admin
|                     Approvals page instead of linking at once; approval
|                     links the number and the TTL counts from the approval.
|                     A request nobody approves closes after the same TTL.
|   release_webhook_url     env DEMO_RELEASE_WEBHOOK_URL. When set together
|   release_webhook_secret  with env DEMO_RELEASE_WEBHOOK_SECRET, a demo
|                     school's marks release POSTs JSON (the exam, and each
|                     student's code and class, nothing else) signed with
|                     HMAC-SHA256 in the X-KlassApp-Signature header.
|   links             shared links shown on the page. An entry without a url
|                     is not shown. The privacy notice link comes from
|                     services.whatsapp.privacy_notice_url (PRIVACY_NOTICE_URL).
*/

return [
    'signup_enabled' => filter_var(env('SIGNUP_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),

    'launch_at' => '2026-12-15T00:00:00Z',

    'demo' => [
        'whatsapp_number' => env('WHATSAPP_DEMO_NUMBER'),
        'bind_ttl_minutes' => (int) env('DEMO_BIND_TTL_MINUTES', 45),
        'bind_requires_approval' => filter_var(env('DEMO_BIND_REQUIRES_APPROVAL', false), FILTER_VALIDATE_BOOLEAN),
        'release_webhook_url' => env('DEMO_RELEASE_WEBHOOK_URL'),
        'release_webhook_secret' => env('DEMO_RELEASE_WEBHOOK_SECRET'),
        'links' => [
            ['label' => 'n8n workflows', 'url' => null],
            ['label' => 'Project repository', 'url' => 'https://github.com/KlassApp-Foundation/KlassApp'],
        ],
    ],

    'countries' => [
        'Argentina', 'Australia', 'Bangladesh', 'Brazil', 'Canada', 'Chile',
        'China', 'Colombia', 'Egypt', 'France', 'Germany', 'Ghana', 'India',
        'Indonesia', 'Ireland', 'Italy', 'Japan', 'Kenya', 'Malaysia', 'Mexico',
        'Netherlands', 'New Zealand', 'Nigeria', 'Pakistan', 'Philippines',
        'Poland', 'Portugal', 'Rwanda', 'Saudi Arabia', 'Singapore',
        'South Africa', 'South Korea', 'Spain', 'Sweden', 'Tanzania',
        'Thailand', 'Turkey', 'Uganda', 'Ukraine', 'United Arab Emirates',
        'United Kingdom', 'United States', 'Vietnam', 'Zambia',
    ],
];
