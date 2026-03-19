<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
namespace Database\Factories;

use App\Models\LibraryCard;
use Faker\Generator as Faker;

$factory->define(LibraryCard::class, function (Faker $faker) {

	$library_card_no= $faker->unique()->numberBetween($min = 1000, $max = 9000);

    return [

    'library_card_no'=>$library_card_no,
    'book_limit'=>5,
    'expiry_date'=>'2021-03-30 00:00:00',
    'status' => 1,
    ];
});
