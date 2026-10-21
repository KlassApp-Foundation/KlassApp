<?php

namespace Tests\Feature\WhatsApp;

use App\Models\MessageDeliveryLog;
use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * A whatsapp_users row whose account is gone (soft-deleted, or user_id null)
 * must be answered as an unrecognised number. Previously the row skipped the
 * stranger path and routeInbound called children() on a null user (HTTP 500).
 */
class OrphanWhatsAppUserInboundTest extends TestCase
{
    use RefreshDatabase;

    private const APP_SECRET = 'orphan-user-test-secret';

    private User $demoParent;

    /** @var list<MessageLogged> */
    private array $warnings = [];

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config([
            'app.timezone' => 'Africa/Kampala',
            'services.whatsapp.app_secret' => self::APP_SECRET,
            'services.whatsapp.verify_signature' => true,
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '000000',
            'services.whatsapp.replies_enabled' => true,
            'services.whatsapp.demo_only_recipients' => false,
        ]);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'Parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $demoSchool = $this->school('Sample Demo Primary', true);
        $this->demoParent = User::factory()->create([
            'school_id' => $demoSchool->id,
            'usergroup_id' => 7,
            'status' => 'active',
            'name' => 'Sample Demo Parent',
            'email' => 'demo-parent@example.com',
            'mobile_no' => '700111000',
        ]);
        config(['services.whatsapp.demo_parent_user_id' => $this->demoParent->id]);

