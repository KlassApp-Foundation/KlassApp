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
use App\Services\ClassStructureService;
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

        $registerUrl = route('teacher.attendance.register');
        $headed = $this->headedBaseIds($schoolId, $teacherId);
        if ($headed !== []) {
            $registerUrl = route('teacher.attendance.register', ['section_id' => $headed[0]]);
        }

        return view('teacher.attendance.index', [
            'records' => $records,
            'sections' => $sections,
            'streams' => $streams,
            'selectedSection' => $selectedSection,
            'selectedStream' => $selectedStream,
            'selectedDate' => $selectedDate,
            'registerUrl' => $registerUrl,
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
     * A class head gets every stream of the base they head, and no other
     * class. A stream teacher gets the streams the school's attendance
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
        $route = $this->registerRouteName();

        return view('teacher/attendance/register', [
            'scopeReady' => $scope['year'] !== null,
            'sections' => $selection['classes'],
            'streamChoices' => $selection['stream_choices'],
            'selectedSection' => $selection['section']?->id,
            'selectedStream' => $selection['single_stream_id'],
            'selectedDate' => $selectedDate,
            'selectedSession' => $selectedSession,
            'className' => $selection['class_name'],
            'isClassHead' => $selection['is_class_head'],
            'wholeClass' => $selection['whole_class'],
            'students' => $students,
            'presentCount' => $present,
            'absentCount' => $absent,
            'filterUrl' => route($route),
            'saveUrl' => route($route.'.save'),
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

        $postedSection = isset($validated['section_id']) ? (int) $validated['section_id'] : null;
        $postedStream = isset($validated['standardLink_id']) ? (int) $validated['standardLink_id'] : null;
        $selection = $this->resolveRegisterSelection(
            $scope,
            $postedSection > 0 ? $postedSection : null,
            $postedStream > 0 ? $postedStream : null,
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
        if ($selection['single_stream_id'] !== null) {
            $params['standardLink_id'] = $selection['single_stream_id'];
        }
        if ($selection['section'] !== null) {
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
     * School admins may open any class in their own school. A class head does
     * not use this path: headedBaseIds() limits them to the streams of the
     * base they head.
     */
    private function mayTakeAnyRegister(int $schoolId, int $userId): bool
    {
        $user = Auth::user();
        if ($user === null || (int) $user->id !== $userId || (int) $user->school_id !== $schoolId) {
            return false;
        }

        return in_array((int) $user->usergroup_id, [3, 4], true);
    }

    /**
     * Base classes this person heads: the undivided section, not a name-encoded stream.
     *
     * @return list<int>
     */
    private function headedBaseIds(int $schoolId, int $userId): array
    {
        return app(ClassStructureService::class)->headedBaseIds($schoolId, $userId);
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

        $streams = $this->expandHeadedBases($schoolId, $teacherId, $year, $allowed->merge($headStreams)->unique('id'))
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
     * A class is the undivided base section. Its streams are name-encoded sections
     * ("Primary Four E") and any extra standards_link rows on the base itself.
     * Opening the register with no stream keeps every stream. It does not fall
     * through to the first stream.
     *
     * @param  array{year: ?\App\Models\AcademicYear, streams: \Illuminate\Support\Collection, sections: \Illuminate\Support\Collection, head_section_ids: list<int>}  $scope
     * @return array{
     *     section: ?\App\Models\Section,
     *     streams: \Illuminate\Support\Collection,
     *     is_class_head: bool,
     *     classes: \Illuminate\Support\Collection,
     *     stream_choices: list<array{id: int, label: string, base_id: int}>,
     *     single_stream_id: ?int,
     *     class_name: ?string,
     *     whole_class: bool
     * }
     */
    private function resolveRegisterSelection(array $scope, ?int $sectionId, ?int $standardLinkId): array
    {
        $empty = [
            'section' => null,
            'streams' => collect(),
            'is_class_head' => false,
            'classes' => collect(),
            'stream_choices' => [],
            'single_stream_id' => null,
            'class_name' => null,
            'whole_class' => false,
        ];

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

        $groups = $this->classGroups($scope);
        if ($groups->isEmpty()) {
            return $empty;
        }

        $classes = $groups->map(fn (array $group) => $group['section'])->values();
        $choices = [];
        foreach ($groups as $baseId => $group) {
            foreach ($group['links'] as $link) {
                $label = $this->streamLabel($group['section'], $link);
                if ($label === '—') {
                    continue;
                }
                $choices[] = [
                    'id' => (int) $link->id,
                    'label' => $label,
                    'base_id' => (int) $baseId,
                ];
            }
        }

        $userId = (int) Auth::id();
        $headed = $this->headedBaseIds($schoolId, $userId);
        $headed = array_values(array_filter($headed, fn (int $id) => $groups->has($id)));

        if (($sectionId === null || $sectionId <= 0) && ($standardLinkId === null || $standardLinkId <= 0)) {
            if ($headed !== []) {
                $sectionId = $headed[0];
            } elseif ($this->mayTakeAnyRegister($schoolId, $userId)) {
                $sectionId = (int) $classes->first()->id;
            } else {
                $standardLinkId = (int) $groups->first()['links']->first()->id;
            }
        }

        if ($standardLinkId !== null && $standardLinkId > 0) {
            $stream = $scope['streams']->firstWhere('id', $standardLinkId);
            if ($stream === null) {
                abort(403, 'You are not allowed to record attendance for this class.');
            }
            $base = $this->baseOf($stream->section);
            $group = $groups->get((int) $base->id);
            $label = $this->streamLabel($base, $stream);

            return [
                'section' => $base,
                'streams' => collect([$stream]),
                'is_class_head' => in_array((int) $base->id, $headed, true),
                'classes' => $classes,
                'stream_choices' => $choices,
                'single_stream_id' => (int) $stream->id,
                'class_name' => trim($base->name.($label !== '' && $label !== '—' ? ' · '.$label : '')),
                'whole_class' => false,
            ];
        }

        $requested = $sectionId !== null ? Section::query()->where('school_id', $schoolId)->whereKey($sectionId)->first() : null;
        if ($requested === null) {
            abort(403, 'You are not allowed to record attendance for this class.');
        }
        $base = $this->baseOf($requested);
        $group = $groups->get((int) $base->id);
        if ($group === null) {
            abort(403, 'You are not allowed to record attendance for this class.');
        }

        $links = $group['links']->values();
        $whole = $this->coversEveryStream($base, $links, $schoolId, (int) $scope['year']->id);

        return [
            'section' => $base,
            'streams' => $links,
            'is_class_head' => in_array((int) $base->id, $headed, true) || $this->mayTakeAnyRegister($schoolId, $userId),
            'classes' => $classes,
            'stream_choices' => $choices,
            'single_stream_id' => $links->count() === 1 ? (int) $links->first()->id : null,
            'class_name' => (string) $base->name,
            'whole_class' => $whole,
        ];
    }

    /**
     * A base head may be the class teacher of the base section, or of the
     * base section's standards_link while the section row itself is empty.
     * Add that class's stream links for this school and year. Other classes
     * stay out. A subject assignment or a stream-only class teacher is not a
     * base head, so those links are not added here.
     *
     * @param  \Illuminate\Support\Collection<int, StandardLink>  $streams
     * @return \Illuminate\Support\Collection<int, StandardLink>
     */
    private function expandHeadedBases(int $schoolId, int $teacherId, \App\Models\AcademicYear $year, $streams)
    {
        $baseIds = $this->headedBaseIds($schoolId, $teacherId);
        if ($baseIds === []) {
            return $streams->values();
        }

        $structure = app(ClassStructureService::class);
        $extra = StandardLink::query()
            ->with(['section', 'standard'])
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where(function ($query) {
                $query->where('status', 1)->orWhere('status', '1');
            })
            ->get()
            ->filter(function (StandardLink $link) use ($structure, $baseIds, $schoolId) {
                $section = $link->section;
                if ($section === null || (int) $section->school_id !== $schoolId) {
                    return false;
                }
                $base = $structure->resolveBaseSection($section);

                return (int) $base->school_id === $schoolId
                    && in_array((int) $base->id, $baseIds, true);
            });

        return $streams->merge($extra)->unique('id')->values();
    }

    /**
     * Whole class means every real stream of this base is on the register.
     * The empty base link (label "—", no students) is not a stream, and a
     * link count is not the test.
     *
     * @param  \Illuminate\Support\Collection<int, StandardLink>  $links
     */
    private function coversEveryStream(Section $base, $links, int $schoolId, int $yearId): bool
    {
        $structure = app(ClassStructureService::class);
        $streamIds = StandardLink::query()
            ->with('section')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $yearId)
            ->where(function ($query) {
                $query->where('status', 1)->orWhere('status', '1');
            })
            ->get()
            ->filter(function (StandardLink $link) use ($structure, $base, $schoolId) {
                $section = $link->section;
                if ($section === null || (int) $section->school_id !== $schoolId) {
                    return false;
                }
                $resolved = $structure->resolveBaseSection($section);
                if ((int) $resolved->id !== (int) $base->id || (int) $resolved->school_id !== $schoolId) {
                    return false;
                }

                return $this->streamLabel($base, $link) !== '—';
            })
            ->map(fn (StandardLink $link) => (int) $link->id)
            ->unique()
            ->values()
            ->all();

        if ($streamIds === []) {
            return false;
        }

        $present = $links->map(fn ($link) => (int) $link->id)->all();
        foreach ($streamIds as $id) {
            if (! in_array($id, $present, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array{year: ?\App\Models\AcademicYear, streams: \Illuminate\Support\Collection, sections: \Illuminate\Support\Collection, head_section_ids: list<int>}  $scope
     * @return \Illuminate\Support\Collection<int, array{section: \App\Models\Section, links: \Illuminate\Support\Collection<int, StandardLink>}>
     */
    private function classGroups(array $scope): \Illuminate\Support\Collection
    {
        $groups = [];
        foreach ($scope['streams'] as $link) {
            if (! $link->section) {
                continue;
            }
            $base = $this->baseOf($link->section);
            $id = (int) $base->id;
            if (! isset($groups[$id])) {
                $groups[$id] = ['section' => $base, 'links' => collect()];
            }
            $groups[$id]['links']->push($link);
        }

        return collect($groups)
            ->sortBy(fn (array $group) => mb_strtolower((string) $group['section']->name))
            ->map(function (array $group) {
                $group['links'] = $group['links']
                    ->sortBy(fn (StandardLink $link) => $this->streamLabel($group['section'], $link))
                    ->values();

                return $group;
            });
    }

    private function baseOf(Section $section): Section
    {
        $base = app(ClassStructureService::class)->resolveBaseSection($section);

        return (int) $base->school_id === (int) $section->school_id ? $base : $section;
    }

    private function streamLabel(Section $base, StandardLink $link): string
    {
        $stored = trim((string) ($link->stream ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        $section = $link->section;
        $name = trim((string) ($section?->name ?? ''));
        $baseName = trim((string) $base->name);
        $prefix = $baseName.' ';
        if ($name !== '' && $baseName !== '' && str_starts_with($name, $prefix)) {
            return trim(substr($name, strlen($prefix)));
        }

        return '—';
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
                $label = '—';
                if ($row->standardLink?->section) {
                    $label = $this->streamLabel($this->baseOf($row->standardLink->section), $row->standardLink);
                }

                return [
                    'user_id' => (int) $row->user_id,
                    'name' => $name,
                    'stream_id' => (int) $row->standardLink_id,
                    'stream' => $label,
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
