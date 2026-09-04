<?php

namespace Tests\Feature\Reports;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\Marks;
use App\Models\School;
use App\Models\SchoolDetail;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Report-card footers/headers must be the rendering school's identity —
 * never another school's hardcoded Kabale copy.
 */
class ReportCardSchoolIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        DB::table('exam_types')->upsert([
            ['id' => 1, 'name' => 'Mid Term', 'code' => 'MID', 'contributes_to_report_total' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_resolve_school_identity_is_per_school_and_omits_blank_uneb(): void
    {
        $service = app(StudentReportCardService::class);

        $alpha = School::create([
            'name' => 'Alpha Primary Academy',
            'email' => 'alpha-id@t.sch.ug',
            'phone' => '+256700111111 / +256700222222',
            'address' => 'P.O Box 10 - Kampala - UGA',
            'slug' => 'alpha-id-'.uniqid(),
            'status' => 1,
            'school_category' => 'primary_nursery',
            'uneb_center_number' => 'U1001/001',
            'report_template' => 'formal',
            'registration_country' => 'Uganda',
        ]);
        SchoolDetail::updateOrCreate(
            ['school_id' => $alpha->id, 'meta_key' => 'moto'],
            ['meta_value' => 'Alpha Motto Forever']
        );
        SchoolDetail::updateOrCreate(
            ['school_id' => $alpha->id, 'meta_key' => 'landline_no'],
            ['meta_value' => '+256700333333']
        );

        $beta = School::create([
            'name' => 'Beta Secondary College',
            'email' => 'beta-id@t.sch.ug',
            'phone' => '+256711444444',
            'address' => 'P.O Box 99 - Entebbe - UGA',
            'slug' => 'beta-id-'.uniqid(),
            'status' => 1,
            'school_category' => 'o_level',
            'uneb_center_number' => '-',
            'report_template' => 'formal',
            'registration_country' => 'Uganda',
        ]);
        SchoolDetail::updateOrCreate(
            ['school_id' => $beta->id, 'meta_key' => 'moto'],
            ['meta_value' => 'Beta Excellence']
        );

        $alphaId = $service->resolveSchoolIdentity($alpha->fresh());
        $betaId = $service->resolveSchoolIdentity($beta->fresh());

        $this->assertSame('Alpha Primary Academy', $alphaId['name']);
        $this->assertSame('(Nursery And Primary)', $alphaId['category_subtitle']);
        $this->assertSame('P.O Box 10 - Kampala - UGA', $alphaId['address']);
        $this->assertSame(['+256700111111', '+256700222222', '+256700333333'], $alphaId['phones']);
        $this->assertSame('Tel: +256700111111 / +256700222222 / +256700333333', $alphaId['phones_line']);
        $this->assertSame('U1001/001', $alphaId['uneb_center_number']);
        $this->assertSame('Alpha Motto Forever', $alphaId['motto']);
        $this->assertStringContainsString('Alpha Primary Academy, UNEB Center No. U1001/001', $alphaId['footer_line']);
        $this->assertStringContainsString('Tel: +256700111111', $alphaId['footer_line']);

        $this->assertSame('Beta Secondary College', $betaId['name']);
        $this->assertSame('(O-Level)', $betaId['category_subtitle']);
        $this->assertSame('P.O Box 99 - Entebbe - UGA', $betaId['address']);
        $this->assertSame(['+256711444444'], $betaId['phones']);
        $this->assertNull($betaId['uneb_center_number']);
        $this->assertSame('Beta Excellence', $betaId['motto']);
        $this->assertSame('Beta Secondary College Tel: +256711444444', $betaId['footer_line']);
        $this->assertStringNotContainsString('UNEB Center No.', $betaId['footer_line']);

        $this->assertNotSame($alphaId['footer_line'], $betaId['footer_line']);
        $this->assertStringNotContainsString('Kabale', $alphaId['footer_line']);
        $this->assertStringNotContainsString('Kabale', $betaId['footer_line']);
        $this->assertStringNotContainsString('+256782255758', $alphaId['footer_line'].$betaId['footer_line']);
        $this->assertStringNotContainsString('HARD WORK PAYS', $alphaId['motto'].$betaId['motto']);
    }

    public function test_report_templates_have_no_hardcoded_kabale_identity(): void
    {
        foreach (['formal', 'warm', 'modern'] as $template) {
            $src = file_get_contents(resource_path("views/admin/marks/report-templates/{$template}.blade.php"));
            $this->assertIsString($src);
            $this->assertStringContainsString('schoolIdentity', $src);
            $this->assertStringNotContainsString('Kabale Junior School', $src);
            $this->assertStringNotContainsString('P.O Box 283 - Kabale - UGA', $src);
            $this->assertStringNotContainsString('+256782255758', $src);
            $this->assertStringNotContainsString('+256784119149', $src);
            $this->assertStringNotContainsString('+256704301646', $src);
            $this->assertStringNotContainsString('HARD WORK PAYS', $src);
            $this->assertStringNotContainsString('Day And Boarding', $src);
        }
    }

    public function test_pdfs_for_two_schools_contain_distinct_identity_not_kabale(): void
    {
        $alpha = $this->seedSchoolWithReportableStudent([
            'name' => 'Alpha Primary Academy',
            'email' => 'alpha-pdf@t.sch.ug',
            'phone' => '+256700111111',
            'address' => 'P.O Box 10 - Kampala - UGA',
            'slug' => 'alpha-pdf-'.uniqid(),
            'school_category' => 'primary_nursery',
            'uneb_center_number' => 'U1001/001',
            'motto' => 'Alpha Motto Forever',
            'student_name' => 'Alpha Student One',
            'student_email' => 'alpha.student@t.sch.ug',
        ]);

        $beta = $this->seedSchoolWithReportableStudent([
            'name' => 'Beta Secondary College',
            'email' => 'beta-pdf@t.sch.ug',
            'phone' => '+256711444444',
            'address' => 'P.O Box 99 - Entebbe - UGA',
            'slug' => 'beta-pdf-'.uniqid(),
            'school_category' => 'o_level',
            'uneb_center_number' => 'U2002/002',
            'motto' => 'Beta Excellence',
            'student_name' => 'Beta Student Two',
            'student_email' => 'beta.student@t.sch.ug',
        ]);

        $service = app(StudentReportCardService::class);
        $alphaIdentity = $service->resolveSchoolIdentity($alpha['school']->fresh());
        $betaIdentity = $service->resolveSchoolIdentity($beta['school']->fresh());

        $this->assertNotSame($alphaIdentity['footer_line'], $betaIdentity['footer_line']);
        $this->assertStringContainsString('P.O Box 10 - Kampala - UGA', $alphaIdentity['address']);
        $this->assertStringContainsString('P.O Box 99 - Entebbe - UGA', $betaIdentity['address']);

        $pdfAlpha = $service->pdfForStudent($alpha['school']->id, $alpha['stdLink'], $alpha['student']);
        $pdfBeta = $service->pdfForStudent($beta['school']->id, $beta['stdLink'], $beta['student']);

        $this->assertStringStartsWith('%PDF', $pdfAlpha);
        $this->assertStringStartsWith('%PDF', $pdfBeta);

        $haystackAlpha = $this->pdfReadableHaystack($pdfAlpha);
        $haystackBeta = $this->pdfReadableHaystack($pdfBeta);

        $kabaleLeaks = [
            'Kabale Junior School',
            'P.O Box 283 - Kabale - UGA',
            '+256782255758',
            '+256784119149',
            '+256704301646',
            'HARD WORK PAYS',
            'Day And Boarding',
        ];

        foreach ([$haystackAlpha, $haystackBeta] as $haystack) {
            foreach ($kabaleLeaks as $leak) {
                $this->assertStringNotContainsString($leak, $haystack);
            }
        }

        $this->assertStringContainsString('Alpha Primary Academy', $haystackAlpha);
        $this->assertStringContainsString('Alpha Motto Forever', $haystackAlpha);
        $this->assertStringContainsString('U1001/001', $haystackAlpha);
        $this->assertStringNotContainsString('Beta Secondary College', $haystackAlpha);

        $this->assertStringContainsString('Beta Secondary College', $haystackBeta);
        $this->assertStringContainsString('Beta Excellence', $haystackBeta);
        $this->assertStringContainsString('U2002/002', $haystackBeta);
        $this->assertStringNotContainsString('Alpha Primary Academy', $haystackBeta);

        $dir = storage_path('app/testing/report-identity');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($dir.'/alpha-formal.pdf', $pdfAlpha);
        file_put_contents($dir.'/beta-formal.pdf', $pdfBeta);
    }

    /**
     * DomPDF stores page text in FlateDecode streams and often splits phrases
     * across show-text operators. Decompress streams and join parenthesized
     * PDF string literals so identity assertions can read real content.
     */
    private function pdfReadableHaystack(string $pdf): string
    {
        $chunks = [$pdf];

        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $matches)) {
            foreach ($matches[1] as $stream) {
                $decoded = @gzuncompress($stream);
                if ($decoded === false) {
                    $decoded = @gzinflate($stream);
                }
                if (is_string($decoded) && $decoded !== '') {
                    $chunks[] = $decoded;
                }
            }
        }

        $joined = implode("\n", $chunks);

        if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/', $joined, $literals)) {
            $text = '';
            foreach ($literals[0] as $literal) {
                $inner = substr($literal, 1, -1);
                $inner = str_replace(
                    ['\\(', '\\)', '\\n', '\\r', '\\t', '\\\\'],
                    ['(', ')', "\n", "\r", "\t", '\\'],
                    $inner
                );
                $text .= $inner;
            }
            $joined .= "\n".$text;
        }

        return $joined;
    }

    /**
     * @param  array<string, string>  $cfg
     * @return array{school: School, stdLink: StandardLink, student: User}
     */
    private function seedSchoolWithReportableStudent(array $cfg): array
    {
        $school = School::create([
            'name' => $cfg['name'],
            'email' => $cfg['email'],
            'phone' => $cfg['phone'],
            'address' => $cfg['address'],
            'slug' => $cfg['slug'],
            'status' => 1,
            'school_category' => $cfg['school_category'],
            'uneb_center_number' => $cfg['uneb_center_number'],
            'report_template' => 'formal',
            'registration_country' => 'Uganda',
        ]);

        SchoolDetail::updateOrCreate(
            ['school_id' => $school->id, 'meta_key' => 'moto'],
            ['meta_value' => $cfg['motto']]
        );

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ]);

        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'status' => 'current',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-04-30',
        ]);

        $standard = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary_lower',
            'order' => 1,
            'status' => '1',
        ]);

        $section = Section::create([
            'school_id' => $school->id,
            'name' => 'P.2 '.$school->id,
            'status' => 1,
        ]);

        $teacher = User::factory()->create([
            'usergroup_id' => 5,
            'school_id' => $school->id,
            'email' => 'ct.'.$school->id.'@t.sch.ug',
        ]);

        $stdLink = StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'class_teacher_id' => $teacher->id,
            'status' => '1',
        ]);

        $subject = Subject::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'name' => 'English',
            'code' => 'ENG',
            'type' => 'core',
            'status' => 1,
        ]);

        $eot = Exam::withoutEvents(fn () => Exam::create([
            'school_id' => $school->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'exam_type_id' => 2,
            'academic_term_id' => $term->id,
            'academic_year_id' => $year->id,
            'scheduled_at' => '2026-04-01',
            'status' => 'submitted',
        ]));

        $student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $school->id,
            'name' => $cfg['student_name'],
            'email' => $cfg['student_email'],
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'user_id' => $student->id,
            'standardLink_id' => $stdLink->id,
        ]);

        Marks::create([
            'student_id' => $student->id,
            'exam_id' => $eot->id,
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'section_id' => $section->id,
            'marks' => 72,
            'grade' => 'D1',
        ]);

        return [
            'school' => $school,
            'stdLink' => $stdLink,
            'student' => $student,
        ];
    }
}
