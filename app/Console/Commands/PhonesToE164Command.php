<?php

namespace App\Console\Commands;

use App\Services\Phones\PhoneE164Converter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * T-077. Converts stored phone numbers to E.164 ("0772 123456" becomes "+256772123456") using the
 * school's country as the default. Dry run is the default; --apply writes.
 *
 * Never invents a number: a value that does not parse is counted, sampled and logged, and left as it is.
 */
class PhonesToE164Command extends Command
{
    protected $signature = 'phones:to-e164
        {--apply : Write the converted numbers (default is a dry run that writes nothing)}
        {--dry-run : Report only. This is the default; it cannot be combined with --apply}
        {--school= : Only this school id}
        {--table= : Only this table}
        {--chunk=500 : Rows per write batch with --apply}';

    protected $description = 'Convert stored phone numbers to E.164 using each school\'s country (dry run by default)';

    private const SAMPLE_LIMIT = 20;

    /**
     * table => [school column (null for the schools table itself), phone columns]
     *
     * @var array<string, array{school: ?string, columns: list<string>}>
     */
    private const TARGETS = [
        'users' => ['school' => 'school_id', 'columns' => ['mobile_no']],
        'userprofiles' => ['school' => 'school_id', 'columns' => ['alternate_no']],
        'schools' => ['school' => null, 'columns' => ['phone']],
        'admissions' => ['school' => 'school_id', 'columns' => ['father_mobile_no', 'mother_mobile_no', 'father_alt_phone', 'mother_alt_phone']],
        'telephone_directory' => ['school' => 'school_id', 'columns' => ['phone_number']],
        'student_health_profiles' => ['school' => 'school_id', 'columns' => ['emergency_contact_phone']],
    ];

    /** @var array<int, ?string> */
    private array $countries = [];

    /** @var list<array{table: string, id: int, column: string, before: string, after: string}> */
    private array $samples = [];

