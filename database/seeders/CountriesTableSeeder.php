<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use App\Reference\CountryCodes;
use Illuminate\Database\Seeder;

/**
 * Writes the corrected country codes from App\Reference\CountryCodes.
 * Those values match PR #1249's migration codes(). A database that
 * still has the old scrambled codes is corrected when this seeder
 * runs, and is also what #1249's migration would change.
 */
class CountriesTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (CountryCodes::corrected() as $name => $spec) {
            ReferenceUpsert::sync('countries', ['name' => $name], [
                'short_name' => $spec['short_name'],
                'iso_code' => $spec['iso_code'],
                'tel_prefix' => $spec['tel_prefix'],
                'status' => 1,
                'deleted_at' => null,
            ]);
        }
    }
}
