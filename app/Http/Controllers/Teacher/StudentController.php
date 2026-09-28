<?php

namespace App\Http\Controllers\Teacher;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Requests\UserProfileUpdateRequest;
use App\Models\User;
use App\Services\RosterScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends AdminStudentController
{
    public function edit($name)
    {
        $student = $this->studentForTeacher($name);

        return response()->view('/admin/member/edit', [
            'user' => $student,
            'userprofile' => $student->userprofile,
        ]);
    }

    public function update(Request $request, $name)
    {
        $this->studentForTeacher($name);

        return parent::update($request, $name);
    }

    public function data($name)
    {
        $this->studentForTeacher($name);

        return response()->json(parent::editStudent($name));
    }

    public function validation(UserProfileUpdateRequest $request, $name)
    {
        $this->studentForTeacher($name);

        return parent::editValidationUser($request, $name);
    }

    private function studentForTeacher(string $name): User
    {
        $teacher = Auth::user();
        $student = User::findByExactNameInSchool($name, (int) $teacher->school_id, 6);

        abort_unless($student !== null, 404);

        $academicYear = SiteHelper::getAcademicYear((int) $teacher->school_id);
        abort_unless(
            $academicYear !== null
            && app(RosterScopeService::class)->actorCanAccessStudent($teacher, $student, (int) $academicYear->id),
            403
        );

        return $student;
    }
}