        $this->relaxDeliveryLog();
        Http::preventStrayRequests();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out']]], 200),
        ]);
        Log::listen(function (MessageLogged $event) {
            if ($event->level === 'warning') {
                $this->warnings[] = $event;
            }
        });
    }

    public function test_demo_from_number_linked_to_soft_deleted_user_returns_pointer_and_relinks(): void
    {
        $phone = '+256700900111';
        $admin = $this->removedAdmin();
        $row = $this->whatsappRow($phone, $admin->id, (int) $admin->school_id);
        $admin->delete();

        $this->assertNull(User::find($admin->id));

        $this->inbound($phone, 'demo')->assertOk();

        $this->assertDemoPointer($phone);
        $fresh = $row->fresh();
        $this->assertSame((int) $this->demoParent->id, (int) $fresh->user_id);
        $this->assertSame((int) $this->demoParent->school_id, (int) $fresh->school_id);
        $this->assertMissingUserWarning($row->id, '111');
    }

    public function test_demo_from_number_with_null_user_id_returns_pointer_and_relinks(): void
    {
        $phone = '+256700900222';
        $school = $this->school('Removed Test Primary', false);
        $row = $this->whatsappRow($phone, null, (int) $school->id);

        $this->inbound($phone, 'demo')->assertOk();

        $this->assertDemoPointer($phone);
        $fresh = $row->fresh();
        $this->assertSame((int) $this->demoParent->id, (int) $fresh->user_id);
        $this->assertSame((int) $this->demoParent->school_id, (int) $fresh->school_id);
        $this->assertMissingUserWarning($row->id, '222');
    }

    public function test_hi_from_number_linked_to_missing_user_does_not_error(): void
    {
        $softPhone = '+256700900333';
        $admin = $this->removedAdmin();
        $softRow = $this->whatsappRow($softPhone, $admin->id, (int) $admin->school_id);
        $admin->delete();

        $nullPhone = '+256700900444';
        $school = $this->school('Empty Link Primary', false);
        $nullRow = $this->whatsappRow($nullPhone, null, (int) $school->id);

        $this->inbound($softPhone, 'Hi')->assertOk();
        $this->inbound($nullPhone, 'Hi')->assertOk();

        $this->assertSame(2, MessageDeliveryLog::where('flow_type', 'unrecognized_prompt')->count());
        $this->assertNull(User::find($softRow->fresh()->user_id));
        $this->assertNull($nullRow->fresh()->user_id);
        $this->assertNotSame((int) $this->demoParent->id, (int) $softRow->fresh()->user_id);
    }

    private function assertDemoPointer(string $phone): void
    {
        $logged = MessageDeliveryLog::where('phone', $phone)->where('flow_type', 'demo_pointer')->first();
        $this->assertNotNull($logged, 'demo pointer was not sent');
        $this->assertStringContainsString('Welcome to the KlassApp demo.', (string) $logged->content_preview);
        $this->assertStringContainsString(url('/demo'), (string) $logged->content_preview);

        $bodies = collect(Http::recorded())
            ->map(fn ($pair) => (string) ($pair[0]->data()['text']['body'] ?? ''))
            ->filter()
            ->values();
        $this->assertTrue(
            $bodies->contains(fn (string $body) => str_contains($body, 'Welcome to the KlassApp demo.')),
            'demo pointer was not posted to the sender',
        );
    }

    private function assertMissingUserWarning(int $whatsappUserId, string $lastThree): void
    {
        $match = collect($this->warnings)->first(function (MessageLogged $log) use ($whatsappUserId, $lastThree) {
            $phone = (string) ($log->context['phone'] ?? '');

            return $log->message === 'WhatsApp inbound: number linked to a missing user, answered as unrecognised'
                && (int) ($log->context['whatsapp_user_id'] ?? 0) === $whatsappUserId
                && $phone === '•••'.$lastThree
                && ! str_contains($phone, '256');
        });
        $this->assertNotNull($match, 'missing-user warning was not logged with a masked phone');
    }

    private function school(string $name, bool $demo): School
    {
        $school = School::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'@example.com',
            'status' => 1,
        ]);
        $school->forceFill(['is_demo' => $demo ? 1 : 0])->save();

        return $school;
    }

    private function removedAdmin(): User
    {
        $school = $this->school('Removed Test School '.uniqid(), false);

        return User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'status' => 'active',
            'name' => 'Removed Admin '.uniqid(),
            'email' => 'removed-admin-'.uniqid().'@example.com',
            'mobile_no' => '701'.random_int(100000, 999999),
        ]);
    }

    private function whatsappRow(string $phone, ?int $userId, int $schoolId): WhatsAppUser
    {
        return WhatsAppUser::create([
            'phone' => $phone,
            'user_id' => $userId,
            'school_id' => $schoolId,
            'opted_in' => true,
            'verified_at' => now(),
        ]);
    }

    private function inbound(string $phone, string $text): TestResponse
    {
        $from = ltrim($phone, '+');
        $body = json_encode(['entry' => [['changes' => [['value' => [
            'contacts' => [['profile' => ['name' => 'Guest'], 'wa_id' => $from]],
            'messages' => [['from' => $from, 'id' => 'wamid.in.'.uniqid(), 'type' => 'text', 'text' => ['body' => $text]]],
        ]]]]]]);

        return $this->call('POST', '/api/whatsapp/inbound', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Hub-Signature-256' => 'sha256='.hash_hmac('sha256', $body, self::APP_SECRET),
        ], $body);
    }

    /**
     * sqlite enforces message_delivery_log's enum CHECKs, which inbound rows
     * (status "received") and flow types such as "demo_pointer" do not fit.
     * Same columns, as strings, for this test only.
     */
    private function relaxDeliveryLog(): void
    {
        Schema::drop('message_delivery_log');
        Schema::create('message_delivery_log', function (Blueprint $table) {
            $table->id();
            $table->string('whatsapp_message_id', 100)->nullable();
            $table->string('phone', 20);
            $table->string('template_name', 100)->nullable();
            $table->string('direction')->default('outbound');
            $table->string('category')->nullable();
            $table->string('status')->default('sent');
            $table->decimal('cost_usd', 8, 6)->default(0);
            $table->text('content_preview')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('flow_type', 50)->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('error_message')->nullable();
        });
    }
}
