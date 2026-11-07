<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Teacher;

use App\Http\Resources\Studentlist as StudentlistResource;
use App\Http\Resources\StandardLink as StandardLinkResource;
use App\Http\Requests\AttendanceAddRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Traits\AcademicProcess;
use App\Models\StudentAcademic;
use Illuminate\Http\Request;
use App\Models\StandardLink;
use App\Models\Section;
use App\Models\AbsentReason;
use App\Traits\LogActivity;
use App\Helpers\SiteHelper;
use App\Models\Attendance;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use League\Csv\Writer;
use App\Traits\Common;
use Carbon\Carbon;
use Exception;

class AttendanceController extends Controller
{
    //
    use AcademicProcess;
    use LogActivity;
    use Common;

    /**
     * Attendance overview for this teacher (view: teacher/attendance/index).
     *
     * The class list and every record are bounded by the school's attendance_scope, so this
     * page shows exactly what the teacher is allowed to record against. It deliberately does
     * not assume school-wide access: under class_teacher_only it lists homeroom classes only,
     * under classes_i_teach it adds the classes they are assigned as a subject teacher.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $schoolId = (int) Auth::user()->school_id;
        $teacherId = (int) Auth::id();
        $academicYear = SiteHelper::getAcademicYear($schoolId);

        $links = SiteHelper::attendanceScopeStandardLinks($schoolId, $teacherId);
        $linkIds = $links->pluck('id')->map(fn ($id) => (int) $id)->all();

        $sections = $links->pluck('section')->filter()->unique('id')->sortBy('name')->values();
        $streams = $links->pluck('stream')->filter()->unique()->sort()->values();

        $selectedSection = $request->integer('section_id') ?: null;
        $selectedStream = trim((string) $request->input('stream', ''));
        $selectedDate = $request->input('date', now()->format('Y-m-d'));

        $records = ($academicYear && ! empty($linkIds))
            ? Attendance::query()
                ->with(['user.studentAcademicLatest', 'standardLink.section'])
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $academicYear->id)
                ->whereIn('standardLink_id', $linkIds)
                ->where('date', $selectedDate)
                ->when($selectedSection, function ($q) use ($selectedSection) {
                    $q->whereHas('standardLink', fn ($s) => $s->where('section_id', $selectedSection));
                })
                ->when($selectedStream !== '', function ($q) use ($selectedStream) {
                    $q->whereHas('standardLink', fn ($s) => $s->where('stream', $selectedStream));
                })
                ->orderByDesc('id')
                ->get()
            : collect();

        return view('teacher.attendance.index', [
            'records' => $records,
            'sections' => $sections,
            'streams' => $streams,
            'selectedSection' => $selectedSection,
            'selectedStream' => $selectedStream,
            'selectedDate' => $selectedDate,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list()
{
    $school_id = Auth::user()->school_id;
    $teacher_id = (int) Auth::id();
    $academic_year = SiteHelper::getAcademicYear($school_id);

    if (!$academic_year) {
        return [
            'standardlist'     => [],
            'studentlist'      => [],
            'absentReasonlist' => [],
        ];
    }

    // Same scope as Api\Teacher\AttendanceController@index: one shared helper reads the
    // school's attendance_scope so the web and API listings cannot diverge.
    $classTeacherLinks = SiteHelper::attendanceScopeStandardLinks((int) $school_id, $teacher_id);
    $linkIds = $classTeacherLinks->pluck('id')->map(fn ($id) => (int) $id)->all();

    $standardLinklist = StandardLinkResource::collection($classTeacherLinks);

    $studentAcademic = StudentAcademic::with('user')
        ->where('school_id', $school_id)
        ->where('academic_year_id', $academic_year->id)
        ->when($linkIds !== [], fn ($q) => $q->whereIn('standardLink_id', $linkIds), fn ($q) => $q->whereRaw('1 = 0'))
        ->whereHas('user', function($q) {
            $q->where('status', 'active')
              ->whereNull('deleted_at');
        })
        ->get();

    $studentlist = [];
    $std_id = null;

    foreach ($studentAcademic as $student) {
        $std_id = $student->standardLink_id;
        if (!isset($studentlist[$std_id])) {
            $studentlist[$std_id] = [];
        }
        $profile = optional(optional($student->user)->userprofile);
        $studentlist[$std_id][] = [
            'user_id'        => $student->user_id,
            'id'             => $student->id,
            'name'           => $profile->firstname && $profile->lastname
                                    ? $profile->firstname . ' ' . $profile->lastname
                                    : ($student->user->name ?? 'No Name'),
            'avatar'         => $profile->AvatarPath ?? null,
            'standardLink_id'=> $student->standardLink_id,
        ];
    }

    $absentReasonlist = AbsentReason::where('status', 1)->get();

    return [
        'standardlist'     => $standardLinklist,
        'studentlist'      => $studentlist,
        'absentReasonlist' => $absentReasonlist,
        'std_id' => $std_id,
        'studentAcademic' => $studentAcademic
    ];
}

    public function create()
    {
        $standard = request()->input('standardLink_id', '');
        return view('/teacher/attendance/create' ,['standard' => $standard]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(AttendanceAddRequest $request)
{ 
    // Authorization runs BEFORE the try/catch: the catch below turns every Exception,
    // HttpException included, into a generic 422, so an abort(403) inside the try would
    // be silently swallowed. This check must therefore live outside it.
    if (! SiteHelper::canTeacherRecordAttendance(
        (int) Auth::user()->school_id,
        (int) Auth::id(),
        (int) $request->standardLink_id
    )) {
        abort(403, 'You are not allowed to record attendance for this class.');
    }

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

        $ip = $this->getRequestIP();
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
        // Log the real cause server-side; never return it to the client. The raw
        // exception message previously reached the browser, SQLSTATE strings included.
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

    /**
     * One-click register: every student in the class or stream, Present by
     * default, one tap to mark Absent, an optional comment, one save.
     *
     * A class head (sections.class_teacher_id) gets every stream of the
     * section. A stream teacher gets the streams the school's attendance
     * scope already allows; under classes_i_teach that includes the streams
     * they subject-teach, never another teacher's class.
     */
    public function register(Request $request)
    {
        $schoolId = (int) Auth::user()->school_id;
        $teacherId = (int) Auth::id();
        $scope = $this->registerScope($schoolId, $teacherId);

        $selectedSection = $request->integer('section_id') ?: null;
        $selectedStream = $request->integer('standardLink_id') ?: null;
        $selectedSession = (string) $request->input('session', 'forenoon');
        if (! in_array($selectedSession, ['forenoon', 'afternoon'], true)) {
            $selectedSession = 'forenoon';
        }

        $selectedDate = (string) $request->input('date', now()->format('Y-m-d'));
        $date = null;
        try {
            $date = Carbon::createFromFormat('Y-m-d', $selectedDate)->startOfDay();
        } catch (\Throwable) {
            $date = null;
        }
        if ($date === null || $date->gt(Carbon::today())) {
            $date = Carbon::today();
        }
        if ($scope['year'] !== null && $date->lt(Carbon::parse($scope['year']->start_date)->startOfDay())) {
            $date = Carbon::today();
        }
        $selectedDate = $date->toDateString();

        $selection = $this->resolveRegisterSelection($scope, $selectedSection, $selectedStream);
        $students = $this->registerRoster($scope, $selection['streams'], $selectedDate, $selectedSession);

        $present = $students->where('status', 1)->count();
        $absent = $students->where('status', 0)->count();

        return view('teacher/attendance/register', [
            'scopeReady' => $scope['year'] !== null,
            'sections' => $scope['sections'],
            'streams' => $scope['streams'],
            'selectedSection' => $selection['section']?->id,
            'selectedStream' => $selection['streams']->count() === 1 ? $selection['streams']->first()->id : null,
            'selectedDate' => $selectedDate,
            'selectedSession' => $selectedSession,
            'className' => $selection['section']?->name,
            'isClassHead' => $selection['is_class_head'],
            'students' => $students,
            'presentCount' => $present,
            'absentCount' => $absent,
        ]);
    }

