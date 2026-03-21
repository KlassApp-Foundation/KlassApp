<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\AuthenticationProcess;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\OTPRequest;
use App\Models\Authentication;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Log;

class OTPController extends Controller
{
    use AuthenticationProcess;

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        if ($request->query('resend') == 1) {
            $user = Auth::user();
            $email = (string) $request->query('email', '');

            if ($email === '' || strcasecmp($email, (string) $user->email) !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid resend email address.',
                ], 422);
            }

            $sent = $this->createAuthentication($user, $request, 'register');

            if ($sent) {
                return response()->json([
                    'success' => true,
                    'message' => 'Verification email sent!',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }

        return view('/admin/otp/create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(OTPRequest $request)
    {
        //
        \DB::beginTransaction();
        try
        {
            $authentication = Authentication::where([
                ['user_id',Auth::id()],
                ['status',0],
                ['type','register'],
            ])->orderBy('id','DESC')->get();

            if($authentication[0]['token'] == $request->otp)
            {
                $authentication_update = Authentication::where('id',$authentication[0]['id'])->first();

                $authentication_update->status = 1;

                $authentication_update->save();

                $user = User::where('id',Auth::id())->first();

                $user->email_verified          = 1;
                $user->email_verified_at       = date('Y-m-d H:i:s');

                $user->save();

                \DB::commit();
                return redirect('/admin/dashboard')->with('successmessage',trans('messages.email_verify_success_msg'));
            }
            else
            {
                return redirect()->back()->with('failmessage',trans('messages.otp_failure_msg'));
            }
        }
        catch(Exception $e)
        {
            \DB::rollBack();
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }
}
