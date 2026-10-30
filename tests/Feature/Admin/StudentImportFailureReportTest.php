<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Imports\UsersImport;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentImportFailureReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            PreventRequestForgery::class,
            MustBePrivilege::class,
            MustBeSchoolAdmin::class,
        ]);
    }

    public function test_a_row_is_counted_only_when_the_student_is_saved_and_the_failure_is_shown(): void
    {
        $school = $this->schoolWithClass();
        $admin = User::where('school_id', $school->id)->where('usergroup_id', 3)->first();
        Auth::login($admin);

        (new UsersImport)->collection(collect([
            [
                'firstname' => 'Ada',
                'lastname' => 'Learner',
                'email' => 'ada@example.test',
                'class' => 'Primary Two',
                'gender' => 'female',
                'date_of_birth' => '2014-03-02',
            ],
            [
                'firstname' => 'Ada',
                'lastname' => 'Duplicate',
                'email' => 'ada@example.test',
                'class' => 'Primary Two',
                'gender' => 'female',
                'date_of_birth' => '2014-03-02',
            ],
            [
                'firstname' => '',
                'class' => '',
            ],
        ]));

        $this->assertSame(1, User::where('school_id', $school->id)->where('usergroup_id', 6)->count());
        $this->assertSame(1, Session::get('insertedcount'));

        $failures = Session::get('import_row_failures');
        $this->assertSame([
            'Row 3 could not be imported. Check the student\'s details and try again.',
            'Row 4: first name and class are required.',
        ], $failures);
    }

    public function test_a_database_error_shows_a_plain_message_and_logs_the_sql(): void
    {
        $school = $this->schoolWithClass();
        $admin = User::where('school_id', $school->id)->where('usergroup_id', 3)->first();
        $email = 'sql-leak-marker@example.test';

        $logged = [];
        Log::listen(function ($log) use (&$logged) {
            $logged[] = $log->level.' '.$log->message.' '.json_encode($log->context);
        });

        $csv = implode("\n", [
            'firstname,lastname,email,class,gender,date_of_birth',
            'Ada,Learner,'.$email.',Primary Two,female,2014-03-02',
            'Ada,Duplicate,'.$email.',Primary Two,female,2014-03-02',
        ]);
        $file = UploadedFile::fake()->createWithContent('students.csv', $csv);

        $response = $this->actingAs($admin)->post('/admin/importUsers', [
            'import_file' => $file,
        ]);

        $plain = 'Row 3 could not be imported. Check the student\'s details and try again.';
        $response->assertRedirect();
        $response->assertSessionHas('import_row_failures', [$plain]);

        $session = json_encode(session()->all());
        $this->assertStringNotContainsString('SQLSTATE', $session);
        $this->assertStringNotContainsString('SQL:', $session);
        $this->assertStringNotContainsString($email, $session);
        $this->assertStringNotContainsString('insert into', strtolower($session));

        $page = $this->actingAs($admin)->get('/admin/import');
        $page->assertOk();
        $page->assertSee($plain);
        $page->assertDontSee('SQLSTATE', false);
        $page->assertDontSee('SQL:', false);
        $page->assertDontSee($email, false);
        $page->assertDontSee('insert into', false);

        $logText = implode("\n", $logged);
        $this->assertStringContainsString('Student import row failed', $logText);
        $this->assertStringContainsString('"row":3', $logText);
        $this->assertStringContainsString('"school_id":'.$school->id, $logText);
        $this->assertStringContainsString('SQLSTATE[', $logText);
        $this->assertStringContainsString('SQL:', $logText);
        $this->assertStringContainsString('insert into', strtolower($logText));
        $this->assertStringNotContainsString($email, $logText);
    }

    public function test_an_exception_outside_create_user_does_not_reach_the_failure_text(): void
    {
        $school = $this->schoolWithClass();
        $admin = User::where('school_id', $school->id)->where('usergroup_id', 3)->first();
        Auth::login($admin);

        $logged = [];
        Log::listen(function ($log) use (&$logged) {
            $logged[] = $log->level.' '.$log->message.' '.json_encode($log->context);
        });

        (new UsersImport)->collection(collect([
            [
                'firstname' => 'Ada',
                'lastname' => 'Learner',
                'class' => 'Primary Two',
                'date_of_birth' => 'not-a-date',
            ],
        ]));

        $failures = Session::get('import_row_failures');
        $this->assertSame([
            'Row 2 could not be imported. Check the student\'s details and try again.',
        ], $failures);
        $shown = implode("\n", $failures);
        $this->assertStringNotContainsString('not-a-date', $shown);
        $this->assertStringNotContainsString('Failed to parse', $shown);

        $logText = implode("\n", $logged);
        $this->assertStringContainsString('Student import stopped', $logText);
        $this->assertStringContainsString('"row":2', $logText);
        $this->assertStringContainsString('"school_id":'.$school->id, $logText);
        $this->assertStringContainsString('not-a-date', $logText);
    }

    private function schoolWithClass(): School
    {
        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'Import Failure School',
            'email' => 'fail-'.Str::random(6).'@example.test',
            'phone' => '+256700'.random_int(100000, 999999),
            'slug' => Str::random(10),
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Import Failure Admin',
            'email' => 'admin-'.Str::random(6).'@example.test',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $admin->id,
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'firstname' => 'Import',
            'lastname' => 'Admin',
        ]);

        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $section = Section::create(['school_id' => $school->id, 'name' => 'Primary Two', 'status' => 1]);
        StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'status' => 1,
        ]);

        return $school;
    }
}
