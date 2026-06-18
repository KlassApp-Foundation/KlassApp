<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Superadmin;


use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Auth;
use App\Events\SinglePushEvent;
use App\Models\StandardLink;

use App\Traits\LogActivity;
use App\Helpers\SiteHelper;
use App\Models\FeePayment;
use App\Traits\Dashboard;
use App\Models\FeeGroup;
use App\Models\Events;
use App\Traits\Common;
use App\Models\Task;
use App\Models\User;
use App\Models\School;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\WhatsAppUser;
use App\Models\MessageDeliveryLog;
use App\Models\Fee;
use Exception;
use Log;

class DashboardSuperController extends Controller 
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

        // dd(Auth::id()); exit;
        // //dd('test');
        
        // \Artisan::call('cache:clear');
        // \Artisan::call('view:clear');
        // \Artisan::call('config:clear');
        
        $admin_id  =   Auth::id();
        $school_id =   Auth::user()->school_id;

        $dashboard = $this->adminDashboard( $school_id, $admin_id );

        $standardLink = StandardLink::where('id',$request->standardLink_id)->first();

        $selected_teacher = User::where('id',$request->teacher_id)->first();

        /*
         * Platform-wide stats for the Super Admin dashboard.
         * These aggregate across all schools on the platform.
         */
        $stats = [
            'totalSchools'     => School::count(),
            'activeSchools'    => School::where('status', 1)->count(),
            'inactiveSchools'  => School::where('status', '!=', 1)->count(),

            'totalUsers'       => User::count(),
            'schoolAdmins'     => User::where('usergroup_id', 3)->count(),
            'teachers'         => User::where('usergroup_id', 5)->count(),
            'students'         => User::where('usergroup_id', 6)->count(),
            'parents'          => User::where('usergroup_id', 7)->count(),

            'recentSchools'    => School::latest()->take(5)->get(),
            'recentUsers'      => User::with('school')->latest()->take(5)->get(),

            'subscriptionsTotal' => Subscription::count(),
            'subscriptionsByStatus' => Subscription::selectRaw('status, count(*) as count')
                                            ->groupBy('status')
                                            ->pluck('count', 'status'),
            'plans'            => (function () {
                // Manual aggregation because Plan::subscription relationship has an inverted FK.
                $subscriptionCounts = Subscription::selectRaw('plan_id, count(*) as count')
                    ->groupBy('plan_id')
                    ->pluck('count', 'plan_id');

                return Plan::where('is_active', true)
                    ->orderBy('amount')
                    ->get()
                    ->map(function ($plan) use ($subscriptionCounts) {
                        $plan->subscriptions_count = $subscriptionCounts[$plan->id] ?? 0;
                        return $plan;
                    });
            })(),

            'whatsappUsers'    => WhatsAppUser::count(),
            'whatsappMessages' => MessageDeliveryLog::whereDate('sent_at', '>=', now()->startOfMonth())->whereDate('sent_at', '<=', now()->endOfMonth())->count(),
        ];

        return view('/superadmin/dashboard',[
            'dashboard' => $dashboard,
            'standardLink' => $standardLink,
            'selected_teacher' => $selected_teacher,
            'stats' => $stats,
        ] );
    }
}