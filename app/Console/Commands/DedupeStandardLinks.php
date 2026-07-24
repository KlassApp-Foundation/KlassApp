<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DedupeStandardLinks extends Command
{
    protected $signature = 'standards-link:dedupe
        {--dry-run : Report duplicates without making changes}';

    protected $description = 'Deduplicate standards_link rows where (school_id, section_id, academic_year_id, stream) repeats';

    private const CHILD_TABLES = [
        'student_academics'       => 'standardLink_id',
        'class_teacher_links'     => 'standardLink_id',
        'assignments'             => 'standardLink_id',
        'attendances'             => 'standardLink_id',
        'disciplines'             => 'standardLink_id',
        'homeworks'               => 'standardLink_id',
        'notice_board'            => 'standardLink_id',
        'task_assignees'          => 'standardLink_id',
        'teacher_leave_applications' => 'standardLink_id',
        'chapters'                => 'standard_id',
        'events'                  => 'standard_id',
        'posts'                   => 'visible_for',
    ];

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $groups = $this->duplicateGroups();
        $totalNonSurvivors = 0;

        if (empty($groups)) {
            $this->info('No duplicate groups found.');
            return 0;
        }

        $this->line("Found " . count($groups) . " duplicate group(s).");

        foreach ($groups as $g) {
            $rows = $this->groupRows($g);
            $survivor = $this->selectSurvivor($rows);
            $nonSurvivors = array_filter($rows, fn($r) => $r->id !== $survivor->id);

            $totalNonSurvivors += count($nonSurvivors);

            // Dry-run / summary output
            $this->line("");
            $this->line("Group: school_id={$g->school_id} section_id={$g->section_id} academic_year_id={$g->academic_year_id} stream=" . ($g->stream ?? '(null)'));
            $this->line("  {$g->cnt} rows: ids=" . implode(',', array_map(fn($r) => $r->id, $rows)));
            $this->line("  Survivor: id={$survivor->id}" . ($survivor->is_first ? ' (first created)' : '') . " ({$survivor->child_total} child records)");

            // Check non-survivors for conflicting non-null data vs survivor
            $conflicts = $this->findConflicts($survivor, $nonSurvivors);
            if (!empty($conflicts)) {
                $this->warn("  ⚠️  CONFLICT: Non-survivor rows have differing non-null values for: " . implode(', ', $conflicts));
                $this->warn("      This group requires manual review — skipping from auto-dedup.");
                continue;
            }

            // Check for children on non-survivor rows
            $childrenOnNonSurvivors = $this->countChildrenOn($nonSurvivors);
            if ($childrenOnNonSurvivors > 0) {
                $this->line("  Repointing {$childrenOnNonSurvivors} child record(s) from non-survivors to survivor id={$survivor->id}");
            }

            if ($dryRun) {
                $this->line("  Would delete " . count($nonSurvivors) . " row(s), keep id={$survivor->id}");
                continue;
            }

            $this->executeDedup($survivor, $nonSurvivors);
        }

        if ($dryRun) {
            $summary = "Dry-run complete. {$totalNonSurvivors} row(s) across " . count($groups) . " group(s) would be removed.";
            if ($totalNonSurvivors > 0) {
                $this->warn($summary);
            } else {
                $this->info($summary);
            }
        } else {
            $this->info("Dedup complete. {$totalNonSurvivors} duplicate row(s) removed.");
        }

        return 0;
    }

    private function duplicateGroups(): array
    {
        return DB::select("
            SELECT school_id, section_id, academic_year_id, stream, COUNT(*) as cnt
            FROM standards_link
            GROUP BY school_id, section_id, academic_year_id, stream
            HAVING cnt > 1
            ORDER BY school_id, section_id
        ");
    }

    private function groupRows(object $group): array
    {
        $rows = DB::select("
            SELECT sl.id, sl.class_teacher_id, sl.no_of_students, sl.created_at,
                (SELECT COUNT(*) FROM student_academics sa WHERE sa.standardLink_id = sl.id) as student_count,
                (SELECT COUNT(*) FROM class_teacher_links ctl WHERE ctl.standardLink_id = sl.id) as teacher_count,
                (SELECT COUNT(*) FROM attendances at WHERE at.standardLink_id = sl.id) as attendance_count,
                (SELECT COUNT(*) FROM assignments a WHERE a.standardLink_id = sl.id) as assignment_count,
                (SELECT COUNT(*) FROM homeworks h WHERE h.standardLink_id = sl.id) as homework_count,
                (SELECT COUNT(*) FROM disciplines d WHERE d.standardLink_id = sl.id) as discipline_count,
                (SELECT COUNT(*) FROM notice_board nb WHERE nb.standardLink_id = sl.id) as notice_count,
                (SELECT COUNT(*) FROM task_assignees ta WHERE ta.standardLink_id = sl.id) as task_count,
                (SELECT COUNT(*) FROM teacher_leave_applications tla WHERE tla.standardLink_id = sl.id) as leave_count,
                (SELECT COUNT(*) FROM chapters ch WHERE ch.standard_id = sl.id) as chapter_count,
                (SELECT COUNT(*) FROM events ev WHERE ev.standard_id = sl.id) as event_count,
                (SELECT COUNT(*) FROM posts p WHERE p.visible_for = sl.id) as post_count
            FROM standards_link sl
            WHERE sl.school_id = ? AND sl.section_id = ? AND sl.academic_year_id = ? AND (sl.stream = ? OR (sl.stream IS NULL AND ? IS NULL))
            ORDER BY sl.id
        ", [$group->school_id, $group->section_id, $group->academic_year_id, $group->stream, $group->stream]);

        // Annotate with derived fields
        foreach ($rows as $r) {
            $r->child_total = $r->student_count + $r->teacher_count + $r->attendance_count
                + $r->assignment_count + $r->homework_count + $r->discipline_count
                + $r->notice_count + $r->task_count + $r->leave_count
                + $r->chapter_count + $r->event_count + $r->post_count;
        }

        usort($rows, fn($a, $b) => $a->id <=> $b->id);
        $firstCreatedAt = $rows[0]->created_at;
        foreach ($rows as $r) {
            $r->is_first = ($r->created_at == $firstCreatedAt && $r->id === $rows[0]->id);
        }

        return $rows;
    }

    private function selectSurvivor(array $rows): object
    {
        // 1. Prefer row with most child records
        usort($rows, fn($a, $b) => $b->child_total <=> $a->child_total);
        $best = $rows[0];

        // 2. Tie-break: earliest created_at (lowest ID)
        $tied = array_filter($rows, fn($r) => $r->child_total === $best->child_total);
        if (count($tied) > 1) {
            usort($tied, fn($a, $b) => $a->id <=> $b->id);
            $best = $tied[0];
        }

        return $best;
    }

    private function findConflicts(object $survivor, array $nonSurvivors): array
    {
        $fields = ['class_teacher_id', 'no_of_students'];
        $conflicts = [];
        foreach ($fields as $field) {
            $survivorVal = $survivor->$field;
            foreach ($nonSurvivors as $ns) {
                if ($ns->$field !== null && $survivorVal !== null && $ns->$field !== $survivorVal) {
                    $conflicts[] = $field;
                    break;
                }
            }
        }
        return $conflicts;
    }

    private function countChildrenOn(array $rows): int
    {
        if (empty($rows)) return 0;
        $ids = array_map(fn($r) => $r->id, $rows);
        $total = 0;
        foreach (self::CHILD_TABLES as $table => $column) {
            $total += DB::table($table)->whereIn($column, $ids)->count();
        }
        return $total;
    }

    private function executeDedup(object $survivor, array $nonSurvivors): void
    {
        $schoolId = $survivor->school_id;

        DB::transaction(function () use ($survivor, $nonSurvivors, $schoolId) {
            $nonSurvivorIds = array_map(fn($r) => $r->id, $nonSurvivors);

            // Repoint children from non-survivor rows to survivor
            foreach (self::CHILD_TABLES as $table => $column) {
                $affected = DB::table($table)
                    ->whereIn($column, $nonSurvivorIds)
                    ->update([$column => $survivor->id]);
                if ($affected > 0) {
                    $this->line("    Repointed {$affected} {$table} row(s) → id={$survivor->id}");
                }
            }

            DB::table('standards_link')->whereIn('id', $nonSurvivorIds)->delete();
            $this->line("  Deleted " . count($nonSurvivorIds) . " duplicate(s), kept id={$survivor->id}");
        });
    }
}