    public function handle(PhoneE164Converter $converter): int
    {
        $apply = (bool) $this->option('apply');
        if ($apply && $this->option('dry-run')) {
            $this->error('Use either --apply or --dry-run, not both. Dry run is the default.');

            return self::INVALID;
        }

        $only = $this->option('table') ? (string) $this->option('table') : null;
        if ($only !== null && ! isset(self::TARGETS[$only])) {
            $this->error('Unknown table. Choose one of: '.implode(', ', array_keys(self::TARGETS)));

            return self::INVALID;
        }

        $schoolId = $this->option('school') !== null && $this->option('school') !== '' ? (int) $this->option('school') : null;
        $chunk = max(1, (int) $this->option('chunk'));
        $this->loadCountries();

        $this->line($apply ? 'APPLY: writing converted numbers.' : 'DRY RUN: nothing is written. Use --apply to write.');

        $rows = [];
        $totals = ['scanned' => 0, 'empty' => 0, 'already' => 0, 'changed' => 0, 'unparseable' => 0];
        foreach (self::TARGETS as $table => $spec) {
            if ($only !== null && $only !== $table) {
                continue;
            }
            $columns = $this->presentColumns($table, $spec['columns']);
            if ($columns === []) {
                continue;
            }
            $counts = $this->runTable($converter, $table, $spec['school'], $columns, $apply, $chunk, $schoolId);
            $rows[] = [$table, $counts['scanned'], $counts['empty'], $counts['already'], $counts['changed'], $counts['unparseable']];
            foreach ($totals as $key => $value) {
                $totals[$key] += $counts[$key];
            }
        }

        $this->table(['Table', 'Values scanned', 'Empty', 'Already E.164', $apply ? 'Changed' : 'Would change', 'Could not convert'], $rows);
        $this->line(sprintf(
            'Total: %d scanned, %d already E.164, %d %s, %d could not convert (left as they are).',
            $totals['scanned'] - $totals['empty'],
            $totals['already'],
            $totals['changed'],
            $apply ? 'changed' : 'would change',
            $totals['unparseable'],
        ));

        if ($this->samples !== []) {
            $this->newLine();
            $this->line('Samples (before => after), up to '.self::SAMPLE_LIMIT.':');
            foreach ($this->samples as $sample) {
                $this->line(sprintf('  %s #%d %s: %s => %s', $sample['table'], $sample['id'], $sample['column'], $sample['before'], $sample['after']));
            }
        }

        if ($totals['unparseable'] > 0) {
            $this->warn('Numbers that could not be converted were not changed'.($apply ? ' and are in the log ("phones:to-e164 could not convert").' : '.'));
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $columns
     * @return array{scanned: int, empty: int, already: int, changed: int, unparseable: int}
     */
    private function runTable(PhoneE164Converter $converter, string $table, ?string $schoolColumn, array $columns, bool $apply, int $chunk, ?int $onlySchool): array
    {
        $counts = ['scanned' => 0, 'empty' => 0, 'already' => 0, 'changed' => 0, 'unparseable' => 0];
        $select = array_merge(['id'], $schoolColumn !== null ? [$schoolColumn] : [], $columns);
        $query = DB::table($table)->select($select);
        if ($onlySchool !== null) {
            $query->where($schoolColumn ?? 'id', $onlySchool);
        }

        $query->orderBy('id')->chunkById($chunk, function ($batch) use ($converter, $table, $schoolColumn, $columns, $apply, &$counts): void {
            $updates = [];
            foreach ($batch as $row) {
                $schoolId = (int) ($schoolColumn === null ? $row->id : ($row->{$schoolColumn} ?? 0));
                $country = $this->countries[$schoolId] ?? null;
                foreach ($columns as $column) {
                    $result = $converter->convert($row->{$column} !== null ? (string) $row->{$column} : null, $country);
                    $counts['scanned']++;
                    $counts[$result['status']]++;

                    if ($result['status'] === PhoneE164Converter::CHANGED) {
                        $updates[(int) $row->id][$column] = $result['value'];
                        $this->sample($table, (int) $row->id, $column, (string) $row->{$column}, (string) $result['value']);
                    } elseif ($result['status'] === PhoneE164Converter::UNPARSEABLE) {
                        $this->sample($table, (int) $row->id, $column, (string) $row->{$column}, '(not changed: '.$result['reason'].')', true);
                        if ($apply) {
                            Log::warning('phones:to-e164 could not convert', [
                                'table' => $table,
                                'id' => (int) $row->id,
                                'column' => $column,
                                'school_id' => $schoolId ?: null,
                                'reason' => $result['reason'],
                                'ends_with' => substr(preg_replace('/\D/', '', (string) $row->{$column}) ?? '', -3),
                            ]);
                        }
                    }
                }
            }

            if ($apply && $updates !== []) {
                DB::transaction(function () use ($table, $updates): void {
                    foreach ($updates as $id => $values) {
                        DB::table($table)->where('id', $id)->update($values);
                    }
                });
            }
        });

        return $counts;
    }

    private function sample(string $table, int $id, string $column, string $before, string $after, bool $unconverted = false): void
    {
        // Show the changes first; a few unconverted ones fill the rest so the operator sees both.
        $limit = self::SAMPLE_LIMIT;
        if (count($this->samples) >= $limit) {
            return;
        }
        if ($unconverted && count($this->samples) >= $limit - 5) {
            return;
        }
        $this->samples[] = ['table' => $table, 'id' => $id, 'column' => $column, 'before' => $before, 'after' => $after];
    }

    /**
     * @param  list<string>  $columns
     * @return list<string>
     */
    private function presentColumns(string $table, array $columns): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($table, $column)));
    }

    /** One country name per school: the registration country, else the linked country row. */
    private function loadCountries(): void
    {
        $this->countries = [];
        foreach (DB::table('schools')->select('id', 'registration_country', 'country_id')->get() as $school) {
            $name = trim((string) ($school->registration_country ?? ''));
            if ($name === '' && $school->country_id) {
                $name = trim((string) DB::table('countries')->where('id', $school->country_id)->value('name'));
            }
            $this->countries[(int) $school->id] = $name !== '' ? $name : null;
        }
    }
}
