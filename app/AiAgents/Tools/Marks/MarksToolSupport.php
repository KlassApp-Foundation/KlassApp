<?php

namespace App\AiAgents\Tools\Marks;

use App\Contracts\Results\ReleaseGate;
use App\Contracts\Results\ReleasedSitting;
use App\Helpers\SiteHelper;
use App\Models\AcademicTerm;
use App\Models\Academics\ExamType;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\Results\CombinedSheetPage;
use App\Services\Results\ReportComposition;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use App\Services\StudentReportCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Shared reads for the marks tools. Scope is the signed-in school.
 * A class teacher is limited to the classes the marks overview already allows.
 * An admin or a head teacher sees their own school. Nothing here calculates a mark.
 */
final class MarksToolSupport
{
    public const TEACHERS_GAP = 'The marks overview does not name the teacher for a subject that has not been submitted.';

    public function actor(): ?User
    {
        $user = auth()->user() ?? request()->user();

        return $user instanceof User ? $user : null;
    }

    public function refusal(?User $user): ?string
    {
        if ($user === null) {
            return '❌ Authentication required.';
        }
        if (! $user->school_id) {
            return '❌ You are not assigned to a school.';
        }
        if (! in_array((int) $user->usergroup_id, [3, 4, 5], true)) {
            return '❌ You cannot open marks for this school.';
        }

        return null;
    }

    /**
     * Null means every class in the school. A list is the teacher's own classes.
     *
     * @return list<int>|null
     */
    public function allowedSectionIds(User $user): ?array
    {
        if (in_array((int) $user->usergroup_id, [3, 4], true)) {
            return null;
        }

        return app(CombinedSheetPage::class)->sectionIdsFor($user);
    }

    /**
     * The marks overview for one class and exam set, passed through.
     *
     * @return array<string, mixed>|string
     */
    public function overview(User $user, string $className, string $examSet, ?string $termName): array|string
    {
        $schoolId = (int) $user->school_id;
        $allowed = $this->allowedSectionIds($user);
        $section = $this->section($schoolId, $className, $allowed);
        if (is_string($section)) {
            return $section;
        }
        $type = $this->examType($schoolId, $examSet);
        if (is_string($type)) {
            return $type;
        }
        $term = $this->term($schoolId, $termName);
        if (is_string($term)) {
            return $term;
        }

        $request = Request::create('/admin/marks/filter', 'GET', [
            'class' => $section->id,
            'term' => $term->id,
            'year' => $term->academic_year_id,
            'examType' => $type->id,
        ]);

        try {
            $data = app(CombinedSheetPage::class)->viewData($schoolId, $request, $allowed);
        } catch (HttpException $e) {
            return $e->getStatusCode() === 403
                ? '❌ You can only open your own class.'
                : '❌ That class is not in your school.';
        }

        if (! empty($data['marksError'])) {
            return '❌ '.$data['marksError'];
        }
        if (! ($data['filtered'] ?? false)) {
            return '❌ Choose a class, a term, and an exam set.';
        }

        $subjects = array_values($data['subjects']->all());
        $missing = array_values($data['missingSubjects']->all());
        $sheet = $data['sheet'];
        $names = [];
        foreach ($subjects as $subject) {
            $names[(int) $subject['id']] = (string) $subject['name'];
        }

        $learners = [];
        foreach ($sheet->students as $student) {
            foreach ($student->cells as $subjectId => $cell) {
                $printed = $cell->sheetLabel();
                if ($printed !== 'Missing') {
                    continue;
                }
                $learners[] = [
                    'name' => $student->name,
                    'subject' => $names[(int) $subjectId] ?? '',
                    'printed' => $printed,
                ];
            }
        }

        $tab = null;
        foreach ($data['examSetTabs'] as $row) {
            if ((int) $row['type']->id !== (int) $type->id) {
                continue;
            }
            $tab = [
                'label' => $row['label'],
                'key' => $row['key'],
                'desktop' => $row['desktop'],
                'phone' => $row['phone'],
                'note' => $row['note'],
                'locked' => $row['locked'],
            ];
            break;
        }

        return [
            'class' => (string) $section->name,
            'section_id' => (int) $section->id,
            'exam_set' => (string) $type->name,
            'term' => (string) $term->name,
            'subjects' => $subjects,
            'missing_subjects' => $missing,
            'unapproved_subjects' => array_values(array_filter(
                $subjects,
                fn (array $subject) => ($subject['submission'] ?? '') !== 'Approved',
            )),
            'learners_missing' => $learners,
            'set_status' => $tab,
            'teachers_not_submitted' => null,
            'teachers_gap' => self::TEACHERS_GAP,
        ];
    }

