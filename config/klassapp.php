<?php

/*
| KlassApp launch settings.
|
| signup_enabled: when false, no new school or school admin can be created
| through self-serve sign-up (register form, Google sign-in for an unknown
| email, the sign-up bootstrap service). /register shows the early access
| page instead. Platform admins can still create schools.
| privacy_notice_url: the public privacy notice linked from consent text.
| launch_at: fixed UTC instant used by the early access countdown.
*/

return [
    'signup_enabled' => (bool) env('SIGNUP_ENABLED', true),

    'privacy_notice_url' => env('PRIVACY_NOTICE_URL', '/privacy-policy'),

    'launch_at' => '2026-12-15T00:00:00Z',
];
