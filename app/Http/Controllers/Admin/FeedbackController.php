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
        $school_id = Auth::user()->school_id;
        $feedback = $this->findSchoolFeedback($feedbackid, $school_id, 'view');
        $feedback->load(['parent', 'admin', 'feedbackMessage']);

        $messages = FeedbackMessage::where('feedback_id', $feedback->id)
            ->where('school_id', $school_id)
            ->with('feedback')
            ->get();
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
        // Scoped lookup outside the try: the catch below would swallow abort().
        $feedbackMessage = FeedbackMessage::where('id', $id)
            ->where('school_id', Auth::user()->school_id)
            ->first();

        if (!$feedbackMessage) {
            Log::warning('Feedback status update refused: message not in admin school', ['admin_id' => Auth::id(), 'requested_id' => $id]);
            abort(404);
        }

        try
        {
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
        // Scoped lookup outside the try: the catch below would swallow abort().
        $feedback = $this->findSchoolFeedback($feedbackid, Auth::user()->school_id, 'reply');

        try
        {
            $message = new FeedbackMessage;

            $message->message = $request->message;
            $message->user_id = Auth::id();
            $message->school_id = Auth::user()->school_id;
            $message->feedback_id = $feedback->id;

            $message->save();

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

    /**
     * A feedback thread by id, only if it belongs to the admin's school.
     * A foreign or unknown id is a 404 (not 403) so ids of other schools'
     * threads are not confirmed to exist.
     */
    private function findSchoolFeedback($feedbackid, $school_id, string $action): Feedback
    {
        $feedback = Feedback::where('id', $feedbackid)
            ->where('school_id', $school_id)
            ->first();

        if (!$feedback) {
            Log::warning("Feedback {$action} refused: thread not in admin school", ['admin_id' => Auth::id(), 'requested_id' => $feedbackid]);
            abort(404);
        }

        return $feedback;
    }
}
