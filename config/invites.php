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

];
