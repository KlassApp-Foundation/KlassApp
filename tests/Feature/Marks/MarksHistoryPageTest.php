<?php

namespace Tests\Feature\Marks;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\ActivityLog;
use App\Models\MarksRelease;
use App\Models\Records\RecordSnapshot;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\Marks\ExamMarkWriter;
use App\Services\Marks\ReleasedMarksLock;
use App\Services\Parent\ParentPortalService;
use App\Services\Records\SnapshotHasher;
use App\Services\Results\EloquentReleaseGate;
use App\Services\Toshi\StudentActionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The history page shows one class sitting from this school only, and a
 * reopen keeps the copy families already received.
 */
class MarksHistoryPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->withoutMiddleware(\App\Http\Middleware\MustBePrivilege::class);

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_the_history_page_uses_the_sitting_that_was_saved(): void
    {
        $ctx = $this->sitting();
        $html = $this->actingAs($ctx['admin'])
            ->get($this->url($ctx))
            ->assertOk()
            ->getContent();

        $when = $ctx['release']->released_at->timezone('Africa/Kampala');
        $this->assertStringContainsString('Form West · Mid Term', $html);
        $this->assertStringContainsString('Released and locked', $html);
        $this->assertStringContainsString('data-testid="released-pill"', $html);
        $this->assertStringContainsString('Term II, 2026 · 2 learners · released '.$when->format('j M Y').' at '.$when->format('H:i').' by Peter Okello (Admin)', $html);
        $this->assertStringContainsString('Record check passed.', $html);
        $this->assertStringContainsString('These marks match the copy saved at release.', $html);
        $this->assertStringContainsString($ctx['grouped'], $html);
        $this->assertStringNotContainsString($ctx['hash'], $html);
        $this->assertStringContainsString('Check again', $html);
        $this->assertStringContainsString('What happened to this class\'s marks', $html);
        $this->assertStringContainsString('Show earlier entries', $html);
        $this->assertStringContainsString('The total was added twice.', $html);
        $this->assertStringContainsString('Sent back · SCIENCE', $html);
        $this->assertStringContainsString('Approved · SCIENCE', $html);
        $this->assertStringContainsString('Resubmitted · SCIENCE', $html);
        $this->assertStringContainsString('Submitted · SCIENCE', $html);
        $this->assertStringContainsString('Entered · SCIENCE', $html);
        $this->assertStringContainsString('The class', $html);
        $this->assertStringContainsString('One learner', $html);
        $this->assertStringContainsString('Reopen Form West · Mid Term?', $html);
        $this->assertStringContainsString('The marks unlock so the class teacher can correct them.', $html);
        $this->assertStringContainsString('Families keep seeing the released results until you release again.', $html);
        $this->assertStringContainsString('Your reason is kept in this history. It isn\'t sent to families.', $html);
        $this->assertStringContainsString('Reopen Form West marks', $html);
        $this->assertStringContainsString('Admins and head teachers only', $html);
        $this->assertStringNotContainsString('Grace Achieng', $html);
        $this->assertStringNotContainsString('7F3A C91E 04B2', $html);
        $this->assertStringNotContainsString('103 learners', $html);
        $summary = strpos($html, 'Show earlier entries');
        $older = strpos($html, 'Entered · ART');
        $this->assertNotFalse($summary);
        $this->assertGreaterThan($summary, $older);

        $amina = $this->actingAs($ctx['admin'])
            ->get($this->url($ctx, ['learner' => $ctx['amina']->id]))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Amina Kato', $amina);
        $this->assertStringContainsString('KLS0010042', $amina);
        $this->assertStringContainsString('SCIENCE', $amina);
        $this->assertStringContainsString('>81<', $amina);
        $this->assertStringContainsString('out of 100', $amina);
        $this->assertStringContainsString('57 → 81', $amina);
        $this->assertStringContainsString('Tom Odeke · Teacher', $amina);

        $bosco = $this->actingAs($ctx['admin'])
            ->get($this->url($ctx, ['learner' => $ctx['bosco']->id]))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Bosco Adei', $bosco);
        $this->assertStringContainsString('KLS0010043', $bosco);
        $this->assertStringContainsString('>ABS<', $bosco);

        $filtered = $this->actingAs($ctx['admin'])
            ->get($this->url($ctx, ['subject' => $ctx['science']->id]))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('Entered · ART', $filtered);
        $this->assertStringContainsString('Entered · SCIENCE', $filtered);
        $this->assertStringContainsString('Released', $filtered);

        $this->actingAs($ctx['siteAdmin'])->get($this->url($ctx))->assertOk();
    }

    public function test_check_again_reports_a_hash_that_no_longer_matches(): void
    {
        $ctx = $this->sitting();
        $ctx['snapshot']->forceFill([
            'payload' => ['subjects' => [['subject_id' => $ctx['science']->id, 'mark' => 1, 'grade' => 'F']]],
        ])->save();

        $html = $this->followingRedirects()
            ->actingAs($ctx['admin'])
            ->post(route('admin.marks.history.check', $this->params($ctx)))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Record check failed.', $html);
        $this->assertStringContainsString('These marks do not match the copy saved at release.', $html);
        $this->assertStringContainsString($ctx['grouped'], $html);
        $this->assertStringNotContainsString('Record check passed.', $html);
    }

    public function test_a_teacher_is_sent_away_and_another_school_is_not_found(): void
    {
        $ctx = $this->sitting();

        $this->actingAs($ctx['teacher'])->get($this->url($ctx))->assertRedirect('/teacher/dashboard');
        $this->actingAs($ctx['otherHead'])->get($this->url($ctx))->assertNotFound();
        $this->actingAs($ctx['admin'])->get($this->url($ctx, [], $ctx['otherSection']->id))->assertNotFound();
        $this->actingAs($ctx['admin'])->get($this->url($ctx, [], null, $ctx['otherTerm']->id))->assertNotFound();
        $this->actingAs($ctx['admin'])->get($this->url($ctx, [], null, null, $ctx['foreignType']->id))->assertNotFound();
        $this->actingAs($ctx['admin'])->get($this->url($ctx, [], null, null, $ctx['sharedType']->id))->assertOk();
        $this->assertSame(MarksRelease::STATUS_RELEASED, $ctx['release']->fresh()->status);
    }

    public function test_reopen_needs_a_reason_and_only_this_schools_head_teacher(): void
    {
        $ctx = $this->sitting();
        $reason = 'The class needs one corrected mark.';

        $this->actingAs($ctx['admin'])
            ->from($this->url($ctx))
            ->post(route('admin.marks.history.reopen', $this->params($ctx)), ['reason' => ''])
            ->assertRedirect($this->url($ctx))
            ->assertSessionHasErrors('reason');
        $this->assertSame(MarksRelease::STATUS_RELEASED, $ctx['release']->fresh()->status);

        $this->actingAs($ctx['otherHead'])
            ->post(route('admin.marks.history.reopen', $this->params($ctx)), ['reason' => $reason])
            ->assertNotFound();
        $this->assertSame(MarksRelease::STATUS_RELEASED, $ctx['release']->fresh()->status);

        $this->actingAs($ctx['head'])
            ->post(route('admin.marks.history.reopen', $this->params($ctx)), ['reason' => $reason])
            ->assertRedirect();

        $this->assertSame(MarksRelease::STATUS_REOPENED, $ctx['release']->fresh()->status);
        $page = $this->actingAs($ctx['head'])->get($this->url($ctx))->assertOk()->getContent();
        $this->assertStringContainsString($reason, $page);
        $this->assertStringContainsString('Reopened', $page);
        $this->assertStringNotContainsString('Released and locked', $page);
    }

    public function test_a_parent_still_sees_the_released_mark_after_reopen(): void
    {
        $ctx = $this->familySitting();
        $parent = $ctx['parent'];
        $student = $ctx['amina'];
        $gate = app(EloquentReleaseGate::class);

        $this->assertTrue($gate->isReleased($student, $ctx['exam']));
        $this->assertSame(81.0, $this->familyScore($parent, $student));
        $this->assertStringContainsString('score 81', StudentActionService::viewMarks($student)['message']);

        app(ReleasedMarksLock::class)->reopen($ctx['head'], $ctx['release'], 'The class needs one corrected mark.');
        app(ExamMarkWriter::class)->save($ctx['teacher'], $ctx['exam'], $student->id, 19);

        $this->assertSame(19.0, (float) Marks::query()->where('student_id', $student->id)->value('marks'));
        $this->assertTrue($gate->isReleased($student, $ctx['exam']->fresh()));
        $this->assertSame(81.0, $this->familyScore($parent, $student));
        $this->assertStringContainsString('score 81', StudentActionService::viewMarks($student)['message']);
        $this->assertStringNotContainsString('score 19', StudentActionService::viewMarks($student)['message']);

        $html = $this->actingAs($parent)->get(route('parent.children.grades', $student))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/scope="row">SCIENCE<\/th>\s*<td>81<\/td>/', $html);
        $this->assertDoesNotMatchRegularExpression('/scope="row">SCIENCE<\/th>\s*<td>19<\/td>/', $html);

        $studentPage = $this->actingAs($student)->get(route('student.marks'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/scope="row">SCIENCE<\/th>\s*<td>81<\/td>/', $studentPage);

        $staff = $this->actingAs($ctx['admin'])
            ->get($this->url($ctx, ['learner' => $student->id]))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('>19<', $staff);

        MarksRelease::create([
            'school_id' => $ctx['school']->id,
            'exam_id' => $ctx['exam']->id,
            'scope' => 'class',
            'standard_link_id' => $ctx['link']->id,
            'scheduled_at' => CarbonImmutable::parse('2026-11-02 10:00:00', 'UTC'),
            'released_at' => CarbonImmutable::parse('2026-11-02 10:00:00', 'UTC'),
            'status' => MarksRelease::STATUS_RELEASED,
            'created_by' => $ctx['admin']->id,
            'timezone' => 'Africa/Kampala',
        ]);

        $this->assertSame(19.0, $this->familyScore($parent, $student));
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @param  array<string, int>  $query
     */
    private function url(array $ctx, array $query = [], ?int $section = null, ?int $term = null, ?int $examType = null): string
    {
        return route('admin.marks.history', $query + [
            'section' => $section ?? $ctx['section']->id,
            'term' => $term ?? $ctx['term']->id,
            'examType' => $examType ?? $ctx['type']->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @return array<string, int>
     */
    private function params(array $ctx): array
    {
        return [
            'section' => $ctx['section']->id,
            'term' => $ctx['term']->id,
            'examType' => $ctx['type']->id,
        ];
    }

    private function familyScore(User $parent, User $student): float
    {
        $grades = app(ParentPortalService::class)->grades($parent, null, $student->id);
        $this->assertTrue($grades['success'] ?? false, json_encode($grades));

        return (float) $grades['data']['exam_groups'][0]['subjects'][0]['score'];
    }

    /**
     * @return array<string, mixed>
     */
    private function familySitting(): array
    {
        $ctx = $this->base();
        $ctx['link'] = StandardLink::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'standard_id' => $ctx['standard']->id,
            'section_id' => $ctx['section']->id,
            'class_teacher_id' => $ctx['teacher']->id,
            'status' => 1,
        ]);
        $ctx['amina'] = $this->person($ctx['school'], 6, 'Amina Kato', 'KLS0010042');
        $this->profile($ctx['amina'], 6, 'Amina', 'Kato');
        StudentAcademic::create([
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['year']->id,
            'user_id' => $ctx['amina']->id,
            'standardLink_id' => $ctx['link']->id,
        ]);
        $ctx['parent'] = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 7,
            'name' => 'Mara Kato',
            'email' => 'mara.kato@example.test',
            'status' => 'active',
        ]);
        $this->profile($ctx['parent'], 7, 'Mara', 'Kato');
        StudentParentLink::create([
            'school_id' => $ctx['school']->id,
            'parent_id' => $ctx['parent']->id,
            'student_id' => $ctx['amina']->id,
            'status' => 1,
        ]);
        $ctx['exam'] = $this->exam($ctx, $ctx['science']);
        app(ExamMarkWriter::class)->save($ctx['teacher'], $ctx['exam'], $ctx['amina']->id, 81);
        $ctx['release'] = $this->release($ctx, $ctx['exam'], $ctx['link']->id);
        $payload = [
            'subjects' => [
                ['subject_id' => $ctx['science']->id, 'mark' => 81.0, 'grade' => 'D2'],
            ],
        ];
        $ctx['snapshot'] = $this->snapshot($ctx, $ctx['amina'], $ctx['release'], $payload);

        return $ctx;
    }

    /**
     * @return array<string, mixed>
     */
    private function sitting(): array
    {
        $ctx = $this->base();
        $ctx['amina'] = $this->person($ctx['school'], 6, 'Amina Kato', 'KLS0010042');
        $ctx['bosco'] = $this->person($ctx['school'], 6, 'Bosco Adei', 'KLS0010043');
        $ctx['exam'] = $this->exam($ctx, $ctx['science']);
        $artExam = $this->exam($ctx, $ctx['art']);
        $this->mark($ctx, $ctx['amina'], $ctx['exam'], $ctx['science'], 81, 'D2', false);
        $this->mark($ctx, $ctx['bosco'], $ctx['exam'], $ctx['science'], null, 'AB', true);
        $ctx['release'] = $this->release($ctx, $ctx['exam'], null);
        $payload = [
            'subjects' => [
                ['subject_id' => $ctx['science']->id, 'mark' => 81.0, 'grade' => 'D2'],
            ],
        ];
        $ctx['hash'] = SnapshotHasher::hash($payload);
        $ctx['grouped'] = SnapshotHasher::grouped($ctx['hash']);
        $ctx['snapshot'] = $this->snapshot($ctx, $ctx['amina'], $ctx['release'], $payload);
        $this->snapshot($ctx, $ctx['bosco'], $ctx['release'], [
            'subjects' => [
                ['subject_id' => $ctx['science']->id, 'mark' => 0.0, 'grade' => 'AB'],
            ],
        ]);

        $this->log($ctx, 'marks.entered', $artExam, $ctx['art'], $ctx['amina'], '2026-10-01 08:00:00', null, '40');
        $this->log($ctx, 'marks.entered', $ctx['exam'], $ctx['science'], $ctx['amina'], '2026-10-20 09:00:00', null, '57');
        $this->log($ctx, 'marks.submission.submitted', $ctx['exam'], $ctx['science'], null, '2026-10-21 09:00:00');
        $this->log($ctx, 'marks.submission.returned', $ctx['exam'], $ctx['science'], null, '2026-10-22 09:00:00', null, null, 'The total was added twice.');
        $this->log($ctx, 'marks.submission.submitted', $ctx['exam'], $ctx['science'], null, '2026-10-23 09:00:00', 'reopened');
        $this->log($ctx, 'marks.submission.approved', $ctx['exam'], $ctx['science'], null, '2026-10-24 09:00:00');
        $this->log($ctx, 'marks.changed', $ctx['exam'], $ctx['science'], $ctx['amina'], '2026-10-25 10:00:00', '57', '81');

        return $ctx;
    }

    /**
     * @return array<string, mixed>
     */
    private function base(): array
    {
        $school = $this->school('Nile Harbour School');
        $other = $this->school('Kidepo Hill School');
        $standard = Standard::create([
            'school_id' => $school->id, 'name' => 'Lower', 'order' => 1, 'status' => 1, 'grading_style' => 'aggregate',
        ]);
        $year = $this->year($school, '2026');
        $term = $this->term($school, $year, 'Term II');
        $otherYear = $this->year($other, '2026');
        $section = Section::create(['school_id' => $school->id, 'name' => 'Form West', 'status' => 1]);
        $type = ExamType::create([
            'school_id' => $school->id, 'name' => 'Mid Term', 'code' => 'MID-'.uniqid(), 'contributes_to_report_total' => 1,
        ]);

        return [
            'school' => $school,
            'other' => $other,
            'standard' => $standard,
            'year' => $year,
            'term' => $term,
            'section' => $section,
            'type' => $type,
            'science' => $this->subject($school, $year, $standard, $section, 'Science', 'SCI'),
            'art' => $this->subject($school, $year, $standard, $section, 'Art', 'ART'),
            'admin' => $this->person($school, 3, 'Peter Okello'),
            'head' => $this->person($school, 4, 'Helen Adong'),
            'teacher' => $this->person($school, 5, 'Tom Odeke'),
            'siteAdmin' => $this->person($school, 1, 'Site Keeper'),
            'otherHead' => $this->person($other, 4, 'Other Head'),
            'otherSection' => Section::create(['school_id' => $other->id, 'name' => 'Form East', 'status' => 1]),
            'otherTerm' => $this->term($other, $otherYear, 'Term II'),
            'foreignType' => ExamType::create([
                'school_id' => $other->id, 'name' => 'Foreign Set', 'code' => 'FOR-'.uniqid(), 'contributes_to_report_total' => 0,
            ]),
            'sharedType' => ExamType::create([
                'school_id' => null, 'name' => 'Shared Set', 'code' => 'SHR-'.uniqid(), 'contributes_to_report_total' => 0,
            ]),
        ];
    }

    private function school(string $name): School
    {
        return School::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'-'.uniqid().'@example.test',
            'phone' => '07'.random_int(10000000, 99999999),
            'slug' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid(),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
    }

    private function person(School $school, int $group, string $name, ?string $registration = null): User
    {
        return User::factory()->create([
            'name' => $name,
            'usergroup_id' => $group,
            'school_id' => $school->id,
            'email' => strtolower(str_replace(' ', '.', $name)).'.'.uniqid().'@example.test',
            'status' => 'active',
            'registration_number' => $registration,
        ]);
    }

    private function profile(User $user, int $group, string $first, string $last): void
    {
        Userprofile::create([
            'user_id' => $user->id,
            'usergroup_id' => $group,
            'school_id' => $user->school_id,
            'firstname' => $first,
            'lastname' => $last,
            'status' => 'active',
        ]);
    }

    private function year(School $school, string $name): AcademicYear
    {
        return AcademicYear::create([
            'school_id' => $school->id,
            'name' => $name,
            'description' => 'Year '.$name,
            'start_date' => $name.'-01-06',
            'end_date' => $name.'-12-15',
            'status' => 1,
        ]);
    }

    private function term(School $school, AcademicYear $year, string $name): AcademicTerm
    {
        return AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => $name,
            'status' => 'current',
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-08-15',
        ]);
    }

    private function subject(School $school, AcademicYear $year, Standard $standard, Section $section, string $name, string $code): Subject
    {
        return Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => $name,
            'code' => $code.'-'.uniqid(),
            'type' => 'core',
            'status' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $ctx
     */
    private function exam(array $ctx, Subject $subject): Exam
    {
        return Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $ctx['school']->id,
            'standard_id' => $ctx['standard']->id,
            'section_id' => $ctx['section']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'exam_type_id' => $ctx['type']->id,
            'academic_term_id' => $ctx['term']->id,
            'academic_year_id' => $ctx['year']->id,
            'status' => 'undone',
        ]));
    }

    /**
     * @param  array<string, mixed>  $ctx
     */
    private function mark(array $ctx, User $student, Exam $exam, Subject $subject, mixed $score, string $grade, bool $absent): void
    {
        Marks::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'school_id' => $ctx['school']->id,
            'subject_id' => $subject->id,
            'teacher_id' => $ctx['teacher']->id,
            'section_id' => $ctx['section']->id,
            'marks' => $score,
            'grade' => $grade,
            'absent' => $absent,
        ]);
    }

    /**
     * @param  array<string, mixed>  $ctx
     */
    private function release(array $ctx, Exam $exam, ?int $linkId): MarksRelease
    {
        return MarksRelease::create([
            'school_id' => $ctx['school']->id,
            'exam_id' => $exam->id,
            'scope' => 'class',
            'standard_link_id' => $linkId,
            'scheduled_at' => CarbonImmutable::parse('2026-10-28 13:00:00', 'UTC'),
            'released_at' => CarbonImmutable::parse('2026-10-28 13:12:00', 'UTC'),
            'status' => MarksRelease::STATUS_RELEASED,
            'created_by' => $ctx['admin']->id,
            'timezone' => 'Africa/Kampala',
        ]);
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @param  array<string, mixed>  $payload
     */
    private function snapshot(array $ctx, User $student, MarksRelease $release, array $payload): RecordSnapshot
    {
        return RecordSnapshot::create([
            'school_id' => $ctx['school']->id,
            'student_id' => $student->id,
            'academic_year_id' => $ctx['year']->id,
            'academic_term_id' => $ctx['term']->id,
            'version' => 1,
            'payload' => $payload,
            'content_hash' => SnapshotHasher::hash($payload),
            'released_at' => $release->released_at,
            'released_by' => $ctx['admin']->id,
            'release_id' => $release->id,
            'state' => RecordSnapshot::STATE_CURRENT,
        ]);
    }

    /**
     * @param  array<string, mixed>  $ctx
     */
    private function log(
        array $ctx,
        string $description,
        Exam $exam,
        Subject $subject,
        ?User $student,
        string $at,
        ?string $old = null,
        ?string $new = null,
        ?string $reason = null,
    ): void {
        $row = ActivityLog::create([
            'log_name' => 'marks',
            'description' => $description,
            'causer_id' => $ctx['teacher']->id,
            'causer_type' => User::class,
            'properties' => [
                'exam_id' => $exam->id,
                'student_id' => $student?->id,
                'subject_id' => $subject->id,
                'section_id' => $ctx['section']->id,
                'academic_term_id' => $ctx['term']->id,
                'old_value' => $old,
                'new_value' => $new,
                'reason' => $reason,
                'school_id' => $ctx['school']->id,
                'actor_id' => $ctx['teacher']->id,
            ],
            'school_id' => $ctx['school']->id,
        ]);
        $row->forceFill([
            'created_at' => CarbonImmutable::parse($at, 'UTC'),
            'updated_at' => CarbonImmutable::parse($at, 'UTC'),
        ])->save();
    }
}
