<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $plans = [
            [
                'name'            => 'freemium',
                'display_name'    => 'Freemium',
                'cycle'           => 30,
                'amount'          => 0,
                'is_active'       => 1,
                'order'           => 1,
                'no_of_users'     => 5,
                'no_of_students'  => 100,
                'no_of_events'    => 10,
                'no_of_folders'   => 2,
                'no_of_files'     => 50,
                'no_of_videos'    => 5,
                'no_of_audios'    => 5,
                'no_of_bulletins' => 3,
                'no_of_groups'    => 5,
            ],
            [
                'name'            => 'growth',
                'display_name'    => 'Growth',
                'cycle'           => 30,
                'amount'          => 35,
                'is_active'       => 1,
                'order'           => 2,
                'no_of_users'     => 25,
                'no_of_students'  => 1000,
                'no_of_events'    => 50,
                'no_of_folders'   => 20,
                'no_of_files'     => 500,
                'no_of_videos'    => 50,
                'no_of_audios'    => 50,
                'no_of_bulletins' => 20,
                'no_of_groups'    => 20,
            ],
            [
                'name'              => 'premium',
                'display_name'      => 'Premium',
                'cycle'             => 30,
                'amount'            => 0,
                'is_custom_pricing' => 1,
                'is_active'         => 1,
                'order'             => 3,
                'no_of_users'       => 999999,
                'no_of_students'    => 999999,
                'no_of_events'      => 999999,
                'no_of_folders'     => 999999,
                'no_of_files'       => 999999,
                'no_of_videos'      => 999999,
                'no_of_audios'      => 999999,
                'no_of_bulletins'   => 999999,
                'no_of_groups'      => 999999,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['name' => $plan['name']],
                array_merge($plan, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $this->command->info('Plans seeded: Freemium, Growth, Premium (USD)');
    }
}