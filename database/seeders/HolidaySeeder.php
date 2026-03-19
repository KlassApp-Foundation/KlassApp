<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\AcademicYear;
use App\Models\School;
use Carbon\Carbon;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Sample Uganda-relevant holidays (2025/2026 approximate; adjust years as needed)
        // Fixed dates mostly; Eid/Good Friday approximate - better to use a library like Carbon or holiday API in prod
        $holidays = [
            [
                'title'      => 'New Year\'s Day',
                'start_date' => '2026-01-01 00:00:00',
                'end_date'   => '2026-01-01 00:00:00',
            ],
            [
                'title'      => 'NRM Liberation Day',
                'start_date' => '2026-01-26 00:00:00',
                'end_date'   => '2026-01-26 00:00:00',
            ],
            [
                'title'      => 'Archbishop Janani Luwum Day',
                'start_date' => '2026-02-16 00:00:00',
                'end_date'   => '2026-02-16 00:00:00',
            ],
            [
                'title'      => 'International Women\'s Day',
                'start_date' => '2026-03-08 00:00:00',
                'end_date'   => '2026-03-08 00:00:00',
            ],
            [
                'title'      => 'Good Friday',
                'start_date' => '2026-04-03 00:00:00', // Approx for 2026
                'end_date'   => '2026-04-03 00:00:00',
            ],
            [
                'title'      => 'Easter Monday',
                'start_date' => '2026-04-06 00:00:00',
                'end_date'   => '2026-04-06 00:00:00',
            ],
            [
                'title'      => 'Labour Day',
                'start_date' => '2026-05-01 00:00:00',
                'end_date'   => '2026-05-01 00:00:00',
            ],
            [
                'title'      => 'Martyrs\' Day',
                'start_date' => '2026-06-03 00:00:00',
                'end_date'   => '2026-06-03 00:00:00',
            ],
            [
                'title'      => 'National Heroes\' Day',
                'start_date' => '2026-06-09 00:00:00',
                'end_date'   => '2026-06-09 00:00:00',
            ],
            [
                'title'      => 'Independence Day',
                'start_date' => '2026-10-09 00:00:00',
                'end_date'   => '2026-10-09 00:00:00',
            ],
            [
                'title'      => 'Christmas Day',
                'start_date' => '2026-12-25 00:00:00',
                'end_date'   => '2026-12-25 00:00:00',
            ],
            [
                'title'      => 'Boxing Day',
                'start_date' => '2026-12-26 00:00:00',
                'end_date'   => '2026-12-26 00:00:00',
            ],
            // Add Eid al-Fitr / Eid al-Adha if needed (lunar, approximate)
            // [
            //     'title'      => 'Eid al-Fitr (tentative)',
            //     'start_date' => '2026-03-20 00:00:00', // Example
            //     'end_date'   => '2026-03-20 00:00:00',
            // ],
        ];

        $schools = School::where('status', 1)->get();

        foreach ($schools as $school) {
            $academic_year = AcademicYear::where([
                ['school_id', $school->id],
                ['status', 1],
            ])->first();

            if (! $academic_year) {
                // Skip if no active academic year - or log
                continue;
            }

            foreach ($holidays as $holiday) {
                DB::table('events')->updateOrInsert(
                    [
                        'school_id'        => $school->id,
                        'title'            => $holiday['title'],
                        'start_date'       => $holiday['start_date'],
                    ],
                    [
                        'academic_year_id' => $academic_year->id,
                        'select_type'      => 'school',
                        'category'         => 'holidays',
                        'end_date'         => $holiday['end_date'],
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]
                );
            }
        }
    }
}