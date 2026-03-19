<?php

namespace App\Http\Middleware;

use Closure;

class AdminAccountant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(\Auth::user()->usergroup_id==11 || \Auth::user()->usergroup_id==3 )
        {
            return $next($request);  
            // return redirect('/library/dashboard');            
        }
          
        if(\Auth::user()->usergroup_id==1)
        {
            return redirect('/superadmin/dashboard');
        }
          
        if(\Auth::user()->usergroup_id==5)
        {
            return redirect('/teacher/dashboard');
        }

        if(\Auth::user()->usergroup_id==6)
        {
            return redirect('/student/dashboard');
        }

        if(\Auth::user()->usergroup_id==10)
        {
            return redirect('/receptionist/dashboard');
        }
        
        abort(404);
    }
}
