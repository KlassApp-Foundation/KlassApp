<?php

namespace App\Console\Commands;

use App\Reference\CountryCodes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only report of reference and tenant data problems.
 * It does not insert, update, or delete. Pass --fail to exit 1
 * when any problem is found.
 */
class DatabaseHealthCommand extends Command
{
    protected $signature = 'db:health {--fail : Exit with status 1 when a problem is found}';

    protected $description = 'Report country-code, school, student-id, duplicate, and orphan problems without writing';

    public function handle(): int
    {
        $problems = $this->readOnly(fn (): array => $this->collect());

        $this->line('Database health');
        $this->line('Connection: '.DB::connection()->getDriverName());
        $this->newLine();

        foreach ($problems as $section => $lines) {
            $this->line($section);
            if ($lines === []) {
                $this->line('  ok');
            }
            foreach ($lines as $line) {
                $this->line('  - '.$line);
            }
            $this->newLine();
        }

        $total = array_sum(array_map('count', $problems));
        if ($total === 0) {
            $this->info('Result: PASS');

            return 0;
        }

        $this->error("Result: PROBLEMS {$total}");

        return $this->option('fail') ? 1 : 0;
    }

    /**
     * @return array<string, list<string>>
     */
    private function collect(): array
    {
        return [
            'Country codes (PR #1249)' => $this->countryCodes(),
            'Missing school_id' => $this->missingSchoolIds(),
            'Students without a KlassApp ID' => $this->studentsWithoutKlassappId(),
            'Duplicate emails or phone numbers' => $this->duplicates(),
            'Orphaned references' => $this->orphans(),
        ];
    }

