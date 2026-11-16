<?php

namespace App\Services\Setup;

use App\Helpers\SiteHelper;
use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Links a section to a class level (a standards_link row) for one academic year, so the section can
 * hold learners and show on class screens. Everything is scoped by school_id: a section, class level
 * or year of another school is reported as not found, exactly like one that does not exist.
 */
final class SectionClassLinker
{
    public const NOT_FOUND = 'not_found';

    public const ALREADY_LINKED = 'already_linked';

    public const OK = 'ok';

    /**
     * Sections of this school with no link for the year, in name order.
     *
     * @return \Illuminate\Support\Collection<int, Section>
     */
    public function unlinkedSections(int $schoolId, int $yearId)
    {
        $linked = StandardLink::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $yearId)
            ->pluck('section_id');

        return Section::query()
            ->where('school_id', $schoolId)
            ->where(fn ($q) => $q->where('status', 1)->orWhere('status', '1'))
            ->whereNotIn('id', $linked)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{result: string, section: ?Section, standard: ?Standard, year: ?AcademicYear}
     */
    public function plan(int $schoolId, int $sectionId, int $standardId, ?int $yearId = null): array
    {
        $section = Section::query()->where('school_id', $schoolId)->whereKey($sectionId)->first();
        $standard = Standard::query()->where('school_id', $schoolId)->whereKey($standardId)->first();
        $year = $yearId === null
            ? SiteHelper::getAcademicYear($schoolId)
            : AcademicYear::query()->where('school_id', $schoolId)->whereKey($yearId)->first();

        if ($section === null || $standard === null || $year === null) {
            return ['result' => self::NOT_FOUND, 'section' => $section, 'standard' => $standard, 'year' => $year];
        }

        $exists = StandardLink::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->where('section_id', $section->id)
            ->exists();

        return ['result' => $exists ? self::ALREADY_LINKED : self::OK, 'section' => $section, 'standard' => $standard, 'year' => $year];
    }

    /**
     * Apply the link and write one log line. Only the result and the plan are returned.
     *
     * @return array{result: string, section: ?Section, standard: ?Standard, year: ?AcademicYear, link: ?StandardLink}
     */
    public function apply(int $schoolId, int $sectionId, int $standardId, ?int $yearId, ?int $actorId, string $via): array
    {
        $plan = $this->plan($schoolId, $sectionId, $standardId, $yearId);
        if ($plan['result'] !== self::OK) {
            return $plan + ['link' => null];
        }

        $link = StandardLink::query()->create([
            'school_id' => $schoolId,
            'academic_year_id' => $plan['year']->id,
            'standard_id' => $plan['standard']->id,
            'section_id' => $plan['section']->id,
            'status' => 1,
        ]);

        Log::info('setup.link-section', [
            'school_id' => $schoolId,
            'section_id' => (int) $plan['section']->id,
            'standard_id' => (int) $plan['standard']->id,
            'academic_year_id' => (int) $plan['year']->id,
            'standard_link_id' => (int) $link->id,
            'actor_id' => $actorId,
            'via' => $via,
        ]);

        $log = activity()->performedOn($link)->useLog('setup')->withProperties([
            'school_id' => $schoolId, 'section_id' => (int) $plan['section']->id, 'standard_id' => (int) $plan['standard']->id,
            'academic_year_id' => (int) $plan['year']->id, 'via' => $via,
        ]);
        if ($actorId !== null && ($actor = User::query()->where('school_id', $schoolId)->find($actorId)) !== null) {
            $log->causedBy($actor);
        }
        $log->log('Linked section "'.$plan['section']->name.'" to class level "'.$plan['standard']->name.'"');

        return $plan + ['link' => $link];
    }
}
