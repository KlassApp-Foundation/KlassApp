<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Reference data, then the platform admin accounts.
 * Does not create demo schools.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ReferenceSeeder::class);
        $this->call(UsersSiteAdminTableSeeder::class);

        $this->command?->info('Production essential seeders completed.');
    }
}
