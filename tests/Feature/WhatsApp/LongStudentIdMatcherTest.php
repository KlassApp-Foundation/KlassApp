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
 * The WhatsApp ID matcher accepts every KlassApp student ID the generator can
 * issue: KLS plus 7 to 16 digits. Seven digits is every ID issued before a
 * school passed sequence 9,999; longer runs are valid IDs now (#1252).
 *
 * The matcher is a literal in WhatsAppController while #1252 is unmerged, so
 * these tests pin the same shape as StudentIdGeneratorService::PATTERN there.
 * The pupils come from the shared follow-along fixtures; only the stored ID is
 * rewritten, because the model guard still pins the old seven-digit shape.
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

    public function test_a_ten_digit_id_still_reaches_the_link_request(): void
    {
        $studentId = $this->storeId('real', 0, 'KLS0011000001');

        $this->inbound('KLS0011000001')->assertOk();

        $request = ParentLinkRequest::sole();
        $this->assertSame($studentId, (int) $request->suggested_student_id);
        $this->assertSame((int) $this->fx['real']['school']->id, (int) $request->school_id);
    }

    public function test_the_old_seven_digit_id_still_works(): void
    {
        $studentId = $this->storeId('real', 0, 'KLS0010001');

        $this->inbound('KLS0010001')->assertOk();

        $this->assertSame($studentId, (int) ParentLinkRequest::sole()->suggested_student_id);
    }

    public function test_sixteen_digits_is_accepted_and_seventeen_is_not(): void
    {
        $long = 'KLS' . str_repeat('1', 16);
        $studentId = $this->storeId('real', 0, $long);

        $this->inbound($long)->assertOk();
        $this->assertSame($studentId, (int) ParentLinkRequest::sole()->suggested_student_id);

        // Past the limit the message is not treated as a KlassApp ID at all.
        $this->inbound('KLS' . str_repeat('2', 17))->assertOk();
        $this->assertSame(1, ParentLinkRequest::count());
    }
}
