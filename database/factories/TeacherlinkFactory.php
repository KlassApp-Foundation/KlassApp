<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
namespace Database\Factories;

use Faker\Generator as Faker;
use App\Models\Teacherlink;

$factory->define(Teacherlink::class, function (Faker $faker) {

    return [
        //
        'status'        =>  '1',
    ];
});