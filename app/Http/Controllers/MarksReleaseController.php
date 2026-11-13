<?php

namespace App\Http\Controllers;

use App\Models\Academics\Exam;
use App\Models\MarksRelease;
use App\Models\User;
use App\Support\ClassLevelOrder;
use App\Services\MarksRelease\MarksReleaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * "Release marks" for school admins (/admin/marks/releases) and class
 * teachers (/teacher/marks/releases): choose, preview, confirm, see the
 * result. The service decides who may release which class.
 */
class MarksReleaseController extends Controller
{
    public function __construct(private readonly MarksReleaseService $releases) {}

    public function index(Request $request): View
    {
        $user = $this->user();
        $linkIds = $this->releases->links($user)->pluck('id')->all();
        $list = MarksRelease::query()
            ->with(['exam.examType', 'exam.academicTerm', 'standardLink.section'])
            ->where('school_id', $user->school_id)
            ->when(! $this->releases->isSchoolAdmin($user), fn ($q) => $q->where('scope', 'class')->whereIn('standard_link_id', $linkIds))
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('marks-releases.index', $this->shared($request) + ['list' => $list]);
    }

    public function create(Request $request): View
    {
        $user = $this->user();
        $exams = $this->releases->exams($user);
        $links = $this->releases->links($user);
        $isAdmin = $this->releases->isSchoolAdmin($user);
        $old = $this->oldInput($request, $exams, $links, $isAdmin);

        return view('marks-releases.create', $this->page($request, $user, $exams, $links, $isAdmin, $old, false));
    }

    public function preview(Request $request): View
    {
        $user = $this->user();
        $data = $this->validated($request);
        $exams = $this->releases->exams($user);
        $links = $this->releases->links($user);
        $isAdmin = $this->releases->isSchoolAdmin($user);
        $timezone = $this->releases->timezone($data['timezone']);
        $when = $data['when'] === 'schedule' ? $this->releases->scheduledAt($data['scheduled_for'], $timezone) : null;

        return view('marks-releases.create', [
            'whenLabel' => $when ? $when->setTimezone($timezone)->format('D j M Y, H:i') . ' (' . $timezone . ')' : 'Now',
        ] + $this->page($request, $user, $exams, $links, $isAdmin, [
            'exam_id' => $data['exam_id'],
            'standard_link_id' => $data['standard_link_id'],
            'class' => $data['class'],
            'stream' => $data['stream'],
            'when' => $data['when'],
            'scheduled_for' => $data['scheduled_for'] ?? '',
            'timezone' => $timezone,
            'confirm_past' => $data['confirm_past'],
            'whatsapp_send' => $data['whatsapp_send'],
        ], true));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->user();
        $data = $this->validated($request);
        // The same class list the screen used, whether it came from the
        // class/stream picker, a standard_link_id on the URL, or both.
        $targets = $this->releaseTargets($user, $data);
        if ($this->needsReason($user, $data['exam_id'], $targets) && $data['release_reason'] === '') {
            throw ValidationException::withMessages([
                'release_reason' => 'This sitting has already been released. Say why it is being released again.',
            ]);
        }
        $localWhen = $data['when'] === 'schedule' ? $data['scheduled_for'] : null;
        $created = [];
        if ($targets === null) {
            $created[] = $this->releases->create($user, $data['exam_id'], null, $localWhen, $data['timezone'], $data['confirm_past'], $data['whatsapp_send'], $data['release_reason']);
        } else {
            // Every class is checked before any release row is written.
            foreach ($targets as $linkId) {
                [$anchor, $link] = $this->releases->authorise($user, $data['exam_id'], $linkId);
                $this->releases->requireApproval($user, $anchor, $link);
                $this->releases->requirePastConfirmation($anchor, $data['confirm_past']);
            }
            foreach ($targets as $linkId) {
                $created[] = $this->releases->create($user, $data['exam_id'], $linkId, $localWhen, $data['timezone'], $data['confirm_past'], $data['whatsapp_send'], $data['release_reason']);
            }
        }
        if ($data['when'] !== 'schedule') {
            foreach ($created as $release) {
                $this->releases->run($release->id);
            }
        }
        if (count($created) === 1) {
            $redirect = redirect()->route($this->area($request) . '.marks.releases.show', $created[0]->id);
            if ($data['when'] === 'schedule') {
                return $redirect;
            }
            $queued = $this->releases->queuedCount($created[0]->fresh());

            return $redirect->with('status', 'Queued ' . $queued . ' ' . ($queued === 1 ? 'message' : 'messages') . '. Refresh this page to see each one sent.');
        }

        $verb = $data['when'] === 'schedule' ? 'Scheduled' : 'Queued';

        return redirect()->route($this->area($request) . '.marks.releases.index')
            ->with('status', $verb . ' ' . count($created) . ' classes.');
    }

