<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\AcademicYear;

class SchoolDetailsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // The original keys — keeping them exactly as-is
        $metaKeys = [
            'about_us',
            'admission_open',
            'admission_close_message',
            'admission_close_on',
            'affiliation_no',
            'affiliated_by',
            'board',
            'date_of_establishment',
            'landline_no',
            'moto',
            'school_logo',
            'website',
        ];

        // Some sensible Uganda defaults (override via admin later)
        $defaults = [
            'about_us'                  => 'A leading school committed to holistic education and excellence in Kampala.',
            'admission_open'            => '1',  // 1 = open, 0 = closed
            'admission_close_message'   => 'Admissions for the current academic year are now closed. Contact us for next session.',
            'admission_close_on'        => null,
            'affiliation_no'            => 'N/A',
            'affiliated_by'             => 'Ministry of Education and Sports',
            'board'                     => 'UNEB',  // Uganda National Examinations Board — most relevant
            'date_of_establishment'     => '2000-01-01',
            'landline_no'               => '+256 414 XXX XXX',
            'moto'                      => 'Knowledge, Discipline & Excellence',  // common Ugandan school motto style
            'school_logo'               => 'images/default-logo.png',
            'website'                   => 'https://myschool.ug',
        ];

        $schools = School::where('status', 1)->get();

        foreach ($schools as $school) {
            $academicYear = AcademicYear::where([
                'school_id' => $school->id,
                'status'    => 1,
            ])->first();

            // Skip if no active academic year (consistent with your other seeders)
            if (! $academicYear) {
                continue;
            }

            foreach ($metaKeys as $key) {
                $value = $defaults[$key] ?? '-';

                DB::table('school_details')->updateOrInsert(
                    [
                        'school_id' => $school->id,
                        'meta_key'  => $key,
                    ],
                    [
                        'meta_value' => $value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        $this->command->info('School details seeded for ' . $schools->count() . ' active schools.');
    }
}