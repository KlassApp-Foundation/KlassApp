<?php

namespace App\Services\MarksRelease;

use App\Models\Academics\Exam;
use App\Models\MarksRelease;
use App\Models\School;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\Demo\DemoBindReplies;
use App\Services\Demo\DemoFollowAlong;
use App\Services\Demo\LatestExamMarks;
use App\Services\DemoSchoolCommsGuard;
use App\Services\WhatsAppBusinessService;
use App\Support\DemoStudentCode;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Release an exam's marks to parents on WhatsApp (Task 9 Tier 2).
 *
 * Who may release: a school admin (any class, or the whole school) and a
 * class teacher (only their own class and stream). Nobody else.
 *
 * Who gets a message: ONLY numbers with an ACTIVE demo binding (BIND, inside
 * the TTL) to a student in scope, in a DEMO school. Every other recipient is
 * skipped with a reason, and nothing at all is sent when
 * WHATSAPP_REPLIES_ENABLED is false. A release runs once: it is claimed
 * (scheduled → sending) in one UPDATE, and a number never gets the same
 * student's marks for the same exam twice, even across releases.
 */
final class MarksReleaseService
{
    public const USERGROUP_SCHOOL_ADMIN = 3;

    public const USERGROUP_TEACHER = 5;

    public function __construct(private readonly WhatsAppBusinessService $whatsApp) {}

    // ── Permissions ──────────────────────────────────────────────

    public function canRelease(?User $user): bool
    {
        return $user !== null && $this->links($user)->isNotEmpty();
    }

    public function isSchoolAdmin(User $user): bool
    {
        return (int) $user->usergroup_id === self::USERGROUP_SCHOOL_ADMIN && $user->school_id !== null;
    }

    /**
     * The classes (class + stream, current academic year) this user may release.
     *
     * @return Collection<int, StandardLink>
     */
    public function links(User $user): Collection
    {
        if (! $user->school_id) {
            return collect();
        }

        $query = StandardLink::query()
            ->with('section')
            ->where('school_id', $user->school_id)
            ->whereIn('academic_year_id', $this->currentYearIds((int) $user->school_id));

        if ($this->isSchoolAdmin($user)) {
            return $query->orderBy('section_id')->orderBy('stream')->get();
        }

        if ((int) $user->usergroup_id === self::USERGROUP_TEACHER) {
            return $query->where('class_teacher_id', $user->id)->orderBy('section_id')->orderBy('stream')->get();
        }

        return collect();
    }

    /**
     * Exams with marks in the user's classes: one entry per exam type and
     * term, keyed by an anchor exam id (the lowest id of that exam).
     *
     * @return Collection<int, array{id:int, label:string, link_ids:array<int,int>}>
     */
    public function exams(User $user): Collection
    {
        $links = $this->links($user);
        if ($links->isEmpty()) {
            return collect();
        }

        $sectionLinks = $links->groupBy('section_id');
        $exams = Exam::query()
            ->with(['examType', 'academicTerm'])
            ->where('school_id', $user->school_id)
            ->whereIn('section_id', $sectionLinks->keys())
            ->whereNull('deleted_at')
            ->whereHas('marks')
            ->orderBy('id')
            ->get();

        return $exams
            ->groupBy(fn (Exam $exam) => $exam->exam_type_id . '|' . $exam->academic_term_id)
            ->map(function (Collection $group) use ($sectionLinks) {
                $anchor = $group->first();

                return [
                    'id' => (int) $anchor->id,
                    'label' => $this->examLabel($anchor),
                    'link_ids' => $group->pluck('section_id')->unique()
                        ->flatMap(fn ($sectionId) => $sectionLinks->get($sectionId, collect())->pluck('id'))
                        ->map(fn ($id) => (int) $id)->values()->all(),
                    'scheduled_at' => $group->max('scheduled_at'),
                ];
            })
            ->sortByDesc('scheduled_at')
            ->values();
    }

    public function examLabel(Exam $exam): string
    {
        $type = (string) ($exam->examType?->name ?? 'Exam');
        $term = (string) ($exam->academicTerm?->name ?? '');

        return trim($type . ($term !== '' ? ' · ' . $term : ''));
    }

    // ── Create ───────────────────────────────────────────────────

