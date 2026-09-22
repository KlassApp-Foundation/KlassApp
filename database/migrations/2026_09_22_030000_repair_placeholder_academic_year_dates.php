<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The demo-showcase seed originally inserted academic_years without start_date and end_date.
 * Both columns are NOT NULL, so on non-strict MySQL the insert succeeded with '0000-00-00'
 * placeholders instead of failing. The seed itself was fixed, but rows created before that
 * fix still carry the invalid dates, which would present as a nonsense academic year.
 *
 * Repairs ONLY those rows, and ONLY for is_demo schools, to the house convention used by
 * every other academic year in this database: 2 February to 4 December of the named year.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('academic_years')
            ->join('schools', 'schools.id', '=', 'academic_years.school_id')
            ->where('schools.is_demo', true)
            ->where(function ($q) {
                $q->whereNull('academic_years.start_date')
                    ->orWhere('academic_years.start_date', '<', '1971-01-01');
            })
            ->select('academic_years.id', 'academic_years.name')
            ->get();

        foreach ($rows as $row) {
            $year = trim((string) $row->name);

            // Guarded: never invent dates for a year label that is not a plain 4 digit year.
            if (! preg_match('/^\d{4}$/', $year)) {
                continue;
            }

            DB::table('academic_years')->where('id', $row->id)->update([
                'start_date' => $year.'-02-02 00:00:00',
                'end_date' => $year.'-12-04 00:00:00',
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally empty. This repairs placeholder data; restoring '0000-00-00' would
        // reintroduce invalid rows, and nothing ever depended on them.
    }
};
