<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Requests\PromotionImportRequest;
use App\Http\Resources\Exam as ExamResource;
use App\Http\Requests\PromotionAddRequest;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use App\Imports\PromotionImport;
use App\Models\StudentAcademic;
use Illuminate\Http\Request;
use App\Models\AcademicYear;
use App\Models\StandardLink;
use App\Models\Standard;
use App\Traits\LogActivity;
use App\Helpers\SiteHelper;
use League\Csv\Writer;
use App\Traits\Common;
use App\Models\Exam;
use App\Models\User;
use SplFileObject;
use Exception;
use Log;

class PromotionController extends Controller
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
        $standardLink = SiteHelper::getStandardLinkList(Auth::user()->school_id); 

        $next_standardLink = []; 

        $curr_academic_year = SiteHelper::getAcademicYear(Auth::user()->school_id);

        $standards = Standard::where('school_id',Auth::user()->school_id)->orderBy('order')->pluck('id')->toArray();
        if(count($standards) > 0)
        {
            $standard = implode(' ,',$standards);
            $standardLinks = StandardLink::where([['school_id',Auth::user()->school_id],['academic_year_id',$curr_academic_year->id]])->orderByRaw('FIELD(standard_id,'.$standard.')')->orderBy('section_id')->groupBy(['standard_id','section_id'])->get();
            foreach ($standardLinks as $key => $standard) 
            {
                $next_standardLink[$key]['id'] = $standard->id;
                $next_standardLink[$key]['standard_section'] = $standard->StandardSection;
            }
            $count = count($standardLinks);

            $next_standardLink[$count+1]['id'] = 'alumni';
            $next_standardLink[$count+1]['standard_section'] = 'Alumni';
        }
        
        $next_academic_year = AcademicYear::where([['school_id',Auth::user()->school_id],['status',2]])->first();

        $exam = Exam::where([['school_id',Auth::user()->school_id],['academic_year_id',$curr_academic_year->id],['exam_type','final']])->get();
        $exam = ExamResource::collection($exam)->groupby('standard_id');

        $array=[];

        $array['curr_academic_yearlist']    =   $curr_academic_year;
        $array['next_academic_yearlist']    =   $next_academic_year;
        $array['next_standardLinklist']     =   $next_standardLink;
        $array['standardLinklist']          =   $standardLink;
        $array['examlist']                  =   $exam;

        return $array;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('/admin/promotion/create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function export(PromotionAddRequest $request)
    {
        //
        try
        {
            $standardLink   =   StandardLink::where('id',$request->curr_standardlink_id)->first();
            $standard_name  =   $standardLink->StandardSection;
            $exam           =   Exam::where('id',$request->exam_id)->where('standard_id',$request->curr_standardlink_id)->first();
            $users          = User::where('school_id',Auth::user()->school_id)->ByRole(6)->whereHas('studentAcademic',function ($q) use ($standardLink) 
            {
                $q->where('standardLink_id',$standardLink->id);
            })->get();
            
            if(count($users) > 0)
            { 
                $location_path   = public_path().'/uploads/promotion';
                if( ! \File::isDirectory($location_path)) 
                {
                    \File::makeDirectory($location_path,0777, true);
                }

                $file   =   'uploads/promotion/promotionlist'.$standard_name.date('_d-m-Y_H_i_s').'.csv';
                $file_open = fopen($file, 'w');

                fputcsv($file_open,['roll_number','student_name','academic_status','comments']);
      
                foreach($users as $user)
                { 
                    $studentAcademic = StudentAcademic::where([['user_id',$user->id],['school_id',Auth::user()->school_id],['academic_year_id',$request->curr_academic_year_id]])->first();
                    $csv_file=[];

                    $csv_file[] = $studentAcademic->roll_number;
                    $csv_file[] = $user->userprofile->firstname.' '.$user->userprofile->lastname;
                    $csv_file[] = 'P';
                    $csv_file[] = '';
                
                    fputcsv($file_open,$csv_file);
                }
                $res['path'] = url($file);
            }
            else
            {
                $res['success'] = "No Students Found";
            }
            return $res;

            $message=('Student Promotionlist Exported Successfully');

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                Auth::user(),
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_EXPORT_STUDENT_PROMOTION,
                $message
            );

        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    public function import(PromotionImportRequest $request)
    { 
        try
        {
            \Session::put('exam_id',$request->exam_id);
            \Session::put('curr_academic_year_id',$request->curr_academic_year_id);
            \Session::put('next_academic_year_id',$request->next_academic_year_id);
            \Session::put('curr_standardlink_id',$request->curr_standardlink_id);
            \Session::put('next_standardlink_id',$request->next_standardlink_id);

            $path = $request->file('promotion_file'); 
            Excel::import(new PromotionImport,$path);

            $insertedcount = \Session::get('insertedcount');
            if($insertedcount > 0)
            {
                $message=('Student Promotion Details Imported Successfully');

                $ip= $this->getRequestIP();
                $this->doActivityLog(
                    Auth::user(),
                    Auth::user(),
                    ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                    LOGNAME_IMPORT_STUDENT_PROMOTION,
                    $message
                );
                $res['success'] = $insertedcount.' '.trans('messages.insert_success_msg');
                
            }
            else
            {
              $res['success'] = trans('messages.insert_failure_msg');
            }

            \Session::forget('insertedcount');
            \Session::forget('exam_id');
            \Session::forget('curr_academic_year_id');
            \Session::forget('next_academic_year_id');
            \Session::forget('curr_standardlink_id');
            \Session::forget('next_standardlink_id'); 
            return $res;
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }
}