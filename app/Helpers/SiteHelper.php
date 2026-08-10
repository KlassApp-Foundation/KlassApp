<?php

namespace App\Helpers;

use App\Http\Resources\AssignmentTeacher as AssignmentTeacherResource;
use App\Http\Resources\API\NonScholastic as NonScholasticResource;
use App\Http\Resources\TeacherDetail as TeacherDetailResource;
use App\Http\Resources\StandardLink as StandardLinkResource;
use App\Http\Resources\API\Scholastic as ScholasticResource;
use App\Http\Resources\TeacherLink as TeacherLinkResource;
use App\Http\Resources\API\Country as CountryResource;
use App\Http\Resources\Standard as StandardResource;
use App\Http\Resources\API\City as CityResource;
use App\Models\TeacherLeaveApplication;
use Illuminate\Support\Facades\Cache;
use App\Models\TeacherProfile;
use App\Models\Qualification;
use App\Models\NonScholastic;
use App\Models\StandardLink;
use App\Models\AcademicYear;
use App\Models\Teacherlink;
use App\Models\Scholastic;
use App\Models\Standard;
use App\Models\Country;
use App\Models\City;
use App\Models\User;

class SiteHelper
{   
    public static function getAcademicYear($school_id)
    {
        $schoolCacheKey = "academic_year_for_school_".$school_id;
        return Cache::remember( $schoolCacheKey, env('CACHE_TIME'), function () use ($school_id)  {
            // Temporary viewing override (NavigationController) — specific year by id.
            if (Cache::has('academic_year') && Cache::get('academic_year') != '') {
                return AcademicYear::where('school_id', $school_id)
                    ->where('id', Cache::get('academic_year'))
                    ->first();
            }

            // status=1 is the real "current" flag (see AcademicYearController::updateStatus
            // and AcademicYear::getStatusDisplayAttribute). Description is free text —
            // never use it as the primary signal (wizard / admin forms allow custom copy).
            $current = AcademicYear::where('school_id', $school_id)
                ->where('status', 1)
                ->orderByDesc('id')
                ->first();

            if ($current !== null) {
                return $current;
            }

            // Legacy fallback for rows that only marked current via description.
            return AcademicYear::where('school_id', $school_id)
                ->where('description', 'Current Academic Year')
                ->orderByDesc('id')
                ->first();
        });
    }

    public static function getAdmin($school_id)
    {
        $schoolCacheKey = "admin".$school_id;
        return Cache::remember( $schoolCacheKey, env('CACHE_TIME'), function () use ($school_id)  {
            $academic_year = SiteHelper::getAcademicYear($school_id);
            //return User::where([['school_id',$school_id],['academic_year_id',$academic_year->id]])->ByRole(3)->first();
            return User::where('school_id',$school_id)->ByRole(3)->first();
        });
    }

    public static function getCountries()
    {
        return Cache::remember( "countries", env('CACHE_TIME'), function ()  {
            $country = Country::all();
            return CountryResource::collection($country)->keyby('id');
        });
    }

    public static function getCities()
    {
        return Cache::remember( "cities", env('CACHE_TIME'), function ()  {
            $city  = City::get();
            return CityResource::collection($city)->groupby('country_id');
        });
    }

    public static function getQualifications()
    {
        return Cache::remember( "qualifications", env('CACHE_TIME'), function ()  {
            return Qualification::where([['status',1],['type','others']])->orWhere('type','pg')->orWhere('type','ug')->orderBy('id','DESC')->get();
        });
    }

    public static function getAdditionalCertificates()
    {
        return Cache::remember( "additionalcertificates", env('CACHE_TIME'), function ()  {
            return Qualification::where([['status',1],['type','teacher']])->orWhere('type','others')->orderBy('id','DESC')->get();
        });
    }

    public static function getUGList()
    {
        return Cache::remember( "UGList", env('CACHE_TIME'), function ()  {
            return Qualification::where([['status',1],['type','ug']])->get();
        });
    }

    public static function getPGList()
    {
        return Cache::remember( "PGList", env('CACHE_TIME'), function ()  {
            return Qualification::where([['status',1],['type','pg']])->get();
        });
    }

    public static function getStandardLinkList($school_id)
    {
        $academic_year = SiteHelper::getAcademicYear($school_id);
        $academic_year_id = $academic_year ? $academic_year->id : 'none';
        $standardLinkCacheKey = 'standardLink'.$school_id.'_'.$academic_year_id;
        return Cache::remember( $standardLinkCacheKey, env('CACHE_TIME'), function () use ($school_id,$academic_year)  {
            if (!$academic_year) return collect();
            $standards = Standard::where('school_id',$school_id)->orderBy('order')->pluck('id')->toArray();
            if(count($standards) > 0)
            {
                $standard = implode(' ,',$standards);
                $standardLink = StandardLink::where([['school_id',$school_id],['academic_year_id',$academic_year->id]])
                    ->orderByRaw('FIELD(standard_id,'.$standard.')')
                    ->orderBy('section_id')
                    ->get()
                    ->unique(function($item) {
                        return $item->standard_id.'-'.$item->section_id;
                    });
                return StandardLinkResource::collection($standardLink);
            }
            return collect();
        });
    }

