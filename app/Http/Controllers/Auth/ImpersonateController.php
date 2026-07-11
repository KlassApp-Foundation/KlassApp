<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\Common;
use App\Models\User;
use Exception;
use Log;

class ImpersonateController extends Controller
{
    use Common;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function impersonate($id)
    {
        try
        {
            $schoolId = Auth::user()->school_id;
            $user = User::where('school_id', $schoolId)->findOrFail($id);

            $is_admin = $this->is_admin($user->id);
            if($is_admin == false)
            {
                Auth::user()->setImpersonating($user->id);
            }
            else
            {
                \Session::put('Impersonate disabled for this user.');
            }

            return redirect('/teacher/dashboard');
        }
        catch(Exception $e)
        {
            Log::error('ImpersonateController@impersonate failed: ' . $e->getMessage());
            return redirect()->back()->with('errormessage', 'Failed to impersonate user.');
        }
    }

    public function librarianimpersonate($id)
    {
        try
        {
            $schoolId = Auth::user()->school_id;
            $user = User::where('school_id', $schoolId)->findOrFail($id);

            $is_admin = $this->is_admin($user->id);
            if($is_admin == false)
            {
                Auth::user()->setImpersonating($user->id);
            }
            else
            {
                \Session::put('Impersonate disabled for this user.');
            }

            return redirect('/library/dashboard');
        }
        catch(Exception $e)
        {
            Log::error('ImpersonateController@librarianimpersonate failed: ' . $e->getMessage());
            return redirect()->back()->with('errormessage', 'Failed to impersonate user.');
        }
    }

    public function studentimpersonate($id)
    {
        try
        {
            $schoolId = Auth::user()->school_id;
            $user = User::where('school_id', $schoolId)->findOrFail($id);

            $is_admin = $this->is_admin($user->id);
            if($is_admin == false)
            {
                Auth::user()->setImpersonating($user->id);
            }
            else
            {
                \Session::put('Impersonate disabled for this user.');
            }

            return redirect('/student/dashboard');
        }
        catch(Exception $e)
        {
            Log::error('ImpersonateController@studentimpersonate failed: ' . $e->getMessage());
            return redirect()->back()->with('errormessage', 'Failed to impersonate user.');
        }
    }

    public function schoolAdminimpersonate($id)
    {
        try
        {
            $user = User::findOrFail($id);

            $is_admin = $this->is_admin($user->id);
            if($is_admin == true)
            {
                Auth::user()->setImpersonating($user->id);
            }
            else
            {
                \Session::put('Impersonate disabled for this user.');
            }

            return redirect('/admin/dashboard');
        }
        catch(Exception $e)
        {
            Log::error('ImpersonateController@schoolAdminimpersonate failed: ' . $e->getMessage());
            return redirect()->back()->with('errormessage', 'Failed to impersonate user.');
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function stopImpersonate()
    {  
        try
        {      
            $user = User::where('id', Auth::user()->id)->first();
            Auth::user()->stopImpersonating();
           // dd($user->usergroup_id);
            if ($user->usergroup_id == 5)
            {
                return redirect('/teacher/dashboard');
            } 
            elseif ($user->usergroup_id == 3)
            {
                return redirect('/admin/dashboard');
            }
            elseif ($user->usergroup_id == 6)   
            {
                return redirect('/student/dashboard');
            } 
            elseif ($user->usergroup_id == 8)   
            {
                return redirect('/library/dashboard');
            } 
            /*elseif ($user->usergroup_id == 1)   
            {
                return redirect('/superadmin/dashboard');
            } */
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        } 
    }
}