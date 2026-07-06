<?php
/**
 * Seed test marks for PDF report verification.
 * Creates sections + students + subjects + exams + marks for all four levels.
 * Run: php -r 'require "database/seed-test-marks.php";'
 */

namespace App\Http\Controllers\Admin;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Academics\NurseryAssessment;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\Subject;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\StudentAcademic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$schoolId = 1;
$teacherId = 30; // Teacher Test School One
$academicYearId = 1; // 2026
$termId = 1; // First Term
$examTypeId = 1; // BOT

echo "=== Seeding test marks for PDF report verification ===\n\n";

// =============================================
// 1. NURSERY — student 35 already exists with nursery assessments
//    Just need to add marks records so learner() query finds them
// =============================================
echo "--- Nursery (Baby Class) ---\n";
$nurseryStudent = User::find(35);
if (!$nurseryStudent) {
    die("Nursery student (id=35) not found!\n");
}
echo "  Student: id={$nurseryStudent->id} name={$nurseryStudent->name}\n";

// Check if marks already exist
$existingNurseryMarks = Marks::where('student_id', 35)->where('exam_id', 1)->count();
if ($existingNurseryMarks === 0) {
    Marks::create([
        'student_id' => 35,
        'teacher_id' => $teacherId,
        'school_id' => $schoolId,
        'subject_id' => 1, // NURSERY ASSESSMENT
        'exam_id' => 1,
        'section_id' => 1, // Baby Class
        'marks' => 85,
        'grade' => 'A',
    ]);
    echo "  Created marks for nursery student.\n";
} else {
    echo "  Marks already exist ({$existingNurseryMarks} records).\n";
}

// =============================================
// 2. PRIMARY — Create Primary One + student + exam + marks
// =============================================
echo "\n--- Primary (Primary One) ---\n";

// Create a subject for Primary One if not exists
$primarySubject = Subject::where('school_id', $schoolId)->where('section_id', 4)->first();
    if (!$primarySubject) {
        $primarySubject = Subject::create([
            'school_id' => $schoolId,
            'section_id' => 4, // Primary One
            'standard_id' => 2, // primary standard
            'academic_year_id' => $academicYearId,
            'name' => 'MATHEMATICS',
            'code' => 'MATH',
        ]);
    echo "  Created subject: id={$primarySubject->id} name={$primarySubject->name}\n";
} else {
    echo "  Subject already exists: id={$primarySubject->id} name={$primarySubject->name}\n";
}

// Create exam for Primary One if not exists
$primaryExam = Exam::where('school_id', $schoolId)
    ->where('section_id', 4)
    ->where('academic_term_id', $termId)
    ->where('exam_type_id', $examTypeId)
    ->where('subject_id', $primarySubject->id)
    ->first();

    if (!$primaryExam) {
    $primaryExam = Exam::create([
        'school_id' => $schoolId,
        'section_id' => 4,
        'standard_id' => 2,
        'subject_id' => $primarySubject->id,
        'teacher_id' => $teacherId,
        'exam_type_id' => $examTypeId,
        'academic_year_id' => $academicYearId,
        'academic_term_id' => $termId,
        'status' => 'submitted',
        'scheduled_at' => now(),
    ]);
    echo "  Created exam: id={$primaryExam->id}\n";
} else {
    echo "  Exam already exists: id={$primaryExam->id}\n";
}

// Create student for Primary One
$primaryStudent = User::where('email', 'primary_student@test.com')->first();
if (!$primaryStudent) {
    $primaryStudent = User::create([
        'school_id' => $schoolId,
        'usergroup_id' => 6, // student
        'name' => 'Primary Student One',
        'email' => 'primary_student@test.com',
        'password' => Hash::make('password'),
        'gender' => 'male',
        'status' => 'active',
    ]);
    echo "  Created user: id={$primaryStudent->id} name={$primaryStudent->name}\n";

    // Create userprofile
    Userprofile::create([
        'user_id' => $primaryStudent->id,
        'school_id' => $schoolId,
        'usergroup_id' => 6,
        'firstname' => 'Primary',
        'lastname' => 'Student One',
        'gender' => 'male',
        'status' => 'active',
    ]);

    // Find the correct standard_link (primary + Primary One section)
    $primaryLink = StandardLink::where('standard_id', 2)
        ->where('section_id', 4)
        ->first();
    if (!$primaryLink) {
        $primaryLink = StandardLink::create([
            'school_id' => $schoolId,
            'standard_id' => 2,
            'section_id' => 4,
            'academic_year_id' => $academicYearId,
        ]);
    }

    StudentAcademic::create([
        'user_id' => $primaryStudent->id,
        'school_id' => $schoolId,
        'academic_year_id' => $academicYearId,
        'standardLink_id' => $primaryLink->id,
    ]);
    echo "  Created student academic record.\n";
} else {
    echo "  Student already exists: id={$primaryStudent->id}\n";
}

// Create marks for Primary student
$existingPrimaryMarks = Marks::where('student_id', $primaryStudent->id)
    ->where('exam_id', $primaryExam->id)
    ->count();

