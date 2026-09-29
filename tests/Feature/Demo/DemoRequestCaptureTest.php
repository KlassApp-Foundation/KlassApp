<?php

namespace Tests\Feature\Demo;

use App\Mail\DemoRequestAlertMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DemoRequestCaptureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Cache::flush();

        config([
            'services.demo.leads_email' => 'leads@example.test',
            'services.demo.booking_url' => 'https://book.example.test/demo-call',
        ]);
    }

    /** @return array<string, string> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'school_name'  => 'Sunrise Junior School',
            'contact_name' => 'Head Teacher',
            'phone'        => '0777000000',
            'email'        => 'head@sunrise.test',
            'district'     => 'Kampala',
            'message'      => 'We would like a walkthrough.',
            'source_page'  => 'landing-v2#demo',
        ], $overrides);
    }

    public function test_valid_request_is_saved_and_alert_is_queued(): void
    {
        $response = $this->post('/demo-request', $this->payload());

        $response->assertRedirect(url('/') . '#demo');
        $response->assertSessionHas('demo_request_success');

        $this->assertDatabaseHas('demo_requests', [
            'school_name' => 'Sunrise Junior School',
            'email'       => 'head@sunrise.test',
            'source_page' => 'landing-v2#demo',
        ]);

        Mail::assertQueued(DemoRequestAlertMail::class, function (DemoRequestAlertMail $mail) {
            return $mail->hasTo('leads@example.test')
                && $mail->hasReplyTo('head@sunrise.test');
        });
    }

    public function test_honeypot_is_silently_accepted_and_dropped(): void
    {
        $response = $this->post('/demo-request', $this->payload(['website' => 'http://spam.example']));

        $response->assertRedirect(url('/') . '#demo');
        $this->assertDatabaseCount('demo_requests', 0);
        Mail::assertNothingQueued();
    }

    public function test_validation_errors_are_returned_and_nothing_is_saved(): void
    {
        $response = $this->post('/demo-request', $this->payload([
            'school_name' => '',
            'email'       => 'not-an-email',
        ]));

        $response->assertSessionHasErrors(['school_name', 'email']);
        $this->assertDatabaseCount('demo_requests', 0);
        Mail::assertNothingQueued();
    }

    public function test_requests_are_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/demo-request', $this->payload(['email' => "head{$i}@sunrise.test"]))->assertRedirect();
        }

        $this->post('/demo-request', $this->payload(['email' => 'blocked@sunrise.test']))->assertStatus(429);
    }

    public function test_saved_when_no_leads_email_is_configured(): void
    {
        config(['services.demo.leads_email' => null]);

        $this->post('/demo-request', $this->payload())->assertRedirect();

        $this->assertDatabaseCount('demo_requests', 1);
        Mail::assertNothingQueued();
    }

    public function test_saved_even_when_the_alert_mail_throws(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('mail down'));

        $this->post('/demo-request', $this->payload())->assertRedirect();

        $this->assertDatabaseCount('demo_requests', 1);
    }

    public function test_success_state_shows_booking_button_when_configured(): void
    {
        $this->withSession(['demo_request_success' => true])
            ->get('/')
            ->assertOk()
            ->assertSee('Pick a time for a call')
            ->assertSee('https://book.example.test/demo-call');
    }

    public function test_siteadmin_list_component_shows_requests(): void
    {
        \App\Models\DemoRequest::create($this->payload());

        \Livewire\Livewire::test(\App\Livewire\Superadmin\Reports\DemoRequests::class)
            ->assertSee('Sunrise Junior School')
            ->assertSee('head@sunrise.test');
    }

    public function test_success_state_hides_booking_button_when_not_configured(): void
    {
        config(['services.demo.booking_url' => null]);

        $this->withSession(['demo_request_success' => true])
            ->get('/')
            ->assertOk()
            ->assertSee('we have your request')
            ->assertDontSee('Pick a time for a call');
    }
}
