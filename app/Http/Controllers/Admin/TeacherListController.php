<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Traits\MemberProcess;
use Illuminate\Http\Request;
use App\Traits\LogActivity;
use App\Traits\Common;
use App\Models\User;
use App\Services\People\PeopleListQuery;
use Exception;

class TeacherListController extends Controller
{
    use MemberProcess;
    use LogActivity;
    use Common;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function find(Request $request)
    {
      //
      return $this->TeacherFilter($request,Auth::user()->school_id,5);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, PeopleListQuery $people)
    {
        return view('/admin/teacher/index', [
            'list' => $people->teachers(Auth::user(), $request),
        ]);
    }

    public function destroy($name)
    {
        try
        {
            $schoolId = Auth::user()->school_id;
            $user = User::where('name',$name)->where('school_id', $schoolId)->firstOrFail();
            $user->delete();

            $message=trans('messages.delete_success_msg',['module' => 'Teacher']);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $user,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_DELETE_TEACHER,
                $message
            );
            \Session::put('successmessage',$message);
            return redirect('/admin/teachers');
        }
        catch(Exception $e)
        {
            //dd($e->getMessage());
        }
    }
}
