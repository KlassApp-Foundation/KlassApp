<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_helper_is_defined(): void
    {
        $this->assertTrue(function_exists('activity'));
        $this->assertInstanceOf(ActivityLogger::class, activity());
    }

    public function test_fluent_logger_persists_activity_log_row(): void
    {
        $user = User::factory()->create([
            'usergroup_id' => 1,
            'school_id' => null,
        ]);

        $log = activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['ip' => '127.0.0.1', 'details' => 'PHPUnit'])
            ->useLog('login')
            ->log('Logged In');

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertDatabaseHas('activity_log', [
            'id' => $log->id,
            'log_name' => 'login',
            'description' => 'Logged In',
            'causer_id' => $user->id,
            'causer_type' => $user->getMorphClass(),
            'subject_id' => $user->id,
            'subject_type' => $user->getMorphClass(),
        ]);
        $this->assertSame(['ip' => '127.0.0.1', 'details' => 'PHPUnit'], $log->properties);
    }

    public function test_logger_infers_school_id_from_causer(): void
    {
        $school = \App\Models\School::create([
            'name' => 'Activity Infer School',
            'slug' => 'activity-infer-school-' . uniqid(),
            'email' => 'activity-infer-' . uniqid() . '@test.sch.ug',
            'phone' => '070' . random_int(1000000, 9999999),
            'status' => 1,
        ]);

        $user = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $school->id,
        ]);

        $log = activity('default')
            ->causedBy($user)
            ->withProperties([])
            ->log('School-scoped action');

        $this->assertSame($school->id, $log->school_id);
    }

    public function test_logger_skips_persist_when_disabled(): void
    {
        config(['activitylog.enabled' => false]);

        $user = User::factory()->create(['usergroup_id' => 1]);

        $result = activity()
            ->causedBy($user)
            ->log('Should not persist');

        $this->assertNull($result);
        $this->assertDatabaseCount('activity_log', 0);
    }
}