    public static function getStandardList($school_id)
    {
        $standardCacheKey = 'standard_'.$school_id;
        return Cache::remember( $standardCacheKey, env('CACHE_TIME'), function () use ($school_id)  {
            $standards = Standard::where('school_id',$school_id)->orderBy('order')->get();
            if(count($standards) > 0)
            {
                return StandardResource::collection($standards);
            }
        });
    }

    public static function getTeachingDesignations()
    {
        return Cache::remember( "teaching_designations", env('CACHE_TIME'), function () {
            $name = array('Principal','Vice Principal','Head Of The Department','Senior Teacher','Assistant Teacher','Teacher','Co-ordinator','Physical Education Teacher','Others');
            $id = array('principal','vice_principal','head_of_the_department','senior_teacher','assistant_teacher','teacher','co_ordinator','physical_education_teacher','others');
            for($i = 1 ; $i <= count($name) ; $i++)
            {
                $array[$i]['id']    = $id[$i-1];
                $array[$i]['name']  = $name[$i-1];
            }
            
            return $array;
        });
    }

    public static function getNonTeachingDesignations()
    {
        return Cache::remember( "non_teaching_designations", env('CACHE_TIME'), function () {
              $name = array('Accountant','Receptionist','Librarian','Lab Assistant','Clerk','Stock Keeper','Peon','Driver','Helpers','Security','Transport Coordinator','Others');
            $id = array('accountant','receptionist','librarian','lab_assistant','clerk','stock_keeper','peon','driver','helpers','security','transport_coordinator','others');


            for($i = 1 ; $i <= count($name) ; $i++)
            {
                $array[$i]['id']    = $id[$i-1];
                $array[$i]['name']  = $name[$i-1];
            }
            
            return $array;
        });
    }

    public static function getBloodGroups()
    {
        $array = [];

        $list_id = array('a+','a1+','b+','b1+','o+','ab+','a1b+','a-','a1-','b-','b1-','o-','ab-','a1b-');
        $list_name = array('A+','A1+','B+','B1+','O+','AB+','A1B+','A-','A1-','B-','B1-','O-','AB-','A1B-');

        return Cache::remember( "blood_groups", env('CACHE_TIME'), function () use($list_id,$list_name) {
            for($i = 1 ; $i <= count($list_name) ; $i++)
            {
                $array[$i]['num'] = $list_id[$i-1];
                $array[$i]['name'] = $list_name[$i-1];
            }
            return $array;
        });
    }

    public static function getMaritalList()
    {
        $array = [];

        $list_id = array('single','married','divorced','widowed');
        $list_name = array('Single','Married','Divorced','Widowed');

        return Cache::remember( "marital_list", env('CACHE_TIME'), function () use($list_id,$list_name) {

            for($i = 1 ; $i <= count($list_name) ; $i++)
            {
                $array[$i]['id'] = $list_id[$i-1];
                $array[$i]['name'] = $list_name[$i-1];
            }
            return $array;
        });
    }

    public static function getCasteList()
    {
        $array = [];

        $list_id = array('BC','BCM','FC','MBC','OBC','Others','SC','SCA','ST');
        $list_name = array('BC','BCM','FC','MBC','OBC','Others','SC','SCA','ST');


        return Cache::remember( "caste_list", env('CACHE_TIME'), function () use($list_id,$list_name) {

            for($i = 1 ; $i <= count($list_name) ; $i++)
            {
                $array[$i]['id'] = $list_id[$i-1];
                $array[$i]['name'] = $list_name[$i-1];
            }
            return $array;
        });
    }

    public static function getTransportList()
    {
        $array = [];


        $list_id = array('auto','car','city_bus','cycle','rickshaw','school_bus','taxi','walking');
        $list_name = array('Auto','Car','City Bus','Cycle','Rickshaw','School Bus','Taxi','Walking');

        return Cache::remember( "transport_list", env('CACHE_TIME'), function () use($list_id,$list_name) {

            for($i = 1 ; $i <= count($list_name) ; $i++)
            {
                $array[$i]['id'] = $list_id[$i-1];
                $array[$i]['name'] = $list_name[$i-1];
            }
            return $array;
        });
    }

