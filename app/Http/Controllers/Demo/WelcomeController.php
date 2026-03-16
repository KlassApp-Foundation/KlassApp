<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Demo;

use App\Http\Resources\Demo\Teacher as TeacherResource;
use App\Http\Resources\Demo\School as SchoolResource;
use App\Http\Resources\Demo\User as UserResource;
use App\Http\Controllers\Controller;
use App\Models\TeacherProfile;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Models\School;
use App\Models\User;

class WelcomeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function schoolList()
    {
        //
        $schoolList = School::where('status',1)->get()->take(3);
        
        $schoolList = SchoolResource::collection($schoolList);

        return $schoolList;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list($school_id)
    {
        //
        $array = [];

        $school = School::with('admin')->where([['id',$school_id],['status',1]])->first();

        $academic_year = SiteHelper::getAcademicYear($school->id);

        $principal =  TeacherProfile::where([['school_id',$school->id],['academic_year_id',$academic_year->id],['designation','principal']])->get();

        $teacher =  TeacherProfile::where([['school_id',$school->id],['academic_year_id',$academic_year->id],['designation','!=','principal'],['designation','!=','librarian'],['designation','!=','receptionist'],['designation','!=','accountant']])->take(3)->get();

        $details = $school->getDetails();

        $array['admin']         = UserResource::collection($details['admin']);
        $array['principal']     = TeacherResource::collection($principal);
        $array['teacher']       = TeacherResource::collection($teacher);
        $array['student']       = UserResource::collection($details['student']);
        $array['parent']        = UserResource::collection($details['parent']);
        $array['librarian']     = UserResource::collection($details['librarian']);
        $array['receptionist']  = UserResource::collection($details['receptionist']);
        $array['accountant']    = UserResource::collection($details['accountant']);

        return $array;
    }
}