<?php

namespace Tests\Feature\WhatsApp;

use App\Http\Controllers\Api\WhatsAppController;
use App\Models\MessageDeliveryLog;
use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class WhatsAppParentLinkFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $phone = '+256700111222';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '1416403124879552',
            'services.whatsapp.business_api_version' => 'v21.0',
            'services.whatsapp.parent_link_flow_id' => '999888777',
            'services.whatsapp.parent_link_flow_screen' => 'LINK_REQUEST',
        ]);
    }

    public function test_send_flow_posts_interactive_flow_payload_to_graph_api(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.flow.test']]], 200),
        ]);

        $service = new WhatsAppBusinessService;

        $result = $service->sendFlow(
            phone: $this->phone,
            flowId: '123456789',
            flowToken: 'token-abc',
            screenId: 'LINK_REQUEST',
            ctaLabel: 'Request link',
            body: 'Fill in your details.',
            header: 'Parent link request',
            footer: 'Reviewed by school',
            flowType: 'parent_link_flow',
        );

        $this->assertTrue($result['success']);
        $this->assertSame('wamid.flow.test', $result['message_id']);

        Http::assertSent(function ($request) {
            $payload = $request->data();
            $params = $payload['interactive']['action']['parameters'] ?? [];

            return ($payload['type'] ?? null) === 'interactive'
                && ($payload['interactive']['type'] ?? null) === 'flow'
                && ($params['flow_id'] ?? null) === '123456789'
                && ($params['flow_action_payload']['screen'] ?? null) === 'LINK_REQUEST'
                && ($params['flow_cta'] ?? null) === 'Request link';
        });

        $this->assertDatabaseHas('message_delivery_log', [
            'phone' => $this->phone,
            'flow_type' => 'parent_link_flow',
            'direction' => 'outbound',
        ]);
    }

    public function test_send_parent_link_request_flow_uses_configured_flow_id(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.parent.flow']]], 200),
        ]);

        $service = new WhatsAppBusinessService;
        $result = $service->sendParentLinkRequestFlow($this->phone);

        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) {
            $params = $request->data()['interactive']['action']['parameters'] ?? [];

            return ($params['flow_id'] ?? null) === '999888777';
        });
    }

    public function test_unrecognized_user_request_link_button_triggers_flow_send(): void
    {
        $flowSent = false;

        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendParentLinkRequestFlow')
            ->once()
            ->with($this->phone)
            ->andReturnUsing(function (string $phone) use (&$flowSent) {
                $flowSent = true;

                return ['success' => true, 'message_id' => 'wamid.sent'];
            });
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeHandleUnrecognizedUserMeta('parent_link_flow');

        $this->assertTrue($flowSent, 'Request Link button should invoke sendParentLinkRequestFlow');
    }

    public function test_flow_completion_sends_acknowledgment_message(): void
    {
        $captured = null;

        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendText')
            ->once()
            ->withArgs(function (string $phone, string $message, ?string $flowType) use (&$captured) {
                $captured = compact('phone', 'message', 'flowType');

                return $phone === $this->phone
                    && $flowType === 'parent_link_flow_ack'
                    && str_contains($message, 'Request received')
                    && str_contains($message, 'Amope Nandawula')
                    && str_contains($message, 'P.3');
            })
            ->andReturn(['success' => true, 'message_id' => 'ack']);
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $controller = app(WhatsAppController::class);
        $method = new ReflectionMethod(WhatsAppController::class, 'processMetaFlowReply');
        $method->setAccessible(true);
        $method->invoke($controller, $this->phone, [
            'name' => 'flow',
            'response_json' => json_encode([
                'parent_name' => 'Jane Parent',
                'child_name' => 'Amope Nandawula',
                'child_class' => 'P.3',
            ]),
        ], 'Jane Parent');

        $this->assertNotNull($captured);
        $this->assertSame('parent_link_flow_ack', $captured['flowType']);
        $this->assertStringContainsString('Jane Parent', $captured['message']);
        $this->assertStringContainsString('P.3', $captured['message']);

        $this->assertDatabaseHas('parent_link_requests', [
            'phone' => $this->phone,
            'parent_name' => 'Jane Parent',
            'child_name' => 'Amope Nandawula',
            'child_class' => 'P.3',
            'status' => 'pending',
        ]);
    }

    public function test_meta_webhook_nfm_reply_is_handled_without_button_body(): void
    {
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendText')
            ->once()
            ->andReturn(['success' => true, 'message_id' => 'ack']);
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $controller = app(WhatsAppController::class);
        $method = new ReflectionMethod(WhatsAppController::class, 'handleMetaInbound');
        $method->setAccessible(true);

        $response = $method->invoke($controller, [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'contacts' => [['profile' => ['name' => 'Flow Parent']]],
                        'messages' => [[
                            'from' => '256700111222',
                            'id' => 'wamid.inbound.flow',
                            'type' => 'interactive',
                            'interactive' => [
                                'type' => 'nfm_reply',
                                'nfm_reply' => [
                                    'name' => 'flow',
                                    'response_json' => json_encode([
                                        'parent_name' => 'Flow Parent',
                                        'child_name' => 'Test Child',
                                        'child_class' => 'P.4',
                                    ]),
                                ],
                            ],
                        ]],
                    ],
                ]],
            ]],
        ]);

        $this->assertSame(200, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function invokeHandleUnrecognizedUserMeta(string $body): void
    {
        $controller = app(WhatsAppController::class);
        $method = new ReflectionMethod(WhatsAppController::class, 'handleUnrecognizedUserMeta');
        $method->setAccessible(true);
        $method->invoke($controller, $this->phone, $body, 'Test Parent');
    }
}