    public static function getClassStudentCount($school_id,$academic_year_id,$standardLink_id)
    {
        $key = "class_student_count".$standardLink_id;
        return Cache::remember( $key, env('CACHE_TIME'), function () use ($school_id,$academic_year_id,$standardLink_id) {
            return User::where([['status','active']])->ByRole(6)->whereHas('studentAcademic',function($query) use ($school_id,$academic_year_id,$standardLink_id)
            {
                $query->where([
                    ['school_id',$school_id],
                    ['academic_year_id',$academic_year_id],
                    ['standardLink_id',$standardLink_id]
                ]);
            })->count();
        });
    }

    public static function getHODList($school_id)
    {
        $academic_year = SiteHelper::getAcademicYear($school_id);
        $key = "hod_list_".$school_id.'_'.$academic_year->id;
        return Cache::remember( $key, env('CACHE_TIME'), function () use ($school_id,$academic_year) {
            $users = User::ByRole(5)->where('status','active')->whereHas('teacherprofile',function($query) use ($school_id,$academic_year)
            {
                $query->where([
                    ['school_id',$school_id],
                    ['academic_year_id',$academic_year->id],
                    ['designation','head_of_the_department']
                ]);
            })->get();
            $teachers = TeacherDetailResource::collection($users);
            return $teachers;
        });
    }

    public static function getPrincipalList($school_id)
    {
        $academic_year = SiteHelper::getAcademicYear($school_id);
        $key = "principal_list_".$school_id.'_'.$academic_year->id;
        return Cache::remember( $key, env('CACHE_TIME'), function () use ($school_id,$academic_year) {
            $users = User::ByRole(5)->where('status','active')->whereHas('teacherprofile',function($query) use ($school_id,$academic_year)
            {
                $query->where([
                    ['school_id',$school_id],
                    ['academic_year_id',$academic_year->id],
                    ['designation','principal']
                ])->orWhere('designation','vice_principal');
            })->get();
            $teachers = TeacherDetailResource::collection($users);
            return $teachers;
        });
    }

    public static function getTeachingStaffList($school_id,$academic_year_id)
    {
        $key = "teaching_staff_lists_".$school_id.'_'.$academic_year_id;
        return Cache::remember( $key, env('CACHE_TIME'), function () use ($school_id,$academic_year_id) {
            return User::where('usergroup_id',5)->where('status','active')->whereHas('teacherprofile',function($query) use ($school_id,$academic_year_id)
            {
                $query->where([
                    ['school_id',$school_id],
                    ['academic_year_id',$academic_year_id]
                ]);
            })->get()->sortBy('userprofile.firstname');
        });
    }


    public static function getStandardSubjectList($school_id,$teacher_id)
    {
        $academic_year = SiteHelper::getAcademicYear($school_id);
        //$key = "standard_subject_list_".$school_id.'_'.$academic_year->id.'_'.$teacher_id;
        //return Cache::remember( $key, env('CACHE_TIME'), function () use ($school_id,$academic_year,$teacher_id) {

        $standardLinks = StandardLink::where([
                ['school_id',$school_id],
                ['academic_year_id',$academic_year->id],
                ['class_teacher_id',$teacher_id]
            ])->pluck('id')->toArray();

        $teacherlinks = Teacherlink::where([
            ['school_id',$school_id],
            ['academic_year_id',$academic_year->id],
            ['teacher_id',$teacher_id]
        ])->pluck('standardLink_id')->toArray();

        $standards = array_merge($standardLinks,$teacherlinks);

        $standardLink = StandardLink::whereIn('id',$standards)->get();

        $standards = StandardLinkResource::collection($standardLink);

            $teacherLink = Teacherlink::where([
                ['school_id',$school_id],
                ['academic_year_id',$academic_year->id],
                ['teacher_id',$teacher_id]
            ])->get();
            //$standardLinklist = AssignmentTeacherResource::collection($teacherLink);
            $subjectlist = TeacherLinkResource::collection($teacherLink)->groupBy('standardLink_id');

            $array = [];

            $array['standardLinklist']=$standards;
            $array['subjectlist']=$subjectlist;
            return $array;
       // });
    }

    public static function getFeedbackCategoryList()
    {
        $array = [];

        $list_id = array('feedback_or_bug_for_app_or_software','student_profile_or_info','complaints','suggestions','others');
        $list_name = array('Feedback / Bug For App / Software','Student Profile / Info','Complaints','Suggestions','Others');

        return Cache::remember( "feedback_category_list", env('CACHE_TIME'), function () use($list_id,$list_name) {

            for($i = 1 ; $i <= count($list_name) ; $i++)
            {
                $array[$i]['id'] = $list_id[$i-1];
                $array[$i]['name'] = $list_name[$i-1];
            }
            return $array;
        });
    }

