<?php
namespace Database\Factories;

//use Faker\Generator as Faker;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {

    return [
        'name' => $this->faker->unique()->userName,
        'email' => $this->faker->unique()->safeEmail,
        'mobile_no' => $this->faker->unique()->randomNumber($nbDigits = 9, $strict = false),
        'password' => bcrypt('password'),
        'email_verification_code' => \Illuminate\Support\Str::random(40),
        'email_verified' => 1,
        'usergroup_id' => 6, // student by default; tests override
        'status' => 'active',
        'registration_number'   =>  'KLS001' . str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'remember_token' => \Illuminate\Support\Str::random(10),
    ];


}

}
