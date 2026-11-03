<?php

namespace Database\Seeders;

use Database\Seeders\result\ExamTypes;
use Database\Seeders\result\RemarksTableSeeder;
use Illuminate\Database\Seeder;

/**
 * Reference rows only. No schools, no people.
 *
 * Order is the dependency order:
 *   usergroups (no dependency)
 *   countries, then states, then cities
 *   plans, settings, roles, templates, keywords, qualifications
 *   curricula and grading bands (no school)
 *   absent reasons, exam types, remarks
 *   pay categories, payroll items, transaction types
 *   EMIS directory, when the dataset file is present
 */
class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UsergroupTableSeeder::class);
        $this->call(CountriesTableSeeder::class);
        $this->call(StatesTableSeeder::class);
        $this->call(CitiesTableSeeder::class);
        $this->call(PlansTableSeeder::class);
        $this->call(SettingsTableSeeder::class);
        $this->call(RolesTableSeeder::class);
        $this->call(MailTemplatesSeeder::class);
        $this->call(SmsTemplatesTableSeeder::class);
        $this->call(KeywordsTableSeeder::class);
        $this->call(QualificationTableSeeder::class);
        $this->call(ReferenceCurriculaSeeder::class);
        $this->call(ReferenceGradingScaleSeeder::class);
        $this->call(AbsentReasonsTableSeeder::class);
        $this->call(ExamTypes::class);
        $this->call(RemarksTableSeeder::class);
        $this->call(PayCategoryTableSeeder::class);
        $this->call(PayrollItemTableSeeder::class);
        $this->call(TransactionTypeTableSeeder::class);
        $this->call(EmisSchoolTableSeeder::class);
    }
}
