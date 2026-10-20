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
  /**
   * Show a confirmation step. Opening the link must not verify the address.
   */
  public function emailverification($token)
  {
    $user = $this->userForToken($token);
    if ($user === null) {
      return redirect()->to('login')->with('errormessage', 'This verification link is not valid.');
    }
    if ((int) $user->email_verified === 1) {
      return $this->alreadyVerifiedRedirect();
    }

    return view('auth.email-verification-confirm', ['token' => $token]);
  }

  /**
   * Mark the address verified after the person confirms.
   */
  public function confirm(Request $request, $token)
  {
    $user = $this->userForToken($token);
    if ($user === null) {
      return redirect()->to('login')->with('errormessage', 'This verification link is not valid.');
    }
    if ((int) $user->email_verified === 1) {
      return $this->alreadyVerifiedRedirect();
    }

    $user->email_verified = 1;
    $user->email_verified_at = Carbon::now();
    $user->save();

    return $this->alreadyVerifiedRedirect();
  }

  private function userForToken($token): ?User
  {
    return User::where('email_verification_code', $token)->first();
  }

  private function alreadyVerifiedRedirect()
  {
    \Session::put('successmessage', 'E-mail Verified Successfully,Login Now');

    if (! is_null(Auth::id())) {
      return redirect()->to('/home');
    }

    return redirect()->to('login');
  }
}
