<?php

namespace Tests\Feature\WhatsApp;

use App\Models\ParentLinkRequest;
use App\Services\DemoSchoolCommsGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Marks\Concerns\ReleaseRulesFixtures;
use Tests\TestCase;

/**
 * The WhatsApp ID matcher uses StudentIdGeneratorService::PATTERN from #1252
 * (branch cursor/t060-widen-student-id-c495): KLS plus exactly 7 digits, or
 * KLS plus exactly 11 digits. Other lengths are not a KlassApp student ID.
 *
 * The constant is defined on this branch so the matcher compiles before #1252
 * merges. The pupils come from the shared follow-along fixtures; an 11-digit
 * ID is written with a query, because the model guard still pins seven digits.
 */
class LongStudentIdMatcherTest extends TestCase
{
    use RefreshDatabase;
    use ReleaseRulesFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON');
        config([
            'services.whatsapp.verify_signature' => false,
            'services.whatsapp.replies_enabled' => true,
            'services.whatsapp.demo_only_recipients' => false,
        ]);
        DemoSchoolCommsGuard::flushCache();
        Cache::flush();
        $this->relaxDeliveryLog();
        $this->buildSchools();
        DemoSchoolCommsGuard::flushCache();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out']]], 200)]);
        Http::preventStrayRequests();
    }

    /** Store the ID the generator would issue, past the old seven-digit ceiling. */
    private function storeId(string $which, int $index, string $kls): int
    {
        $studentId = (int) $this->fx[$which]['students'][$index]['user']->id;
        DB::table('student_academics')
            ->where('school_id', $this->fx[$which]['school']->id)
            ->where('user_id', $studentId)
            ->update(['klassapp_student_id' => $kls]);

        return $studentId;
    }

    private function inbound(string $text): \Illuminate\Testing\TestResponse
    {
        $this->withoutExceptionHandling();

        return $this->call('POST', '/api/whatsapp/inbound', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['entry' => [['changes' => [['value' => [
            'contacts' => [['profile' => ['name' => 'Guardian'], 'wa_id' => '256700123456']],
            'messages' => [['from' => '256700123456', 'id' => 'wamid.in.1', 'type' => 'text', 'text' => ['body' => $text]]],
        ]]]]]]));
    }

    public function test_a_seven_digit_id_reaches_the_link_request(): void
    {
        $studentId = $this->storeId('real', 0, 'KLS0010001');

        $this->inbound('KLS0010001')->assertOk();

        $request = ParentLinkRequest::sole();
        $this->assertSame($studentId, (int) $request->suggested_student_id);
        $this->assertSame((int) $this->fx['real']['school']->id, (int) $request->school_id);
    }

    public function test_an_eleven_digit_id_reaches_the_link_request(): void
    {
        $studentId = $this->storeId('real', 0, 'KLS00100010001');

        $this->inbound('kls00100010001')->assertOk();

        $request = ParentLinkRequest::sole();
        $this->assertSame($studentId, (int) $request->suggested_student_id);
        $this->assertSame((int) $this->fx['real']['school']->id, (int) $request->school_id);
    }

    public function test_other_lengths_are_not_treated_as_a_klassapp_id(): void
    {
        foreach ([6, 8, 10, 12, 16, 17] as $length) {
            $this->inbound('KLS'.str_repeat('1', $length))->assertOk();
        }

        $this->assertSame(0, ParentLinkRequest::count());
    }
}
