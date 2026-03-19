<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Api\Teacher;

use App\Http\Resources\API\Teacher\LessonPlan as LessonPlanResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\LogActivity;
use App\Helpers\SiteHelper;
use App\Models\LessonPlan;
use App\Traits\Common;
use Exception;
use Log;
use PDF;

class LessonPlanController extends Controller
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
        $academic_year = SiteHelper::getAcademicYear(Auth::user()->school_id);
        if(Auth::user()->hasRole('principal'))
        {
            $lessonplan = LessonPlan::with('teacherlink')->whereHas('teacherlink' , function ($query) use($academic_year)
            { 
                $query->where([
                    ['school_id',Auth::user()->school_id],
                    ['academic_year_id',$academic_year->id]
                ]);
            })->where('status','approved')->paginate('10');
        }
        else
        {
            $lessonplan = LessonPlan::with('teacherlink')->whereHas('teacherlink' , function ($query) use($academic_year)
            { 
                $query->where([
                    ['school_id',Auth::user()->school_id],
                    ['academic_year_id',$academic_year->id],
                    ['teacher_id',Auth::id()]
                ]);
            })->where('status','approved')->paginate('10');
        }
        $lessonplan = LessonPlanResource::collection($lessonplan);
        
        return $lessonplan;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function print($id)
    {
        try
        {
            $lessonplan = LessonPlan::where('id',$id)->first();

            $hour = date('H',strtotime($lessonplan->duration));
            $minutes = date('i',strtotime($lessonplan->duration));
            if($hour == '00')
            {
                $duration = $minutes.' minutes';
            }
            elseif($minutes == '00')
            {
                $duration = $hour.' hours';
            }
            else
            {
                $duration = $hour.' hours '.$minutes.' minutes';
            }

            $array['class']                 =   $lessonplan->teacherlink->standardLink->StandardSection;
            $array['subject']               =   $lessonplan->teacherlink->subject->name;
            $array['unit_no']               =   $lessonplan->unit_no;
            $array['unit_name']             =   $lessonplan->unit_name;
            $array['description']           =   $lessonplan->description;
            $array['title']                 =   $lessonplan->title;
            $array['duration']              =   $duration;
            $array['objective']             =   $lessonplan->objective;
            $array['materials_required']    =   $lessonplan->materials_required;
            $array['introduction']          =   $lessonplan->introduction;
            $array['procedure']             =   $lessonplan->procedure;
            $array['conclusion']            =   $lessonplan->conclusion;
            $array['notes']                 =   $lessonplan->notes;
            $array['assessment']            =   $lessonplan->assessment;
            $array['modification']          =   $lessonplan->modification;

            $pdf = PDF::loadView('/teacher/lessonplan/print', $array);
            
            $folder = Auth::user()->school_id.'/lessonplan';
            $filename = str_replace(' ', '_', $array['title']).'_'.$array['class'].'.pdf';

            $this->putContents($folder.'/'.$filename, $pdf->output());

            $path = $this->getFilePath($folder.'/'.$filename);

            return response()->json([
                'success'   =>  true,
                'message'   =>  'View Lesson Plan',
                'data'      =>  $path
            ],200);
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }
}