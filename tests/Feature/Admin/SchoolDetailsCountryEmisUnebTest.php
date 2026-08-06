<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\AgentToshi;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolDetailsCountryEmisUnebTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private Country $uganda;

    private Country $kenya;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->uganda = Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->kenya = Country::create([
            'name' => 'Kenya',
            'short_name' => 'KE',
            'status' => 1,
            'order' => 2,
        ]);

        $this->school = School::create([
            'name' => 'Details Test School',
            'email' => 'details@test.sch.ug',
            'phone' => '0700000001',
            'slug' => 'details-test-school',
            'status' => 1,
            'curriculum' => null,
            'country_id' => null,
            'registration_country' => null,
            'ministry_code' => null,
            'uneb_center_number' => null,
            'toshi_enabled' => 0,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Details Admin',
            'email' => 'admin@details.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Details',
            'lastname' => 'Admin',
        ]);

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => date('Y'),
            'type' => 'Current Academic Year',
            'description' => 'Current Academic Year',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => $this->school->name,
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
        ], $overrides);
    }

    public function test_can_set_country_emis_and_uneb_when_all_null(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(
            '/admin/schooldetails/update/'.$this->school->id,
            $this->validPayload()
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('successmessage');

        $fresh = $this->school->fresh();
        $this->assertSame('Uganda', $fresh->registration_country);
        $this->assertSame($this->uganda->id, (int) $fresh->country_id);
        $this->assertSame('EMIS-90001', $fresh->ministry_code);
        $this->assertSame('U9001', $fresh->uneb_center_number);
        $this->assertSame('uneb', $fresh->curriculum);
    }

    public function test_uganda_rejects_empty_emis_and_accepts_valid_emis(): void
    {
        $this->actingAs($this->admin);

        $rejected = $this->post(
            '/admin/schooldetails/update/'.$this->school->id,
            $this->validPayload(['ministry_code' => ''])
        );
        $rejected->assertSessionHasErrors('ministry_code');
        $this->assertNull($this->school->fresh()->ministry_code);

        $accepted = $this->post(
            '/admin/schooldetails/update/'.$this->school->id,
            $this->validPayload(['ministry_code' => 'EMIS-44112'])
        );
        $accepted->assertSessionHasNoErrors();
        $this->assertSame('EMIS-44112', $this->school->fresh()->ministry_code);
    }

    public function test_non_uganda_does_not_require_emis(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(
            '/admin/schooldetails/update/'.$this->school->id,
            $this->validPayload([
                'country_id' => $this->kenya->id,
                'ministry_code' => null,
                'board' => 'cambridge',
                'uneb_center_number' => null,
            ])
        );

        $response->assertSessionHasNoErrors();

        $fresh = $this->school->fresh();
        $this->assertSame('Kenya', $fresh->registration_country);
        $this->assertSame($this->kenya->id, (int) $fresh->country_id);
        $this->assertNull($fresh->ministry_code);
        $this->assertSame('cambridge', $fresh->curriculum);
    }

    public function test_uneb_curriculum_accepts_center_other_curricula_do_not_require_it(): void
    {
        $this->actingAs($this->admin);

        $uneb = $this->post(
            '/admin/schooldetails/update/'.$this->school->id,
            $this->validPayload([
                'board' => 'uneb',
                'uneb_center_number' => 'U5555',
            ])
        );
        $uneb->assertSessionHasNoErrors();
        $this->assertSame('U5555', $this->school->fresh()->uneb_center_number);

        $cambridge = $this->post(
            '/admin/schooldetails/update/'.$this->school->id,
            $this->validPayload([
                'board' => 'cambridge',
                'uneb_center_number' => null,
            ])
        );
        $cambridge->assertSessionHasNoErrors();
        $this->assertSame('cambridge', $this->school->fresh()->curriculum);
    }

    public function test_toshi_complete_mode_does_not_overwrite_set_values_with_null(): void
    {
        $this->school->update([
            'registration_country' => 'Uganda',
            'country_id' => $this->uganda->id,
            'ministry_code' => 'KEEP-EMIS',
            'uneb_center_number' => 'KEEP-UNEB',
            'curriculum' => 'uneb',
            'toshi_enabled' => 1,
        ]);

        $this->actingAs($this->admin);

        $component = Livewire::test(AgentToshi::class);
        $component->set('mode', 'complete');
        $component->set('schoolId', $this->school->id);
        $component->set('schoolCountry', '');
        $component->set('ministryCode', '');
        $component->set('unebCenterNumber', null);
        $component->set('curriculum', 'uneb');
        $component->set('schoolName', $this->school->name);
        $component->call('commit');

        $fresh = $this->school->fresh();
        $this->assertSame('Uganda', $fresh->registration_country);
        $this->assertSame($this->uganda->id, (int) $fresh->country_id);
        $this->assertSame('KEEP-EMIS', $fresh->ministry_code);
        $this->assertSame('KEEP-UNEB', $fresh->uneb_center_number);
    }

    public function test_edit_payload_includes_country_emis_and_uneb_fields(): void
    {
        $this->school->update([
            'registration_country' => 'Uganda',
            'country_id' => $this->uganda->id,
            'ministry_code' => 'EMIS-EDIT',
            'uneb_center_number' => 'U1111',
            'curriculum' => 'uneb',
        ]);

        $this->actingAs($this->admin);

        $response = $this->getJson('/admin/schooldetails/edit/'.$this->school->id);
        $response->assertOk();
        $response->assertJsonPath('details.country_id', $this->uganda->id);
        $response->assertJsonPath('details.registration_country', 'Uganda');
        $response->assertJsonPath('details.ministry_code', 'EMIS-EDIT');
        $response->assertJsonPath('details.uneb_center_number', 'U1111');
        $response->assertJsonPath('details.curriculum', 'uneb');
    }

    public function test_index_shows_registration_country_emis_and_uneb(): void
    {
        $this->school->update([
            'registration_country' => 'Uganda',
            'country_id' => $this->uganda->id,
            'ministry_code' => 'EMIS-SHOW',
            'uneb_center_number' => 'U2222',
            'curriculum' => 'uneb',
        ]);

        $this->actingAs($this->admin);

        $response = $this->get('/admin/schooldetails');
        $response->assertOk();
        $response->assertSee('Uganda');
        $response->assertSee('EMIS-SHOW');
        $response->assertSee('U2222');
        $response->assertSee('UNEB Centre Number');
    }
}
