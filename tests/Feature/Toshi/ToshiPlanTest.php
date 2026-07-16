<?php

namespace Tests\Feature\Toshi;

use App\Services\ToshiPlanService;
use Tests\TestCase;

class ToshiPlanTest extends TestCase
{
    private ToshiPlanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ToshiPlanService;
    }

    /** @test */
    public function single_item_query_returns_null()
    {
        $plan = $this->service->generatePlan('add student John');

        $this->assertNull($plan, 'Single item should not trigger a plan');
    }

    /** @test */
    public function add_students_comma_separated_returns_plan()
    {
        $plan = $this->service->generatePlan('add students John, Grace, Peter');

        $this->assertNotNull($plan);
        $this->assertCount(3, $plan['steps']);
        $this->assertEquals('toolAddStudent', $plan['tool']);
        $this->assertEquals('John', $plan['steps'][0]['args']['name']);
        $this->assertEquals('Grace', $plan['steps'][1]['args']['name']);
        $this->assertEquals('Peter', $plan['steps'][2]['args']['name']);
    }

    /** @test */
    public function add_students_and_separated_returns_plan()
    {
        $plan = $this->service->generatePlan('add students Alice and Bob and Charlie');

        $this->assertNotNull($plan);
        $this->assertCount(3, $plan['steps']);
        $this->assertEquals('toolAddStudent', $plan['tool']);
    }

    /** @test */
    public function add_students_with_class_includes_shared_arg()
    {
        $plan = $this->service->generatePlan('add students Grace and Peter in Primary 4');

        $this->assertNotNull($plan);
        $this->assertCount(2, $plan['steps']);
        $this->assertEquals('toolAddStudent', $plan['tool']);
        foreach ($plan['steps'] as $step) {
            $this->assertEquals('Primary 4', $step['args']['class']);
        }
    }

    /** @test */
    public function record_attendance_batch_returns_plan()
    {
        $plan = $this->service->generatePlan('record attendance for Alice, Bob, Charlie');

        $this->assertNotNull($plan);
        $this->assertCount(3, $plan['steps']);
        $this->assertEquals('toolRecordAttendance', $plan['tool']);
        $this->assertEquals('Alice', $plan['steps'][0]['args']['student']);
    }

    /** @test */
    public function record_attendance_with_date_and_status()
    {
        $plan = $this->service->generatePlan('record attendance for Alice, Bob on 2026-07-15 as present');

        $this->assertNotNull($plan);
        $this->assertCount(2, $plan['steps']);
        $this->assertEquals('2026-07-15', $plan['steps'][0]['args']['date']);
        $this->assertEquals('present', $plan['steps'][0]['args']['status']);
    }

    /** @test */
    public function create_subjects_batch_returns_plan()
    {
        $plan = $this->service->generatePlan('create subjects Math, English, Science');

        $this->assertNotNull($plan);
        $this->assertCount(3, $plan['steps']);
        $this->assertEquals('toolCreateSubject', $plan['tool']);
        $this->assertEquals('Math', $plan['steps'][0]['args']['name']);
    }

    /** @test */
    public function single_subject_does_not_trigger_plan()
    {
        $plan = $this->service->generatePlan('create subject Mathematics');

        $this->assertNull($plan);
    }

    /** @test */
    public function short_irrelevant_query_returns_null()
    {
        $plan = $this->service->generatePlan('hello');

        $this->assertNull($plan);
    }

    /** @test */
    public function empty_query_returns_null()
    {
        $plan = $this->service->generatePlan('');

        $this->assertNull($plan);
    }
}
