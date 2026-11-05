<?php

namespace Database\Seeders;

use Database\Seeders\result\ClassesSeeder;
use Database\Seeders\result\ExamTableSeeder;
use Database\Seeders\result\MarksTableSeeder;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Synthetic demo schools. Never runs when APP_ENV=production.
 *
 * Depends on reference data (countries, states, cities, roles,
 * exam types, remarks). That seeder is called first.
 *
 * School chain, in dependency order:
 *   schools
 *   academic years, then the three terms those years need
 *   sections and standards (class catalog), then subjects
 *   school staff, then teachers
 *   one standards_link per class
 *   subscriptions, leave types, grade rows, holidays, page categories
 *   book categories, then books
 *   role assignments, classes, exams, marks, one cash account
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') || config('app.env') === 'production') {
            throw new RuntimeException('DemoSeeder refuses to run when APP_ENV=production.');
        }

        $this->call(ReferenceSeeder::class);

        $this->call(SchoolsTableSeeder::class);
        $this->call(AcademicYearsTableSeeder::class);
        $this->call(AcademicTermsTableSeeder::class);
        $this->call(SectionsTableSeeder::class);
        $this->call(StandardsTableSeeder::class);
        $this->call(SubjectsTableSeeder::class);
        $this->call(SchoolDetailsTableSeeder::class);
        $this->call(UsersSchoolAdminTableSeeder::class);
        $this->call(UsersTeacherTableSeeder::class);
        $this->call(StandardsLinkTableSeeder::class);
        $this->call(SubscriptionsTableSeeder::class);
        $this->call(LeaveTypesTableSeeder::class);
        $this->call(ScholasticGradesTableSeeder::class);
        $this->call(NonScholasticGradesTableSeeder::class);
        $this->call(HolidaySeeder::class);
        $this->call(PageCategoryTableSeeder::class);
        $this->call(BooksCategoryTableSeeder::class);
        $this->call(BooksTableSeeder::class);
        $this->call(RoleUsersTableSeeder::class);
        $this->call(ClassesSeeder::class);
        $this->call(ExamTableSeeder::class);
        $this->call(MarksTableSeeder::class);
        $this->call(TrasactionAccountTableSeeder::class);
        $this->call(TeacherTableSeeder::class);
    }
}
