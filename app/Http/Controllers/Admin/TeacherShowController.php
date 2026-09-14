<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Resources\TeacherTimeTable as TeacherTimeTableResource;
use App\Http\Resources\TeacherClasses as TeacherClassesResource;
use App\Http\Resources\TeacherDetail as TeacherDetailResource;
use App\Http\Resources\LeaveHistory as LeaveHistoryResource;
use App\Http\Resources\ActivityLog as ActivityLogResource;
use App\Models\TeacherLeaveApplication;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Helpers\SiteHelper;
use App\Models\User;

class TeacherShowController extends Controller
{

    /**
     * @param  list<string>  $with
     */
    private function findSchoolTeacherByName(string $name, array $with = []): User
    {
        $actor = Auth::user();
        if ($actor === null) {
            abort(403);
        }

        $query = User::query()->exactNameInSchool($name, (int) $actor->school_id, 5);
        if ($with !== []) {
            $query->with($with);
        }
        $user = $query->first();
        if ($user === null) {
            abort(404);
        }
        return $user;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showDetails($name)
    {
      //
      $users = collect([$this->findSchoolTeacherByName($name, ['standardLink'])]);
      $users = TeacherDetailResource::collection($users);

      return $users;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showTimetable($name)
    {
      //
      $users = collect([$this->findSchoolTeacherByName($name, ['teacherlink'])]);
      if( count($users[0]['teacherlink']) > 0)
      {
        $users = TeacherTimeTableResource::collection($users);
      }
      else
      {
        $users = null;
      }
      return $users;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showClasses($name)
    {
      //
      $user = $this->findSchoolTeacherByName($name, ['teacherlink']);
      $users = TeacherClassesResource::collection($user->teacherlink);

      return $users;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showClassTeacher($name)
    {
      //
      $user = $this->findSchoolTeacherByName($name, ['standardLink']);
      $array['standard']  = $user->standardLink->StandardName;
      $array['section']   = $user->standardLink->section->name;

      return $array;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showLeaveHistory($name)
    {
      //
      $user = $this->findSchoolTeacherByName($name);
      $school_id = Auth::user()->school_id;
      $academic_year = SiteHelper::getAcademicYear($school_id);
      $leave = TeacherLeaveApplication::where([
                ['user_id',$user->id],
                ['school_id',$school_id],
                ['academic_year_id',$academic_year->id]
              ])->paginate(5);
      $leave = LeaveHistoryResource::collection($leave);

      return $leave;
    }

    public function showActivity($name)
    {
      //
      $user = $this->findSchoolTeacherByName($name, ['userprofile']);
      $activitylog = ActivityLog::where('subject_id',$user->userprofile->id)->orWhere('subject_id',$user->members[0]['id'])->paginate(5);
      $activitylog = ActivityLogResource::collection($activitylog);

      return $activitylog;
    }

     public function showActivityLog($name)
    {
      //
      $user = $this->findSchoolTeacherByName($name, ['userprofile']);
      $activitylog = ActivityLog::where('causer_id',$user->userprofile->id)->orWhere('causer_id',$user->members[0]['id'])->paginate(5);
      $activitylog = ActivityLogResource::collection($activitylog);

      return $activitylog;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($name)
    {
      // Prefer username slug; fall back to numeric id when users.name is null
      // (avoids /admin/teacher/show/null from JS string concat).
      $actor = Auth::user();
      if ($actor === null) {
          abort(403);
      }
      $schoolId = (int) $actor->school_id;
      $user = User::findByExactNameInSchool($name, $schoolId, 5);

      if ($user === null && ctype_digit((string) $name)) {
          $user = User::query()
              ->where('id', (int) $name)
              ->where('school_id', $schoolId)
              ->where('usergroup_id', 5)
              ->first();
      }

      if ($user === null) {
          abort(404);
      }

      return view('/admin/teacher/show',['user' => $user]);
    }
}
