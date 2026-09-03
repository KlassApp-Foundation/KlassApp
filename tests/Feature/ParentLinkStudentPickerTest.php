<?php

namespace Tests\Feature;

use App\Livewire\Admin\ParentLinkStudentPicker;
use App\Models\AcademicYear;
use App\Models\Approval;
use App\Models\ParentLinkRequest;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\WhatsApp\ParentLinkRequestService;
use App\Services\WhatsAppBusinessService;
use App\States\Approval\Pending;
use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ParentLinkStudentPickerTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200),
        ]);

        config([
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '1416403124879552',
            'services.whatsapp.business_api_version' => 'v21.0',
        ]);

        $this->withoutMiddleware([
            VerifyCsrfToken::class,
            MustBeSchoolAdmin::class,
            MustBePrivilege::class,
        ]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Picker School',
            'email' => 'picker@test.sch.ug',
            'status' => 1,
        ]);

        $this->admin = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Picker Admin',
        ]);

        $link = $this->createStandardLink($this->school, 'P.5');
        $this->student = $this->createStudent($this->school, $link, 'KEVIN MWESIGYE');
    }

    public function test_search_students_for_admin_matches_name_tokens_without_class_filter(): void
    {
        $matches = app(ParentLinkRequestService::class)
            ->searchStudentsForAdmin('Mwesigye Ford', $this->school->id);

        $this->assertTrue($matches->contains('id', $this->student->id));
    }

    public function test_picker_livewire_finds_and_selects_student_by_typed_name(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ParentLinkStudentPicker::class, [
                'schoolId' => $this->school->id,
                'initialQuery' => 'Mwesigye Ford',
            ])
            ->assertSee('KEVIN MWESIGYE')
            ->call('selectStudent', $this->student->id)
            ->assertSet('selectedStudentId', $this->student->id)
            ->assertSee('Change');
    }

    public function test_picker_does_not_leak_other_school_students(): void
    {
        $other = School::create([
            'name' => 'Other Picker School',
            'email' => 'other-picker@test.sch.ug',
            'status' => 1,
        ]);
        $otherLink = $this->createStandardLink($other, 'P.5');
        $this->createStudent($other, $otherLink, 'KEVIN MWESIGYE OTHER');

        Livewire::actingAs($this->admin)
            ->test(ParentLinkStudentPicker::class, [
                'schoolId' => $this->school->id,
                'initialQuery' => 'Mwesigye',
            ])
            ->assertSee('KEVIN MWESIGYE')
            ->assertDontSee('KEVIN MWESIGYE OTHER');
    }

    public function test_inbox_shows_name_search_when_candidates_empty_not_numeric_id(): void
    {
        $linkRequest = ParentLinkRequest::create([
            'school_id' => $this->school->id,
            'phone' => '+256700111000',
            'parent_name' => 'Empty Cand Parent',
            'child_name' => 'Mwesigye Ford',
            'child_class' => 'P5',
            'school_name' => 'Picker School',
            'suggested_student_id' => null,
            'candidate_student_ids' => null,
            'status' => 'pending',
        ]);

        Approval::create([
            'approvable_type' => ParentLinkRequest::class,
            'approvable_id' => $linkRequest->id,
            'state' => Pending::class,
            'requested_by' => null,
            'comments' => $linkRequest->summaryLine(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.approvals.inbox'));

        $response->assertOk();
        $response->assertSee('Search student name', false);
        $response->assertDontSee('Student user ID', false);
        $response->assertSee('Mwesigye Ford', false);
    }

    public function test_matched_candidate_dropdown_path_unchanged(): void
    {
        $linkRequest = ParentLinkRequest::create([
            'school_id' => $this->school->id,
            'phone' => '+256700222000',
            'parent_name' => 'Matched Parent',
            'child_name' => 'KEVIN MWESIGYE',
            'child_class' => 'P.5',
            'school_name' => 'Picker School',
            'suggested_student_id' => $this->student->id,
            'candidate_student_ids' => [$this->student->id],
            'status' => 'pending',
        ]);

        Approval::create([
            'approvable_type' => ParentLinkRequest::class,
            'approvable_id' => $linkRequest->id,
            'state' => Pending::class,
            'requested_by' => null,
            'comments' => $linkRequest->summaryLine(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.approvals.inbox'));

        $response->assertOk();
        $response->assertSee('name="matched_student_id"', false);
        $response->assertSee('KEVIN MWESIGYE', false);
        $response->assertDontSee('Search student name', false);
        $response->assertDontSee('wire:model.live.debounce.300ms="query"', false);
    }

    public function test_approve_with_searched_student_id_when_candidates_null(): void
    {
        $this->app->instance(
            WhatsAppBusinessService::class,
            \Mockery::mock(WhatsAppBusinessService::class, function ($mock) {
                $mock->shouldReceive('sendText')->andReturn(['success' => true, 'message_id' => 'ok']);
            })
        );

        $linkRequest = ParentLinkRequest::create([
            'school_id' => $this->school->id,
            'phone' => '+256700333000',
            'parent_name' => 'Search Approve Parent',
            'child_name' => 'Mwesigye Ford',
            'child_class' => 'P5',
            'school_name' => 'Picker School',
            'suggested_student_id' => null,
            'candidate_student_ids' => null,
            'status' => 'pending',
        ]);

        $approval = Approval::create([
            'approvable_type' => ParentLinkRequest::class,
            'approvable_id' => $linkRequest->id,
            'state' => Pending::class,
            'requested_by' => null,
            'comments' => $linkRequest->summaryLine(),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.approvals.approve', $approval), [
            'matched_student_id' => $this->student->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('approved', $linkRequest->fresh()->status);
        $this->assertSame($this->student->id, $linkRequest->fresh()->matched_student_id);
    }

    private function createStandardLink(School $school, string $sectionName): StandardLink
    {
        $ay = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => '2026-12-31 23:59:59',
            'status' => 1,
        ]);

        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary_upper', 'order' => 1]);
        $section = Section::create(['school_id' => $school->id, 'name' => $sectionName]);

        return StandardLink::create([
            'school_id' => $school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $ay->id,
        ]);
    }

    private function createStudent(School $school, StandardLink $link, string $name): User
    {
        $student = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => $name,
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $link->academic_year_id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);

        return $student;
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
