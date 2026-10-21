<?php

namespace Tests\Feature\Toshi;

use App\Models\Country;
use App\Models\School;
use App\Models\User;
use App\Onboarding\Steps\ToshiOnboardingV2Driver;
use App\Services\OnboardingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ToshiOnboardingV2EmisTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['toshi.onboarding_v2' => true]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create(['name' => 'Uganda', 'short_name' => 'UG', 'status' => 1, 'order' => 1]);

        School::create([
            'name' => 'Existing School',
            'email' => 'existing@test.sch.ug',
            'phone' => '0700000001',
            'slug' => 'existing-emis',
            'status' => 1,
            'ministry_code' => 'EMIS-1001',
        ]);

        $this->school = School::create([
            'name' => "Emis Admin's School",
            'email' => 'emis-onboard@test.sch.ug',
            'phone' => '0700000002',
            'slug' => 'emis-onboard',
            'status' => 1,
            'toshi_enabled' => 0,
            'toshi_mode' => 'onboarding',
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Emis Admin',
            'email' => 'admin@emis-onboard.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
    }

    private function driveToEmis(): ToshiOnboardingV2Driver
    {
        $driver = app(ToshiOnboardingV2Driver::class);
        foreach (['Emis Primary', 'Up to 500', 'Uganda', 'UNEB', 'primary'] as $reply) {
            $result = $driver->handleReply($this->school->fresh(), $reply, $this->admin->id);
            $this->assertNotSame('rejected', $result['status'], 'Rejected '.$reply);
        }
        $this->assertSame('emis', $driver->promptNext($this->school->fresh(), $this->admin->id)['key']);

        return $driver;
    }

    public function test_duplicate_emis_is_rejected_with_a_message_instead_of_a_500(): void
    {
        $driver = $this->driveToEmis();

        $result = $driver->handleReply($this->school->fresh(), 'EMIS-1001', $this->admin->id);

        $this->assertSame('rejected', $result['status']);
        $this->assertSame('emis', $result['key']);
        $this->assertStringContainsString('already used by another school', (string) $result['hint']);
        $this->assertNull($this->school->fresh()->ministry_code);

        $ok = $driver->handleReply($this->school->fresh(), 'EMIS-1002', $this->admin->id);
        $this->assertSame('advanced', $ok['status']);
        $this->assertSame('EMIS-1002', $this->school->fresh()->ministry_code);
    }

    public function test_engine_rejects_duplicate_and_overlong_emis_before_hitting_the_database(): void
    {
        $engine = app(OnboardingEngine::class);
        $this->school->registration_country = 'Uganda';
        $this->school->save();

        foreach (['EMIS-1001', str_repeat('9', 21)] as $code) {
            try {
                $engine->saveEmis($this->school->fresh(), $code);
                $this->fail('Expected a ValidationException for '.$code);
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('ministryCode', $e->errors());
            }
        }

        // Re-saving the school's own code is not a duplicate.
        $engine->saveEmis($this->school->fresh(), 'EMIS-1003');
        $engine->saveEmis($this->school->fresh(), 'EMIS-1003');
        $this->assertSame('EMIS-1003', $this->school->fresh()->ministry_code);
    }
}
