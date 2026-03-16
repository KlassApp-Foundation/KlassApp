<?php

namespace App\Http\Middleware;

use Closure;

class MustBeStockKeeper
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
        //dd('kjjkh');

        if(\Auth::user()->usergroup_id==12)
        {
            return $next($request);  
            // return redirect('/stock/dashboard');            
        }
          
        if(\Auth::user()->usergroup_id==1)
        {
            return redirect('/superadmin/dashboard');
        }
          
        if(\Auth::user()->usergroup_id==3)
        {
            return redirect('/admin/dashboard');
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

        if(\Auth::user()->usergroup_id==11)
        {
            return redirect('/accountant/dashboard');
        }
        
        abort(404);
    }
}
