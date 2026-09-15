<?php

namespace App\Console\Commands;

use App\Helpers\DashboardCache;
use App\Helpers\SiteHelper;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\StudentIdGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repair active students who have a users row but no student_academics row.
 *
 * Collision-era onboarding (pre-transaction) could leave orphans when
 * StudentAcademic::create failed after User::create. Prefer the modal
 * standardLink_id from same-second batch-mates; else the school's first
 * current-year StandardLink. Class may still need a human check.
 */
class RepairOrphanStudentAcademics extends Command
{
    protected $signature = 'students:repair-orphan-academics
                            {--school= : Limit to one school_id}
                            {--dry-run : Report only; do not write}';

    protected $description = 'Create missing student_academics rows for active students (orphan repair)';

    public function handle(): int
    {
        $schoolFilter = $this->option('school');
        $dryRun = (bool) $this->option('dry-run');

        $query = User::query()
            ->where('usergroup_id', 6)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->whereDoesntHave('studentAcademic')
            ->orderBy('school_id')
            ->orderBy('id');

        if ($schoolFilter !== null && $schoolFilter !== '') {
            $query->where('school_id', (int) $schoolFilter);
        }

        $orphans = $query->get();
        if ($orphans->isEmpty()) {
            $this->info('No orphaned active students found.');

            return self::SUCCESS;
        }

        $this->info('Found '.$orphans->count().' orphan(s).'.($dryRun ? ' (dry-run)' : ''));

        $repaired = 0;
        $skipped = 0;

        foreach ($orphans as $student) {
            $linkId = $this->resolveStandardLinkId($student);
            if ($linkId === null) {
                $this->warn("SKIP user {$student->id} ({$student->name}) school {$student->school_id}: no StandardLink for current AY");
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line("WOULD repair user {$student->id} ({$student->name}) → standardLink_id={$linkId}");
                $repaired++;

                continue;
            }

            DB::transaction(function () use ($student, $linkId) {
                $klassappId = StudentIdGeneratorService::nextForStudent($student);
                $profileLin = optional($student->userprofile)->LIN;
                $lin = is_string($profileLin) && trim($profileLin) !== '' ? trim($profileLin) : null;

                StudentAcademic::create([
                    'school_id' => $student->school_id,
                    'academic_year_id' => StandardLink::find($linkId)?->academic_year_id
                        ?? SiteHelper::getAcademicYear($student->school_id)?->id,
                    'user_id' => $student->id,
                    'standardLink_id' => $linkId,
                    'klassapp_student_id' => $klassappId,
                    'lin' => $lin,
                ]);

                DashboardCache::forgetRosterCounts((int) $student->school_id);
            });

            $this->info("Repaired user {$student->id} ({$student->name}) → standardLink_id={$linkId}");
            $repaired++;
        }

        $this->info("Done. repaired={$repaired} skipped={$skipped}");

        return $skipped > 0 && $repaired === 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolveStandardLinkId(User $student): ?int
    {
        $created = $student->created_at;
        if ($created !== null) {
            $batchMateIds = User::query()
                ->where('school_id', $student->school_id)
                ->where('usergroup_id', 6)
                ->where('id', '!=', $student->id)
                ->whereBetween('created_at', [
                    $created->copy()->subSeconds(5),
                    $created->copy()->addSeconds(5),
                ])
                ->pluck('id');

            if ($batchMateIds->isNotEmpty()) {
                $modal = StudentAcademic::query()
                    ->whereIn('user_id', $batchMateIds)
                    ->select('standardLink_id', DB::raw('COUNT(*) as c'))
                    ->groupBy('standardLink_id')
                    ->orderByDesc('c')
                    ->orderBy('standardLink_id')
                    ->first();

                if ($modal && $modal->standardLink_id) {
                    return (int) $modal->standardLink_id;
                }
            }
        }

        $year = SiteHelper::getAcademicYear($student->school_id);
        if ($year === null) {
            return null;
        }

        $first = StandardLink::query()
            ->where('school_id', $student->school_id)
            ->where('academic_year_id', $year->id)
            ->orderBy('id')
            ->value('id');

        return $first !== null ? (int) $first : null;
    }
}
