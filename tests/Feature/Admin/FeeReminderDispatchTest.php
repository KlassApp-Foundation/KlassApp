<?php

namespace Tests\Feature\Admin;

use App\Events\SinglePushEvent;
use App\Models\AcademicYear;
use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FeeReminderDispatchTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        \DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Reminder School',
            'email' => 'remind@test.sch.ug',
            'phone' => '0700000091',
            'slug' => 'reminder-school',
            'status' => 1,
        ]);
        $this->admin = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 3,
            'name' => 'Remind Admin',
            'email' => 'remind.admin@test.sch.ug',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $this->withoutMiddleware([
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\MustBeSchoolAdmin::class,
            \App\Http\Middleware\MustBeFullSchoolAdmin::class,
            \App\Http\Middleware\MustBePrivilege::class,
        ]);
    }

    public function test_fees_page_offers_a_reminder_only_for_a_balance_and_names_the_parents(): void
    {
        $owing = $this->student('Ada Owing', 100000, 1);
        $this->student('Ben Settled', 450000, 1);

        $html = $this->actingAs($this->admin)->get(route('admin.fee-payments'))->assertOk()->getContent();
        preg_match_all('/data-testid="fee-balance-row".*?<\\/tr>/s', $html, $rows);
        $section = implode("\n", $rows[0]);

        $this->assertCount(1, $rows[0]);
        $this->assertStringContainsString('ADA OWING', $section);
        $this->assertStringNotContainsString('BEN SETTLED', $section);
        $this->assertStringContainsString('data-parents="1"', $section);
        $this->assertStringContainsString('Send a reminder to ', file_get_contents(resource_path('views/admin/fees/payments.blade.php')));
        $this->assertSame($owing->school_id, $this->school->id);
    }

    public function test_confirmed_reminder_uses_the_existing_sender(): void
    {
        Event::fake([SinglePushEvent::class]);
        $student = $this->student('Ada Owing', 100000, 2);

        $this->actingAs($this->admin)
            ->post(route('admin.fee-payments.reminders'), [
                'student_ids' => [$student->id],
                'confirmed' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('reminder_result', 'Sent 1.');

        Event::assertDispatched(SinglePushEvent::class, 2);
    }

    public function test_unconfirmed_post_does_not_send(): void
    {
        Event::fake([SinglePushEvent::class]);
        $student = $this->student('Ada Owing', 100000, 1);

        $this->actingAs($this->admin)
            ->post(route('admin.fee-payments.reminders'), [
                'student_ids' => [$student->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('reminder_result', 'Confirm the reminder first. It would message 1 parent.');

        Event::assertNotDispatched(SinglePushEvent::class);
    }

    public function test_demo_and_test_schools_send_nothing(): void
    {
        Event::fake([SinglePushEvent::class]);
        $student = $this->student('Ada Owing', 100000, 1);
        $this->school->forceFill(['is_demo' => true, 'is_test' => true])->save();

        $this->actingAs($this->admin)
            ->post(route('admin.fee-payments.reminders'), [
                'student_ids' => [$student->id],
                'confirmed' => 1,
            ])
            ->assertRedirect();

        $result = session('reminder_result');
        $this->assertStringContainsString('Sent 0.', $result);
        $this->assertStringContainsString('Demo and test schools do not send messages', $result);
        Event::assertNotDispatched(SinglePushEvent::class);

        $this->school->forceFill(['is_demo' => false, 'is_test' => true])->save();
        $this->actingAs($this->admin)
            ->post(route('admin.fee-payments.reminders'), [
                'student_ids' => [$student->id],
                'confirmed' => 1,
            ]);
        Event::assertNotDispatched(SinglePushEvent::class);
    }

    public function test_another_schools_student_is_not_messaged(): void
    {
        Event::fake([SinglePushEvent::class]);
        $other = School::create([
            'name' => 'Other School',
            'email' => 'other@test.sch.ug',
            'phone' => '0700000092',
            'slug' => 'other-reminder-school',
            'status' => 1,
        ]);
        $foreign = User::create([
            'school_id' => $other->id,
            'usergroup_id' => 6,
            'name' => 'Foreign Student',
            'email' => 'foreign@test.sch.ug',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.fee-payments.reminders'), [
                'student_ids' => [$foreign->id],
                'confirmed' => 1,
            ])
            ->assertRedirect();

        $this->assertStringContainsString('Sent 0.', session('reminder_result'));
        $this->assertStringContainsString('not one with a balance at this school', session('reminder_result'));
        Event::assertNotDispatched(SinglePushEvent::class);
    }

    private function student(string $name, int $paid, int $parents): User
    {
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026 '.$name,
            'description' => 'y',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);
        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary '.$name,
            'order' => 1,
            'status' => '1',
        ]);
        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.1 '.$name,
            'status' => 1,
        ]);
        $link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);
        $category = FeesCategories::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'name' => 'Tuition',
            'amount' => 450000,
        ]);
        $student = User::create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@test.sch.ug',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
            'klassapp_student_id' => sprintf('KLS%07d', $student->id),
        ]);
        FeePayment::create([
            'school_id' => $this->school->id,
            'fee_category_id' => $category->id,
            'user_id' => $student->id,
            'amount' => $paid,
            'paid_on' => now()->toDateString(),
            'payment_method' => 'cash',
            'recorded_by' => $this->admin->id,
            'status' => 'paid',
        ]);
        for ($i = 0; $i < $parents; $i++) {
            $parent = User::create([
                'school_id' => $this->school->id,
                'usergroup_id' => 7,
                'name' => $name.' Parent '.$i,
                'email' => strtolower(str_replace(' ', '.', $name)).'.p'.$i.'@test.sch.ug',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]);
            StudentParentLink::create([
                'school_id' => $this->school->id,
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'status' => 1,
            ]);
        }

        return $student;
    }
}
