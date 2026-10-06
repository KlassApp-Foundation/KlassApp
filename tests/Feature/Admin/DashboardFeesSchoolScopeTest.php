<?php

namespace Tests\Feature\Admin;

use App\Events\SinglePushEvent;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\StudentParentLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Dashboard fees list and reminders are scoped to the caller's school:
 * another school's records must 404, and no message may be sent for them.
 */
class DashboardFeesSchoolScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;
    private User $studentA;
    private User $studentB;
    private User $parentA;
    private FeePayment $paymentA;
    private FeePayment $paymentB;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolA = $this->createSchool('Fees Scope School A');
        $this->schoolB = $this->createSchool('Fees Scope School B');

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 3,
            'email' => 'fees-scope-admin-a@test.sch.ug',
        ]);

        $this->studentA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 6,
            'email' => 'fees-scope-student-a@test.sch.ug',
            'name' => 'Fees Scope Student A',
        ]);

        $this->studentB = User::factory()->create([
            'school_id' => $this->schoolB->id,
            'usergroup_id' => 6,
            'email' => 'fees-scope-student-b@test.sch.ug',
            'name' => 'Fees Scope Student B',
        ]);

        $this->parentA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 7,
            'email' => 'fees-scope-parent-a@test.sch.ug',
        ]);

        StudentParentLink::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $this->studentA->id,
            'parent_id' => $this->parentA->id,
            'status' => 1,
        ]);

        $this->paymentA = FeePayment::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $this->studentA->id,
            'amount' => 25000,
            'paid_on' => '2026-10-01',
            'recorded_by' => $this->adminA->id,
            'status' => 'paid',
        ]);

        $this->paymentB = FeePayment::create([
            'school_id' => $this->schoolB->id,
            'user_id' => $this->studentB->id,
            'amount' => 25000,
            'paid_on' => '2026-10-01',
            'recorded_by' => $this->adminA->id,
            'status' => 'paid',
        ]);
    }

    private function createSchool(string $name): School
    {
        return School::create([
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)) . '-' . random_int(1000, 9999),
            'email' => strtolower(str_replace(' ', '-', $name)) . '@test.sch.ug',
            'phone' => '+256700' . random_int(100000, 999999),
            'status' => 1,
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
        ]);
    }

    public function test_feeslist_for_another_schools_record_gets_a_404_and_returns_no_data(): void
    {
        $response = $this->actingAs($this->adminA)
            ->get('/admin/dashboard/feeslist/' . $this->paymentB->id);

        $response->assertNotFound();
        $response->assertDontSee('Fees Scope Student B');
    }

    public function test_feeslist_never_serves_the_legacy_unpaid_flow(): void
    {
        // The legacy Fee model this endpoint referenced no longer exists
        // (removed with the pre-V2 fees system) and it has no remaining
        // consumer. It must fail closed for every caller instead of erroring
        // or ever resolving another school's data.
        $this->actingAs($this->adminA)
            ->get('/admin/dashboard/feeslist/' . $this->paymentA->id)
            ->assertNotFound();
    }

    public function test_send_reminder_for_another_schools_payment_gets_a_404_and_sends_no_message(): void
    {
        Event::fake([SinglePushEvent::class]);

        $this->actingAs($this->adminA)
            ->post('/admin/dashboard/send/reminder/' . $this->paymentB->id, ['name' => $this->studentA->name])
            ->assertNotFound();

        Event::assertNotDispatched(SinglePushEvent::class);
    }

    public function test_send_reminder_for_own_school_is_not_rejected_as_cross_school(): void
    {
        $response = $this->actingAs($this->adminA)
            ->post('/admin/dashboard/send/reminder/' . $this->paymentA->id, ['name' => $this->studentA->name]);

        $this->assertNotSame(404, $response->getStatusCode());
    }
}
