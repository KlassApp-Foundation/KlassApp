<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Realistic subscription plans for Ugandan schools/churches/SaaS (2026 pricing)
        // Prices in UGX, cycle in days, limits scaled reasonably
        $plans = [
            // Free / Trial plan (good for onboarding small schools/churches)
            [
                'name'            => 'free',
                'display_name'    => 'Free',
                'cycle'           => 30,          // 30 days trial/forever free tier
                'amount'          => 0,
                'no_of_users'   => 5,
                'no_of_students'  => 10,
                'no_of_events'    => 5,
                'no_of_folders'   => 1,
                'no_of_files'     => 10,
                'no_of_videos'    => 0,
                'no_of_bulletins' => 1,
                'no_of_groups'    => 3,
            ],

            // Standard (most schools start here)
            [
                'name'            => 'standard',
                'display_name'    => 'Standard',
                'cycle'           => 30,          // monthly
                'amount'          => 45000,       
                'no_of_users'   => 50,
                'no_of_students'  => 300,
                'no_of_events'    => 20,
                'no_of_folders'   => 20,
                'no_of_files'     => 500,
                'no_of_videos'    => 20,
                'no_of_bulletins' => 20,
                'no_of_groups'    => 10,
            ],

            // Extended / Professional
            [
                'name'            => 'extended',
                'display_name'    => 'Extended',
                'cycle'           => 30,         
                'amount'          => 95000,       // ~90k UGX/quarter (~24 USD)
                'no_of_users'   => 100,
                'no_of_students'  => 1000,
                'no_of_events'    => 50,
                'no_of_folders'   => 50,
                'no_of_files'     => 2000,
                'no_of_videos'    => 50,
                'no_of_bulletins' => 50,
                'no_of_groups'    => 30,
            ],

            // Premium (large schools, dioceses, networks)
            [
                'name'            => 'premium',
                'display_name'    => 'Premium',
                'cycle'           => 30,         // annual
                'amount'          => 150000,      // ~300k UGX/year (~80 USD)
                'no_of_users'   => 500,
                'no_of_students'  => 10000,
                'no_of_events'    => 200,
                'no_of_folders'   => 200,
                'no_of_files'     => 10000,
                'no_of_videos'    => 200,
                'no_of_bulletins' => 200,
                'no_of_groups'    => 100,
            ],
        ];
        foreach ($plans as $plan) {
            $whatsappService = "WhatsApp notifications to parents and teachers";
           Plan::updateOrCreate(
                ['name' => $plan['name']],  // unique by plan name
                [
                    'display_name'    => $plan['display_name'],
                    'cycle'           => $plan['cycle'],
                    'amount'          => $plan['amount'],
                    'no_of_users'   => $plan['no_of_users'],
                    'no_of_students'   => $plan['no_of_students'],
                    'whatsapp_services'   => $plan['name'] !== "free" ? $whatsappService : null,
                    'no_of_events'    => $plan['no_of_events'],
                    'no_of_folders'   => $plan['no_of_folders'],
                    'no_of_files'     => $plan['no_of_files'],
                    'no_of_videos'    => $plan['no_of_videos'],
                    'no_of_bulletins' => $plan['no_of_bulletins'],
                    'no_of_groups'    => $plan['no_of_groups'],
                ]
            );
        }
    }
}