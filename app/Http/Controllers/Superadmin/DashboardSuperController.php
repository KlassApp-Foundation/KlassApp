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
        //return view( '/admin/dashboard/dashboard', ['dashboard' => $dashboard , 'standardLink' => $standardLink , 'selected_teacher' => $selected_teacher ] );*/

        return view('/superadmin/dashboard',['dashboard' => $dashboard , 'standardLink' => $standardLink , 'selected_teacher' => $selected_teacher ] );
    }
}