<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Events\EmergencyNotificationEvent;
use App\Http\Requests\EmergencyNotificationRequest;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Traits\LogActivity;
use App\Models\SendMail;
use App\Traits\Common;
use App\Models\User;
use Exception;
use Log;

class SendEmergencyMessageController extends Controller
{
    
    public function create()
    {
        //
        return view('admin.message.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(EmergencyNotificationRequest $request)
    {
        //
        //dump($request->all());
        try
        {
            $data=[];
            $data['message_type'] =$request->message_type;
            $data['message']=$request->message;
            $data['standard_id']=$request->standardLink_id;
            $datas=(object)$data;
            
            event (new EmergencyNotificationEvent ($datas , Auth::user()->school_id , Auth::user()->email , Auth::user() ) );
                  
            $res['message'] = trans('messages.message_success_msg');
            return $res;
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

}
