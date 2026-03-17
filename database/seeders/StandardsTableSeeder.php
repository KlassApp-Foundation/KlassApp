<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StandardsTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $standards = [
            // Pre-primary (Nursery)
            ['name' => 'Baby Class',     'order' => 1,  'level_type' => 'pre_primary'],
            ['name' => 'Middle Class',   'order' => 2,  'level_type' => 'pre_primary'],
            ['name' => 'Top Class',      'order' => 3,  'level_type' => 'pre_primary'],

            // Primary
            ['name' => 'Primary 1',      'order' => 4,  'short_name' => 'P1', 'level_type' => 'primary'],
            ['name' => 'Primary 2',      'order' => 5,  'short_name' => 'P2', 'level_type' => 'primary'],
            ['name' => 'Primary 3',      'order' => 6,  'short_name' => 'P3', 'level_type' => 'primary'],
            ['name' => 'Primary 4',      'order' => 7,  'short_name' => 'P4', 'level_type' => 'primary'],
            ['name' => 'Primary 5',      'order' => 8,  'short_name' => 'P5', 'level_type' => 'primary'],
            ['name' => 'Primary 6',      'order' => 9,  'short_name' => 'P6', 'level_type' => 'primary'],
            ['name' => 'Primary 7',      'order' => 10, 'short_name' => 'P7', 'level_type' => 'primary'],

            // Secondary (O & A level)
            ['name' => 'Senior 1',       'order' => 11, 'short_name' => 'S1', 'level_type' => 'secondary'],
            ['name' => 'Senior 2',       'order' => 12, 'short_name' => 'S2', 'level_type' => 'secondary'],
            ['name' => 'Senior 3',       'order' => 13, 'short_name' => 'S3', 'level_type' => 'secondary'],
            ['name' => 'Senior 4',       'order' => 14, 'short_name' => 'S4', 'level_type' => 'secondary'],
            ['name' => 'Senior 5',       'order' => 15, 'short_name' => 'S5', 'level_type' => 'secondary'],
            ['name' => 'Senior 6',       'order' => 16, 'short_name' => 'S6', 'level_type' => 'secondary'],
        ];

        foreach ($standards as $data) {
            DB::table('standards')->updateOrInsert(
                ['name' => $data['name']],
                $data + ['created_at' => $now, 'updated_at' => $now]
            );
        }

        $this->command->info('Seeded ' . count($standards) . ' national standards.');
    }
}