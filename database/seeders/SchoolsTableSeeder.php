<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use Illuminate\Database\Seeder;

class SchoolsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Synthetic demo schools. AI stays off. They are marked demo and test.
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
            $slug = $school['slug'];
            unset($school['slug']);
            ReferenceUpsert::sync('schools', ['slug' => $slug], $school + [
                'toshi_enabled' => 0,
                'toshi_mode' => 'preview',
                'is_demo' => 1,
                'is_test' => 1,
            ]);
        }

        $this->command->info('Seeded ' . count($fakeSchools) . ' safe demo Ugandan schools.');
    }
}