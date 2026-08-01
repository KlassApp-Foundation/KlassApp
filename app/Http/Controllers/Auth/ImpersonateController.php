<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Services\Superadmin\ImpersonationService;
use Illuminate\Http\Request;
use App\Traits\Common;
use App\Models\User;
use Exception;
use Illuminate\Validation\ValidationException;
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
            app(ImpersonationService::class)->startSchoolAdminImpersonation(
                Auth::user(),
                (int) $id
            );

            return redirect('/admin/dashboard');
        }
        catch (ValidationException $e)
        {
            \Session::put('Impersonate disabled for this user.');

            return redirect()->back()->with(
                'errormessage',
                collect($e->errors())->flatten()->first() ?? 'Failed to impersonate user.'
            );
        }
        catch(Exception $e)
        {
            Log::error('ImpersonateController@schoolAdminimpersonate failed: ' . $e->getMessage());
            return redirect()->back()->with('errormessage', 'Failed to impersonate user.');
        }
    }

    /**
     * Stop impersonation and return the real (session) user to their home dashboard.
     *
     * Impersonation uses Auth::onceUsingId in middleware, so Auth::user() is the
     * impersonated account. The real user id remains in the session login key.
     *
     * @return \Illuminate\Http\Response
     */
    public function stopImpersonate()
    {  
        try
        {
            $impersonatorId = session()->get(Auth::getName());

            if (Auth::check() && Auth::user()->isImpersonating()) {
                Auth::user()->stopImpersonating();
            }

            $user = User::find($impersonatorId);

            if ($user === null) {
                return redirect('/');
            }

            return match ((int) $user->usergroup_id) {
                1 => redirect('/superadmin/dashboard'),
                3 => redirect('/admin/dashboard'),
                5 => redirect('/teacher/dashboard'),
                6 => redirect('/student/dashboard'),
                8 => redirect('/library/dashboard'),
                default => redirect('/'),
            };
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());

            return redirect('/');
        } 
    }
}
