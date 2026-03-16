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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showDetails($name)
    {
      //
      $users = User::with('standardLink')->where('name', $name)->get();
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
      $users = User::with('teacherlink')->where('name', $name)->get();
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
      $user = User::with('teacherlink')->where('name', $name)->first();
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
      $user = User::with('standardLink')->where('name', $name)->first();
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
      $user = User::where('name', $name)->first();
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
      $user = User::with('userprofile')->where('name', $name)->first();
      $activitylog = ActivityLog::where('subject_id',$user->userprofile->id)->orWhere('subject_id',$user->members[0]['id'])->paginate(5);
      $activitylog = ActivityLogResource::collection($activitylog);
         
      return $activitylog;
    }

     public function showActivityLog($name)
    {
      //
      $user = User::with('userprofile')->where('name', $name)->first();
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
      //
      $user = User::where('name',$name)->first(); 

      return view('/admin/teacher/show',['user' => $user]);
    }
}