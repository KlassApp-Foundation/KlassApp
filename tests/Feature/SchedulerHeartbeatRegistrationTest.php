<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class SchedulerHeartbeatRegistrationTest extends TestCase
{
    public function test_scheduler_registers_minute_heartbeat_event(): void
    {
        /** @var Schedule $schedule */
        $schedule = $this->app->make(Schedule::class);

        $events = collect($schedule->events());
        $heartbeat = $events->first(
            fn ($event) => ($event->description ?? null) === 'scheduler-heartbeat'
                || str_contains((string) ($event->command ?? ''), 'scheduler-heartbeat')
                || (($event->description ?? null) === null
                    && method_exists($event, 'getSummaryForDisplay')
                    && str_contains($event->getSummaryForDisplay(), 'scheduler-heartbeat'))
        );

        $this->assertNotNull(
            $heartbeat,
            'Expected a named every-minute scheduler-heartbeat event. Found: '.
            $events->map(fn ($e) => $e->getSummaryForDisplay())->implode(' | ')
        );
        $this->assertSame('* * * * *', $heartbeat->expression);
    }
}
