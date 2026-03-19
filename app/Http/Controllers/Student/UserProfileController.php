<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Student;

use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\LogActivity;
use App\Traits\Common;
use App\Models\User;
use Exception;
use Hash;

class UserProfileController extends Controller
{
    use Common;
    use LogActivity;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function ChangePassword()
    {
        return view('/student/changepassword');
    }
      
    /**
     * Updates the password of the specified user.
     * 
     * @param \Illuminate\Http\Request $request 
     * 
     * @return \Illuminate\Http\Response
     */
    public function updateChangePassword(ChangePasswordRequest $request)
    {
        $user = User::find(Auth::id());
        $hashedPassword = $user->password;

        if($hashedPassword!='')
        { 
            $user->password = Hash::make($request->newpassword);
            $user->save();

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $user,
                Auth::user(),
                ['ip' => $ip,'details' => $_SERVER['HTTP_USER_AGENT']],
                LOGNAME_CHANGE_PASSWORD,
                'Changed Profile Password.'                        
            );         
        } 
           
        $res['message']=__('admin_userprofile.password_update');
        return $res;
    }
}