<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Events\Notification\SingleNotificationEvent;
use App\Http\Requests\FeedbackRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Events\SinglePushEvent;
use App\Models\FeedbackMessage;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Traits\LogActivity;
use App\Models\Userprofile;
use App\Models\Feedback;
use App\Traits\Common;
use App\Models\User;
use Exception;
use Log;

class FeedbackController extends Controller
{
    use LogActivity;
    use Common;
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
        $feedbacks = Feedback::where([['school_id',$school_id],['created_at','>=',$academic_year->start_date],['created_at','<=',$academic_year->end_date]])->with(['parent', 'admin','feedbackMessage']);
        if(count((array)\Request::getQueryString())>0)
        {
            if($request->search != '')
            { 
                $feedbacks = $feedbacks->whereHas('feedbackMessage',function ($q) use($request){
                    $q->where('message','LIKE','%'.$request->search.'%');
                });
            }
        }
        $feedbacks = $feedbacks->latest()->paginate(10);

        return view('admin/feedbacks/index',[ 'feedbacks' => $feedbacks ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($feedbackid)
    {
        //
        $messages = FeedbackMessage::where('feedback_id', $feedbackid)->with('feedback')->get();
        $feedback = Feedback::where('id', $feedbackid)->with(['parent', 'admin','feedbackMessage'])->first();
        /*foreach ($messages as $message)
        {
            $message = FeedbackMessage::where('id', $message->id )->first();
            $message->is_seen = 'has_seen';
            $message->save();
        }*/
        return view('admin/feedbacks/view', [ 'messages' => $messages , 'feedback' => $feedback ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request,$id)
    {
        //
        try
        {
            $feedbackMessage = FeedbackMessage::where('id', $id)->first();

            $feedbackMessage->is_seen = $request->status;

            $feedbackMessage->save();

            $feedback = Feedback::where('id',$feedbackMessage->feedback_id)->first();

           
            $data=[];

            $data['school_id']  =   Auth::user()->school_id;
            $data['user_id']    =   $feedbackMessage->feedback->parent_id;
            $data['message']    =   'New Response For Your Feedback';
            $data['type']       =   'feedback';
            
            event(new SinglePushEvent($data));

            $array = [];
            $student = User::where('id',$feedbackMessage->feedback->student_id)->first();
            $array['user']       =   $student;
            $array['details']    =   'New Response For Your Feedback';
            event(new SingleNotificationEvent($array));

            $message=trans('messages.update_status_success_msg',['module' => 'Feedback']);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $feedbackMessage,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_UPDATE_FEEDBACK_STATUS,
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

    public function update(FeedbackRequest $request,$feedbackid)
    {
        //
        try
        {
            $message = new FeedbackMessage;

            $message->message = $request->message;
            $message->user_id = Auth::id();
            $message->school_id = Auth::user()->school_id;
            $message->feedback_id = $feedbackid;

            $message->save();

            $feedback = Feedback::where('id',$feedbackid)->first();

            $data=[];

            $data['school_id']  =   Auth::user()->school_id;
            $data['user_id']    =   $feedback->parent_id;
            $data['message']    =   'New Message Response For Your Feedback';
            $data['type']       =   'feedback';
            
            event(new SinglePushEvent($data));
            
            return \Redirect::back()->with('successmessage',trans('messages.message_success_msg'));
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }
}