<?php

namespace Tests\Feature\Teacher;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeTeacher;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\AcademicYear;
use App\Models\Attendance;
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
 * T-095: one register per class or stream. Everyone is present by default,
 * one tap marks absent, one save writes the whole register.
 */
class AttendanceRegisterTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private Section $section;

    private StandardLink $streamA;

    private StandardLink $streamB;

    private User $classHead;

    private User $streamTeacher;

    private User $outsider;

    /** @var array<string, User> */
    private array $students = [];

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
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Register School',
            'slug' => 'register-'.uniqid(),
            'email' => 'register-'.uniqid().'@t.sch.ug',
            'phone' => '+256700'.random_int(100000, 999999),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => (string) now()->year,
            'description' => 'Current Academic Year',
            'start_date' => now()->subMonths(2)->startOfDay(),
            'end_date' => now()->addMonths(6)->endOfDay(),
            'status' => 1,
        ]);
        Cache::forget('academic_year_for_school_'.$this->school->id);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary',
            'order' => 1,
            'status' => 1,
        ]);

        $this->classHead = $this->teacher('Class Head');
        $this->streamTeacher = $this->teacher('Stream B Teacher');
        $this->outsider = $this->teacher('Unrelated Teacher');

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.7',
            'class_teacher_id' => $this->classHead->id,
            'status' => 1,
        ]);

        $this->streamA = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'class_teacher_id' => $this->classHead->id,
            'stream' => 'A',
            'status' => 1,
        ]);

        $this->streamB = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'class_teacher_id' => $this->streamTeacher->id,
            'stream' => 'B',
            'status' => 1,
        ]);

        $this->students['amina'] = $this->student('Amina Nakato', $this->streamA);
        $this->students['brian'] = $this->student('Brian Okello', $this->streamA);
        $this->students['carol'] = $this->student('Carol Nansubuga', $this->streamB);
    }

    public function test_class_head_register_lists_every_stream_with_everyone_present(): void
    {
        $response = $this->actingAs($this->classHead)
            ->get(route('teacher.attendance.register', ['section_id' => $this->section->id]));

        $response->assertOk()
            ->assertSee('data-testid="teacher-attendance-register"', false)
            ->assertSee('AMINA NAKATO')
            ->assertSee('BRIAN OKELLO')
            ->assertSee('CAROL NANSUBUGA')
            ->assertSee('class head: every stream');

        $html = $response->getContent();
        $this->assertSame(3, substr_count($html, 'data-testid="attendance-row"'));
        $this->assertSame(3, substr_count($html, 'data-testid="status-present"'));
        $this->assertSame(3, substr_count($html, 'value="1" checked'));
    }

    public function test_one_save_writes_present_absent_and_comment_once(): void
    {
        $response = $this->actingAs($this->classHead)->post(route('teacher.attendance.register.save'), [
            'section_id' => $this->section->id,
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'rows' => [
                $this->students['amina']->id => ['status' => '1', 'comment' => ''],
                $this->students['brian']->id => ['status' => '1', 'comment' => ''],
                $this->students['carol']->id => ['status' => '0', 'comment' => 'Hospital visit, note from parent.'],
            ],
        ]);

        $response->assertRedirect(route('teacher.attendance.register', [
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'section_id' => $this->section->id,
        ]));
        $response->assertSessionHas('successmessage', 'Attendance saved for 3 students.');

        $this->assertSame(3, Attendance::query()->where('school_id', $this->school->id)->count());

        $this->assertDatabaseHas('attendances', [
            'school_id' => $this->school->id,
            'standardLink_id' => $this->streamA->id,
            'user_id' => $this->students['amina']->id,
            'status' => 1,
            'recorded_by' => $this->classHead->id,
        ]);
        $this->assertDatabaseHas('attendances', [
            'school_id' => $this->school->id,
            'standardLink_id' => $this->streamB->id,
            'user_id' => $this->students['carol']->id,
            'status' => 0,
            'remarks' => 'Hospital visit, note from parent.',
            'recorded_by' => $this->classHead->id,
        ]);

        $this->actingAs($this->classHead)
            ->get(route('teacher.attendance.register', ['section_id' => $this->section->id]))
            ->assertOk()
            ->assertSee('value="0" checked', false)
            ->assertSee('Hospital visit, note from parent.');
    }

    public function test_second_save_updates_the_same_rows_instead_of_duplicating(): void
    {
        $url = route('teacher.attendance.register.save');
        $base = [
            'section_id' => $this->section->id,
            'date' => now()->toDateString(),
            'session' => 'forenoon',
        ];

        $this->actingAs($this->classHead)->post($url, $base + ['rows' => [
            $this->students['amina']->id => ['status' => '1', 'comment' => ''],
            $this->students['brian']->id => ['status' => '1', 'comment' => ''],
            $this->students['carol']->id => ['status' => '1', 'comment' => ''],
        ]])->assertRedirect();

        $this->actingAs($this->classHead)->post($url, $base + ['rows' => [
            $this->students['amina']->id => ['status' => '0', 'comment' => 'Sick'],
            $this->students['brian']->id => ['status' => '1', 'comment' => ''],
            $this->students['carol']->id => ['status' => '1', 'comment' => ''],
        ]])->assertRedirect();

        $this->assertSame(3, Attendance::query()->where('school_id', $this->school->id)->count());
        $this->assertSame(0, (int) Attendance::query()
            ->where('user_id', $this->students['amina']->id)
            ->value('status'));
        $this->assertSame('Sick', Attendance::query()
            ->where('user_id', $this->students['amina']->id)
            ->value('remarks'));
    }

    public function test_stream_teacher_only_sees_and_saves_their_own_stream(): void
    {
        $this->actingAs($this->streamTeacher)
            ->get(route('teacher.attendance.register', ['section_id' => $this->section->id]))
            ->assertOk()
            ->assertSee('CAROL NANSUBUGA')
            ->assertDontSee('AMINA NAKATO')
            ->assertDontSee('BRIAN OKELLO');

        $this->actingAs($this->streamTeacher)->post(route('teacher.attendance.register.save'), [
            'section_id' => $this->section->id,
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'rows' => [
                $this->students['carol']->id => ['status' => '1', 'comment' => ''],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->students['carol']->id,
            'standardLink_id' => $this->streamB->id,
            'status' => 1,
        ]);
    }

    public function test_stream_teacher_cannot_save_a_student_from_another_stream(): void
    {
        $this->actingAs($this->streamTeacher)->post(route('teacher.attendance.register.save'), [
            'section_id' => $this->section->id,
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'rows' => [
                $this->students['carol']->id => ['status' => '1', 'comment' => ''],
                $this->students['amina']->id => ['status' => '0', 'comment' => 'not mine'],
            ],
        ])->assertForbidden();

        $this->assertSame(0, Attendance::query()->count());
    }

    public function test_teacher_without_the_class_cannot_open_or_save_the_register(): void
    {
        $this->actingAs($this->outsider)
            ->get(route('teacher.attendance.register', ['section_id' => $this->section->id]))
            ->assertOk()
            ->assertSee('No active class or stream is assigned to you yet');

        $this->actingAs($this->outsider)->post(route('teacher.attendance.register.save'), [
            'standardLink_id' => $this->streamA->id,
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'rows' => [
                $this->students['amina']->id => ['status' => '1', 'comment' => ''],
            ],
        ])->assertForbidden();

        $this->assertSame(0, Attendance::query()->count());
    }

    public function test_a_single_json_field_saves_the_same_register(): void
    {
        $payload = json_encode([
            $this->students['amina']->id => ['status' => '1', 'comment' => ''],
            $this->students['brian']->id => ['status' => '0', 'comment' => 'Sick'],
            $this->students['carol']->id => ['status' => '1', 'comment' => ''],
        ]);

        $this->actingAs($this->classHead)->post(route('teacher.attendance.register.save'), [
            'section_id' => $this->section->id,
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'rows_json' => $payload,
        ])->assertRedirect();

        $this->assertSame(3, Attendance::query()->where('school_id', $this->school->id)->count());
        $this->assertSame('Sick', Attendance::query()
            ->where('user_id', $this->students['brian']->id)
            ->value('remarks'));
    }

    public function test_a_future_date_is_rejected(): void
    {
        $this->actingAs($this->classHead)->post(route('teacher.attendance.register.save'), [
            'section_id' => $this->section->id,
            'date' => now()->addDay()->toDateString(),
            'session' => 'forenoon',
            'rows' => [
                $this->students['amina']->id => ['status' => '1', 'comment' => ''],
            ],
        ])->assertSessionHasErrors('date');

        $this->assertSame(0, Attendance::query()->count());
    }

    public function test_existing_attendance_prefills_the_register(): void
    {
        Attendance::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'standardLink_id' => $this->streamA->id,
            'user_id' => $this->students['amina']->id,
            'date' => now()->toDateString(),
            'session' => 'forenoon',
            'status' => 0,
            'remarks' => 'Travelled for a family event.',
            'recorded_by' => $this->classHead->id,
        ]);

        $this->actingAs($this->classHead)
            ->get(route('teacher.attendance.register', [
                'standardLink_id' => $this->streamA->id,
                'date' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('value="0" checked', false)
            ->assertSee('Travelled for a family event.');
    }

    public function test_each_saved_row_uses_the_students_own_stream(): void
    {
        $this->actingAs($this->classHead)->post(route('teacher.attendance.register.save'), [
            'section_id' => $this->section->id,
            'date' => now()->toDateString(),
            'session' => 'afternoon',
            'rows' => [
                $this->students['amina']->id => ['status' => '1', 'comment' => ''],
                $this->students['carol']->id => ['status' => '1', 'comment' => ''],
            ],
        ])->assertRedirect();

        $this->assertSame($this->streamA->id, (int) Attendance::query()
            ->where('user_id', $this->students['amina']->id)->value('standardLink_id'));
        $this->assertSame($this->streamB->id, (int) Attendance::query()
            ->where('user_id', $this->students['carol']->id)->value('standardLink_id'));
    }

    private function teacher(string $name): User
    {
        return User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 5,
            'name' => $name,
            'status' => 'active',
        ]);
    }

    private function student(string $name, StandardLink $stream): User
    {
        $user = User::factory()->create([
            'school_id' => $this->school->id,
            'usergroup_id' => 6,
            'name' => $name,
            'status' => 'active',
        ]);

        Userprofile::create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
            'usergroup_id' => 6,
            'firstname' => $name,
            'lastname' => '',
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $user->id,
            'standardLink_id' => $stream->id,
        ]);

        return $user;
    }
}
