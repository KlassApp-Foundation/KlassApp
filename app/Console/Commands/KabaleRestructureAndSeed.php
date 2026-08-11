<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Section;
use App\Models\AcademicYear;
use App\Models\Academics\SchoolGradingSystem;

class KabaleRestructureAndSeed extends Command
{
    protected $signature = 'kabale:restructure-and-seed
                            {--dry-run : Report what would change without writing}';

    protected $description = 'Restructure Kabale (school 104) into 4 standards with corrected grading, per Sections — tested Artisan command, no raw SQL';

    private const CHILD_TABLES_BY_STANDARDLINK = [
        'student_academics'           => 'standardLink_id',
        'class_teacher_links'         => 'standardLink_id',
        'assignments'                 => 'standardLink_id',
        'attendances'                 => 'standardLink_id',
        'disciplines'                 => 'standardLink_id',
        'homeworks'                   => 'standardLink_id',
        'task_assignees'              => 'standardLink_id',
        'teacher_leave_applications'  => 'standardLink_id',
    ];

    private const CHILD_TABLES_BY_STANDARD = [
        'exams'       => 'standard_id',
        'subjects'    => 'standard_id',
        'chapters'    => 'standard_id',
        'fees_categories' => 'standard_id',
        'student_promotion_rules' => 'standard_id',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $school = School::findOrFail(104);

        if ($school->registration_country !== 'Uganda') {
            $this->warn("School 104 ({$school->name}) is not Uganda — aborting.");
            return 1;
        }

        // ── Identify existing standards ──────────────────────────────────
        $primary = Standard::where('school_id', 104)->where('name', 'primary')->first();
        $nursery = Standard::where('school_id', 104)->where('name', 'nursery')->first();

        if (!$primary || !$nursery) {
            $this->error('Expected standards "primary" and "nursery" not both present.');
            return 1;
        }

        $this->info("School: {$school->name} (id=104)");
        $this->line("  Existing: nursery id={$nursery->id}, primary id={$primary->id}");

        // ── Create new standards ─────────────────────────────────────────
        $lower  = Standard::firstOrCreate(
            ['school_id' => 104, 'name' => 'primary_lower'],
            ['order' => 1, 'status' => '1']
        );
        $middle = $primary; // keep existing "primary" as middle (P.4-P6)
        $upper  = Standard::firstOrCreate(
            ['school_id' => 104, 'name' => 'primary_upper'],
            ['order' => 3, 'status' => '1']
        );

        $this->line("  Created/found: lower id={$lower->id} ({$lower->wasRecentlyCreated}), upper id={$upper->id} ({$upper->wasRecentlyCreated})");

        // ── Map sections to class groups ─────────────────────────────────
        $sections = $this->classifySections($school->id);

        $this->line("  Sections: lower=" . implode(',', $sections['lower']) . " middle=" . implode(',', $sections['middle']) . " upper=" . implode(',', $sections['upper']));

        if (empty($sections['lower']) && empty($sections['upper'])) {
            $this->info('Already restructured — nothing to move.');
        }

        DB::beginTransaction();
        try {
            $this->reassignSections($school, $sections, $lower, $middle, $upper, $dryRun);
            $this->seedGradingForAll($school, $lower, $middle, $upper, $nursery, $dryRun);

            if ($dryRun) {
                DB::rollBack();
                $this->info('Dry run complete — changes rolled back.');
            } else {
                DB::commit();
                $this->info('Restructure + grading seed committed.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    private function classifySections(int $schoolId): array
    {
        $all = Section::where('school_id', $schoolId)->get()->keyBy('id');
        $map = ['lower' => [], 'middle' => [], 'upper' => []];

        foreach ($all as $sec) {
            $name = strtolower(trim($sec->name));
            if (preg_match('/primary\s+(one|two|three|1|2|3)/i', $name)) {
                $map['lower'][] = $sec->id;
            } elseif (preg_match('/primary\s+(seven|7)/i', $name)) {
                $map['upper'][] = $sec->id;
            } else {
                $map['middle'][] = $sec->id; // P.4, P.5, P.6 stay with original primary
            }
        }
        return $map;
    }

    private function reassignSections(School $school, array $sections, Standard $lower, Standard $middle, Standard $upper, bool $dryRun): void
    {
        $year = AcademicYear::where('school_id', $school->id)->where('status', 1)->first();
        if (!$year) {
            $year = AcademicYear::where('school_id', $school->id)->first();
        }

        $moves = [
            'lower' => $lower,
            'upper' => $upper,
        ];

        foreach ($moves as $group => $newStandard) {
            $sectionIds = $sections[$group];
            if (empty($sectionIds)) {
                continue;
            }

            foreach ($sectionIds as $sectionId) {
                $oldLink = StandardLink::where('school_id', $school->id)
                    ->where('section_id', $sectionId)
                    ->first();

                if (!$oldLink) {
                    $this->warn("  No standards_link for section_id={$sectionId} — skipping");
                    continue;
                }

                if ((int) $oldLink->standard_id === (int) $newStandard->id) {
                    $this->line("  Section {$sectionId} already linked to {$newStandard->name} — skipping");
                    continue;
                }

                $newLink = StandardLink::firstOrCreate([
                    'school_id'        => $school->id,
                    'academic_year_id' => $year->id,
                    'standard_id'      => $newStandard->id,
                    'section_id'       => $sectionId,
                ], [
                    'status'           => '1',
                ]);

                $this->line("  Section {$sectionId}: standards_link {$oldLink->id} → {$newLink->id} (standard {$oldLink->standard_id} → {$newStandard->id} [{$newStandard->name}])");

                if ($dryRun) {
                    continue;
                }

                $this->moveChildRecords($oldLink->id, (int) $newLink->id, $oldLink->standard_id, $newStandard->id);
            }
        }
    }

    private function moveChildRecords(int $oldStdLinkId, int $newStdLinkId, int $oldStandardId, int $newStandardId): void
    {
        foreach (self::CHILD_TABLES_BY_STANDARDLINK as $table => $col) {
            $updated = DB::table($table)
                ->where($col, $oldStdLinkId)
                ->update([$col => $newStdLinkId]);
            if ($updated > 0) {
                $this->line("    {$table}.{$col}: {$updated} row(s) updated");
            }
        }

        foreach (self::CHILD_TABLES_BY_STANDARD as $table => $col) {
            $updated = DB::table($table)
                ->where($col, $oldStandardId)
                ->update([$col => $newStandardId]);
            if ($updated > 0) {
                $this->line("    {$table}.{$col}: {$updated} row(s) updated");
            }
        }
    }

    private function seedGradingForAll(School $school, Standard $lower, Standard $middle, Standard $upper, Standard $nursery, bool $dryRun): void
    {
        $this->line("\n--- Grading seed ---");

        foreach (['nursery' => $nursery, 'primary_lower' => $lower] as $label => $std) {
            $this->seedBands($school->id, $std->id, $label, $this->p1p3NurseryBands(), $dryRun);
        }

        $this->seedBands($school->id, $middle->id, 'primary (P4-P6)', $this->p4p6Bands(), $dryRun);
        $this->seedBands($school->id, $upper->id, 'primary_upper (P7)', $this->p7Bands(), $dryRun);
    }

    private function seedBands(int $schoolId, int $standardId, string $label, array $bands, bool $dryRun): void
    {
        $this->line("  {$label} (standard_id={$standardId}): " . count($bands) . " bands");
        if ($dryRun) {
            foreach ($bands as $b) {
                $this->line("    grade={$b['grade']} points=" . ($b['points'] ?? 'null') . " score={$b['min_score']}-{$b['max_score']} remark={$b['remark']}");
            }
            return;
        }

        SchoolGradingSystem::where('school_id', $schoolId)
            ->where('standard_id', $standardId)
            ->delete();

        foreach ($bands as $b) {
            SchoolGradingSystem::create([
                'school_id'   => $schoolId,
                'standard_id' => $standardId,
                'grade'       => $b['grade'],
                'points'      => $b['points'] ?? null,
                'min_score'   => $b['min_score'],
                'max_score'   => $b['max_score'],
                'remark'      => $b['remark'],
            ]);
        }
    }

    private function p1p3NurseryBands(): array
    {
        return [
            ['grade' => 'Excellent',        'points' => null, 'min_score' => 95, 'max_score' => 100, 'remark' => 'Consistently exceeds expectations'],
            ['grade' => 'V.Good',           'points' => null, 'min_score' => 85, 'max_score' => 94,  'remark' => 'Very good performance'],
            ['grade' => 'Good',             'points' => null, 'min_score' => 75, 'max_score' => 84,  'remark' => 'Good understanding'],
            ['grade' => 'F.Good/Q.Good',    'points' => null, 'min_score' => 65, 'max_score' => 74,  'remark' => 'Fairly good / quite good'],
            ['grade' => 'Promising',        'points' => null, 'min_score' => 60, 'max_score' => 64,  'remark' => 'Shows promise'],
            ['grade' => 'Fair',             'points' => null, 'min_score' => 50, 'max_score' => 59,  'remark' => 'Satisfactory effort'],
            ['grade' => 'Work hard',        'points' => null, 'min_score' => 45, 'max_score' => 49,  'remark' => 'Needs to work harder'],
            ['grade' => 'Aim higher',       'points' => null, 'min_score' => 40, 'max_score' => 44,  'remark' => 'Should aim higher'],
            ['grade' => 'More effort',      'points' => null, 'min_score' => 0,  'max_score' => 39,  'remark' => 'More effort needed'],
        ];
    }

    private function p4p6Bands(): array
    {
        return [
            ['grade' => '1', 'points' => 1, 'min_score' => 95, 'max_score' => 100, 'remark' => 'Excellent'],
            ['grade' => '2', 'points' => 2, 'min_score' => 85, 'max_score' => 94,  'remark' => 'V.Good'],
            ['grade' => '3', 'points' => 3, 'min_score' => 75, 'max_score' => 84,  'remark' => 'Good'],
            ['grade' => '4', 'points' => 4, 'min_score' => 65, 'max_score' => 74,  'remark' => 'F.Good/Q.Good'],
            ['grade' => '5', 'points' => 5, 'min_score' => 60, 'max_score' => 64,  'remark' => 'Promising'],
            ['grade' => '6', 'points' => 6, 'min_score' => 50, 'max_score' => 59,  'remark' => 'Fair'],
            ['grade' => '7', 'points' => 7, 'min_score' => 45, 'max_score' => 49,  'remark' => 'Work hard'],
            ['grade' => '8', 'points' => 8, 'min_score' => 40, 'max_score' => 44,  'remark' => 'Aim higher'],
            ['grade' => '9', 'points' => 8, 'min_score' => 0,  'max_score' => 39,  'remark' => 'More effort'],
        ];
    }

    private function p7Bands(): array
    {
        return [
            ['grade' => '1', 'points' => 1, 'min_score' => 95, 'max_score' => 100, 'remark' => 'Excellent'],
            ['grade' => '2', 'points' => 2, 'min_score' => 85, 'max_score' => 94,  'remark' => 'V.Good'],
            ['grade' => '3', 'points' => 3, 'min_score' => 75, 'max_score' => 84,  'remark' => 'Good'],
            ['grade' => '4', 'points' => 4, 'min_score' => 65, 'max_score' => 74,  'remark' => 'F.Good/Q.Good'],
            ['grade' => '5', 'points' => 5, 'min_score' => 60, 'max_score' => 64,  'remark' => 'Promising'],
            ['grade' => '6', 'points' => 6, 'min_score' => 50, 'max_score' => 59,  'remark' => 'Fair'],
            ['grade' => '7', 'points' => 7, 'min_score' => 45, 'max_score' => 49,  'remark' => 'Work hard'],
            ['grade' => '8', 'points' => 8, 'min_score' => 40, 'max_score' => 44,  'remark' => 'Aim higher'],
            ['grade' => '9', 'points' => 9, 'min_score' => 0,  'max_score' => 39,  'remark' => 'More effort'],
        ];
    }
}