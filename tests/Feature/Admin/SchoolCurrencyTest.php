<?php

namespace Tests\Feature\Admin;

use App\Models\School;
use App\Services\DashboardV2DataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolCurrencyTest extends TestCase
{
    use RefreshDatabase;

    private function school(array $overrides = []): School
    {
        return School::create(array_merge([
            'name' => 'Currency School', 'slug' => 'currency-'.uniqid(), 'email' => uniqid().'@t.ug',
            'phone' => '0700000009', 'status' => 1,
        ], $overrides));
    }

    public function test_a_ugandan_school_is_ugx_without_being_told(): void
    {
        $school = $this->school(['registration_country' => 'Uganda']);

        $this->assertSame('UGX', app(DashboardV2DataService::class)->currencyFor($school->id));
    }

    public function test_an_explicit_setting_wins_so_a_school_can_use_usd(): void
    {
        $school = $this->school(['registration_country' => 'Uganda']);
        DB::table('school_details')->insert([
            'school_id' => $school->id, 'meta_key' => 'currency', 'meta_value' => 'USD',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertSame('USD', app(DashboardV2DataService::class)->currencyFor($school->id));
    }

    public function test_no_setting_and_no_known_country_stays_null(): void
    {
        $school = $this->school(['registration_country' => null]);

        $this->assertNull(app(DashboardV2DataService::class)->currencyFor($school->id));
    }
}
