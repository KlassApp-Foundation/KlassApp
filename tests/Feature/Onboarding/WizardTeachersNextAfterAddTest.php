<?php

namespace Tests\Feature\Onboarding;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Livewire\ManualOnboardingWizard;
use App\Models\Country;
use App\Models\Plan;
use App\Models\School;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class WizardTeachersNextAfterAddTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

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
            'name' => 'Bright Stars Primary',
            'email' => 'bright@test.sch.ug',
            'phone' => '0700000099',
            'slug' => 'bright-stars-primary',
            'status' => 1,
            'curriculum' => null,
            'toshi_enabled' => 1,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Bright Admin',
            'email' => 'admin@bright.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Bright',
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

    private function advanceToTeachers(object $component): void
    {
        $component
            ->set('schoolName', 'Bright Stars Primary')
            ->call('next')
            ->set('studentSize', 'Under 100 students')
            ->call('next')
            ->set('countryName', 'Uganda')
            ->call('next')
            ->set('curriculum', 'uneb')
            ->call('next')
            ->set('schoolCategory', 'primary')
            ->call('next')
            ->set('ministryCode', 'EMIS-BRIGHT')
            ->call('next')
            ->call('next') // uneb
            ->call('next') // academic year seeds classes/subjects
            ->call('next'); // structure checkpoint (optional) → teachers
    }

    public function test_next_after_add_teacher_persists_even_when_deferred_email_resyncs_blank(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $this->assertSame(
            'teachers',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null
        );

        // Reproduce Agent 2: add teacher, then Next with stale name + blank email
        // (deferred wire:model / type=email morph left email empty on the client).
        $component
            ->set('teacherName', 'Sarah Okello')
            ->set('teacherEmail', 'sarah@bright.sch.ug')
            ->set('teacherPhone', '+256700111222')
            ->call('addTeacherDraft')
            ->assertCount('teacherDrafts', 1)
            ->set('teacherName', 'Sarah Okello') // stale name re-sync
            ->set('teacherEmail', '') // blank deferred email — previously blocked Next
            ->call('next');

        $this->assertSame(1, Teacherlink::where('school_id', $this->school->id)->count());
        $this->assertSame(
            'students',
            $component->instance()->steps[$component->get('stepIndex')]['key'] ?? null,
            'Next must advance after adding a teacher; blank deferred email must not block.'
        );
        $this->assertSame('', $component->get('errorMessage'));
    }

    public function test_next_with_name_only_auto_generates_email_and_advances(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ManualOnboardingWizard::class);
        $this->advanceToTeachers($component);

        $component
            ->set('teacherName', 'John Ssali')
            ->set('teacherEmail', '')
            ->set('teacherPhone', '+256700333444')
            ->call('next');

        $this->assertSame(1, Teacherlink::where('school_id', $this->school->id)->count());
        $teacher = User::where('school_id', $this->school->id)->where('usergroup_id', 5)->first();
        $this->assertNotNull($teacher);
        $this->assertNotEmpty($teacher->email);
    }
}
