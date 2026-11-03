<?php

namespace App\Reference;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Counts and a checksum of reference rows.
 *
 * Timestamps and primary keys are left out of the checksum so a
 * reseed that does not change reference values compares equal.
 */
final class ReferenceSnapshot
{
    /**
     * @return array{counts: array<string, int>, checksum: string, problems: list<string>}
     */
    public static function capture(): array
    {
        $counts = self::counts();
        $problems = self::problems($counts);

        return [
            'counts' => $counts,
            'checksum' => hash('sha256', json_encode(self::canonicalRows(), JSON_THROW_ON_ERROR)),
            'problems' => $problems,
        ];
    }

    /**
     * @return array<string, int>
     */
    public static function counts(): array
    {
        $counts = [];
        foreach (self::plainTables() as $table) {
            $counts[$table] = Schema::hasTable($table) ? (int) DB::table($table)->count() : -1;
        }
        $counts['schools'] = Schema::hasTable('schools') ? (int) DB::table('schools')->count() : -1;

        return $counts;
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<string>
     */
    public static function problems(array $counts): array
    {
        $problems = [];

        $expectedCountries = count(CountryCodes::corrected());
        if (($counts['countries'] ?? -1) !== $expectedCountries) {
            $problems[] = "countries count is {$counts['countries']}, expected {$expectedCountries}";
        }

        if (Schema::hasTable('countries')) {
            foreach (CountryCodes::corrected() as $name => $spec) {
                $row = DB::table('countries')->where('name', $name)->first();
                if ($row === null) {
                    $problems[] = "missing country {$name}";

                    continue;
                }
                if (CountryCodes::triple($row) !== CountryCodes::specTriple($spec)) {
                    $problems[] = "{$name} codes are ".implode('/', CountryCodes::triple($row))
                        .', expected '.implode('/', CountryCodes::specTriple($spec));
                }
            }
        }

        if (Schema::hasTable('states') && Schema::hasTable('countries')) {
            $orphanStates = DB::table('states')
                ->leftJoin('countries', 'countries.id', '=', 'states.country_id')
                ->whereNull('countries.id')
                ->count();
            if ($orphanStates > 0) {
                $problems[] = "{$orphanStates} states have no country";
            }
            if (($counts['states'] ?? -1) !== Geography::expectedStateCount()) {
                $problems[] = 'states count is '.($counts['states'] ?? -1).', expected '.Geography::expectedStateCount();
            }
        }

        if (Schema::hasTable('cities') && Schema::hasColumn('cities', 'state_id')) {
            $orphanCities = DB::table('cities')
                ->leftJoin('states', 'states.id', '=', 'cities.state_id')
                ->where(function ($query): void {
                    $query->whereNull('cities.state_id')->orWhereNull('states.id');
                })
                ->count();
            if ($orphanCities > 0) {
                $problems[] = "{$orphanCities} cities have no state";
            }

            $mismatched = DB::table('cities')
                ->join('states', 'states.id', '=', 'cities.state_id')
                ->whereColumn('states.country_id', '!=', 'cities.country_id')
                ->count();
            if ($mismatched > 0) {
                $problems[] = "{$mismatched} cities belong to a state from another country";
            }

            if (($counts['cities'] ?? -1) !== Geography::expectedCityCount()) {
                $problems[] = 'cities count is '.($counts['cities'] ?? -1).', expected '.Geography::expectedCityCount();
            }
        } elseif (Schema::hasTable('cities')) {
            $problems[] = 'cities.state_id is missing, so a city cannot belong to a state';
        }

        if (Schema::hasTable('reference_curricula')) {
            $codes = DB::table('reference_curricula')->orderBy('code')->pluck('code')->all();
            $expected = array_column(CurriculumCatalog::all(), 'code');
            sort($expected);
            if ($codes !== $expected) {
                $problems[] = 'reference_curricula codes are '.json_encode($codes);
            }
        } else {
            $problems[] = 'reference_curricula table is missing';
        }

        $expectedBands = 0;
        foreach (config('grading_uganda') as $bands) {
            if (is_array($bands)) {
                $expectedBands += count($bands);
            }
        }
        if (($counts['reference_grading_bands'] ?? -1) !== $expectedBands) {
            $problems[] = 'reference_grading_bands count is '.($counts['reference_grading_bands'] ?? -1).", expected {$expectedBands}";
        }

        if (($counts['exam_types'] ?? -1) !== count(ExamTypeCatalog::all())) {
            $problems[] = 'exam_types count is '.($counts['exam_types'] ?? -1).', expected '.count(ExamTypeCatalog::all());
        }

        if (($counts['schools'] ?? -1) !== 0) {
            $problems[] = 'schools count is '.($counts['schools'] ?? -1).'; reference seed must not create schools';
        }

        return $problems;
    }

    /**
     * @return list<string>
     */
    private static function plainTables(): array
    {
        return [
            'countries',
            'states',
            'cities',
            'usergroups',
            'plans',
            'settings',
            'roles',
            'reference_curricula',
            'reference_grading_bands',
            'absent_reasons',
            'exam_types',
            'remarks',
            'keywords',
            'qualifications',
            'mailtemplates',
            'sms_templates',
            'pay_categories',
            'payroll_items',
            'transaction_types',
        ];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private static function canonicalRows(): array
    {
        $rows = [];

        $rows['countries'] = self::select('countries', ['name', 'short_name', 'iso_code', 'tel_prefix', 'status'], 'name');
        $rows['states'] = Schema::hasTable('states')
            ? DB::table('states')
                ->leftJoin('countries', 'countries.id', '=', 'states.country_id')
                ->orderBy('countries.name')
                ->orderBy('states.name')
                ->get(['countries.name as country', 'states.name as name', 'states.status as status'])
                ->map(fn ($row) => (array) $row)
                ->all()
            : [];
        $rows['cities'] = Schema::hasTable('cities')
            ? DB::table('cities')
                ->leftJoin('states', 'states.id', '=', 'cities.state_id')
                ->leftJoin('countries', 'countries.id', '=', 'cities.country_id')
                ->orderBy('countries.name')
                ->orderBy('states.name')
                ->orderBy('cities.name')
                ->get([
                    'countries.name as country',
                    'states.name as state',
                    'cities.name as name',
                    'cities.status as status',
                ])
                ->map(fn ($row) => (array) $row)
                ->all()
            : [];

        foreach ([
            'usergroups' => ['id', 'name'],
            'plans' => ['name', 'display_name', 'cycle', 'amount', 'is_active', 'is_custom_pricing', 'order'],
            'settings' => ['key', 'name', 'value', 'field', 'active'],
            'roles' => ['name', 'display_name', 'description'],
            'reference_curricula' => ['code', 'name'],
            'reference_grading_bands' => ['level', 'position', 'grade', 'points', 'min_score', 'max_score', 'remark'],
            'absent_reasons' => ['title', 'status'],
            'exam_types' => ['code', 'name', 'contributes_to_report_total'],
            'remarks' => ['remark'],
            'keywords' => ['id', 'name'],
            'qualifications' => ['id', 'display_name', 'type', 'status'],
            'mailtemplates' => ['id', 'name', 'subject', 'mail_content', 'status'],
            'sms_templates' => ['id', 'name', 'template', 'content', 'status'],
            'pay_categories' => ['name'],
            'payroll_items' => ['key', 'name', 'type'],
            'transaction_types' => ['name'],
        ] as $table => $columns) {
            $order = $columns[0];
            $rows[$table] = self::select($table, $columns, $order);
        }

        return $rows;
    }

    /**
     * @param  list<string>  $columns
     * @return list<array<string, mixed>>
     */
    private static function select(string $table, array $columns, string $order): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return DB::table($table)
            ->orderBy($order)
            ->get($columns)
            ->map(fn ($row) => (array) $row)
            ->all();
    }
}