    /**
     * @return array<string, mixed>|string
     */
    public function reportStatus(User $user, string $name, ?int $learnerId): array|string
    {
        $resolved = $this->resolveLearner($user, $name, $learnerId);
        if (is_string($resolved)) {
            return $resolved;
        }

        /** @var User $learner */
        $learner = $resolved['user'];
        /** @var StandardLink $link */
        $link = $resolved['link'];
        $section = $resolved['section'];
        $schoolId = (int) $user->school_id;
        $term = AcademicTerm::query()
            ->where('school_id', $schoolId)
            ->where('status', 'current')
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->first();

        $gate = app(ReleaseGate::class);
        $released = $term !== null && $gate->isTermReportReleased($learner, (int) $term->id);
        $sittings = array_map(fn (ReleasedSitting $sitting) => [
            'school_id' => $sitting->schoolId,
            'exam_type_id' => $sitting->examTypeId,
            'academic_term_id' => $sitting->academicTermId,
            'section_id' => $sitting->sectionId,
        ], $gate->releasedSittings($learner));

        $included = app(StudentReportCardService::class)->examSetNames($schoolId, $link);
        $blocking = $term === null
            ? []
            : app(ReportComposition::class)->blockingSets($schoolId, (int) $section->id, (int) $term->id);

        $unreleased = [];
        if ($term !== null) {
            $sheet = app(ResultsService::class)->sheet(new ResultsQuery(
                schoolId: $schoolId,
                sectionId: (int) $section->id,
                academicTermId: (int) $term->id,
                standardLinkId: (int) $link->id,
                studentId: (int) $learner->id,
            ));
            $unreleased = $sheet->unreleasedWeightedSets;
        }

        return [
            'learner' => (string) $learner->name,
            'class' => (string) $section->name,
            'stream' => (string) ($link->stream ?? ''),
            'term' => $term?->name,
            'released' => $released,
            'included_sets' => $included,
            'not_released_yet' => $blocking,
            'unreleased_weighted_sets' => $unreleased,
            'released_sittings' => $sittings,
        ];
    }

    /**
     * @return list<string>
     */
    public function classTeacherNames(int $schoolId, Section $section): array
    {
        $ids = [];
        if ($section->class_teacher_id) {
            $ids[] = (int) $section->class_teacher_id;
        }
        $fromLinks = StandardLink::query()
            ->where('school_id', $schoolId)
            ->where('section_id', $section->id)
            ->whereNotNull('class_teacher_id')
            ->pluck('class_teacher_id');
        foreach ($fromLinks as $id) {
            $ids[] = (int) $id;
        }
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return [];
        }

