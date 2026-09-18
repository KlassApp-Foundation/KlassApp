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
    public function index(Request $request)
    {
      //
      $query = User::with('userprofile')
          ->ByRole(5)
          ->where('school_id', Auth::user()->school_id);

      $search = trim((string) $request->input('search', ''));
      if ($search !== '') {
          $query->where(function ($builder) use ($search) {
              $builder->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile_no', 'like', "%{$search}%")
                  ->orWhereHas('userprofile', function ($profileQuery) use ($search) {
                      $profileQuery->where('firstname', 'like', "%{$search}%")
                          ->orWhere('lastname', 'like', "%{$search}%");
                  });
          });
      }

      $statusFilter = $request->input('status');
      if (in_array($statusFilter, ['active', 'inactive'], true)) {
          $query->where('status', $statusFilter);
      }

      $alphabet = strtoupper((string) $request->input('alphabet', ''));
      if (preg_match('/^[A-Z]$/', $alphabet)) {
          $query->where('name', 'like', $alphabet . '%');
      }

      $teachers = $query->orderBy('name')->get();
      $count    = $teachers->count();
      $statusFilter = in_array($statusFilter, ['active', 'inactive'], true) ? $statusFilter : '';
      $query    = \Request::getQueryString();
      $birthday = request('date_of_birth') != null ? 'true' : null;
      $totalTeachers = $teachers->count();

      return view('/admin/teacher/index', [
          'alphabet' => $alphabet,
          'query' => $query,
          'birthday' => $birthday,
          'teachers' => $teachers,
          'count' => $count,
          'totalTeachers' => $totalTeachers,
          'search' => $search,
          'statusFilter' => $statusFilter,
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