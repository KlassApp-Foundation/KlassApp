<?php

namespace Tests\Feature\Onboarding;

use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\SchoolSignupBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ToshiSchoolNameOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => "Grace's School",
            'email' => 'grace-onboard@example.com',
            'phone' => '+256701111111',
            'slug' => 'graces-school-onboard',
            'status' => 1,
            'toshi_enabled' => 1,
            'curriculum' => null,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Grace Admin',
            'email' => 'grace-onboard@example.com',
            'password' => Hash::make('secret123'),
            'email_verified' => 1,
            'mobile_no' => '+256701111111',
            'status' => 'active',
        ]);

        Userprofile::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'firstname' => 'Grace Admin',
        ]);
    }

    /** @test */
    public function complete_mode_multi_word_school_name_via_send_asks_confirm_not_student_lookup(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);

        $this->assertSame('complete', $component->get('mode'));
        $this->assertSame('school_info', $component->get('steps')[$component->get('step')]);
        $this->assertSame(0, (int) $component->get('substep'));

        $component->set('input', 'My Cool Primary School')->call('send');

        $this->assertSame('complete', $component->get('mode'));
        $this->assertSame('school_info', $component->get('steps')[$component->get('step')]);
        $this->assertSame(1, (int) $component->get('substep'));
        $this->assertTrue((bool) $component->get('awaitingConfirm'));
        $this->assertSame('My Cool Primary School', $component->get('schoolName'));

        $botTexts = collect($component->get('messages'))
            ->where('role', 'bot')
            ->pluck('text')
            ->implode("\n");

        $this->assertStringNotContainsString("couldn't find a student", $botTexts);
        $this->assertStringContainsString('Is the name correct?', $botTexts);
        $this->assertStringContainsString('My Cool Primary School', $botTexts);
    }

    /** @test */
    public function complete_mode_colliding_school_name_via_send_gets_unique_suffix(): void
    {
        School::create([
            'name' => 'Sunrise Primary School',
            'email' => 'sunrise-existing@example.com',
            'phone' => '+256702222222',
            'slug' => 'sunrise-primary-existing',
            'status' => 1,
            'toshi_enabled' => 1,
            'curriculum' => 'uneb',
        ]);

        $expected = app(SchoolSignupBootstrapService::class)->uniqueSchoolName('Sunrise Primary School');
        $this->assertSame('Sunrise Primary School-2', $expected);

        $this->actingAs($this->admin);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);

        $component->set('input', 'Sunrise Primary School')->call('send');

        $this->assertSame('complete', $component->get('mode'));
        $this->assertSame(1, (int) $component->get('substep'));
        $this->assertTrue((bool) $component->get('awaitingConfirm'));
        $this->assertSame('Sunrise Primary School-2', $component->get('schoolName'));

        $botTexts = collect($component->get('messages'))
            ->where('role', 'bot')
            ->pluck('text')
            ->implode("\n");

        $this->assertStringNotContainsString('already exists', $botTexts);
        $this->assertStringNotContainsString("couldn't find a student", $botTexts);
        $this->assertStringContainsString('Sunrise Primary School-2', $botTexts);
        $this->assertStringContainsString('Is the name correct?', $botTexts);

        $component->set('input', 'yes')->call('send');

        $this->school->refresh();
        $this->assertSame('Sunrise Primary School-2', $this->school->name);
    }

    /** @test */
    public function multi_word_name_still_triggers_student_lookup_outside_school_info(): void
    {
        // Mark school as fully named so mount does not enter school_info complete flow.
        $this->school->update([
            'name' => 'Lakeside Academy',
            'curriculum' => 'uneb',
            'slug' => 'lakeside-academy-lookup',
        ]);

        // Satisfy remaining gates that would keep complete mode (AY + a standard).
        \App\Models\AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => date('Y'),
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'Current Academic Year',
            'description' => 'Current Academic Year',
            'status' => 1,
        ]);

        \App\Models\Standard::create([
            'school_id' => $this->school->id,
            'name' => 'Primary',
            'order' => 1,
            'status' => 1,
        ]);

        $this->actingAs($this->admin);

        $component = Livewire::test(\App\Livewire\AgentToshi::class);

        // Force assistant mode away from school_info even if other steps remain incomplete.
        $component->set('mode', 'assistant');
        $component->set('step', 99);
        $component->set('substep', 0);
        $component->set('awaitingConfirm', false);
        $component->set('actionStep', null);

        $component->set('input', 'My Cool Primary School')->call('send');

        $botTexts = collect($component->get('messages'))
            ->where('role', 'bot')
            ->pluck('text')
            ->implode("\n");

        $this->assertStringContainsString("couldn't find a student", $botTexts);
        $this->assertSame('assistant', $component->get('mode'));
    }
}
