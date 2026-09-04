<?php

namespace Tests\Feature\Admin;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SchoolSettingsNameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('settings')->insert([
            [
                'key' => 'sitetitle',
                'name' => 'Site Title',
                'description' => 'Site title',
                'value' => 'School-Plus',
                'field' => '{"name":"value","label":"Value","type":"text"}',
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'sitename',
                'name' => 'Site Name',
                'description' => 'Site name',
                'value' => 'School-Plus',
                'field' => '{"name":"value","label":"Value","type":"text"}',
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'sitelogo',
                'name' => 'Site Logo',
                'description' => 'Logo',
                'value' => 'images/logo.png',
                'field' => '{"name":"value","label":"Value","type":"browse"}',
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'favicon',
                'name' => 'Favicon',
                'description' => 'Favicon',
                'value' => 'images/favicon.png',
                'field' => '{"name":"value","label":"Value","type":"browse"}',
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->withoutMiddleware([
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\MustBeSchoolAdmin::class,
            \App\Http\Middleware\MustBeFullSchoolAdmin::class,
            \App\Http\Middleware\MustBePrivilege::class,
        ]);
    }

    public function test_settings_form_shows_school_name_not_platform_sitename(): void
    {
        $school = School::create([
            'name' => 'UI Review Demo School',
            'email' => 'settings@test.sch.ug',
            'phone' => '0700000077',
            'slug' => 'settings-name-school',
            'status' => 1,
        ]);
        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Settings Admin',
            'email' => 'settings.admin@test.sch.ug',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $html = $this->actingAs($admin)
            ->get('/admin/settings/generalsettings')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('School Name', $html);
        $this->assertStringContainsString('UI Review Demo School', $html);
        $this->assertStringNotContainsString('name="sitename"', $html);
    }

    public function test_settings_store_updates_school_name_not_platform_sitename(): void
    {
        $school = School::create([
            'name' => 'Old School Name',
            'email' => 'settings2@test.sch.ug',
            'phone' => '0700000076',
            'slug' => 'settings-name-school-2',
            'status' => 1,
        ]);
        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Settings Admin 2',
            'email' => 'settings.admin2@test.sch.ug',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post('/admin/settings/generalsettings', [
                'sitetitle' => 'School-Plus',
                'school_name' => 'Renamed Demo School',
            ])
            ->assertRedirect();

        $this->assertSame('Renamed Demo School', $school->fresh()->name);
        $this->assertSame('School-Plus', DB::table('settings')->where('key', 'sitename')->value('value'));
    }
}
