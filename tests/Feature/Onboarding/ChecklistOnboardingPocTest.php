<?php

namespace Tests\Feature\Onboarding;

use App\Console\Commands\DemoChecklistResetCommand;
use App\Helpers\AuthRedirectHelper;
use App\Livewire\ChecklistOnboarding;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\OnboardingPlan;
use App\Models\School;
use App\Models\Section;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\TeacherInvite;
use App\Models\User;
use App\Services\Onboarding\ChecklistOnboardingGate;
use App\Services\Onboarding\ChecklistOnboardingService;
use App\Services\Onboarding\DemoChecklistWorkbook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ChecklistOnboardingPocTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->dir = sys_get_temp_dir().'/checklist-poc-'.bin2hex(random_bytes(4));
        mkdir($this->dir);
        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
        config(['toshi.checklist_onboarding_poc' => true]);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir.'/*') ?: [] as $file) {
            is_file($file) && unlink($file);
        }
        @rmdir($this->dir);
        parent::tearDown();
    }

    public function test_the_flag_stays_off_for_a_real_school(): void
    {
        $real = $this->school(false, false);
        $demo = $this->school(true, false, 'flagged-demo');
        $testSchool = $this->school(false, true, 'flagged-test');

        $gate = app(ChecklistOnboardingGate::class);
        $this->assertFalse($gate->enabledFor($real));
        $this->assertTrue($gate->enabledFor($demo));
        $this->assertTrue($gate->enabledFor($testSchool));

        config(['toshi.checklist_onboarding_poc' => false]);
        $this->assertFalse($gate->enabledFor($demo->fresh()));

        config(['toshi.checklist_onboarding_poc' => true]);
        $admin = $this->admin($real);
        $this->actingAs($admin);
        Livewire::test(ChecklistOnboarding::class)
            ->assertDontSee('Onboard from checklist')
            ->assertSet('enabled', false);
    }

    public function test_a_flagged_demo_admin_lands_on_the_dashboard(): void
    {
        [$school, $admin] = $this->demoAdmin();

        $this->assertSame('/admin/dashboard', AuthRedirectHelper::dashboardPathForUser($admin));

        config(['toshi.checklist_onboarding_poc' => false]);
        $this->assertStringContainsString(
            '/admin/onboarding/wizard',
            AuthRedirectHelper::dashboardPathForUser($admin->fresh()),
        );

        config(['toshi.checklist_onboarding_poc' => true]);
        $real = $this->admin($this->school(false, false, 'real-redirect'));
        $this->assertStringContainsString(
            '/admin/onboarding/wizard',
            AuthRedirectHelper::dashboardPathForUser($real),
        );
    }

    public function test_a_missing_data_processing_yes_names_the_tab_and_row_and_stores_nothing(): void
    {
        [$school, $admin] = $this->demoAdmin();
        $path = (new DemoChecklistWorkbook)->write($this->dir.'/dpa.xlsx', false, false, 'No');

        $result = app(ChecklistOnboardingService::class)->ingest($school, $admin, $path, 'dpa.xlsx');

        $this->assertFalse($result['ok']);
        $this->assertSame('dpa', $result['code']);
        $this->assertSame('refused', $result['card']['kind']);
        $this->assertSame('Confirmations', $result['card']['tab']);
        $this->assertSame(3, $result['card']['row']);
        $this->assertStringContainsString('Confirmations', $result['card']['message']);
        $this->assertStringContainsString('row 3', $result['card']['message']);
        $this->assertSame(0, OnboardingPlan::query()->count());
        $this->assertSame(0, AcademicYear::query()->where('school_id', $school->id)->count());
    }

    public function test_flags_name_the_bad_phone_and_the_unknown_class_with_tab_and_row(): void
    {
        [$school, $admin] = $this->demoAdmin();
        $path = (new DemoChecklistWorkbook)->write($this->dir.'/flags.xlsx', true, false, 'Yes');

        $result = app(ChecklistOnboardingService::class)->ingest($school, $admin, $path, 'flags.xlsx');

        $this->assertTrue($result['ok']);
        $this->assertSame('Northwind Harbour School', $result['card']['school_name']);
        $flags = $result['card']['flags'];
        $this->assertCount(2, $flags);
        $joined = json_encode($flags);
        $this->assertStringContainsString('12345', $joined);
        $this->assertStringContainsString('Primary Eight', $joined);
        $this->assertStringContainsString('Learners', $joined);
        foreach ($flags as $flag) {
            $this->assertNotNull($flag['row']);
            $this->assertNotSame('', $flag['tab']);
        }

        $plan = OnboardingPlan::query()->firstOrFail();
        $this->assertSame($school->id, $plan->school_id);
        $this->assertNotEmpty($plan->payload['steps']['students']['rows']);
        $this->assertSame(1, $plan->schema);

        $skipped = app(ChecklistOnboardingService::class)->skipFlags($plan, $admin);
        $this->assertSame('preview', $skipped['code']);
        $this->assertSame(0, User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->count());
    }

    public function test_preview_counts_and_each_step_apply_then_a_second_run_creates_nothing(): void
    {
        [$school, $admin] = $this->demoAdmin();
        $path = (new DemoChecklistWorkbook)->write($this->dir.'/clean.xlsx', false, false, 'Yes');
        $service = app(ChecklistOnboardingService::class);

        $first = $service->ingest($school, $admin, $path, 'clean.xlsx');
        $plan = OnboardingPlan::query()->findOrFail($first['plan_id']);
        $expected = [
            'terms' => [1, 0],
            'standards' => [1, 0],
            'subjects' => [1, 0],
            'teachers' => [1, 0],
            'students' => [2, 0],
        ];
        foreach ($expected as $key => [$create, $exists]) {
            $preview = $service->previewCard($plan->fresh(), $admin);
            $this->assertSame($key, $preview['step_key']);
            $this->assertSame($create, $preview['create'], $key.' create');
            $this->assertSame($exists, $preview['exists'], $key.' exists');
            $this->assertSame(0, $preview['conflict'], $key.' conflict');
            $applied = $service->apply($plan->fresh(), $admin);
            $this->assertTrue($applied['ok'], $key.' '.($applied['card']['message'] ?? ''));
            $this->assertSame($create, $applied['card']['created']);
        }

        $this->assertSame(1, AcademicTerm::query()->where('school_id', $school->id)->count());
        $this->assertSame(1, Section::query()->where('school_id', $school->id)->where('name', 'Baby Class')->count());
        $this->assertSame(1, Subject::query()->where('school_id', $school->id)->where('name', 'Mathematics')->count());
        $this->assertSame(1, User::query()->where('school_id', $school->id)->where('usergroup_id', 5)->count());
        $this->assertSame(2, User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->count());
        $this->assertSame(2, StudentAcademic::query()->where('school_id', $school->id)->count());
        $this->assertSame(2, User::query()->where('school_id', $school->id)->where('usergroup_id', 7)->count());
        $this->assertGreaterThan(0, TeacherInvite::query()->where('school_id', $school->id)->count());
        Mail::assertNothingSent();
        Mail::assertNothingQueued();

        $done = $service->previewCard($plan->fresh(), $admin);
        $this->assertSame('done', $done['kind']);
        $this->assertSame(2, $done['checks'][0]['actual']);
        $this->assertTrue($done['checks'][0]['match']);
        $this->assertTrue($done['kls_present']);
        $this->assertTrue($done['phones_e164']);
        $this->assertSame(TeacherInvite::query()->where('school_id', $school->id)->count(), $done['invites_ready']);

        $before = $this->counts($school);
        $second = $service->ingest($school, $admin, $path, 'clean.xlsx');
        $again = OnboardingPlan::query()->findOrFail($second['plan_id']);
        foreach (['terms', 'standards', 'subjects', 'teachers', 'students'] as $key) {
            $preview = $service->previewCard($again->fresh(), $admin);
            $this->assertSame($key, $preview['step_key']);
            $this->assertSame(0, $preview['create'], $key.' second create');
            $this->assertGreaterThan(0, $preview['exists'], $key.' second exists');
            $this->assertSame(0, $preview['conflict'], $key.' second conflict');
            $service->apply($again->fresh(), $admin);
        }
        $this->assertSame($before, $this->counts($school));
    }

    public function test_a_learner_step_that_fails_writes_nothing_from_that_step(): void
    {
        [$school, $admin] = $this->demoAdmin();
        $path = (new DemoChecklistWorkbook)->write($this->dir.'/bad-email.xlsx', false, false, 'Yes');
        $book = IOFactory::load($path);
        $book->getSheetByName('All Learners (for import)')->setCellValueExplicit('I4', 'not-an-email', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        IOFactory::createWriter($book, 'Xlsx')->save($path);

        $service = app(ChecklistOnboardingService::class);
        $plan = OnboardingPlan::query()->findOrFail($service->ingest($school, $admin, $path, 'bad-email.xlsx')['plan_id']);
        foreach (['terms', 'standards', 'subjects', 'teachers'] as $key) {
            $applied = $service->apply($plan->fresh(), $admin);
            $this->assertTrue($applied['ok'], $key);
        }

        $failed = $service->apply($plan->fresh(), $admin);
        $this->assertFalse($failed['ok']);
        $this->assertSame(0, User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->count());
        $this->assertSame(1, AcademicTerm::query()->where('school_id', $school->id)->count());
        $this->assertSame('students', $service->previewCard($plan->fresh(), $admin)['step_key']);
    }

    public function test_skipping_flagged_rows_imports_only_the_good_learners(): void
    {
        [$school, $admin] = $this->demoAdmin();
        $path = (new DemoChecklistWorkbook)->write($this->dir.'/skip.xlsx', true, false, 'Yes');
        $service = app(ChecklistOnboardingService::class);
        $plan = OnboardingPlan::query()->findOrFail($service->ingest($school, $admin, $path, 'skip.xlsx')['plan_id']);
        $service->skipFlags($plan, $admin);
        foreach (['terms', 'standards', 'subjects', 'teachers', 'students'] as $key) {
            $preview = $service->previewCard($plan->fresh(), $admin);
            $this->assertSame(0, $preview['conflict'], $key);
            $applied = $service->apply($plan->fresh(), $admin);
            $this->assertTrue($applied['ok'], $applied['card']['message'] ?? $key);
        }

        $this->assertSame(2, User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->count());
        $this->assertNull(User::query()->where('school_id', $school->id)->where('name', 'like', '%9000%')->first());
        $done = $service->previewCard($plan->fresh(), $admin);
        $this->assertTrue($done['checks'][0]['match']);
        Mail::assertNothingQueued();
    }

    public function test_the_demo_workbook_has_six_classes_and_two_flags_only(): void
    {
        $path = (new DemoChecklistWorkbook)->write($this->dir.'/full.xlsx', true, true, 'Yes');
        $converted = app(\App\Services\Onboarding\ChecklistWorkbookConverter::class)->convert($path);
        $errors = array_values(array_filter($converted['flags'], fn (array $flag): bool => ($flag['severity'] ?? 'error') === 'error'));

        $this->assertNull($converted['blocked']);
        $this->assertSame('Northwind Harbour School', $converted['school']);
        $this->assertCount(2, $errors);
        $this->assertCount(121, $converted['planRows']['students']);
        $this->assertCount(8, $converted['planRows']['teachers']);
        $this->assertCount(3, $converted['planRows']['terms']);
        $this->assertCount(8, $converted['planRows']['classes']);
        $classNames = array_values(array_unique(array_column($converted['planRows']['classes'], 'class')));
        $this->assertCount(6, $classNames);
    }

    public function test_reset_refuses_a_real_school_and_clears_only_the_demo_import(): void
    {
        $real = $this->school(false, false, DemoChecklistResetCommand::SLUG);
        $kept = $this->admin($real);
        $this->withPassword();
        $this->artisan('demo:checklist-reset')->assertExitCode(1);
        $this->assertNotNull(User::query()->find($kept->id));
        $this->assertSame(0, OnboardingPlan::query()->count());

        DB::table('userprofiles')->where('user_id', $kept->id)->delete();
        User::withTrashed()->whereKey($kept->id)->forceDelete();
        DB::table('schools')->where('id', $real->id)->delete();

        $bystander = $this->school(true, true, 'other-demo');
        $this->artisan('demo:checklist-reset')->assertExitCode(0);
        $school = School::query()->where('slug', DemoChecklistResetCommand::SLUG)->firstOrFail();
        $this->assertTrue((bool) $school->is_demo);
        $this->assertTrue((bool) $school->is_test);
        $this->assertSame(1, (int) $school->toshi_enabled);
        $this->assertSame(0, (int) $bystander->fresh()->toshi_enabled);
        $admin = User::query()->where('email', DemoChecklistResetCommand::ADMIN_EMAIL)->firstOrFail();
        $this->assertSame($school->id, $admin->school_id);

        $path = (new DemoChecklistWorkbook)->write($this->dir.'/reset.xlsx', false, false, 'Yes');
        $service = app(ChecklistOnboardingService::class);
        $plan = OnboardingPlan::query()->findOrFail($service->ingest($school, $admin, $path, 'reset.xlsx')['plan_id']);
        foreach (['terms', 'standards', 'subjects', 'teachers', 'students'] as $ignored) {
            $service->apply($plan->fresh(), $admin);
        }
        $this->assertSame(2, User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->count());

        $this->artisan('demo:checklist-reset')->assertExitCode(0);
        $this->assertSame(0, User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->count());
        $this->assertSame(0, AcademicYear::query()->where('school_id', $school->id)->count());
        $this->assertSame(0, OnboardingPlan::query()->where('school_id', $school->id)->count());
        $this->assertNotNull(User::query()->where('email', DemoChecklistResetCommand::ADMIN_EMAIL)->first());
        $this->artisan('demo:checklist-reset')
            ->expectsOutputToContain('northwind.admin@example.test')
            ->expectsOutputToContain('http://localhost:8000')
            ->doesntExpectOutputToContain('Reset-Only-1')
            ->doesntExpectOutputToContain('127.0.0.1')
            ->assertExitCode(0);
    }

    public function test_the_chat_card_reads_a_workbook_from_the_upload_control(): void
    {
        [$school, $admin] = $this->demoAdmin();
        $path = (new DemoChecklistWorkbook)->write($this->dir.'/upload.xlsx', true, false, 'Yes');
        $upload = new UploadedFile($path, 'upload.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($admin);
        $component = Livewire::test(ChecklistOnboarding::class)
            ->call('start')
            ->assertSee('I agree, continue')
            ->assertSee('Privacy')
            ->assertSee('Terms')
            ->set('workbook', $upload)
            ->call('ingest')
            ->assertDontSee('Northwind Harbour School');
        $this->assertSame(0, OnboardingPlan::query()->where('school_id', $school->id)->count());
        $component
            ->call('confirmAgreement')
            ->set('workbook', $upload)
            ->call('ingest')
            ->assertSee('Northwind Harbour School')
            ->assertSee('Learners')
            ->assertSee('Skip these rows')
            ->assertSee("Stop, I'll fix the workbook", false)
            ->assertSee('12345')
            ->assertSee('Primary Eight')
            ->assertSee('Correct the phone on that row, or skip the row.')
            ->call('skipFlags')
            ->assertSee('Create 1 term')
            ->assertSee('Use the term names and dates as written')
            ->assertSee('Change')
            ->assertSee('Skip step')
            ->assertDontSee('Confirm step');
    }

    public function test_a_google_sheet_link_asks_for_an_xlsx_download(): void
    {
        [, $admin] = $this->demoAdmin();
        $this->actingAs($admin);

        Livewire::test(ChecklistOnboarding::class)
            ->call('confirmAgreement')
            ->call('pasteSheetLink', 'https://docs.google.com/spreadsheets/d/abc/edit')
            ->assertSee('download the sheet as .xlsx', false)
            ->assertSee('File, then Download', false)
            ->assertDontSee('anyone with the link');
    }

    public function test_a_picked_google_sheet_is_read_like_an_upload_and_the_token_is_not_logged(): void
    {
        [$school, $admin] = $this->demoAdmin();
        $this->actingAs($admin);
        config([
            'services.google.client_id' => 'test-client.apps.googleusercontent.com',
            'services.google.picker_key' => 'test-picker-key',
            'services.google.project_number' => '123456',
        ]);
        $path = (new DemoChecklistWorkbook)->write($this->dir.'/picked.xlsx', true, false, 'Yes');
        $token = 'ya29.test-token-not-real';
        $loggedToken = false;
        Log::listen(function ($event) use (&$loggedToken, $token): void {
            $encoded = $event->message.json_encode($event->context);
            if (is_string($encoded) && str_contains($encoded, $token)) {
                $loggedToken = true;
            }
        });
        Http::preventStrayRequests();
        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($path) {
            if (str_contains($request->url(), '/export')) {
                return Http::response(
                    file_get_contents($path),
                    200,
                    ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                );
            }

            return Http::response(['mimeType' => 'application/vnd.google-apps.spreadsheet'], 200);
        });

        $card = Livewire::test(ChecklistOnboarding::class)
            ->call('confirmAgreement')
            ->assertSee('Connect Google Sheet')
            ->assertSee('https://www.googleapis.com/auth/drive.file')
            ->assertDontSee('https://www.googleapis.com/auth/drive.readonly')
            ->call('rememberSheetAccess', $token)
            ->call('importPickedSheet', '1aBcDeFgHiJkLmNoPqRs');

        $card->assertSee('Northwind Harbour School')
            ->assertDontSee($token);
        Http::assertSentCount(2);
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($token): bool {
            $this->assertStringNotContainsString($token, $request->url());
            $this->assertSame('Bearer '.$token, $request->header('Authorization')[0] ?? null);

            return str_contains($request->url(), '/export')
                && str_contains($request->url(), 'spreadsheetml.sheet');
        });
        Http::assertNotSent(fn (\Illuminate\Http\Client\Request $request): bool => str_contains($request->url(), 'alt=media'));
        $this->assertFalse($loggedToken);
        $this->assertNull(session('checklist_google_token'));
        $this->assertSame(0, User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->count());
        $this->assertSame(0, AcademicTerm::query()->where('school_id', $school->id)->count());

        Http::fake();
        Livewire::test(ChecklistOnboarding::class)
            ->call('confirmAgreement')
            ->call('importPickedSheet', '1aBcDeFgHiJkLmNoPqRs')
            ->assertSee('upload the .xlsx file');
        Http::assertNothingSent();
    }

    public function test_a_refused_google_sheet_does_not_write_and_is_not_retried(): void
    {
        [$school, $admin] = $this->demoAdmin();
        $this->actingAs($admin);
        $token = 'ya29.test-token-not-real';
        Http::preventStrayRequests();
        Http::fake([
            'https://www.googleapis.com/drive/v3/files/*' => Http::response(['error' => 'no'], 403),
        ]);

        Livewire::test(ChecklistOnboarding::class)
            ->call('confirmAgreement')
            ->call('rememberSheetAccess', $token)
            ->call('importPickedSheet', '1aBcDeFgHiJkLmNoPqRs')
            ->assertSee('could not be read')
            ->assertDontSee($token);

        Http::assertSentCount(1);
        $this->assertSame(0, OnboardingPlan::query()->where('school_id', $school->id)->count());
        $this->assertSame(0, User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->count());
    }

    public function test_a_picked_xlsx_file_is_downloaded_as_stored(): void
    {
        [$school, $admin] = $this->demoAdmin();
        $this->actingAs($admin);
        $path = (new DemoChecklistWorkbook)->write($this->dir.'/stored.xlsx', true, false, 'Yes');
        $token = 'ya29.test-token-not-real';
        Http::preventStrayRequests();
        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($path) {
            if (str_contains($request->url(), 'alt=media')) {
                return Http::response(file_get_contents($path), 200, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]);
            }

            return Http::response([
                'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ], 200);
        });

        Livewire::test(ChecklistOnboarding::class)
            ->call('confirmAgreement')
            ->assertSee('https://www.googleapis.com/auth/drive.file')
            ->call('rememberSheetAccess', $token)
            ->call('importPickedSheet', '1aBcDeFgHiJkLmNoPqRs')
            ->assertSee('Northwind Harbour School')
            ->assertSee('Primary Eight')
            ->assertDontSee($token);

        Http::assertSentCount(2);
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($token): bool {
            $this->assertStringNotContainsString($token, $request->url());
            $this->assertSame('Bearer '.$token, $request->header('Authorization')[0] ?? null);

            return str_contains($request->url(), 'alt=media')
                && ! str_contains($request->url(), '/export');
        });
        Http::assertNotSent(fn (\Illuminate\Http\Client\Request $request): bool => str_contains($request->url(), '/export'));
        $this->assertSame(0, User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->count());
        $this->assertNull(session('checklist_google_token'));
    }

    public function test_a_google_picker_error_code_is_shown_and_a_token_is_not(): void
    {
        [, $admin] = $this->demoAdmin();
        $this->actingAs($admin);

        Livewire::test(ChecklistOnboarding::class)
            ->call('confirmAgreement')
            ->call('noteSheet', 'google:access_denied')
            ->assertSee('access_denied')
            ->call('noteSheet', 'google:ya29.secret-token')
            ->assertDontSee('ya29')
            ->assertSee('Upload the .xlsx file');
    }

    public function test_a_class_without_a_teacher_asks_before_anything_is_written(): void
    {
        [$school, $admin] = $this->demoAdmin();
        $path = (new DemoChecklistWorkbook)->write($this->dir.'/noteacher.xlsx', false, false, 'Yes');
        $service = app(ChecklistOnboardingService::class);
        $plan = OnboardingPlan::query()->findOrFail($service->ingest($school, $admin, $path, 'noteacher.xlsx')['plan_id']);
        $payload = $plan->payload;
        $payload['walk_index'] = 3;
        $payload['steps']['teachers']['rows'][0]['links'] = [];
        $plan->payload = $payload;
        $plan->save();

        $card = $service->previewCard($plan->fresh(), $admin);

        $this->assertSame('teachers', $card['step_key']);
        $this->assertFalse($card['question']['blocks_write']);
        $this->assertSame('Leave it without one for now', $card['question']['answers'][0]['label']);
        $this->assertStringContainsString('has no teacher', $card['question']['prompt']);
    }

    public function test_two_stream_names_are_one_question_and_stay_on_the_sheet(): void
    {
        [$school, $admin] = $this->demoAdmin();
        $path = (new DemoChecklistWorkbook)->write($this->dir.'/streams.xlsx', false, false, 'Yes');
        $service = app(ChecklistOnboardingService::class);
        $plan = OnboardingPlan::query()->findOrFail($service->ingest($school, $admin, $path, 'streams.xlsx')['plan_id']);
        $service->apply($plan, $admin);
        $plan = $plan->fresh();
        $payload = $plan->payload;
        $payload['walk_index'] = 1;
        $payload['steps']['standards']['rows'] = [
            ['source' => ['tab' => 'Classes & Streams', 'row' => 3], 'name' => 'Baby Class', 'stream' => 'E'],
            ['source' => ['tab' => 'Classes & Streams', 'row' => 4], 'name' => 'Baby Class', 'stream' => 'East'],
        ];
        $plan->payload = $payload;
        $plan->save();

        $card = $service->previewCard($plan->fresh(), $admin);

        $this->assertSame('standards', $card['step_key']);
        $this->assertFalse($card['question']['blocks_write']);
        $this->assertSame('Keep both as written', $card['question']['answers'][0]['label']);
        $this->assertStringContainsString('E', $card['question']['prompt']);
        $this->assertStringContainsString('East', $card['question']['prompt']);
    }

    public function test_plain_requests_stay_on_the_plan_and_a_typed_yes_writes_nothing(): void
    {
        [$school, $admin] = $this->demoAdmin();
        $this->actingAs($admin);
        config([
            'ai.providers.openai-compatible.key' => '',
            'toshi.api_key' => '',
        ]);

        Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('input', 'set up my school')
            ->call('send')
            ->assertDispatched('checklist-onboarding-open')
            ->assertSee('data agreement');

        Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('input', "here's our checklist")
            ->call('send')
            ->assertDispatched('checklist-onboarding-open');

        $talk = app(\App\Services\Onboarding\ChecklistConversation::class);
        $before = $talk->hear('how many learners?', null, $school->name);
        $this->assertStringContainsString("don't know", $before['say']);
        $this->assertSame(0, User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->count());

        $path = (new DemoChecklistWorkbook)->write($this->dir.'/talk.xlsx', true, true, 'Yes');
        $plan = OnboardingPlan::query()->findOrFail(
            app(ChecklistOnboardingService::class)->ingest($school, $admin, $path, 'talk.xlsx')['plan_id']
        );
        $brief = app(ChecklistOnboardingService::class)->conversationBrief($plan);
        $learners = (int) collect($brief['stats'])->firstWhere('label', 'Learners')['value'];
        $teacher = $brief['teachers'][0];

        $count = $talk->hear('is it 999 learners?', $plan->fresh(), $school->name);
        $this->assertStringContainsString((string) $learners, $count['say']);
        $this->assertStringNotContainsString('999', $count['say']);

        $who = $talk->hear('who is '.$teacher.'?', $plan->fresh(), $school->name);
        $this->assertStringContainsString($teacher, $who['say']);
        $this->assertStringNotContainsString('999', $who['say']);

        $unknown = $talk->hear('what is the head cook\'s favourite colour?', $plan->fresh(), $school->name);
        $this->assertStringContainsString("don't know that from this checklist", $unknown['say']);
        $this->assertStringContainsString('button', $unknown['say']);

        $yes = $talk->hear('yes', $plan->fresh(), $school->name);
        $this->assertStringContainsString('button', $yes['say']);
        $this->assertFalse($yes['open']);
        $this->assertSame(0, User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->count());
        $this->assertSame(0, AcademicTerm::query()->where('school_id', $school->id)->count());

        $why = $talk->hear('why this step?', $plan->fresh(), $school->name);
        $this->assertStringContainsString($brief['step']['proposal'], $why['say']);
        $this->assertStringContainsString($brief['step']['approve_label'], $why['say']);
    }

    public function test_a_question_uses_one_chat_call_and_typed_text_writes_nothing(): void
    {
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        [$school, $admin] = $this->demoAdmin();
        $this->actingAs($admin);
        config([
            'ai.providers.openai-compatible.url' => 'https://llm.test',
            'ai.providers.openai-compatible.key' => 'test-key-not-real',
            'toshi.model' => 'gemini-3.5-flash',
            'ai.providers.openai-compatible.models.text.default' => 'gemini-3.5-flash',
        ]);

        $path = (new DemoChecklistWorkbook)->write($this->dir.'/narrate.xlsx', true, true, 'Yes');
        $plan = OnboardingPlan::query()->findOrFail(
            app(ChecklistOnboardingService::class)->ingest($school, $admin, $path, 'narrate.xlsx')['plan_id']
        );
        $brief = app(ChecklistOnboardingService::class)->conversationBrief($plan);
        $learners = (int) collect($brief['stats'])->firstWhere('label', 'Learners')['value'];

        $calls = 0;
        \Illuminate\Support\Facades\Http::fake(function () use (&$calls, $learners) {
            $calls++;
            if ($calls === 1) {
                return \Illuminate\Support\Facades\Http::response([
                    'id' => 'narr-1',
                    'model' => 'gemini-3.5-flash',
                    'choices' => [[
                        'index' => 0,
                        'message' => ['role' => 'assistant', 'content' => 'The checklist has '.$learners.' learners.'],
                        'finish_reason' => 'stop',
                    ]],
                ]);
            }
            if ($calls === 2) {
                return \Illuminate\Support\Facades\Http::response([
                    'id' => 'narr-2',
                    'model' => 'gemini-3.5-flash',
                    'choices' => [[
                        'index' => 0,
                        'message' => ['role' => 'assistant', 'content' => 'There are 999 learners.'],
                        'finish_reason' => 'stop',
                    ]],
                ]);
            }
            if ($calls === 3) {
                return \Illuminate\Support\Facades\Http::response([
                    'error' => ['message' => 'rate limit'],
                ], 429);
            }

            throw new \Illuminate\Http\Client\ConnectionException('cURL error 28');
        });

        $answered = Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('input', 'is it 999 learners?')
            ->call('send');
        $bots = $this->botText($answered);
        $this->assertStringContainsString((string) $learners, $bots);
        $this->assertStringNotContainsString('999', $bots);
        $this->assertSame(0, User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->count());
        $this->assertSame(0, AcademicTerm::query()->where('school_id', $school->id)->count());

        \Illuminate\Support\Facades\Http::assertSentCount(1);
        \Illuminate\Support\Facades\Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($learners): bool {
            $data = $request->data();
            $this->assertArrayNotHasKey('tools', $data);
            $this->assertSame('minimal', $data['reasoning_effort'] ?? null);
            $encoded = json_encode($data);
            $this->assertIsString($encoded);
            $this->assertStringContainsString((string) $learners, $encoded);
            $this->assertStringNotContainsString('12345', $encoded);
            $this->assertStringNotContainsString('Example Parent', $encoded);
            $this->assertStringNotContainsString('0712000000', $encoded);
            $system = collect($data['messages'] ?? [])->firstWhere('role', 'system')['content'] ?? '';
            $this->assertStringNotContainsString('999', $system);

            return str_ends_with($request->url(), '/chat/completions');
        });

        $rejected = Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('input', 'how many learners?')
            ->call('send');
        $rejectedBots = $this->botText($rejected);
        $this->assertStringContainsString('Learners: '.$learners, $rejectedBots);
        $this->assertStringNotContainsString('999', $rejectedBots);

        $limited = Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('input', 'how many learners?')
            ->call('send');
        $this->assertStringContainsString('not retried', $this->botText($limited));
        \Illuminate\Support\Facades\Http::assertSentCount(3);
        $this->assertSame(0, User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->count());

        $timedOut = Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('input', 'how many learners?')
            ->call('send');
        $this->assertStringContainsString('Learners: '.$learners, $this->botText($timedOut));
        $this->assertStringNotContainsString('cURL', $this->botText($timedOut));

        config([
            'ai.providers.openai-compatible.key' => '',
            'toshi.api_key' => '',
        ]);
        \Illuminate\Support\Facades\Http::fake();
        $quiet = Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('input', 'how many learners?')
            ->call('send');
        $this->assertStringContainsString('Learners: '.$learners, $this->botText($quiet));
        \Illuminate\Support\Facades\Http::assertNothingSent();

        $yes = Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('input', 'yes')
            ->call('send');
        $this->assertStringContainsString('button', $this->botText($yes));
        \Illuminate\Support\Facades\Http::assertNothingSent();
        $this->assertSame(0, AcademicTerm::query()->where('school_id', $school->id)->count());
    }

    public function test_the_checklist_panel_skips_the_old_setup_and_does_not_call_a_model(): void
    {
        \Illuminate\Support\Facades\Http::fake();
        [$school, $admin] = $this->demoAdmin();
        $school->forceFill(['toshi_enabled' => 1])->save();
        config([
            'ai.providers.openai-compatible.key' => 'test-key-not-real',
            'toshi.api_key' => 'test-key-not-real',
        ]);
        $this->actingAs($admin);

        Livewire::test(\App\Livewire\AgentToshi::class)
            ->assertDontSee("Let's finish setting up")
            ->assertDontSee('things to set up')
            ->set('input', 'thanks')
            ->call('send')
            ->assertSee("don't know that from this checklist");

        \Illuminate\Support\Facades\Http::assertNothingSent();
        $this->assertSame(0, User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->count());
    }

    public function test_a_rate_limit_is_announced_once_and_not_retried(): void
    {
        $limited = \Laravel\Ai\Exceptions\RateLimitedException::forProvider('openai-compatible', 429);
        $this->assertTrue(\App\Services\Toshi\ToshiRateLimit::isLimited($limited));
        $this->assertFalse(\App\Services\Toshi\ToshiRateLimit::isLimited(new \RuntimeException('ordinary failure')));
        $message = \App\Services\Toshi\ToshiRateLimit::adminMessage();
        $this->assertStringContainsString('Nothing was written', $message);
        $this->assertStringContainsString('not retried', $message);
    }

    /**
     * @return array{0: School, 1: User}
     */
    private function demoAdmin(): array
    {
        $school = $this->school(true, true);
        $school->forceFill(['registration_country' => 'Kenya'])->save();

        return [$school, $this->admin($school)];
    }

    private function school(bool $demo, bool $test, string $slug = ''): School
    {
        $slug = $slug !== '' ? $slug : 'poc-'.bin2hex(random_bytes(3));
        $school = School::query()->create([
            'name' => 'School '.$slug,
            'email' => $slug.'@northwind.example',
            'phone' => '+2547'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'slug' => $slug,
            'status' => 1,
            'registration_country' => 'Kenya',
            'toshi_enabled' => 0,
        ]);
        $school->refresh();
        $school->forceFill([
            'slug' => $slug,
            'is_demo' => $demo,
            'is_test' => $test,
            'toshi_mode' => 'preview',
            'toshi_enabled' => 0,
        ])->save();

        return $school->fresh();
    }

    private function admin(School $school): User
    {
        return User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Northwind Admin',
            'email' => $school->slug.'-admin@example.test',
            'status' => 'active',
        ]);
    }

    private function botText(\Livewire\Features\SupportTesting\Testable $component): string
    {
        return collect($component->get('messages'))
            ->where('role', 'bot')
            ->pluck('text')
            ->implode("\n");
    }

    private function withPassword(): void
    {
        $password = 'Reset-Only-1';
        putenv('DEMO_CHECKLIST_ADMIN_PASSWORD='.$password);
        $_ENV['DEMO_CHECKLIST_ADMIN_PASSWORD'] = $password;
        $_SERVER['DEMO_CHECKLIST_ADMIN_PASSWORD'] = $password;
    }

    /**
     * @return array<string, int>
     */
    private function counts(School $school): array
    {
        return [
            'years' => AcademicYear::query()->where('school_id', $school->id)->count(),
            'terms' => AcademicTerm::query()->where('school_id', $school->id)->count(),
            'sections' => Section::query()->where('school_id', $school->id)->count(),
            'subjects' => Subject::query()->where('school_id', $school->id)->count(),
            'teachers' => User::query()->where('school_id', $school->id)->where('usergroup_id', 5)->count(),
            'students' => User::query()->where('school_id', $school->id)->where('usergroup_id', 6)->count(),
            'parents' => User::query()->where('school_id', $school->id)->where('usergroup_id', 7)->count(),
            'invites' => TeacherInvite::query()->where('school_id', $school->id)->count(),
        ];
    }
}
