<?php

namespace Tests\Feature\Admin;

use App\Events\SinglePushEvent;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Standard;
use App\Models\StudentParentLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * The dashboard fee-reminder path must complete without error and carry a real
 * message. Regression: the copy referenced the removed pre-V2 Fee model, so the
 * message came out broken (junk date / missing name) on every reminder.
 *
 * Covers the admin dashboard AND the bursar (accountant) copy, both of which
 * must stay scoped to the caller's school.
 */
class FeeReminderPathTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;
    private User $bursarA;
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
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolA = $this->createSchool('Reminder Path School A');
        $this->schoolB = $this->createSchool('Reminder Path School B');

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 3,
            'email' => 'reminder-admin-a@test.sch.ug',
        ]);

        $this->bursarA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 11,
            'email' => 'reminder-bursar-a@test.sch.ug',
        ]);

        $this->studentA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 6,
            'email' => 'reminder-student-a@test.sch.ug',
            'name' => 'Reminder Path Student A',
        ]);

        $this->studentB = User::factory()->create([
            'school_id' => $this->schoolB->id,
            'usergroup_id' => 6,
            'email' => 'reminder-student-b@test.sch.ug',
            'name' => 'Reminder Path Student B',
        ]);

        $this->parentA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'usergroup_id' => 7,
            'email' => 'reminder-parent-a@test.sch.ug',
        ]);

        StudentParentLink::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $this->studentA->id,
            'parent_id' => $this->parentA->id,
            'status' => 1,
        ]);

        $category = FeesCategories::create([
            'school_id' => $this->schoolA->id,
            'standard_id' => Standard::create(['school_id' => $this->schoolA->id, 'name' => 'primary', 'order' => 0])->id,
            'name' => 'Tuition',
            'amount' => 50000,
            'due_date' => '2026-10-20',
        ]);

        $this->paymentA = FeePayment::create([
            'school_id' => $this->schoolA->id,
            'fee_category_id' => $category->id,
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

    public function test_admin_reminder_completes_and_carries_the_category_and_due_date(): void
    {
        Event::fake([SinglePushEvent::class]);

        $response = $this->actingAs($this->adminA)
            ->post('/admin/dashboard/send/reminder/' . $this->paymentA->id, ['name' => $this->studentA->name]);

        $response->assertOk();
        $this->assertSame('Fees Reminder Sent Successfully', $response->json('success'));

        Event::assertDispatched(SinglePushEvent::class, function (SinglePushEvent $event) {
            $message = (string) ($event->data['message'] ?? '');

            return $event->data['user_id'] === $this->parentA->id
                && str_contains($message, 'Tuition')
                && str_contains($message, 'pending')
                && str_contains($message, '20-10-2026')
                && ! str_contains($message, '1970');
        });
    }

    public function test_accountant_reminder_completes_with_the_same_message(): void
    {
        Event::fake([SinglePushEvent::class]);

        $response = $this->actingAs($this->bursarA)
            ->post('/accountant/dashboard/send/reminder/' . $this->paymentA->id, ['name' => $this->studentA->name]);

        $response->assertOk();
        $this->assertSame('Fees Reminder Sent Successfully', $response->json('success'));

        Event::assertDispatched(SinglePushEvent::class, function (SinglePushEvent $event) {
            $message = (string) ($event->data['message'] ?? '');

            return $event->data['user_id'] === $this->parentA->id
                && str_contains($message, 'Tuition')
                && str_contains($message, '20-10-2026');
        });
    }

    public function test_accountant_reminder_for_another_schools_payment_gets_a_404_and_sends_nothing(): void
    {
        Event::fake([SinglePushEvent::class]);

        $this->actingAs($this->bursarA)
            ->post('/accountant/dashboard/send/reminder/' . $this->paymentB->id, ['name' => $this->studentA->name])
            ->assertNotFound();

        Event::assertNotDispatched(SinglePushEvent::class);
    }

    public function test_admin_reminder_for_an_unknown_student_name_is_a_404(): void
    {
        Event::fake([SinglePushEvent::class]);

        $this->actingAs($this->adminA)
            ->post('/admin/dashboard/send/reminder/' . $this->paymentA->id, ['name' => 'No Such Student'])
            ->assertNotFound();

        Event::assertNotDispatched(SinglePushEvent::class);
    }
}
