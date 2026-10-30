<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invite link expiry
    |--------------------------------------------------------------------------
    |
    | One value drives the expiry of BOTH teacher and co-admin invite links:
    | links are single-use and expire this many hours after issue or reissue.
    | Every invite email and page derives its wording from the invite's
    | expires_at timestamp, so nothing else needs to change when this value does.
    |
    */

    'expiry_hours' => (int) env('INVITE_EXPIRY_HOURS', 72),

    /* Early-adopter school invites are deliberately longer-lived (7 days). */
    'school_expiry_hours' => (int) env('SCHOOL_INVITE_EXPIRY_HOURS', 168),

    /*
     * staff_invite is not approved by Meta. Email is the channel.
     * Leave this false. Tests that need the branch use a fake, never a live send.
     */
    'staff_whatsapp_enabled' => (bool) env('STAFF_INVITE_WHATSAPP_ENABLED', false),

    'create_attempts' => (int) env('TEACHER_INVITE_CREATE_ATTEMPTS', 30),
    'create_per_invite_attempts' => (int) env('TEACHER_INVITE_CREATE_PER_INVITE_ATTEMPTS', 10),
    'accept_attempts' => (int) env('TEACHER_INVITE_ACCEPT_ATTEMPTS', 20),
    'accept_school_attempts' => (int) env('TEACHER_INVITE_ACCEPT_SCHOOL_ATTEMPTS', 200),
    'rate_decay_seconds' => (int) env('TEACHER_INVITE_RATE_DECAY_SECONDS', 3600),

    /* Tests opt in. The suite must not share a file-cache counter. */
    'force_rate_limits' => false,

];
