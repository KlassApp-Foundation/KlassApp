<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Requests\ImportMemberRequest;
use App\Http\Controllers\Controller;
use App\Services\ToshiActionService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use App\Imports\TeachersImport;
use App\Traits\MemberProcess;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Traits\LogActivity;
use App\Traits\Common;
use League\Csv\Writer;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class TeacherImportExportController extends Controller
{
    use MemberProcess;
    use LogActivity;
    use Common;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $users = $this->TeacherFilter($request,Auth::user()->school_id,5);  
        $csv = Writer::createFromFileObject(new \SplTempFileObject());

        if(count($users) > 0)
        {
            $csv->insertOne(['employee_id','designation','firstname','lastname','gender','date_of_birth','address','city','state','country','pincode','mobile_no','email','notes','status',]);
      
            foreach($users as $user)
            {
                $csv->insertOne
                ([
                    $user->getTeacherDetails()['employee_id'],
                    $user->getTeacherDetails()['designation']=='others' ? $user->getTeacherDetails()['sub_designation']:$user->getTeacherDetails()['designation'],
                    $user->userprofile->firstname,
                    $user->userprofile->lastname,
                    $user->userprofile->gender,
                    date('d-m-Y',strtotime($user->userprofile->date_of_birth)),
                    $user->userprofile->address,
                    $user->userprofile->city->name,
                    $user->userprofile->state->name,
                    $user->userprofile->country->name,
                    $user->userprofile->pincode,
                    $user->mobile_no,
                    $user->email,
                    $user->userprofile->notes,
                    $user->userprofile->status,   
                ]);
            }
        }
        else
        {
            $csv->insertOne(['No Records Found']);
            $csv->output('SP Teacher Export'.date('_d-m-Y_H:i').'.csv');
        }
        $csv->output('SP Teacher Export'.date('_d-m-Y_H:i').'.csv');
        $message=trans('messages.export_success_msg',['module' => 'Teacher']);

        $ip= $this->getRequestIP();
        $this->doActivityLog(
            Auth::user(),
            Auth::user(),
            ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
            LOGNAME_EXPORT_TEACHER,
            $message
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function importCreate()
    {
        //
        return view('admin/teacher/import');
    }
  
    /**
    * @return \Illuminate\Support\Collection
    */
    public function import(ImportMemberRequest $request)
    {
        //
        $school_id = Auth::user()->school_id;

        // ── Plan limit check — reject whole batch upfront if at/over limit ──
        $limit = ToshiActionService::enforcePlanLimit($school_id, 'teachers');
        if (!$limit['success']) {
            return back()->with('failmessage', $limit['message']);
        }

        try
        {
            Excel::import(new TeachersImport,$request->file('import_file'));
            $count = Session::get('count');
                    

            if($count != 0)
            {
                return back()->with('failmessage','You can add only '.$count.' Members');
            }
         
            $insertedcount = Session::get('insertedcount');
            if($insertedcount > 0)
            {
                $message=trans('messages.import_success_msg',['module' => 'Teacher']);

                $ip= $this->getRequestIP();
                $this->doActivityLog(
                    Auth::user(),
                    Auth::user(),
                    ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                    LOGNAME_IMPORT_TEACHER,
                    $message
                );
                return back()->with('successmessage',$insertedcount.' '.trans('messages.insert_success_msg'));
            }
            else
            {
                return back()->with('failmessage',trans('messages.insert_failure_msg'));
            } 
        }
        catch(Exception $e)
        {
            Log::error('TeacherImportExportController@import failed: ' . $e->getMessage());
            return back()->with('failmessage', 'Import failed due to an error. Please check your file format and try again.');
        }
    }

   public function downloadFormat(Request $request)
{
    $csv = Writer::createFromFileObject(new \SplTempFileObject());

    /*
    |--------------------------------------------------------------------------
    | HEADER (UNCHANGED - IMPORTANT FOR IMPORT COMPATIBILITY)
    |--------------------------------------------------------------------------
    */
    // to remove 'blood_group', 'ug_degree','pg_degree','additional_coures'
    $csv->insertOne([
        'firstname','lastname','mobile_no','email','gender','date_of_birth', 'address','district','region',
        'country','joining_date','employee_id','specialization','designation','notes'
    ]);

    /*
    |--------------------------------------------------------------------------
    | SAMPLE ROW (UG SIMPLIFIED)
    |--------------------------------------------------------------------------
    */
    $csv->insertOne([
        'John','Kato','256700000000','john@example.com','male','1990-05-10','Kampala Central','Kampala','Central','Uganda', '2025-01-10', 'EMP001', 'Mathematics','Head Of Department','Sample notes'
    ]);

    $filename = 'klassapp_teacher_template' . date('d-m-Y_H:i') . '.csv';
    $csv->output($filename);

    $message = 'Downloaded UG Teacher Sample Format Successfully';
exit;
    // $ip = $this->getRequestIP();

    // $this->doActivityLog(
    //     Auth::user(),
    //     Auth::user(),
    //     ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT']],
    //     LOGNAME_DOWNLOAD_SAMPLE_FORMAT_TEACHER,
    //     $message
    // );
}
     public function teacherexport(Request $request)
   {
    
     /* if(!\Session::has('headings'))
       {*/
        Session::forget('teacher_headings');
      // }
    $heads=[];
    $heads=array_values($request->headings);
    Session::put('teacher_headings', $heads);
       // dd($heads);

   }
   public function teacherexports(Request $request)
   {
    $headings=\Session::get('teacher_headings');
    $heads=array_values($headings);
    //dd($heads);
     $users = $this->TeacherFilter($request,Auth::user()->school_id,5);    
        $csv = Writer::createFromFileObject(new \SplTempFileObject());
     $default=array('employee_id','designation','name','email','mobile_no','gender','Joining_date','adhaar','blood_group','date_of_birth','address','region','district','country','pincode',);
     $result=[];
     $result = array_intersect($default, $heads);
     $result = array_map('ucfirst', $result);
     //dd($result);

        if(count($users) > 0)
        {
            $csv->insertOne($result);
      
            foreach($users as $user)
            {
                $data=[];
                if(in_array('employee_id', $heads))
                {
                    $data[]=$user->getTeacherDetails()['employee_id'];
                }
                if(in_array('designation', $heads))
                {
                    $data[]=$user->getTeacherDetails()['designation']=='others' ? $user->getTeacherDetails()['sub_designation']:$user->getTeacherDetails()['designation'];
                }
                if(in_array('name', $heads))
                {
                    $data[]=$user->FullName;
                }
                if(in_array('email', $heads))
                {
                    $data[]=$user->email;
                }
                if(in_array('mobile_no', $heads))
                {
                    $data[]=$user->mobile_no;
                }
                if(in_array('gender', $heads))
                {
                    $data[]=$user->userprofile->gender;
                }
                if(in_array('Joining_date', $heads))
                {
                    $data[]=$user->userprofile->joining_date;
                }
                 if(in_array('caste', $heads))
                {
                    $data[]=$user->userprofile->caste;
                }
                 if(in_array('adhaar', $heads))
                {
                    $data[]=$user->userprofile->aadhar_number;
                }
                 if(in_array('blood_group', $heads))
                {
                    $data[]=$user->userprofile->blood_group;
                }
                 if(in_array('date_of_birth', $heads))
                {
                    $data[]=date('d-m-Y',strtotime($user->userprofile->date_of_birth));
                }
                if(in_array('address', $heads))
                {
                    $data[]=$user->userprofile->address;
                }
                if(in_array('region', $heads))
                {
                    $data[]=$user->userprofile->city->name;
                }
                if(in_array('district', $heads))
                {
                    $data[]=$user->userprofile->state->name;
                }
                if(in_array('country', $heads))
                {
                    $data[]=$user->userprofile->country->name;
                }
                if(in_array('pincode', $heads))
                {
                    $data[]=$user->userprofile->pincode;
                }
                
                $csv->insertOne($data);
            }
        }
        else
        {
           $csv->insertOne(['No Records Found']);
           $csv->output('SP Student Export'.date('_d-m-Y_H:i').'.csv');
        }
        $csv->output('SP Student Export'.date('_d-m-Y_H:i').'.csv');
        $message= trans('messages.export_success_msg',['module' => 'Student']);

        $ip= $this->getRequestIP();
        $this->doActivityLog(
            Auth::user(),
            Auth::user(),
            ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
            LOGNAME_EXPORT_STUDENT,
            $message
        );

   }
}
