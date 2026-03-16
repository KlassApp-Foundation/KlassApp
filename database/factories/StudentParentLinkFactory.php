<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
namespace Database\Factories;

use App\Models\StudentParentLink;
use Faker\Generator as Faker;

$factory->define(StudentParentLink::class, function (Faker $faker) {
    return [
        //
    'status' => 1,
    ];
});
