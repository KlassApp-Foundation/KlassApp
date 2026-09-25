<?php

namespace Tests\Feature\Security;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ViewTrussGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_gate_denies_everyone_except_siteadmin()
    {
        DB::table('usergroups')->insert([
            ['id' => 1, 'name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'Truss Gate School',
            'slug' => 'truss-gate-school',
            'email' => 'admin@trussgate.sch.ug',
            'phone' => '+256700000012',
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $superAdmin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 1,
            'email' => 'superadmin@trussgate.sch.ug',
        ]);
        $schoolAdmin = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'email' => 'schooladmin@trussgate.sch.ug',
        ]);

        $teacher = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 5,
            'email' => 'teacher@trussgate.sch.ug',
        ]);

        // Truss's Authorize middleware bypasses the gate in local-class envs;
        // the gate itself is the fail-closed policy for non-local deployments.
        Auth::setUser($superAdmin);
        $this->assertTrue(Gate::allows('viewTruss'));

        Auth::setUser($schoolAdmin);
        $this->assertFalse(Gate::allows('viewTruss'));

        Auth::setUser($teacher);
        $this->assertFalse(Gate::allows('viewTruss'));
    }
}
