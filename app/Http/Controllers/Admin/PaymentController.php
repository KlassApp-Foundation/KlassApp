<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Resources\Subscription as SubscriptionResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Controller;
use App\Traits\PaymentProcess;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Traits\LogActivity;
use App\Traits\Common;
use App\Models\Plan;


class PaymentController extends Controller
{
    use PaymentProcess;
    use LogActivity;
    use Common;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        //
        if(Gate::allows('payment',$id))
        {
            $subscription = Subscription::with('plan')->where('user_id',Auth::user()->id)->first();
            if($subscription->status == 'pending')
            {
                $plan       =   Plan::where('id',$id)->first();
                $key        =   "dyTv15Mu";
                $txnid      =   "Txn12345678";
                $amount     =   $plan->amount;
                $pinfo      =   "Subscription_Amount";
                $fname      =   $subscription->user->name;
                $email      =   $subscription->user->email;
                $mobile     =   $subscription->user->mobile_no;
                $udf5       =   "BOLT_KIT_PHP7";
                $udf1       =   $plan->id;
                $salt       =   "kbwgdh7TaL";
                $surl       =   url('/admin/payment/response');
                $url        =   url('/admin/payment/index');

                $hash       =   hash('sha512', $key.'|'.$txnid.'|'.$amount.'|'.$pinfo.'|'.$fname.'|'.$email.'|'.$udf1.'||||'.$udf5.'||||||'.$salt);

                return view('/admin/payment/index',['key'=>$key , 'txnid'=>$txnid , 'amount'=>$amount , 'pinfo'=>$pinfo , 'fname'=>$fname , 'email'=>$email , 'udf5'=>$udf5 , 'salt'=>$salt , 'surl'=>$surl , 'url'=>$url , 'mobile'=>$mobile , 'hash' => $hash , 'subscription' => $subscription , 'udf1' => $udf1]);
            }
            else
            {
                return view('/admin/payment/success',['subscription' => $subscription]);
            }
        }
      else
      {
        abort(403);
      } 
    }

    public function response(Request $request)
    {
        $user_id    = Auth::id();
        $school_id  = Auth::user()->school_id; 
        $payment    = Subscription::where([['user_id',$user_id],['school_id',$school_id]])->first();

        $this->PaymentProcess($request , $user_id , $school_id , $payment);

        $message=('Payment Done Successfully');

        $ip= $this->getRequestIP();
        $this->doActivityLog(
                $payment,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_PAYMENT,
                $message
        ); 

        return view('/admin/payment/response');
    }

    public function Subscription()
    {
        $subscription       = Subscription::where('status','expired')->get();
        $subscription_list  = $subscription->take(5);
        $subscriptions      = SubscriptionResource::collection($subscription_list);

        return $subscriptions;
    }
}
