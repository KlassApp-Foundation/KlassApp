<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Api\Teacher;


use App\Http\Resources\Teacher\Task as TaskResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\Dashboard;
use App\Models\Task;

class DashboardController extends Controller
{
       use Dashboard;

         public function index(Request $request)
    {
        //
        $teacher_id = Auth::id();
        $school_id  = Auth::user()->school_id;

        $dashboard = $this->teacherDashboard( $school_id, $teacher_id );

          return response()->json([
            'success'   =>  true,
            'message'   =>  'Teacher Dashboard Data',
            'data'      =>  $dashboard['subject'],
        ],200);
        
        
    }
    //
}
