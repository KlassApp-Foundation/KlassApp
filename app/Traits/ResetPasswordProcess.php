<?php

namespace App\Traits;

use App\Mail\SetPasswordLinkMail;
use App\Support\SetPasswordLink;
use App\Traits\SmsProcess;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

trait ResetPasswordProcess
{
    use SmsProcess;

    public function resetPasswordToUser($model)
    {
        $sendMail = env('MAIL_STATUS') == 'on' && $model->email != '';
        $sendSms = env('SMS_STATUS') == 'on' && ! empty($model->mobile_no);

        if (! $sendMail && ! $sendSms) {
            \Session::put('failmessage', 'You cannot send message');

            return;
        }

        try {
            $issued = SetPasswordLink::issue($model);
        } catch (Exception $e) {
            Log::info($e->getMessage());
            \Session::put('successmessage', 'Password Reset failed for this User');

            return;
        }

        if ($sendSms) {
            $this->sendUserResetPassword($model->mobile_no, $issued['url']);
        }

        if ($sendMail) {
            Mail::to($model->email)->queue(new SetPasswordLinkMail(
                $model->name ?: 'there',
                $issued['url'],
                $issued['expires_at'],
                $issued['minutes'],
            ));
            \Session::put('successmessage', 'Check your email to reset the password');
        } else {
            \Session::put('failmessage', 'You cannot send message');
        }
    }

    public function resetPasswordSms($user)
    {
        try {
            if (env('SMS_STATUS') != 'on' || empty($user->mobile_no)) {
                return;
            }

            $issued = SetPasswordLink::issue($user);
            $this->sendUserResetPassword($user->mobile_no, $issued['url']);
        } catch (Exception $e) {
            Log::info($e->getMessage());
        }
    }
}
