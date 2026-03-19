<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;

class EmailVerificationController extends Controller
{
  public function emailverification($token)
  {
    $check = User::where('email_verification_code', $token)->first();
    if (!is_null($check)) 
    {
      $user=User::where('id',$check->id)->first();
      if ($user->email_verified == 1)
      {
        if (!is_null(Auth::id())) 
        {
          \Session::put('successmessage','E-mail Verified Successfully,Login Now');
          return redirect()->to('/home');
        }
        \Session::put('successmessage','E-mail Verified Successfully,Login Now');
        return redirect()->to('login');        
      }
      $user->email_verified = 1;
      $user->email_verified_at = Carbon::now();

      $user->save();
    } 
  }
}