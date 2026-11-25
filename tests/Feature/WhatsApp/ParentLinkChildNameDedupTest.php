<?php

namespace Tests\Feature\WhatsApp;

use App\Models\ParentLinkRequest;
use App\Models\School;
use App\Services\WhatsApp\ParentLinkRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * A second child at the same school must get its own pending request,
 * not be merged into an existing one for a different child.
 * Match key: phone + school + child_name (case-insensitive, trimmed).
 */
class ParentLinkChildNameDedupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200),
        ]);

        config([
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '1416403124879552',
            'services.whatsapp.business_api_version' => 'v21.0',
        ]);

        DB::table('usergroups')->insert([
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_same_child_at_same_school_is_deduped(): void
    {
        $school = School::create(['name' => 'Dedup School', 'email' => 'dedup@test.sch.ug', 'status' => 1]);
        $svc = app(ParentLinkRequestService::class);

        $r1 = $svc->createFromFlowSubmission('+256700000011', [
            'parent_name' => 'Parent One',
            'child_name'  => 'Grace Auma',
            'child_class' => 'P.7',
            'school_name' => $school->name,
        ]);
        $r2 = $svc->createFromFlowSubmission('+256700000011', [
            'parent_name' => 'Parent One',
            'child_name'  => 'Grace Auma',
            'child_class' => 'P.7',
            'school_name' => $school->name,
        ]);

        $this->assertSame($r1->id, $r2->id, 'Same child at same school must be deduped');
        $this->assertFalse($r2->wasRecentlyCreated);
        $this->assertSame(1, ParentLinkRequest::where('phone', '+256700000011')->count());
    }

    public function test_different_child_at_same_school_creates_separate_request(): void
    {
        $school = School::create(['name' => 'Two Child School', 'email' => 'twochild@test.sch.ug', 'status' => 1]);
        $svc = app(ParentLinkRequestService::class);

        $r1 = $svc->createFromFlowSubmission('+256700000012', [
            'parent_name' => 'Parent Two',
            'child_name'  => 'Grace Auma',
            'child_class' => 'P.7',
            'school_name' => $school->name,
        ]);
        $r2 = $svc->createFromFlowSubmission('+256700000012', [
            'parent_name' => 'Parent Two',
            'child_name'  => 'Oscar Kizza',
            'child_class' => 'P.6',
            'school_name' => $school->name,
        ]);

        $this->assertNotSame($r1->id, $r2->id, 'Different child must create a separate request');
        $this->assertTrue($r2->wasRecentlyCreated);
        $this->assertSame(2, ParentLinkRequest::where('phone', '+256700000012')->count());
    }

    public function test_two_children_back_to_back_each_get_their_own_pending_row(): void
    {
        $school = School::create(['name' => 'Back To Back School', 'email' => 'b2b@test.sch.ug', 'status' => 1]);
        $svc = app(ParentLinkRequestService::class);

        $children = ['Alice Nanteza', 'Brian Ssempa', 'Carol Nakato'];
        $ids = [];
        foreach ($children as $child) {
            $req = $svc->createFromFlowSubmission('+256700000013', [
                'parent_name' => 'Parent Three',
                'child_name'  => $child,
                'child_class' => 'P.5',
                'school_name' => $school->name,
            ]);
            $this->assertTrue($req->wasRecentlyCreated, "{$child} must create a new request");
            $ids[] = $req->id;
        }

        $this->assertSame(3, count(array_unique($ids)), 'Three distinct children must have three distinct request ids');
        $this->assertSame(3, ParentLinkRequest::where('phone', '+256700000013')->count());
    }

    public function test_child_name_match_is_case_insensitive(): void
    {
        $school = School::create(['name' => 'Case School', 'email' => 'case@test.sch.ug', 'status' => 1]);
        $svc = app(ParentLinkRequestService::class);

        $r1 = $svc->createFromFlowSubmission('+256700000014', [
            'parent_name' => 'Parent Four',
            'child_name'  => 'Grace Auma',
            'child_class' => 'P.7',
            'school_name' => $school->name,
        ]);
        $r2 = $svc->createFromFlowSubmission('+256700000014', [
            'parent_name' => 'Parent Four',
            'child_name'  => 'GRACE AUMA',
            'child_class' => 'P.7',
            'school_name' => $school->name,
        ]);

        $this->assertSame($r1->id, $r2->id, 'Same name in different case must be deduped');
    }
}
