<?php

namespace Tests\Feature\Exams;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\Academics\ExamSetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The class is the unit: "Primary One" is a class, "Primary One E" is a stream.
 * Whole school / class picks create exams on the class itself; a stream gets its
 * own exam only when it is picked explicitly.
 */
class ExamSetServiceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private AcademicTerm $term;

    private Section $base;

    private Section $streamE;

    private Section $baby;

    private User $admin;

    private User $mathTeacher;

    private User $englishTeacher;

    private ExamType $eot;

    private ExamType $bot;

    private ExamType $otherSchoolType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Riverside Test', 'slug' => 'riverside-test', 'email' => 'rs@test.ug',
            'phone' => '0700000001', 'status' => 1,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026', 'description' => 'Current',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);
        AcademicYear::where('school_id', $this->school->id)->where('id', '!=', $this->year->id)->update(['status' => 0]);

        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'name' => 'Term 3', 'status' => 'current', 'starts_on' => '2026-09-01', 'ends_on' => '2026-12-01',
        ]);

        $primary = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 2, 'status' => 1]);
        $this->base = Section::create(['school_id' => $this->school->id, 'name' => 'Primary One', 'status' => 1]);
        $this->streamE = Section::create(['school_id' => $this->school->id, 'name' => 'Primary One E', 'status' => 1]);
        $this->baby = Section::create(['school_id' => $this->school->id, 'name' => 'Baby Class', 'status' => 1]);
        Section::create(['school_id' => $this->school->id, 'name' => 'Primary One W', 'status' => 1]);

        $this->admin = $this->makeUser(3, 'Admin');
        $this->mathTeacher = $this->makeUser(5, 'Maths Teacher');
        $this->englishTeacher = $this->makeUser(5, 'English Teacher');

        $math = $this->makeSubject($primary, $this->base, 'Mathematics');
        $english = $this->makeSubject($primary, $this->base, 'English');
        $this->makeSubject($primary, $this->streamE, 'Mathematics');
        $this->makeSubject($primary, $this->baby, 'Play');

        $this->link($primary, $this->base, $math, $this->mathTeacher);
        $this->link($primary, $this->base, $english, $this->englishTeacher);
        $this->link($primary, $this->streamE, $this->makeSubject($primary, $this->streamE, 'Science'), $this->mathTeacher);

        $this->eot = ExamType::create(['name' => 'End of Term Examination', 'code' => 'EOT', 'contributes_to_report_total' => true]);
        $this->bot = ExamType::create(['name' => 'Beginning of Term Examination', 'code' => 'BOT', 'contributes_to_report_total' => false]);

        $other = School::create(['name' => 'Other', 'slug' => 'other-'.uniqid(), 'email' => uniqid().'@t.ug', 'phone' => '0700000002', 'status' => 1]);
        $this->otherSchoolType = ExamType::create([
            'school_id' => $other->id, 'name' => 'Other Special', 'code' => 'OSS', 'contributes_to_report_total' => false,
        ]);
    }

    public function test_whole_school_creates_exams_on_base_classes_only(): void
    {
        $result = app(ExamSetService::class)->create($this->school, $this->term, [$this->eot->id], null, $this->admin);

        // Base "Primary One" has 2 subjects, "Baby Class" has 1. Streams are not included.
        $this->assertSame(3, $result['created']);
        $this->assertSame(0, Exam::where('school_id', $this->school->id)->where('section_id', $this->streamE->id)->count());
        $this->assertSame(2, Exam::where('school_id', $this->school->id)->where('section_id', $this->base->id)->count());
    }

    public function test_an_explicit_stream_pick_creates_that_streams_exam(): void
    {
        $result = app(ExamSetService::class)->create(
            $this->school, $this->term, [$this->eot->id], [$this->base->id, $this->streamE->id], $this->admin
        );

        $this->assertSame(4, $result['created'], 'base 2 + stream 2');
        $this->assertSame(2, Exam::where('school_id', $this->school->id)->where('section_id', $this->streamE->id)->count());
    }

    public function test_rerunning_creates_nothing_new(): void
    {
        $service = app(ExamSetService::class);
        $first = $service->create($this->school, $this->term, [$this->eot->id, $this->bot->id], null, $this->admin);
        $this->assertSame(6, $first['created'], '3 base subject rows x 2 types');

        $second = $service->create($this->school, $this->term, [$this->eot->id, $this->bot->id], null, $this->admin);
        $this->assertSame(0, $second['created']);
        $this->assertSame(6, $second['skipped']);
        $this->assertSame(6, Exam::where('school_id', $this->school->id)->count());
    }

    public function test_school_owned_types_stay_with_their_school(): void
    {
        $mine = ExamType::visibleToSchool($this->school->id)->pluck('id')->all();

        $this->assertContains($this->eot->id, $mine);
        $this->assertNotContains($this->otherSchoolType->id, $mine);
    }

    public function test_teacher_sees_only_their_own_exams(): void
    {
        app(ExamSetService::class)->create($this->school, $this->term, [$this->eot->id], null, $this->admin);

        $this->actingAs($this->mathTeacher)
            ->get('/teacher/exam/marks')
            ->assertOk()
            ->assertSee('MATHEMATICS')
            ->assertDontSee('ENGLISH');
    }

    public function test_admin_can_open_the_form_with_the_current_term_and_whole_school_default(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/exams/sets/create')
            ->assertOk()
            ->assertSee("Create this term's exam set", false)
            ->assertSee('Primary One', false)
            ->assertSee('checked data-testid="exam-set-whole-school', false);
    }

    public function test_classes_are_chipped_in_level_order_with_streams_behind_details(): void
    {
        // Baby Class sits in the nursery level (order 1), Primary One in primary (order 2).
        $nursery = Standard::create(['school_id' => $this->school->id, 'name' => 'nursery', 'order' => 1, 'status' => 1]);
        DB::table('standards_link')->insert([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $nursery->id, 'section_id' => $this->baby->id,
            'status' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $html = $this->actingAs($this->admin)
            ->get('/admin/exams/sets/create')
            ->assertOk()
            ->getContent();

        // One <details> chip per class; the old flat stream list is gone.
        $this->assertSame(2, substr_count($html, 'class="exam-set-class"'), 'Primary One + Baby Class');
        $this->assertStringNotContainsString('<select name="standard_ids[]"', $html);

        $nurseryAt = strpos($html, '>Nursery<');
        $primaryAt = strpos($html, '>Primary<');
        $this->assertNotFalse($nurseryAt);
        $this->assertNotFalse($primaryAt);
        $this->assertLessThan($primaryAt, $nurseryAt, 'levels render in standards.order');
        $this->assertLessThan(strpos($html, 'Primary One'), strpos($html, 'Baby Class'));

        // Primary One's streams live inside its own disclosure, closed by default.
        $this->assertMatchesRegularExpression(
            '/<details[^>]*data-testid="exam-set-class-'.$this->base->id.'"[^>]*>.*?data-testid="exam-set-streams-'.$this->base->id.'".*?<span>E<\/span>.*?<span>W<\/span>.*?<\/details>/s',
            $html
        );
        $this->assertStringNotContainsString('data-testid="exam-set-class-'.$this->base->id.'" open', $html);
    }

    public function test_a_class_teacher_only_sees_and_picks_streams_of_their_own_class(): void
    {
        $this->base->forceFill(['class_teacher_id' => $this->mathTeacher->id])->save();
        $babyStream = Section::create(['school_id' => $this->school->id, 'name' => 'Baby Class E', 'status' => 1]);

        $this->actingAs($this->mathTeacher)
            ->get('/teacher/exam/sets/create')
            ->assertOk()
            ->assertSee('2 streams')
            ->assertSee('data-testid="exam-set-stream-pick-'.$this->streamE->id.'"', false)
            ->assertDontSee('Baby Class E');

        $this->actingAs($this->mathTeacher)
            ->post('/teacher/exam/sets', [
                'academic_term_id' => $this->term->id,
                'type_ids' => [$this->eot->id],
                'standard_ids' => [$this->base->id],
                'stream_ids' => [$this->streamE->id, $babyStream->id],
            ])
            ->assertRedirect(route('teacher.exam-sets.show', ['batch' => \App\Models\Academics\ExamSet::query()->where('school_id', $this->school->id)->value('id')]));

        $exams = fn (Section $section) => Exam::where('school_id', $this->school->id)->where('section_id', $section->id)->count();
        $this->assertSame(2, $exams($this->base), 'their class: 2 subjects');
        $this->assertSame(2, $exams($this->streamE), 'their class stream: 2 subjects');
        $this->assertSame(0, $exams($babyStream), 'another class stream is refused');
    }

    private function makeUser(int $group, string $name): User
    {
        $user = User::create([
            'school_id' => $this->school->id, 'usergroup_id' => $group, 'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'-'.uniqid().'@test.ug',
            'password' => bcrypt('secret'), 'status' => 'active',
        ]);
        Userprofile::create([
            'school_id' => $this->school->id, 'user_id' => $user->id, 'usergroup_id' => $group,
            'firstname' => $name, 'lastname' => 'User',
        ]);

        return $user;
    }

    private function makeSubject(Standard $standard, Section $section, string $name): Subject
    {
        return Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id, 'section_id' => $section->id,
            'name' => $name, 'code' => substr($name, 0, 3), 'type' => 'core', 'status' => 1,
        ]);
    }

    private function link(Standard $standard, Section $section, Subject $subject, User $teacher): void
    {
        $linkId = (int) DB::table('standards_link')->insertGetId([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id, 'section_id' => $section->id, 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Teacherlink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standardLink_id' => $linkId, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
        ]);
    }
}
