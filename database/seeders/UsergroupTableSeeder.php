<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use Illuminate\Database\Seeder;

class UsergroupTableSeeder extends Seeder
{
    public function run()
    {
        $groups = [
            ['id' => 1, 'name' => 'SiteAdmin'],
            ['id' => 3, 'name' => 'SchoolAdmin'],
            ['id' => 4, 'name' => 'SchoolSubadmin'],
            ['id' => 5, 'name' => 'Teacher'],
            ['id' => 6, 'name' => 'Student'],
            ['id' => 7, 'name' => 'Parent'],
            ['id' => 8, 'name' => 'Librarian'],
            ['id' => 9, 'name' => 'OldStudent'],
            ['id' => 10, 'name' => 'Receptionist'],
            ['id' => 11, 'name' => 'Accountant'],
            ['id' => 12, 'name' => 'Stock Keeper'],
        ];

        foreach ($groups as $group) {
            ReferenceUpsert::sync('usergroups', ['id' => $group['id']], [
                'name' => $group['name'],
            ]);
        }
    }
}
