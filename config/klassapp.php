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
*/

return [
    'signup_enabled' => filter_var(env('SIGNUP_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),

    'launch_at' => '2026-12-15T00:00:00Z',

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
