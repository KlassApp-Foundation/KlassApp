<?php

namespace App\Console\Commands;

use Database\Seeders\PlansTableSeeder;
use Illuminate\Console\Command;

/**
 * Idempotent Freemium / Growth / Premium plan rows (PlansTableSeeder).
 * Staging Cloud DBs have been empty after provision; run this (or the seeder) once.
 */
class EnsurePlansCommand extends Command
{
    protected $signature = 'plans:ensure';

    protected $description = 'Ensure Freemium, Growth ($35), and Premium Plan rows exist (updateOrCreate by name)';

    public function handle(): int
    {
        $this->call('db:seed', [
            '--class' => PlansTableSeeder::class,
            '--force' => true,
        ]);

        return self::SUCCESS;
    }
}