    public static function getClassStudents($school_id,$academic_year_id,$standardLink_id)
    {
        //dd($standardLink_id);

        $key = "class_students_".$standardLink_id;
        return Cache::remember( $key, env('CACHE_TIME'), function () use ($school_id,$academic_year_id,$standardLink_id) {
            return User::ByRole(6)->where('status','active')->whereHas('studentAcademic',function($query) use ($school_id,$academic_year_id,$standardLink_id)
            {
                $query->where([
                    ['school_id',$school_id],
                    ['academic_year_id',$academic_year_id],
                    ['standardLink_id',$standardLink_id]
                ]);
            })->get()->sortBy('userprofile.firstname');
        });
    }

    public static function getTeachers($school_id,$academic_year_id)
    {
        $key = "teacher_lists_".$school_id.'_'.$academic_year_id;
        return Cache::remember( $key, env('CACHE_TIME'), function () use ($school_id,$academic_year_id) {
            return User::whereIn('usergroup_id',[5,8,10,11,12])->where('status','active')->whereHas('teacherprofile',function($query) use ($school_id,$academic_year_id)
            {
                $query->where([
                    ['school_id',$school_id],
                    ['academic_year_id',$academic_year_id]
                ]);
            })->get()->sortBy('userprofile.firstname');
        });
    }

    public static function getPendingLeaveCount($school_id,$academic_year_id,$teacher_id)
    {
        $key = "pending_leave_count_".$school_id.'_'.$academic_year_id.'_'.$teacher_id;
        return Cache::remember( $key, env('CACHE_TIME'), function () use ($school_id,$academic_year_id,$teacher_id) {
            $teacherprofiles = TeacherProfile::where([
                ['school_id',$school_id],
                ['academic_year_id',$academic_year_id],
                ['reporting_to',$teacher_id]
            ])->pluck('user_id')->toArray();

            $pending_count = TeacherLeaveApplication::where([
                ['school_id',$school_id],
                ['academic_year_id',$academic_year_id],
                ['status','pending']
            ])->whereIn('user_id',$teacherprofiles)->count();

            return $pending_count;
        });
    }

    public static function getScholasticList($school_id)
    {
        $key = "scholastic_list_".$school_id;
        return Cache::remember( $key, env('CACHE_TIME'), function () use ($school_id) {
            $sc_grade = Scholastic::where('school_id',$school_id)->get();
            return ScholasticResource::collection($sc_grade);
        });
    }

    public static function getNonScholasticList($school_id)
    {
        $key = "non_scholastic_list_".$school_id;
        return Cache::remember( $key, env('CACHE_TIME'), function () use ($school_id) {
            $non_sc_grade = NonScholastic::where('school_id',$school_id)->get();
            return NonScholasticResource::collection($non_sc_grade);
        });
    }

    public static function getTaskAssigneeList()
    {
        $array = [];

        $list_id = array('class','self','student','teacher');
        $list_name = array('Class','Self','Student','Teachers');

        return Cache::remember( "task_assignee_list", env('CACHE_TIME'), function () use($list_id,$list_name) {
            for($i = 0 ; $i < count($list_name) ; $i++)
            {
                $array[$i]['id'] = $list_id[$i];
                $array[$i]['name'] = $list_name[$i];
            }
            return $array;
        });
    }

    public static function getTaskReminderList()
    {
        $array = [];

        $list_id = array('one_hour_before_the_task','one_day_before_the_task','two_days_before_the_task');
        $list_name = array('One Hour Before The Task','One Day Before The Task','Two Days Before The Task');

        return Cache::remember( "task_reminder_list", env('CACHE_TIME'), function () use($list_id,$list_name) {
            for($i = 0 ; $i < count($list_name) ; $i++)
            {
                $array[$i]['id'] = $list_id[$i];
                $array[$i]['name'] = $list_name[$i];
            }
            return $array;
        });
    }

    public static function getAcademicYearStatusList()
    {
        $array = [];

        $list_id = array('current','new','old');
        $list_name = array('Current Academic Year','New Academic Year','Old Academic Year');

        return Cache::remember( "academic_year_status_list", env('CACHE_TIME'), function () use($list_id,$list_name) {
            for($i = 0 ; $i < count($list_name) ; $i++)
            {
                $array[$i]['id'] = $list_id[$i];
                $array[$i]['name'] = $list_name[$i];
            }
            return $array;
        });
    }

    public static function getClasCooridnators($school_id,$auth_id)
    {
        return User::where([['id','!=',$auth_id],['school_id',$school_id],['status','active']])->whereHas(
            'roles', function($q){
                $q->where('name', 'class_coordinator');
            }
        );

       
    }
}