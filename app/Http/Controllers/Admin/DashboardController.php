<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Resources\UnpaidFees as UnpaidFeesResource;
use App\Http\Resources\TaskCount as TaskCountResource;
use App\Http\Resources\ShowEvent as ShowEventResource;
use App\Http\Resources\FeeGroup as FeeGroupResource;
use App\Http\Resources\FeeList as FeeListResource;
use App\Http\Resources\Task as TaskResource;
use App\Http\Resources\Fee as FeeResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Events\SinglePushEvent;
use App\Models\StandardLink;
use Illuminate\Http\Request;
use App\Traits\LogActivity;
use App\Helpers\SiteHelper;
use App\Models\FeePayment;
use App\Models\CurrentPlan;
use App\Traits\Dashboard;
use App\Models\FeeGroup;
use App\Models\Events;
use App\Traits\Common;
use App\Models\Task;
use App\Models\User;
use App\Models\Fee;
use App\Models\Approval;
use App\States\Approval\Pending as PendingState;
use Exception;
use Log;

class DashboardController extends Controller 
{
    use LogActivity;
    use Dashboard;
    use Common;

    /**
    * Show the application dashboard.
    *
    * @return \Illuminate\Http\Response
    */
    public function index(Request $request) 
    {
        $admin_id  =   Auth::id();
        $school_id =   Auth::user()->school_id;

        $dashboard = $this->adminDashboard( $school_id, $admin_id );

        $standardLink = $request->standardLink_id
            ? StandardLink::where('id', $request->standardLink_id)->first()
            : null;
        
        $selected_teacher = $request->teacher_id
            ? User::where('id', $request->teacher_id)->first()
            : null;

        // ── Plan usage data for banner ──
        $plan = null;
        $planUsage = [];
        if ($school_id) {
            $currentPlan = CurrentPlan::with('plan')->where('school_id', $school_id)->first();
            if ($currentPlan && $currentPlan->plan) {
                $studentCount = User::where('school_id', $school_id)->where('usergroup_id', 6)->count();
                $teacherCount = User::where('school_id', $school_id)->where('usergroup_id', 5)->count();
                $plan = $currentPlan->plan;
                $planUsage = [
                    'students' => ['used' => $studentCount, 'limit' => $plan->no_of_students],
                    'teachers' => ['used' => $teacherCount, 'limit' => $plan->no_of_users],
                ];
            }
        }

        // ── Onboarding reminder for school admins ──
        $onboardingMissing = [];
        $onboardingSteps = [];
        if (Auth::user()->usergroup_id === 3 && $school_id) {
            $school = Auth::user()->school;

            // Free-tier milestone: once all content steps are done, auto-assign a
            // free plan so plan selection stays informational/non-blocking. Safe
            // for existing schools — see FreeTierPlanService for the guarantees.
            if ($school) {
                app(\App\Services\FreeTierPlanService::class)
                    ->assignIfEligible($school, Auth::id());
            }

            $onboardingMissing = \App\Helpers\OnboardingHelper::getMissingSteps($school_id, Auth::id());
            $onboardingSteps = $school
                ? \App\Services\OnboardingStepsService::incompleteSteps($school, Auth::id())
                : [];
        }

        $setupIncomplete = ! empty($dashboard['setupIncomplete'])
            || (Auth::user()->usergroup_id === 3 && ! empty($onboardingMissing));

        $openToshiOnboarding = $request->boolean('toshi_onboarding')
            || session()->pull('open_toshi_onboarding', false)
            || $setupIncomplete;

        $pendingApprovals = $school_id
            ? Approval::where('state', PendingState::class)
                ->whereHas('approvable', fn($q) => $q->where('school_id', $school_id))
                ->count()
            : 0;

        // ── Fee Collection Trend (default: monthly, last 6 periods) ──
        $trendPeriod = in_array($request->period, ['day', 'week', 'month']) ? $request->period : 'month';
        $feeTrend = $setupIncomplete
            ? ['labels' => [], 'values' => []]
            : $this->computeFeeTrend($school_id, $trendPeriod, 6);

        return view( '/admin/dashboard/dashboard', [
            'dashboard' => $dashboard,
            'standardLink' => $standardLink,
            'selected_teacher' => $selected_teacher,
            'pendingApprovals' => $pendingApprovals,
            'plan' => $plan,
            'planUsage' => $planUsage,
            'onboardingMissing' => $onboardingMissing,
            'onboardingSteps' => $onboardingSteps,
            'setupIncomplete' => $setupIncomplete,
            'openToshiOnboarding' => $openToshiOnboarding,
            'feeTrend' => $feeTrend,
            'trendPeriod' => $trendPeriod,
        ] );
    }

    public function list(Request $request,$task_flag)
    {
        //
        $tasks = Task::where([['school_id',Auth::user()->school_id],['user_id',Auth::id()],['task_status',0],['task_flag',$task_flag]])->ByType('to_me',Auth::id());

        if($request->q != null)
        {
            $tasks = $tasks->where('title','LIKE','%'.$request->q.'%');
        }
        $tasks = $tasks->get();

        $tasks = TaskResource::collection($tasks);

        return $tasks;    
    }

