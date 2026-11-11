<?php

namespace Tests\Feature\Marks;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\Marks\KlsFiller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * The filler copies a KLS number only when one active learner in that class
 * matches. Near-misses and duplicates are listed and left blank.
 */
class KlsFillerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'schoolsubadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_an_exact_name_is_filled_and_everything_else_is_listed(): void
    {
        $school = $this->school('Filler School');
        $other = $this->school('Other Filler School');
        $this->year($other);
        $head = $this->person($school, 4);
        $admin = $this->person($school, 3);
        $teacher = $this->person($school, 5);
        $otherAdmin = $this->person($other, 3);
        $year = $this->year($school);
        $standard = Standard::create([
            'school_id' => $school->id, 'name' => 'Lower', 'order' => 1, 'status' => 1, 'grading_style' => 'aggregate',
        ]);
        $yearFive = Section::create(['school_id' => $school->id, 'name' => 'Year 5', 'status' => 1]);
        $yearSix = Section::create(['school_id' => $school->id, 'name' => 'Year 6', 'status' => 1]);
        $linkFive = $this->link($school, $year, $standard, $yearFive);
        $linkSix = $this->link($school, $year, $standard, $yearSix);

        $amina = $this->learner($school, $linkFive, 'login.amina', 'KLS0010001', 'Amina', 'North');
        $samOne = $this->learner($school, $linkFive, 'Sam Lee', 'KLS0010002');
        $samTwo = $this->learner($school, $linkFive, 'Sam Lee', 'KLS0010003');
        $this->learner($school, $linkFive, 'No Number', null);
        $ada = $this->learner($school, $linkFive, 'Ada Lovelace', 'KLS0010004');
        $bea = $this->learner($school, $linkFive, 'Bea Stone', 'KLS0010005');
        $inactive = $this->learner($school, $linkFive, 'Ina Active', 'KLS0010006');
        $inactive->forceFill(['status' => 'inactive'])->save();
        $elsewhere = $this->learner($school, $linkSix, 'Cara West', 'KLS0010007');

        $path = $this->workbook([
            'Year 5' => [
                ['', 'Amina   North'],
                ['', 'Amina Nort'],
                ['', 'Sam Lee'],
                ['', 'No Number'],
                ['KLS0099999', 'Ada Lovelace'],
                ['', 'Ada Lovelace'],
                ['', 'Bea Stone'],
                ['', 'Bea Stone'],
                ['', 'Ina Active'],
                ['', 'Cara West'],
            ],
            'Year 6' => [
                ['', 'Cara West'],
            ],
            'Not A Class' => [
                ['', 'Amina North'],
            ],
            'Instructions' => [
                ['', 'Ignore this sheet'],
            ],
        ], klsHeader: ['Year 5', 'Year 6', 'Not A Class']);

        $result = app(KlsFiller::class)->fill($head, $path);
        $byOutcome = [];
        foreach ($result['rows'] as $row) {
            $byOutcome[$row['outcome']][] = $row;
        }

        $this->assertSame('KLS0010001', $this->cell($path, 'Year 5', 3));
        $this->assertSame('', $this->cell($path, 'Year 5', 4));
        $this->assertSame('', $this->cell($path, 'Year 5', 5));
        $this->assertSame('', $this->cell($path, 'Year 5', 6));
        $this->assertSame('KLS0099999', $this->cell($path, 'Year 5', 7));
        $this->assertSame('', $this->cell($path, 'Year 5', 8));
        $this->assertSame('', $this->cell($path, 'Year 5', 9));
        $this->assertSame('', $this->cell($path, 'Year 5', 10));
        $this->assertSame('', $this->cell($path, 'Year 5', 11));
        $this->assertSame('', $this->cell($path, 'Year 5', 12));
        $this->assertSame('KLS0010007', $this->cell($path, 'Year 6', 3));
        $this->assertSame('', $this->cell($path, 'Not A Class', 3));

        $this->assertSame(2, $result['filled']);
        $this->assertArrayHasKey('no_match', $byOutcome);
        $ambiguous = $byOutcome['ambiguous'][0];
        $this->assertCount(2, $ambiguous['candidates']);
        $this->assertEqualsCanonicalizing(
            ['KLS0010002', 'KLS0010003'],
            array_column($ambiguous['candidates'], 'kls'),
        );
        $this->assertSame('learner_has_no_kls', $byOutcome['learner_has_no_kls'][0]['outcome']);
        $this->assertSame('already_has_different_kls', $byOutcome['already_has_different_kls'][0]['outcome']);
        $this->assertCount(3, $byOutcome['duplicate_learner']);
        $this->assertSame('unknown_class', $byOutcome['unknown_class'][0]['outcome']);
        $this->assertSame((int) $amina->id, (int) $byOutcome['filled'][0]['learner_id']);
        $this->assertNotSame((int) $elsewhere->id, (int) $byOutcome['filled'][0]['learner_id']);

        $again = app(KlsFiller::class)->fill($admin, $path);
        $this->assertSame(0, $again['filled']);
        $this->assertSame(2, $again['unchanged']);
        $this->assertSame('KLS0010001', $this->cell($path, 'Year 5', 3));
        $this->assertSame('KLS0010007', $this->cell($path, 'Year 6', 3));

        try {
            app(KlsFiller::class)->fill($teacher, $path);
            $this->fail('a teacher must not fill KLS numbers');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $foreign = $this->workbook([
            'Year 5' => [['', 'Amina North']],
        ], klsHeader: ['Year 5']);
        $foreignResult = app(KlsFiller::class)->fill($otherAdmin, $foreign);
        $this->assertSame('unknown_class', $foreignResult['rows'][0]['outcome']);
        $this->assertSame('', $this->cell($foreign, 'Year 5', 3));
        $this->assertSame([], $foreignResult['rows'][0]['candidates']);
    }

    public function test_more_than_one_current_year_writes_nothing(): void
    {
        $school = $this->school('Two Year School');
        $admin = $this->person($school, 3);
        $this->year($school, '2025');
        $this->year($school, '2026');
        $path = $this->workbook(['Year 5' => [['', 'Amina North']]], klsHeader: ['Year 5']);

        try {
            app(KlsFiller::class)->fill($admin, $path);
            $this->fail('two current years must be refused');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('academic_year', $e->errors());
        }

        $this->assertSame('', $this->cell($path, 'Year 5', 3));
    }

    private function school(string $name): School
    {
        return School::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'-'.uniqid().'@example.test',
            'phone' => '07'.random_int(10000000, 99999999),
            'slug' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid(),
            'status' => 1,
            'registration_country' => 'Kenya',
        ]);
    }

    private function person(School $school, int $group): User
    {
        return User::factory()->create([
            'usergroup_id' => $group,
            'school_id' => $school->id,
            'email' => 'u.'.uniqid().'@example.test',
            'status' => 'active',
        ]);
    }

    private function year(School $school, string $name = '2026'): AcademicYear
    {
        return AcademicYear::create([
            'school_id' => $school->id,
            'name' => $name,
            'description' => 'Year '.$name,
            'start_date' => $name.'-01-06',
            'end_date' => $name.'-12-15',
            'status' => 1,
        ]);
    }

    private function link(School $school, AcademicYear $year, Standard $standard, Section $section): StandardLink
    {
        return StandardLink::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'standard_id' => $standard->id,
            'section_id' => $section->id,
            'stream' => null,
            'status' => 1,
        ]);
    }

    private function learner(School $school, StandardLink $link, string $name, ?string $kls, ?string $first = null, ?string $last = null): User
    {
        $student = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $school->id,
            'name' => $name,
            'email' => 's.'.uniqid().'@example.test',
            'status' => 'active',
            'registration_number' => $kls,
        ]);

        if ($first !== null) {
            Userprofile::create([
                'school_id' => $school->id,
                'user_id' => $student->id,
                'usergroup_id' => 6,
                'firstname' => $first,
                'lastname' => $last ?? '',
                'gender' => 'female',
                'status' => 'active',
            ]);
        }

        StudentAcademic::create([
            'school_id' => $school->id,
            'academic_year_id' => $link->academic_year_id,
            'user_id' => $student->id,
            'standardLink_id' => $link->id,
        ]);

        return $student;
    }

    /**
     * @param  array<string, list<array{0: string, 1: string}>>  $sheets
     * @param  list<string>  $klsHeader
     */
    private function workbook(array $sheets, array $klsHeader): string
    {
        $spreadsheet = new Spreadsheet;
        $first = true;

        foreach ($sheets as $title => $rows) {
            $sheet = $first ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $first = false;
            $sheet->setTitle($title);
            $sheet->setCellValue('A1', $title);
            $sheet->setCellValue('A2', in_array($title, $klsHeader, true) ? 'KLS number' : 'Note');
            $sheet->setCellValue('B2', 'Learner name');
            foreach ($rows as $index => $row) {
                $sheet->setCellValue('A'.($index + 3), $row[0]);
                $sheet->setCellValue('B'.($index + 3), $row[1]);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'kls').'.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        return $path;
    }

    private function cell(string $path, string $sheet, int $row): string
    {
        $spreadsheet = IOFactory::load($path);

        return trim((string) $spreadsheet->getSheetByName($sheet)?->getCell('A'.$row)->getCalculatedValue());
    }
}
