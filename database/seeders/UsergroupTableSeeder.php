<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsergroupTableSeeder extends Seeder
{
    public function run()
    {
        $groups = [
            ['id' => 1, 'name' => 'SiteAdmin'],
            ['id' => 2, 'name' => 'SiteSubadmin'],
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
            ['id' => 13, 'name' => 'Non Teaching'],
        ];

        foreach ($groups as $group) {
            DB::table('usergroups')->updateOrInsert(
                ['id' => $group['id']], // match condition
                [
                    'name'       => $group['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
