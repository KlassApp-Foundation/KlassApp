<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
namespace Database\Factories;

use App\Models\ParentProfile;
use App\Models\Qualification;
use Faker\Generator as Faker;

$factory->define(ParentProfile::class, function (Faker $faker) {
	
	$qualification 		= Qualification::where('status',1)->where('type','!=','teacher')->pluck('id')->toArray();
	$qualification_id 	= $faker->randomElement($qualification);

	$profession = $faker->randomElement(['business','central_government_employee','private','home_maker','state_government_employee','others']);

	if($profession != 'home_maker')
	{
		$sub_occupation = $faker->jobTitle;

		$designation = $faker->jobTitle;

		$organization_name = $faker->company;

    	$official_address = $faker->randomElement(['Bangalore' , 'Chennai' , 'Hyderabad' , 'Mumbai' , 'Thiruvananthapuram']);

    	$annual_income = $faker->randomNumber($nbDigits = 7, $strict = false);
	}

    $relation = $faker->randomElement(['father' , 'mother' , 'guardian']);

    return [
        //
    	'qualification_id'	=>	$qualification_id,
    	'profession'		=>	$profession,
    	'sub_occupation'	=>	$sub_occupation,
    	'designation'		=>	$designation,
    	'organization_name'	=>	$organization_name,
    	'official_address'	=>	$official_address,
    	'relation'			=>	$relation,
    	'annual_income'		=>	$annual_income,
    ];
});
