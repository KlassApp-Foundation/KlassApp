<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
namespace Database\Factories;

use Faker\Generator as Faker;

$factory->define(App\Models\StandardLink::class, function (Faker $faker) {

    return [
        //
        'status'            =>  '1',
    ];
});