<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
namespace Database\Factories;

use App\Models\StudentAcademic;
use App\Models\AcademicYear;
use App\Models\StandardLink;
use Faker\Generator as Faker;

$factory->define(StudentAcademic::class, function (Faker $faker) {
	$academicYear = AcademicYear::where('status',1)->first();

	$standardLink = StandardLink::where([['academic_year_id',$academicYear->id],['status',1]])->pluck('id')->toArray();
	$standardLink_id = $faker->randomElement($standardLink);

	$selected_standardLink = StandardLink::where([['academic_year_id',$academicYear->id],['id',$standardLink_id],['status',1]])->first();

	$roll_number = $faker->numberBetween($min=1,$max=25);

	$id_card_number = $faker->numberBetween($min=1,$max=25);

	if( ($selected_standardLink->standard->name == '10') || ($selected_standardLink->standard->name == '12') )
	{
		$board_registration_number = $faker->randomNumber($nbDigits = 8, $strict = false);
	}

	$mode_of_transport = $faker->randomElement(['auto','car','city_bus','cycle','rickshaw','school_bus','taxi','walking']);

	if( ($mode_of_transport == 'auto') || ($mode_of_transport == 'rickshaw') || ($mode_of_transport == 'taxi') )
    {
        $transport_details['driver_name']           = $faker->firstName.' '.$faker->lastName;
        $transport_details['driver_contact_number'] = $faker->unique()->randomNumber($nbDigits = 9, $strict = false);
    }

    $siblings = $faker->randomElement(['yes','no']);
    
    if($siblings == 'yes')
    {
    	$siblings_count = $faker->randomElement(['1','2']);

    	for($i = 0 ; $i < $siblings_count ; $i++)
    	{
    		$sibling_relation = $faker->randomElement(['brother','sister']);
    		$sibling_date_of_birth = $faker->dateTimeBetween($startDate = '-18 years', $endDate = '-5 years', $timezone = null);

        	$sibling_details[$i]['sibling_relation']      	= $sibling_relation;
        	$sibling_details[$i]['sibling_name']         	= $faker->firstName.' '.$faker->lastName;
        	$sibling_details[$i]['sibling_date_of_birth']	= $sibling_date_of_birth;
        	$sibling_details[$i]['sibling_standard']      	= $faker->randomElement($standardLink);
        }
    }

    return [
        //
	    //'academic_year_id'			=>	$academicYear->id,
	    //'standardLink_id'			=>	$standardLink_id,
	    'roll_number'				=>	$roll_number,
	    'id_card_number'			=>  $id_card_number,
	    'board_registration_number'	=>	$board_registration_number,
	    'mode_of_transport'			=>	$mode_of_transport,
	    'transport_details'			=>	$transport_details,
	    'siblings'					=>	$siblings,
	    'siblings_count'			=>	$siblings_count,
	    'sibling_details'			=>	$sibling_details,
    ];
});
