<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserProfileUpdateRequest;
use App\Http\Requests\UserProfileAddRequest;
use App\Http\Controllers\Controller;
use App\Services\ToshiActionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\StudentParentLink;
use App\Models\Subscription;
use App\Traits\MemberProcess;
use App\Traits\RegisterUser;
use App\Models\StudentAcademic;
use App\Models\StandardLink;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Traits\LogActivity;
use App\Models\ActivityLog;
use App\Models\Userprofile;
use App\Models\Standard;
use App\Traits\Common;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    use RegisterUser;
    use MemberProcess;
    use LogActivity;
    use Common;

    public function find(Request $request)
    {
        //
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);

        // When no filter is specified, return ALL students (don't default to lowest standard)
        if (count((array)$request->query()) == 0 && !$request->has('standard')) {
            $request->merge(['standard' => '']);
        }

        return $this->MemberFilter($request, Auth::user()->school_id, 6, 'active');
    }

    public function index(Request $request)
    {
        $school_id = Auth::user()->school_id;
        $standardLinks = SiteHelper::getStandardLinkList($school_id);

        // Subquery: latest student_academics per user
        $latestSa = DB::table('student_academics as sa')
            ->select('sa.id', 'sa.user_id', 'sa.standardLink_id')
            ->whereIn('sa.academic_year_id', function ($q) {
                $q->select('id')->from('academic_years')->where('status', 1);
            })
            ->whereNull('sa.deleted_at')
            ->orderByDesc('sa.id');

        $query = User::where('users.school_id', $school_id)
            ->where('users.usergroup_id', 6)
            ->whereNull('users.deleted_at')
            ->leftJoin(DB::raw("({$latestSa->toSql()}) as latest_sa"), 'users.id', '=', 'latest_sa.user_id')
            ->addBinding($latestSa->getBindings(), 'join')
            ->leftJoin('standards_link', 'latest_sa.standardLink_id', '=', 'standards_link.id')
            ->leftJoin('sections', 'standards_link.section_id', '=', 'sections.id')
            ->select('users.*', 'sections.name as class_name');

        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('userprofile', function ($uq) use ($search) {
                    $uq->where('firstname', 'like', "%{$search}%")
                       ->orWhere('lastname', 'like', "%{$search}%");
                });
            });
        }

        $standardFilter = $request->input('standard');
        if ($standardFilter) {
            $selectedLink = StandardLink::find($standardFilter);
            if ($selectedLink) {
                // A class can have multiple stream links (e.g. East/West) —
                // filter by standard + section so every stream is included.
                $query->where('standards_link.standard_id', $selectedLink->standard_id)
                      ->where('standards_link.section_id', $selectedLink->section_id);
            }
        }

        $streamFilter = $request->input('stream');
        if ($streamFilter) {
            $query->where('standards_link.stream', $streamFilter);
        }

        $statusFilter = $request->input('status');
        if ($statusFilter) {
            if ($statusFilter === 'active') {
                $query->where('users.status', 'active');
            } elseif ($statusFilter === 'inactive') {
                $query->where('users.status', '!=', 'active');
            }
        } else {
            // Default view: only currently-active students. The broad
            // `!= 'exit'` filter previously included `status='inactive'`
            // junk records (flagged by the 2026_08_12 cleanup migration).
            // Use a positive `= 'active'` filter so inactive/exit are
            // excluded by default; admins can still explicitly request
            // `?status=inactive` above to audit the flagged junk rows.
            $query->where('users.status', 'active');
        }

        $students = $query->with([
            'parents.userParent.userprofile',
            'userprofile',
        ])->orderBy(Userprofile::select('firstname')
            ->whereColumn('user_id', 'users.id')
            ->limit(1)
        )->paginate(25)->withQueryString();

        $count = $students->total();

        return view('/admin/member/index', [
            'students' => $students,
            'count' => $count,
            'standardLinks' => $standardLinks,
            'search' => $search,
            'standardFilter' => $standardFilter,
            'streamFilter' => $streamFilter,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      //
      $count    = User::where('school_id',Auth::user()->school_id)->where('usergroup_id',6)->count();
      $subscription = Subscription::with('plan')->where('school_id',Auth::user()->school_id)->first();
      return view('/admin/member/create',['count'=>$count , 'subscription'=>$subscription]);
    }

    public function member()
    {
      $academic_year  = SiteHelper::getAcademicYear(Auth::user()->school_id);

      $array = [];

      $array['academic_year_id']  =   $academic_year->id;
      $array['countrylist']       =   SiteHelper::getCountries();
      $array['citylist']          =   SiteHelper::getCities();
      $array['standardLinklist']  =   SiteHelper::getStandardLinkList(Auth::user()->school_id);
      $array['blood_groups']      =   SiteHelper::getBloodGroups();
      $array['castelist']         =   SiteHelper::getCasteList();
      $array['transportlist']     =   SiteHelper::getTransportList();
      $array['date_of_birth']     =   date('Y-m-d',strtotime('-4 years',strtotime(date('Y'))));
      $array['joining_date']      =   date('Y-m-d');

      return $array;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validationUser(UserProfileAddRequest $request)
    {
      //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      //
      try
      {
        $school_id = Auth::user()->school_id;

        // ── Plan limit check (uses shared enforcePlanLimit on CurrentPlan) ──
        $limit = ToshiActionService::enforcePlanLimit($school_id, 'students');
        if (!$limit['success']) {
            return redirect()->back()->withErrors(['plan_limit' => $limit['message']]);
        }

        $academic_year = SiteHelper::getAcademicYear($school_id);

        $file = $request->file('avatar');
        if($file)
        {
          $folder=Auth::user()->school->slug.'/student/avatar';
          $path = $this->uploadFile($folder,$file);
        }
        else
        {
          $path = '';
        }

        $user = $this->CreateUser($request , $school_id , $academic_year->id , $path , 6);
        $mes = trans('messages.add_success_msg',['module' => 'Student']);
        $ip= $this->getRequestIP();
        if(!$user){
          throw new \Exception("User createion failed");
          }
          $this->doActivityLog(
          $user,
          Auth::user(),
          ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
          LOGNAME_ADD_STUDENT,
          $mes
        );

        // create class student from here
        return redirect()->back()->with('successmessage',$mes);
      }
      catch(Exception $e)
      {
        // Log::error($e);
        // return back()->with("errormessage", $e->getMessage());
        dd($e->getMessage());
      }
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editStudent($name)
    {
      //
      $user             = User::where('name',$name)->first();
      $userprofile      = Userprofile::where('user_id',$user->id)->first();
      $studentAcademic  = $user->studentAcademicLatest;

      $array = [];

      $array['firstname']                 = $userprofile->firstname;
      $array['lastname']                  = $userprofile->lastname;
      $array['date_of_birth']             = date('Y-m-d',strtotime($userprofile->date_of_birth));
      $array['gender']                    = $userprofile->gender;
      $array['blood_group']               = $userprofile->blood_group;
      $array['aadhar_number']             = $userprofile->aadhar_number==NULL ? '':$userprofile->aadhar_number;
      $array['city_id']                   = $userprofile->city_id;
      $array['country_id']                = $userprofile->country_id;
      $array['pincode']                   = $userprofile->pincode==NULL ? '':$userprofile->pincode;
      $array['birth_place']               = $userprofile->birth_place;
      $array['native_place']              = $userprofile->native_place;
      $array['mother_tongue']             = $userprofile->mother_tongue;
      $array['caste']                     = $userprofile->caste;
      $array['sub_caste']                 = $userprofile->sub_caste;
      $array['avatar']                    = $userprofile->AvatarPath;
      $array['notes']                     = $userprofile->notes;
      $array['registration_number']       = $user->registration_number==NULL ? $userprofile->registration_number:$user->registration_number;
      $array['lin']               = $userprofile->lin==NULL ? '':$userprofile->lin;
      $array['joining_date']              = $userprofile->joining_date==NULL ? '':date('Y-m-d',strtotime($userprofile->joining_date));

      $array['standardLink_id']           = $studentAcademic->standardLink_id;
      $array['std_school_pay_number']               = $studentAcademic->std_school_pay_number==NULL ? '':$studentAcademic->std_school_pay_number;
      $array['klassapp_student_id']                 = $studentAcademic->klassapp_student_id==NULL ? '':$studentAcademic->klassapp_student_id;
      $array['school_student_id']          = $studentAcademic->school_student_id==NULL ? '':$studentAcademic->school_student_id;
      $array['board_registration_number'] = $studentAcademic->board_registration_number==NULL ? '':$studentAcademic->board_registration_number;
      $array['mode_of_transport']         = $studentAcademic->mode_of_transport;
      $array['driver_name']               = $studentAcademic->transport_details['driver_name'];
      $array['driver_contact_number']     = $studentAcademic->transport_details['driver_contact_number'];
      $array['siblings']                  = $studentAcademic->siblings;
      $array['siblings_count']            = $studentAcademic->siblings_count;

      for($i = 0 ; $i < $studentAcademic->siblings_count ; $i++)
      {
        $array['sibling_details'][$i]['sibling_relation']       = $studentAcademic->sibling_details[$i]['sibling_relation'];
        $array['sibling_details'][$i]['sibling_name']           = $studentAcademic->sibling_details[$i]['sibling_name'];
        $array['sibling_details'][$i]['sibling_date_of_birth']  = date('Y-m-d',strtotime($studentAcademic->sibling_details[$i]['sibling_date_of_birth']));
        $array['sibling_details'][$i]['sibling_standard']       = $studentAcademic->sibling_details[$i]['sibling_standard'];
      }

      $array['countrylist']       =   SiteHelper::getCountries();
      $array['citylist']          =   SiteHelper::getCities();
      $array['standardLinklist']  =   SiteHelper::getStandardLinkList(Auth::user()->school_id);
      $array['blood_groups']      =   SiteHelper::getBloodGroups();
      $array['castelist']         =   SiteHelper::getCasteList();
      $array['transportlist']     =   SiteHelper::getTransportList();
      $array['today']             =   date('Y-m-d');

      return $array;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($name)
    {
      //
      $user = User::where('name',$name)->first();
      $userprofile = Userprofile::where('user_id',$user->id)->first();
      if(Gate::allows('member',$user))
      {
        return view('/admin/member/edit',['user' => $user , 'userprofile' => $userprofile ]);
      }
      else
      {
        abort(403);
      }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function editValidationUser(UserProfileUpdateRequest $request,$name)
    {
      //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$name)
    {
      $school_id = Auth::user()->school_id;
      $user = User::where('name', $name)->where('school_id', $school_id)->firstOrFail();

      try
      {
        $userprofile = Userprofile::where('user_id',$user->id)->first();

        $academic_year = SiteHelper::getAcademicYear($school_id);

        if($request->hasFile('avatar'))
        {
          $file = $request->file('avatar');
          $folder=Auth::user()->school->slug.'/member/avatar';
          $path = $this->uploadFile($folder,$file);
        }
        else
        {
          $path= $userprofile->avatar;
        }

        $userprofile = $this->UpdateUser($request , $school_id , $academic_year->id ,$user->id , $path);

        $message=trans('messages.update_success_msg',['module' => 'Student']);

        $ip= $this->getRequestIP();
        $this->doActivityLog(
          $userprofile,
          Auth::user(),
          ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
          LOGNAME_EDIT_STUDENT,
          $message
        );
        \Session::put('successmessage',$message);
        return redirect()->back();
      }
      catch(Exception $e)
      {
        Log::error('StudentController@update failed: ' . $e->getMessage());
        \Session::put('errormessage', trans('messages.update_error_msg', ['module' => 'Student']));
        return redirect()->back();
      }
    }

    public function destroy($name)
    {
        $schoolId = Auth::user()->school_id;
        $user = User::with('userprofile')->where('name', $name)
            ->where('school_id', $schoolId)
            ->firstOrFail();

        \DB::beginTransaction();
        try
        {
            StudentAcademic::where('user_id', $user->id)
                ->where('school_id', $schoolId)
                ->delete();

            StudentParentLink::where('student_id', $user->id)
                ->delete();

            Userprofile::where('user_id', $user->id)
                ->delete();

            $user->delete();

            \DB::commit();

            $message = trans('messages.delete_success_msg', ['module' => 'Student']);

            $ip = $this->getRequestIP();
            $this->doActivityLog(
                $user,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_DELETE_STUDENT,
                $message
            );
            \Session::put('successmessage', $message);
            return redirect('/admin/students');
        }
        catch(Exception $e)
        {
            \DB::rollBack();
            Log::error('StudentController@destroy failed: ' . $e->getMessage());
            \Session::put('errormessage', trans('messages.delete_error_msg', ['module' => 'Student']));
            return redirect('/admin/students');
        }
    }

    public function blockedstudents()
    {
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);
        $count    = User::ByRole(6)->where([['school_id',$school_id],['status','inactive']])->where('deleted_at',NULL)->count();
        $alphabet = request('alphabet')?request('alphabet'):'';
        $query    = \Request::getQueryString();
        $standardLink = SiteHelper::getStandardLinkList($school_id);

        $lowest_standard = Standard::where('school_id',$school_id)->orderBy('order')->first();

        $birthday = null;
        if(count((array)\Request::getQueryString()) == 0)
        {
            $standard = StandardLink::where([['school_id',$school_id],['academic_year_id',$academic_year->id]])->first();
        }
        if(request('date_of_birth') != null)
        {
            $birthday = 'true';
        }
        if(request('standard') != null)
        {
            $selected_standard = request('standard');
        }
        else
        {
            $selected_standard = $standard->id;
        }

        return view('/admin/member/blockedstudents',[ 'alphabet' => $alphabet , 'query' => $query , 'count' => $count , 'standardLinks' => $standardLink , 'standard' => $standard->id , 'birthday' => $birthday , 'selected_standard' => $selected_standard ]);
    }
}
