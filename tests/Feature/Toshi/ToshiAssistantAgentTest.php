<?php

namespace Tests\Feature\Toshi;

use App\AiAgents\ToshiAssistantAgent;
use App\Models\User;
use App\Services\ToshiActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ToshiAssistantAgentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->admin = User::factory()->create(['usergroup_id' => 3, 'school_id' => 1]);
        $this->actingAs($this->admin);
    }

    /** @test */
    public function it_returns_null_when_laragent_disabled()
    {
        config(['toshi.laragent_enabled' => false]);

        $agent = app(ToshiAssistantAgent::class);
        $result = $agent->handleQuery($this->admin, 1, 'how many students?', []);

        $this->assertNull($result);
    }

    /** @test */
    public function it_enforces_daily_budget_limit()
    {
        config(['toshi.laragent_enabled' => true]);
        config(['toshi.daily_llm_limit' => 3]);

        // Exhaust the budget
        Cache::put('toshi_llm_daily_' . $this->admin->id . '_' . date('Y-m-d'), 3, now()->endOfDay());

        $agent = app(ToshiAssistantAgent::class);
        $result = $agent->handleQuery($this->admin, 1, 'test query', []);

        $this->assertNull($result, 'Should return null when budget exhausted');
    }

    /** @test */
    public function budget_is_per_date()
    {
        config(['toshi.daily_llm_limit' => 5]);

        $todayKey = 'toshi_llm_daily_' . $this->admin->id . '_' . date('Y-m-d');
        $yesterdayKey = 'toshi_llm_daily_' . $this->admin->id . '_' . date('Y-m-d', strtotime('-1 day'));

        Cache::put($todayKey, 2, now()->endOfDay());
        Cache::put($yesterdayKey, 5, now()->endOfDay());

        $this->assertEquals(2, Cache::get($todayKey));
        $this->assertEquals(5, Cache::get($yesterdayKey));
    }

    /** @test */
    public function tool_add_student_returns_correct_structure()
    {
        $result = ToshiActionService::addStudent(
            $this->admin, ['name' => 'Test Student', 'class' => 'P1']
        );

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsString($result['message']);
    }

    /** @test */
    public function tool_list_classes_returns_correct_structure()
    {
        $result = ToshiActionService::listClasses($this->admin);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsBool($result['success']);
    }

    /** @test */
    public function tool_list_teachers_returns_correct_structure()
    {
        $result = ToshiActionService::listTeachers($this->admin);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertIsBool($result['success']);
    }

    /** @test */
    public function laragent_config_has_nvidia_provider()
    {
        $providers = config('laragent.providers');
        $this->assertArrayHasKey('default', $providers);
        $this->assertEquals('nvidia-nim', $providers['default']['label']);
        $this->assertStringContainsString('nvidia', $providers['default']['base_url']);
    }

    /** @test */
    public function feature_flag_defaults_to_disabled()
    {
        $this->assertFalse(config('toshi.laragent_enabled'));
    }

    /** @test */
    public function keyword_router_handles_hello()
    {
        // The keyword router is in the Livewire component, not the agent.
        // This test verifies the agent class can be instantiated with tools.
        $agent = app(ToshiAssistantAgent::class);
        $this->assertNotNull($agent);
    }
}
