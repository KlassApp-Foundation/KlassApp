<?php

namespace Tests\Feature\Google;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\School;
use App\Models\SchoolGoogleConnection;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\Google\Drive\GoogleDriveService;
use App\Services\Google\GoogleAccessRevokedException;
use App\Services\Google\GoogleApiException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\InteractsWithFakeGoogle;
use Tests\TestCase;

class GoogleDriveConnectorTest extends TestCase
{
    use InteractsWithFakeGoogle;
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $student;

    private Exam $exam;

    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFakeGoogle();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        DB::table('exam_types')->upsert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'Drive File School',
            'email' => 'drive-file@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'drive-file-'.uniqid(),
            'status' => 1,
            'report_template' => 'formal',
            'registration_country' => 'Uganda',
        ]);

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'status' => 'current',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
        ]);

        $standard = Standard::create([
            'school_id' => $this->school->id,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => '1',
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'P.2 Extract',
            'status' => 1,
        ]);

        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'email' => 'ct.drive@t.sch.ug',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'usergroup_id' => 3,
            'school_id' => $this->school->id,
            'email' => 'admin.drive@t.sch.ug',
            'status' => 'active',
        ]);

        $stdLink = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'class_teacher_id' => $teacher->id,
            'status' => '1',
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'name' => 'English',
            'code' => 'ENG',
            'type' => 'core',
            'status' => 1,
        ]);

        $this->exam = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'section_id' => $this->section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'exam_type_id' => 2,
            'academic_term_id' => $term->id,
            'academic_year_id' => $year->id,
            'scheduled_at' => '2026-04-01',
            'status' => 'submitted',
        ]));

        $this->student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'Drive Student',
            'email' => 'student.drive@t.sch.ug',
            'registration_number' => 'KLS0000042',
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'user_id' => $this->student->id,
            'standardLink_id' => $stdLink->id,
        ]);

        Marks::create([
            'student_id' => $this->student->id,
            'exam_id' => $this->exam->id,
            'school_id' => $this->school->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'section_id' => $this->section->id,
            'marks' => 72,
            'grade' => 'D1',
        ]);
    }

    protected function tearDown(): void
    {
        $this->tearDownFakeGoogle();
        parent::tearDown();
    }

    public function test_files_a_report_card_under_term_and_class_then_disconnects(): void
    {
        $this->connectGoogle($this->admin, 'drive');

        $filed = $this->actingAs($this->admin)
            ->postJson(route('admin.google.drive.file'), [
                'exam_id' => $this->exam->id,
                'learner_id' => $this->student->id,
            ])
            ->assertOk()
            ->json();

        $this->assertSame('KlassApp / Term 1 / P.2 Extract', $filed['folder']);
        $this->assertSame('KLS0000042-report-card.pdf', $filed['name']);

        $files = $this->google->state()['drive']['files'];
        $klass = $this->onlyFolder($files, 'KlassApp', 'root');
        $term = $this->onlyFolder($files, 'Term 1', $klass['id']);
        $class = $this->onlyFolder($files, 'P.2 Extract', $term['id']);
        $pdfs = array_values(array_filter(
            $files,
            fn ($file) => ($file['mimeType'] ?? '') === 'application/pdf' && ($file['parents'] ?? []) === [$class['id']]
        ));
        $this->assertCount(1, $pdfs);
        $this->assertSame($filed['fileId'], $pdfs[0]['id']);
        $this->assertStringStartsWith('%PDF', base64_decode((string) $pdfs[0]['content']));

        $this->actingAs($this->admin)
            ->postJson(route('admin.google.drive.file'), [
                'exam_id' => $this->exam->id,
                'learner_id' => $this->student->id,
            ])
            ->assertOk();

        $again = $this->google->state()['drive']['files'];
        $this->assertCount(1, array_filter($again, fn ($file) => ($file['name'] ?? '') === 'KlassApp'));
        $this->assertCount(2, array_filter($again, fn ($file) => ($file['mimeType'] ?? '') === 'application/pdf'));

        $this->student->forceFill(['status' => 'inactive'])->save();
        $this->actingAs($this->admin)
            ->postJson(route('admin.google.drive.file'), [
                'exam_id' => $this->exam->id,
                'learner_id' => $this->student->id,
            ])
            ->assertNotFound();

        $other = School::create([
            'name' => 'Drive Other School',
            'email' => 'drive-other@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'drive-other-'.uniqid(),
            'status' => 1,
            'registration_country' => 'Uganda',
        ]);
        $foreign = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $other->id,
            'standard_id' => $this->exam->standard_id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->exam->academic_year_id,
            'academic_term_id' => $this->exam->academic_term_id,
            'subject_id' => $this->exam->subject_id,
            'teacher_id' => $this->exam->teacher_id,
            'exam_type_id' => 2,
            'status' => 'submitted',
        ]));
        $this->actingAs($this->admin)
            ->postJson(route('admin.google.drive.file'), [
                'exam_id' => $foreign->id,
                'learner_id' => $this->student->id,
            ])
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->post(route('admin.google.disconnect', ['connector' => 'drive']))
            ->assertRedirect();
        $this->assertNull(SchoolGoogleConnection::query()->where('school_id', $this->school->id)->first()->credentials);
        $this->assertNotNull(collect($this->google->calls())->first(fn ($call) => $call['path'] === '/revoke'));
    }

    public function test_expired_token_refreshes_before_upload(): void
    {
        $this->connectGoogle($this->admin, 'drive');
        SchoolGoogleConnection::query()->where('school_id', $this->school->id)
            ->update(['token_expires_at' => now()->subMinute()]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.google.drive.file'), [
                'exam_id' => $this->exam->id,
                'learner_id' => $this->student->id,
            ])
            ->assertOk();

        $this->assertNotNull(collect($this->google->calls())->first(
            fn ($call) => $call['path'] === '/token' && ($call['body']['grant_type'] ?? '') === 'refresh_token'
        ));
        $this->assertNotEmpty(array_filter(
            $this->google->state()['drive']['files'],
            fn ($file) => ($file['mimeType'] ?? '') === 'application/pdf'
        ));
    }

    public function test_revoked_access_clears_tokens_and_files_nothing(): void
    {
        $this->connectGoogle($this->admin, 'drive');
        SchoolGoogleConnection::query()->where('school_id', $this->school->id)
            ->update(['token_expires_at' => now()->subMinute()]);
        $this->google->update(function (array &$state) {
            $state['modes']['refresh'] = 'invalid_grant';
        });

        $this->actingAs($this->admin)
            ->postJson(route('admin.google.drive.file'), [
                'exam_id' => $this->exam->id,
                'learner_id' => $this->student->id,
            ])
            ->assertStatus(400);

        $row = SchoolGoogleConnection::query()->where('school_id', $this->school->id)->first();
        $this->assertSame('disabled', $row->status);
        $this->assertNull($row->credentials);
        $this->assertSame([], $this->google->state()['drive']['files']);
    }

    public function test_drive_api_error_keeps_the_connection_and_stores_no_file(): void
    {
        $this->connectGoogle($this->admin, 'drive');
        $this->google->update(function (array &$state) {
            $state['modes']['drive'] = '500';
        });

        try {
            app(GoogleDriveService::class)->fileReportCard($this->admin, $this->exam, $this->student->id);
            $this->fail('A Drive API error must surface.');
        } catch (GoogleApiException $e) {
            $this->assertSame(500, $e->status);
            $this->assertNotInstanceOf(GoogleAccessRevokedException::class, $e);
        }

        $row = SchoolGoogleConnection::query()->where('school_id', $this->school->id)->first();
        $this->assertSame('active', $row->status);
        $this->assertNotNull($row->accessToken());
        $this->assertSame([], $this->google->state()['drive']['files']);
    }

    /**
     * @param  list<array<string, mixed>>  $files
     * @return array<string, mixed>
     */
    private function onlyFolder(array $files, string $name, string $parentId): array
    {
        $matches = array_values(array_filter(
            $files,
            fn ($file) => ($file['name'] ?? '') === $name
                && ($file['mimeType'] ?? '') === 'application/vnd.google-apps.folder'
                && ($file['parents'] ?? []) === [$parentId]
        ));
        $this->assertCount(1, $matches, $name);

        return $matches[0];
    }
}
