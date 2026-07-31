<?php

namespace Tests\Feature\Toshi;

use App\AiAgents\ToshiSdkV2Service;
use App\Enums\ToshiScope;
use App\Models\User;
use App\Services\Toshi\ToshiAvailabilityGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ToshiAvailabilityGateTest extends TestCase
{
    use RefreshDatabase;

    private User $siteadmin;

    private User $schoolAdmin;

    private int $schoolId;

    private int $disabledSchoolId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Gate Enabled School',
            'slug' => 'gate-enabled',
            'email' => 'gate-enabled@test.sch.ug',
            'phone' => '+256700000001',
            'status' => 1,
            'registration_country' => 'Uganda',
            'toshi_enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->disabledSchoolId = DB::table('schools')->insertGetId([
            'name' => 'Gate Disabled School',
            'slug' => 'gate-disabled',
            'email' => 'gate-disabled@test.sch.ug',
            'phone' => '+256700000002',
            'status' => 1,
            'registration_country' => 'Uganda',
            'toshi_enabled' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->siteadmin = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 1,
            'email' => 'siteadmin-gate@test.sch.ug',
        ]);

        $this->schoolAdmin = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 3,
            'email' => 'schooladmin-gate@test.sch.ug',
        ]);

        Config::set('toshi.sdk_v2_enabled', true);
        Config::set('toshi.per_school_gate', true);
        Config::set('ai.providers.openai-compatible.key', 'test-key-for-gate');
        Config::set('toshi.platform_gate.enabled', false);
        Config::set('toshi.platform_gate.user_ids', []);
    }

    public function test_siteadmin_with_platform_capability_is_available(): void
    {
        Config::set('toshi.platform_gate.enabled', true);

        $service = app(ToshiSdkV2Service::class);

        $this->assertTrue(
            $service->isAvailable($this->siteadmin, null, ToshiScope::Platform),
            'siteadmin + platform_gate.enabled must pass Platform scope without school_id',
        );
    }

    public function test_siteadmin_without_platform_capability_is_unavailable(): void
    {
        Config::set('toshi.platform_gate.enabled', false);

        $service = app(ToshiSdkV2Service::class);

        $this->assertFalse(
            $service->isAvailable($this->siteadmin, null, ToshiScope::Platform),
            'siteadmin without platform_gate.enabled must fail Platform scope',
        );
    }

    public function test_siteadmin_outside_user_id_allowlist_is_unavailable(): void
    {
        Config::set('toshi.platform_gate.enabled', true);
        Config::set('toshi.platform_gate.user_ids', [$this->siteadmin->id + 999]);

        $service = app(ToshiSdkV2Service::class);

        $this->assertFalse(
            $service->isAvailable($this->siteadmin, null, ToshiScope::Platform),
            'siteadmin not in platform_gate.user_ids allowlist must fail',
        );
    }

    public function test_siteadmin_in_user_id_allowlist_is_available(): void
    {
        Config::set('toshi.platform_gate.enabled', true);
        Config::set('toshi.platform_gate.user_ids', [$this->siteadmin->id]);

        $service = app(ToshiSdkV2Service::class);

        $this->assertTrue(
            $service->isAvailable($this->siteadmin, null, ToshiScope::Platform),
        );
    }

    public function test_school_admin_with_toshi_enabled_school_is_available(): void
    {
        $service = app(ToshiSdkV2Service::class);

        $this->assertTrue(
            $service->isAvailable($this->schoolAdmin, $this->schoolId),
            'Default School scope must keep prior behaviour for enabled schools',
        );
        $this->assertTrue(
            $service->isAvailable($this->schoolAdmin, $this->schoolId, ToshiScope::School),
        );
    }

    public function test_school_admin_with_toshi_disabled_school_is_unavailable(): void
    {
        $disabledAdmin = User::factory()->create([
            'school_id' => $this->disabledSchoolId,
            'usergroup_id' => 3,
            'email' => 'disabled-school-admin@test.sch.ug',
        ]);

        $service = app(ToshiSdkV2Service::class);

        $this->assertFalse(
            $service->isAvailable($disabledAdmin, $this->disabledSchoolId),
            'per_school_gate must still deny schools without toshi_enabled',
        );
    }

    public function test_siteadmin_school_scope_without_school_remains_unavailable(): void
    {
        Config::set('toshi.platform_gate.enabled', true);

        $service = app(ToshiSdkV2Service::class);

        $this->assertFalse(
            $service->isAvailable($this->siteadmin, null),
            'Default School scope must still fail for siteadmin with null school_id',
        );
        $this->assertFalse(
            $service->isAvailable($this->siteadmin, null, ToshiScope::School),
        );
    }

    public function test_school_admin_cannot_pass_platform_scope(): void
    {
        Config::set('toshi.platform_gate.enabled', true);

        $gate = app(ToshiAvailabilityGate::class);

        $this->assertFalse(
            $gate->allows($this->schoolAdmin, ToshiScope::Platform),
            'Non-usergroup-1 users must never pass platform gate',
        );
    }

    public function test_platform_scope_ignores_school_toshi_enabled(): void
    {
        Config::set('toshi.platform_gate.enabled', true);

        $gate = app(ToshiAvailabilityGate::class);

        $this->assertTrue(
            $gate->allows($this->siteadmin, ToshiScope::Platform, $this->disabledSchoolId),
            'Platform gate must not depend on school_id or toshi_enabled',
        );
    }
}