if ($existingPrimaryMarks === 0) {
    Marks::create([
        'student_id' => $primaryStudent->id,
        'teacher_id' => $teacherId,
        'school_id' => $schoolId,
        'subject_id' => $primarySubject->id,
        'exam_id' => $primaryExam->id,
        'section_id' => 4,
        'marks' => 78,
        'grade' => 'B',
    ]);
    echo "  Created marks for primary student.\n";
} else {
    echo "  Marks already exist ({$existingPrimaryMarks}).\n";
}

// =============================================
// 3. O-LEVEL — Create section + student + exam + marks
// =============================================
echo "\n--- O-Level ---\n";

// Create O-Level section (Senior One) in school 1
$olevelSection = Section::where('school_id', $schoolId)->where('name', 'Senior One')->first();
if (!$olevelSection) {
    $olevelSection = Section::create([
        'school_id' => $schoolId,
        'name' => 'Senior One',
    ]);
    echo "  Created section: id={$olevelSection->id} name={$olevelSection->name}\n";

    // Create standard_link for o-level standard + this section
    StandardLink::create([
        'school_id' => $schoolId,
        'standard_id' => 3, // o-level standard
        'section_id' => $olevelSection->id,
        'academic_year_id' => $academicYearId,
    ]);
    echo "  Created standard_link for o-level + Senior One.\n";
} else {
    echo "  Section already exists: id={$olevelSection->id}\n";
}

// Create subject for O-Level
$olevelSubject = Subject::where('school_id', $schoolId)->where('section_id', $olevelSection->id)->first();
if (!$olevelSubject) {
    $olevelSubject = Subject::create([
        'school_id' => $schoolId,
        'section_id' => $olevelSection->id,
        'standard_id' => 3, // o-level standard
        'academic_year_id' => $academicYearId,
        'name' => 'ENGLISH',
        'code' => 'ENG',
    ]);
    echo "  Created subject: id={$olevelSubject->id} name={$olevelSubject->name}\n";
} else {
    echo "  Subject already exists: id={$olevelSubject->id}\n";
}

// Create exam for O-Level
$olevelExam = Exam::where('school_id', $schoolId)
    ->where('section_id', $olevelSection->id)
    ->where('academic_term_id', $termId)
    ->where('exam_type_id', $examTypeId)
    ->where('subject_id', $olevelSubject->id)
    ->first();

if (!$olevelExam) {
    $olevelExam = Exam::create([
        'school_id' => $schoolId,
        'section_id' => $olevelSection->id,
        'standard_id' => 3,
        'subject_id' => $olevelSubject->id,
        'teacher_id' => $teacherId,
        'exam_type_id' => $examTypeId,
        'academic_year_id' => $academicYearId,
        'academic_term_id' => $termId,
        'status' => 'submitted',
        'scheduled_at' => now(),
    ]);
    echo "  Created exam: id={$olevelExam->id}\n";
} else {
    echo "  Exam already exists: id={$olevelExam->id}\n";
}

// Create O-Level student
$olevelStudent = User::where('email', 'olevel_student@test.com')->first();
if (!$olevelStudent) {
    $olevelStudent = User::create([
        'school_id' => $schoolId,
        'usergroup_id' => 6,
        'name' => 'O-Level Student One',
        'email' => 'olevel_student@test.com',
        'password' => Hash::make('password'),
        'gender' => 'female',
        'status' => 'active',
    ]);
    echo "  Created user: id={$olevelStudent->id} name={$olevelStudent->name}\n";

    Userprofile::create([
        'user_id' => $olevelStudent->id,
        'school_id' => $schoolId,
        'usergroup_id' => 6,
        'firstname' => 'O-Level',
        'lastname' => 'Student One',
        'gender' => 'female',
        'status' => 'active',
    ]);

    $olevelLink = StandardLink::where('standard_id', 3)
        ->where('section_id', $olevelSection->id)
        ->first();
    if (!$olevelLink) {
        $olevelLink = StandardLink::create([
            'school_id' => $schoolId,
            'standard_id' => 3,
            'section_id' => $olevelSection->id,
            'academic_year_id' => $academicYearId,
        ]);
    }

    StudentAcademic::create([
        'user_id' => $olevelStudent->id,
        'school_id' => $schoolId,
        'academic_year_id' => $academicYearId,
        'standardLink_id' => $olevelLink->id,
    ]);
    echo "  Created student academic record.\n";
} else {
    echo "  Student already exists: id={$olevelStudent->id}\n";
}

// Create marks for O-Level student
$existingOlevelMarks = Marks::where('student_id', $olevelStudent->id)
    ->where('exam_id', $olevelExam->id)
    ->count();

if ($existingOlevelMarks === 0) {
    Marks::create([
        'student_id' => $olevelStudent->id,
        'teacher_id' => $teacherId,
        'school_id' => $schoolId,
        'subject_id' => $olevelSubject->id,
        'exam_id' => $olevelExam->id,
        'section_id' => $olevelSection->id,
        'marks' => 65,
        'grade' => 'C',
    ]);
    echo "  Created marks for O-Level student.\n";
} else {
    echo "  Marks already exist ({$existingOlevelMarks}).\n";
}

