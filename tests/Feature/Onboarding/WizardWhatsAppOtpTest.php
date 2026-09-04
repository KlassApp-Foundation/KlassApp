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

class WizardWhatsAppOtpTest extends TestCase
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
            'name' => 'OTP Wizard School',
            'email' => 'otp@test.sch.ug',
            'phone' => '0700000099',
            'slug' => 'otp-wizard-school',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'OTP Admin',
            'email' => 'admin@otp.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'OTP',
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

    private function advanceToWhatsAppStep()
    {
        $this->actingAs($this->admin);

        return Livewire::test(ManualOnboardingWizard::class)
            ->set('schoolName', 'OTP Academy')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-OTP')
            ->call('next')
            ->call('next') // uneb skip
            ->call('next') // academic year
            ->call('next') // teachers skip
            ->call('next') // students skip
            ->call('next') // terms
            ->call('next') // fees
            ->assertSee('WhatsApp verification');
    }

    public function test_next_blocked_until_otp_verified(): void
    {
        $component = $this->advanceToWhatsAppStep()
            ->set('whatsappPhone', '+256700111222')
            ->call('next')
            ->assertSet('errorMessage', 'Verify the code sent to your WhatsApp before continuing.')
            ->assertSet('whatsappVerified', false);

        $this->assertNull(WhatsAppUser::where('user_id', $this->admin->id)->first());
        $this->assertStringContainsString('Verify the code', $component->get('errorMessage'));
    }

    public function test_wrong_otp_does_not_verify(): void
    {
        $this->advanceToWhatsAppStep()
            ->set('whatsappPhone', '+256700111222')
            ->call('sendWhatsAppVerificationCode')
            ->set('whatsappOtpInput', '000000')
            ->call('verifyWhatsAppCode')
            ->assertSet('whatsappVerified', false)
            ->assertSet('errorMessage', "That code doesn't match. Try again or send a new code.");
    }

    public function test_correct_otp_then_next_persists_whatsapp_user(): void
    {
        $component = $this->advanceToWhatsAppStep()
            ->set('whatsappPhone', '+256700111222')
            ->call('sendWhatsAppVerificationCode');

        $code = (string) $component->get('whatsappOtpDisplay');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $component
            ->set('whatsappOtpInput', $code)
            ->call('verifyWhatsAppCode')
            ->assertSet('whatsappVerified', true)
            ->call('next')
            ->assertSet('errorMessage', '');

        $wa = WhatsAppUser::where('user_id', $this->admin->id)->first();
        $this->assertNotNull($wa);
        $this->assertEquals('+256700111222', $wa->phone);
        $this->assertNotNull($wa->verified_at);
    }
}
