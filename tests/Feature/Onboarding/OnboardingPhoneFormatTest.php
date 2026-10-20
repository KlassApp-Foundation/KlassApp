<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\AgentToshi;
use App\Livewire\ManualOnboardingWizard;
use App\Models\AcademicYear;
use App\Models\Country;
use App\Models\EarlyAccessRequest;
use App\Models\Plan;
use App\Models\School;
use App\Models\TeacherInvite;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class OnboardingPhoneFormatTest extends TestCase
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
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Phone Format School',
            'email' => 'phone-format@test.sch.ug',
            'phone' => '+256700000001',
            'slug' => 'phone-format-school',
            'status' => 1,
            'curriculum' => null,
            'registration_country' => 'Uganda',
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Phone Admin',
            'email' => 'admin@phone-format.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Phone',
            'lastname' => 'Admin',
        ]);
    }

    public function test_early_access_accepts_local_international_and_rejects_invalid(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $this->get('/early-access')
            ->assertOk()
            ->assertSee('data-testid="early-access-phone-hint"', false)
            ->assertSee('Your phone number, including the country code.', false)
            ->assertSee('data-example="+256 765 274 289"', false);

        $this->post('/early-access', $this->earlyAccessPayload([
            'email' => 'local@early.test',
            'phone' => '0765274289',
            'country' => 'Uganda',
        ]))->assertRedirect(route('early-access.show'));
        $this->assertSame('+256765274289', EarlyAccessRequest::where('email', 'local@early.test')->value('phone'));

        foreach ([
            '0765 274 289',
            '+256765274289',
            '+256 765 274 289',
            '+256-765-274-289',
            '256765274289',
            '256 765 274 289',
            '256-765-274-289',
        ] as $index => $phone) {
            $email = "fmt{$index}@early.test";
            $this->post('/early-access', $this->earlyAccessPayload([
                'email' => $email,
                'phone' => $phone,
                'country' => 'Uganda',
            ]))->assertRedirect(route('early-access.show'));
            $this->assertSame('+256765274289', EarlyAccessRequest::where('email', $email)->value('phone'));
        }

        $this->from('/early-access')->post('/early-access', $this->earlyAccessPayload([
            'email' => 'bad@early.test',
            'phone' => '12345',
            'country' => 'Uganda',
        ]))->assertSessionHasErrors([
            'phone' => 'Enter your phone number with the country code, e.g. +256 765 274 289.',
        ]);
        $this->assertNull(EarlyAccessRequest::where('email', 'bad@early.test')->first());

        $this->post('/early-access', $this->earlyAccessPayload([
            'email' => 'kenya@early.test',
            'phone' => '0712-345-678',
            'country' => 'Kenya',
        ]))->assertRedirect(route('early-access.show'));
        $this->assertSame('+254712345678', EarlyAccessRequest::where('email', 'kenya@early.test')->value('phone'));
    }

    public function test_wizard_whatsapp_teacher_and_parent_phones_accept_local_formats(): void
    {
        $this->actingAs($this->admin);

        $wizard = Livewire::test(ManualOnboardingWizard::class);

        $wizard->set('whatsappPhone', '0765274289')
            ->call('sendWhatsAppVerificationCode')
            ->assertSet('errorMessage', '')
            ->assertSet('whatsappPhone', '+256765274289');
        $this->assertNotSame('', $wizard->get('whatsappOtpDisplay'));

        $wizard->set('whatsappPhone', '+256 765 274 289')
            ->call('sendWhatsAppVerificationCode')
            ->assertSet('whatsappPhone', '+256765274289');

        $wizard->set('whatsappPhone', '256-765-274-289')
            ->call('sendWhatsAppVerificationCode')
            ->assertSet('whatsappPhone', '+256765274289');

        $wizard->set('whatsappPhone', '12345')
            ->call('sendWhatsAppVerificationCode')
            ->assertSet('errorMessage', 'Enter your WhatsApp number with the country code, e.g. +256 765 274 289.')
            ->assertSet('whatsappOtpDisplay', '');

        $wizard->set('teacherName', 'Ada Teacher')
            ->set('teacherEmail', 'ada@phone-format.sch.ug')
            ->set('teacherPhone', '0765 274 289')
            ->call('addTeacherDraft')
            ->assertSet('errorMessage', '');
        $this->assertSame('+256765274289', $wizard->get('teacherDrafts')[0]['phone']);

        $wizard->set('teacherName', 'Bad Teacher')
            ->set('teacherEmail', 'bad-teacher@phone-format.sch.ug')
            ->set('teacherPhone', 'not-a-phone')
            ->call('addTeacherDraft')
            ->assertSet('errorMessage', 'Enter your phone number with the country code, e.g. +256 765 274 289.');
        $this->assertCount(1, $wizard->get('teacherDrafts'));

        $wizard->set('studentName', 'Amina Student')
            ->set('studentClass', 'P.1')
            ->set('studentParentPhone', '0765274289')
            ->call('addStudentDraft')
            ->assertSet('errorMessage', '');
        $this->assertSame('+256765274289', $wizard->get('studentDrafts')[0]['parent_phone']);

        $wizard->set('studentName', 'Bad Student')
            ->set('studentClass', 'P.1')
            ->set('studentParentPhone', '12345')
            ->call('addStudentDraft')
            ->assertSet('errorMessage', 'Enter your phone number with the country code, e.g. +256 765 274 289.');
        $this->assertCount(1, $wizard->get('studentDrafts'));

        $studentsIndex = collect($wizard->get('steps'))->search(
            fn ($step) => ($step['key'] ?? '') === 'students'
        );
        $whatsappIndex = collect($wizard->get('steps'))->search(
            fn ($step) => ($step['key'] ?? '') === 'whatsapp_verify'
        );
        $this->assertNotFalse($studentsIndex);
        $this->assertNotFalse($whatsappIndex);

        $wizard->call('goToStep', $studentsIndex)
            ->assertSee("Parent's phone number, e.g. +256 765 274 289");
        $wizard->call('goToStep', $whatsappIndex)
            ->assertSee('Your WhatsApp number, e.g. +256 765 274 289', false);
    }

    public function test_wizard_whatsapp_save_stores_the_normalised_number(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ManualOnboardingWizard::class)
            ->set('whatsappPhone', '0765274289')
            ->call('sendWhatsAppVerificationCode')
            ->assertSet('whatsappPhone', '+256765274289');

        // saveWhatsApp is private. Drive the stored value through the engine.
        $saved = app(\App\Services\OnboardingEngine::class)->saveWhatsApp(
            $this->school->fresh(),
            $this->admin->id,
            '0765-274-289',
        );

        $this->assertSame('+256765274289', $saved['linked']['phone']);
        $this->assertSame('+256765274289', WhatsAppUser::where('user_id', $this->admin->id)->value('phone'));
    }

    public function test_engine_stores_normalised_teacher_and_parent_phones(): void
    {
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'AY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $engine = app(\App\Services\OnboardingEngine::class);

        $teachers = $engine->saveTeachers($this->school, $year, [[
            'name' => 'Ada Teacher',
            'email' => 'ada-engine@phone-format.sch.ug',
            'phone' => '+256 765 274 289',
        ]]);
        $this->assertSame('+256765274289', $teachers['created'][0]['phone'] ?? User::where('email', 'ada-engine@phone-format.sch.ug')->value('mobile_no'));
        $this->assertSame('+256765274289', User::where('email', 'ada-engine@phone-format.sch.ug')->value('mobile_no'));

        $students = $engine->saveStudents($this->school, $year, [[
            'name' => 'Amina Student',
            'phone' => '0765-274-289',
        ]]);
        $studentId = $students['created'][0]['user_id'] ?? null;
        $this->assertNotNull($studentId);
        $this->assertSame('+256765274289', User::whereKey($studentId)->value('mobile_no'));

        try {
            $engine->saveTeachers($this->school, $year, [[
                'name' => 'Bad Teacher',
                'email' => 'bad-engine@phone-format.sch.ug',
                'phone' => 'not-a-phone',
            ]]);
            $this->fail('An invalid teacher phone should be rejected.');
        } catch (ValidationException $e) {
            $this->assertSame(
                'Teacher Bad Teacher has a phone that could not be read. Enter the phone number with the country code, e.g. +256 765 274 289.',
                $e->errors()['teachers'][0] ?? null,
            );
        }
        $this->assertNull(User::where('email', 'bad-engine@phone-format.sch.ug')->first());

        try {
            $engine->saveStudents($this->school, $year, [[
                'name' => 'Bad Student',
                'phone' => '12345',
            ]]);
            $this->fail('An invalid parent phone should be rejected.');
        } catch (ValidationException $e) {
            $this->assertSame(
                'Student Bad Student has a phone that could not be read. Enter the phone number with the country code, e.g. +256 765 274 289.',
                $e->errors()['students'][0] ?? null,
            );
        }
        $this->assertNull(User::where('name', 'Bad Student')->first());
    }

    public function test_upload_keeps_a_row_when_the_phone_cannot_be_read(): void
    {
        $this->actingAs($this->admin);
        $wizard = Livewire::test(ManualOnboardingWizard::class);
        $wizard->set('errorMessage', 'Enter your phone number with the country code, e.g. leftover from before.');

        $good = UploadedFile::fake()->createWithContent(
            'teachers-good.csv',
            "Name,Email,Phone\nAda Teacher,ada-upload@phone-format.sch.ug,0765274289\n",
        );
        $wizard->set('teacherUpload', $good)
            ->assertCount('teacherDrafts', 1)
            ->assertSet('errorMessage', '');
        $this->assertSame('+256765274289', $wizard->get('teacherDrafts')[0]['phone']);

        $mixed = UploadedFile::fake()->createWithContent(
            'teachers-mixed.csv',
            "Name,Email,Phone\nBen Teacher,ben-upload@phone-format.sch.ug,+256 765 274 289\nCara Teacher,cara-upload@phone-format.sch.ug,12345\n",
        );
        $wizard->set('teacherUpload', $mixed)
            ->assertCount('teacherDrafts', 3)
            ->assertSet('errorMessage', 'Could not read the phone for Cara Teacher. The row was kept without that phone. Enter the number with the country code, e.g. +256 765 274 289.');
        $this->assertSame('+256765274289', $wizard->get('teacherDrafts')[1]['phone']);
        $this->assertSame('Cara Teacher', $wizard->get('teacherDrafts')[2]['name']);
        $this->assertSame('', $wizard->get('teacherDrafts')[2]['phone']);

        $students = UploadedFile::fake()->createWithContent(
            'students.csv',
            "Name,Class,Parent Phone\nAmina Student,P.1,0765-274-289\nBad Student,P.1,not-a-phone\n",
        );
        $wizard->set('studentUpload', $students)
            ->assertCount('studentDrafts', 2)
            ->assertSet('errorMessage', 'Could not read the phone for Bad Student. The row was kept without that phone. Enter the number with the country code, e.g. +256 765 274 289.');
        $this->assertSame('Bad Student', $wizard->get('studentDrafts')[1]['name']);
        $this->assertSame('', $wizard->get('studentDrafts')[1]['parent_phone']);
        $this->assertSame('+256765274289', $wizard->get('studentDrafts')[0]['parent_phone']);
    }

    public function test_wizard_structure_class_teacher_phone_accepts_a_local_number(): void
    {
        Mail::fake();
        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
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

        $this->actingAs($this->admin);
        $wizard = Livewire::test(ManualOnboardingWizard::class)
            ->set('schoolName', 'Phone Format School')
            ->call('next')
            ->set('studentSize', 'Up to 500')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-PHONE')
            ->call('next')
            ->call('next')
            ->call('next');

        $this->assertSame('standards', $wizard->instance()->steps[$wizard->get('stepIndex')]['key'] ?? null);
        $wizard->assertSee("Class teacher's phone number, e.g. +256 765 274 289");

        $sectionId = (int) $wizard->get('structureClasses')[0]['section_id'];
        $wizard->set('structureCtDrafts.'.$sectionId, [
            'email' => 'ct@phone-format.sch.ug',
            'existing_teacher_id' => '',
            'name' => 'Grace CT',
            'phone' => '0765274289',
        ])->call('inviteStructureClassTeacher', $sectionId)
            ->assertSet('errorMessage', '');

        $this->assertSame('+256765274289', TeacherInvite::where('email', 'ct@phone-format.sch.ug')->value('phone'));

        $wizard->set('structureCtDrafts.'.$sectionId, [
            'email' => 'bad-ct@phone-format.sch.ug',
            'existing_teacher_id' => '',
            'name' => 'Bad CT',
            'phone' => '12345',
        ])->call('inviteStructureClassTeacher', $sectionId)
            ->assertSet('errorMessage', 'Enter your phone number with the country code, e.g. +256 765 274 289.');
        $this->assertNull(TeacherInvite::where('email', 'bad-ct@phone-format.sch.ug')->first());
    }

    public function test_toshi_parent_teacher_and_whatsapp_steps_accept_local_numbers(): void
    {
        $this->actingAs($this->admin);

        $toshi = Livewire::test(AgentToshi::class)
            ->set('mode', 'create')
            ->set('schoolCountry', 'Uganda')
            ->set('schoolId', $this->school->id);

        $toshi->set('studentFormName', 'Amina Student')
            ->set('studentFormClass', 'P.1')
            ->set('studentFormParentPhone', '0765274289')
            ->call('saveStudent');
        $this->assertSame('+256765274289', $toshi->get('actionData')['students'][0]['parent_phone']);

        $toshi->set('studentFormName', 'Bad Student')
            ->set('studentFormClass', 'P.1')
            ->set('studentFormParentPhone', '12345')
            ->call('saveStudent');
        $this->assertCount(1, $toshi->get('actionData')['students']);
        $this->assertStringContainsString(
            'Enter your phone number with the country code, e.g. +256 765 274 289.',
            collect($toshi->get('messages'))->pluck('text')->implode("\n"),
        );

        $toshi->set('teacherFormName', 'Ada Teacher')
            ->set('teacherFormPhone', '+256 765 274 289')
            ->call('saveTeacher');
        $this->assertSame('+256765274289', $toshi->get('actionData')['teacherPhones']['Ada Teacher']);

        $toshi->set('teacherFormName', 'Bad Teacher')
            ->set('teacherFormPhone', 'not-a-phone')
            ->call('saveTeacher');
        $this->assertArrayNotHasKey('Bad Teacher', $toshi->get('actionData')['teacherPhones']);

        $toshi->set('mode', 'create')
            ->set('actionStep', null)
            ->set('step', 15)
            ->set('substep', 2)
            ->set('input', '0765274289')
            ->call('send')
            ->assertSet('whatsappPhone', '+256765274289');

        $toshi->set('mode', 'create')
            ->set('actionStep', null)
            ->set('step', 5)
            ->set('substep', 4)
            ->set('input', '256-765-274-289')
            ->call('send')
            ->assertSet('schoolPhone', '+256765274289');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function earlyAccessPayload(array $overrides = []): array
    {
        return array_merge([
            'school_name' => 'Hillside Academy',
            'contact_name' => 'Alex Example',
            'contact_role' => 'Head teacher',
            'email' => 'alex@example.com',
            'phone' => '+256765274289',
            'country' => 'Uganda',
            'approx_students' => '320',
            'consent' => '1',
        ], $overrides);
    }
}
