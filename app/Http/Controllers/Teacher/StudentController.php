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
    public function edit($id)
    {
        $student = $this->studentForTeacher((int) $id);

        return response()->view('/admin/member/edit', [
            'user' => $student,
            'userprofile' => $student->userprofile,
        ]);
    }

    public function update(Request $request, $id)
    {
        $student = $this->studentForTeacher((int) $id);

        return parent::update($request, $student->name);
    }

    public function data($id)
    {
        $student = $this->studentForTeacher((int) $id);

        return response()->json(parent::editStudent($student->name));
    }

    public function validation(UserProfileUpdateRequest $request, $id)
    {
        $student = $this->studentForTeacher((int) $id);

        return parent::editValidationUser($request, $student->name);
    }

    private function studentForTeacher(int $id): User
    {
        $teacher = Auth::user();
        $student = User::find($id);

        abort_unless(
            $student !== null
                && (int) $student->school_id === (int) $teacher->school_id
                && (int) $student->usergroup_id === 6,
            404
        );

        $academicYear = SiteHelper::getAcademicYear((int) $teacher->school_id);
        abort_unless(
            $academicYear !== null
            && app(RosterScopeService::class)->actorCanAccessStudent($teacher, $student, (int) $academicYear->id),
            403
        );

        return $student;
    }
}
