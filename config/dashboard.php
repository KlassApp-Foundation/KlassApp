<?php

return [
    /*
     | Admin dashboard v2 (design handoff-2026-09-30 profiles, Part B).
     | Default off: set DASHBOARD_V2_ENABLED=true on the environment to turn
     | the new layout on, or append ?v2=1 for a single request.
     */
    'v2_enabled' => env('DASHBOARD_V2_ENABLED', false),
];
