<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UsergroupTableSeeder::class);
        $this->call(CountriesTableSeeder::class);
        $this->call(StatesTableSeeder::class);
        $this->call(CitiesTableSeeder::class);
        $this->call(PlansTableSeeder::class);
        $this->call(SettingsTableSeeder::class);
        $this->call(MailTemplatesSeeder::class);
        $this->call(SmsTemplatesTableSeeder::class);
        $this->call(KeywordsTableSeeder::class);
        $this->call(QualificationTableSeeder::class);
        $this->call(AbsentReasonsTableSeeder::class);
        $this->call(RolesTableSeeder::class);
        $this->call(EmisSchoolTableSeeder::class);
        $this->call(UsersSiteAdminTableSeeder::class);

        $this->command->info('Production essential seeders completed.');
    }
}
