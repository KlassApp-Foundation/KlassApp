<?php

namespace Tests\Feature\Superadmin;

use App\Livewire\Superadmin\Academics\CreateSchool;
use App\Models\City;
use App\Models\Country;
use App\Models\EmisSchool;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CreateSchoolUnebToshiTest extends TestCase
{
    use RefreshDatabase;

    private User $siteadmin;

    private Country $uganda;

    private City $kampala;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->siteadmin = User::factory()->create([
            'usergroup_id' => 1,
            'school_id' => null,
            'email' => 'siteadmin@create.sch.ug',
        ]);

        $this->uganda = Country::create([
            'name' => 'Uganda', 'short_name' => 'UG', 'iso_code' => 'UG',
            'tel_prefix' => '+256', 'status' => 1,
        ]);

        $this->kampala = City::create([
            'country_id' => $this->uganda->id, 'name' => 'Kampala', 'status' => 1, 'state_id' => 0,
        ]);

        EmisSchool::create([
            'emis_code' => '4527', 'school_name' => 'Create EMIS School',
            'district' => 'Kampala', 'status' => 1,
        ]);
    }

    private function fillValid(object $c): object
    {
        return $c
            ->set('name', 'New Superadmin School')
            ->set('email', 'new.super.'.uniqid().'@example.com')
            ->set('phone', '0700123456')
            ->set('address', 'Plot 1 Kampala')
            ->set('city', $this->kampala->id)
            ->set('country', $this->uganda->id)
            ->set('pincode', '25601')
            ->set('registration_country', 'Uganda')
            ->set('ministry_code', '4527')
            ->set('curriculum', 'uneb')
            ->set('uneb_center_number', 'U0777');
    }

    /** @test */
    public function toshi_enabled_defaults_to_on(): void
    {
        $this->actingAs($this->siteadmin);

        Livewire::test(CreateSchool::class, ['id' => ''])
            ->assertSet('toshi_enabled', true);
    }

    /** @test */
    public function creates_school_with_uneb_center_and_toshi_enabled(): void
    {
        $this->actingAs($this->siteadmin);

        $component = Livewire::test(CreateSchool::class, ['id' => '']);
        $this->fillValid($component)->call('submitSchool');

        $school = School::where('name', 'New Superadmin School')->firstOrFail();
        $this->assertSame('U0777', $school->uneb_center_number);
        $this->assertSame(1, (int) $school->toshi_enabled);
        $this->assertSame('uneb', $school->curriculum);
        $this->assertSame('4527', $school->ministry_code);
    }

    /** @test */
    public function toshi_can_be_turned_off_explicitly(): void
    {
        $this->actingAs($this->siteadmin);

        $component = Livewire::test(CreateSchool::class, ['id' => '']);
        $this->fillValid($component)
            ->set('name', 'Toshi Off School')
            ->set('toshi_enabled', false)
            ->call('submitSchool');

        $school = School::where('name', 'Toshi Off School')->firstOrFail();
        $this->assertSame(0, (int) $school->toshi_enabled);
    }

    /** @test */
    public function uganda_school_requires_emis_code(): void
    {
        $this->actingAs($this->siteadmin);

        $component = Livewire::test(CreateSchool::class, ['id' => '']);
        $this->fillValid($component)
            ->set('name', 'No EMIS School')
            ->set('ministry_code', null)
            ->call('submitSchool')
            ->assertHasErrors('ministry_code');

        $this->assertDatabaseMissing('schools', ['name' => 'No EMIS School']);
    }
}
