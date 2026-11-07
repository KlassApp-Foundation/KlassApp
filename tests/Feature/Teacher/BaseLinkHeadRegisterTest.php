<?php

namespace Tests\Feature\Teacher;

use App\Helpers\SiteHelper;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\School;
use App\Models\SchoolDetail;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Reported class shape, anonymized.
 *
 * The class head is class teacher of the base standards_link and of one stream
 * section. The base section's own class_teacher_id is empty. standards_link.stream
 * is null. There is no attendance_scope row and no class_teacher_links row.
 * Students sit on the stream links (60 and 58), never on the base link.
 *
 * Provenance: user-supplied local-agent read-only findings. Factory ids differ.
 * The relationships match. This is not a live database.
 */
class BaseLinkHeadRegisterTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private AcademicYear $otherYear;

    private Standard $standard;

    private Section $base;

    private Section $eastSection;

    private Section $westSection;

    private StandardLink $baseLink;

    private StandardLink $eastLink;

    private StandardLink $westLink;

    private User $head;

    private User $westTeacher;

    private User $admin;

    /** @var list<int> */
    private array $eastIds = [];

    /** @var list<int> */
    private array $westIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Synthetic Class School',
            'slug' => 'shape-'.uniqid(),
            'email' => 'shape-'.uniqid().'@t.sch.ug',
            'phone' => '+256707'.random_int(100000, 999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'description' => 'AY',
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'status' => 1,
        ]);
        $this->otherYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025',
            'description' => 'Previous',
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->subMonths(3)->toDateString(),
            'status' => 0,
        ]);
        Cache::forget('academic_year_for_school_'.$this->school->id);

        $this->standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary', 'order' => 4, 'status' => 1,
        ]);

        $this->head = $this->user(5, 'Class Head');
        $this->westTeacher = $this->user(5, 'West Teacher');
        $this->admin = $this->user(3, 'School Admin');

        $this->base = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary Four',
            'status' => 1,
            'class_teacher_id' => null,
        ]);
        $this->eastSection = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary Four E',
            'status' => 1,
            'class_teacher_id' => $this->head->id,
        ]);
        $this->westSection = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary Four W',
            'status' => 1,
            'class_teacher_id' => $this->westTeacher->id,
        ]);

        $this->baseLink = $this->link($this->base, $this->head->id, $this->year);
        $this->eastLink = $this->link($this->eastSection, $this->head->id, $this->year);
        $this->westLink = $this->link($this->westSection, $this->westTeacher->id, $this->year);

        $this->eastIds = $this->students($this->eastLink, 'EAST', 60);
        $this->westIds = $this->students($this->westLink, 'WEST', 58);
    }

    public function test_the_fixture_matches_the_reported_relationships(): void
    {
        $this->assertNull($this->base->fresh()->class_teacher_id);
        $this->assertSame($this->head->id, (int) $this->eastSection->fresh()->class_teacher_id);
        $this->assertSame($this->head->id, (int) $this->baseLink->fresh()->class_teacher_id);
        $this->assertSame($this->head->id, (int) $this->eastLink->fresh()->class_teacher_id);
        $this->assertSame($this->westTeacher->id, (int) $this->westLink->fresh()->class_teacher_id);
        $this->assertNull($this->baseLink->stream);
        $this->assertNull($this->eastLink->stream);
        $this->assertNull($this->westLink->stream);
        $this->assertSame(0, Teacherlink::query()->where('teacher_id', $this->head->id)->count());
        $this->assertSame(0, StudentAcademic::query()->where('standardLink_id', $this->baseLink->id)->count());
        $this->assertSame(60, StudentAcademic::query()->where('standardLink_id', $this->eastLink->id)->count());
        $this->assertSame(58, StudentAcademic::query()->where('standardLink_id', $this->westLink->id)->count());
        $this->assertSame(0, SchoolDetail::query()->where('school_id', $this->school->id)->where('meta_key', 'attendance_scope')->count());
        $this->assertSame('classes_i_teach', SiteHelper::resolveAttendanceScope($this->school->id));
        $this->assertSame($this->standard->id, (int) $this->eastLink->standard_id);
        $this->assertSame($this->standard->id, (int) $this->westLink->standard_id);
        $this->assertSame($this->year->id, (int) $this->eastLink->academic_year_id);
    }

    public function test_a_base_link_head_opens_both_streams_and_a_stream_teacher_does_not(): void
    {
        $page = $this->actingAs($this->head)->get(route('teacher.attendance.register'))->assertOk();
        $html = $page->getContent();
        $this->assertSame(118, substr_count($html, 'data-testid="attendance-row"'));
        $page->assertSee('>Primary Four<', false);
        $page->assertSee('Stream E', false);
        $page->assertSee('Stream W', false);
        $page->assertSee('EAST 1');
        $page->assertSee('WEST 1');
        $page->assertSee('class head: every stream');
        $page->assertDontSee('· one stream');
        $this->assertSame(['Whole class', 'E', 'W'], $this->streamOptionLabels($html));
        $this->assertSame(118, substr_count($html, 'value="1" checked'));

        $filtered = $this->actingAs($this->head)->get(route('teacher.attendance.register', [
            'section_id' => $this->base->id,
            'standardLink_id' => $this->eastLink->id,
        ]))->assertOk();
        $this->assertSame(60, substr_count($filtered->getContent(), 'data-testid="attendance-row"'));
        $filtered->assertDontSee('WEST 1');
        $filtered->assertSee('· one stream');

        $westOnly = $this->actingAs($this->westTeacher)->get(route('teacher.attendance.register'))->assertOk();
        $this->assertSame(58, substr_count($westOnly->getContent(), 'data-testid="attendance-row"'));
        $westOnly->assertSee('WEST 1');
        $westOnly->assertDontSee('EAST 1');
        $westOnly->assertDontSee('class head: every stream');

        $this->actingAs($this->westTeacher)
            ->get(route('teacher.attendance.register', ['standardLink_id' => $this->eastLink->id]))
            ->assertForbidden();
    }

    public function test_saving_one_stream_does_not_write_the_other_and_the_other_school_stays_out(): void
    {
        Attendance::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standardLink_id' => $this->westLink->id,
            'user_id' => $this->westIds[0],
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'status' => 0,
            'remarks' => 'West stays absent',
            'recorded_by' => $this->head->id,
        ]);

        $rows = [];
        foreach ($this->eastIds as $id) {
            $rows[$id] = ['status' => '1', 'comment' => ''];
        }
        $this->actingAs($this->head)->post(route('teacher.attendance.register.save'), [
            'section_id' => $this->base->id,
            'standardLink_id' => $this->eastLink->id,
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'rows' => $rows,
        ])->assertRedirect();

        $this->assertSame(60, Attendance::query()->where('standardLink_id', $this->eastLink->id)->where('status', 1)->count());
        $this->assertSame(0, (int) Attendance::query()->where('user_id', $this->westIds[0])->value('status'));
        $this->assertSame('West stays absent', Attendance::query()->where('user_id', $this->westIds[0])->value('remarks'));
        $this->assertSame(1, Attendance::query()->where('standardLink_id', $this->westLink->id)->count());

        $oldYearStudent = $this->user(6, 'OLDYEAR Pupil');
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->otherYear->id,
            'user_id' => $oldYearStudent->id,
            'standardLink_id' => $this->westLink->id,
        ]);

        $other = School::create([
            'name' => 'Other Shape School',
            'slug' => 'shape-other-'.uniqid(),
            'email' => 'shape-other-'.uniqid().'@t.sch.ug',
            'phone' => '+256708'.random_int(100000, 999999),
            'status' => 1,
        ]);
        $otherYear = AcademicYear::create([
            'school_id' => $other->id,
            'name' => '2026',
            'description' => 'AY',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
            'status' => 1,
        ]);
        Cache::forget('academic_year_for_school_'.$other->id);
        $otherStandard = Standard::create([
            'school_id' => $other->id, 'name' => 'primary', 'order' => 4, 'status' => 1,
        ]);
        $otherSection = Section::create([
            'school_id' => $other->id, 'name' => 'Primary Four W', 'status' => 1,
        ]);
        $otherLink = StandardLink::create([
            'school_id' => $other->id,
            'academic_year_id' => $otherYear->id,
            'standard_id' => $otherStandard->id,
            'section_id' => $otherSection->id,
            'class_teacher_id' => null,
            'stream' => null,
            'status' => 1,
        ]);
        $otherPupil = $this->user(6, 'OTHER SCHOOL PUPIL', $other);
        StudentAcademic::create([
            'school_id' => $other->id,
            'academic_year_id' => $otherYear->id,
            'user_id' => $otherPupil->id,
            'standardLink_id' => $otherLink->id,
        ]);

        $again = $this->actingAs($this->head)->get(route('teacher.attendance.register'))->assertOk();
        $this->assertSame(118, substr_count($again->getContent(), 'data-testid="attendance-row"'));
        $again->assertDontSee('OLDYEAR');
        $again->assertDontSee('OTHER SCHOOL');

        $otherAdmin = $this->user(3, 'Other Admin', $other);
        $this->actingAs($otherAdmin)
            ->get(route('admin.attendance.register', ['section_id' => $this->base->id]))
            ->assertForbidden();
        $this->actingAs($otherAdmin)
            ->get(route('admin.attendance.register', ['standardLink_id' => $this->westLink->id]))
            ->assertForbidden();
    }

    public function test_an_admin_sees_the_whole_class_and_the_head_cannot_open_another_class(): void
    {
        $seven = Section::create([
            'school_id' => $this->school->id, 'name' => 'Primary Seven', 'status' => 1, 'class_teacher_id' => null,
        ]);
        $sevenLink = $this->link($seven, null, $this->year);
        $sevenPupil = $this->user(6, 'SEVEN Pupil');
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $sevenPupil->id,
            'standardLink_id' => $sevenLink->id,
        ]);

        $admin = $this->actingAs($this->admin)
            ->get(route('admin.attendance.register', ['section_id' => $this->base->id]))
            ->assertOk();
        $this->assertSame(118, substr_count($admin->getContent(), 'data-testid="attendance-row"'));
        $admin->assertSee('WEST 1');
        $admin->assertDontSee('SEVEN');

        $this->actingAs($this->admin)
            ->get(route('admin.attendance.register', ['standardLink_id' => $sevenLink->id]))
            ->assertOk()
            ->assertSee('SEVEN');

        $this->actingAs($this->head)
            ->get(route('teacher.attendance.register', ['standardLink_id' => $sevenLink->id]))
            ->assertForbidden();
        $this->actingAs($this->head)
            ->get(route('teacher.attendance.register'))
            ->assertOk()
            ->assertDontSee('SEVEN');
    }

    public function test_a_subject_assignment_does_not_open_another_stream(): void
    {
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $this->eastSection->id,
            'name' => 'Mathematics',
            'code' => 'M4',
            'type' => 'core',
            'status' => 1,
        ]);
        $eastSubjectTeacher = $this->user(5, 'East Subject');
        Teacherlink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standardLink_id' => $this->eastLink->id,
            'subject_id' => $subject->id,
            'teacher_id' => $eastSubjectTeacher->id,
        ]);

        $eastPage = $this->actingAs($eastSubjectTeacher)->get(route('teacher.attendance.register'))->assertOk();
        $this->assertSame(60, substr_count($eastPage->getContent(), 'data-testid="attendance-row"'));
        $eastPage->assertDontSee('WEST 1');
        $eastPage->assertDontSee('class head: every stream');
        $this->actingAs($eastSubjectTeacher)
            ->get(route('teacher.attendance.register', ['standardLink_id' => $this->westLink->id]))
            ->assertForbidden();
    }

    /**
     * @return list<string>
     */
    private function streamOptionLabels(string $html): array
    {
        $start = strpos($html, 'data-testid="attendance-stream-filter"');
        $this->assertNotFalse($start);
        $slice = substr($html, $start, 4000);
        $end = strpos($slice, '</select>');
        $this->assertNotFalse($end);
        preg_match_all('/<option[^>]*>([^<]*)<\/option>/', substr($slice, 0, $end), $matches);

        return array_map('trim', $matches[1]);
    }

    private function link(Section $section, ?int $teacherId, AcademicYear $year): StandardLink
    {
        return StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $this->standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $teacherId,
            'stream' => null,
            'status' => 1,
        ]);
    }

    /**
     * @return list<int>
     */
    private function students(StandardLink $link, string $side, int $count): array
    {
        $ids = [];
        for ($i = 1; $i <= $count; $i++) {
            $user = $this->user(6, $side.' '.$i);
            StudentAcademic::create([
                'school_id' => $this->school->id,
                'academic_year_id' => $this->year->id,
                'user_id' => $user->id,
                'standardLink_id' => $link->id,
            ]);
            $ids[] = (int) $user->id;
        }

        return $ids;
    }

    private function user(int $group, string $name, ?School $school = null): User
    {
        $school = $school ?: $this->school;
        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'-'.uniqid().'@shape.test',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'firstname' => explode(' ', $name)[0],
            'lastname' => explode(' ', $name, 2)[1] ?? 'Person',
        ]);

        return $user;
    }
}
