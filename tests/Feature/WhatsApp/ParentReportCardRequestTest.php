<?php

namespace Tests\Feature\WhatsApp;

use App\Http\Controllers\Api\WhatsAppController;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\WhatsAppBusinessService;
use App\Services\WhatsAppReportCardDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class ParentReportCardRequestTest extends TestCase
{
    use RefreshDatabase;

    private string $parentPhone = '+256700777111';

    private School $school;

    private User $parent;

    private User $student;

    private StandardLink $stdLink;

    private WhatsAppUser $parentWa;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.business_api_token' => 'test-token',
            'services.whatsapp.business_phone_number_id' => '1416403124879552',
            'services.whatsapp.business_api_version' => 'v21.0',
            'app.url' => 'http://localhost',
        ]);

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        DB::table('exam_types')->upsert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->school = School::create([
            'name' => 'WA Report School',
            'email' => 'wa-report@t.sch.ug',
            'phone' => '070'.random_int(1000000, 9999999),
            'slug' => 'wa-report-'.uniqid(),
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
            'name' => 'primary_upper',
            'order' => 7,
            'status' => '1',
        ]);

        $section = Section::create([
            'school_id' => $this->school->id,
            'name' => 'Primary Seven',
            'status' => 1,
        ]);

        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $this->school->id,
            'email' => 'ct.wareport@t.sch.ug',
        ]);

        $this->stdLink = StandardLink::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $teacher->id,
            'status' => '1',
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'Mathematics',
            'code' => 'MATH',
            'type' => 'core',
            'status' => 1,
        ]);

        $this->student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'name' => 'Amina Nansubuga3736',
            'email' => 'amina.wareport@t.sch.ug',
            'status' => 'active',
        ]);

        Userprofile::create([
            'user_id' => $this->student->id,
            'usergroup_id' => 6,
            'school_id' => $this->school->id,
            'firstname' => 'Amina',
            'lastname' => 'Nansubuga',
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'user_id' => $this->student->id,
            'standardLink_id' => $this->stdLink->id,
        ]);

        $eot = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $this->school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'exam_type_id' => 2,
            'academic_term_id' => $term->id,
            'academic_year_id' => $year->id,
            'scheduled_at' => '2026-06-15',
            'status' => 'done',
        ]));

        Marks::create([
            'student_id' => $this->student->id,
            'exam_id' => $eot->id,
            'school_id' => $this->school->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'section_id' => $section->id,
            'marks' => 72,
            'grade' => 'D1',
        ]);

        $this->parent = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 7,
            'status' => 'active',
            'name' => 'Parent Report',
        ]);

        Userprofile::create([
            'user_id' => $this->parent->id,
            'usergroup_id' => 7,
            'school_id' => null,
            'firstname' => 'Parent',
            'lastname' => 'Report',
            'status' => 'active',
        ]);

        StudentParentLink::create([
            'school_id' => $this->school->id,
            'parent_id' => $this->parent->id,
            'student_id' => $this->student->id,
            'status' => 1,
        ]);

        $this->parentWa = WhatsAppUser::create([
            'phone' => $this->parentPhone,
            'user_id' => $this->parent->id,
            'school_id' => $this->school->id,
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        RateLimiter::clear('whatsapp-report:'.$this->parentPhone);
        Storage::disk('local')->deleteDirectory(WhatsAppReportCardDeliveryService::STORAGE_DIR);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Storage::disk('local')->deleteDirectory(WhatsAppReportCardDeliveryService::STORAGE_DIR);
        parent::tearDown();
    }

    public function test_report_keyword_sends_document_via_signed_url_using_real_pdf_pipeline(): void
    {
        $captured = null;
        $whatsApp = $this->mockWhatsApp();
        $whatsApp->shouldReceive('sendDocument')
            ->once()
            ->withArgs(function (string $phone, string $fileUrl, string $caption, ?string $filename, ?string $flowType) use (&$captured) {
                $captured = compact('phone', 'fileUrl', 'caption', 'filename', 'flowType');

                return $phone === $this->parentPhone
                    && $flowType === 'report_card'
                    && str_contains($fileUrl, '/whatsapp/report-files/')
                    && str_contains($fileUrl, 'signature=')
                    && str_contains($caption, 'AMINA NANSUBUGA')
                    && is_string($filename)
                    && str_ends_with($filename, '-report-card.pdf');
            })
            ->andReturn(['success' => true, 'message_id' => 'doc']);
        $whatsApp->shouldNotReceive('sendText');
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeProcessMeta('REPORT');

        $this->assertNotNull($captured);

        $path = parse_url($captured['fileUrl'], PHP_URL_PATH);
        $token = basename($path);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{40}$/', $token);
        $this->assertTrue(
            Storage::disk('local')->exists(WhatsAppReportCardDeliveryService::STORAGE_DIR.'/'.$token.'.pdf')
        );

        $pdf = Storage::disk('local')->get(WhatsAppReportCardDeliveryService::STORAGE_DIR.'/'.$token.'.pdf');
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function test_grades_keyword_does_not_send_document(): void
    {
        $gradeBodies = [];
        $whatsApp = $this->mockWhatsApp();
        $whatsApp->shouldReceive('sendText')
            ->atLeast()->once()
            ->andReturnUsing(function (string $phone, string $message, ?string $flowType = null) use (&$gradeBodies) {
                $gradeBodies[] = $flowType;

                return ['success' => true, 'message_id' => 'grades'];
            });
        $whatsApp->shouldNotReceive('sendDocument');
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeProcessMeta('GRADES');

        $this->assertNotEmpty($gradeBodies);
        $this->assertTrue(
            collect($gradeBodies)->contains(fn ($t) => in_array($t, ['grades', 'grades_none', 'grades_none_all'], true))
        );
    }

    public function test_report_soft_fails_when_no_eot_exam(): void
    {
        Exam::query()->delete();
        Marks::query()->delete();

        $bodies = [];
        $whatsApp = $this->mockWhatsApp();
        $whatsApp->shouldReceive('sendText')
            ->atLeast()->once()
            ->andReturnUsing(function (string $phone, string $message, ?string $flowType = null) use (&$bodies) {
                $bodies[] = compact('message', 'flowType');

                return ['success' => true, 'message_id' => 'soft'];
            });
        $whatsApp->shouldNotReceive('sendDocument');
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeProcessMeta('REPORT');

        $reportMessages = collect($bodies)->filter(fn ($b) => ($b['flowType'] ?? '') === 'report_none');
        $this->assertGreaterThan(0, $reportMessages->count());
        $this->assertStringContainsString('AMINA NANSUBUGA', $reportMessages->first()['message']);
        $this->assertStringContainsString('No report card has been published yet', $reportMessages->first()['message']);
    }

    public function test_report_soft_fails_when_no_children_linked(): void
    {
        StudentParentLink::query()->delete();

        $bodies = [];
        $whatsApp = $this->mockWhatsApp();
        $whatsApp->shouldReceive('sendText')
            ->once()
            ->andReturnUsing(function (string $phone, string $message, ?string $flowType = null) use (&$bodies) {
                $bodies[] = compact('message', 'flowType');

                return ['success' => true, 'message_id' => 'none'];
            });
        $whatsApp->shouldNotReceive('sendDocument');
        $this->app->instance(WhatsAppBusinessService::class, $whatsApp);

        $this->invokeProcessMeta('REPORT');

        $this->assertSame('report_no_children', $bodies[0]['flowType']);
        $this->assertStringContainsString('No children linked', $bodies[0]['message']);
    }

    public function test_signed_report_file_route_serves_pdf_and_rejects_unsigned(): void
    {
        $delivery = app(WhatsAppReportCardDeliveryService::class);
        $result = $delivery->prepareForStudent($this->parent, $this->student);
        $this->assertTrue($result['ok']);

        $this->get($result['url'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $path = parse_url($result['url'], PHP_URL_PATH);
        $this->get($path)->assertForbidden();
    }

    public function test_prune_command_deletes_old_report_files(): void
    {
        Storage::disk('local')->put(
            WhatsAppReportCardDeliveryService::STORAGE_DIR.'/abcdefghijklmnopqrstuvwxyz0123456789ABCD.pdf',
            '%PDF-1.4 old'
        );

        $fullPath = Storage::disk('local')->path(
            WhatsAppReportCardDeliveryService::STORAGE_DIR.'/abcdefghijklmnopqrstuvwxyz0123456789ABCD.pdf'
        );
        touch($fullPath, now()->subHours(5)->getTimestamp());

        $this->artisan('whatsapp:prune-report-files', ['--hours' => 2])
            ->assertSuccessful();

        $this->assertFalse(
            Storage::disk('local')->exists(
                WhatsAppReportCardDeliveryService::STORAGE_DIR.'/abcdefghijklmnopqrstuvwxyz0123456789ABCD.pdf'
            )
        );
    }

    public function test_prepare_uses_whatsapp_display_name_not_raw_users_name(): void
    {
        $result = app(WhatsAppReportCardDeliveryService::class)
            ->prepareForStudent($this->parent, $this->student);

        $this->assertTrue(
            $result['ok'] ?? false,
            'prepareForStudent failed: '.json_encode($result)
        );
        $this->assertStringContainsString('AMINA NANSUBUGA', $result['caption']);
        $this->assertStringNotContainsString('3736', $result['caption']);
    }

    private function mockWhatsApp()
    {
        $whatsApp = Mockery::mock(WhatsAppBusinessService::class);
        $whatsApp->shouldReceive('sendList')
            ->zeroOrMoreTimes()
            ->andReturn(['success' => true, 'message_id' => 'list']);
        $whatsApp->shouldReceive('sendInteractiveButtons')
            ->zeroOrMoreTimes()
            ->andReturn(['success' => true, 'message_id' => 'btns']);

        return $whatsApp;
    }

    private function invokeProcessMeta(string $body): void
    {
        $controller = app(WhatsAppController::class);
        $method = new ReflectionMethod(WhatsAppController::class, 'processMetaMessage');
        $method->setAccessible(true);
        $method->invoke($controller, $this->parentPhone, $body, 'wamid.test', 'Parent');
    }
}
