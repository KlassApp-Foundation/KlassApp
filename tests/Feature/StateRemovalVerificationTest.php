<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Models\Plan;
use App\Models\EmisSchool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StateRemovalVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::create([
            'cycle' => 1,
            'name' => 'growth',
            'display_name' => 'Growth',
            'amount' => 0,
            'is_active' => 1,
            'order' => 1,
        ]);
    }

    public function test_fulladdress_degrades_gracefully(): void
    {
        $school = School::create([
            'name' => 'FullAddr Test School ' . now()->timestamp,
            'email' => 'fulladdr' . now()->timestamp . '@test.com',
            'phone' => '771234568',
            'slug' => 'fulladdr-test-' . now()->timestamp,
            'address' => '456 Test Ave',
            'pincode' => '67890',
            'status' => 1,
        ]);

        $result = $school->fulladdress();
        $this->assertNotNull($result);
        $this->assertStringContainsString('456 Test Ave', $result);
    }

    public function test_emis_auto_fill_works(): void
    {
        EmisSchool::create([
            'emis_code' => 'TEST01',
            'school_name' => 'Verification Test School',
            'district' => 'Test District',
        ]);

        $match = EmisSchool::where('emis_code', 'TEST01')->first();
        $this->assertNotNull($match);
        $this->assertEquals('Verification Test School', $match->school_name);
        $this->assertEquals('Test District', $match->district);
    }

    public function test_schools_table_has_no_state_id_on_mysql(): void
    {
        if (\Illuminate\Support\Facades\Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite test DB does not support dropping columns');
        }
        $cols = \Illuminate\Support\Facades\Schema::getColumnListing('schools');
        $this->assertNotContains('state_id', $cols);
    }
}
