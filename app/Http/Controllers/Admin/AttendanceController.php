<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Resources\AttendanceStudentList as AttendanceStudentListResource;
use App\Http\Resources\StandardLink as StandardLinkResource;
use App\Http\Resources\Attendance as AttendanceResource;
use App\Http\Requests\AttendanceAddRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Traits\AcademicProcess;
use App\Models\StudentAcademic;
use Illuminate\Http\Request;
use App\Models\StandardLink;
use App\Models\AbsentReason;
use App\Traits\LogActivity;
use App\Helpers\SiteHelper;
use App\Models\Attendance;
use App\Models\Section;
use App\Services\ClassStructureService;
use League\Csv\Writer;
use App\Traits\Common;
use Carbon\Carbon;
use Exception;
use Log;

class AttendanceController extends Controller
{
    use AcademicProcess;
    use LogActivity;
    use Common;

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list()
    {
        //
        $array = [];
        $school_id = Auth::user()->school_id;

        $academic_year = SiteHelper::getAcademicYear($school_id);

        $standardLinklist = SiteHelper::getStandardLinkList($school_id);

        //$studentAcademic = StudentAcademic::with('user')->where([['school_id',$school_id],['academic_year_id',$academic_year->id]])->get();
        $studentAcademic = StudentAcademic::with('user.userprofile')->where([['school_id',$school_id],['academic_year_id',$academic_year->id]])->whereHas('user', function($q){
                        $q->where([['status','active'],['deleted_at',null]]);
                    })->get();

        $studentlist = $this->studentsByClass($studentAcademic, (int) $school_id, (int) $academic_year->id);

        $absentReasonlist   = AbsentReason::where('status',1)->get();

        $array['standardlist']      = $standardLinklist;
        $array['studentlist']       = $studentlist;
        $array['absentReasonlist']  = $absentReasonlist;

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
        $standard = \Request::get('standardLink_id') ? \Request::get('standardLink_id'):'';

        return view('/admin/attendance/create' ,['standard' => $standard]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(AttendanceAddRequest $request)
    { 
        try
        {
            $school_id      = Auth::user()->school_id;
            $academic_year  = SiteHelper::getAcademicYear($school_id);
            $admin          = Auth::id();

            if (!$academic_year) {
                return response()->json(['error' => 'Academic year not set'], 422);
            }

            $attendance = $this->createAttendance($school_id , $academic_year->id , $admin , $request);

            $message = trans('messages.add_success_msg',['module' => 'Attendance']);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                $attendance,
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_ADD_ATTENDANCE,
                $message
            );

            return response()->json([
                'success' => $message
            ]);
        }
        catch(Exception $e)
        {
            Log::error($e->getMessage());
            // Log the real cause server-side; never return it to the client. The raw
            // exception message previously reached the browser, including "SQLSTATE[23000]
            // Integrity constraint violation" and the failing statement.
            Log::error('Attendance store failed', [
                'user_id' => Auth::id(),
                'standardLink_id' => $request->input('standardLink_id'),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'We could not save this attendance. Please check the details and try again.',
            ], 422);
        }
    }

    public function export($standardLink_id)
    {
        try
        {
            //
            $school_id      = Auth::user()->school_id;
            $academic_year = SiteHelper::getAcademicYear($school_id);
            $standardLink = StandardLink::where('id',$standardLink_id)->first();
            $standard = $standardLink->StandardName;
            $section = $standardLink->section->name;
            $csv_name = 'SP Student Attendance Export_'.$standard.'_'.$section.'_'.date('_d-m-Y_H:i').'.csv';
            $attendances  = Attendance::where([
                ['school_id',$school_id],
                ['academic_year_id',$academic_year->id],
                ['standardLink_id',$standardLink_id],
                ['status',0]
            ])->orderBy('date','DESC')->get()->groupBy([function($attendance) {
                    return Carbon::parse($attendance->date)->format('d-m-Y'); 
                },'session']);
            $csv = Writer::createFromFileObject(new \SplTempFileObject());

            if(count($attendances) > 0)
            {
                $csv->insertOne(['Date','Forenoon_Absent_Count','Afternoon_Absent_Count']);
          
                $i = 0;
                foreach ($attendances as $key => $attendance) 
                {
                    foreach ($attendance as $key1 => $student) 
                    { 
                        if($key1 == 'forenoon')
                        {
                            if($student != null)
                            {
                                $forenoon_count[$i] = count($student);
                            }
                            else
                            {
                                $forenoon_count[$i] = 0;
                            }
                        }
                        else
                        {
                            if($student != null)
                            {
                                $afternoon_count[$i] = count($student);
                            }
                            else
                            {
                                $afternoon_count[$i] = 0;
                            }
                        }
                    }
                    $csv->insertOne([ $key, $forenoon_count[$i] , $afternoon_count[$i] ]);
                    $i++;
                }
            }
            else
            {
               $csv->insertOne(['No Records Found']);
               $csv->output($csv_name);
            }
            $csv->output($csv_name);
            $message= trans('messages.export_success_msg',['module' => 'Student Attendance']);

            $ip= $this->getRequestIP();
            $this->doActivityLog(
                Auth::user(),
                Auth::user(),
                ['ip' => $ip, 'details' => $_SERVER['HTTP_USER_AGENT'] ],
                LOGNAME_EXPORT_STUDENT_ATTENDANCE,
                $message
            );
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function student()
    {
        //
        $school_id      = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);
        $standardLinklist = SiteHelper::getStandardLinkList($school_id);
        $absentees = Attendance::ByRole(6)->where([
                        ['school_id',$school_id],
                        ['academic_year_id',$academic_year->id],
                        ['date',date('Y-m-d')],
                        ['status',0]
                    ]);
        $count=$absentees->count();
        $standards=$absentees->distinct('standardLink_id')->pluck('standardLink_id')->toArray();
        //dd($standards);
        $standardLink = StandardLink::where([['school_id',$school_id],['academic_year_id',$academic_year->id]])->whereIn('id',$standards)->get();
        $standardLinklist=StandardLinkResource::collection($standardLink);

        return [ 'studentAbsentees' => $count, 'standardLinklist'=>$standardLinklist ];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function studentList(Request $request)
    {
        //
        $school_id      = Auth::user()->school_id;
        $academic_year = SiteHelper::getAcademicYear($school_id);
        $absentees = Attendance::ByRole(6)->where([
                        ['school_id',$school_id],
                        ['academic_year_id',$academic_year->id],
                        ['date',date('Y-m-d')],
                        ['status',0],
                        ['session',$request->session]
                    ]);
        if($request->standardLink_id!='')
        {
            $absentees=$absentees->where('standardLink_id',$request->standardLink_id);
        }
        $absentees=$absentees->get();

        $attendance = AttendanceResource::collection($absentees);

        return $attendance;
    }

    // =============== attendance records ===============================
   public function index(Request $request)
{
    $school_id = Auth::user()->school_id;
    $academic_year = SiteHelper::getAcademicYear($school_id);

    $date    = $request->date ?? now()->format('Y-m-d');
    $session = $request->session;
    $section = $request->section;
    $classes = Section::where("school_id", $school_id)->get();
    // dd($section);

    $standardLink_id = $request->standardLink_id;

    // Get Standards for dropdown
    $standardlist = SiteHelper::getStandardLinkList($school_id);

    $sectionIds = $this->sectionIdsForFilter((int) $school_id, $section);

    // Get Attendance Records
    $attendances = Attendance::with(['user', 'absentReason', 'standardLink'])
        ->where('school_id', $school_id)
        ->whereHas("user", function($q){
            $q->where("usergroup_id", 6);
        })
        ->whereDate('date', $date)
        ->when($sectionIds !== null, function ($q) use ($sectionIds, $school_id) {
            $q->whereHas('standardLink', function ($link) use ($sectionIds, $school_id) {
                $link->where('school_id', $school_id)->whereIn('section_id', $sectionIds);
            });
        })
        ->when($session, function($q) use ($session) {
            return $q->where('session', $session);
        })
        ->orderBy('id')
        ->get();
        // dd($attendances);
        return view('admin.attendance.index', compact(
        'attendances', 
        'standardlist', 
        'date', 
        'session', 
        'classes',
        'section'
    ));
}

    /**
     * Students keyed by standard-link id. A base class includes every stream's
     * students, because learners are enrolled on the stream sections.
     *
     * @param  \Illuminate\Support\Collection<int, StudentAcademic>  $studentAcademic
     * @return array<int, list<array<string, mixed>>>
     */
    private function studentsByClass($studentAcademic, int $schoolId, int $yearId): array
    {
        $grouped = [];
        foreach ($studentAcademic as $academic) {
            $grouped[(int) $academic->standardLink_id][] = (new AttendanceStudentListResource($academic))->resolve(request());
        }

        $links = StandardLink::with('section')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $yearId)
            ->get();
        $bySection = $links->groupBy(fn (StandardLink $link) => (int) $link->section_id);
        $classes = app(ClassStructureService::class);

        foreach ($links as $link) {
            $section = $link->section;
            if (! $section || (int) $section->school_id !== $schoolId || ! $classes->isBaseSection($section)) {
                continue;
            }

            $seen = array_map(
                fn (array $row) => (int) ($row['user_id'] ?? 0),
                $grouped[(int) $link->id] ?? []
            );
            foreach ($classes->classSectionIds($section) as $sectionId) {
                if ($sectionId === (int) $section->id) {
                    continue;
                }
                foreach ($bySection->get($sectionId, collect()) as $streamLink) {
                    foreach ($grouped[(int) $streamLink->id] ?? [] as $row) {
                        $userId = (int) ($row['user_id'] ?? 0);
                        if ($userId === 0 || in_array($userId, $seen, true)) {
                            continue;
                        }
                        $copy = $row;
                        $copy['standardLink_id'] = (int) $link->id;
                        $grouped[(int) $link->id][] = $copy;
                        $seen[] = $userId;
                    }
                }
            }
        }

        return $grouped;
    }

    /**
     * A base class filter covers its streams. An empty filter stays unscoped
     * so "All Classes" lists the school's records. A stream stays that stream.
     *
     * @return list<int>|null
     */
    private function sectionIdsForFilter(int $schoolId, mixed $section): ?array
    {
        if ($section === null || $section === '') {
            return null;
        }

        $chosen = Section::query()->where('school_id', $schoolId)->whereKey($section)->first();
        if ($chosen === null) {
            return [(int) $section];
        }

        $classes = app(ClassStructureService::class);
        if ($classes->isBaseSection($chosen)) {
            return $classes->classSectionIds($chosen);
        }

        return [(int) $chosen->id];
    }
}