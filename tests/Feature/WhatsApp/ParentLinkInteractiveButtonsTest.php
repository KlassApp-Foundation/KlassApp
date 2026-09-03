<?php

namespace Tests\Feature\WhatsApp;

use App\Models\ParentLinkRequest;
use App\Models\School;
use App\Services\WhatsApp\ParentLinkRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ParentLinkInteractiveButtonsTest extends TestCase
{
    use RefreshDatabase;

    private string $phone = '+256700444555';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '1416403124879552',
            'services.whatsapp.business_api_version' => 'v21.0',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.interactive.test']]], 200),
        ]);
    }

    public function test_reject_notify_sends_interactive_request_link_button(): void
    {
        $school = School::create([
            'name' => 'Button School',
            'email' => 'buttons@test.sch.ug',
            'status' => 1,
        ]);

        $request = ParentLinkRequest::create([
            'school_id' => $school->id,
            'phone' => $this->phone,
            'parent_name' => 'Button Parent',
            'child_name' => 'Button Child',
            'child_class' => 'P.3',
            'school_name' => 'Button School',
            'status' => 'rejected',
        ]);

        app(ParentLinkRequestService::class)->notifyRejected($request, 'Details did not match');

        Http::assertSent(function ($httpRequest) {
            $data = $httpRequest->data();

            return ($data['type'] ?? null) === 'interactive'
                && ($data['interactive']['type'] ?? null) === 'button'
                && str_contains((string) ($data['interactive']['body']['text'] ?? ''), "couldn't approve")
                && str_contains((string) ($data['interactive']['body']['text'] ?? ''), 'Tap *Request Link* below')
                && ! str_contains((string) ($data['interactive']['body']['text'] ?? ''), 'welcome menu')
                && collect($data['interactive']['action']['buttons'] ?? [])
                    ->contains(fn ($b) => ($b['reply']['id'] ?? null) === 'parent_link_flow');
        });
    }

    public function test_approve_notify_sends_interactive_menu_button(): void
    {
        $school = School::create([
            'name' => 'Approve Button School',
            'email' => 'approve-btn@test.sch.ug',
            'status' => 1,
        ]);

        $request = ParentLinkRequest::create([
            'school_id' => $school->id,
            'phone' => $this->phone,
            'parent_name' => 'Approve Parent',
            'child_name' => 'Approve Child',
            'child_class' => 'P.4',
            'school_name' => 'Approve Button School',
            'status' => 'approved',
        ]);

        app(ParentLinkRequestService::class)->notifyApproved($request);

        Http::assertSent(function ($httpRequest) {
            $data = $httpRequest->data();

            return ($data['type'] ?? null) === 'interactive'
                && ($data['interactive']['type'] ?? null) === 'button'
                && str_contains((string) ($data['interactive']['body']['text'] ?? ''), "You've been linked")
                && str_contains((string) ($data['interactive']['body']['text'] ?? ''), 'Tap *Menu* below')
                && ! str_contains((string) ($data['interactive']['body']['text'] ?? ''), 'Reply *MENU*')
                && collect($data['interactive']['action']['buttons'] ?? [])
                    ->contains(fn ($b) => ($b['reply']['id'] ?? null) === 'MENU');
        });
    }

    public function test_pending_status_has_no_typed_command_copy(): void
    {
        $school = School::create([
            'name' => 'Pending Button School',
            'email' => 'pending-btn@test.sch.ug',
            'status' => 1,
        ]);

        $request = ParentLinkRequest::create([
            'school_id' => $school->id,
            'phone' => $this->phone,
            'parent_name' => 'Pending Parent',
            'child_name' => 'Pending Child',
            'child_class' => 'P.1',
            'school_name' => 'Pending Button School',
            'status' => 'pending',
        ]);

        $message = app(ParentLinkRequestService::class)->pendingStatusMessage($request);

        $this->assertStringContainsString('still being reviewed', $message);
        $this->assertStringNotContainsString('Reply', $message);
        $this->assertStringNotContainsString('MENU', $message);
        $this->assertStringNotContainsString('Request Link', $message);
    }
}
