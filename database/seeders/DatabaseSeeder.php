<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        $this->call(UsergroupTableSeeder::class);
        $this->call(CountriesTableSeeder::class);
        $this->call(StatesTableSeeder::class);
        $this->call(CitiesTableSeeder::class);
        $this->call(PlansTableSeeder::class);
        $this->call(MailTemplatesSeeder::class);
        $this->call(SettingsTableSeeder::class);
        $this->call(SmsTemplatesTableSeeder::class);
        $this->call(KeywordsTableSeeder::class);
        $this->call(QualificationTableSeeder::class);
        $this->call(AbsentReasonsTableSeeder::class);
        $this->call(RolesTableSeeder::class);
       
        $this->call(UsersSiteAdminTableSeeder::class);
        
        $this->call(SchoolsTableSeeder::class);  //test //
        $this->call(SchoolDetailsTableSeeder::class);  //test //
        $this->call(AcademicYearsTableSeeder::class);  //test //
        $this->call(SectionsTableSeeder::class);  //test //
        $this->call(StandardsTableSeeder::class); //test //
        $this->call(SubjectsTableSeeder::class); //test //
        //$this->call(UsersTableSeeder::class);  //test
        $this->call(UsersSchoolAdminTableSeeder::class);  //Experimental //
        $this->call(UsersTeacherTableSeeder::class);  //Experimental //
       // $this->call(StandardsLinkTableSeeder::class); //test //
        $this->call(UsersStudentTableSeeder::class);  //Experimental //
       
        $this->call(SubscriptionsTableSeeder::class);  //test //

        $this->call(TeacherTableSeeder::class); //test //
       
        $this->call(BooksCategoryTableSeeder::class); //test //
        $this->call(BooksTableSeeder::class); //test //

        //$this->call(AssignmentTableSeeder::class); //test
        //$this->call(StudentAssignmentTableSeeder::class); //test
        $this->call(ScholasticGradesTableSeeder::class); //test //
        $this->call(NonScholasticGradesTableSeeder::class); //test //
        $this->call(LeaveTypesTableSeeder::class); //test //
        $this->call(RoleUsersTableSeeder::class);
        //$this->call(FeesTableSeeder::class); //test
        //$this->call(FeePaymentsTableSeeder::class); //test
   
        $this->call(HolidaySeeder::class);//test //
        //$this->call(LessonPlanTableSeeder::class);//test
        $this->call(PageCategoryTableSeeder::class);//test
  
       /* $this->call(CategoryTableSeeder::class); //test //
        $this->call(VendorsTableSeeder::class); //test //
        $this->call(CategoryVendorsTableSeeder::class); //test //
        $this->call(ProductsTableSeeder::class); //test //
        $this->call(ProductCodesTableSeeder::class); //test //
        $this->call(LocationsTableSeeder::class); //test // */ 

        $this->call(PayCategoryTableSeeder::class);
        $this->call(PayrollItemTableSeeder::class);
        $this->call(TransactionTypeTableSeeder::class);
        
        $this->call(TrasactionAccountTableSeeder::class);
          
    }
}