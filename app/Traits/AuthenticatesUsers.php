<?php

namespace App\Traits;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Traits\ThrottlesLogins;
use App\Traits\RedirectsUsers;
use Illuminate\Http\Request;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Validator;

trait AuthenticatesUsers
{
    use RedirectsUsers, ThrottlesLogins;

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
*/
    protected function validateLogin(Request $request)
    { //dump($request);

        Validator::extend('checkschool', function ($attribute, $value, $parameters, $validator) 
        {
           
           $users = User::orWhere('email', request('email'))->orWhere('mobile_no', request('email'))->orWhere('name', request('email'))->orWhere('registration_number',request('email'))->first();

//dump($users);
            if($users->usergroup_id == 1)
            {
                return TRUE;
            }


          
            else
            { 
                $school = School::IsActive($users->school_id)->exists();
                //dump($school);
                if($school == FALSE)
                { 
                    return FALSE;
                }
                else
                {
                    return TRUE;
                }  
            }    
        },'Invalid Credentials.You are not in this school');

        Validator::extend('checkusers', function ($attribute, $value, $parameters, $validator) 
        {
            $users = User::where('email', request('email'))->with('userprofile')->first();
            if($users==null)
            { 
                return FALSE;
            }
            else
            {
                return TRUE;
            }       
        },'Invalid Credentials');
       
        Validator::extend('checkactive', function ($attribute, $value, $parameters, $validator) 
        {
            $users = User::where('email', request('email'))->with('userprofile')->first();
            if($users->userprofile->status=="inactive")
            { 
                return FALSE;
            }
            else
            {
                return TRUE;
            }
        },'You are suspended by site admin');

        Validator::extend('checkexit', function ($attribute, $value, $parameters, $validator) 
        {
            $users = User::where('email', request('email'))->with('userprofile')->first();
            if($users->userprofile->status=="exit")
            { 
                return FALSE;
            }
            else
            {
                return TRUE;
            }
        },'You have exited this school');
       
        Validator::extend('checkstatus', function ($attribute, $value, $parameters, $validator) 
        {
            $user = User::where('email', request('email'))->with(['userprofile','alumniprofile'])->first();
            if(count($user)>0)
            {
                if($user->usergroup_id==1)
                {
                    return TRUE;
                }
                elseif($user->usergroup_id==3)
                {
                    return TRUE;
                }
                elseif($user->usergroup_id==4)
                { 
                    return TRUE;
                }
                elseif ($user->usergroup_id==5)
                { 
                    if(\Config::get('settings.login_status')==1)
                    return TRUE;
                }
                elseif ($user->usergroup_id==6)
                { 
                    return TRUE;
                }
                elseif($user->usergroup_id==8)
                { 
                    return TRUE;
                }
                elseif($user->usergroup_id==9)
                { 
                    return TRUE;
                }
                elseif($user->usergroup_id==10)
                { 
                    return TRUE;
                }
                elseif($user->usergroup_id==11)
                { 
                    return TRUE;
                }
                elseif($user->usergroup_id==12)
                { 
                    return TRUE;
                }
                return FALSE;
            }
            return FALSE;   
        },'Invalid Credentials');

         $this->validate($request,[
            $this->username() => 'required|string',
            'password' => 'bail|required|string|checkschool',
        ]);

        $messages=[];
        $rules=[];
        $this->validate($request,$rules,$messages);
         
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        return $this->guard()->attempt(
            $this->credentials($request), $request->filled('remember')
        );
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only($this->username(), 'password');
    }

  

    /**
     * Send the response after the user was authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        return $this->authenticated($request, $this->guard()->user())
                ?: redirect()->intended($this->redirectPath());
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        //
    }

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
       $login = request()->input('email');
       $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'registration_number';
       request()->merge([$field => $login]);
       return $field;
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        return $this->loggedOut($request) ?: redirect('/');
    }

    /**
     * The user has logged out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function loggedOut(Request $request)
    {
        //
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();
    }
}
