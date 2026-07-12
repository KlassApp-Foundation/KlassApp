<?php

namespace App\Http\Middleware;

use Closure;

class MustBeSchoolAdmin
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
        
        if(\Auth::user()->usergroup_id==3)
        {
            return $next($request);
        }
          
        if(\Auth::user()->usergroup_id==1)
        {
            // Superadmins pass through to admin routes (they handle null school_id gracefully).
            return $next($request);
        }

        if(\Auth::user()->usergroup_id==3)
        {
            return $next($request);
        }

        if(\Auth::user()->usergroup_id==5)
        {
            return redirect('/teacher/dashboard');
        }

        if(\Auth::user()->usergroup_id==6)
        {
            return redirect('/student/dashboard');
        }
        
        if(\Auth::user()->usergroup_id==8)
        {
            return redirect('/library/dashboard');          
        }
        
        if(\Auth::user()->usergroup_id==9)
        {
            return redirect('/alumni/dashboard');          
        }
        
        if(\Auth::user()->usergroup_id==10)
        {
            return redirect('/receptionist/dashboard');          
        }
        
        if(\Auth::user()->usergroup_id==11)
        {
            return redirect('/accountant/dashboard');          
        }
            
        abort(404);
    }
}