        return User::query()
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->orderBy('id')
            ->pluck('name')
            ->map(fn ($name) => (string) $name)
            ->all();
    }

    public function draft(array $snapshot, array $teacherNames): string
    {
        $who = $teacherNames === []
            ? 'the class teacher (none is assigned)'
            : implode(', ', $teacherNames);
        $lines = [
            'Draft reminder for '.$who.', class teacher of '.$snapshot['class'].'.',
            $snapshot['exam_set'].' · '.$snapshot['term'].'.',
            'Subjects:',
        ];
        foreach ($snapshot['subjects'] as $subject) {
            $lines[] = '- '.$subject['name'].': '.$subject['submission'];
        }
        if ($snapshot['missing_subjects'] === []) {
            $lines[] = 'No subject is missing on the marks overview.';
        } else {
            $lines[] = 'Missing on the overview:';
            foreach ($snapshot['missing_subjects'] as $subject) {
                $lines[] = '- '.$subject['name'].': '.$subject['submission'];
            }
        }
        if ($snapshot['learners_missing'] === []) {
            $lines[] = 'No learner is marked Missing.';
        } else {
            $lines[] = 'Learners marked Missing:';
            foreach ($snapshot['learners_missing'] as $row) {
                $lines[] = '- '.$row['name'].' — '.$row['subject'].': '.$row['printed'];
            }
        }
        $lines[] = self::TEACHERS_GAP;
        $lines[] = 'Nothing is sent from this draft.';

        return implode("\n", $lines);
    }

    public function encode(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return Section|string
     */
    private function section(int $schoolId, string $className, ?array $allowed): Section|string
    {
        $needle = $this->needle($className);
        if ($needle === '') {
            return '❌ A class name is required.';
        }

        $inSchool = Section::query()
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->orderBy('name')
            ->orderBy('id')
            ->get();
        $named = $this->byName($inSchool, $needle);
        if ($named->isEmpty()) {
            return '❌ That class is not in your school.';
        }

        $inScope = $allowed === null
            ? $named
            : $named->filter(fn (Section $section) => in_array((int) $section->id, $allowed, true))->values();
        if ($inScope->isEmpty()) {
            return '❌ You can only open your own class.';
        }
        if ($inScope->count() > 1) {
            $list = $inScope->map(fn (Section $section) => $section->name.' ('.$section->id.')')->implode(', ');

            return 'Which class? '.$list.'.';
        }

        return $inScope->first();
    }

    /**
     * @return ExamType|string
     */
    private function examType(int $schoolId, string $examSet): ExamType|string
    {
        $needle = $this->needle($examSet);
        if ($needle === '') {
            return '❌ An exam set name is required.';
        }

        $types = ExamType::query()->visibleToSchool($schoolId)->orderBy('name')->orderBy('id')->get();
        $named = $this->byName($types, $needle);
        if ($named->isEmpty()) {
            return '❌ That exam set is not in your school.';
        }
        if ($named->count() > 1) {
            return 'Which exam set? '.$named->pluck('name')->implode(', ').'.';
        }

        return $named->first();
    }

    /**
     * @return AcademicTerm|string
     */
    private function term(int $schoolId, ?string $termName): AcademicTerm|string
    {
        $needle = $this->needle((string) $termName);
        $terms = AcademicTerm::query()
            ->where('school_id', $schoolId)
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get();

        if ($needle === '') {
            $current = $terms->filter(fn (AcademicTerm $term) => $term->status === 'current')->values();
            if ($current->isEmpty()) {
                return '❌ This school has no current term. Name the term.';
            }

            return $current->first();
        }

        $named = $this->byName($terms, $needle);
        if ($named->isEmpty()) {
            return '❌ That term is not in your school.';
        }
        if ($named->count() > 1) {
            return 'Which term? '.$named->pluck('name')->implode(', ').'.';
        }

        return $named->first();
    }

    /**
     * @return array{user: User, link: StandardLink, section: Section}|string
     */
    private function resolveLearner(User $user, string $name, ?int $learnerId): array|string
    {
        $needle = $this->needle($name);
        if ($needle === '') {
            return '❌ A learner name is required.';
        }

        $schoolId = (int) $user->school_id;
        $allowed = $this->allowedSectionIds($user);
        $matches = $this->enrolledMatches($schoolId, $needle, $allowed);

        if ($learnerId !== null) {
            $chosen = $matches->first(fn (array $row) => (int) $row['user']->id === $learnerId);
            if ($chosen === null) {
                $inSchool = User::query()
                    ->where('school_id', $schoolId)
                    ->where('usergroup_id', 6)
                    ->where('status', 'active')
                    ->whereKey($learnerId)
                    ->exists();

                return $inSchool
                    ? '❌ You can only open a learner in your own class.'
                    : '❌ That learner is not in your school.';
            }

            return $chosen;
        }

        if ($matches->isEmpty()) {
            $anyInSchool = $this->enrolledMatches($schoolId, $needle, null);

            return $anyInSchool->isEmpty()
                ? '❌ No learner by that name is in your school.'
                : '❌ No learner by that name is in your class.';
        }
        if ($matches->count() > 1) {
            $lines = $matches->map(function (array $row) {
                $stream = trim((string) ($row['link']->stream ?? ''));
                $class = trim($row['section']->name.($stream !== '' ? ' '.$stream : ''));

                return '- '.$row['user']->name.' ('.$class.', id '.$row['user']->id.')';
            })->implode("\n");

            return "Which learner?\n".$lines;
        }

        return $matches->first();
    }

    /**
     * @param  list<int>|null  $allowed
     * @return Collection<int, array{user: User, link: StandardLink, section: Section}>
     */
    private function enrolledMatches(int $schoolId, string $needle, ?array $allowed): Collection
    {
        $base = User::query()
            ->where('school_id', $schoolId)
            ->where('usergroup_id', 6)
            ->where('status', 'active');
        $exact = (clone $base)->whereRaw('LOWER(name) = ?', [$needle])->orderBy('id')->get();
        $users = $exact->isNotEmpty()
            ? $exact
            : (clone $base)->whereRaw("LOWER(name) LIKE ? ESCAPE '\\'", ['%'.$this->like($needle).'%'])->orderBy('id')->get();
        if ($users->isEmpty()) {
            return collect();
        }

        $year = SiteHelper::getAcademicYear($schoolId);
        $rows = StudentAcademic::query()
            ->where('school_id', $schoolId)
            ->whereIn('user_id', $users->pluck('id'))
            ->when($year !== null, fn ($query) => $query->where('academic_year_id', $year->id))
            ->with(['user', 'standardLink.section'])
            ->orderBy('id')
            ->get();

        $found = [];
        foreach ($rows as $row) {
            $link = $row->standardLink;
            $section = $link?->section;
            $student = $row->user;
            if ($link === null || $section === null || $student === null) {
                continue;
            }
            if ((int) $link->school_id !== $schoolId || (int) $section->school_id !== $schoolId) {
                continue;
            }
            if ($allowed !== null && ! in_array((int) $section->id, $allowed, true)) {
                continue;
            }
            $found[(int) $student->id] = [
                'user' => $student,
                'link' => $link,
                'section' => $section,
            ];
        }

        return collect(array_values($found));
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    private function byName(Collection $rows, string $needle): Collection
    {
        $exact = $rows->filter(fn ($row) => mb_strtolower(trim((string) $row->name)) === $needle)->values();
        if ($exact->isNotEmpty()) {
            return $exact;
        }

        return $rows->filter(function ($row) use ($needle) {
            return str_contains(mb_strtolower(trim((string) $row->name)), $needle);
        })->values();
    }

    private function needle(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    private function like(string $needle): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $needle);
    }
}
