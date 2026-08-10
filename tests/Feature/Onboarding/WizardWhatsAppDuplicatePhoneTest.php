<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class WizardWhatsAppDuplicatePhoneTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

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

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $this->school = School::create([
            'name' => "WA Dup's School",
            'email' => 'wadup@test.sch.ug',
            'phone' => '0700000088',
            'slug' => 'wa-dup-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'WA Admin',
            'email' => 'admin@wadup.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'WA',
            'lastname' => 'Admin',
        ]);

        Plan::create([
            'name' => 'Freemium',
            'display_name' => 'Freemium',
            'cycle' => 30,
            'no_of_students' => 0,
            'no_of_users' => 0,
            'amount' => 0,
            'order' => 1,
            'is_active' => 1,
        ]);
    }

    public function test_duplicate_whatsapp_phone_shows_validation_not_sql(): void
    {
        WhatsAppUser::create([
            'phone' => '+256700555666',
            'user_id' => null,
            'school_id' => null,
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        $this->actingAs($this->admin);

        $component = Livewire::test(ManualOnboardingWizard::class);

        $component
            ->set('schoolName', 'WA Dup Academy')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('ministryCode', 'EMIS-WADUP')
            ->call('next')
            ->call('next') // uneb skip
            ->call('next') // academic year
            ->set('className', 'P1')
            ->call('next')
            ->set('subjectName', 'Math')
            ->call('next')
            ->call('next') // teachers skip
            ->call('next') // students skip
            ->call('next') // terms (defaults)
            ->call('next') // fees (defaults)
            ->assertSee('WhatsApp verification')
            ->set('whatsappPhone', '+256700555666')
            ->call('next')
            ->assertSet('errorMessage', 'This WhatsApp number is already registered')
            ->assertDontSee('SQLSTATE', false)
            ->assertDontSee('Integrity constraint', false)
            ->assertDontSee('whatsapp_users_phone_unique', false);

        $html = $component->html();
        $this->assertStringNotContainsString('SQLSTATE', $html);
        $this->assertStringNotContainsString('Connection:', $html);
        $this->assertStringContainsString('This WhatsApp number is already registered', $html);

        $this->assertNull(
            WhatsAppUser::where('user_id', $this->admin->id)->first(),
            'Duplicate phone must not create a row for the current admin'
        );
    }
}
