<?php

namespace Tests\Feature;

use App\Helpers\SiteHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentCreateLocationDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_scrambled_foreign_city_mappings_are_corrected(): void
    {
        $now = now();

        $countries = [
            'Uganda' => 1,
            'Kenya' => 2,
            'Tanzania' => 3,
            'Rwanda' => 4,
            'Burundi' => 5,
            'South Sudan' => 6,
            'DRC' => 7,
            'Other' => 10,
        ];

        foreach ($countries as $name => $id) {
            DB::table('countries')->insert([
                'id' => $id,
                'name' => $name,
                'short_name' => strtoupper(substr($name, 0, 3)).$id,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Reproduce the GeGoK12-scrambled seed rows.
        // SQLite test DB may still have NOT NULL state_id (drop migration skips SQLite).
        $scrambled = [
            ['name' => 'Kampala', 'country_id' => 1, 'state_id' => 1, 'status' => 1],
            ['name' => 'Bujumbura', 'country_id' => 4, 'state_id' => 1, 'status' => 1],
            ['name' => 'Juba', 'country_id' => 5, 'state_id' => 1, 'status' => 1],
            ['name' => 'Kinshasa', 'country_id' => 6, 'state_id' => 1, 'status' => 1],
            ['name' => 'Johannesburg', 'country_id' => 7, 'state_id' => 1, 'status' => 1],
        ];

        if (\Illuminate\Support\Facades\Schema::hasTable('states')) {
            DB::table('states')->insert([
                'id' => 1,
                'country_id' => 1,
                'name' => 'Central',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $scrambled = array_map(static function (array $city): array {
                unset($city['state_id']);

                return $city;
            }, $scrambled);
        }

        foreach ($scrambled as $city) {
            DB::table('cities')->insert(array_merge($city, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        Cache::forget('cities');
        Cache::forget('countries');

        // RefreshDatabase already ran migrations on empty tables — re-apply the
        // data fix against the scrambled rows seeded above.
        $migration = require database_path(
            'migrations/2026_09_08_232404_fix_scrambled_foreign_city_country_mappings.php'
        );
        $migration->up();

        $this->assertSame(5, (int) DB::table('cities')->where('name', 'Bujumbura')->value('country_id'));
        $this->assertSame(6, (int) DB::table('cities')->where('name', 'Juba')->value('country_id'));
        $this->assertSame(7, (int) DB::table('cities')->where('name', 'Kinshasa')->value('country_id'));
        $this->assertSame(4, (int) DB::table('cities')->where('name', 'Kigali')->value('country_id'));
        $this->assertSame(0, (int) DB::table('cities')->where('name', 'Johannesburg')->value('status'));
        $this->assertNotNull(DB::table('cities')->where('name', 'Johannesburg')->value('deleted_at'));

        $citiesByCountry = SiteHelper::getCities();
        $drcCities = collect($citiesByCountry[7] ?? [])->pluck('name')->all();
        $ugandaCities = collect($citiesByCountry[1] ?? [])->pluck('name')->all();

        $this->assertContains('Kinshasa', $drcCities);
        $this->assertNotContains('Johannesburg', $drcCities);
        $this->assertContains('Kampala', $ugandaCities);
    }

    public function test_get_cities_excludes_inactive_rows(): void
    {
        $now = now();

        DB::table('countries')->insert([
            'id' => 1,
            'name' => 'Uganda',
            'short_name' => 'UGA',
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $cityRows = [
            [
                'name' => 'Kampala',
                'country_id' => 1,
                'status' => 1,
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Hidden City',
                'country_id' => 1,
                'status' => 0,
                'deleted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('cities', 'state_id')) {
            if (\Illuminate\Support\Facades\Schema::hasTable('states')) {
                DB::table('states')->insert([
                    'id' => 1,
                    'country_id' => 1,
                    'name' => 'Central',
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $cityRows = array_map(static function (array $city): array {
                $city['state_id'] = 1;

                return $city;
            }, $cityRows);
        }

        DB::table('cities')->insert($cityRows);

        Cache::forget('cities');

        $ugandaCities = collect(SiteHelper::getCities()[1] ?? [])->pluck('name')->all();

        $this->assertContains('Kampala', $ugandaCities);
        $this->assertNotContains('Hidden City', $ugandaCities);
    }
}
