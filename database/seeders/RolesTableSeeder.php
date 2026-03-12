<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $roles = [
            [
                'id'           => 1,
                'name'         => 'leave_applier',
                'display_name' => 'Leave Applier',
                'description'  => 'Staff or teacher who can apply for leave',
            ],
            [
                'id'           => 2,
                'name'         => 'leave_checker',
                'display_name' => 'Leave Checker / Approver',
                'description'  => 'Can review and approve/reject staff leave requests',
            ],
            [
                'id'           => 3,
                'name'         => 'principal',
                'display_name' => 'Principal / Headteacher',
                'description'  => 'School head - full admin access, final approvals',
            ],
            [
                'id'           => 4,
                'name'         => 'student_leave_checker',
                'display_name' => 'Student Leave Checker',
                'description'  => 'Can review and approve student leave/outing requests',
            ],
            [
                'id'           => 5,
                'name'         => 'class_coordinator',
                'display_name' => 'Class Coordinator / Class Teacher',
                'description'  => 'Manages class activities, attendance, parent communication',
            ],
            [
                'id'           => 6,
                'name'         => 'transport_coordinator',
                'display_name' => 'Transport Coordinator',
                'description'  => 'Manages school bus routes, drivers, student transport',
            ],
            [
                'id'           => 7,
                'name'         => 'transport_driver',
                'display_name' => 'Transport Driver',
                'description'  => 'School bus/van driver - limited access',
            ],

            // Bonus realistic Ugandan school roles (feel free to remove if not needed)
            [
                'id'           => 8,
                'name'         => 'deputy_principal',
                'display_name' => 'Deputy Principal / Deputy Headteacher',
                'description'  => 'Assists principal, handles discipline & academics',
            ],
            [
                'id'           => 9,
                'name'         => 'bursar',
                'display_name' => 'Bursar / Accountant',
                'description'  => 'Handles fees, payments, financial reports',
            ],
            [
                'id'           => 10,
                'name'         => 'librarian',
                'display_name' => 'Librarian',
                'description'  => 'Manages library resources, books, digital materials',
            ],
            [
                'id'           => 11,
                'name'         => 'admin_staff',
                'display_name' => 'Admin Staff / Secretary',
                'description'  => 'General office support, records, communication',
            ],
            [
                'id'           => 12,
                'name'         => 'parent',
                'display_name' => 'Parent / Guardian',
                'description'  => 'Limited access - view child progress, fees, notices',
            ],
            [
                'id'           => 13,
                'name'         => 'student',
                'display_name' => 'Student',
                'description'  => 'Basic access - timetable, assignments, notices',
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['id' => $role['id']],
                [
                    'name'         => $role['name'],
                    'display_name' => $role['display_name'],
                    'description'  => $role['description'],
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]
            );
        }
    }
}