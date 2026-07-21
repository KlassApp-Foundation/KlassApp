<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Requests\StandardRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Traits\AcademicProcess;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Traits\LogActivity;
use App\Models\Standard;
use App\Traits\Common;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Services\AcademicSetupService;

class StandardController extends Controller
{

// constructor dependency injection 
protected $academicSetupService;
    public function __construct(AcademicSetupService $academicSetupService){
        $this->academicSetupService = $academicSetupService;
    }

    use AcademicProcess;
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
        $school_id = Auth::user()->school_id;
        $standards = Standard::where('school_id', $school_id)->orderBy('order')->get();
        // $standards = DB::table("standards")->where("school_id", $school_id)->get();
//         dd(
//     DB::table('standards')
//         ->where('school_id', $school_id)
//             ->orderBy('order')
//         ->get(['id', 'name', 'order', 'school_id', 'created_at'])
// );
//         dd($standards);
        
        return view('admin.school.standards.index', ['standards' => $standards]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StandardRequest $request)
    { 
        //
        try
        {
            $school_id = Auth::user()->school_id;
        //   dd($request);
            $standards = $this->createStandard($school_id , $request);
            // add default subjects@UG
            foreach ($standards as $standard){
                $this->academicSetupService->defaultClassesAndSubjects($standard);
            }
            $message = trans('messages.add_success_msg',['module' => 'Standard']);

            foreach ($standards as $standard){
               $ip= $this->getRequestIP();
            $this->doActivityLog(
                $standard,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_ADD_STANDARD,
                $message
            );
            }

            

            return ['success' => $message];
        }
        catch(Exception $e)
        {
            //dd($e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $school = Auth::user()->school;
        $academic_year = SiteHelper::getAcademicYear(Auth::user()->school_id);
        $board = ["uneb", "cambridge", "ib", "montessori", "other"];
        $standards = ["nursery", "primary", "o-level", "a-level"];
// ['academic_year_id' => $academic_year->id]
// compact("board", "academic_year")
        return view('admin.school.standards.add', [
            'academic_year_id' => $academic_year->id,
            'current_board' => $school->curriculum ?? '',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function add(Request $request)
    { 
        //
        try
        {
            $school_id = Auth::user()->school_id;
            $standards = $this->addStandard($school_id , $request);
            // add default subjects@UG
            foreach ($standards as $standard){
                $this->academicSetupService->defaultClassesAndSubjects($standard);
            }
            
            $message = trans('messages.standard_setup_success_msg');

            foreach($standards as $standard){
                $ip= $this->getRequestIP();
            $this->doActivityLog(
                $standard,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_ADD_STANDARD_SETUP,
                $message
            );
            }

             return ['success' => $message];
        }
        catch(Exception $e)
        {
            dd($e->getMessage());
        }
    }

    // =========== UG update, added delete =============
    // public function destroy($standardId){
    //     $admin = Auth::user();
    //     Standard::softDeleted()
    // }
}