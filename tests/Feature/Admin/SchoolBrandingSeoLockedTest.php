<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Country;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchoolBrandingSeoLockedTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private Country $uganda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        foreach ([
            'sitetitle' => 'KlassApp',
            'sitename' => 'KlassApp',
            'sitelogo' => 'images/logo.png',
            'favicon' => 'images/favicon.png',
            'site_description' => 'Original description',
            'site_keyword' => 'original',
            'twitter_handle' => '@original',
            'twitter_description' => 'Original twitter',
            'twitter_card_image' => 'images/twitter.png',
            'facebook_site_url' => 'https://example.test/original',
            'facebook_card_image' => 'images/facebook.png',
        ] as $key => $value) {
            DB::table('settings')->insert([
                'key' => $key,
                'name' => $key,
                'description' => $key,
                'value' => $value,
                'field' => '{"name":"value","label":"Value","type":"text"}',
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->uganda = Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => 'Profile School',
            'email' => 'profile@test.sch.ug',
            'phone' => '0700000088',
            'slug' => 'profile-school',
            'status' => 1,
            'country_id' => $this->uganda->id,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Profile Admin',
            'email' => 'profile.admin@test.sch.ug',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
    }

    public function test_settings_menu_omits_branding_and_seo_and_keeps_maintenance(): void
    {
        $html = $this->actingAs($this->admin)
            ->get('/admin/settings')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('/admin/settings/generalsettings', $html);
        $this->assertStringNotContainsString('/admin/settings/seodetailsettings', $html);
        $this->assertStringNotContainsString('Site branding', $html);
        $this->assertStringNotContainsString('SEO details', $html);
        $this->assertStringContainsString('/admin/school-profile', $html);
        $this->assertStringContainsString('/admin/settings/maintenancesettings', $html);

        $nav = view('layouts.partials.settings-nav')->render();
        $this->assertStringNotContainsString('generalsettings', $nav);
        $this->assertStringNotContainsString('seodetailsettings', $nav);
        $this->assertStringContainsString('maintenancesettings', $nav);
    }

    public function test_school_admin_seo_routes_leave_global_settings_unchanged(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/settings/seodetailsettings')
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->post('/admin/settings/seodetailsettings', [
                'sitetitle' => 'Hijacked',
                'site_description' => 'Hijacked description',
                'site_keyword' => 'hijack',
                'twitter_handle' => '@hijack',
                'twitter_description' => 'Hijacked twitter',
                'twitter_card_image' => UploadedFile::fake()->image('twitter.png'),
                'facebook_site_url' => 'https://evil.test',
                'facebook_card_image' => UploadedFile::fake()->image('facebook.png'),
            ])
            ->assertForbidden();

        $this->assertSame('KlassApp', DB::table('settings')->where('key', 'sitetitle')->value('value'));
        $this->assertSame('Original description', DB::table('settings')->where('key', 'site_description')->value('value'));
        $this->assertSame('original', DB::table('settings')->where('key', 'site_keyword')->value('value'));
        $this->assertSame('@original', DB::table('settings')->where('key', 'twitter_handle')->value('value'));
        $this->assertSame('https://example.test/original', DB::table('settings')->where('key', 'facebook_site_url')->value('value'));
    }

    public function test_school_profile_still_updates_name_and_logo(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin)
            ->post('/admin/schooldetails/update/'.$this->school->id, [
                'name' => 'Renamed Profile School',
                'moto' => 'Learn well',
                'date_of_establishment' => '2010-01-15',
                'board' => 'uneb',
                'about_us' => 'A test school',
                'country_id' => $this->uganda->id,
                'city_id' => null,
                'address' => 'Kampala',
                'website' => null,
                'ministry_code' => 'EMIS-90001',
                'uneb_center_number' => 'U9001',
                'school_logo' => UploadedFile::fake()->image('crest.png'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed Profile School', $this->school->fresh()->name);

        $logo = DB::table('school_details')
            ->where('school_id', $this->school->id)
            ->where('meta_key', 'school_logo')
            ->value('meta_value');

        $this->assertNotEmpty($logo);
        $this->assertNotSame('-', $logo);
        Storage::disk('local')->assertExists($logo);

        $this->assertSame('KlassApp', DB::table('settings')->where('key', 'sitetitle')->value('value'));
        $this->assertSame('images/logo.png', DB::table('settings')->where('key', 'sitelogo')->value('value'));
    }
}
