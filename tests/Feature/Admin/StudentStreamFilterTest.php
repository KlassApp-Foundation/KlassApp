<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The students class filter uses ClassStructureService::resolveBaseSection.
 * Streams are separate sections with a null standards_link.stream.
 */
class StudentStreamFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_base_class_filters_the_whole_class_and_each_stream(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $school = School::create([
            'name' => 'Filter School', 'slug' => 'filter-'.uniqid(),
            'email' => uniqid().'@t.sch.ug', 'phone' => '+256710'.random_int(100000, 999999),
            'status' => 1, 'registration_country' => 'Uganda',
        ]);
        $year = AcademicYear::create([
            'school_id' => $school->id, 'name' => '2026', 'description' => 'AY',
            'start_date' => now()->subMonth()->toDateString(), 'end_date' => now()->addMonths(4)->toDateString(), 'status' => 1,
        ]);
        Cache::forget('academic_year_for_school_'.$school->id);
        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 4, 'status' => 1]);
        $admin = $this->user($school, 3, 'Filter Admin');

        $base = Section::create(['school_id' => $school->id, 'name' => 'Primary Four', 'status' => 1, 'class_teacher_id' => null]);
        $east = Section::create(['school_id' => $school->id, 'name' => 'Primary Four E', 'status' => 1]);
        $west = Section::create(['school_id' => $school->id, 'name' => 'Primary Four W', 'status' => 1]);
        $other = Section::create(['school_id' => $school->id, 'name' => 'Primary Seven', 'status' => 1]);
        $baseLink = $this->link($school, $year, $standard, $base);
        $eastLink = $this->link($school, $year, $standard, $east);
        $westLink = $this->link($school, $year, $standard, $west);
        $otherLink = $this->link($school, $year, $standard, $other);
        $this->enrol($school, $year, $eastLink, 'EAST Pupil');
        $this->enrol($school, $year, $westLink, 'WEST Pupil');
        $this->enrol($school, $year, $otherLink, 'SEVEN Pupil');

        $otherSchool = School::create([
            'name' => 'Other Filter School', 'slug' => 'filter-other-'.uniqid(),
            'email' => uniqid().'@t.sch.ug', 'phone' => '+256711'.random_int(100000, 999999), 'status' => 1,
        ]);
        $otherYear = AcademicYear::create([
            'school_id' => $otherSchool->id, 'name' => '2026', 'description' => 'AY',
            'start_date' => now()->subMonth()->toDateString(), 'end_date' => now()->addMonths(4)->toDateString(), 'status' => 1,
        ]);
        $otherStandard = Standard::create(['school_id' => $otherSchool->id, 'name' => 'primary', 'order' => 4, 'status' => 1]);
        $foreignSection = Section::create(['school_id' => $otherSchool->id, 'name' => 'Primary Four E', 'status' => 1]);
        $foreignLink = $this->link($otherSchool, $otherYear, $otherStandard, $foreignSection);
        $this->enrol($otherSchool, $otherYear, $foreignLink, 'FOREIGN Pupil');

        $whole = $this->actingAs($admin)->get('/admin/students?chip=class&standard='.$baseLink->id.'&search=Pupil');
        $whole->assertOk();
        $whole->assertSee('East Pupil', false);
        $whole->assertSee('West Pupil', false);
        $whole->assertDontSee('Seven Pupil', false);
        $whole->assertDontSee('Foreign Pupil', false);
        $whole->assertSee('data-testid="student-stream-filter"', false);
        $whole->assertSee('Whole class', false);
        $whole->assertSee('>E<', false);
        $whole->assertSee('>W<', false);
        $whole->assertSee('search=Pupil', false);
        $whole->assertSee('stream='.$eastLink->id, false);

        $onlyEast = $this->actingAs($admin)->get('/admin/students?chip=class&standard='.$baseLink->id.'&stream='.$eastLink->id.'&search=Pupil');
        $onlyEast->assertOk();
        $onlyEast->assertSee('East Pupil', false);
        $onlyEast->assertDontSee('West Pupil', false);
        $onlyEast->assertDontSee('Seven Pupil', false);
        $onlyEast->assertSee('search=Pupil', false);

        $onlyWest = $this->actingAs($admin)->get('/admin/students?chip=class&standard='.$baseLink->id.'&stream='.$westLink->id);
        $onlyWest->assertOk();
        $onlyWest->assertSee('West Pupil', false);
        $onlyWest->assertDontSee('East Pupil', false);

        $this->actingAs($admin)->get('/admin/students?chip=class&standard='.$foreignLink->id)
            ->assertOk()
            ->assertSee('East Pupil', false)
            ->assertDontSee('Foreign Pupil', false);

        $studentsPage = $this->actingAs($admin)->get('/admin/students')->assertOk();
        $studentsPage->assertDontSee('>Import list<', false);
        $studentsPage->assertSee('data-testid="add-student"', false);
        $studentsPage->assertSee('/admin/student/add', false);
        $html = $studentsPage->getContent();
        $this->assertStringContainsString('sidebar-v3-link', $html);
        $this->assertMatchesRegularExpression('/sidebar-v3-link[^"]*is-ancestor(?![^"]*\\bactive\\b)/', $html);
        $this->assertMatchesRegularExpression('/sidebar-v3-child[^"]*\\bactive\\b/', $html);
    }

    private function link(School $school, AcademicYear $year, Standard $standard, Section $section): StandardLink
    {
        return StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => null,
            'stream' => null,
            'status' => 1,
        ]);
    }

    private function enrol(School $school, AcademicYear $year, StandardLink $link, string $name): void
    {
        $user = $this->user($school, 6, $name);
        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $user->id,
            'standardLink_id' => $link->id,
        ]);
    }

    private function user(School $school, int $group, string $name): User
    {
        $user = User::create([
            'school_id' => $school->id,
            'usergroup_id' => $group,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'-'.uniqid().'@filter.test',
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