    public function listCount()
    {
        //
        $tasks = Task::where([['school_id',Auth::user()->school_id],['user_id',Auth::id()],['task_status',0]])->ByType('to_me',Auth::id())->get()->groupBy('Flag');

        foreach ($tasks as $key => $value) 
        {
            $tasks[$key] = count($value);
        }

        return $tasks;    
    }

    public function event()
    {
        $now = date( 'Y-m-d H:i:s' );

        $event = Events::where( 'school_id', Auth::user()->school_id )->where( 'start_date', '>=', $now )->orderBy( 'start_date', 'asc' )->take( 5 )->get();
        $event = ShowEventResource::collection( $event );

        return $event;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function structuralList()
    {
        //
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);
        $feelists = FeeGroup::where('school_id',$school_id)->whereHas('fee',function($query) use($school_id,$academic_year){
            $query->where([['school_id',$school_id],['academic_year_id',$academic_year->id]]);//,['fee_type','structural']
        })->get();

        $feelists = FeeGroupResource::collection($feelists);

        return $feelists;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showStructuralList(Request $request)
    {
        //
        try
        {
            $school_id = Auth::user()->school_id;
            $academic_year = SiteHelper::getAcademicYear($school_id);
            $feelists = FeeGroup::where('school_id',$school_id)->whereIn('id',$request->feegroup)->whereHas('fee',function($query) use($school_id,$academic_year){
                $query->where([['school_id',$school_id],['academic_year_id',$academic_year->id]]);//,['fee_type','structural']
            })->get();
            \Session::put('amount',0);

            $feelists = FeeListResource::collection($feelists);

            return $feelists;
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function unpaidList($status)
    {
        //
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);
        $fees = Fee::where([['school_id',$school_id],['academic_year_id',$academic_year->id]]);
        if($status != 'null')
        {
            $fees = $fees->where('fee_type',$status);
        }
        $fees = $fees->paginate(2);
        \Session::put('amount',0);
        $fees = FeeResource::collection($fees);
        /*$array['feelist'] = FeeResource::collection($fees);

        foreach ($fees as $fee) 
        {
            $paidfees  = FeePayment::where('fee_id',$fee->id)->where('status',1); 

            $unpaidfees  = FeePayment::where('fee_id',$fee->id)->where('status',0); 

            if($fee->standardLink_id != null)
            {
                $paidfees = $paidfees->whereHas('user',function($query) use($fee)
                { 
                    $query->whereHas('studentAcademicLatest',function($q) use($fee)
                    {
                        $q->where('standardLink_id',$fee->standardLink_id);
                    });
                });

                $unpaidfees = $unpaidfees->whereHas('user',function($query) use($fee)
                { 
                    $query->whereHas('studentAcademicLatest',function($q) use($fee)
                    {
                        $q->where('standardLink_id',$fee->standardLink_id);
                    });
                });
            }

            $paid[$fee->id] = $paidfees->pluck('user_id')->toArray();

            $unpaid[$fee->id] = $unpaidfees->pluck('user_id')->toArray();

            $students[$fee->id] = User::whereIn('id',array_diff($unpaid[$fee->id],$paid[$fee->id]));
                  
            $array['unpaidCount'][$fee->id] = $students[$fee->id]->count();
            //$array['unpaidStudents'][$fee->id] = UserResource::collection($students[$fee->id]->get());

            $array['paidCount'][$fee->id] = $paidfees->count();
            //$array['paidStudents'][$fee->id] = FeePaymentResource::collection($paidfees->get());
        }
        return $array;*/

        return $fees;
    }

    public function show($fee_id)
    {
        //
        return view('/admin/dashboard/unpaidfees', ['fee_id' => $fee_id]);
    }

    public function feeslist($fee_id)
    {
        $fees = Fee::where('id',$fee_id)->first();

        $unpaidfees  = FeePayment::where('fee_id',$fees->id)->where('status',0);

        if($fees->standardLink_id != null)
        {
            $unpaidfees  = $unpaidfees->whereHas('user',function($query) use($fees)
            { 
                $query->whereHas('studentAcademicLatest',function($q) use($fees)
                {
                    $q->where('standardLink_id',$fees->standardLink_id);
                });
            }); 
        }

        $unpaidfees = $unpaidfees->get();
        $array['unpaidCount'] = $unpaidfees->count();
        $array['unpaidList'] = UnpaidFeesResource::collection($unpaidfees);

        return $array;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendReminder(Request $request,$fee_id)
    {
        //
        try
        {
            $user = User::where('name',$request->name)->first();

            $feepayment = FeePayment::where('id',$fee_id)->first();

            foreach($user->parents as $parent)
            {
                $array=[];

                $array['school_id']  = Auth::user()->school_id;
                $array['user_id']    = $parent->userParent->id;
                $array['message']    = $feepayment->fee->name.' Fee Payment Is Pending.Last Date For Payment - '.date('d-m-Y',strtotime($feepayment->fee->end_date));
                $array['type']       = 'private message';

                event(new SinglePushEvent($array));
            }
            $message = trans('messages.send_fee_reminder_msg');

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $feepayment,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_SEND_FEEPAYMENT_REMINDER,
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
}