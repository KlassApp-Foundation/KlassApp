<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Requests\ImportMemberRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Imports\UsersImport;
use App\Traits\LogActivity;
use App\Traits\Common;
use League\Csv\Writer;
use App\Models\User;
use Exception;

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
      try
      {
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
          return back()->with('failmessage',trans('messages.insert_failure_msg'));
        } 
        \Session::forget('insertedcount'); 
      }
      catch(Exception $e)
      {
        dd($e->getMessage());
      }
    }

   public function downloadFormat()
{
    $csv = Writer::createFromFileObject(new \SplTempFileObject());

    // ✅ SIMPLE, SCHOOL-FRIENDLY HEADERS @Ugandan schools
    $csv->insertOne([
 'firstname', 'lastname', 'gender', 'date_of_birth', 'class', 'address', 'region', 'district', 'country', 'mother_tongue', 'joining_date'
    ]);
    // ✅ REALISTIC UGANDA SAMPLE @Ugandan schools
    $csv->insertOne([
        'Bukayo', 'Saka', 'male', '2012-05-14', 'Primary One', 'Kekuubo', 'Western', 'Kabale', 'Uganda', 'Rukiga', '2025-01-10'
    ]);

    // Headers (EXACTLY your system format)
    // $csv->insertOne([
    //     'firstname','lastname','mobile_no','email','gender','date_of_birth','blood_group',
    //     'class','section','address','city','state','country','pincode','birth_place',
    //     'native_place','mother_tongue','caste','sub_caste','aadhar_number','joining_date',
    //     'admission_number','EMIS_number','roll_number','id_card_number','board_registration_number',
    //     'mode_of_transport','driver_name','driver_contact_number','siblings','siblings_count',
    //     'sibling_relation','sibling_name','sibling_date_of_birth','sibling_class','notes',
    //     'parent_firstname','parent_lastname','parent_mobile_no','parent_alternate_no','parent_email',
    //     'parent_qualification','parent_occupation','parent_sub_occupation','parent_designation',
    //     'parent_organization_name','parent_official_address','parent_annual_income','relation'
    // ]);

    // Sample UG row (light + realistic)
    // $csv->insertOne([
    //     'John','Mugisha','256700000000','john@example.com','male','2012-05-14','O+',
    //     'Primary One','A','Kampala Central','Kampala','Central','Uganda','','Kampala',
    //     'Kampala','Luganda','','','','2025-01-10',
    //     'ADM001','EMIS001','1','','','school_bus','Driver Name','256700000001',
    //     'no','','','','','',
    //     'Peter','Mugisha','256700000002','','father@example.com',
    //     'degree','business','retail','manager','Mugisha Ltd','Kampala','500000','father'
    // ]);

    $filename = 'klassapp_student_template' . date('d-m-Y_H:i') . '.csv';

    $csv->output($filename);

    exit;
}
}
