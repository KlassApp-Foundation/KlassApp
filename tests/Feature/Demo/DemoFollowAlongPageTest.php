<?php

namespace Tests\Feature\Demo;

use App\Services\DemoSchoolCommsGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Demo\Concerns\BuildsFollowAlongSchools;
use Tests\TestCase;

/**
 * Task 9 Tier 0: the public follow-along page at /demo.
 */
class DemoFollowAlongPageTest extends TestCase
{
    use BuildsFollowAlongSchools;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config([
            'app.timezone' => 'Pacific/Auckland',
            'services.whatsapp.demo_parent_user_id' => 0,
            'services.whatsapp.privacy_notice_url' => 'https://example.com/privacy',
            'klassapp.demo.whatsapp_number' => '+1 555 010 0000',
            'klassapp.demo.bind_ttl_minutes' => 45,
            'klassapp.demo.links' => [
                ['label' => 'n8n workflows', 'url' => 'https://example.com/n8n-workflows'],
                ['label' => 'Project repository', 'url' => 'https://github.com/KlassApp-Foundation/KlassApp'],
                ['label' => 'Not set yet', 'url' => null],
            ],
        ]);
        DemoSchoolCommsGuard::flushCache();
        $this->buildSchools();
        // The suite's cache store is a file store, so throttle counters
        // outlive a test: each test asks from its own fictional address.
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.' . random_int(1, 254) . '0' . random_int(0, 9)]);
    }

    public function test_task9_demo_page_is_public_and_not_indexed(): void
    {
        $response = $this->get('/demo');

        $response->assertOk();
        $this->assertGuest();
        $this->assertStringContainsString('noindex', (string) $response->headers->get('X-Robots-Tag'));
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_task9_demo_page_is_rate_limited(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->get('/demo')->assertOk();
        }

        $this->get('/demo')->assertStatus(429);
    }

    public function test_task9_demo_page_has_the_qr_open_whatsapp_and_bind_links(): void
    {
        $response = $this->get('/demo')->assertOk();
        $html = $response->getContent();

        $this->assertMatchesRegularExpression('/data-testid="demo-qr"[^>]*>\s*<svg/', $html);
        $response->assertSee('href="https://wa.me/15550100000?text=JOIN"', false);
        $response->assertSee('>Open WhatsApp</a>', false);
        foreach ($this->fx['demo']['students'] as $student) {
            $response->assertSee('href="https://wa.me/15550100000?text=BIND%20' . $student['code'] . '"', false);
            $response->assertSee('Bind to ' . $student['first'], false);
        }
        $response->assertSee('Your number is removed after 45 minutes.');
        $response->assertSee('href="https://example.com/n8n-workflows"', false);
        $response->assertSee('href="https://github.com/KlassApp-Foundation/KlassApp"', false);
        $response->assertSee('href="https://example.com/privacy"', false);
        $response->assertDontSee('Not set yet');
    }

    public function test_task9_demo_page_lists_only_demo_school_students_and_no_real_data(): void
    {
        $response = $this->get('/demo')->assertOk();
        $html = $response->getContent();

        $this->assertSame(3, substr_count($html, 'data-testid="demo-student"'));
        foreach ($this->fx['demo']['students'] as $student) {
            $response->assertSee($student['code']);
            // First names only: no surnames, KLS numbers, emails or phones.
            $response->assertDontSee($student['user']->name);
            $response->assertDontSee($student['kls']);
        }
        $real = $this->fx['real']['students'][0];
        $response->assertDontSee('Zed');
        $response->assertDontSee($real['code']);
        $response->assertDontSee('Hillside');
        $this->assertDoesNotMatchRegularExpression('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[a-z]{2,}/', $html);
        $this->assertStringNotContainsString('+25670019', $html);
    }

    public function test_task9_demo_page_without_a_number_shows_no_whatsapp_links(): void
    {
        config(['klassapp.demo.whatsapp_number' => null]);

        $response = $this->get('/demo')->assertOk();

        $response->assertSee('data-testid="demo-no-number"', false);
        $response->assertDontSee('wa.me', false);
    }
}
