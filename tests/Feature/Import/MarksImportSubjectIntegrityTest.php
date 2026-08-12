<?php

namespace Tests\Feature\Import;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\User;
use App\Models\Subject;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarksImportSubjectIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private AcademicYear $year;
    private AcademicTerm $term;
    private Standard $standard;
    private Section $section;
    private User $student;
    private User $teacher;
    private Exam $exam;

    private const SUBJECT_COLUMNS = [
        'eng'    => 'English',
        'mtc'    => 'Mathematics',
        're'     => 'Religious Education',
        'lit_i'  => 'Literacy I',
        'lit_ii' => 'Literacy II',
        'r_r'    => 'Reading and Response',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('exam_types')->insert([
            ['id' => 2, 'name' => 'End Of Term', 'code' => 'EOT', 'contributes_to_report_total' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->school = School::create([
            'name' => 'Import Test School', 'email' => 'import@test.sch.ug',
            'phone' => '0700000002', 'slug' => 'import-test-school', 'status' => 1,
        ]);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id, 'name' => '2026',
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1,
        ]);

        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'name' => 'Term 2', 'status' => 'current',
            'starts_on' => '2026-05-01', 'ends_on' => '2026-08-31',
        ]);

        $this->standard = Standard::create([
            'school_id' => $this->school->id, 'name' => 'primary_lower', 'order' => 1, 'status' => '1',
        ]);

        $this->section = Section::create([
            'school_id' => $this->school->id, 'name' => 'P.2', 'status' => 1,
        ]);

        StandardLink::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'standard_id' => $this->standard->id, 'section_id' => $this->section->id, 'status' => '1',
        ]);

        $this->teacher = User::create([
            'school_id' => $this->school->id, 'usergroup_id' => 5,
            'name' => 'Import Teacher', 'email' => 'importer@test.sch.ug',
            'password' => bcrypt('password'), 'status' => 'active',
        ]);

        $this->student = User::create([
            'school_id' => $this->school->id, 'usergroup_id' => 6,
            'name' => 'Import Student', 'email' => 'importstudent@test.sch.ug',
            'password' => bcrypt('password'), 'status' => 'active',
        ]);

        $this->exam = Exam::withoutEvents(fn() => Exam::create([
            'school_id' => $this->school->id, 'standard_id' => $this->standard->id,
            'section_id' => $this->section->id, 'subject_id' => 0,
            'teacher_id' => $this->teacher->id, 'exam_type_id' => 2,
            'academic_term_id' => $this->term->id, 'academic_year_id' => $this->year->id,
            'scheduled_at' => now(), 'status' => 'done',
        ]));

        foreach (self::SUBJECT_COLUMNS as $subjectName) {
            Subject::create([
                'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
                'standard_id' => $this->standard->id, 'section_id' => $this->section->id,
                'name' => $subjectName, 'code' => '', 'type' => 'core', 'status' => 1,
            ]);
        }
    }

    /** @test */
    public function each_column_maps_to_distinct_subject_id(): void
    {
        $row = ['eng' => 90, 'mtc' => 85, 're' => 80, 'lit_i' => 75, 'lit_ii' => 70, 'r_r' => 65];
        $subjectIds = [];

        foreach (self::SUBJECT_COLUMNS as $colKey => $subjectName) {
            $subjectId = Subject::where('school_id', $this->school->id)->where('name', $subjectName)->value('id');
            $this->assertNotNull($subjectId);
            $this->assertNotContains($subjectId, $subjectIds);
            $subjectIds[] = $subjectId;

            Marks::create([
                'student_id' => $this->student->id, 'exam_id' => $this->exam->id,
                'school_id' => $this->school->id, 'subject_id' => $subjectId,
                'teacher_id' => $this->teacher->id, 'marks' => $row[$colKey],
                'grade' => '1', 'section_id' => $this->section->id,
            ]);
        }

        $this->assertCount(6, array_unique($subjectIds));
        $this->assertEquals(6, Marks::where('exam_id', $this->exam->id)->where('student_id', $this->student->id)->count());
        $this->assertEquals(6, Marks::where('exam_id', $this->exam->id)->where('student_id', $this->student->id)->distinct('subject_id')->count('subject_id'));
    }

    /** @test */
    public function unknown_column_name_is_detected_before_insert(): void
    {
        $columns = ['eng', 'mtc', 're', 'lit_i', 'lit_ii', 'r_r', 'physics'];
        $resolved = 0;
        $missing = [];

        foreach ($columns as $col) {
            if (!isset(self::SUBJECT_COLUMNS[$col])) {
                $missing[] = $col;
                continue;
            }
            $subjectId = Subject::where('school_id', $this->school->id)->where('name', self::SUBJECT_COLUMNS[$col])->value('id');
            $subjectId ? $resolved++ : $missing[] = self::SUBJECT_COLUMNS[$col];
        }

        $this->assertEquals(6, $resolved);
        $this->assertCount(1, $missing);
        $this->assertContains('physics', $missing);
    }

    /** @test */
    public function reimport_of_same_data_is_idempotent(): void
    {
        $row = ['eng' => 90, 'mtc' => 85, 're' => 80, 'lit_i' => 75, 'lit_ii' => 70, 'r_r' => 65];

        $import = function () use ($row) {
            foreach (self::SUBJECT_COLUMNS as $colKey => $subjectName) {
                $subjectId = Subject::where('school_id', $this->school->id)->where('name', $subjectName)->value('id');
                Marks::updateOrCreate(
                    ['student_id' => $this->student->id, 'exam_id' => $this->exam->id, 'school_id' => $this->school->id, 'subject_id' => $subjectId],
                    ['teacher_id' => $this->teacher->id, 'marks' => $row[$colKey], 'grade' => '1', 'section_id' => $this->section->id]
                );
            }
        };

        $import();
        $this->assertEquals(6, Marks::where('exam_id', $this->exam->id)->where('student_id', $this->student->id)->count());

        $import();
        $this->assertEquals(6, Marks::where('exam_id', $this->exam->id)->where('student_id', $this->student->id)->count());
    }

    /** @test */
    public function subject_creation_is_idempotent(): void
    {
        $name = 'English';
        $this->assertEquals(1, Subject::where('school_id', $this->school->id)->where('name', $name)->count());

        Subject::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => $name],
            ['academic_year_id' => $this->year->id, 'standard_id' => $this->standard->id, 'section_id' => $this->section->id, 'code' => '', 'type' => 'core', 'status' => 1]
        );

        $this->assertEquals(1, Subject::where('school_id', $this->school->id)->where('name', $name)->count());
    }
}