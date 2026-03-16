<?php

namespace App\Calendar;

use Illuminate\Support\ServiceProvider;
use App\Calendar\CalendarService;

class CalendarServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('calendar', function() {
            return new CalendarService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
       
    }
}