// =============================================
// 4. A-LEVEL — Create section + student + exam + marks
// =============================================
echo "\n--- A-Level ---\n";

// Create A-Level section (Senior Five) in school 1
$alevelSection = Section::where('school_id', $schoolId)->where('name', 'Senior Five')->first();
if (!$alevelSection) {
    $alevelSection = Section::create([
        'school_id' => $schoolId,
        'name' => 'Senior Five',
    ]);
    echo "  Created section: id={$alevelSection->id} name={$alevelSection->name}\n";

    // Create standard_link for a-level standard + this section
    StandardLink::create([
        'school_id' => $schoolId,
        'standard_id' => 4, // a-level standard
        'section_id' => $alevelSection->id,
        'academic_year_id' => $academicYearId,
    ]);
    echo "  Created standard_link for a-level + Senior Five.\n";
} else {
    echo "  Section already exists: id={$alevelSection->id}\n";
}

// Create subject for A-Level
$alevelSubject = Subject::where('school_id', $schoolId)->where('section_id', $alevelSection->id)->first();
if (!$alevelSubject) {
    $alevelSubject = Subject::create([
        'school_id' => $schoolId,
        'section_id' => $alevelSection->id,
        'standard_id' => 4, // a-level standard
        'academic_year_id' => $academicYearId,
        'name' => 'BIOLOGY',
        'code' => 'BIO',
    ]);
    echo "  Created subject: id={$alevelSubject->id} name={$alevelSubject->name}\n";
} else {
    echo "  Subject already exists: id={$alevelSubject->id}\n";
}

// Create exam for A-Level
$alevelExam = Exam::where('school_id', $schoolId)
    ->where('section_id', $alevelSection->id)
    ->where('academic_term_id', $termId)
    ->where('exam_type_id', $examTypeId)
    ->where('subject_id', $alevelSubject->id)
    ->first();

if (!$alevelExam) {
    $alevelExam = Exam::create([
        'school_id' => $schoolId,
        'section_id' => $alevelSection->id,
        'standard_id' => 4,
        'subject_id' => $alevelSubject->id,
        'teacher_id' => $teacherId,
        'exam_type_id' => $examTypeId,
        'academic_year_id' => $academicYearId,
        'academic_term_id' => $termId,
        'status' => 'submitted',
        'scheduled_at' => now(),
    ]);
    echo "  Created exam: id={$alevelExam->id}\n";
} else {
    echo "  Exam already exists: id={$alevelExam->id}\n";
}

// Create A-Level student
$alevelStudent = User::where('email', 'alevel_student@test.com')->first();
if (!$alevelStudent) {
    $alevelStudent = User::create([
        'school_id' => $schoolId,
        'usergroup_id' => 6,
        'name' => 'A-Level Student One',
        'email' => 'alevel_student@test.com',
        'password' => Hash::make('password'),
        'gender' => 'male',
        'status' => 'active',
    ]);
    echo "  Created user: id={$alevelStudent->id} name={$alevelStudent->name}\n";

    Userprofile::create([
        'user_id' => $alevelStudent->id,
        'school_id' => $schoolId,
        'usergroup_id' => 6,
        'firstname' => 'A-Level',
        'lastname' => 'Student One',
        'gender' => 'male',
        'status' => 'active',
    ]);

    $alevelLink = StandardLink::where('standard_id', 4)
        ->where('section_id', $alevelSection->id)
        ->first();
    if (!$alevelLink) {
        $alevelLink = StandardLink::create([
            'school_id' => $schoolId,
            'standard_id' => 4,
            'section_id' => $alevelSection->id,
            'academic_year_id' => $academicYearId,
        ]);
    }

    StudentAcademic::create([
        'user_id' => $alevelStudent->id,
        'school_id' => $schoolId,
        'academic_year_id' => $academicYearId,
        'standardLink_id' => $alevelLink->id,
    ]);
    echo "  Created student academic record.\n";
} else {
    echo "  Student already exists: id={$alevelStudent->id}\n";
}

// Create marks for A-Level student
$existingAleveMarks = Marks::where('student_id', $alevelStudent->id)
    ->where('exam_id', $alevelExam->id)
    ->count();

if ($existingAleveMarks === 0) {
    Marks::create([
        'student_id' => $alevelStudent->id,
        'teacher_id' => $teacherId,
        'school_id' => $schoolId,
        'subject_id' => $alevelSubject->id,
        'exam_id' => $alevelExam->id,
        'section_id' => $alevelSection->id,
        'marks' => 70,
        'grade' => 'B',
    ]);
    echo "  Created marks for A-Level student.\n";
} else {
    echo "  Marks already exist ({$existingAleveMarks}).\n";
}

echo "\n=== Seed complete! ===\n";
echo "Nursery:   student_id=35, exam_id=1\n";
echo "Primary:   student_id={$primaryStudent->id}, exam_id={$primaryExam->id}\n";
echo "O-Level:   student_id={$olevelStudent->id}, exam_id={$olevelExam->id}\n";
echo "A-Level:   student_id={$alevelStudent->id}, exam_id={$alevelExam->id}\n";
