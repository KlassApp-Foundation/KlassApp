<?php

namespace Database\Seeders;

use App\Database\ReferenceUpsert;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Depends on a school and that school's admin. Does not assume
 * user id 1 or school id 1 exist.
 */
class TrasactionAccountTableSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::query()->where('status', 1)->orderBy('id')->first();
        if ($school === null) {
            $this->command?->warn('No school. Skipping the cash account.');

            return;
        }

        $adminId = User::query()
            ->where('school_id', $school->id)
            ->where('usergroup_id', 3)
            ->value('id');
        if ($adminId === null) {
            $this->command?->warn("No admin for school {$school->id}. Skipping the cash account.");

            return;
        }

        ReferenceUpsert::sync('transaction_accounts', [
            'school_id' => $school->id,
            'key' => 'CA',
        ], [
            'user_id' => $adminId,
            'account_number' => '348738747833',
            'ifsc_code' => 'CASH123',
            'name' => 'Cash Account',
        ]);
    }
}
