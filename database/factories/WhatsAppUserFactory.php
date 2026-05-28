<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WhatsAppUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhatsAppUserFactory extends Factory
{
    protected $model = WhatsAppUser::class;

    public function definition(): array
    {
        return [
            'phone'       => '+2567' . $this->faker->numerify('########'),
            'user_id'     => User::factory(),
            'user_type'   => User::class,
            'opted_in'    => true,
            'verified_at' => now(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['verified_at' => null]);
    }

    public function optedOut(): static
    {
        return $this->state(fn () => ['opted_in' => false]);
    }
}
