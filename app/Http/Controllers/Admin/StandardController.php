<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Requests\StandardRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Traits\AcademicProcess;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Traits\LogActivity;
use App\Models\Standard;
use App\Traits\Common;
use Exception;

class StandardController extends Controller
{
    use AcademicProcess;
    use LogActivity;
    use Common;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StandardRequest $request)
    { 
        //
        try
        {
            $school_id = Auth::user()->school_id;
          
            $standard = $this->createStandard($school_id , $request);

            $message = trans('messages.add_success_msg',['module' => 'Standard']);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $standard,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_ADD_STANDARD,
                $message
            );

            $res['success'] = $message;

            return $res;
        }
        catch(Exception $e)
        {
            //dd($e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $academic_year = SiteHelper::getAcademicYear(Auth::user()->school_id);

        return view('/admin/school/standards/add' , ['academic_year_id' => $academic_year->id]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function add(Request $request)
    { 
        //
        try
        {
            $school_id = Auth::user()->school_id;
          
            $standard = $this->addStandard($school_id , $request);

            $message = trans('messages.standard_setup_success_msg');

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $standard,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_ADD_STANDARD_SETUP,
                $message
            );

            $res['success'] = $message;

            return $res;
        }
        catch(Exception $e)
        {
            //dd($e->getMessage());
        }
    }
}