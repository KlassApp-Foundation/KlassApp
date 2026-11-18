<?php

namespace Tests\Feature\Toshi\Marks;

use App\AiAgents\DeputyAdminOperationsAgent;
use App\AiAgents\Skills\ReportingSkill;
use App\AiAgents\TeacherOperationsAgent;
use App\AiAgents\Tools\Marks\LearnerReportStatusTool;
use App\AiAgents\Tools\Marks\MarksToolSupport;
use App\AiAgents\Tools\Marks\MissingMarksTool;
use App\AiAgents\Tools\Marks\PrepareClassTeacherReminderTool;
use App\AiAgents\WhatsApp\WhatsAppWriteExclusion;
use App\Contracts\Results\ReleaseGate;
use App\Contracts\Results\ReleasedSitting;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\MarksRelease;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Services\Results\CombinedSheetPage;
use App\Services\Results\ReportComposition;
use App\Services\Results\ResultsQuery;
use App\Services\Results\ResultsService;
use App\Services\StudentReportCardService;
use App\Services\ToshiActionService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Tools\Request as ToolRequest;
use Tests\TestCase;

class ToshiMarksReadToolsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $otherSchool;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Section $p4;

    private Section $p6;

    private StandardLink $p4Link;

    private User $admin;

    private User $head;

    private User $classTeacher;

    private User $otherClassTeacher;

    private User $subjectTeacher;

    private User $opio;

    private User $daphineAkello;

    private User $daphineBasemera;

    private User $daphineWest;

    private User $daphineOffshore;

    private Subject $english;

    private Subject $science;

    private ExamType $mid;

    private ExamType $end;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'headteacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = $this->makeSchool('Read Marks School', 'read-marks', 'read-marks@school.test', '+256700009901');
        $this->otherSchool = $this->makeSchool('Other Read Marks School', 'other-read-marks', 'other-read-marks@school.test', '+256700009902');
        $this->year = $this->makeYear($this->school);
        $otherYear = $this->makeYear($this->otherSchool);
        $this->term = $this->makeTerm($this->school, $this->year);
        $this->makeTerm($this->otherSchool, $otherYear);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);
        $otherStandard = Standard::create([
            'school_id' => $this->otherSchool->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $this->classTeacher = $this->makeUser($this->school, 5, 'Amina Kato', 'amina@read-marks.test');
        $this->otherClassTeacher = $this->makeUser($this->school, 5, 'Ben Otim', 'ben@read-marks.test');
        $this->subjectTeacher = $this->makeUser($this->school, 5, 'Winnie Lab', 'winnie@read-marks.test');
        $this->admin = $this->makeUser($this->school, 3, 'Ada Admin', 'ada.admin@read-marks.test');
        $this->head = $this->makeUser($this->school, 4, 'Hassan Head', 'hassan@read-marks.test');
        $otherAdmin = $this->makeUser($this->otherSchool, 3, 'Other Admin', 'other.admin@read-marks.test');

        $this->p4 = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.4',
            'status' => 1,
            'class_teacher_id' => $this->classTeacher->id,
        ]);
        $this->p6 = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.6',
            'status' => 1,
            'class_teacher_id' => $this->otherClassTeacher->id,
        ]);
        $otherSection = Section::create([
            'school_id' => $this->otherSchool->id,
            'name' => 'P.9',
            'status' => 1,
            'class_teacher_id' => $otherAdmin->id,
        ]);

        $this->p4Link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->p4->id,
            'stream' => 'East',
            'status' => 1,
            'class_teacher_id' => $this->classTeacher->id,
        ]);
        $p6Link = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->p6->id,
            'stream' => 'West',
            'status' => 1,
            'class_teacher_id' => $this->otherClassTeacher->id,
        ]);
        $otherLink = StandardLink::create([
            'school_id' => $this->otherSchool->id,
            'academic_year_id' => $otherYear->id,
            'standard_id' => $otherStandard->id,
            'section_id' => $otherSection->id,
            'stream' => 'North',
            'status' => 1,
        ]);

        $this->daphineAkello = $this->learner($this->school, 'Daphine Akello', $this->p4Link);
        $this->daphineBasemera = $this->learner($this->school, 'Daphine Basemera', $this->p4Link);
        $this->opio = $this->learner($this->school, 'Opio Okello', $this->p4Link);
        $this->daphineWest = $this->learner($this->school, 'Daphine West', $p6Link);
        $this->daphineOffshore = $this->learner($this->otherSchool, 'Daphine Offshore', $otherLink);

        $this->english = $this->subject($this->school, $standard, $this->p4, 'English', 'ENG');
        $this->science = $this->subject($this->school, $standard, $this->p4, 'Science', 'SCI');
        $astronomy = $this->subject($this->otherSchool, $otherStandard, $otherSection, 'Astronomy', 'AST');

        $this->mid = ExamType::create([
            'name' => 'Mid term',
            'code' => 'MID-READ',
            'contributes_to_report_total' => 1,
        ]);
        $this->end = ExamType::create([
            'name' => 'End of term',
            'code' => 'EOT-READ',
            'contributes_to_report_total' => 1,
        ]);

        $englishMid = $this->exam($this->school, $standard, $this->p4, $this->english, $this->mid, $this->subjectTeacher, '2026-10-08');
        $scienceMid = $this->exam($this->school, $standard, $this->p4, $this->science, $this->mid, $this->subjectTeacher, '2026-10-09');
        $englishEnd = $this->exam($this->school, $standard, $this->p4, $this->english, $this->end, $this->subjectTeacher, '2026-11-12');
        $this->exam($this->otherSchool, $otherStandard, $otherSection, $astronomy, $this->mid, $otherAdmin, '2026-10-08');

        $this->mark($englishMid, $this->english, $this->opio, 73);
        $this->mark($englishEnd, $this->english, $this->opio, 81);
        $this->submission($englishMid, $this->english, 'submitted', 'pending');
        $this->submission($scienceMid, $this->science, 'draft', 'pending');
        $this->submission($englishEnd, $this->english, 'submitted', 'approved');

        MarksRelease::create([
            'school_id' => $this->school->id,
            'exam_id' => $englishEnd->id,
            'scope' => 'class',
            'standard_link_id' => $this->p4Link->id,
            'scheduled_at' => now()->subHour(),
            'released_at' => now(),
            'status' => MarksRelease::STATUS_RELEASED,
            'timezone' => 'UTC',
            'created_by' => $this->admin->id,
        ]);

        ToshiActionService::$bypassConfirm = false;
    }

    protected function tearDown(): void
    {
        ToshiActionService::$bypassConfirm = false;
        parent::tearDown();
    }

    public function test_teacher_cannot_read_another_class(): void
    {
        $this->actingAs($this->otherClassTeacher);

        $result = (new MissingMarksTool)->handle(new ToolRequest([
            'class' => 'P.4',
            'exam_set' => 'Mid term',
        ]));

        $this->assertSame('❌ You can only open your own class.', $result);
        $this->assertStringNotContainsString('Winnie Lab', $result);
        $this->assertStringNotContainsString('Science', $result);
        $this->assertStringNotContainsString('73', $result);

        $own = json_decode((new MissingMarksTool)->handle(new ToolRequest([
            'class' => 'P.6',
            'exam_set' => 'Mid term',
        ])), true);
        $this->assertIsArray($own);
        $this->assertSame('P.6', $own['class']);
        $this->assertSame([], $own['subjects']);
    }

    public function test_user_cannot_read_another_school(): void
    {
        $this->actingAs($this->admin);

        $missing = (new MissingMarksTool)->handle(new ToolRequest([
            'class' => 'P.9',
            'exam_set' => 'Mid term',
        ]));
        $this->assertSame('❌ That class is not in your school.', $missing);
        $this->assertStringNotContainsString('Astronomy', $missing);
        $this->assertStringNotContainsString('Daphine Offshore', $missing);

        $status = (new LearnerReportStatusTool)->handle(new ToolRequest([
            'learner' => 'Daphine',
            'learner_id' => $this->daphineOffshore->id,
        ]));
        $this->assertSame('❌ That learner is not in your school.', $status);
        $this->assertStringNotContainsString('Astronomy', $status);
        $this->assertStringNotContainsString('Offshore', $status);

        $home = (new MissingMarksTool)->handle(new ToolRequest([
            'class' => 'P.4',
            'exam_set' => 'Mid term',
        ]));
        $this->assertStringNotContainsString('Astronomy', $home);
        $this->assertStringNotContainsString('Daphine Offshore', $home);
        $this->assertStringNotContainsString('73', $home);
        $this->assertStringNotContainsString('81', $home);
    }

    public function test_missing_marks_pass_through_the_overview(): void
    {
        $this->actingAs($this->classTeacher);

        $raw = (new MissingMarksTool)->handle(new ToolRequest([
            'class' => 'P.4',
            'exam_set' => 'Mid term',
        ]));
        $payload = json_decode($raw, true);
        $overview = $this->overview('P.4', $this->mid->id);

        $this->assertSame(array_values($overview['subjects']->all()), $payload['subjects']);
        $this->assertSame(array_values($overview['missingSubjects']->all()), $payload['missing_subjects']);
        $this->assertSame(
            array_values(array_filter(
                array_values($overview['subjects']->all()),
                fn (array $subject) => ($subject['submission'] ?? '') !== 'Approved',
            )),
            $payload['unapproved_subjects'],
        );
        $this->assertSame($this->learnersMissing($overview), $payload['learners_missing']);
        $this->assertSame($this->tabStatus($overview, $this->mid->id), $payload['set_status']);
        $this->assertNull($payload['teachers_not_submitted']);
        $this->assertSame(MarksToolSupport::TEACHERS_GAP, $payload['teachers_gap']);
        $this->assertStringNotContainsString('Winnie Lab', $raw);
        $this->assertStringNotContainsString('73', $raw);
        $this->assertStringNotContainsString('81', $raw);

        $keys = [];
        $this->collectKeys($payload, $keys);
        foreach (['mark', 'marks', 'total', 'average', 'aggregate', 'division', 'points', 'grade'] as $banned) {
            $this->assertNotContains($banned, $keys);
        }
        foreach ($payload['learners_missing'] as $row) {
            $this->assertSame('Missing', $row['printed']);
        }
    }

    public function test_head_teacher_reads_the_school_and_a_parent_cannot(): void
    {
        $this->actingAs($this->head);
        $payload = json_decode((new MissingMarksTool)->handle(new ToolRequest([
            'class' => 'P.4',
            'exam_set' => 'Mid term',
        ])), true);
        $this->assertSame(array_values($this->overview('P.4', $this->mid->id)['subjects']->all()), $payload['subjects']);

        $parent = $this->makeUser($this->school, 7, 'Parent Person', 'parent@read-marks.test');
        $this->actingAs($parent);
        $this->assertSame(
            '❌ You cannot open marks for this school.',
            (new MissingMarksTool)->handle(new ToolRequest([
                'class' => 'P.4',
                'exam_set' => 'Mid term',
            ])),
        );
    }

    public function test_ambiguous_learner_asks_which_one_in_scope(): void
    {
        $this->actingAs($this->admin);
        $asked = (new LearnerReportStatusTool)->handle(new ToolRequest([
            'learner' => 'Daphine',
        ]));
        $this->assertStringStartsWith('Which learner?', $asked);
        $this->assertStringContainsString('Daphine Akello', $asked);
        $this->assertStringContainsString('Daphine Basemera', $asked);
        $this->assertStringContainsString('Daphine West', $asked);
        $this->assertStringNotContainsString('Offshore', $asked);
        $this->assertStringNotContainsString('73', $asked);

        $this->actingAs($this->classTeacher);
        $classList = (new LearnerReportStatusTool)->handle(new ToolRequest([
            'learner' => 'Daphine',
        ]));
        $this->assertStringContainsString('Daphine Akello', $classList);
        $this->assertStringContainsString('Daphine Basemera', $classList);
        $this->assertStringNotContainsString('Daphine West', $classList);
        $this->assertStringNotContainsString('Offshore', $classList);

        $this->actingAs($this->classTeacher);
        $refused = (new LearnerReportStatusTool)->handle(new ToolRequest([
            'learner' => 'Daphine',
            'learner_id' => $this->daphineWest->id,
        ]));
        $this->assertSame('❌ You can only open a learner in your own class.', $refused);
        $this->assertStringNotContainsString('West', $refused);
    }

    public function test_report_status_passes_through_release_and_report_services(): void
    {
        $this->actingAs($this->admin);
        $raw = (new LearnerReportStatusTool)->handle(new ToolRequest([
            'learner' => 'Opio Okello',
        ]));
        $payload = json_decode($raw, true);

        $gate = app(ReleaseGate::class);
        $sheet = app(ResultsService::class)->sheet(new ResultsQuery(
            schoolId: (int) $this->school->id,
            sectionId: (int) $this->p4->id,
            academicTermId: (int) $this->term->id,
            standardLinkId: (int) $this->p4Link->id,
            studentId: (int) $this->opio->id,
        ));

        $this->assertSame('Opio Okello', $payload['learner']);
        $this->assertSame($gate->isTermReportReleased($this->opio, (int) $this->term->id), $payload['released']);
        $this->assertSame(
            app(StudentReportCardService::class)->examSetNames((int) $this->school->id, $this->p4Link),
            $payload['included_sets'],
        );
        $this->assertSame(
            app(ReportComposition::class)->blockingSets((int) $this->school->id, (int) $this->p4->id, (int) $this->term->id),
            $payload['not_released_yet'],
        );
        $this->assertSame($sheet->unreleasedWeightedSets, $payload['unreleased_weighted_sets']);
        $this->assertSame(array_map(fn (ReleasedSitting $sitting) => [
            'school_id' => $sitting->schoolId,
            'exam_type_id' => $sitting->examTypeId,
            'academic_term_id' => $sitting->academicTermId,
            'section_id' => $sitting->sectionId,
        ], $gate->releasedSittings($this->opio)), $payload['released_sittings']);

        $keys = [];
        $this->collectKeys($payload, $keys);
        foreach (['mark', 'marks', 'total', 'average', 'aggregate', 'division', 'points', 'grade'] as $banned) {
            $this->assertNotContains($banned, $keys);
        }
        $this->assertStringNotContainsString('73', $raw);
        $this->assertStringNotContainsString('81', $raw);
        $this->assertTrue($payload['released']);
    }

    public function test_reminder_sends_nothing_before_or_after_confirmation(): void
    {
        Http::preventStrayRequests();
        Queue::fake();
        $whatsapp = $this->mock(WhatsAppBusinessService::class);
        $whatsapp->shouldNotReceive('sendText');
        $whatsapp->shouldNotReceive('sendTemplate');
        $whatsapp->shouldNotReceive('sendToUser');
        $whatsapp->shouldNotReceive('sendDocument');

        $this->actingAs($this->admin);
        ToshiActionService::$bypassConfirm = false;
        $pending = (new PrepareClassTeacherReminderTool)->handle(new ToolRequest([
            'class' => 'P.4',
            'exam_set' => 'Mid term',
        ]));
        $card = json_decode($pending, true);

        $this->assertTrue($card['__tier2_confirm']);
        $this->assertSame(PrepareClassTeacherReminderTool::CONFIRM_KEY, $card['tool']);
        $this->assertStringContainsString('Draft reminder for Amina Kato', $card['preview']);
        $overview = $this->overview('P.4', $this->mid->id);
        foreach ($overview['subjects'] as $subject) {
            $this->assertStringContainsString($subject['name'].': '.$subject['submission'], $card['preview']);
        }
        $this->assertStringContainsString('Nothing is sent from this draft.', $card['preview']);
        $this->assertStringNotContainsString('73', $card['preview']);
        $this->assertStringNotContainsString('Winnie Lab', $card['preview']);

        ToshiActionService::$bypassConfirm = true;
        $confirmed = (new PrepareClassTeacherReminderTool)->handle(new ToolRequest([
            'class' => 'P.4',
            'exam_set' => 'Mid term',
        ]));
        $this->assertStringContainsString('Nothing was sent.', $confirmed);
        $this->assertStringContainsString('no confirmation-gated send path', $confirmed);
        foreach ($overview['subjects'] as $subject) {
            $this->assertStringContainsString($subject['name'].': '.$subject['submission'], $confirmed);
        }
        $this->assertStringNotContainsString('73', $confirmed);
        Queue::assertNothingPushed();
        $this->assertSame(1, MarksRelease::query()->count());

        $this->actingAs($this->otherClassTeacher);
        ToshiActionService::$bypassConfirm = false;
        $blocked = (new PrepareClassTeacherReminderTool)->handle(new ToolRequest([
            'class' => 'P.4',
            'exam_set' => 'Mid term',
        ]));
        $this->assertSame('❌ You can only open your own class.', $blocked);
    }

    public function test_tools_are_registered_on_the_existing_agents(): void
    {
        $teacher = collect((new TeacherOperationsAgent)->tools())->map(fn ($tool) => $tool::class)->all();
        $head = collect((new DeputyAdminOperationsAgent)->tools())->map(fn ($tool) => $tool::class)->all();
        $reports = collect((new ReportingSkill)->tools())->map(fn ($tool) => $tool::class)->all();

        foreach ([$teacher, $head, $reports] as $classes) {
            $this->assertContains(MissingMarksTool::class, $classes);
            $this->assertContains(LearnerReportStatusTool::class, $classes);
            $this->assertContains(PrepareClassTeacherReminderTool::class, $classes);
        }

        $this->assertFalse(WhatsAppWriteExclusion::allowsClass(PrepareClassTeacherReminderTool::class));
        $this->assertNotContains(
            PrepareClassTeacherReminderTool::class,
            WhatsAppWriteExclusion::WRITE_ALLOWLIST,
        );

        $map = (new \ReflectionClass(\App\Livewire\AgentToshi::class))->getConstant('TOOL_CLASS_MAP');
        $this->assertSame(
            PrepareClassTeacherReminderTool::class,
            $map[PrepareClassTeacherReminderTool::CONFIRM_KEY] ?? null,
        );
    }

    private function overview(string $className, int $examTypeId): array
    {
        $section = Section::query()->where('school_id', $this->school->id)->where('name', $className)->firstOrFail();

        return app(CombinedSheetPage::class)->viewData((int) $this->school->id, Request::create('/admin/marks/filter', 'GET', [
            'class' => $section->id,
            'term' => $this->term->id,
            'year' => $this->year->id,
            'examType' => $examTypeId,
        ]));
    }

    /**
     * @param  array<string, mixed>  $overview
     * @return list<array{name: string, subject: string, printed: string}>
     */
    private function learnersMissing(array $overview): array
    {
        $names = [];
        foreach ($overview['subjects'] as $subject) {
            $names[(int) $subject['id']] = (string) $subject['name'];
        }
        $rows = [];
        foreach ($overview['sheet']->students as $student) {
            foreach ($student->cells as $subjectId => $cell) {
                $printed = $cell->sheetLabel();
                if ($printed !== 'Missing') {
                    continue;
                }
                $rows[] = [
                    'name' => $student->name,
                    'subject' => $names[(int) $subjectId] ?? '',
                    'printed' => $printed,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $overview
     * @return array<string, mixed>|null
     */
    private function tabStatus(array $overview, int $examTypeId): ?array
    {
        foreach ($overview['examSetTabs'] as $row) {
            if ((int) $row['type']->id !== $examTypeId) {
                continue;
            }

            return [
                'label' => $row['label'],
                'key' => $row['key'],
                'desktop' => $row['desktop'],
                'phone' => $row['phone'],
                'note' => $row['note'],
                'locked' => $row['locked'],
            ];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $keys
     */
    private function collectKeys(mixed $value, array &$keys): void
    {
        if (! is_array($value)) {
            return;
        }
        foreach ($value as $key => $child) {
            if (is_string($key)) {
                $keys[] = $key;
            }
            $this->collectKeys($child, $keys);
        }
    }

    private function makeSchool(string $name, string $slug, string $email, string $phone): School
    {
        return School::create([
            'name' => $name,
            'slug' => $slug,
            'email' => $email,
            'phone' => $phone,
            'status' => 1,
            'registration_country' => 'Uganda',
            'toshi_mode' => 'preview',
            'toshi_enabled' => 0,
        ]);
    }

    private function makeYear(School $school): AcademicYear
    {
        return AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current year',
            'start_date' => '2026-02-02',
            'end_date' => '2026-12-04',
            'status' => 1,
        ]);
    }

    private function makeTerm(School $school, AcademicYear $year): AcademicTerm
    {
        return AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 2',
            'starts_on' => '2026-05-25',
            'ends_on' => '2026-08-21',
            'status' => 'current',
        ]);
    }

    private function makeUser(School $school, int $group, string $name, string $email): User
    {
        return User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'name' => $name,
            'email' => $email,
            'status' => 'active',
        ]);
    }

    private function learner(School $school, string $name, StandardLink $link): User
    {
        $user = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@'.$school->slug.'.test',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $link->academic_year_id,
            'user_id' => $user->id,
            'standardLink_id' => $link->id,
        ]);

        return $user;
    }

    private function subject(School $school, Standard $standard, Section $section, string $name, string $code): Subject
    {
        return Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $standard->school_id === $school->id ? $this->yearFor($school) : null,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => $name,
            'code' => $code,
            'type' => 'core',
            'status' => 1,
        ]);
    }

    private function yearFor(School $school): int
    {
        return (int) AcademicYear::query()->where('school_id', $school->id)->value('id');
    }

    private function exam(School $school, Standard $standard, Section $section, Subject $subject, ExamType $type, User $teacher, string $when): Exam
    {
        $yearId = (int) AcademicYear::query()->where('school_id', $school->id)->value('id');
        $termId = (int) AcademicTerm::query()->where('school_id', $school->id)->value('id');

        return Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'exam_type_id' => $type->id,
            'academic_term_id' => $termId,
            'academic_year_id' => $yearId,
            'scheduled_at' => $when,
            'status' => 'undone',
        ]));
    }

    private function mark(Exam $exam, Subject $subject, User $student, int $score): void
    {
        Marks::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'school_id' => $exam->school_id,
            'subject_id' => $subject->id,
            'teacher_id' => $exam->teacher_id,
            'section_id' => $exam->section_id,
            'marks' => $score,
            'grade' => '',
            'absent' => false,
        ]);
    }

    private function submission(Exam $exam, Subject $subject, string $status, string $approval): void
    {
        ExamMarksSubmission::create([
            'school_id' => $exam->school_id,
            'exam_id' => $exam->id,
            'class_id' => $exam->section_id,
            'subject_id' => $subject->id,
            'teacher_id' => $exam->teacher_id,
            'status' => $status,
            'approval_status' => $approval,
        ]);
    }
}
