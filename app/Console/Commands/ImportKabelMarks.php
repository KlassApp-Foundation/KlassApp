<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportKabelMarks extends Command
{
    protected $signature = 'import:kabel-marks
        {--class= : Class name (e.g. P.1, P.7)}
        {--stream= : Stream (e.g. East, West)}
        {--exam= : Exam period only (e.g. eot, june, july)}
        {--dry-run : Show what would be written without committing}';

    protected $description = 'Import marks from Kabel xlsx source files into the database';

    private array $subjMap = [];
    private array $examMap = [];
    private int $sectionId = 0;
    private int $schoolId = 104;

    public function handle(): int
    {
        $class = $this->option('class');
        $stream = $this->option('stream');

        if (!$class) {
            $this->error('Please specify --class (e.g. P.1, P.7)');
            return self::FAILURE;
        }

        $this->info("Importing {$class} marks" . ($stream ? " ({$stream})" : ''));

        // Resolve config
        if (!$this->resolveConfig($class, $stream)) {
            return self::FAILURE;
        }

        // Filter by exam period if specified
        $examFilter = $this->option('exam');
        if ($examFilter) {
            $examFilter = strtolower($examFilter);
            // Accept common aliases
            $examAliases = ['eot' => 'end_of_term', 'mid' => 'june', 'july' => 'july', 'june' => 'june'];
            $examFilter = $examAliases[$examFilter] ?? $examFilter;
            if (!isset($this->examMap[$examFilter])) {
                $this->error("Unknown exam period '{$examFilter}'. Available: " . implode(', ', array_keys($this->examMap)));
                return self::FAILURE;
            }
            $this->examMap = [$examFilter => $this->examMap[$examFilter]];
            $this->info("Filtering to exam period: {$examFilter}");
        }

        // Build student map
        $nameMap = $this->buildNameMap();
        $this->info("Loaded " . count($nameMap) . " student name variants");

        // Read source files
        $files = $this->getSourceFiles($class, $stream);
        // Filter files to only include the specified exam period
        if ($examFilter) {
            $files = array_filter($files, fn($period) => $period === $examFilter, ARRAY_FILTER_USE_KEY);
        }
        if (empty($files)) {
            $this->error('No source files found');
            return self::FAILURE;
        }

        // Backup existing marks
        $examIds = array_values($this->examMap);
        $backup = DB::table('marks')
            ->whereIn('exam_id', $examIds)
            ->where('section_id', $this->sectionId)
            ->get()
            ->toArray();

        $backupPath = storage_path('app/backup_' . strtolower(str_replace('.', '', $class)) . '_' . date('Ymd_His') . '.json');
        file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT));
        $this->info("Backed up " . count($backup) . " rows to {$backupPath}");

        // Process files
        [$writes, $stats, $unmatched, $flagged] = $this->processFiles($files, $nameMap);

        // Display stats
        $this->newLine();
        $this->info('=== MATCH STATS ===');
        foreach ($stats as $stat) {
            $this->line("  {$stat}");
        }
        $this->info("Total writes: " . count($writes) . " cells");

        if (!empty($flagged)) {
            $this->newLine();
            $this->warn('=== FLAGGED (' . count($flagged) . ') ===');
            foreach ($flagged as $f) {
                $this->warn("  ⚠ {$f}");
            }
        }

        if (!empty($unmatched)) {
            $this->newLine();
            $this->warn('=== UNMATCHED (' . count(array_unique($unmatched)) . ') ===');
            foreach (array_unique($unmatched) as $u) {
                $this->warn("  ? {$u}");
            }
        }

        if ($this->option('dry-run')) {
            $this->info("\nDRY RUN — no changes made");
            return self::SUCCESS;
        }

        // Execute transaction
        $this->newLine();
        $this->info('--- EXECUTING TRANSACTION ---');

        DB::beginTransaction();
        try {
            $deleted = DB::table('marks')
                ->whereIn('exam_id', $examIds)
                ->where('section_id', $this->sectionId)
                ->delete();
            $this->line("Deleted {$deleted} existing marks");

            $inserted = 0;
            foreach (array_chunk($writes, 200) as $chunk) {
                DB::table('marks')->insert($chunk);
                $inserted += count($chunk);
            }
            $this->line("Inserted {$inserted} marks");

            DB::commit();
            $this->info('COMMIT OK');

            // Verify
            $this->newLine();
            $this->info('--- POST-WRITE COUNTS ---');
            foreach ($this->examMap as $period => $examId) {
                $cnt = DB::table('marks')
                    ->where('exam_id', $examId)
                    ->where('section_id', $this->sectionId)
                    ->count();
                $this->line("{$period}: {$cnt} marks");
            }

            $total = DB::table('marks')
                ->whereIn('exam_id', $examIds)
                ->where('section_id', $this->sectionId)
                ->count();
            $this->info("TOTAL: {$total} marks");

            return self::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("ROLLBACK: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function resolveConfig(string $class, ?string $stream): bool
    {
        $classLower = strtolower($class);

        if ($classLower === 'p.1' || $classLower === 'p1') {
            $this->subjMap = [
                'ENG' => 236,
                'MTC' => 237,
                'RE' => 240,
                'LIT I' => 241,
                'LIT II' => 242,
                'RR' => 311,
            ];
            $this->examMap = ['june' => 24, 'july' => 25, 'end_of_term' => 26];
            $this->sectionId = 45;

            if ($stream && strtolower($stream) === 'east') {
                $this->schoolId = 104;
                // Link 78 = P.1 East
            } elseif ($stream && strtolower($stream) === 'west') {
                $this->schoolId = 104;
                // Link 82 = P.1 West
            }
        } elseif ($classLower === 'p.7' || $classLower === 'p7') {
            $this->subjMap = [
                'ENG' => 284,
                'MTC' => 285,
                'SCI' => 287, // Note: 286=SST, 287=SCI in DB
                'SST' => 286,
            ];
            $this->examMap = ['june' => 42, 'july' => 43, 'end_of_term' => 44];
            $this->sectionId = $stream && strtolower($stream) === 'west' ? 45 : 51;
        } elseif ($classLower === 'p.4' || $classLower === 'p4') {
            $this->subjMap = [
                'ENG' => 260,
                'MTC' => 261,
                'SCI' => 310,
                'SST' => 262,
            ];
            $this->examMap = ['june' => 30, 'july' => 31, 'end_of_term' => 32];
            $this->sectionId = 48;
        } else {
            $this->error("Unsupported class: {$class}");
            return false;
        }

        return true;
    }

    private function buildNameMap(): array
    {
        $users = DB::table('users')
            ->where('school_id', $this->schoolId)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->get(['id', 'name']);

        $map = [];
        foreach ($users as $u) {
            $upper = strtoupper(trim($u->name));
            $map[$upper] = $u->id;

            $words = preg_split('/\s+/', $upper);
            if (count($words) >= 2) {
                $map[implode(' ', array_reverse($words))] = $u->id;
            }
            if (count($words) >= 3) {
                $map[$words[0] . ' ' . end($words)] = $u->id;
                $map[end($words) . ' ' . $words[0]] = $u->id;
                $map[$words[0] . ' ' . $words[1]] = $u->id;
                $map[end($words) . ' ' . $words[0] . ' ' . $words[1]] = $u->id;
            }
        }

        return $map;
    }

    private function getSourceFiles(string $class, ?string $stream): array
    {
        $classLower = strtolower(str_replace('.', '', $class));

        if ($classLower === 'p1') {
            if ($stream && strtolower($stream) === 'east') {
                return [
                    'june' => 'p1East_june.xlsx',
                    'july' => 'p1East_july.xlsx',
                    'end_of_term' => 'p1East_end_of_term.xlsx',
                ];
            } elseif ($stream && strtolower($stream) === 'west') {
                return [
                    'june' => 'p1West_june.xlsx',
                    'july' => 'p1West_july.xlsx',
                    'end_of_term' => 'p1West_end_of_term.xlsx',
                ];
            }
            // Both streams
            return array_merge(
                $this->getSourceFiles($class, 'East'),
                $this->getSourceFiles($class, 'West')
            );
        }

        if ($classLower === 'p7') {
            $prefix = $stream && strtolower($stream) === 'west' ? 'p7B' : 'p7A';
            return [
                'june' => "{$prefix}_june.xlsx",
                'july' => "{$prefix}_july.xlsx",
                'end_of_term' => "{$prefix}_end_of_term.xlsx",
            ];
        }

        if ($classLower === 'p4') {
            // P.4 East: June/July in single file, EOT in separate file
            return [
                'june' => 'P4 East June&July.xlsx|Sheet1',
                'july' => 'P4 East June&July.xlsx|Sheet2|header_row=2',
                'end_of_term' => 'AGABA   END  OF TERM.xlsx|Sheet2|header_row=3|footer_start=58',
            ];
        }

        return [];
    }

    private function processFiles(array $files, array $nameMap): array
    {
        $writes = [];
        $stats = [];
        $unmatched = [];
        $flagged = [];
        $resolutions = $this->getNameResolutions();

        foreach ($files as $period => $fileConfig) {
            $examId = $this->examMap[$period] ?? null;
            if (!$examId) continue;

            // Parse file config: "filename.xlsx|Sheet2|header_row=2|footer_start=58"
            $parts = explode('|', $fileConfig);
            $filename = $parts[0];
            $sheetName = $parts[1] ?? null;
            $headerRow = 0;
            $footerStart = null;
            for ($i = 2; $i < count($parts); $i++) {
                if (str_starts_with($parts[$i], 'header_row=')) {
                    $headerRow = (int) substr($parts[$i], 11);
                }
                if (str_starts_with($parts[$i], 'footer_start=')) {
                    $footerStart = (int) substr($parts[$i], 13);
                }
            }

            $path = storage_path("app/{$filename}");
            if (!file_exists($path)) {
                $this->warn("MISSING: {$filename}");
                continue;
            }

            $spreadsheet = IOFactory::load($path);
            $ws = $sheetName ? $spreadsheet->getSheetByName($sheetName) : $spreadsheet->getActiveSheet();
            if (!$ws) {
                $this->warn("Sheet '{$sheetName}' not found in {$filename}");
                continue;
            }
            $rows = $ws->toArray();

            // Find header row (first row with a "NAME" or "NAMES" column)
            $header = $rows[$headerRow] ?? [];
            $cols = $this->mapColumns($header);

            // Determine data start (row after header) and end (before footer)
            $dataStart = $headerRow + 1;
            $dataEnd = $footerStart !== null ? min($footerStart, count($rows)) : count($rows);

            $matched = 0;
            $totalRows = 0;

            for ($r = $dataStart; $r < $dataEnd; $r++) {
                $row = $rows[$r];
                $name = trim($row[$cols['name']] ?? '');
                if (empty($name) || strtoupper($name) === 'TOTAL' || strtoupper($name) === 'ANALYSIS') continue;
                $totalRows++;

                // Collapse internal whitespace so multi-space spellings (e.g. "OWAMANI    SAMUEL") match resolution keys.
                $upper = strtoupper(preg_replace('/\s+/', ' ', $name));

                // Apply resolution
                $lookupName = $resolutions[$upper] ?? $upper;
                $uid = $nameMap[$lookupName] ?? null;

                if (!$uid) {
                    // Try reversed
                    $words = preg_split('/\s+/', $lookupName);
                    $reversed = implode(' ', array_reverse($words));
                    $uid = $nameMap[$reversed] ?? null;
                }

                if (!$uid) {
                    // Fuzzy match
                    foreach ($nameMap as $dbName => $dbUid) {
                        $dbWords = preg_split('/\s+/', $dbName);
                        $srcWords = preg_split('/\s+/', $lookupName);
                        if (count(array_intersect($srcWords, $dbWords)) === count($srcWords)) {
                            $uid = $dbUid;
                            break;
                        }
                    }
                }

                if (!$uid) {
                    $unmatched[] = "{$name} ({$period})";
                    continue;
                }

                $matched++;

                foreach ($this->subjMap as $subj => $subjId) {
                    $colKey = strtolower(str_replace(' ', '_', $subj));
                    $col = $cols[$colKey] ?? null;
                    if ($col === null) continue;

                    $val = is_numeric($row[$col] ?? null) ? (float)$row[$col] : null;
                    if ($val === null) continue;

                    $writes[] = [
                        'student_id' => $uid,
                        'teacher_id' => 525,
                        'school_id' => $this->schoolId,
                        'subject_id' => $subjId,
                        'exam_id' => $examId,
                        'section_id' => $this->sectionId,
                        'marks' => $val,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            $stats[] = sprintf("%-12s: %d/%d matched", $period, $matched, $totalRows);
        }

        return [$writes, $stats, $unmatched, $flagged];
    }

    private function mapColumns(array $header): array
    {
        $cols = ['name' => null];
        foreach ($header as $i => $h) {
            $h = strtoupper(trim($h));
            if (preg_match('/NAME|PUPIL/i', $h)) $cols['name'] = $i;
            if ($h === 'ENG') $cols['eng'] = $i;
            if ($h === 'MTC') $cols['mtc'] = $i;
            if ($h === 'RE') $cols['re'] = $i;
            if (in_array($h, ['LIT I', 'LIT1', 'LITERACY I'])) $cols['lit_i'] = $i;
            if (in_array($h, ['LIT II', 'LIT2', 'LITERACY II'])) $cols['lit_ii'] = $i;
            if ($h === 'RR') $cols['rr'] = $i;
            if ($h === 'SCI') $cols['sci'] = $i;
            if ($h === 'SST') $cols['sst'] = $i;
        }
        return $cols;
    }

    private function getNameResolutions(): array
    {
        return [
            // P.1 East
            'ANKUNDA TASHA' => 'TASHA AKANKUNDA',
            'DUSHIME FREDRICK' => 'FREDRICK DUSHIME',
            'ALPHA ELSHADAI' => 'ALPHA ELISHADAY ARINANYE',
            'ALPHA ELISHADAI' => 'ALPHA ELISHADAY ARINANYE',
            'AKANDWANAHO RAMOSI' => 'JOVIC RAMOS AKANDWANAHO',
            'AHEISIBWE SERENA' => 'SERENA AHEEBWE',
            'ANKUNDA SHIPRAH' => 'SHIPHORA AKUNDA',
            'NALUBEGA WITNEY' => 'WHITNEY NAUBEGA',
            'MUHARABEINGANA BIBIANA' => 'BIBIANA MUHARABINGANA',
            'NANSASIRA GRANT' => 'GRANT AKANASASIRA',
            'AHABWE AGNESS' => 'AGNES AHABWE',
            'ARINDA LUCKY CALVIN' => 'LUCKY CALVIN ARINDA',
            'AKANKWASA DELVIN' => 'DELVIN AKAKWASA',
            'NAHABWE BRAYDEN' => 'BRANDON GIFT NAHABWE',
            'BABIRYE AIHA' => 'AILA BABIRYE',
            'DUSHIME FREDERICK' => 'FREDRICK DUSHIME',
            'AHIKIRIZA FAVOUR' => 'FAVOUR ATIKIRIZA',
            'AKANYIHAYO SABITI' => 'SABITI AKANDWANAHO',
            'AINEBYONA ASHTON' => 'ASHTONE AINEBYONA',
            'ORIKIRIZA CHOSEN' => 'CHOSEN DIKIRIZA',
            'MWEBAZE ASHLEY' => 'ASHLEY TUMWEBAZE',
            'SHERIMO ETHAN' => 'ETHAN OSHERIMO',
            'ANYUUKIRE MESSIAH' => 'MESSIAH ATUJUKIRE',
            'AINAMAANI JONATHAN' => 'JONATHAN AINAMANI',
            'AINAMAANI ELIJAH' => 'ELIJAH AINAMANI',
            'WOTALI SHALOM ALICE' => 'SHALOM WOTALI',
            'NAHABWE BRAYDEN GIFT' => 'BRANDON GIFT NAHABWE',
            'ABAHINE STABLE' => 'STABLE ABIINE',

            // P.1 West
            'AINAMANI SHONE' => 'SHONE AINAMAANI',
            'TURINAWE ELIORAH' => 'ELIORA TURINAWE',
            'TURINAWE ELIORA' => 'ELIORA TURINAWE',
            'AMUTUHEIRE ELIZABETH' => 'ELIZABETH AMUTUHEIRE',

            // P.4 East — spelling variants + reversed names
            'AIJUKA KEITH' => 'KEITH AJUKA',
            'KANGWAGYE VICENT' => 'VINCENT KANGWAGYE',
            'NIWAMANYA PAMELLA' => 'PAMELA NIWAMANYA',
            'NIWAMANYA PAMELLAH' => 'PAMELA NIWAMANYA',
            'NABAASSA MELISSA' => 'MELISSA NABAASA',
            'NABAAASA MELISSA' => 'MELISSA NABAASA',
            'AHURIRA OSBERT' => 'OSBERT AHURIRA',
            'AINEBYOONA BRIGHTON' => 'BRIGHTON AINEBYONA',
            'AKATUKWASA MARTIN' => 'MARTIN AKAWAKWASA',
            'NTWARI ALEXANDER' => 'ALEXANDER ANTWARI',
            'ATWINE DYLIAN' => 'DYLAN ATWINE',
            'TWESHEGYEREZE GASPARI' => 'GASPARI TWESIGYEEREZE',
            'OWANI SAMUEL' => 'SAMUEL OWAMANI',
            'OWAMANI SAMUEL' => 'SAMUEL OWAMANI',
            'OWAMAANI SAMUEL' => 'SAMUEL OWAMANI',
            'OWAMAANI FRANK' => 'FRANK OWAMANI',
            'NAJJEMBA MARY ATARAH' => 'MARY ATARAH AJUEMBA',
        ];
    }
}
