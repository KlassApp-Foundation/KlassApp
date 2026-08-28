<?php

namespace Tests\Feature\WhatsApp;

use App\Http\Controllers\Api\WhatsAppController;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\ParentLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class ParentCrossSchoolLinkingTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '+256700123456';

    private School $schoolA;

    private School $schoolB;

    private User $studentA;

    private User $studentB;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = School::create(['name' => 'Alpha Primary', 'email' => 'alpha@test.sch.ug']);
        $this->schoolB = School::create(['name' => 'Beta Senior', 'email' => 'beta@test.sch.ug']);

        foreach ([$this->schoolA, $this->schoolB] as $school) {
            AcademicYear::create([
                'school_id' => $school->id,
                'name' => '2026',
                'description' => 'Current Academic Year',
                'start_date' => '2026-01-01 00:00:00',
                'end_date' => '2026-12-31 23:59:59',
                'status' => 1,
            ]);
        }

        $this->studentA = $this->createStudentWithKls($this->schoolA, 'KLS0000001');
        $this->studentB = $this->createStudentWithKls($this->schoolB, 'KLS0000002');
    }

    /**
     * Regression: controller delegates to ParentLinkService — no duplicate WhatsAppUser on 2nd school.
     *
     * @test
     */
    public function controller_link_parent_to_student_allows_cross_school_second_child(): void
    {
        $service = app(ParentLinkService::class);
        $service->linkByStudentId(self::PHONE, $this->studentA->id, 'Cross Parent');

        $controller = app(WhatsAppController::class);
        $method = new ReflectionMethod(WhatsAppController::class, 'linkParentToStudent');
        $method->setAccessible(true);
        $noop = static fn (): null => null;

        $method->invoke($controller, self::PHONE, $this->studentB->id, $noop, $noop, 'Cross Parent');

        $this->assertSame(1, WhatsAppUser::where('phone', self::PHONE)->count());
        $this->assertSame(2, StudentParentLink::count());
    }

    /** @test */
    public function parent_links_child_at_school_a_then_school_b_via_service(): void
    {
        $service = app(ParentLinkService::class);

        $first = $service->linkByStudentId(self::PHONE, $this->studentA->id, 'Cross Parent');
        $this->assertTrue($first->linked);
        $this->assertSame(1, User::where('usergroup_id', 7)->count());
        $this->assertSame(1, WhatsAppUser::where('phone', self::PHONE)->count());
        $this->assertSame(1, StudentParentLink::count());

        $parent = User::where('usergroup_id', 7)->first();
        $this->assertNull($parent->school_id);

        $second = $service->linkByStudentId(self::PHONE, $this->studentB->id, 'Cross Parent');
        $this->assertTrue($second->linked);

        $this->assertSame(1, User::where('usergroup_id', 7)->count());
        $this->assertSame(1, WhatsAppUser::where('phone', self::PHONE)->count());
        $this->assertSame(2, StudentParentLink::count());

        $parentId = WhatsAppUser::where('phone', self::PHONE)->value('user_id');
        $this->assertSame(
            [$this->studentA->id, $this->studentB->id],
            StudentParentLink::where('parent_id', $parentId)->orderBy('student_id')->pluck('student_id')->all(),
        );

        $this->assertSame(
            [$this->schoolA->id, $this->schoolB->id],
            StudentParentLink::where('parent_id', $parentId)->orderBy('student_id')->pluck('school_id')->all(),
        );
    }

    /** @test */
    public function payment_code_link_for_existing_user_sets_school_id_on_link(): void
    {
        $service = app(ParentLinkService::class);
        $service->linkByStudentId(self::PHONE, $this->studentA->id, 'Cross Parent');

        $whatsappUser = WhatsAppUser::where('phone', self::PHONE)->firstOrFail();
        $parent = User::findOrFail($whatsappUser->user_id);

        $codeB = '1234567890';
        StudentAcademic::where('user_id', $this->studentB->id)->update([
            'std_school_pay_number' => $codeB,
        ]);

        $result = $service->linkByPaymentCodeForExistingUser($whatsappUser, $codeB);
        $this->assertTrue($result->linked);

        $link = StudentParentLink::where('parent_id', $parent->id)
            ->where('student_id', $this->studentB->id)
            ->first();

        $this->assertNotNull($link);
        $this->assertSame($this->schoolB->id, (int) $link->school_id);
    }

    private function createStudentWithKls(School $school, string $klsId): User
    {
        $ay = AcademicYear::where('school_id', $school->id)->first();
        $standard = Standard::create(['school_id' => $school->id, 'name' => 'Primary', 'order' => 1]);
        $section = Section::create(['school_id' => $school->id, 'name' => 'P1']);
        $link = StandardLink::create([
            'school_id' => $school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'academic_year_id' => $ay->id,
        ]);

        $student = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => 'Student '.$klsId,
        ]);

        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
            'klassapp_student_id' => $klsId,
        ]);

        return $student;
    }
}
