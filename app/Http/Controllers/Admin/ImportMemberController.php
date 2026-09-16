<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Requests\ImportMemberRequest;
use App\Services\ToshiActionService;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Imports\UsersImport;
use App\Traits\LogActivity;
use App\Traits\Common;
use League\Csv\Writer;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class ImportMemberController extends Controller
{
  use LogActivity;
  use Common;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      //
      return view('admin/member/import/import');
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function importUsers(ImportMemberRequest $request)
    {
      //
      $school_id = Auth::user()->school_id;

      // ── Plan limit check — reject whole batch upfront if at/over limit ──
      $limit = ToshiActionService::enforcePlanLimit($school_id, 'students');
      if (!$limit['success']) {
          return back()->with('failmessage', $limit['message']);
      }

      try
      {
        \Session::forget('skippedcount');
        Excel::import(new UsersImport,$request->file('import_file'));
        $count = \Session::get('count');
        if($count != 0)
        {
          return back()->with('failmessage','You can add only '.$count.' Members');
        }
        \Session::forget('count');

        $insertedcount = \Session::get('insertedcount');
        if($insertedcount > 0)
        {
          \Session::forget('skippedcount');
          $message= trans('messages.import_success_msg',['module' => 'Student']);

          $ip= $this->getRequestIP();
          $this->doActivityLog(
            Auth::user(),
            Auth::user(),
            ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
            LOGNAME_IMPORT_STUDENT,
            $message
          );
          return back()->with('successmessage',$insertedcount.' '.trans('messages.insert_success_msg'));
        }
        else
        {
          $skipped = \Session::get('skippedcount', 0);
          $message = $skipped > 0
            ? "No students were imported. {$skipped} row(s) were skipped because each row must have a Name and Class."
            : 'No students were imported. Check that the file contains data rows with the required Name and Class columns.';

          return back()->with('failmessage', $message);
        }
        \Session::forget('insertedcount');
      }
      catch(Exception $e)
      {
        Log::error('ImportMemberController@importUsers failed', [
          'exception' => $e,
          'school_id' => $school_id,
          'filename' => $request->file('import_file')?->getClientOriginalName(),
        ]);

        return back()->with('failmessage', 'Student import failed: '.$e->getMessage());
      }
    }

   public function downloadFormat()
{
    $csv = Writer::createFromFileObject(new \SplTempFileObject());

    // ✅ SIMPLE, SCHOOL-FRIENDLY HEADERS @Ugandan schools
    $csv->insertOne([
 'firstname', 'lastname', 'gender', 'date_of_birth', 'class', 'address', 'region', 'district', 'country', 'joining_date', "lin", "std_school_pay_number"
    ]);
    // ✅ REALISTIC UGANDA SAMPLE @Ugandan schools
    $csv->insertOne([
        'Bukayo', 'Saka', 'male', '2012-05-14', 'Primary One', 'Kekuubo', 'Western', 'Kabale', 'Uganda', '2025-01-10', "U12F0521A33556", "654321"
    ]);

    
    $filename = 'klassapp_student_template' . date('d-m-Y_H:i') . '.csv';

    $csv->output($filename);

    exit;
}
}
