<?php

namespace Tests\Feature;

use App\Http\Requests\StandardDetailRequest;
use App\Http\Requests\StandardDetailUpdateRequest;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SetupClassTeacherValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_rules_require_same_school_teacher_for_both_teacher_fields(): void
    {
        [$school, $admin, $teacher, $foreignTeacher, $standard, $section] = $this->fixture();

        $validRequest = Request::create('/', 'POST', [
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $teacher->id,
            'section_class_teacher_id' => $teacher->id,
            'standard_name' => '1',
            'count' => 0,
        ]);
        $validValidator = $this->validatorFor(StandardDetailRequest::class, $validRequest, $admin);

        $this->assertFalse($validValidator->fails());

        $invalidRequest = Request::create('/', 'POST', [
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $foreignTeacher->id,
            'section_class_teacher_id' => $foreignTeacher->id,
            'standard_name' => '1',
            'count' => 0,
        ]);
        $invalidValidator = $this->validatorFor(StandardDetailRequest::class, $invalidRequest, $admin);

        $this->assertTrue($invalidValidator->fails());
        $this->assertTrue($invalidValidator->errors()->has('class_teacher_id'));
        $this->assertTrue($invalidValidator->errors()->has('section_class_teacher_id'));
    }

    public function test_update_rules_allow_nullable_teachers_and_reject_foreign_teacher(): void
    {
        [$school, $admin, $teacher, $foreignTeacher, $standard, $section] = $this->fixture();

        $nullableRequest = Request::create('/', 'POST', [
            'id' => 999,
            'class_teacher_id' => null,
            'section_class_teacher_id' => null,
            'standard' => '1',
            'count' => 0,
        ]);
        $nullableValidator = $this->validatorFor(StandardDetailUpdateRequest::class, $nullableRequest, $admin);

        $this->assertFalse($nullableValidator->fails());

        $invalidRequest = Request::create('/', 'POST', [
            'id' => 999,
            'class_teacher_id' => $foreignTeacher->id,
            'section_class_teacher_id' => $foreignTeacher->id,
            'standard' => '1',
            'count' => 0,
        ]);
        $invalidValidator = $this->validatorFor(StandardDetailUpdateRequest::class, $invalidRequest, $admin);

        $this->assertTrue($invalidValidator->fails());
        $this->assertTrue($invalidValidator->errors()->has('class_teacher_id'));
        $this->assertTrue($invalidValidator->errors()->has('section_class_teacher_id'));
    }

    public function test_create_and_edit_standard_link_are_null_safe_and_support_section_teacher(): void
    {
        [$school, $admin, $teacher, $foreignTeacher, $standard, $section] = $this->fixture();
        $academicYear = AcademicYear::where('school_id', $school->id)->firstOrFail();
        $controller = app(\App\Http\Controllers\Admin\SectionController::class);

        Auth::setUser($admin);
        $createRequest = Request::create('/', 'POST', [
            'class_teacher_id' => null,
            'section_class_teacher_id' => $teacher->id,
            'standard_id' => $standard->id,
            'no_of_students' => null,
            'standard_name' => '1',
            'section_id' => $section->id,
            'count' => 0,
        ]);

        $standardLink = $controller->createStandardLink($school->id, $academicYear->id, $createRequest);

        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'class_teacher_id' => $teacher->id,
        ]);
        $this->assertNull($standardLink->class_teacher_id);

        $editRequest = Request::create('/', 'POST', [
            'class_teacher_id' => null,
            'section_class_teacher_id' => null,
            'no_of_students' => null,
            'standard' => '1',
            'count' => 0,
        ]);

        $controller->editStandardLink($school->id, $academicYear->id, $standardLink->id, $editRequest);

        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'class_teacher_id' => null,
        ]);
        $this->assertDatabaseHas('standards_link', [
            'id' => $standardLink->id,
            'class_teacher_id' => null,
        ]);
    }

    /** @return array{0: School, 1: User, 2: User, 3: User, 4: Standard, 5: Section} */
    private function fixture(): array
    {
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $school = School::create([
            'name' => 'Setup Teacher School',
            'slug' => 'setup-teacher-school-' . uniqid(),
            'email' => 'setup-teacher-' . uniqid() . '@test.sch.ug',
            'phone' => '0700000003',
            'status' => 1,
        ]);
        $foreignSchool = School::create([
            'name' => 'Foreign Teacher School',
            'slug' => 'foreign-teacher-school-' . uniqid(),
            'email' => 'foreign-teacher-' . uniqid() . '@test.sch.ug',
            'phone' => '0700000004',
            'status' => 1,
        ]);
        $admin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
        ]);
        $teacher = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 5,
        ]);
        $foreignTeacher = User::factory()->create([
            'school_id' => $foreignSchool->id,
            'usergroup_id' => 5,
        ]);
        AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P1',
            'status' => 1,
        ]);

        return [$school, $admin, $teacher, $foreignTeacher, $standard, $section];
    }

    private function validatorFor(string $formRequestClass, Request $request, User $admin): \Illuminate\Contracts\Validation\Validator
    {
        Auth::setUser($admin);
        $this->app->instance('request', $request);
        $formRequest = new $formRequestClass;
        $validator = Validator::make($request->all(), $formRequest->rules(), $formRequest->messages());

        return $validator;
    }
}
