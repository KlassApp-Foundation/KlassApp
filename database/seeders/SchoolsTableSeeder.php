<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SchoolsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // Super safe, generic demo schools — no real names or emails
        $fakeSchools = [
            [
                'name'    => 'Test School One',
                'email'   => 'testschoolone@gmail.com',
                'phone'   => '+256 700 111 222',
                'slug'    => 'test-school-one',
                'status'  => 1,
            ],
            [
                'name'    => 'Demo Academy Uganda',
                'email'   => 'demoacademyug@gmail.com',
                'phone'   => '+256 701 333 444',
                'slug'    => 'demo-academy-uganda',
                'status'  => 1,
            ],
            [
                'name'    => 'Sample Primary & Secondary School',
                'email'   => 'sampleschoolkampala@gmail.com',
                'phone'   => '+256 702 555 666',
                'slug'    => 'sample-primary-secondary',
                'status'  => 1,
            ],
            [
                'name'    => 'Kampala Test College',
                'email'   => 'kampalatestcollege@gmail.com',
                'phone'   => '+256 703 777 888',
                'slug'    => 'kampala-test-college',
                'status'  => 1,
            ],
            [
                'name'    => 'Future Stars International School',
                'email'   => 'futurestarsdemo@gmail.com',
                'phone'   => '+256 704 999 000',
                'slug'    => 'future-stars-international',
                'status'  => 1,
            ],
        ];

        foreach ($fakeSchools as $school) {
            
            DB::table('schools')->updateOrInsert(
                ['slug' => $school['slug']],
                [
                    'name'       => $school['name'],
                    'email'      => $school['email'],
                    'phone'      => $school['phone'],
                    'status'     => $school['status'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $this->command->info('Seeded ' . count($fakeSchools) . ' safe demo Ugandan schools.');
    }
}