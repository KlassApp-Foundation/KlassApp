<?php

namespace Tests\Feature\Reports;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\ActivityLog;
use App\Models\School;
use App\Models\SchoolDetail;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\Schools\SchoolIdentity;
use App\Services\StudentReportCardService;
use App\View\Composers\ReportIdentityComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SyntheticClass;
use Tests\TestCase;

/**
 * T-129 / T-130: the card reads the crest from the disk it is saved to, falls back to the school's
 * initials when the file is gone, and the head teacher's name is saved in school settings and given to
 * the card templates. Invented school and people.
 */
class CrestAndHeadTeacherTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/admin/school-identity';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([VerifyCsrfToken::class, MustBePrivilege::class]);
        config(['filesystems.default' => 'local']);
        Storage::fake('local');
        Storage::fake('public');
    }

    // ---- where the crest is read from -------------------------------------------------------

    public function test_a_crest_saved_to_the_default_disk_is_found(): void
    {
        $school = $this->school();
        $path = $this->saveCrest($school, 'local');

        $found = SchoolIdentity::crestPath((int) $school->id);

        $this->assertSame(Storage::disk('local')->path($path), $found);
    }

    public function test_a_crest_saved_by_older_code_on_the_public_disk_is_still_found(): void
    {
        $school = $this->school();
        $path = $this->saveCrest($school, 'public');

        $this->assertSame(Storage::disk('public')->path($path), SchoolIdentity::crestPath((int) $school->id));
    }

    public function test_the_card_service_alone_misses_a_crest_on_the_default_disk(): void
    {
        // The bug this fixes: the card read the "public" disk, the upload wrote the default disk.
        $school = $this->school();
        $this->saveCrest($school, 'local');

        $this->assertNull(app(StudentReportCardService::class)->resolveLogoPath((int) $school->id));
        $this->assertNotNull(SchoolIdentity::crestPath((int) $school->id));
    }

    public function test_a_missing_file_nothing_saved_a_placeholder_and_a_non_image_all_give_no_crest(): void
    {
        $school = $this->school();
        $this->assertNull(SchoolIdentity::crestPath((int) $school->id), 'nothing saved');

        SchoolDetail::create(['school_id' => $school->id, 'meta_key' => 'school_logo', 'meta_value' => '-']);
        $this->assertNull(SchoolIdentity::crestPath((int) $school->id), 'placeholder');

        SchoolDetail::where('school_id', $school->id)->where('meta_key', 'school_logo')->update(['meta_value' => 'gone/crest.png']);
        $this->assertNull(SchoolIdentity::crestPath((int) $school->id), 'file wiped by a deploy');

        Storage::disk('local')->put('notes/readme.png', 'this is text, not a picture');
        SchoolDetail::where('school_id', $school->id)->where('meta_key', 'school_logo')->update(['meta_value' => 'notes/readme.png']);
        $this->assertNull(SchoolIdentity::crestPath((int) $school->id), 'not an image');
    }

    public function test_another_schools_crest_is_never_used(): void
    {
        $school = $this->school();
        $other = $this->school();
        $this->saveCrest($other, 'local');

        $this->assertNull(SchoolIdentity::crestPath((int) $school->id));
        $this->assertNotNull(SchoolIdentity::crestPath((int) $other->id));
    }

    // ---- what the card templates get --------------------------------------------------------

    public function test_the_real_card_prints_the_crest_when_it_is_on_the_default_disk_and_the_initials_when_it_is_gone(): void
    {
        $class = SyntheticClass::build(['learners' => 3, 'counting_sets' => [1]]);
        $learner = array_values($class->learners)[0];

        $path = $this->saveCrest($class->school, 'local');
        $withCrest = $this->renderCard($class, $learner);
        $this->assertStringContainsString('<img class="crest" src="'.Storage::disk('local')->path($path).'"', $withCrest['html']);
        $this->assertStringNotContainsString('data-kind="monogram"', $withCrest['html']);

        Storage::disk('local')->delete($path); // a deploy wiped the upload
        $without = $this->renderCard($class, $learner);
        $this->assertStringNotContainsString('<img class="crest"', $without['html']);
        $this->assertStringContainsString('data-kind="monogram"', $without['html']);
        $this->assertStringContainsString('>SJS<', $without['html'], 'the school\'s initials');
    }

    public function test_the_head_teacher_name_reaches_the_card_templates(): void
    {
        $class = SyntheticClass::build(['learners' => 2, 'counting_sets' => [1]]);
        $learner = array_values($class->learners)[0];

        $this->assertNull($this->renderCard($class, $learner)['data']['headTeacherName']);

        SchoolDetail::create(['school_id' => $class->school->id, 'meta_key' => SchoolIdentity::HEAD_TEACHER_KEY, 'meta_value' => 'Ruth Example']);
        $card = $this->renderCard($class, $learner);
        $this->assertSame('Ruth Example', $card['data']['headTeacherName']);
        $this->assertStringContainsString('data-kind="head-teacher"', $card['html']);
        $this->assertStringContainsString('Ruth Example', $card['html']);
        foreach (['formal', 'warm'] as $template) {
            $html = view('admin.marks.report-templates.'.$template, $card['data'])->render();
            $this->assertStringContainsString('Ruth Example', $html, $template);
        }
    }

    public function test_the_composer_is_registered_for_every_report_template_and_uses_the_views_own_school(): void
    {
        $this->assertTrue(app('events')->hasListeners('composing: admin.marks.report-templates.*'));

        $school = $this->school();
        $other = $this->school();
        $this->saveCrest($other, 'local');
        SchoolDetail::create(['school_id' => $other->id, 'meta_key' => SchoolIdentity::HEAD_TEACHER_KEY, 'meta_value' => 'Other Head']);

        $view = view('errors.404', ['school' => $school, 'logoPath' => '/old/path.png']);
        (new ReportIdentityComposer)->compose($view);

        $this->assertSame('/old/path.png', $view->getData()['logoPath'], 'no crest for this school: what the card worked out stays');
        $this->assertNull($view->getData()['headTeacherName'], 'another school\'s head teacher is not used');
    }

    // ---- the settings screen ----------------------------------------------------------------

    public function test_the_screen_says_which_of_the_three_crest_states_the_school_is_in(): void
    {
        $school = $this->school();
        $admin = $this->user($school, 3);

        $this->actingAs($admin)->get(self::URL)->assertOk()->assertSee('No crest yet');

        $path = $this->saveCrest($school, 'local');
        $this->actingAs($admin)->get(self::URL)->assertOk()->assertSee('A crest is saved and will be printed');

        Storage::disk('local')->delete($path);
        $this->actingAs($admin)->get(self::URL)->assertOk()->assertSee('can no longer be found');
    }

    public function test_saving_the_head_teacher_name_and_a_crest_stores_them_for_this_school_only(): void
    {
        $school = $this->school();
        $other = $this->school();
        $admin = $this->user($school, 3);

        $this->actingAs($admin)->post(self::URL, [
            'head_teacher_name' => '  <b>Ruth Example</b>  ',
            'crest' => UploadedFile::fake()->image('crest.png', 40, 40),
        ])->assertRedirect(route('admin.school-identity'));

        $this->assertSame('Ruth Example', SchoolIdentity::headTeacherName((int) $school->id));
        $this->assertNull(SchoolIdentity::headTeacherName((int) $other->id));
        $stored = SchoolDetail::where('school_id', $school->id)->where('meta_key', 'school_logo')->value('meta_value');
        $this->assertTrue(Storage::disk('local')->exists($stored), 'the crest is on the default disk');
        $this->assertNotNull(SchoolIdentity::crestPath((int) $school->id));
        $this->assertSame(0, SchoolDetail::where('school_id', $other->id)->where('meta_key', 'school_logo')->where('meta_value', '!=', '-')->count());
        $this->assertTrue(ActivityLog::query()->where('log_name', 'setup')->where('causer_id', $admin->id)->exists());
    }

    public function test_an_empty_head_teacher_name_clears_it(): void
    {
        $school = $this->school();
        $admin = $this->user($school, 3);
        $this->actingAs($admin)->post(self::URL, ['head_teacher_name' => 'Ruth Example'])->assertRedirect();
        $this->actingAs($admin)->post(self::URL, ['head_teacher_name' => '   '])->assertRedirect();

        $this->assertNull(SchoolIdentity::headTeacherName((int) $school->id));
    }

    public function test_a_file_that_is_not_a_small_png_or_jpg_is_refused_and_nothing_is_saved(): void
    {
        $school = $this->school();
        $admin = $this->user($school, 3);

        foreach ([
            UploadedFile::fake()->create('crest.txt', 5, 'text/plain'),
            UploadedFile::fake()->create('crest.pdf', 5, 'application/pdf'),
            UploadedFile::fake()->image('big.png', 40, 40)->size(3000),
        ] as $file) {
            $this->actingAs($admin)->post(self::URL, ['crest' => $file])->assertSessionHasErrors('crest');
        }
        $this->assertSame(0, SchoolDetail::where('school_id', $school->id)->where('meta_key', 'school_logo')->where('meta_value', '!=', '-')->count());
        $this->actingAs($admin)->post(self::URL, ['head_teacher_name' => str_repeat('x', 121)])->assertSessionHasErrors('head_teacher_name');
    }

    public function test_a_head_teacher_can_open_school_details_and_save_the_name(): void
    {
        $school = $this->school();
        $head = $this->user($school, 4);

        $this->actingAs($head)->get(self::URL)->assertOk()->assertSee('data-testid="school-identity"', false);
        $this->actingAs($head)->post(self::URL, ['head_teacher_name' => 'Hugo Head'])->assertRedirect(route('admin.school-identity'));

        $this->assertSame('Hugo Head', SchoolIdentity::headTeacherName((int) $school->id));
    }

    public static function rolesRefused(): array
    {
        return ['teacher' => [5], 'student' => [6], 'parent' => [7], 'accountant' => [11]];
    }

    #[DataProvider('rolesRefused')]
    public function test_other_roles_cannot_change_these_details(int $group): void
    {
        $school = $this->school();
        $user = $this->user($school, $group);

        foreach ([$this->actingAs($user)->get(self::URL), $this->actingAs($user)->post(self::URL, ['head_teacher_name' => 'Nobody'])] as $response) {
            $this->assertContains($response->getStatusCode(), [302, 403, 404], 'role '.$group);
        }
        $this->assertNull(SchoolIdentity::headTeacherName((int) $school->id));
    }

    // ---- helpers ----------------------------------------------------------------------------

    /** @return array{html: string, data: array<string, mixed>} */
    private function renderCard(SyntheticClass $class, User $learner): array
    {
        $captured = null;
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('loadView')->andReturnUsing(function (string $view, array $data) use (&$captured, $pdf) {
            $captured = $data;

            return $pdf;
        });
        $pdf->shouldReceive('setPaper')->andReturnSelf();
        $pdf->shouldReceive('output')->andReturn('%PDF');
        $this->app->instance('dompdf.wrapper', $pdf);

        app(StudentReportCardService::class)->pdfForStudent((int) $class->school->id, $class->link, $learner);
        $view = view('admin.marks.report-templates.progressive', $captured);
        $html = $view->render();

        return ['html' => $html, 'data' => $view->getData()];
    }

    private function saveCrest(School $school, string $disk): string
    {
        $path = Storage::disk($disk)->putFile($school->slug.'/school_logo', UploadedFile::fake()->image('crest.png', 40, 40));
        SchoolDetail::updateOrCreate(['school_id' => $school->id, 'meta_key' => 'school_logo'], ['meta_value' => $path]);

        return $path;
    }

    private function school(): School
    {
        return School::create([
            'name' => 'Identity School '.uniqid(), 'slug' => 'id-'.uniqid(), 'email' => uniqid().'@id.test',
            'phone' => '073'.random_int(1000000, 9999999), 'status' => 1,
        ]);
    }

    private function user(School $school, int $group): User
    {
        DB::table('usergroups')->insertOrIgnore([
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'SchoolSubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'Parent', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'Accountant', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $user = User::create([
            'name' => 'Identity User '.uniqid(), 'email' => uniqid().'@id-user.test', 'password' => Hash::make('password123'),
            'usergroup_id' => $group, 'school_id' => $school->id, 'status' => 'active',
        ]);
        Userprofile::create(['user_id' => $user->id, 'school_id' => $school->id, 'usergroup_id' => $group, 'status' => 'active']);

        return $user;
    }
}
