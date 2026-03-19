<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Requests\LeaveTypeUpdateRequest;
use App\Http\Requests\LeaveTypeAddRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Traits\LogActivity;
use App\Models\LeaveType;
use League\Csv\Writer;
use App\Traits\Common;
use Carbon\Carbon;
use Exception;

class LeaveTypesController extends Controller
{
    use LogActivity;
    use Common;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);
        $leavetypes = LeaveType::where([['school_id',$school_id],['academic_year_id',$academic_year->id],['status',1]])->get();
        return view('admin/leavetypes/index' , ['leavetypes' => $leavetypes]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('admin/leavetypes/create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(LeaveTypeAddRequest $request)
    {
        //
        try
        {
            $school_id = Auth::user()->school_id;
            $academic_year = SiteHelper::getAcademicYear($school_id);

            $leavetype                      = new LeaveType;

            $leavetype->school_id           = $school_id;
            $leavetype->academic_year_id    = $academic_year->id;
            $leavetype->name                = $request->name;
            $leavetype->max_no_of_days      = $request->max_no_of_days;
            $leavetype->status              = 1;

            $leavetype->save();

            $message=trans('messages.add_success_msg',['module' => 'LeaveType']);


            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $leavetype,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_ADD_LEAVETYPE,
                $message
            );

            return redirect('/admin/leavetypes')->with('successmessage',$message);
        }
        catch(Exception $e)
        {
            //dd($e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $leavetype = LeaveType::where('id',$id)->first();
        return view('admin/leavetypes/edit',['leavetype' => $leavetype]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(LeaveTypeUpdateRequest $request, $id)
    {
        //
        try
        {
            $leavetype                      = LeaveType::where('id',$id)->first();

            $leavetype->name                = $request->name;
            $leavetype->max_no_of_days      = $request->max_no_of_days;

            $leavetype->save();

            $message=trans('messages.update_success_msg',['module' => 'LeaveType']);


            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $leavetype,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_ADD_LEAVETYPE,
                $message
            );

            return redirect('/admin/leavetypes')->with('successmessage',$message);
        }
        catch(Exception $e)
        {
            //dd($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        try
        {
            $leavetype = LeaveType::where('id',$id)->first();

            $leavetype->delete();

            $message= trans('messages.delete_success_msg',['module' => 'LeaveType']);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $leavetype,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_DELETE_LEAVETYPE,
                $message
            );

            return redirect()->back()->with('successmessage',$message);
        }
        catch(Exception $e)
        {
            //dd($e->getMessage());
        }
    }
}
