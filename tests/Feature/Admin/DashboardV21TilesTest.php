<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Attendance;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\DashboardV2DataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardV21TilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        config(['dashboard.v2_enabled' => true]);
    }

    public function test_tiles_link_out_and_the_quick_action_row_is_gone(): void
    {
        [$school, $admin] = $this->schoolWithClass('Tiles');
        $this->student($school, 'Amina', 'female');

        $html = $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->getContent();

        $this->assertStringNotContainsString('aria-label="Quick actions"', $html);
        $this->assertStringContainsString('href="'.url('/admin/students').'"', $html);
        $this->assertStringContainsString('+ Add students', $html);
        $this->assertStringContainsString('View registers', $html);
        $this->assertStringContainsString('Record payment', $html);
        // F4: one report cards action by state; no exam yet, so it is Generate.
        $this->assertStringContainsString('>Generate</a>', $html);
        $this->assertStringContainsString('Enter marks', $html);
        $css = file_get_contents(public_path('css/dashboard-v2.css'));
        $this->assertStringContainsString('min-height:280px', $css);
        $this->assertStringContainsString('minmax(176px,1fr)', $css);
        $this->assertStringContainsString('max-width: 695px', $css);
        $this->assertStringContainsString('max-height:150px', $css);
        $this->assertStringContainsString('html.toshi-collapsed .dv2-kpi', $css);
        $this->assertStringContainsString('container-type:inline-size', $css);
        $this->assertStringContainsString('1 girl, 0 boys', $html);
        $this->assertStringContainsString('max-width:1600px', $css);
        $shell = file_get_contents(public_path('css/dashboard-refresh.css'));
        $js = file_get_contents(public_path('js/custom.js'));
        $this->assertStringContainsString('.sidebar-v3-link.is-ancestor', $shell);
        $this->assertStringContainsString('border-left: 0', $shell);
        $this->assertStringContainsString('aria-label="Close menu"', $html);
        $this->assertStringContainsString('drawer-close', $js);
        $this->assertStringContainsString('border-radius: 999px', $shell);
        $this->assertStringContainsString('td form:has(input[name="_method"][value="DELETE"]) .ds-btn-danger', $shell);
        $this->assertStringContainsString('max-width: 1600px', $shell);
        $this->assertStringContainsString("method.toUpperCase() !== 'DELETE'", $js);
        $toshi = file_get_contents(public_path('vendor/toshi-ui/toshi-ui.css'));
        $this->assertStringContainsString('top: var(--ka-topbar-h, 64px)', $toshi);
        $this->assertStringNotContainsString('height: 3px', $toshi);
        $this->assertStringNotContainsString('Tiles ·', $html);
    }

    public function test_attendance_tile_uses_last_week_when_this_week_is_empty(): void
    {
        [$school, $admin] = $this->schoolWithClass('Attend');
        $student = $this->student($school, 'Brian', 'male');
        $yearId = AcademicYear::where('school_id', $school->id)->value('id');
        Attendance::create([
            'school_id' => $school->id,
            'academic_year_id' => $yearId,
            'user_id' => $student->id,
            'date' => now()->subWeek()->startOfWeek()->setTime(8, 0),
            'session' => 'forenoon',
            'status' => 1,
            'recorded_by' => $admin->id,
        ]);

        $data = app(DashboardV2DataService::class)->build($school, $admin);

        $this->assertSame('100.0%', $data['kpis']['attendance']['value']);
        $this->assertSame('Last week', $data['kpis']['attendance']['detail']);
    }

    public function test_gender_bars_follow_this_schools_performance_classes(): void
    {
        [$school, $admin, $link] = $this->schoolWithClass('Gender A', 'P.4');
        $other = School::create([
            'name' => 'Gender B', 'slug' => 'g-b-'.uniqid(), 'email' => uniqid().'@t.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999), 'status' => 1,
        ]);
        $this->student($school, 'Girl', 'female', $link);
        $this->student($school, 'Boy', 'male', $link);
        $this->student($other, 'Other', 'female');
        $this->mark($school, $admin, $link, 70);

        $data = app(DashboardV2DataService::class)->build($school, $admin);
        $labels = array_column($data['charts']['per_class'], 'label');
        $genderLabels = array_column($data['charts']['gender_classes'], 'label');

        $this->assertSame($labels, $genderLabels);
        $this->assertSame('P.4', $genderLabels[0]);
        $this->assertSame(1, $data['charts']['gender_classes'][0]['girls']);
        $this->assertSame(1, $data['charts']['gender_classes'][0]['boys']);
        $this->assertSame(2, $data['charts']['gender']['girls'] + $data['charts']['gender']['boys']);
    }

    private function schoolWithClass(string $name, string $class = 'P.4'): array
    {
        $school = School::create([
            'name' => $name, 'slug' => strtolower($name).'-'.uniqid(), 'email' => uniqid().'@t.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999), 'status' => 1,
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'y',
            'start_date' => now()->startOfYear()->toDateString(), 'end_date' => now()->endOfYear()->toDateString(), 'status' => 1,
        ]);
        AcademicTerm::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term III',
            'starts_on' => now()->subMonths(2)->toDateString(), 'ends_on' => now()->addMonth()->toDateString(), 'status' => 'current',
        ]);
        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $section = Section::create(['school_id' => $school->id, 'name' => $class, 'status' => 1]);
        $link = StandardLink::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'standard_id' => $standard->id, 'section_id' => $section->id, 'status' => 1,
        ]);
        $admin = User::factory()->create(['school_id' => $school->id, 'usergroup_id' => 3, 'status' => 'active']);
        Userprofile::create([
            'user_id' => $admin->id, 'school_id' => $school->id, 'usergroup_id' => 3,
            'firstname' => 'Daniel', 'lastname' => 'Mukasa', 'status' => 'active',
        ]);

        return [$school, $admin, $link];
    }

    private function student(School $school, string $first, string $gender, ?StandardLink $link = null): User
    {
        $user = User::factory()->create([
            'school_id' => $school->id, 'usergroup_id' => 6, 'status' => 'active', 'name' => strtolower($first).uniqid(),
        ]);
        Userprofile::create([
            'user_id' => $user->id, 'school_id' => $school->id, 'usergroup_id' => 6,
            'firstname' => $first, 'lastname' => 'Pupil', 'gender' => $gender, 'status' => 'active',
        ]);
        if ($link) {
            StudentAcademic::create([
                'school_id' => $school->id, 'academic_year_id' => $link->academic_year_id,
                'user_id' => $user->id, 'standardLink_id' => $link->id,
            ]);
        }

        return $user;
    }

    private function mark(School $school, User $teacher, StandardLink $link, float $score): void
    {
        $type = ExamType::create(['name' => 'End of term', 'code' => 'EOT', 'contributes_to_report_total' => true]);
        $subject = Subject::create([
            'school_id' => $school->id, 'academic_year_id' => $link->academic_year_id,
            'standard_id' => $link->standard_id, 'section_id' => $link->section_id,
            'name' => 'Mathematics', 'code' => 'MTC', 'type' => 'core',
        ]);
        $exam = new Exam([
            'school_id' => $school->id, 'standard_id' => $link->standard_id, 'section_id' => $link->section_id,
            'academic_year_id' => $link->academic_year_id,
            'academic_term_id' => AcademicTerm::where('school_id', $school->id)->value('id'),
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id, 'status' => 'done',
        ]);
        $exam->exam_type_id = $type->id;
        $exam->save();
        $studentId = StudentAcademic::where('standardLink_id', $link->id)->value('user_id');
        Marks::create([
            'student_id' => $studentId, 'teacher_id' => $teacher->id, 'school_id' => $school->id,
            'subject_id' => $subject->id, 'exam_id' => $exam->id, 'section_id' => $link->section_id,
            'marks' => $score, 'grade' => 'C3',
        ]);
    }
}
