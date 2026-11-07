<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * php artisan migrate:fresh --seed
 *
 * Runs reference data only. Demo schools are a separate seeder:
 *
 *     php artisan db:seed --class=DemoSeeder
 *
 * Set SEED_DEMO_SCHOOLS=true to run them from this class as well.
 * DemoSeeder refuses when APP_ENV=production.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ReferenceSeeder::class);

        if ($this->demoRequested()) {
            $this->call(DemoSeeder::class);
        }
    }

    private function demoRequested(): bool
    {
        $raw = $_ENV['SEED_DEMO_SCHOOLS'] ?? $_SERVER['SEED_DEMO_SCHOOLS'] ?? getenv('SEED_DEMO_SCHOOLS');

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }
}
