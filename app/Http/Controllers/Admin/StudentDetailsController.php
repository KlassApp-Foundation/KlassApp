<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Resources\AttendanceUser as AttendanceUserResource;
use App\Http\Resources\API\BookLending as BookLendingResource;
use App\Http\Resources\UserRelation as UserRelationResource;
use App\Http\Resources\UserSibling as UserSiblingResource;
use App\Http\Resources\ActivityLog as ActivityLogResource;
use App\Http\Resources\UserDetail as UserDetailResource;
use App\Http\Resources\Discipline as DisciplineResource;
use App\Http\Resources\UserFees as UserFeesResource;
use App\Http\Resources\User as UserResource;
use App\Http\Resources\SiblingListResource;
use App\Http\Requests\MedicalHistoryRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentParentLink;
use App\Models\StudentAcademic;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Traits\LogActivity;
use App\Models\ActivityLog;
use App\Models\FeePayment;
use App\Traits\Common;
use App\Models\User;
use App\Models\Fee;
use Exception;
use Log;

class StudentDetailsController extends Controller
{
    //
    use LogActivity;
    use Common;


    /**
     * Resolve a student by route name within the actor's school.
     * Bare users.name is not globally unique (PR #517 / AGENTS.md rule 18).
     *
     * @param  list<string>  $with
     */
    private function findSchoolStudentByName(string $name, array $with = []): User
    {
        $actor = Auth::user();

        if ($actor === null) {
            abort(403);
        }

        $query = User::query()->exactNameInSchool($name, (int) $actor->school_id, 6);

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
     * @param  list<string>  $with
     */
    private function findSchoolStudentMemberOrAbort(string $name, array $with = []): User
    {
        $user = $this->findSchoolStudentByName($name, $with);

        if (! Gate::allows('member', $user)) {
            abort(403);
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
        $users = collect([$this->findSchoolStudentByName($name, ['userprofile'])]);

        $users = UserDetailResource::collection($users);

        return $users;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showRelations($name)
    {
        //
        $student = $this->findSchoolStudentByName($name, ['userprofile']);

        $parents = UserRelationResource::collection($student->parents);

        return $parents;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showSiblings($name)
    {
        //

        $student = $this->findSchoolStudentByName($name, ['userprofile']);
        $parents=StudentParentLink::where('student_id',$student->id)->pluck('parent_id')->toArray();
       $siblings=StudentParentLink::where('student_id','!=',$student->id)->whereIn('parent_id',$parents)->get()->unique('student_id');

        $siblings = SiblingListResource::collection($siblings);
        return $siblings;
    }

    public function showActivity($name)
    {
        //
        $user = $this->findSchoolStudentByName($name, ['userprofile']);
        if(Gate::allows('member',$user))
        {
            $activitylog = ActivityLog::whereIn('subject_id', array_values(array_filter([optional($user->userprofile)->id, data_get($user->members->first(), 'id')])))->paginate(5);

            $activitylog = ActivityLogResource::collection($activitylog);

            return $activitylog;
        }
        else
        {
            abort(403);
        }
    }

    public function showActivityLog($name)
    {
        //
        $user = $this->findSchoolStudentByName($name, ['userprofile']);
        if(Gate::allows('member',$user))
        {
            $activitylog = ActivityLog::whereIn('causer_id', array_values(array_filter([optional($user->userprofile)->id, data_get($user->members->first(), 'id')])))->paginate(5);

            $activitylog = ActivityLogResource::collection($activitylog);

            return $activitylog;
        }
        else
        {
            abort(403);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showDisciplines($name)
    {
        //
        $student = $this->findSchoolStudentByName($name, ['disciplineUser', 'disciplineTeacher']);

        $discipline = DisciplineResource::collection($student->disciplineUser);

        return $discipline;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showAttendance($name)
    {
        //
        $student = $this->findSchoolStudentByName($name);

        $attendances = AttendanceUserResource::collection($student->AttendanceUserAbsent);

        return $attendances;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showMedicalHistory($name)
    {
        //
        $student = $this->findSchoolStudentByName($name);

        $studentacademic = StudentAcademic::where('user_id', $student->id)
            ->whereIn('academic_year_id', function ($query) {
                $query->select('id')
                    ->from('academic_years')
                    ->where('status', 1);
            })
            ->orderByDesc('id')
            ->first();

        $medicals = [];

        $medicals['medication_problems']        = $studentacademic->medication_problems == 'null' ? null:$studentacademic->medication_problems;
        $medicals['medication_needs']           = $studentacademic->medication_needs == 'null' ? null:$studentacademic->medication_needs;
        $medicals['medication_allergies']       = $studentacademic->medication_allergies == 'null' ? null:$student->studentAcademicLatest->medication_allergies;
        $medicals['food_allergies']             = $studentacademic->food_allergies == 'null' ? null:$studentacademic->food_allergies;
        $medicals['other_allergies']            = $studentacademic->other_allergies == 'null' ? null:$studentacademic->other_allergies;
        $medicals['other_medical_information']  = $studentacademic->other_medical_information == 'null' ? null:$studentacademic->other_medical_information;

        return $medicals;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showFees($name)
    {
        //
        $student = $this->findSchoolStudentByName($name);

        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);

        //new
        if(class_exists('Gegok12\Fee\Models\Fee'))
        {

            $fees = \Gegok12\Fee\Models\Fee::where([['school_id',$school_id],['academic_year_id',$academic_year->id]])->where('standardLink_id',$student->studentAcademicLatest->standardLink_id)->orWhere('standardLink_id',null)->orderBy('start_date','DESC')->paginate(5);
        }
        else{
            $fees = Fee::where([['school_id',$school_id],['academic_year_id',$academic_year->id]])->where('standardLink_id',$student->studentAcademicLatest->standardLink_id)->orWhere('standardLink_id',null)->orderBy('start_date','DESC')->paginate(5);
        }

        if(class_exists('Gegok12\Fee\Http\Resources\UserFees'))
        {
            $feepayments = \Gegok12\Fee\Http\Resources\UserFees::collection($fees);
        }
        else
        {
            $feepayments = UserFeesResource::collection($fees);
        }


        return $feepayments;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function createMedicalHistory($name)
    {
        //
        $user = $this->findSchoolStudentByName($name);

        return view('/admin/member/create_medical_history' , ['user' => $user]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function addMedicalHistory(MedicalHistoryRequest $request,$name)
    {
        //
        try
        {
            $user = $this->findSchoolStudentByName($name);

            $studentacademic = StudentAcademic::where('id',$user->studentAcademicLatest->id)->orderBy('id','DESC')->first();

            $studentacademic->medication_problems        = $request->medication_problems == 'null' ? null:$request->medication_problems;
            $studentacademic->medication_needs           = $request->medication_needs == 'null' ? null:$request->medication_needs;
            $studentacademic->medication_allergies       = $request->medication_allergies == 'null' ? null:$request->medication_allergies;
            $studentacademic->food_allergies             = $request->food_allergies == 'null' ? null:$request->food_allergies;
            $studentacademic->other_allergies            = $request->other_allergies == 'null' ? null:$request->other_allergies;
            $studentacademic->other_medical_information  = $request->other_medical_information == 'null' ? null:$request->other_medical_information;

            $studentacademic->save();

            $message = trans('messages.update_success_msg',['module' => 'Student Medical History']);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
              $studentacademic,
              Auth::user(),
              ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
              LOGNAME_EDIT_STUDENT_MEDICAL_HISTORY,
              $message
            );

            $res['success'] = $message;
            return $res;
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    public function showBookLent($name)
    {
        //
        $student = $this->findSchoolStudentByName($name, ['lending']);

        $lent = BookLendingResource::collection($student->lending);

        return $lent;
    }

    public function show($name)
    {
        $user = $this->findSchoolStudentMemberOrAbort($name, ['studentAcademicLatest']);
        $parents = $user->parent;

        // Direct visits / bookmarks send no Referer header; $_SERVER['HTTP_REFERER'] is then undefined.
        $prev_url = request()->headers->get('referer') ?: url('/admin/students');

        return view('/admin/member/show', ['user' => $user, 'parents' => $parents, 'prev_url' => $prev_url]);
    }

    /**
     * Old admin bookmarks use the student name. Send them to the id profile
     * when that name belongs to exactly one student in this school.
     */
    public function redirectToIdProfile(string $name)
    {
        $actor = Auth::user();
        if ($actor === null) {
            abort(403);
        }

        $matches = User::query()
            ->exactNameInSchool($name, (int) $actor->school_id, 6)
            ->get();

        if ($matches->count() !== 1) {
            abort(404);
        }

        $user = $matches->first();
        if (! Gate::allows('member', $user)) {
            abort(403);
        }

        return redirect('/admin/students/'.$user->id, 302);
    }
}
