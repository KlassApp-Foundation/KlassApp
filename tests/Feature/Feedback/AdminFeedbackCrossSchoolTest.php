<?php

namespace Tests\Feature\Feedback;

use App\Events\Notification\SingleNotificationEvent;
use App\Events\SinglePushEvent;
use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\Feedback;
use App\Models\FeedbackMessage;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Admin feedback routes load a thread/message by id. Every one of them must
 * refuse (404) an id belonging to another school — crafted by an admin of
 * school A against school B's real ids — and must not read, write or notify.
 */
class AdminFeedbackCrossSchoolTest extends TestCase
{
    use RefreshDatabase;

    private User $adminA;
    private Feedback $feedbackA;
    private Feedback $feedbackB;
    private FeedbackMessage $messageA;
    private FeedbackMessage $messageB;

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
        Event::fake([SinglePushEvent::class, SingleNotificationEvent::class]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        foreach (['A', 'B'] as $letter) {
            $school = School::create([
                'name' => 'Feedback School '.$letter,
                'slug' => 'feedback-school-'.strtolower($letter),
                'email' => strtolower($letter).'@feedback.test',
                'phone' => '+25670000010'.($letter === 'A' ? 1 : 2),
                'status' => 1,
                'registration_country' => 'Uganda',
            ]);
            AcademicYear::create([
                'school_id' => $school->id,
                'name' => '2026 '.$letter,
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 1,
            ]);
            $admin = User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 3, 'email' => 'admin.'.strtolower($letter).'@feedback.test']);
            $parent = User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 7, 'email' => 'parent.'.strtolower($letter).'@feedback.test']);
            $student = User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 6, 'email' => 'student.'.strtolower($letter).'@feedback.test']);

            $feedback = Feedback::create([
                'school_id' => $school->id,
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'admin_id' => $admin->id,
                'status' => 1,
            ]);
            $message = FeedbackMessage::create([
                'school_id' => $school->id,
                'user_id' => $parent->id,
                'feedback_id' => $feedback->id,
                'message' => 'Confidential thread of school '.$letter,
                'category' => 'complaints',
                'is_seen' => 0,
            ]);

            $this->{'feedback'.$letter} = $feedback;
            $this->{'message'.$letter} = $message;
            if ($letter === 'A') {
                $this->adminA = $admin;
            }
        }
    }

    private function asAdminA(): static
    {
        return $this->actingAs($this->adminA)
            ->withoutMiddleware([MustBePrivilege::class, VerifyCsrfToken::class]);
    }

    public function test_own_school_thread_opens(): void
    {
        $this->asAdminA()
            ->get('/admin/feedback/edit/'.$this->feedbackA->id)
            ->assertOk()
            ->assertSee('Confidential thread of school A');
    }

    public function test_crafted_foreign_thread_id_is_refused_and_leaks_nothing(): void
    {
        $this->asAdminA()
            ->get('/admin/feedback/edit/'.$this->feedbackB->id)
            ->assertNotFound()
            ->assertDontSee('Confidential thread of school B');
    }

    public function test_crafted_reply_into_foreign_thread_is_refused_and_writes_nothing(): void
    {
        $this->asAdminA()
            ->post('/admin/feedback/edit/'.$this->feedbackB->id, ['message' => 'Injected reply', 'category' => 'complaints'])
            ->assertNotFound();

        $this->assertDatabaseMissing('feedback_messages', ['message' => 'Injected reply', 'category' => 'complaints']);
        $this->assertSame(1, FeedbackMessage::where('feedback_id', $this->feedbackB->id)->count());
        Event::assertNotDispatched(SinglePushEvent::class);
    }

    public function test_crafted_status_change_on_foreign_message_is_refused_and_writes_nothing(): void
    {
        $this->asAdminA()
            ->post('/admin/feedback/updateStatus/'.$this->messageB->id, ['status' => 'action_taken'])
            ->assertNotFound();

        $this->assertSame('0', (string) $this->messageB->fresh()->is_seen);
        Event::assertNotDispatched(SinglePushEvent::class);
        Event::assertNotDispatched(SingleNotificationEvent::class);
    }

    public function test_unknown_ids_are_refused_the_same_way(): void
    {
        $this->asAdminA()->get('/admin/feedback/edit/999999')->assertNotFound();
        $this->asAdminA()->post('/admin/feedback/updateStatus/999999', ['status' => 'has_seen'])->assertNotFound();
    }

    public function test_own_school_status_change_still_works(): void
    {
        $this->asAdminA()
            ->post('/admin/feedback/updateStatus/'.$this->messageA->id, ['status' => 'has_seen'])
            ->assertOk()
            ->assertJsonStructure(['success']);

        $this->assertSame('has_seen', $this->messageA->fresh()->is_seen);
        Event::assertDispatched(SinglePushEvent::class);
    }

    /**
     * Own-school replies pass the new school check (not a 404). The insert
     * itself still fails on main, before and after this change:
     * update() never sets the NOT NULL `category` column and the catch
     * swallows it (empty 200). Pre-existing, tracked separately.
     */
    public function test_own_school_reply_is_not_refused_by_the_school_check(): void
    {
        $this->asAdminA()
            ->post('/admin/feedback/edit/'.$this->feedbackA->id, ['message' => 'Thanks we are on it', 'category' => 'complaints'])
            ->assertOk();
    }
}