    /**
     * Rows whose name is in the #1249 correction list and whose
     * short name, ISO code, or dialling prefix is not the corrected triple.
     * Those are the rows that migration would change. A name that matches
     * more than one live row is reported too: that migration skips it.
     *
     * @return list<string>
     */
    private function countryCodes(): array
    {
        if (! Schema::hasTable('countries')) {
            return ['countries table is missing'];
        }

        $lines = [];
        foreach (CountryCodes::corrected() as $name => $spec) {
            $query = DB::table('countries')->whereRaw('LOWER(name) = ?', [strtolower($name)]);
            if (Schema::hasColumn('countries', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            $rows = $query->orderBy('id')->get();

            if ($rows->count() === 0) {
                continue;
            }
            if ($rows->count() > 1) {
                $lines[] = "{$name} matches {$rows->count()} countries rows; the correction skips that name";

                continue;
            }

            $row = $rows->first();
            $current = CountryCodes::triple($row);
            $corrected = CountryCodes::specTriple($spec);
            if ($current === $corrected) {
                continue;
            }

            $previous = CountryCodes::previousSeed()[$name] ?? null;
            $stillSeed = $previous !== null && $current === CountryCodes::specTriple($previous);
            $note = $stillSeed ? ' still holds the original scrambled seed' : '';
            $lines[] = "countries id {$row->id} {$name} is ".implode('/', $current)
                .', corrected codes are '.implode('/', $corrected).$note;
        }

        return $lines;
    }

    /**
     * A school_id column the schema marks NOT NULL is required.
     * NULL or 0 is reported. Users who are not site admins
     * (usergroup_id 1) require a school_id even though that column is nullable.
     *
     * @return list<string>
     */
    private function missingSchoolIds(): array
    {
        $lines = [];
        foreach ($this->tables() as $table) {
            $column = $this->column($table, 'school_id');
            if ($column === null) {
                continue;
            }

            if (! $column['nullable']) {
                $count = DB::table($table)->where(function ($query): void {
                    $query->whereNull('school_id')->orWhere('school_id', 0);
                })->count();
                if ($count > 0) {
                    $lines[] = "{$table}: {$count} rows have no school_id";
                }

                continue;
            }

            if ($table === 'users' && Schema::hasColumn('users', 'usergroup_id')) {
                $count = DB::table('users')
                    ->where(function ($query): void {
                        $query->whereNull('usergroup_id')->orWhere('usergroup_id', '!=', 1);
                    })
                    ->where(function ($query): void {
                        $query->whereNull('school_id')->orWhere('school_id', 0);
                    })
                    ->count();
                if ($count > 0) {
                    $lines[] = "users: {$count} non-site-admin rows have no school_id";
                }
            }
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function studentsWithoutKlassappId(): array
    {
        $lines = [];
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'registration_number') && Schema::hasColumn('users', 'usergroup_id')) {
            $count = DB::table('users')
                ->where('usergroup_id', 6)
                ->where(function ($query): void {
                    $query->whereNull('registration_number')->orWhereRaw("TRIM(registration_number) = ''");
                })
                ->count();
            if ($count > 0) {
                $lines[] = "users: {$count} students have no registration_number";
            }
        }

        if (Schema::hasTable('student_academics') && Schema::hasColumn('student_academics', 'klassapp_student_id')) {
            $count = DB::table('student_academics')
                ->where(function ($query): void {
                    $query->whereNull('klassapp_student_id')->orWhereRaw("TRIM(klassapp_student_id) = ''");
                })
                ->count();
            if ($count > 0) {
                $lines[] = "student_academics: {$count} rows have no klassapp_student_id";
            }
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function duplicates(): array
    {
        $lines = [];
        foreach ([
            ['users', 'email'],
            ['users', 'mobile_no'],
            ['schools', 'email'],
            ['schools', 'phone'],
        ] as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            $groups = $this->duplicateGroups($table, $column);
            foreach ($groups as $value => $count) {
                $lines[] = "{$table}.{$column} {$value} is used by {$count} rows";
            }
        }

        return $lines;
    }

    /**
     * @return array<string, int>
     */
    private function duplicateGroups(string $table, string $column): array
    {
        $expression = "lower(trim({$column}))";
        $rows = DB::table($table)
            ->selectRaw("{$expression} as value_key, count(*) as aggregate")
            ->whereNotNull($column)
            ->whereRaw("trim({$column}) != ''")
            ->groupByRaw($expression)
            ->having('aggregate', '>', 1)
            ->get();

        $groups = [];
        foreach ($rows as $row) {
            $groups[(string) $row->value_key] = (int) $row->aggregate;
        }

        return $groups;
    }

    /**
     * @return list<string>
     */
    private function orphans(): array
    {
        $lines = [];

        if (Schema::hasTable('cities') && ! Schema::hasColumn('cities', 'state_id')) {
            $lines[] = 'cities.state_id is missing, so cities are not tied to a state';
        }

        if (Schema::hasTable('cities') && Schema::hasColumn('cities', 'state_id') && Schema::hasTable('states')) {
            $mismatch = DB::table('cities')
                ->join('states', 'states.id', '=', 'cities.state_id')
                ->whereColumn('states.country_id', '!=', 'cities.country_id')
                ->count();
            if ($mismatch > 0) {
                $lines[] = "cities: {$mismatch} rows point at a state from another country";
            }
        }

        foreach ($this->tables() as $table) {
            try {
                $foreignKeys = Schema::getForeignKeys($table);
            } catch (\Throwable) {
                continue;
            }

            foreach ($foreignKeys as $foreignKey) {
                $columns = $foreignKey['columns'];
                $parents = $foreignKey['foreign_columns'];
                $parent = $foreignKey['foreign_table'];
                if (count($columns) !== 1 || count($parents) !== 1 || ! Schema::hasTable($parent)) {
                    continue;
                }
                if (! $this->safeIdentifier($columns[0]) || ! $this->safeIdentifier($parents[0]) || ! $this->safeIdentifier($parent) || ! $this->safeIdentifier($table)) {
                    continue;
                }

                $childColumn = $columns[0];
                $parentColumn = $parents[0];
                $count = DB::table($table.' as child')
                    ->leftJoin($parent.' as parent', 'parent.'.$parentColumn, '=', 'child.'.$childColumn)
                    ->whereNotNull('child.'.$childColumn)
                    ->whereNull('parent.'.$parentColumn)
                    ->count();
                if ($count > 0) {
                    $lines[] = "{$table}.{$childColumn}: {$count} rows reference a missing {$parent}";
                }
            }
        }

        return $lines;
    }

    /**
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    private function readOnly(callable $callback): mixed
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA query_only = ON');
            try {
                return $callback();
            } finally {
                DB::statement('PRAGMA query_only = OFF');
            }
        }

        if (in_array($driver, ['mysql', 'mariadb'], true) && DB::transactionLevel() === 0) {
            DB::statement('SET TRANSACTION READ ONLY');
            DB::beginTransaction();
            try {
                return $callback();
            } finally {
                DB::rollBack();
                DB::statement('SET TRANSACTION READ WRITE');
            }
        }

        return $callback();
    }

    /**
     * @return list<string>
     */
    private function tables(): array
    {
        $names = [];
        foreach (Schema::getTables() as $table) {
            $name = $table['name'];
            if (! $this->safeIdentifier($name) || str_starts_with($name, 'sqlite_')) {
                continue;
            }
            $names[] = $name;
        }

        return $names;
    }

    /**
     * @return array{name: string, nullable: bool}|null
     */
    private function column(string $table, string $name): ?array
    {
        foreach (Schema::getColumns($table) as $column) {
            if ($column['name'] === $name) {
                return [
                    'name' => $column['name'],
                    'nullable' => (bool) $column['nullable'],
                ];
            }
        }

        return null;
    }

    private function safeIdentifier(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]+$/', $name);
    }
}
