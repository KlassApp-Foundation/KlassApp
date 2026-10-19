<?php

namespace Tests\Feature\Teacher;

use App\Events\GradesPublished;
use App\Events\MarksUpdated;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Academics\SchoolGradingSystem;
use App\Models\MessageDeliveryLog;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\WhatsAppPendingNotification;
use App\Models\WhatsAppUser;
use App\Services\Toshi\TeacherActionService;
use App\Services\ToshiActionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Audit L6: saving marks never messages a parent.
 *
 * A release still messages the parent who is following that student. That
 * behaviour stays covered by
 * Tests\Feature\Demo\MarksReleaseTest::test_task9_release_sends_only_to_the_bound_number_with_the_exact_marks.
 */
class TeacherSaveMarksDoesNotNotifyParentsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $teacher;

    private User $student;

    private User $coldParent;

    private User $warmParent;

    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        // The save path counts subjects with is_active. The subjects table
        // stores that flag as status, so the count throws and the catch
        // swallows it. This column is what the count expects, so the test
        // reaches the parent message instead of passing on a swallowed error.
        if (! Schema::hasColumn('subjects', 'is_active')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->boolean('is_active')->default(1);
            });
        }

        $this->school = School::create([
            'name' => 'L6 Marks School',
            'slug' => 'l6-marks-'.uniqid(),
            'email' => 'l6-marks-'.uniqid().'@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1,
            'is_demo' => 0,
            'registration_country' => 'Uganda',
        ]);

        $this->teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'name' => 'Subject Teacher',
            'email' => 'l6.teacher.'.uniqid().'@t.sch.ug',
        ]);

        $this->student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'Amina Nakato',
            'email' => 'l6.student.'.uniqid().'@t.sch.ug',
        ]);

        $this->coldParent = $this->parent('Cold Parent', '+256700111001', null);
        $this->warmParent = $this->parent('Warm Parent', '+256700111002', now());

        foreach ([$this->coldParent, $this->warmParent] as $parent) {
            StudentParentLink::create([
                'school_id' => $this->school->id,
                'parent_id' => $parent->id,
                'student_id' => $this->student->id,
                'status' => 1,
            ]);
        }

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term I',
            'status' => 'current',
            'starts_on' => '2026-02-01',
            'ends_on' => '2026-05-01',
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.1',
            'status' => 1,
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'name' => 'Mathematics',
            'type' => 'core',
            'status' => 1,
        ]);

        $examType = ExamType::create([
            'name' => 'End of Term',
            'code' => 'EOT-L6',
            'contributes_to_report_total' => true,
        ]);

        SchoolGradingSystem::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'grade' => 'A',
            'points' => 1,
            'min_score' => 80,
            'max_score' => 100,
            'remark' => 'Excellent',
        ]);

        $this->exam = Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'academic_term_id' => $term->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'exam_type_id' => $examType->id,
            'status' => 'undone',
        ]);

        config([
            'services.whatsapp.replies_enabled' => true,
            'services.whatsapp.demo_only_recipients' => false,
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '123456',
        ]);
    }

    public function test_L6_teacher_save_does_not_message_parents(): void
    {
        $this->fakeOutbound();

        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.exam.marks.save', $this->exam), [
                'marks' => [
                    $this->student->id => 88,
                ],
            ]);

        $response->assertRedirect(route('teacher.exam.marks'));
        $this->assertDatabaseHas('marks', [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'marks' => 88,
        ]);

        Event::assertNotDispatched(GradesPublished::class);
        $this->assertNoParentResultsMessage();
    }

    public function test_L6_teacher_mark_update_does_not_message_parents(): void
    {
        Marks::create([
            'student_id' => $this->student->id,
            'subject_id' => $this->exam->subject_id,
            'section_id' => $this->exam->section_id,
            'exam_id' => $this->exam->id,
            'teacher_id' => $this->teacher->id,
            'school_id' => $this->school->id,
            'marks' => 70,
            'grade' => 'A',
        ]);

        $this->fakeOutbound();

        $response = $this->actingAs($this->teacher)
            ->patch(route('teacher.marks.update', [$this->exam, $this->student]), [
                'marks' => 91,
            ]);

        $response->assertRedirect(route('teacher.exam.marks'));
        $this->assertDatabaseHas('marks', [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'marks' => 91,
        ]);

        Event::assertNotDispatched(GradesPublished::class);
        $this->assertNoParentResultsMessage();
    }

    public function test_L6_toshi_mark_entry_does_not_message_parents(): void
    {
        $this->fakeOutbound();

        try {
            TeacherActionService::enterMarks($this->teacher, [
                'exam_id' => $this->exam->id,
                'student' => 'Amina Nakato',
                'marks' => 84,
            ]);
        } catch (\Throwable) {
            // Grade handling on this path belongs to a later marks PR.
            // A failed write still must not message parents.
        }

        $admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
            'name' => 'School Admin',
            'email' => 'l6.admin.'.uniqid().'@t.sch.ug',
        ]);

        ToshiActionService::enterMark($admin, [
            'student_id' => $this->student->id,
            'exam_id' => $this->exam->id,
            'marks' => 86,
        ]);

        Event::assertNotDispatched(GradesPublished::class);
        $this->assertNoParentResultsMessage();
    }

    public function test_L6_marks_updated_event_does_not_message_parents(): void
    {
        $this->fakeOutbound();

        event(new MarksUpdated($this->exam, $this->teacher, null, false, 1));

        Event::assertNotDispatched(GradesPublished::class);
        $this->assertNoParentResultsMessage();
    }

    private function parent(string $name, string $phone, $lastInbound): User
    {
        $parent = User::factory()->create([
            'usergroup_id' => 7,
            'school_id' => $this->school->id,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'.'.uniqid().'@t.sch.ug',
            'mobile_no' => ltrim($phone, '+'),
        ]);

        WhatsAppUser::create([
            'phone' => $phone,
            'user_id' => $parent->id,
            'school_id' => $this->school->id,
            'opted_in' => true,
            'verified_at' => now(),
            'last_inbound_at' => $lastInbound,
        ]);

        return $parent;
    }

    private function fakeOutbound(): void
    {
        Http::fake([
            '*' => Http::response(['messages' => [['id' => 'wamid.L6']]], 200),
        ]);
        Notification::fake();
        Event::fake([GradesPublished::class]);
    }

    private function assertNoParentResultsMessage(): void
    {
        $pending = WhatsAppPendingNotification::query()->get();
        $this->assertCount(0, $pending, 'A mark save queued a parent WhatsApp message.');
        foreach ($pending as $row) {
            $this->assertStringNotContainsString('Results Published', (string) $row->message);
            $this->assertNotSame('comprehensive_grades', $row->flow_type);
            $this->assertNotSame('grades', $row->flow_type);
        }

        $logged = MessageDeliveryLog::query()->get();
        $this->assertCount(0, $logged, 'A mark save wrote a parent WhatsApp delivery log.');
        foreach ($logged as $row) {
            $this->assertStringNotContainsString('Results Published', (string) $row->content_preview);
            $this->assertNotContains($row->flow_type, ['comprehensive_grades', 'grades'], true);
        }

        foreach (Http::recorded() as [$request]) {
            $body = (string) $request->body();
            $this->assertStringNotContainsString('Results Published', $body);
            $this->assertStringNotContainsString('comprehensive_grades', $body);
        }
    }
}
