<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Depends on a book category and an active academic year for the school.
 * Book codes are stable so a second run does not insert another copy.
 */
class BooksTableSeeder extends Seeder
{
    public function run(): void
    {
        foreach (School::query()->where('status', 1)->get() as $school) {
            $year = AcademicYear::query()
                ->where('school_id', $school->id)
                ->where('status', 1)
                ->first();
            $categoryId = DB::table('books_category')->where('school_id', $school->id)->orderBy('id')->value('id');
            if ($year === null || $categoryId === null) {
                $this->command?->warn("Skipping books for school {$school->id}.");

                continue;
            }

            foreach ([1, 2, 3] as $number) {
                ReferenceUpsert::sync('books', [
                    'school_id' => $school->id,
                    'book_code' => 'DEMO-'.$school->id.'-'.$number,
                ], [
                    'academic_year_id' => $year->id,
                    'category_id' => $categoryId,
                    'title' => 'Demo Book '.$number,
                    'author' => 'Demo Author',
                    'isbn_number' => 9780000000000 + $number,
                    'quantity' => 5,
                ]);
            }
        }
    }
}
