<?php

use App\Services\ActivityLogger;

if (! function_exists('activity')) {
    /**
     * Start a new activity log entry (Spatie-compatible helper).
     */
    function activity(?string $logName = null): ActivityLogger
    {
        return new ActivityLogger($logName);
    }
}
