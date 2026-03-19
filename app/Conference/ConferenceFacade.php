<?php

namespace App\Conference;
use Illuminate\Support\Facades\Facade;

class ConferenceFacade extends Facade
{
	protected static function getFacadeAccessor()
    {
        return 'conference';
    }
}