    /**
     * Save the whole register once. A second save updates the same rows
     * instead of creating duplicates.
     */
    public function saveRegister(Request $request)
    {
        $schoolId = (int) Auth::user()->school_id;
        $teacherId = (int) Auth::id();

        // A class head can have a thousand learners. The browser sends the
        // register as one JSON field so PHP's max_input_vars cannot truncate
        // it; the per-row inputs stay as the no-JavaScript fallback.
        if ($request->filled('rows_json')) {
            $decoded = json_decode((string) $request->input('rows_json'), true);
            if (is_array($decoded)) {
                $request->merge(['rows' => $decoded]);
            }
        }

        $validated = $request->validate([
            'section_id' => ['nullable', 'integer'],
            'standardLink_id' => ['nullable', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'session' => ['required', 'in:forenoon,afternoon'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.status' => ['required', 'in:0,1'],
            'rows.*.comment' => ['nullable', 'string', 'max:500'],
        ]);

        $scope = $this->registerScope($schoolId, $teacherId);
        if ($scope['year'] === null) {
            throw ValidationException::withMessages([
                'date' => 'Set up the academic year before recording attendance.',
            ]);
        }

        $date = Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay();
        $yearStart = Carbon::parse($scope['year']->start_date)->startOfDay();
        if ($date->lt($yearStart) || $date->gt(Carbon::today())) {
            throw ValidationException::withMessages([
                'date' => 'Choose a date within this academic year, up to today.',
            ]);
        }

        $selection = $this->resolveRegisterSelection(
            $scope,
            isset($validated['section_id']) ? (int) $validated['section_id'] : null,
            isset($validated['standardLink_id']) ? (int) $validated['standardLink_id'] : null,
        );

        if ($selection['streams']->isEmpty()) {
            abort(403, 'You are not allowed to record attendance for this class.');
        }

        $students = $this->registerRoster($scope, $selection['streams'], $validated['date'], $validated['session']);
        $rosterIds = $students->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        $postedIds = array_map('intval', array_keys($validated['rows']));
        if (array_diff($postedIds, $rosterIds) !== []) {
            abort(403, 'One of those students is not in this class.');
        }

        $rows = $validated['rows'];
        $saved = 0;
        $first = null;

        DB::transaction(function () use ($students, $rows, $scope, $date, $validated, $teacherId, &$saved, &$first): void {
            foreach ($students as $student) {
                $row = $rows[$student['user_id']] ?? null;
                if ($row === null) {
                    continue;
                }

                $comment = trim((string) ($row['comment'] ?? ''));
                $attributes = [
                    'status' => (int) $row['status'],
                    'remarks' => $comment !== '' ? $comment : null,
                    'reason_id' => null,
                    'recorded_by' => $teacherId,
                ];

                $attendance = Attendance::withTrashed()
                    ->where('school_id', (int) $scope['year']->school_id)
                    ->where('academic_year_id', (int) $scope['year']->id)
                    ->where('standardLink_id', (int) $student['stream_id'])
                    ->where('user_id', (int) $student['user_id'])
                    ->where('session', $validated['session'])
                    ->whereDate('date', $date->toDateString())
                    ->orderByDesc('id')
                    ->first();

                if ($attendance !== null) {
                    if ($attendance->trashed()) {
                        $attendance->restore();
                    }
                    $attendance->fill($attributes)->save();
                } else {
                    $attendance = Attendance::create(array_merge($attributes, [
                        'school_id' => (int) $scope['year']->school_id,
                        'academic_year_id' => (int) $scope['year']->id,
                        'standardLink_id' => (int) $student['stream_id'],
                        'user_id' => (int) $student['user_id'],
                        'date' => $date->toDateString(),
                        'session' => $validated['session'],
                    ]));
                }

                $saved++;
                $first ??= $attendance;
            }
        });

        if ($saved === 0) {
            return back()->with('errormessage', 'No students were saved. Check the class and try again.');
        }

        if ($first !== null) {
            $this->doActivityLog(
                $first,
                Auth::user(),
                ['ip' => $this->getRequestIP(), 'details' => request()->userAgent() ?? ''],
                LOGNAME_ADD_ATTENDANCE,
                'Attendance saved for '.$saved.' students',
            );
        }

        $params = [
            'date' => $date->toDateString(),
            'session' => $validated['session'],
        ];
        if ($selection['streams']->count() === 1) {
            $params['standardLink_id'] = $selection['streams']->first()->id;
        } elseif ($selection['section'] !== null) {
            $params['section_id'] = $selection['section']->id;
        }

        return redirect()->route($this->registerRouteName(), $params)
            ->with('successmessage', 'Attendance saved for '.$saved.' '.(($saved === 1) ? 'student' : 'students').'.');
    }

    /**
     * Admins and class heads use the same register. A save from the admin
     * address stays on that address.
     */
    private function registerRouteName(): string
    {
        $name = (string) optional(request()->route())->getName();

        return str_starts_with($name, 'admin.attendance.register')
            ? 'admin.attendance.register'
            : 'teacher.attendance.register';
    }

    /**
     * School admins and a class head (sections.class_teacher_id) may open any
     * class or stream in their own school. A stream teacher keeps the
     * attendance_scope list.
     */
    private function mayTakeAnyRegister(int $schoolId, int $userId): bool
    {
        $user = Auth::user();
        if ($user === null || (int) $user->id !== $userId || (int) $user->school_id !== $schoolId) {
            return false;
        }

        if (in_array((int) $user->usergroup_id, [3, 4], true)) {
            return true;
        }

        if ((int) $user->usergroup_id !== 5) {
            return false;
        }

        return Section::query()
            ->where('school_id', $schoolId)
            ->where('class_teacher_id', $userId)
            ->where(function ($query) {
                $query->where('status', 1)->orWhere('status', '1');
            })
            ->exists();
    }

    /**
     * The streams this teacher may take the register for.
     *
     * @return array{year: ?\App\Models\AcademicYear, streams: \Illuminate\Support\Collection, sections: \Illuminate\Support\Collection, head_section_ids: list<int>}
     */
    private function registerScope(int $schoolId, int $teacherId): array
    {
        $year = SiteHelper::getAcademicYear($schoolId);
        if (! $year) {
            return ['year' => null, 'streams' => collect(), 'sections' => collect(), 'head_section_ids' => []];
        }

        if ($this->mayTakeAnyRegister($schoolId, $teacherId)) {
            $streams = StandardLink::query()
                ->with(['section', 'standard'])
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $year->id)
                ->where(function ($query) {
                    $query->where('status', 1)->orWhere('status', '1');
                })
                ->get()
                ->sortBy(fn (StandardLink $link) => trim((string) ($link->section?->name ?? '')).'|'.trim((string) ($link->stream ?? '')))
                ->values();

            $sections = $streams->pluck('section')->filter()->unique('id')->sortBy('name')->values();

            return [
                'year' => $year,
                'streams' => $streams,
                'sections' => $sections,
                'head_section_ids' => $sections->pluck('id')->map(fn ($id) => (int) $id)->all(),
            ];
        }

        $allowed = SiteHelper::attendanceScopeStandardLinks($schoolId, $teacherId);

        $headSectionIds = Section::query()
            ->where('school_id', $schoolId)
            ->where('class_teacher_id', $teacherId)
            ->where('status', 1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $headStreams = $headSectionIds === [] ? collect() : StandardLink::query()
            ->with(['section', 'standard'])
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('status', 1)
            ->whereIn('section_id', $headSectionIds)
            ->get();

        $streams = $allowed->merge($headStreams)
            ->unique('id')
            ->values()
            ->sortBy(fn (StandardLink $link) => trim((string) ($link->section?->name ?? '')).'|'.trim((string) ($link->stream ?? '')))
            ->values();

        $sections = $streams->pluck('section')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        return [
            'year' => $year,
            'streams' => $streams,
            'sections' => $sections,
            'head_section_ids' => $headSectionIds,
        ];
    }

    /**
     * @param  array{year: ?\App\Models\AcademicYear, streams: \Illuminate\Support\Collection, sections: \Illuminate\Support\Collection, head_section_ids: list<int>}  $scope
     * @return array{section: ?\App\Models\Section, streams: \Illuminate\Support\Collection, is_class_head: bool}
     */
    private function resolveRegisterSelection(array $scope, ?int $sectionId, ?int $standardLinkId): array
    {
        $schoolId = (int) ($scope['year']->school_id ?? 0);
        if ($schoolId > 0 && $standardLinkId !== null && $standardLinkId > 0) {
            $linkSchool = StandardLink::query()->whereKey($standardLinkId)->value('school_id');
            if ($linkSchool !== null && (int) $linkSchool !== $schoolId) {
                abort(403, 'You are not allowed to record attendance for this class.');
            }
        }
        if ($schoolId > 0 && $sectionId !== null && $sectionId > 0) {
            $sectionSchool = Section::query()->whereKey($sectionId)->value('school_id');
            if ($sectionSchool !== null && (int) $sectionSchool !== $schoolId) {
                abort(403, 'You are not allowed to record attendance for this class.');
            }
        }

        $streams = $scope['streams'];
        if ($streams->isEmpty()) {
            return ['section' => null, 'streams' => collect(), 'is_class_head' => false];
        }

        if ($standardLinkId !== null && $standardLinkId > 0) {
            $stream = $streams->firstWhere('id', $standardLinkId);
            if ($stream === null) {
                abort(403, 'You are not allowed to record attendance for this class.');
            }

            return [
                'section' => $stream->section,
                'streams' => collect([$stream]),
                'is_class_head' => in_array((int) $stream->section_id, $scope['head_section_ids'], true),
            ];
        }

        if ($sectionId !== null && $sectionId > 0) {
            $section = $scope['sections']->firstWhere('id', $sectionId);
            if ($section === null) {
                abort(403, 'You are not allowed to record attendance for this class.');
            }

            $isClassHead = in_array((int) $section->id, $scope['head_section_ids'], true);
            $sectionStreams = $isClassHead
                ? StandardLink::query()
                    ->with(['section', 'standard'])
                    ->where('school_id', (int) $section->school_id)
                    ->where('academic_year_id', (int) $scope['year']->id)
                    ->where('status', 1)
                    ->where('section_id', (int) $section->id)
                    ->orderBy('stream')
                    ->get()
                : $streams->where('section_id', (int) $section->id)->values();

            return ['section' => $section, 'streams' => $sectionStreams, 'is_class_head' => $isClassHead];
        }

        $headSectionId = $scope['head_section_ids'][0] ?? null;
        if ($headSectionId !== null && $scope['sections']->contains('id', $headSectionId)) {
            return $this->resolveRegisterSelection($scope, (int) $headSectionId, null);
        }

        return $this->resolveRegisterSelection($scope, null, (int) $streams->first()->id);
    }

    /**
     * @param  array{year: ?\App\Models\AcademicYear, streams: \Illuminate\Support\Collection, sections: \Illuminate\Support\Collection, head_section_ids: list<int>}  $scope
     * @param  \Illuminate\Support\Collection  $streams
     * @return \Illuminate\Support\Collection<int, array{user_id: int, name: string, stream_id: int, stream: string, status: int, comment: string}>
     */
    private function registerRoster(array $scope, $streams, string $date, string $session)
    {
        $streamIds = $streams->pluck('id')->map(fn ($id) => (int) $id)->all();

        $students = StudentAcademic::query()
            ->with(['user.userprofile', 'standardLink.section'])
            ->where('school_id', (int) $scope['year']->school_id)
            ->where('academic_year_id', (int) $scope['year']->id)
            ->whereIn('standardLink_id', $streamIds)
            ->whereHas('user', function ($query) {
                $query->where('usergroup_id', 6)
                    ->where('status', 'active')
                    ->whereNull('deleted_at');
            })
            ->get();

        $existing = Attendance::query()
            ->where('school_id', (int) $scope['year']->school_id)
            ->where('academic_year_id', (int) $scope['year']->id)
            ->whereIn('standardLink_id', $streamIds)
            ->where('session', $session)
            ->whereDate('date', $date)
            ->orderByDesc('id')
            ->get()
            ->keyBy('user_id');

        return $students
            ->unique('user_id')
            ->map(function (StudentAcademic $row) use ($existing) {
                $profile = optional(optional($row->user)->userprofile);
                $name = trim((string) ($profile?->firstname ?? '').' '.(string) ($profile?->lastname ?? ''));
                if ($name === '') {
                    $name = trim((string) ($row->user->name ?? '')) ?: 'Student';
                }

                $record = $existing->get($row->user_id);
                $stream = trim((string) ($row->standardLink?->section?->name ?? '').' '.(string) ($row->standardLink?->stream ?? ''));

                return [
                    'user_id' => (int) $row->user_id,
                    'name' => $name,
                    'stream_id' => (int) $row->standardLink_id,
                    'stream' => $stream !== '' ? $stream : 'No stream',
                    'status' => $record ? (int) $record->status : 1,
                    'comment' => (string) ($record->remarks ?? ''),
                ];
            })
            ->sortBy(fn (array $row) => $row['stream'].'|'.$row['name'])
            ->values();
    }

    public function export($standardLink_id)
    {
        // Same swallow hazard as store(): abort() inside this try would be converted to
        // a generic response by the catch below. The scope check must run before it.
        if (! SiteHelper::canTeacherRecordAttendance(
            (int) Auth::user()->school_id,
            (int) Auth::id(),
            (int) $standardLink_id
        )) {
            abort(403, 'You are not allowed to export attendance for this class.');
        }

        try
        {
            //
            $school_id      = Auth::user()->school_id;
            $academic_year = SiteHelper::getAcademicYear($school_id);

$standardLink = StandardLink::query()
                ->where('school_id', $school_id)
                ->where('academic_year_id', $academic_year->id)
                ->where('status', 1)
                ->findOrFail($standardLink_id);
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
                            $forenoon_count[$i] = count($student);
                        }
                        else
                        {
                            $afternoon_count[$i] = count($student);
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
            //dd($e->getMessage());
        }
    }
}
