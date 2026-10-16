<?php

namespace App\Http\Controllers;

use App\Models\MarksRelease;
use App\Services\MarksRelease\MarksReleaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $old = [
            'exam_id' => (int) ($request->input('exam_id') ?: ($exams->first()['id'] ?? 0)),
            'standard_link_id' => $request->filled('standard_link_id') ? (int) $request->input('standard_link_id') : null,
            'when' => $request->input('when', 'now'),
            'scheduled_for' => $request->input('scheduled_for', ''),
            'timezone' => $request->input('timezone', 'Africa/Kampala'),
        ];
        if (! $isAdmin && ! $old['standard_link_id']) {
            $old['standard_link_id'] = (int) ($links->first()->id ?? 0) ?: null;
        }

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
            'when' => $data['when'],
            'scheduled_for' => $data['scheduled_for'] ?? '',
            'timezone' => $timezone,
        ], true));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->user();
        $data = $this->validated($request);
        $release = $this->releases->create(
            $user,
            $data['exam_id'],
            $data['standard_link_id'],
            $data['when'] === 'schedule' ? $data['scheduled_for'] : null,
            $data['timezone'],
        );
        $redirect = redirect()->route($this->area($request) . '.marks.releases.show', $release->id);
        if ($data['when'] !== 'schedule') {
            // Sends are queued (one job per number): the page returns at once.
            $this->releases->run($release->id);
            $queued = $this->releases->queuedCount($release->fresh());

            return $redirect->with('status', 'Queued ' . $queued . ' ' . ($queued === 1 ? 'message' : 'messages') . '. Refresh this page to see each one sent.');
        }

        return $redirect;
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

    /** @return array{exam_id:int, standard_link_id:int|null, when:string, scheduled_for:string|null, timezone:string|null} */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'exam_id' => ['required', 'integer'],
            'standard_link_id' => ['nullable', 'integer'],
            'when' => ['required', 'in:now,schedule'],
            'scheduled_for' => ['nullable', 'required_if:when,schedule', 'date_format:Y-m-d\TH:i'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);

        return [
            'exam_id' => (int) $data['exam_id'],
            'standard_link_id' => isset($data['standard_link_id']) && $data['standard_link_id'] !== '' ? (int) $data['standard_link_id'] : null,
            'when' => $data['when'],
            'scheduled_for' => $data['scheduled_for'] ?? null,
            'timezone' => $data['timezone'] ?? null,
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
     * @param  \Illuminate\Support\Collection<int, mixed>  $exams
     * @param  \Illuminate\Support\Collection<int, mixed>  $links
     * @param  array{exam_id:int, standard_link_id:?int, when:string, scheduled_for:string, timezone:string}  $old
     * @return array<string, mixed>
     */
    private function page(Request $request, \App\Models\User $user, $exams, $links, bool $isAdmin, array $old, bool $confirming): array
    {
        $examId = (int) $old['exam_id'];
        $linkId = $old['standard_link_id'] ? (int) $old['standard_link_id'] : null;
        $known = $exams->contains(fn ($exam) => (int) $exam['id'] === $examId);
        $sheet = ($examId && $known) ? $this->releases->combinedSheet($user, $examId, $linkId) : ['subjects' => [], 'groups' => []];
        $previewMessage = ($examId && $known) ? $this->releases->preview($user, $examId, $linkId) : '';

        return $this->shared($request) + [
            'exams' => $exams,
            'links' => $links,
            'isAdmin' => $isAdmin,
            'old' => $old,
            'confirming' => $confirming,
            'whenLabel' => 'Now',
            'sheet' => $sheet,
            'previewMessage' => $previewMessage,
            'wide' => count($sheet['subjects']) > 6,
        ];
    }

    /** @return array{area:string, layout:string} */
    private function shared(Request $request): array
    {
        $area = $this->area($request);

        return ['area' => $area, 'layout' => $area === 'teacher' ? 'layouts.teacher.layout' : 'layouts.admin.layout'];
    }
}