    /**
     * @param  string|null  $localWhen  "Y-m-d\TH:i" in $timezone, or null for now
     */
    public function create(User $user, int $examId, ?int $standardLinkId, ?string $localWhen, ?string $timezone): MarksRelease
    {
        [$anchor, $link] = $this->authorise($user, $examId, $standardLinkId);
        $timezone = $this->timezone($timezone);
        $when = $this->scheduledAt($localWhen, $timezone);

        $release = MarksRelease::create([
            'school_id' => $user->school_id,
            'exam_id' => $anchor->id,
            'scope' => $link ? 'class' : 'school',
            'standard_link_id' => $link?->id,
            'scheduled_at' => $when,
            'status' => MarksRelease::STATUS_SCHEDULED,
            'created_by' => $user->id,
            'timezone' => $timezone,
        ]);

        activity('marks_release')->performedOn($release)->causedBy($user)
            ->withProperties([
                'exam_id' => $anchor->id,
                'scope' => $release->scope,
                'standard_link_id' => $release->standard_link_id,
                'scheduled_at_utc' => $release->scheduled_at->format('Y-m-d H:i:s'),
                'timezone' => $timezone,
            ])
            ->log($localWhen === null || $localWhen === '' ? 'Marks release created (now)' : 'Marks release scheduled');

        return $release;
    }

    /**
     * @return array{0: Exam, 1: StandardLink|null}
     */
    public function authorise(User $user, int $examId, ?int $standardLinkId): array
    {
        $exam = $this->exams($user)->firstWhere('id', $examId);
        if (! $exam) {
            throw new HttpException(404);
        }
        $anchor = Exam::query()->where('school_id', $user->school_id)->findOrFail($examId);

        if ($standardLinkId === null) {
            if (! $this->isSchoolAdmin($user)) {
                throw new HttpException(403, 'Only a school admin can release marks for every class.');
            }

            return [$anchor, null];
        }

        $link = $this->links($user)->firstWhere('id', $standardLinkId);
        if (! $link) {
            throw new HttpException(403, 'You can release marks only for your own class.');
        }
        if (! in_array((int) $link->id, $exam['link_ids'], true)) {
            throw ValidationException::withMessages(['standard_link_id' => 'This class has no marks for that exam.']);
        }

        return [$anchor, $link];
    }

    public function timezone(?string $timezone): string
    {
        $timezone = trim((string) $timezone);

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : 'Africa/Kampala';
    }

    public function scheduledAt(?string $localWhen, string $timezone): CarbonImmutable
    {
        if ($localWhen === null || trim($localWhen) === '') {
            return CarbonImmutable::now('UTC');
        }

        try {
            $when = CarbonImmutable::createFromFormat('Y-m-d\TH:i', trim($localWhen), $timezone);
        } catch (\Throwable) {
            $when = false;
        }
        if (! $when) {
            throw ValidationException::withMessages(['scheduled_for' => 'Enter a date and time.']);
        }
        if ($when->lt(CarbonImmutable::now($timezone)->subMinute())) {
            throw ValidationException::withMessages(['scheduled_for' => 'Choose a time in the future, or release now.']);
        }

        return $when->setTimezone('UTC');
    }

    // ── Preview ──────────────────────────────────────────────────

    /** The message the first student in scope's parent would get. */
    public function preview(User $user, int $examId, ?int $standardLinkId): string
    {
        [$anchor, $link] = $this->authorise($user, $examId, $standardLinkId);
        $school = School::find($user->school_id);
        foreach ($this->students((int) $user->school_id, $anchor, $link) as [$studentId, $studentLink]) {
            $text = $this->message($school, $anchor, (int) $studentId, $studentLink);
            if ($text !== null) {
                return $text;
            }
        }

        return 'No marks yet for this exam in the chosen class.';
    }

    // ── Run ──────────────────────────────────────────────────────

