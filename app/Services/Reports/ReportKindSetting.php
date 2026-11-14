<?php

namespace App\Services\Reports;

use App\Models\MarksRelease;
use App\Models\School;
use App\Models\SchoolReportKindChange;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Progressive report or set-based reports, one choice per school.
 *
 * The choice is stored on the school. A release copies it onto the release
 * row, and that copy is what a released report keeps.
 */
final class ReportKindSetting
{
    public const PROGRESSIVE = 'progressive';

    public const SET_BASED = 'set_based';

    /**
     * Screen copy. The keys are the stored values.
     *
     * @var array<string, array{label: string, description: string}>
     */
    public const OPTIONS = [
        self::PROGRESSIVE => [
            'label' => 'Progressive report',
            'description' => 'The formal card titled PROGRESSIVE REPORT. It has a monthly results table and an end-of-term table, a mark and a grade for each subject, and the aggregate, division and position for classes that use them.',
        ],
        self::SET_BASED => [
            'label' => 'Set-based reports',
            'description' => 'Each exam set has its own report, and a combined report uses the weights for the sets that count. Those weights are on Exam Type Settings.',
        ],
    ];

    public function current(?School $school): string
    {
        $kind = (string) ($school->report_kind ?? '');

        return isset(self::OPTIONS[$kind]) ? $kind : self::PROGRESSIVE;
    }

    public function label(string $kind): string
    {
        return self::OPTIONS[$kind]['label'] ?? self::OPTIONS[self::PROGRESSIVE]['label'];
    }

    /**
     * The type a released row was issued under.
     * A release from before this choice existed has no stored type and stays progressive.
     */
    public function forRelease(?MarksRelease $release, ?School $school): string
    {
        if ($release !== null && $release->status === MarksRelease::STATUS_RELEASED) {
            $frozen = (string) ($release->report_kind ?? '');

            return isset(self::OPTIONS[$frozen]) ? $frozen : self::PROGRESSIVE;
        }

        return $this->current($school);
    }

    public function change(School $school, User $actor, string $to): void
    {
        if (! isset(self::OPTIONS[$to])) {
            throw ValidationException::withMessages([
                'report_kind' => 'Choose Progressive report or Set-based reports.',
            ]);
        }

        if ((int) $actor->school_id !== (int) $school->id) {
            abort(403);
        }

        $from = $this->current($school);
        if ($from === $to) {
            return;
        }

        $school->report_kind = $to;
        $school->save();

        SchoolReportKindChange::query()->create([
            'school_id' => $school->id,
            'user_id' => $actor->id,
            'from_kind' => $from,
            'to_kind' => $to,
        ]);
    }

    /**
     * Copy the school's current type onto a release that does not have one yet.
     * A second call leaves the first copy in place.
     */
    public function freeze(MarksRelease $release): string
    {
        $existing = (string) ($release->report_kind ?? '');
        if (isset(self::OPTIONS[$existing])) {
            return $existing;
        }

        $school = $release->relationLoaded('school') ? $release->school : $release->school()->first();
        $kind = $this->current($school);
        $release->report_kind = $kind;
        $release->save();

        return $kind;
    }
}
