<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use Illuminate\Database\Seeder;

/**
 * Keyed on title. The attendance migration inserts the same four
 * reasons; this seeder must not insert a second copy.
 */
class AbsentReasonsTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Health Issue', 'Family Functions', 'Personal Work', 'Others'] as $reason) {
            ReferenceUpsert::sync('absent_reasons', ['title' => $reason], [
                'status' => 1,
                'deleted_at' => null,
            ]);
        }
    }
}
