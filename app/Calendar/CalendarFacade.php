<?php

namespace App\Calendar;
use Illuminate\Support\Facades\Facade;

class CalendarFacade extends Facade
{
	protected static function getFacadeAccessor()
    {
        return 'calendar';
    }
}