<?php

namespace Tests\Feature\Demo;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A fresh migrate must create schema only: no demo schools, no users, no
 * academic years. Demo data is always seeded on purpose (DemoAcademySeeder),
 * never by a migration. A RefreshDatabase test starts from exactly that state.
 */
class FreshInstallSeedsNoSchoolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_migrate_creates_no_schools_or_users(): void
    {
        // The is_demo schema change from the old demo migration is retained.
        $this->assertTrue(Schema::hasColumn('schools', 'is_demo'));

        $this->assertSame(0, School::count(), 'a fresh migrate must not create schools');
        $this->assertSame(0, User::count(), 'a fresh migrate must not create users');
        $this->assertSame(0, DB::table('academic_years')->count());
        $this->assertSame(0, DB::table('userprofiles')->count());
    }
}
