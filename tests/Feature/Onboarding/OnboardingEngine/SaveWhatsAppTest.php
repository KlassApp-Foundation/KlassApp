<?php

namespace Tests\Feature\Onboarding\OnboardingEngine;

use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\OnboardingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SaveWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Test School '.Str::random(6),
            'email' => Str::random(8).'@test.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        // Seed usergroups so User::create works
        \DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->user = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Admin User',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt(Str::random(16)),
            'status' => 'active',
            'is_reset' => 1,
        ]);
    }

    public function test_links_whatsapp_number_to_user(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveWhatsApp($this->school, $this->user->id, '+256700111222');

        $this->assertNotNull($result['linked']);
        $this->assertEquals($this->user->id, $result['linked']['user_id']);
        $this->assertEquals('+256700111222', $result['linked']['phone']);

        $whatsapp = WhatsAppUser::where('user_id', $this->user->id)->first();
        $this->assertNotNull($whatsapp);
        $this->assertEquals('+256700111222', $whatsapp->phone);
        $this->assertEquals($this->school->id, $whatsapp->school_id);
        $this->assertTrue($whatsapp->opted_in);
    }

    public function test_rejects_empty_phone_number(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveWhatsApp($this->school, $this->user->id, '');

        $this->assertNull($result['linked']);
        $this->assertNotEmpty($result['skipped']);
        $this->assertStringContainsString('required', $result['skipped'][0]['reason']);
    }

    public function test_skips_if_user_already_has_whatsapp_record(): void
    {
        $engine = app(OnboardingEngine::class);

        // First link succeeds
        $result1 = $engine->saveWhatsApp($this->school, $this->user->id, '+256700111222');
        $this->assertNotNull($result1['linked']);

        // Second attempt with different phone skips
        $result2 = $engine->saveWhatsApp($this->school, $this->user->id, '+256700999888');
        $this->assertNull($result2['linked']);
        $this->assertNotEmpty($result2['skipped']);
        $this->assertStringContainsString('already has', $result2['skipped'][0]['reason']);

        // Original record unchanged
        $whatsapp = WhatsAppUser::where('user_id', $this->user->id)->first();
        $this->assertEquals('+256700111222', $whatsapp->phone);
    }

    public function test_skips_on_duplicate_phone(): void
    {
        $engine = app(OnboardingEngine::class);

        // Link phone to user 1
        $result1 = $engine->saveWhatsApp($this->school, $this->user->id, '+256700111222');
        $this->assertNotNull($result1['linked']);

        // Create a second user
        $user2 = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => 'Teacher Two',
            'email' => Str::random(8).'@test.sch.ug',
            'password' => bcrypt(Str::random(16)),
            'status' => 'active',
        ]);

        // Same phone on user 2 should hit the unique constraint and be skipped
        $result2 = $engine->saveWhatsApp($this->school, $user2->id, '+256700111222');
        $this->assertNull($result2['linked']);
        $this->assertNotEmpty($result2['skipped']);
        $this->assertStringContainsString('already registered', $result2['skipped'][0]['reason']);
    }

    public function test_trims_phone_whitespace(): void
    {
        $engine = app(OnboardingEngine::class);

        $result = $engine->saveWhatsApp($this->school, $this->user->id, '  +256700111222  ');

        $this->assertNotNull($result['linked']);
        $this->assertEquals('+256700111222', $result['linked']['phone']);

        $whatsapp = WhatsAppUser::where('user_id', $this->user->id)->first();
        $this->assertEquals('+256700111222', $whatsapp->phone);
    }

    public function test_sets_verified_at_and_opted_in(): void
    {
        $engine = app(OnboardingEngine::class);

        $engine->saveWhatsApp($this->school, $this->user->id, '+256700111222');

        $whatsapp = WhatsAppUser::where('user_id', $this->user->id)->first();
        $this->assertTrue($whatsapp->opted_in);
        $this->assertNotNull($whatsapp->verified_at);
    }
}
