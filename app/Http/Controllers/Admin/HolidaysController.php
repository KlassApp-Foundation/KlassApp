<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Resources\Holiday as HolidayResource;
use App\Http\Requests\HolidayUpdateRequest;
use App\Http\Requests\HolidayAddRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Traits\LogActivity;
use App\Models\Events;
use App\Traits\Common;
use Exception;

class HolidaysController extends Controller
{
    use LogActivity;
    use Common;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list()
    {
        //
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);
        $holidays = Events::where([['school_id',$school_id],['academic_year_id',$academic_year->id],['category','holidays']])->orderBy('start_date','ASC')->paginate(10);
        $holidays = HolidayResource::collection($holidays);

        return $holidays;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return view('/admin/school/holidays/index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createList()
    {
        //
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);
        
        $array = [];

        $array['start_date'] = date('Y-m-d');

        return $array;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('/admin/school/holidays/create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(HolidayAddRequest $request)
    {
        //
        try
        {
            for($i = 0 ; $i < $request->count ; $i++)
            {
                $date = 'date'.$i;
                $title = 'title'.$i;

                $school_id = Auth::user()->school_id;
                $academic_year = SiteHelper::getAcademicYear($school_id);

                $holiday = new Events;

                $holiday->school_id         = $school_id;
                $holiday->academic_year_id  = $academic_year->id;
                $holiday->select_type       = 'school';
                $holiday->title             = $request->$title;
                $holiday->category          = 'holidays';
                $holiday->start_date        = date('Y-m-d',strtotime($request->$date));
                $holiday->end_date          = date('Y-m-d',strtotime($request->$date));

                $holiday->save();
 
                $message= trans('messages.add_success_msg',['module' => 'Holidays']);

                $ip= $this->getRequestIP();
                $this->doActivityLog(
                    $holiday,
                    Auth::user(),
                    ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                    LOGNAME_ADD_HOLIDAY,
                    $message
                );
            } 
 
            $res['success']= $message;
            return $res;
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
        $holiday = Events::where('id',$id)->first();

        $array = [];

        $array['date']  =   date('Y-m-d',strtotime($holiday->start_date));
        $array['title'] =   $holiday->title;

        return $array;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(HolidayUpdateRequest $request, $id)
    {
        //
        try
        {
            $holiday = Events::where('id',$id)->first();

            $holiday->title             = $request->title;
            $holiday->start_date        = date('Y-m-d',strtotime($request->date));
            $holiday->end_date          = date('Y-m-d',strtotime($request->date));

            $holiday->save();
 
            $message= trans('messages.update_success_msg',['module' => 'Holiday']);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $holiday,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_EDIT_HOLIDAY,
                $message
            );
 
            $res['success']= $message;
            return $res;
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
            $holiday = Events::where('id',$id)->first();
            $holiday->delete();

            $message=trans('messages.delete_success_msg',['module' => 'Holiday']);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $holiday,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_DELETE_HOLIDAY,
                $message
            );
 
            $res['success']= $message;
            return $res;
        }
        catch(Exception $e)
        {
            //dd($e->getMessage());
        }
    }
}
