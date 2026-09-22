<?php

namespace Tests\Feature\Navigation;

use App\Helpers\SiteHelper;
use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Findings 2 and 3 of the role capability matrix.
 *
 * 2: the teacher Attendance nav item pointed at teacher/dashboard#attendance rather than
 *    the real /teacher/attendance page.
 * 3: the class-teacher nav condition used homeroom links only. It turns out the two
 *    conditioned items do NOT share one rule: Report Cards is enforced by
 *    ReportCardsController::authorizeClassTeacher() (homeroom via standards_link), while
 *    Class Streams is enforced by ExamAuthorization::sectionIdsForClassTeacher(), which
 *    also honours sections.class_teacher_id. The nav now mirrors each feature's own check.
 */
class ClassTeacherNavVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private User $homeroomTeacher;

    private User $subjectOnlyTeacher;

    private User $sectionTeacher;

    private StandardLink $homeroomLink;

    private StandardLink $subjectLink;

    private StandardLink $sectionLink;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(MustBeTeacher::class);
        $this->withoutMiddleware(MustBePrivilege::class);

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Nav Visibility School', 'slug' => 'nav-vis-'.uniqid(),
            'email' => 'nv-'.uniqid().'@t.sch.ug', 'phone' => '070'.random_int(1000000, 9999999),
            'status' => 1, 'registration_country' => 'Uganda',
        ]);

        $this->homeroomTeacher = User::factory()->create(['usergroup_id' => 5, 'school_id' => $this->school->id, 'email' => 'nv.home@t.sch.ug']);
        $this->subjectOnlyTeacher = User::factory()->create(['usergroup_id' => 5, 'school_id' => $this->school->id, 'email' => 'nv.subject@t.sch.ug']);
        $this->sectionTeacher = User::factory()->create(['usergroup_id' => 5, 'school_id' => $this->school->id, 'email' => 'nv.section@t.sch.ug']);
        $peer = User::factory()->create(['usergroup_id' => 5, 'school_id' => $this->school->id, 'email' => 'nv.peer@t.sch.ug']);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => (string) now()->year,
            'description' => 'Current Academic Year',
            'start_date' => now()->subMonths(2)->startOfDay(), 'end_date' => now()->addMonths(6)->endOfDay(),
            'status' => 1,
        ]);
        Cache::flush();

        $standard = Standard::create(['school_id' => $this->school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $mkSection = fn (string $n) => Section::create(['school_id' => $this->school->id, 'name' => $n, 'status' => 1]);
        $mkLink = function (Section $s, User $ct, string $stream) use ($standard) {
            return StandardLink::create([
                'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
                'standard_id' => $standard->id, 'section_id' => $s->id,
                'class_teacher_id' => $ct->id, 'stream' => $stream, 'status' => 1,
            ]);
        };

        $ctSection = $mkSection('NV P7 HOMEROOM');
        $subjSection = $mkSection('NV P6 SUBJECT');
        $secSection = $mkSection('NV P5 SECTION-CT');

        $this->homeroomLink = $mkLink($ctSection, $this->homeroomTeacher, 'A');
        $this->subjectLink = $mkLink($subjSection, $peer, 'A');

        // The section-level class teacher: designated on the section itself, and NOT on any
        // standards_link row. ExamAuthorization honours this; ReportCardsController does not.
        $secSection->update(['class_teacher_id' => $this->sectionTeacher->id]);
        $this->sectionLink = $mkLink($secSection, $peer, 'A');

        // subjectOnlyTeacher teaches a subject in a class they do not homeroom.
        $subject = Subject::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id, 'section_id' => $subjSection->id, 'name' => 'Science', 'status' => 1,
        ]);
        Teacherlink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standardLink_id' => $this->subjectLink->id, 'subject_id' => $subject->id, 'teacher_id' => $this->subjectOnlyTeacher->id,
        ]);
    }

    private function sidebarFor(User $teacher): string
    {
        $this->actingAs($teacher);

        return view('layouts.partials.sidebar-menu', ['role' => 'teacher'])->render();
    }

    private function setScope(string $scope): void
    {
        $this->school->setDetailValue(SiteHelper::ATTENDANCE_SCOPE_KEY, $scope);
        SiteHelper::forgetAttendanceScope($this->school->id);
        Cache::flush();
    }

    public function test_attendance_nav_points_at_the_real_page(): void
    {
        foreach ([$this->homeroomTeacher, $this->subjectOnlyTeacher, $this->sectionTeacher] as $teacher) {
            $html = $this->sidebarFor($teacher);
            $this->assertStringContainsString(url('/teacher/attendance'), $html);
            $this->assertStringNotContainsString('dashboard#attendance', $html);
        }
    }

    public function test_homeroom_teacher_sees_both_conditioned_items(): void
    {
        $html = $this->sidebarFor($this->homeroomTeacher);
        $this->assertStringContainsString('Report Cards', $html);
        $this->assertStringContainsString('Class Streams', $html);
    }

    public function test_subject_only_teacher_sees_neither_conditioned_item(): void
    {
        $html = $this->sidebarFor($this->subjectOnlyTeacher);
        $this->assertStringNotContainsString('Report Cards', $html);
        $this->assertStringNotContainsString('Class Streams', $html);
        $this->assertStringContainsString('Attendance', $html);
    }

    public function test_section_level_class_teacher_sees_class_streams_but_not_report_cards(): void
    {
        $html = $this->sidebarFor($this->sectionTeacher);
        $this->assertStringContainsString('Class Streams', $html, 'section-level class teacher must see Class Streams');
        $this->assertStringNotContainsString('Report Cards', $html, 'Report Cards stays homeroom-only, matching its controller');
    }

    public function test_attendance_scope_does_not_move_the_conditioned_items(): void
    {
        foreach (['class_teacher_only', 'classes_i_teach', 'school_wide'] as $scope) {
            $this->setScope($scope);

            $subjectHtml = $this->sidebarFor($this->subjectOnlyTeacher);
            $this->assertStringNotContainsString('Report Cards', $subjectHtml, "scope {$scope} must not reveal Report Cards");
            $this->assertStringNotContainsString('Class Streams', $subjectHtml, "scope {$scope} must not reveal Class Streams");

            $homeroomHtml = $this->sidebarFor($this->homeroomTeacher);
            $this->assertStringContainsString('Report Cards', $homeroomHtml, "scope {$scope} must keep Report Cards for a homeroom teacher");
        }
    }

    public function test_real_attendance_page_honours_the_scope(): void
    {
        $this->setScope('classes_i_teach');
        $r = $this->actingAs($this->subjectOnlyTeacher)->get('/teacher/attendance');
        $r->assertOk();
        $this->assertStringContainsString('NV P6 SUBJECT', $r->getContent(), 'the subject-taught class should be listed under classes_i_teach');

        $this->setScope('class_teacher_only');
        $r2 = $this->actingAs($this->subjectOnlyTeacher)->get('/teacher/attendance');
        $r2->assertOk();
        $this->assertStringNotContainsString('NV P6 SUBJECT', $r2->getContent(), 'under class_teacher_only the subject-taught class must not be listed');
    }
}