    /** Run every scheduled release that is due. Returns how many ran. */
    public function runDue(): int
    {
        $ran = 0;
        $due = MarksRelease::query()
            ->where('status', MarksRelease::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', CarbonImmutable::now('UTC')->format('Y-m-d H:i:s'))
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->pluck('id');

        foreach ($due as $id) {
            $ran += $this->run((int) $id) ? 1 : 0;
        }

        return $ran;
    }

    /** Run one release if it is due and not yet claimed. False when it was not ours to run. */
    public function run(int $releaseId): bool
    {
        $claimed = MarksRelease::query()
            ->whereKey($releaseId)
            ->where('status', MarksRelease::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', CarbonImmutable::now('UTC')->format('Y-m-d H:i:s'))
            ->update(['status' => MarksRelease::STATUS_SENDING, 'updated_at' => now()]);
        if ($claimed !== 1) {
            return false;
        }

        $release = MarksRelease::with(['school', 'exam', 'standardLink'])->findOrFail($releaseId);
        $result = $this->deliver($release);

        $release->forceFill([
            'status' => MarksRelease::STATUS_RELEASED,
            'released_at' => CarbonImmutable::now('UTC'),
            'result' => $result,
        ])->save();

        activity('marks_release')->performedOn($release)->causedBy($release->creator)
            ->withProperties(['sent' => $result['sent'], 'skipped' => $result['skipped'], 'reasons' => $result['reasons']])
            ->log('Marks released');

        return true;
    }

    /**
     * @return array{sent:int, skipped:int, reasons:array<string,int>, messages:array<int, array{student:string, to:string|null, status:string, reason:string|null}>}
     */
    private function deliver(MarksRelease $release): array
    {
        $school = $release->school;
        $anchor = $release->exam;
        $messages = [];
        $repliesOn = (bool) config('services.whatsapp.replies_enabled');
        $isDemo = (int) ($school?->is_demo ?? 0) === 1;
        $alreadySent = $this->alreadySent($release);
        $released = [];

        foreach ($this->students((int) $release->school_id, $anchor, $release->standardLink) as [$studentId, $link]) {
            $code = $this->studentCode((int) $release->school_id, (int) $studentId);
            $skip = function (string $reason, ?string $to = null) use (&$messages, $code): void {
                $messages[] = ['student' => $code, 'to' => $to, 'status' => 'skipped', 'reason' => $reason];
            };

            if (! $repliesOn) {
                $skip('WhatsApp messages are switched off');

                continue;
            }
            if (! $isDemo) {
                // Task 9 sends only for demo schools; real schools are not messaged yet.
                $skip('Not a demo school: release messages are sent only in the demo for now');

                continue;
            }

            $text = $this->message($school, $anchor, (int) $studentId, $link);
            if ($text === null) {
                $skip('No marks for this exam');

                continue;
            }

            $released[] = ['code' => $code, 'class' => trim(($link->section?->name ?? '') . ' ' . ($link->stream ?? ''))];

            $bindings = $this->activeBindings((int) $release->school_id, (int) $studentId);
            if ($bindings->isEmpty()) {
                $skip('No number is following this student');

                continue;
            }

            foreach ($bindings as $binding) {
                $masked = $this->mask((string) $binding->phone);
                $key = $this->dedupeKey((string) $binding->phone, (int) $studentId);
                if (isset($alreadySent[$key])) {
                    $skip('Already sent to this number', $masked);

                    continue;
                }

                $response = DemoSchoolCommsGuard::duringBoundDemoRelease(
                    (string) $binding->phone,
                    fn () => $this->whatsApp->sendText((string) $binding->phone, $text, 'marks_release', $binding->user_id ? (int) $binding->user_id : null),
                );

                if (($response['success'] ?? false) === true) {
                    $messages[] = ['student' => $code, 'to' => $masked, 'status' => 'sent', 'reason' => null, 'key' => $key];
                    $alreadySent[$key] = true;
                } else {
                    $skip('Not delivered: ' . (string) ($response['message'] ?? $response['error'] ?? 'unknown error'), $masked);
                }
            }
        }

        $sent = count(array_filter($messages, fn ($m) => $m['status'] === 'sent'));
        $reasons = [];
        foreach ($messages as $m) {
            if ($m['status'] === 'skipped') {
                $reasons[$m['reason']] = ($reasons[$m['reason']] ?? 0) + 1;
            }
        }

        return [
            'sent' => $sent,
            'skipped' => count($messages) - $sent,
            'reasons' => $reasons,
            'messages' => $messages,
            'webhook' => ($repliesOn && $isDemo) ? $this->postWebhook($release, $released) : 'not sent',
        ];
    }

    /**
     * Tier 3: tell a presenter's workflow that marks were released. Only for a
     * demo school, only when DEMO_RELEASE_WEBHOOK_URL and _SECRET are both
     * set, and the body carries only the exam and each student's code and
     * class. A failure is logged and never stops the release.
     *
     * @param  array<int, array{code:string, class:string}>  $students
     */
    private function postWebhook(MarksRelease $release, array $students): string
    {
        $url = trim((string) config('klassapp.demo.release_webhook_url'));
        $secret = (string) config('klassapp.demo.release_webhook_secret');
        if ($url === '' || $secret === '') {
            return 'not configured';
        }
        if (! str_starts_with($url, 'https://')) {
            return 'not sent: the URL must use https';
        }

        $body = json_encode([
            'event' => 'marks.released',
            'release_id' => (int) $release->id,
            'exam' => $this->examLabel($release->exam),
            'students' => array_values($students),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-KlassApp-Signature' => 'sha256=' . hash_hmac('sha256', $body, $secret),
                ])
                ->withBody($body, 'application/json')
                ->post($url);

            return $response->successful() ? 'sent' : 'failed: HTTP ' . $response->status();
        } catch (\Throwable $e) {
            Log::warning('Marks release webhook failed', ['release_id' => $release->id, 'error' => $e->getMessage()]);

            return 'failed: no response';
        }
    }

    /** Numbers with an active BIND to this student (inside the TTL), in this school. */
    private function activeBindings(int $schoolId, int $studentId): Collection
    {
        return WhatsAppUser::query()
            ->where('school_id', $schoolId)
            ->where('demo_student_id', $studentId)
            ->whereNotNull('user_id')
            ->where('opted_in', true)
            ->whereNotNull('demo_linked_at')
            ->where('demo_linked_at', '>', DemoFollowAlong::bindCutoff())
            ->orderBy('id')
            ->get(['id', 'phone', 'user_id']);
    }

    /** @return array<string, true> numbers that already got this exam for a student, in any release */
    private function alreadySent(MarksRelease $release): array
    {
        $sameExam = Exam::query()
            ->where('school_id', $release->school_id)
            ->where('exam_type_id', $release->exam->exam_type_id)
            ->where('academic_term_id', $release->exam->academic_term_id)
            ->pluck('id');

        $keys = [];
        MarksRelease::query()
            ->where('school_id', $release->school_id)
            ->whereIn('exam_id', $sameExam)
            ->whereKeyNot($release->id)
            ->whereNotNull('result')
            ->get(['id', 'result'])
            ->each(function (MarksRelease $other) use (&$keys) {
                foreach (($other->result['messages'] ?? []) as $m) {
                    if (($m['status'] ?? '') === 'sent' && isset($m['key'])) {
                        $keys[$m['key']] = true;
                    }
                }
            });

        return $keys;
    }

    private function dedupeKey(string $phone, int $studentId): string
    {
        return hash('sha256', preg_replace('/\D+/', '', $phone) . '|' . $studentId);
    }

    /**
     * Students in scope, current academic year, with the class they are in.
     *
     * @return \Generator<int, array{0:int, 1:StandardLink}>
     */
    private function students(int $schoolId, Exam $anchor, ?StandardLink $link): \Generator
    {
        $sections = Exam::query()
            ->where('school_id', $schoolId)
            ->where('exam_type_id', $anchor->exam_type_id)
            ->where('academic_term_id', $anchor->academic_term_id)
            ->whereNull('deleted_at')
            ->pluck('section_id')->unique();

        $links = $link
            ? collect([$link])
            : StandardLink::query()->with('section')->where('school_id', $schoolId)
                ->whereIn('academic_year_id', $this->currentYearIds($schoolId))
                ->whereIn('section_id', $sections)->orderBy('section_id')->orderBy('stream')->get();

        foreach ($links as $classLink) {
            $ids = StudentAcademic::query()
                ->where('school_id', $schoolId)
                ->where('standardLink_id', $classLink->id)
                ->whereIn('academic_year_id', $this->currentYearIds($schoolId))
                ->orderBy('user_id')
                ->pluck('user_id');
            foreach ($ids as $id) {
                yield [(int) $id, $classLink];
            }
        }
    }

    private function message(?School $school, Exam $anchor, int $studentId, StandardLink $link): ?string
    {
        $student = User::query()->where('school_id', $anchor->school_id)->whereKey($studentId)->first();
        if (! $student) {
            return null;
        }
        $exam = LatestExamMarks::forExam((int) $anchor->school_id, (int) $link->section_id, $anchor, $studentId);
        if ($exam['subjects'] === []) {
            return null;
        }
        $class = trim(($link->section?->name ?? '') . ' ' . ($link->stream ?? ''));

        return '📣 *' . ($school?->name ?? 'The school') . "* has released marks.\n\n"
            . app(DemoBindReplies::class)->renderMarks((string) $student->name, $class, $exam);
    }

    private function studentCode(int $schoolId, int $studentId): string
    {
        $kls = (string) User::query()->where('school_id', $schoolId)->whereKey($studentId)->value('registration_number');

        return DemoStudentCode::fromKls($kls) ?? ('#' . $studentId);
    }

    /** Only the last three digits are kept for the result view and the audit log. */
    private function mask(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        return '•••' . substr($digits, -3);
    }

    /** @return array<int, int> */
    private function currentYearIds(int $schoolId): array
    {
        return DB::table('academic_years')->where('school_id', $schoolId)->where('status', 1)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
