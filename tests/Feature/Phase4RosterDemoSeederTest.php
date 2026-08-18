<?php

namespace Tests\Feature;

use Database\Seeders\Phase4RosterDemoSeeder;
use App\Models\School;
use App\Models\StudentAcademic;
use App\Models\Teacherlink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4RosterDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase4_demo_seeder_is_idempotent_and_creates_isolated_roster_data(): void
    {
        $this->seed(Phase4RosterDemoSeeder::class);
        $this->seed(Phase4RosterDemoSeeder::class);

        $this->assertDatabaseHas('schools', [
            'email' => 'phase4-roster-demo@klassapp.xyz',
            'name' => 'Phase 4 Roster Demo School',
        ]);
        $schoolId = School::where('email', 'phase4-roster-demo@klassapp.xyz')->value('id');

        $this->assertSame(6, User::where('school_id', $schoolId)->count());
        $this->assertDatabaseHas('sections', ['school_id' => $schoolId, 'name' => 'P.4 Demo']);
        $this->assertDatabaseHas('standards_link', ['school_id' => $schoolId, 'stream' => 'A']);
        $this->assertSame(1, Teacherlink::where('school_id', $schoolId)->count());
        $this->assertSame(3, StudentAcademic::where('school_id', $schoolId)->count());
    }
}
