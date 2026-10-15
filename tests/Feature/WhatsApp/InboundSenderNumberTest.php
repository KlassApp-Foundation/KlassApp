<?php

namespace Tests\Feature\WhatsApp;

use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\DemoSchoolCommsGuard;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Inbound webhook "from" values: which WhatsApp user the message is matched
 * to, which number is stored, and which number the reply is sent to.
 * Fictional numbers only. Foreign keys on, non-UTC timezone (rules 35, 42).
 */
class InboundSenderNumberTest extends TestCase
{
    use RefreshDatabase;

    private const APP_SECRET = 'sender-number-test-secret';

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
        ]);
        Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00', 'Africa/Kampala'));
        DemoSchoolCommsGuard::flushCache();
        DB::table('usergroups')->upsert([
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        $this->relaxDeliveryLog();
        Http::preventStrayRequests();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out']]], 200)]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @return array<string, array{string, string}> inbound "from", and the number it belongs to */
    public static function senders(): array
    {
        return [
            'local with a leading 0' => ['0772000104', '+256772000104'],
            '256 with no plus' => ['256772000104', '+256772000104'],
            'already +256' => ['+256772000104', '+256772000104'],
            'non-Uganda' => ['15550100004', '+15550100004'],
        ];
    }

    #[DataProvider('senders')]
    public function test_linked_parent_is_matched_and_answered_at_its_own_number(string $from, string $number): void
    {
        $row = $this->linkedParent($number, $this->school(false));

        $this->inbound($from, 'menu')->assertOk();

        $this->assertSame(1, WhatsAppUser::count(), 'no second row for the same person');
        $this->assertSame($number, $row->fresh()->phone);
        $this->assertRepliesOnlyTo($number);
    }

    #[DataProvider('senders')]
    public function test_new_demo_visitor_is_stored_and_answered_at_its_own_number(string $from, string $number): void
    {
        $school = $this->school(true);
        config(['services.whatsapp.demo_parent_user_id' => $this->parent($school)->id]);

        $this->inbound($from, 'DEMO')->assertOk();

        $this->assertSame([$number], WhatsAppUser::pluck('phone')->all());
        $this->assertRepliesOnlyTo($number);
    }

    public function test_replies_only_ever_go_to_a_non_uganda_sender(): void
    {
        $school = $this->school(true);
        config(['services.whatsapp.demo_parent_user_id' => $this->parent($school)->id]);

        foreach (['JOIN', 'DEMO', 'menu', 'help'] as $text) {
            $this->inbound('15550100004', $text)->assertOk();
        }

        $this->assertRepliesOnlyTo('+15550100004');
    }

    private function assertRepliesOnlyTo(string $number): void
    {
        $to = collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), 'graph.facebook.com'))
            ->map(fn ($pair) => (string) ($pair[0]->data()['to'] ?? ''))
            ->values();

        $this->assertNotEmpty($to, 'a reply was sent');
        $this->assertSame([ltrim($number, '+')], $to->unique()->values()->all());
    }

    private function school(bool $demo): School
    {
        $school = School::create([
            'name' => $demo ? 'Sample Demo Primary' : 'Hillside Sample Primary',
            'email' => ($demo ? 'demo' : 'real') . '@example.com',
            'status' => 1,
        ]);
        $school->forceFill(['is_demo' => $demo ? 1 : 0])->save();

        return $school;
    }

    private function parent(School $school): User
    {
        $parent = User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 7, 'status' => 'active', 'name' => 'Sample Parent']);
        Userprofile::create(['user_id' => $parent->id, 'usergroup_id' => 7, 'school_id' => $school->id, 'firstname' => 'Sample', 'lastname' => 'Parent', 'status' => 'active']);

        return $parent;
    }

    private function linkedParent(string $number, School $school): WhatsAppUser
    {
        return WhatsAppUser::create([
            'phone' => $number,
            'user_id' => $this->parent($school)->id,
            'school_id' => $school->id,
            'opted_in' => true,
            'verified_at' => now(),
        ]);
    }

    private function inbound(string $from, string $text): TestResponse
    {
        $body = json_encode(['entry' => [['changes' => [['value' => [
            'contacts' => [['profile' => ['name' => 'Guest'], 'wa_id' => ltrim($from, '+')]],
            'messages' => [['from' => $from, 'id' => 'wamid.in.' . uniqid(), 'type' => 'text', 'text' => ['body' => $text]]],
        ]]]]]]);

        return $this->call('POST', '/api/whatsapp/inbound', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Hub-Signature-256' => 'sha256=' . hash_hmac('sha256', $body, self::APP_SECRET),
        ], $body);
    }

    /**
     * sqlite enforces message_delivery_log's enum CHECKs, which inbound rows
     * (status "received") do not fit; MySQL runs with strict off. Same columns,
     * as strings, for this test only.
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
