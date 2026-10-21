<?php

namespace Tests\Feature\Accountant;

use App\Models\AcademicYear;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\SchoolDetail;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The bursar dashboard must label money with the school's currency
 * (school_details meta `currency`, via DashboardV2DataService::currencyFor).
 * A hardcoded code is wrong for every other school.
 */
class BursarDashboardCurrencyTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $bursar;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        DB::table('usergroups')->upsert([
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Bursar Currency School',
            'slug' => 'bursar-currency-'.uniqid(),
            'email' => 'bursar-currency-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
        ]);

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $this->bursar = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 11,
            'email' => 'bursar.currency.'.uniqid().'@t.sch.ug',
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        FeesCategories::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'name' => 'Tuition',
            'amount' => 120000,
        ]);
    }

    public function test_bursar_dashboard_shows_the_school_currency(): void
    {
        SchoolDetail::create([
            'school_id' => $this->school->id,
            'meta_key' => 'currency',
            'meta_value' => 'KES',
        ]);

        $html = $this->actingAs($this->bursar)
            ->get('/accountant/dashboard')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Total Fees (KES)', $html);
        $this->assertStringContainsString('KES 120,000', $html);
        $this->assertStringNotContainsString('UGX', $html);
    }

    public function test_bursar_dashboard_omits_a_currency_code_when_the_school_has_not_set_one(): void
    {
        $html = $this->actingAs($this->bursar)
            ->get('/accountant/dashboard')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Total Fees', $html);
        $this->assertStringContainsString('120,000', $html);
        $this->assertStringNotContainsString('UGX', $html);
        $this->assertStringNotContainsString('Total Fees (', $html);
    }
}