    public function show(Request $request, int $release): View
    {
        $user = $this->user();
        $row = MarksRelease::query()
            ->with(['exam.examType', 'exam.academicTerm', 'standardLink.section', 'creator'])
            ->where('school_id', $user->school_id)
            ->findOrFail($release);
        if (! $this->releases->isSchoolAdmin($user)
            && ! ($row->scope === 'class' && $this->releases->links($user)->contains('id', $row->standard_link_id))) {
            abort(404);
        }

        return view('marks-releases.show', $this->shared($request) + ['release' => $row]);
    }

    /** @return array{exam_id:int, standard_link_id:int|null, class:string, stream:string, when:string, scheduled_for:string|null, timezone:string|null, confirm_past:bool, whatsapp_send:bool} */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'exam_id' => ['required', 'integer'],
            'standard_link_id' => ['nullable', 'integer'],
            'class' => ['nullable', 'string', 'max:80'],
            'stream' => ['nullable', 'string', 'max:40'],
            'when' => ['required', 'in:now,schedule'],
            'scheduled_for' => ['nullable', 'required_if:when,schedule', 'date_format:Y-m-d\TH:i'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'confirm_past' => ['nullable', 'boolean'],
            'whatsapp_send' => ['nullable', 'boolean'],
            'release_reason' => ['nullable', 'string', 'max:500'],
        ]);

        return [
            'exam_id' => (int) $data['exam_id'],
            'standard_link_id' => isset($data['standard_link_id']) && $data['standard_link_id'] !== '' ? (int) $data['standard_link_id'] : null,
            'class' => trim((string) ($data['class'] ?? '')),
            'stream' => trim((string) ($data['stream'] ?? '')),
            'when' => $data['when'],
            'scheduled_for' => $data['scheduled_for'] ?? null,
            'timezone' => $data['timezone'] ?? null,
            'confirm_past' => filter_var($data['confirm_past'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'whatsapp_send' => filter_var($data['whatsapp_send'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'release_reason' => trim((string) ($data['release_reason'] ?? '')),
        ];
    }

    private function user(): \App\Models\User
    {
        $user = Auth::user();
        abort_unless($this->releases->canRelease($user), 403);

        return $user;
    }

    private function area(Request $request): string
    {
        return str_starts_with((string) $request->route()?->getName(), 'teacher.') ? 'teacher' : 'admin';
    }

    /**
     * @param  Collection<int, mixed>  $exams
     * @param  Collection<int, mixed>  $links
     * @param  array{exam_id:int, standard_link_id:?int, class?:string, stream?:string, when:string, scheduled_for:string, timezone:string}  $old
     * @return array<string, mixed>
     */
    private function page(Request $request, User $user, $exams, $links, bool $isAdmin, array $old, bool $confirming): array
    {
        $examId = (int) $old['exam_id'];
        $known = $exams->contains(fn ($exam) => (int) $exam['id'] === $examId);
        $class = trim((string) ($old['class'] ?? ''));
        $stream = trim((string) ($old['stream'] ?? ''));
        $examLinks = $known ? $this->examLinks($links, $exams->firstWhere('id', $examId)) : collect();
        if ($class !== '' && $stream !== '' && $this->filterLinks($examLinks, $class, $stream)->isEmpty()) {
            $stream = '';
        }
        $targets = null;
        if ($known && ($class !== '' || $stream !== '')) {
            $targets = $this->filterLinks($examLinks, $class, $stream)->map(fn ($link) => (int) $link->id)->all();
        } elseif ($known && ! empty($old['standard_link_id'])) {
            $targets = [(int) $old['standard_link_id']];
            $picked = $examLinks->firstWhere('id', (int) $old['standard_link_id']) ?? $links->firstWhere('id', (int) $old['standard_link_id']);
            if ($picked) {
                $class = trim((string) ($picked->section?->name ?? ''));
                $stream = trim((string) ($picked->stream ?? ''));
            }
        }
        $sheet = ($examId && $known)
            ? $this->sheetFor($user, $examId, $targets)
            : ['subjects' => [], 'groups' => []];
        $approvals = ($examId && $known)
            ? $this->approvalsFor($user, $examId, $targets)
            : [];
        $pastSitting = $examId && $known && $this->releases->sittingIsPast((int) $user->school_id, $examId);
        $previewId = $targets === null ? null : ($targets[0] ?? null);
        $previewMessage = ($examId && $known && $targets !== [])
            ? $this->releases->preview($user, $examId, $previewId)
            : '';
        $count = (int) array_sum(array_column($sheet['groups'], 'count'));
        $missingCount = 0;
        foreach ($sheet['groups'] as $group) {
            foreach ($group['students'] ?? [] as $student) {
                foreach ($sheet['subjects'] as $subject) {
                    if (($student['cells'][$subject['key']] ?? null) === null) {
                        $missingCount++;
                    }
                }
            }
        }
        $who = $this->audience($examLinks, $class, $stream, $targets);
        $noun = $count === 1 ? 'student' : 'students';
        $verb = ($old['when'] ?? 'now') === 'schedule' ? 'Schedule for' : 'Release to';
        $old['class'] = $class;
        $old['stream'] = $stream;

        return $this->shared($request) + [
            'exams' => $exams,
            'links' => $links,
            'isAdmin' => $isAdmin,
            'old' => $old,
            'classOptions' => $examLinks->map(fn ($link) => trim((string) ($link->section?->name ?? '')))->filter()->unique()->values(),
            'streamOptions' => $this->filterLinks($examLinks, $class, '')->map(fn ($link) => trim((string) ($link->stream ?? '')))->filter()->unique()->values(),
            'confirming' => $confirming,
            'whenLabel' => 'Now',
            'sheet' => $sheet,
            'missingCount' => $missingCount,
            'approvals' => $approvals,
            'pastSitting' => $pastSitting,
            'needsReason' => $examId > 0 && $known && $this->needsReason($user, $examId, $targets),
            'releaseReasonPrompt' => $this->releaseReasonPrompt($this->releasedClassNames($user, $examId, $known ? $targets : [])),
            'previewMessage' => $previewMessage,
            'audience' => ($examId && $known)
                ? $this->releases->audienceSummary($user, $examId, $targets)
                : ['classes' => [], 'parents' => 0, 'whatsapp' => 0],
            'subjectsApproved' => $approvals !== [] && collect($approvals)->every(fn (array $row) => $row['state'] === 'approved'),
            'wide' => count($sheet['subjects']) > 6,
            'buttonLabel' => $verb . ' ' . $who . ' (' . $count . ' ' . $noun . ')',
            'releaseTo' => $who . ' (' . $count . ' ' . $noun . ')',
        ];
    }

    /**
     * @param  Collection<int, mixed>  $exams
     * @param  Collection<int, mixed>  $links
     * @return array{exam_id:int, standard_link_id:?int, class:string, stream:string, when:string, scheduled_for:string, timezone:string}
     */
    private function oldInput(Request $request, $exams, $links, bool $isAdmin): array
    {
        // A failed save redirects back with the posted class. Prefer that over
        // the URL so the reason box matches the class that was refused.
        $examId = (int) (old('exam_id') ?: $request->input('exam_id') ?: ($exams->first()['id'] ?? 0));
        $class = trim((string) old('class', $request->input('class', '')));
        $stream = trim((string) old('stream', $request->input('stream', '')));
        $postedLink = old('standard_link_id', $request->input('standard_link_id'));
        $linkId = ($postedLink !== null && $postedLink !== '') ? (int) $postedLink : null;
        if (! $isAdmin && $class === '' && $stream === '' && ! $linkId) {
            $first = $links->first();
            if ($first) {
                $class = trim((string) ($first->section?->name ?? ''));
                $stream = trim((string) ($first->stream ?? ''));
                $linkId = (int) $first->id;
            }
        }

        return [
            'exam_id' => $examId,
            'standard_link_id' => $linkId,
            'class' => $class,
            'stream' => $stream,
            'when' => $request->input('when', 'now') === 'schedule' ? 'schedule' : 'now',
            'scheduled_for' => (string) $request->input('scheduled_for', ''),
            'timezone' => (string) $request->input('timezone', 'Africa/Kampala'),
            'whatsapp_send' => $request->boolean('whatsapp_send'),
        ];
    }

    /** @param  array<string, mixed>|null  $exam */
    private function examLinks(Collection $links, ?array $exam): Collection
    {
        $ids = array_map('intval', (array) ($exam['link_ids'] ?? []));

        return $links->filter(fn ($link) => in_array((int) $link->id, $ids, true))->values();
    }

    private function filterLinks(Collection $links, string $class, string $stream): Collection
    {
        return $links->filter(function ($link) use ($class, $stream) {
            if ($class !== '' && trim((string) ($link->section?->name ?? '')) !== $class) {
                return false;
            }
            if ($stream !== '' && trim((string) ($link->stream ?? '')) !== $stream) {
                return false;
            }

            return true;
        })->values();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, int>|null
     */
    private function releaseTargets(User $user, array $data): ?array
    {
        if ($data['class'] === '' && $data['stream'] === '') {
            return $data['standard_link_id'] ? [$data['standard_link_id']] : null;
        }
        $links = $this->filterLinks(
            $this->examLinks($this->releases->links($user), $this->releases->exams($user)->firstWhere('id', $data['exam_id'])),
            $data['class'],
            $data['stream'],
        );
        if ($links->isEmpty()) {
            throw ValidationException::withMessages(['standard_link_id' => 'Choose a class that has marks for this exam.']);
        }

        return $links->map(fn ($link) => (int) $link->id)->all();
    }

    /** @param  array<int, int>|null  $targets */
    private function audience(Collection $links, string $class, string $stream, ?array $targets): string
    {
        if ($targets !== null && count($targets) === 1) {
            $link = $links->firstWhere('id', $targets[0]);
            if ($link) {
                return trim(trim((string) ($link->section?->name ?? '')) . ' ' . trim((string) ($link->stream ?? '')));
            }
        }
        if ($class !== '' && $stream !== '') {
            return trim($class . ' ' . $stream);
        }
        if ($class !== '') {
            return $class;
        }
        if ($stream !== '') {
            return 'stream ' . $stream;
        }

        return 'all classes';
    }

    /** @param  array<int, int>|null  $linkIds
     * @return array<int, array{subject_id:int, name:string, class_name:string, state:string, label:string}>
     */
    private function approvalsFor(User $user, int $examId, ?array $linkIds): array
    {
        if ($linkIds === null) {
            return $this->releases->subjectApprovals($user, $examId, null);
        }

        $rows = [];
        foreach ($linkIds as $id) {
            foreach ($this->releases->subjectApprovals($user, $examId, (int) $id) as $row) {
                $rows[$row['subject_id'].'|'.$row['class_name']] = $row;
            }
        }

        return array_values($rows);
    }

    /** @param  array<int, int>|null  $linkIds */
    private function sheetFor(User $user, int $examId, ?array $linkIds): array
    {
        if ($linkIds === null) {
            return $this->releases->combinedSheet($user, $examId, null);
        }
        $subjects = [];
        $groups = [];
        foreach ($linkIds as $id) {
            $part = $this->releases->combinedSheet($user, $examId, (int) $id);
            foreach ($part['subjects'] as $subject) {
                $subjects[$subject['key']] = $subject;
            }
            array_push($groups, ...$part['groups']);
        }

        return ['subjects' => array_values($subjects), 'groups' => $groups];
    }

    /**
     * Has this sitting already been released for what is being released now?
     *
     * $standardLinkIds null is a whole-school release: any earlier release of
     * this sitting, of any class, asks for a reason. A list is the streams the
     * screen resolved (picker, URL, or a change that reloaded the page). A
     * stream asks for a reason when that stream was already released, or when
     * the whole school was. Another stream of the same class does not. An
     * empty list is not a class. A release that failed is not a release.
     *
     * @param  array<int, int>|null  $standardLinkIds
     */
    private function needsReason(User $user, int $examId, ?array $standardLinkIds = null): bool
    {
        if ($standardLinkIds === []) {
            return false;
        }

        $anchor = Exam::query()->where('school_id', $user->school_id)->find($examId);
        if ($anchor === null) {
            return false;
        }

        $released = MarksRelease::query()
            ->where('school_id', $user->school_id)
            ->where('status', MarksRelease::STATUS_RELEASED)
            ->whereHas('exam', fn ($q) => $q
                ->where('school_id', $user->school_id)
                ->where('exam_type_id', $anchor->exam_type_id)
                ->where('academic_term_id', $anchor->academic_term_id));

        if ($standardLinkIds === null) {
            return $released->exists();
        }

        return $released->where(function ($q) use ($standardLinkIds): void {
            $q->where('scope', 'school')
                ->orWhereIn('standard_link_id', $standardLinkIds);
        })->exists();
    }

    /**
     * Class names in this selection that already have a released sitting.
     * A whole-school release is named as such. An empty selection names nothing.
     *
     * @param  array<int, int>|null  $standardLinkIds
     * @return list<string>
     */
    private function releasedClassNames(User $user, int $examId, ?array $standardLinkIds): array
    {
        if ($examId <= 0 || $standardLinkIds === []) {
            return [];
        }

        $anchor = Exam::query()->where('school_id', $user->school_id)->find($examId);
        if ($anchor === null) {
            return [];
        }

        $rows = MarksRelease::query()
            ->with('standardLink.section')
            ->where('school_id', $user->school_id)
            ->where('status', MarksRelease::STATUS_RELEASED)
            ->whereHas('exam', fn ($q) => $q
                ->where('school_id', $user->school_id)
                ->where('exam_type_id', $anchor->exam_type_id)
                ->where('academic_term_id', $anchor->academic_term_id))
            ->get();

        $names = [];
        foreach ($rows as $row) {
            if ($row->scope === 'school') {
                $names[] = 'The whole school';

                continue;
            }
            if ($standardLinkIds !== null && ! in_array((int) $row->standard_link_id, $standardLinkIds, true)) {
                continue;
            }
            $section = trim((string) ($row->standardLink?->section?->name ?? ''));
            $stream = trim((string) ($row->standardLink?->stream ?? ''));
            $label = trim($section.' '.$stream);
            if ($label !== '') {
                $names[] = $label;
            }
        }

        $names = array_values(array_unique($names));
        $whole = in_array('The whole school', $names, true);
        $classes = array_values(array_filter($names, fn (string $name) => $name !== 'The whole school'));
        $classes = ClassLevelOrder::sort(collect($classes), fn (string $name) => $name)->all();

        return $whole ? array_merge(['The whole school'], $classes) : $classes;
    }

    /** @param  list<string>  $names */
    private function releaseReasonPrompt(array $names): string
    {
        if ($names === []) {
            return 'Why release this sitting again?';
        }

        $list = count($names) === 1
            ? $names[0]
            : implode(', ', array_slice($names, 0, -1)).' and '.$names[array_key_last($names)];
        $verb = count($names) === 1 ? 'was' : 'were';

        return $list.' '.$verb.' already released. Why release this sitting again?';
    }

    /** @return array{area:string, layout:string} */
    private function shared(Request $request): array
    {
        $area = $this->area($request);

        return ['area' => $area, 'layout' => $area === 'teacher' ? 'layouts.teacher.layout' : 'layouts.admin.layout'];
    }
}
