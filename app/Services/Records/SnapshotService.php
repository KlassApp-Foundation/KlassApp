<?php

namespace App\Services\Records;

use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\MarksRelease;
use App\Models\Records\RecordSnapshot;
use App\Models\StandardLink;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Verifiable records, step 1: a release writes one immutable snapshot per
 * learner per term.
 *
 * No hashing, signing or QR here. The point of this step is that a released
 * term record stops being a function of whatever the marks table says today:
 * it becomes a row that is never edited, and a correction is a new version.
 *
 * A learner with no marks in the sitting is not snapshotted: an empty record is
 * not a record, and a release that messaged nobody must not create one.
 */
class SnapshotService
{
    public function __construct(private readonly ResultsService $results) {}

    /**
     * Write the snapshots for one release. Returns how many were written.
     *
     * Throws on failure: the caller must not message a family when the record
     * could not be written.
     */
    public function capture(MarksRelease $release, ?string $reason = null): int
    {
        $release->loadMissing(['exam.examType', 'exam.academicTerm', 'standardLink.section', 'school']);
        $exam = $release->exam;

        if ($exam === null || $exam->academic_term_id === null || $exam->academic_year_id === null) {
            throw new \RuntimeException('A release without a sitting cannot be snapshotted.');
        }

        $termId = (int) $exam->academic_term_id;
        $yearId = (int) $exam->academic_year_id;
        $written = 0;

        // The caller owns the transaction: the release row and every snapshot
        // are written together, all or none.
        foreach ($this->links($release) as $link) {
            $studentIds = $this->learnersWithMarks($release, (int) $link->section_id, $termId);

            if ($studentIds === []) {
                continue;
            }

            $sheet = $this->results->sheet(new ResultsQuery(
                schoolId: (int) $release->school_id,
                sectionId: (int) $link->section_id,
                academicTermId: $termId,
                standardLinkId: (int) $link->id,
                examTypeId: (int) $exam->exam_type_id,
            ));

            foreach ($studentIds as $studentId) {
                $row = $sheet->student($studentId);
                if ($row === null) {
                    continue;
                }

                $this->writeVersion($release, $studentId, $yearId, $termId, $this->payload($release, $link, $row), $reason);
                $written++;
            }
        }

        return $written;
    }

    /**
     * The class links in scope: the released class, or every class that sat
     * this sitting when the release covers the school.
     *
     * @return \Illuminate\Support\Collection<int, StandardLink>
     */
    private function links(MarksRelease $release): \Illuminate\Support\Collection
    {
        if ($release->standard_link_id !== null) {
            return StandardLink::query()
                ->with('section')
                ->where('school_id', $release->school_id)
                ->whereKey($release->standard_link_id)
                ->get();
        }

        $sectionIds = Exam::query()
            ->where('school_id', $release->school_id)
            ->where('exam_type_id', $release->exam->exam_type_id)
            ->where('academic_term_id', $release->exam->academic_term_id)
            ->whereNull('deleted_at')
            ->pluck('section_id')
            ->unique();

        return StandardLink::query()
            ->with('section')
            ->where('school_id', $release->school_id)
            ->whereIn('academic_year_id', $this->currentYearIds((int) $release->school_id))
            ->whereIn('section_id', $sectionIds)
            ->orderBy('section_id')
            ->orderBy('stream')
            ->get();
    }

    /** @return list<int> learners with a mark on this sitting in this class */
    private function learnersWithMarks(MarksRelease $release, int $sectionId, int $termId): array
    {
        $examIds = Exam::query()
            ->where('school_id', $release->school_id)
            ->where('section_id', $sectionId)
            ->where('exam_type_id', $release->exam->exam_type_id)
            ->where('academic_term_id', $termId)
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($examIds->isEmpty()) {
            return [];
        }

        return Marks::query()
            ->where('school_id', $release->school_id)
            ->whereIn('exam_id', $examIds)
            ->whereNotNull('marks')
            ->distinct()
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
    }

    /**
     * The record as a family receives it: subjects, marks, grades, totals, the
     * class place, and the identities needed to read it later.
     *
     * @return array<string, mixed>
     */
    private function payload(MarksRelease $release, StandardLink $link, \App\Services\Results\ResultStudent $row): array
    {
        $subjects = [];
        foreach ($row->cells as $cell) {
            if ($cell->mark === null) {
                continue;
            }
            $subjects[] = [
                'subject_id' => (int) $cell->subjectId,
                'mark' => (float) $cell->mark,
                'grade' => $cell->grade ?? null,
            ];
        }

        return [
            'school' => (string) ($release->school?->name ?? ''),
            'class' => trim((string) ($link->section?->name ?? '') . ' ' . (string) ($link->stream ?? '')),
            'stream' => (string) ($link->stream ?? ''),
            'sitting' => (string) ($release->exam?->examType?->name ?? '') . ' · ' . (string) ($release->exam?->academicTerm?->name ?? ''),
            'term_id' => (int) $release->exam->academic_term_id,
            'term' => (string) ($release->exam?->academicTerm?->name ?? ''),
            'year' => (string) ($release->exam?->academicYear?->name ?? ''),
            'subjects' => $subjects,
            'total' => $row->total,
            'average' => $row->average,
            'aggregate' => $row->aggregate,
            'division' => $row->division,
            'position' => $row->classPosition,
            'of' => $row->classOutOf,
            'released_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Insert one version and close the one it replaces, in one transaction.
     *
     * @param array<string, mixed> $payload
     */
    private function writeVersion(MarksRelease $release, int $studentId, int $yearId, int $termId, array $payload, ?string $reason): void
    {
        (function () use ($release, $studentId, $yearId, $termId, $payload, $reason): void {
            $current = RecordSnapshot::query()
                ->where('school_id', $release->school_id)
                ->where('student_id', $studentId)
                ->where('academic_year_id', $yearId)
                ->where('academic_term_id', $termId)
                ->where('state', RecordSnapshot::STATE_CURRENT)
                ->orderByDesc('version')
                ->lockForUpdate()
                ->first();

            $version = (int) ($current?->version ?? 0) + 1;

            if ($current !== null) {
                $current->forceFill(['state' => RecordSnapshot::STATE_SUPERSEDED])->save();
            }

            RecordSnapshot::create([
                'school_id' => $release->school_id,
                'student_id' => $studentId,
                'academic_year_id' => $yearId,
                'academic_term_id' => $termId,
                'version' => $version,
                'supersedes_id' => $current?->id,
                'reason' => $version > 1 ? $reason : null,
                'payload' => $payload,
                'released_at' => now(),
                'released_by' => $release->created_by,
                'release_id' => $release->id,
                'state' => RecordSnapshot::STATE_CURRENT,
            ]);
        })();
    }

    /** @return list<int> */
    private function currentYearIds(int $schoolId): array
    {
        return DB::table('academic_years')
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
