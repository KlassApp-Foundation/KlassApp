<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode as Middleware;

class CheckForMaintenanceMode extends Middleware
{
    /**
     * The URIs that should be reachable while maintenance mode is enabled.
     *
     * @var array
     */
    protected $except = [
        // Google's OAuth consent screen links these. Keep them up for guests
        // while the rest of the app is in maintenance.
        'privacy',
        'terms',
        'ploicy',
        'policy',
        'privacy-policy',
        'terms-of-service',
    ];
}
