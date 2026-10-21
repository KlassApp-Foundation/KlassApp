<?php

namespace Tests\Feature\Toshi;

use App\Models\Country;
use App\Models\School;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Onboarding\Steps\ToshiOnboardingV2Driver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ToshiOnboardingV2SeededClassesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_classes_and_subjects_are_asked_after_the_academic_year(): void
    {
        config(['toshi.onboarding_v2' => true]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'status' => 1,
            'order' => 1,
        ]);

        $school = School::create([
            'name' => "Seeded Classes's School",
            'email' => 'seeded-classes@test.sch.ug',
            'phone' => '0700000042',
            'slug' => 'seeded-classes',
            'status' => 1,
            'toshi_enabled' => 0,
            'toshi_mode' => 'onboarding',
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Seeded Admin',
            'email' => 'admin@seeded-classes.sch.ug',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        $driver = app(ToshiOnboardingV2Driver::class);
        foreach ([
            'Seeded Classes Primary',
            'Up to 500',
            'Uganda',
            'UNEB',
            'primary',
            'EMIS-SEED1',
            'skip',
            (string) now()->year,
        ] as $reply) {
            $result = $driver->handleReply($school->fresh(), $reply, $admin->id);
            $this->assertNotSame('rejected', $result['status'], 'Rejected '.$reply.' at '.($result['key'] ?? '?'));
        }

        $school = $school->fresh();
        $this->assertTrue(StandardLink::where('school_id', $school->id)->exists());
        $this->assertTrue(Subject::where('school_id', $school->id)->exists());

        $classes = $driver->promptNext($school, $admin->id);
        $this->assertSame('standards', $classes['key']);

        $afterClasses = $driver->handleReply($school->fresh(), 'done', $admin->id);
        $this->assertSame('advanced', $afterClasses['status']);
        $this->assertSame('subjects', $afterClasses['key']);

        $afterSubjects = $driver->handleReply($school->fresh(), 'done', $admin->id);
        $this->assertSame('advanced', $afterSubjects['status']);
        $this->assertSame('teachers', $afterSubjects['key']);
    }
}
