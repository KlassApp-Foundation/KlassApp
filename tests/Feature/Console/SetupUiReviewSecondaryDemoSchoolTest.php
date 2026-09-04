<?php

namespace Tests\Feature\Console;

use App\Models\CurrentPlan;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\WhatsAppUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SetupUiReviewSecondaryDemoSchoolTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('plans')->insert([
            [
                'id' => 1,
                'cycle' => 30,
                'name' => 'Freemium',
                'display_name' => 'Freemium',
                'order' => 1,
                'is_active' => 1,
                'amount' => 0,
                'no_of_students' => 0,
                'no_of_users' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('exam_types')->insert([
            [
                'id' => 2,
                'name' => 'End Of Term',
                'code' => 'EOT',
                'contributes_to_report_total' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function test_command_seeds_o_a_level_structure_genders_and_tuition_labels(): void
    {
        $exit = Artisan::call('schools:setup-ui-review-secondary-demo', ['--force' => true]);
        $this->assertSame(0, $exit);

        $school = School::where('slug', 'ui-review-secondary-demo-school')->first();
        $this->assertNotNull($school);
        $this->assertSame('o_a_level', $school->school_category);

        $this->assertSame(
            ['o-level', 'a-level'],
            Standard::where('school_id', $school->id)->orderBy('order')->pluck('name')->all()
        );

        foreach (['Senior One', 'Senior Four', 'Senior Six'] as $section) {
            $this->assertDatabaseHas('sections', [
                'school_id' => $school->id,
                'name' => $section,
            ]);
        }
        $this->assertSame(6, Section::where('school_id', $school->id)->count());

        $diana = User::where('school_id', $school->id)->where('email', 'diana.namukasa@uireview-secondary.klassapp.demo')->first();
        $this->assertNotNull($diana);
        $this->assertSame('female', optional($diana->userprofile)->gender);
        $this->assertSame(
            'U5678/901',
            StudentAcademic::where('user_id', $diana->id)->value('board_registration_number')
        );

        $faith = User::where('school_id', $school->id)->where('email', 'faith.atwine@uireview-secondary.klassapp.demo')->first();
        $this->assertSame('U9012/345', StudentAcademic::where('user_id', $faith->id)->value('board_registration_number'));

        $female = User::ByActive()->BySchool($school->id)->ByRole(6)->ByGender('female')->count();
        $male = User::ByActive()->BySchool($school->id)->ByRole(6)->ByGender('male')->count();
        $this->assertSame(3, $female);
        $this->assertSame(2, $male);

        $tuition = FeesCategories::with('standard')
            ->where('school_id', $school->id)
            ->where('name', 'Tuition')
            ->get();
        $this->assertCount(2, $tuition);
        $labels = $tuition->map->labeledName()->sort()->values()->all();
        $this->assertSame(["Tuition (A'Level)", "Tuition (O'Level)"], $labels);

        $this->assertTrue(
            User::where('email', 'classteacher@uireview-secondary.klassapp.demo')->where('usergroup_id', 5)->exists()
        );

        $admin = User::where('email', 'admin@uireview-secondary.klassapp.demo')->first();
        $this->assertNotNull($admin);
        $this->assertTrue(WhatsAppUser::where('user_id', $admin->id)->exists());
        $this->assertTrue(CurrentPlan::where('school_id', $school->id)->exists());
    }

    public function test_command_is_idempotent_on_rerun(): void
    {
        Artisan::call('schools:setup-ui-review-secondary-demo', ['--force' => true]);
        $schoolId = School::where('slug', 'ui-review-secondary-demo-school')->value('id');
        $studentCount = User::where('school_id', $schoolId)->where('usergroup_id', 6)->count();

        Artisan::call('schools:setup-ui-review-secondary-demo');
        $this->assertSame(
            $studentCount,
            User::where('school_id', $schoolId)->where('usergroup_id', 6)->count()
        );
        $this->assertSame(1, School::where('slug', 'ui-review-secondary-demo-school')->count());
    }
}
