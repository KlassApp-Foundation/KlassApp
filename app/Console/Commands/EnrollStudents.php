<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\StandardLink;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;

class EnrollStudents extends Command
{
    protected $signature = 'kabale:enroll-students
                            {--sheet= : Sheet name (P1East, P1West, P2East, ..., P7B, BabyClass, MiddleClass, TopClass)}
                            {--default-gender=N/A : Default gender when cell is empty}
                            {--dry-run : Preview without writing}';

    protected $description = 'Enroll Kabale students from authoritative roster Excel files into current 4-standard structure';

    private const STD_MAP = [
        'P.1' => 'primary_lower', 'P.2' => 'primary_lower', 'P.3' => 'primary_lower',
        'P.4' => 'primary',       'P.5' => 'primary',       'P.6' => 'primary',
        'P.7' => 'primary_upper',
        'Baby Class' => 'nursery', 'Middle Class' => 'nursery', 'Top Class' => 'nursery',
    ];

    public function handle(): int
    {
        $sheet = $this->option('sheet');
        $defaultGender = $this->option('default-gender') ?: 'N/A';
        $dryRun = $this->option('dry-run');
        $school = School::findOrFail(104);

        $file = $sheet && in_array($sheet, ['BabyClass', 'MiddleClass', 'TopClass'])
            ? storage_path('app/Nursery_Students.xlsx')
            : storage_path('app/All_Students_P1_to_P7_final.xlsx');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $sheets = Excel::toArray(new SheetsOnlyImport, $file);
        $allSheetNames = array_keys($sheets);

        if ($sheet) {
            $allSheets = Excel::toArray(new SheetsOnlyImport, $file);
            $sheets = $allSheets;
            // Map sheet names from the "All Students" summary sheet or try by index
            $targetIndex = $this->findSheetIndex($sheets, $sheet);
            if ($targetIndex === null) {
                $this->error("Sheet '{$sheet}' not found. Available indices: " . implode(', ', array_keys($sheets)));
                return 1;
            }
            $sheetNames = [$targetIndex];
        } else {
            // Skip "All Students" (index 0)
            $sheetNames = array_filter(array_keys($sheets), fn($k) => $k !== 0);
        }

        foreach ($sheetNames as $sn) {
            $rows = $sheets[$sn];
            $label = is_string($sn) ? $sn : "sheet {$sn}";
            $this->enrollSheet($rows, $label, $school->id, $defaultGender, $dryRun);
        }

        return 0;
    }

    private function enrollSheet(array $rows, string $sheetName, int $schoolId, string $defaultGender, bool $dryRun): void
    {
        $headers = array_map(fn($h) => strtolower(trim((string) $h)), $rows[0] ?? []);
        $dataRows = array_slice($rows, 1);
        $stdLinks = $this->loadStdLinks($schoolId);

        $enrolled = 0;
        $skipped = 0;
        $errors = 0;

        $this->info("--- {$sheetName}: " . count($dataRows) . " rows ---");

        foreach ($dataRows as $i => $raw) {
            $row = array_combine($headers, array_slice(array_values($raw), 0, count($headers)));
            $firstName = trim((string) ($row['firstname'] ?? ''));
            $lastName = trim((string) ($row['lastname'] ?? ''));
            if ($firstName === '' && $lastName === '') continue;

            $fullName = trim($firstName . ' ' . $lastName);
            $className = trim((string) ($row['class'] ?? ''));
            $gender = trim((string) ($row['gender'] ?? ''));
            if ($gender === '' || $gender === null) $gender = $defaultGender;

            if ($className === '') {
                $this->warn("  Row {$i}: no class — skipping {$fullName}");
                $errors++;
                continue;
            }

            $std = self::STD_MAP[$className] ?? null;
            if (!$std) {
                $this->warn("  Row {$i}: unknown class '{$className}' — skipping {$fullName}");
                $errors++;
                continue;
            }

            $key = strtoupper(str_replace(' ', '', $className)) . '|' . $std;
            $stdLinkId = $stdLinks[$key] ?? null;
            if (!$stdLinkId) {
                $this->warn("  Row {$i}: no stdLink for {$className}/{$std} — skipping {$fullName}");
                $errors++;
                continue;
            }

            $sectionId = DB::table('standards_link')->where('id', $stdLinkId)->value('section_id');

            $exists = User::where('school_id', $schoolId)
                ->where('usergroup_id', 6)
                ->where('name', $fullName)
                ->whereHas('studentAcademic', fn($q) => $q->where('standardLink_id', $stdLinkId))
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("  Would enroll: {$fullName} → {$className} ({$std}) gender={$gender}");
                $enrolled++;
                continue;
            }

            DB::beginTransaction();
            try {
                $user = User::create([
                    'name' => $fullName,
                    'email' => null,
                    'school_id' => $schoolId,
                    'usergroup_id' => 6,
                    'password' => bcrypt('password'),
                    'status' => 'active',
                ]);

                Userprofile::create([
                    'user_id' => $user->id,
                    'school_id' => $schoolId,
                    'usergroup_id' => 6,
                    'firstname' => $firstName,
                    'lastname' => $lastName,
                    'gender' => strtolower($gender),
                    'status' => 'active',
                ]);

                DB::table('student_academics')->insert([
                    'user_id' => $user->id,
                    'standardLink_id' => $stdLinkId,
                    'school_id' => $schoolId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();
                if ($enrolled < 5 || $enrolled % 20 === 0) {
                    $this->line("  Enrolled: {$fullName} → {$className}");
                }
                $enrolled++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("  Row {$i} ERROR: {$fullName} — " . $e->getMessage());
                $errors++;
            }
        }

        $this->info("Result: {$enrolled} enrolled, {$skipped} already exist, {$errors} errors");
    }

    private function findSheetIndex(array $sheets, string $target): ?int
    {
        if (isset($sheets[$target])) return $target;
        $targetNorm = strtolower($target);
        foreach ($sheets as $idx => $rows) {
            if ($idx === 0 || count($rows) < 2) continue;
            $h = array_map(fn($h) => strtolower(trim((string) $h)), $rows[0] ?? []);
            $ci = array_search('class', $h);
            if ($ci === false) $ci = 3;
            $classVal = strtolower(trim((string) ($rows[1][$ci] ?? '')));
            $streamVal = strtolower(trim((string) ($rows[1][$ci + 1] ?? '')));
            if (str_replace(['.', ' '], '', $classVal . $streamVal) === $targetNorm) return $idx;
        }
        return null;
    }

    private function loadStdLinks(int $schoolId): array
    {
        $links = StandardLink::where('school_id', $schoolId)
            ->with(['section', 'standard'])
            ->get();

        $map = [];
        foreach ($links as $sl) {
            $key = strtoupper(str_replace(' ', '', $sl->section->name)) . '|' . $sl->standard->name;
            $map[$key] = $sl->id;
        }
        return $map;
    }
}
