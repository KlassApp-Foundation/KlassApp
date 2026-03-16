<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Events\SendMessageTeacherEvent;
use App\Http\Requests\SendMailRequest;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Events\SendMessageEvent;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Traits\LogActivity;
use App\Models\SendMail;
use App\Traits\Common;
use App\Models\User;
use App\Models\StudentAcademic;
use Exception;
use Log;
use App\Models\AcademicYear;
use App\Models\Userprofile;
use App\Traits\RegisterUser;

class SendMessageController extends Controller
{
    use LogActivity;
    use Common;
    use RegisterUser;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
        $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);
        $messages =  SendMail::with('user')->where([['school_id',$school_id],['academic_year_id',$academic_year->id]])->orderBy('fired_at','desc');

        if($request->type!= '')
        { 
            $messages = $messages->whereHas('user',function ($query) use($request)
            {
                if($request->type == 'teacher')
                {
                    $query->where('usergroup_id',5);
                }
                elseif($request->type == 'parent')
                {
                    $query->where('usergroup_id',7);
                }
                elseif($request->type == 'alumni')
                {
                    $query->where('usergroup_id',9);
                }
            });
        }

        $messages = $messages->paginate(10);

        return view('admin/member/sentmessagetoall',['messages' => $messages ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SendMailRequest $request)
    {
        //
        try
        {
            event (new SendMessageEvent ($request , Auth::user()->school_id , Auth::user()->email , Auth::user() ) );
                  
            $res['message'] = trans('messages.message_success_msg');
            return $res;
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeTeacher(SendMailRequest $request)
    {
        //
        try
        {
            $data=[];
            $data['selected'] =$request->selected;
            $data['subject']=$request->subject;
            $data['message']=$request->message;
            $data['send_later']=$request->send_later;
            $data['executed_at']=$request->executed_at;
            $datas=(object)$data;
            
            event (new SendMessageTeacherEvent ($datas , Auth::user()->school_id , Auth::user()->email , Auth::user() ) );
                  
            $res['message'] = trans('messages.message_success_msg');
            return $res;
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    public function shift(request $request)
    {
        //
       // dd($request);  
        
        try
        {
             $school_id = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);

        foreach ($request->selectedUsers as $key => $value) {
            
           $studentAcademic = StudentAcademic::where([['user_id',$value],['school_id',$school_id],['academic_year_id',$academic_year->id]])->latest()->first();
          
                
               if($studentAcademic)
               {
                if( $studentAcademic->standardLink_id != 42 )
                {
                    $studentAcademic->standardLink_id              = $request->shift_std;
                    $studentAcademic->update();
                }
                else
                {
                   
                    $user=User::where('id', $value)->first();
                    $user->usergroup_id="9";
                    $user->save();
                    $userprofile=Userprofile::where('user_id',$value)->first();
                    $userprofile->usergroup_id="9";
                    $userprofile->save();
                    $academic_year_data=AcademicYear::where('id',$academic_year->id)->first();
                    $passing_session_year=explode("-",$academic_year_data->name);//dd($user->userprofile);
                    $data=$user;
                    $data['name']=$user->userprofile->firstname;
                    /*$data['mobile_no']=$user->mobile_no;
                    $data['email']=$user->email;*/
                    $data['passing_session']=$passing_session_year['1'];
                    $data['current_studying']=$academic_year->id;
                    $data['institution_name']="";
                    $data['degree']="";
                    $data['specialization']="";
                    $data['college_start_year']="";
                    $data['college_end_year']="";
                    $data['grade']="";
                    $data['company_name']="";
                    $data['designation']="";
                    $data['location']="";
                    $data['job_start_year']="";
                    $data['job_start_month']="";
                    $data['present']="";
                    $data['twitter']="";
                    $data['linkedin']="";
                    $data['telegram']="";
                    $data['facebook']="";
                    $data['about_me']="";
                    $path = '';
                    $usergroup_id="9";
                  
                  //  $alumniprofile = $this->CreateAlumni($data, $path, $usergroup_id, $school_id, $user);

           // \DB::commit();
                }
                
               }

         }
       
          $res['message'] = count($request->selectedUsers)." Students shifted sucessfully";
            return $res;
            
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

}