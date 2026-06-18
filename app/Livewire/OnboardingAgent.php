<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\School;
use App\Models\User;
use App\Models\Standard;
use App\Models\Section;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\StandardLink;
use App\Models\Teacherlink;
use App\Models\Userprofile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OnboardingAgent extends Component
{
    use WithFileUploads;

    public $step = 0;
    public $visible = false;
    public $maximized = false;
    public $attachment = null;
    public $messages = [];
    public $input = '';
    public $schoolId = null;
    public $preview = [];

    // Collected data across steps
    public $schoolName = '';
    public $schoolType = 'primary';
    public $schoolEmail = '';
    public $schoolPhone = '';
    public $adminName = '';
    public $adminEmail = '';
    public $adminPassword = '';
    public $academicYearLabel = '';
    public $standards = [];
    public $subjects = [];
    public $teacherList = [];
    public $teacherLinks = [];
    public $studentList = [];
    public $terms = [];
    public $fees = [];
    public $exams = [];

    public $steps = [
        'school_info',
        'admin_account',
        'academic_year',
        'standards',
        'subjects',
        'teachers',
        'teacher_links',
        'students',
        'terms',
        'fees',
        'exams',
        'review',
    ];

    public function mount()
    {
        $this->botSay("Hello! I'll help you set up your school on KlassApp. | What's the name of your school?");
    }

    public function show() { $this->visible = true; }
    public function hide() { $this->visible = false; $this->maximized = false; }
    public function maximize() { $this->maximized = true; }
    public function restore() { $this->maximized = false; }

    public function updatedAttachment()
    {
        $this->validate(['attachment' => 'file|mimes:csv,txt,xlsx,xls,pdf,png,jpg,jpeg,docx|max:5120']);

        $ext = strtolower($this->attachment->getClientOriginalExtension());
        $tabular = in_array($ext, ['csv', 'txt', 'xlsx', 'xls']);

        if ($tabular) {
            $path = $this->attachment->getRealPath();
            $handle = fopen($path, 'r');
            $headers = fgetcsv($handle);
            $names = [];

            while (($row = fgetcsv($handle)) !== false) {
                $nameIdx = null;
                foreach (['firstname', 'name', 'First Name', 'Name'] as $col) {
                    $idx = array_search($col, $headers);
                    if ($idx !== false) { $nameIdx = $idx; break; }
                }
                if ($nameIdx === null && count($row) > 0) $nameIdx = 0;
                if ($nameIdx !== null && !empty(trim($row[$nameIdx]))) {
                    $name = trim($row[$nameIdx]);
                    $lastIdx = array_search('lastname', array_map('strtolower', $headers));
                    if ($lastIdx !== false && !empty(trim($row[$lastIdx]))) {
                        $name .= ' ' . trim($row[$lastIdx]);
                    }
                    $names[] = $name;
                }
            }
            fclose($handle);

            if (count($names) > 0) {
                $stepName = $this->steps[$this->step];
                if (in_array($stepName, ['teachers', 'students'])) {
                    if ($stepName === 'teachers') $this->teacherList = $names;
                    else $this->studentList = $names;
                    $this->userSay("📎 Uploaded " . count($names) . " names from file");
                    $this->botSay("Parsed **" . count($names) . "** names from your file. Continue?");
                } else {
                    $this->botSay("File received with " . count($names) . " names. We'll use this when we get to the teachers/students step.");
                }
            } else {
                $this->botSay("I couldn't find any names in that file. Make sure it has a 'firstname' or 'name' column.");
            }
        } else {
            $fileName = $this->attachment->getClientOriginalName();
            $size = round($this->attachment->getSize() / 1024, 1);
            $this->userSay("📎 Uploaded: {$fileName} ({$size} KB)");
            $this->botSay("Received **{$fileName}**. I can extract names from CSV files — this file type ({$ext}) will be stored for reference. Continue?");
        }

        $this->attachment = null;
    }

    public function render()
    {
        return view('livewire.onboarding-agent');
    }

    // ── Agent says something ──
    private function botSay(string $message)
    {
        $this->messages[] = ['role' => 'bot', 'text' => $message];
    }

    // ── User says something ──
    private function userSay(string $message)
    {
        $this->messages[] = ['role' => 'user', 'text' => $message];
    }

    // ── Mark step as done ──
    private function advance(int $to = null)
    {
        $this->step = $to ?? $this->step + 1;
    }

    // ── Handle user input ──
    public function send()
    {
        $text = trim($this->input);
        if ($text === '') return;

        $this->userSay($text);
        $this->input = '';

        $stepName = $this->steps[$this->step];

        match ($stepName) {
            'school_info'    => $this->handleSchoolInfo($text),
            'admin_account'  => $this->handleAdminAccount($text),
            'academic_year'  => $this->handleAcademicYear($text),
            'standards'      => $this->handleStandards($text),
            'subjects'       => $this->handleSubjects($text),
            'teachers'       => $this->handleTeachers($text),
            'teacher_links'  => $this->handleTeacherLinks($text),
            'students'       => $this->handleStudents($text),
            'terms'          => $this->handleTerms($text),
            'fees'           => $this->handleFees($text),
            'exams'          => $this->handleExams($text),
            'review'         => $this->handleReview($text),
            default          => $this->advance(),
        };
    }

    // ════════════════════════════════════════════════
    //  Step 1: School Info
    // ════════════════════════════════════════════════
    private function handleSchoolInfo(string $text)
    {
        $this->schoolName = $text;
        $type = $this->detectSchoolType($text);

        $this->botSay("Got it — **{$this->schoolName}**.");
        $this->botSay("I detected this as a **{$type}** school. | Is that correct? (yes / no)");
        $this->schoolType = $type;
        $this->step = 1; // substep — awaiting confirmation
    }

    private function detectSchoolType(string $text): string
    {
        $text = strtolower($text);
        if (str_contains($text, 'primary') || str_contains($text, 'p.')) return 'primary';
        if (str_contains($text, 'secondary') || str_contains($text, 's.')) return 'secondary';
        if (str_contains($text, 'nursery')) return 'nursery';
        if (str_contains($text, 'mixed') || str_contains($text, 'both')) return 'mixed';
        if (str_contains($text, 'o-level') || str_contains($text, 'olevel')) return 'o-level';
        if (str_contains($text, 'a-level') || str_contains($text, 'alevel')) return 'a-level';
        return 'primary';
    }

    // ── Step 1 continued (confirmation) ──
    public function confirmSchoolType(bool $yes)
    {
        if ($yes) {
            $this->botSay("Great. What email should we use for this school?");
            $this->advance(1);
        } else {
            $this->botSay("No problem — what type of school is it? (Nursery, Primary, Secondary, O-Level, A-Level, Mixed)");
        }
    }

    public function sendSchoolType(string $type)
    {
        $valid = ['nursery', 'primary', 'secondary', 'o-level', 'a-level', 'mixed'];
        $type = strtolower(trim($type));
        if (!in_array($type, $valid)) {
            $this->botSay("I didn't recognize that. Please pick: Nursery, Primary, Secondary, O-Level, A-Level, Mixed.");
            return;
        }
        $this->schoolType = $type;
        $this->botSay("Updated to **{$type}**. | What email should we use for this school?");
        $this->step = 1;
    }

    // ════════════════════════════════════════════════
    //  Step 2: Admin Account
    // ════════════════════════════════════════════════
    private function handleAdminAccount(string $text)
    {
        $this->schoolEmail = $text;
        $this->botSay("Email set: **{$this->schoolEmail}**.");
        $this->botSay("What's the name of the school admin?");
    }

    // ════════════════════════════════════════════════
    //  Step 3: Academic Year
    // ════════════════════════════════════════════════
    private function handleAcademicYear(string $text)
    {
        $this->adminName = $text;
        $year = date('Y');
        $this->botSay("Admin: **{$this->adminName}**.");
        $this->botSay("Academic year: I'll set it to **{$year}**. | Confirm? (yes / no / change to YYYY)");
        $this->academicYearLabel = (string) $year;
    }

    // ════════════════════════════════════════════════
    //  Step 4: Standards (Classes)
    // ════════════════════════════════════════════════
    private function handleStandards(string $text)
    {
        $defaults = $this->curriculumDefaults();
        $this->standards = $defaults['classes'] ?? [];
        $classList = implode(', ', array_column($this->standards, 'name'));
        $this->botSay("I'll create these classes: **{$classList}**.");
        $this->botSay("Any to add, remove, or rename? (type changes or 'ok')");
    }

    // ════════════════════════════════════════════════
    //  Step 5: Subjects
    // ════════════════════════════════════════════════
    private function handleSubjects(string $text)
    {
        $defaults = $this->curriculumDefaults();
        $this->subjects = $defaults['subjects'] ?? [];
        $subjectList = collect($this->subjects)->first() ?? [];
        if (is_array($subjectList)) {
            $subjectList = implode(', ', array_slice($subjectList, 0, 5)) . '...';
        }
        $this->botSay("Default subjects assigned per class (NCDC curriculum). | Any subjects to add or remove? (type changes or 'ok')");
    }

    // ════════════════════════════════════════════════
    //  Step 6: Teachers
    // ════════════════════════════════════════════════
    private function handleTeachers(string $text)
    {
        $this->teacherList = $this->parseNameList($text);
        $count = count($this->teacherList);
        if ($count > 0) {
            $names = implode(', ', array_slice($this->teacherList, 0, 3));
            $this->botSay("Parsed **{$count}** teachers: {$names}...");
            $this->botSay("Teacher accounts will be created with auto-generated emails. | Continue? (yes / no)");
        } else {
            $this->botSay("I couldn't find any names. Please paste a list (one per line) or type 'skip' to add later.");
        }
    }

    private function parseNameList(string $text): array
    {
        $lines = preg_split('/[\n,]+/', $text);
        $names = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) > 3 && !is_numeric($line)) {
                $names[] = $line;
            }
        }
        return array_slice(array_unique($names), 0, 50);
    }

    // ════════════════════════════════════════════════
    //  Step 7: Teacher-Class-Subject Linking
    // ════════════════════════════════════════════════
    private function handleTeacherLinks(string $text)
    {
        if ($text === 'skip' || $text === 'later') {
            $this->botSay("Skipped. You can assign teachers to classes later in the admin panel.");
            $this->advance();
            return;
        }
        $this->teacherLinks[] = $text;
        $this->botSay("Noted: **{$text}**. | Any other teacher assignments? (type more or 'done')");
    }

    // ════════════════════════════════════════════════
    //  Step 8: Students
    // ════════════════════════════════════════════════
    private function handleStudents(string $text)
    {
        if ($text === 'skip' || $text === 'later') {
            $this->botSay("Skipped. You can add students later in the admin panel.");
            $this->advance();
            return;
        }
        $this->studentList = $this->parseNameList($text);
        $this->botSay("Parsed **" . count($this->studentList) . "** students. | Adding students now is optional. Continue? (yes / skip)");
    }

    // ════════════════════════════════════════════════
    //  Step 9: Academic Terms
    // ════════════════════════════════════════════════
    private function handleTerms(string $text)
    {
        $this->terms = [
            ['name' => 'Term I',   'start' => date('Y') . '-02-01', 'end' => date('Y') . '-04-30'],
            ['name' => 'Term II',  'start' => date('Y') . '-05-01', 'end' => date('Y') . '-08-31'],
            ['name' => 'Term III', 'start' => date('Y') . '-09-01', 'end' => date('Y') . '-12-31'],
        ];
        $this->botSay("Default Ugandan terms set: Term I (Feb-Apr), Term II (May-Aug), Term III (Sep-Dec).");
        $this->botSay("Need to adjust any dates? (type changes or 'ok')");
    }

    // ════════════════════════════════════════════════
    //  Step 10: Fees
    // ════════════════════════════════════════════════
    private function handleFees(string $text)
    {
        if ($text === 'skip' || $text === 'later') {
            $this->botSay("Skipped. You can set up fees later in the admin panel.");
            $this->advance();
            return;
        }
        $this->fees[] = $text;
        $this->botSay("Noted. | Any other fee categories? (type more or 'skip')");
    }

    // ════════════════════════════════════════════════
    //  Step 11: Exams
    // ════════════════════════════════════════════════
    private function handleExams(string $text)
    {
        if ($text === 'skip' || $text === 'later') {
            $this->botSay("Skipped. You can create exams later in the admin panel.");
            $this->advance();
            return;
        }
        $this->exams[] = $text;
        $this->botSay("Noted. | Any other exams? (type more or 'skip')");
    }

    // ════════════════════════════════════════════════
    //  Step 12: Review & Commit
    // ════════════════════════════════════════════════
    private function handleReview(string $text)
    {
        if ($text === 'commit') {
            $this->commitAll();
            $this->botSay("🎉 **Done!** {$this->schoolName} is ready. | Login credentials will be sent to {$this->schoolEmail}.");
            $this->step = 99; // done
            return;
        }
        $this->botSay("Here's a summary of what will be created: | • School: **{$this->schoolName}** ({$this->schoolType}) | • Admin: {$this->adminName} | • Classes: " . count($this->standards) . " | • Teachers: " . count($this->teacherList) . " | • Terms: " . count($this->terms) . " | Type **commit** to save everything.");
    }

    // ════════════════════════════════════════════════
    //  Commit everything to the database
    // ════════════════════════════════════════════════
    private function commitAll()
    {
        DB::transaction(function () {
            $school = School::create([
                'name'    => $this->schoolName,
                'email'   => $this->schoolEmail ?: Str::slug($this->schoolName) . '@klassapp.sch.ug',
                'phone'   => $this->schoolPhone ?: '0700000000',
                'status'  => 1,
                'slug'    => Str::slug($this->schoolName),
                'registration_country' => 'Uganda',
            ]);
            $this->schoolId = $school->id;

            $academicYear = AcademicYear::create([
                'school_id'   => $school->id,
                'name'        => $this->academicYearLabel ?: date('Y'),
                'start_date'  => now()->startOfYear(),
                'end_date'    => now()->endOfYear(),
                'type'        => 'Current Academic Year',
            ]);

            $password = bcrypt($this->adminPassword ?: 'password');

            $adminUser = User::create([
                'school_id'    => $school->id,
                'usergroup_id' => 3,
                'name'         => $this->adminName ?: 'School Admin',
                'email'        => $this->adminEmail ?: 'admin@' . Str::slug($this->schoolName) . '.sch.ug',
                'password'     => $password,
                'status'       => 'active',
                'email_verified' => 1,
            ]);

            Userprofile::create([
                'school_id'   => $school->id,
                'user_id'     => $adminUser->id,
                'usergroup_id'=> 3,
                'firstname'   => $this->adminName ?: 'School',
                'lastname'    => 'Admin',
                'status'      => 'active',
            ]);

            // Create standard + sections
            $phase = Standard::create([
                'school_id' => $school->id,
                'name'      => $this->schoolType,
                'order'     => 1,
                'status'    => '1',
            ]);

            foreach ($this->standards as $class) {
                $section = Section::firstOrCreate(
                    ['school_id' => $school->id, 'name' => $class['name']],
                    ['value' => $class['name'], 'status' => '1']
                );

                $standardLink = StandardLink::create([
                    'school_id'         => $school->id,
                    'academic_year_id'  => $academicYear->id,
                    'standard_id'       => $phase->id,
                    'section_id'        => $section->id,
                    'status'            => '1',
                ]);

                // Assign subjects per class
                $classSubjects = $this->subjects[$class['name']] ?? [];
                foreach ($classSubjects as $subjectName) {
                    Subject::firstOrCreate(
                        ['school_id' => $school->id, 'standard_id' => $phase->id, 'section_id' => $section->id, 'name' => $subjectName],
                        ['academic_year_id' => $academicYear->id, 'type' => 'core']
                    );
                }
            }

            // Create teachers
            foreach ($this->teacherList as $i => $name) {
                $email = Str::slug($name) . '@' . Str::slug($this->schoolName) . '.edu';

                $teacher = User::create([
                    'school_id'    => $school->id,
                    'usergroup_id' => 5,
                    'name'         => $name,
                    'email'        => $email,
                    'password'     => $password,
                    'status'       => 'active',
                    'email_verified' => 1,
                ]);

                Userprofile::create([
                    'school_id'    => $school->id,
                    'user_id'      => $teacher->id,
                    'usergroup_id' => 5,
                    'firstname'    => $name,
                    'lastname'     => '',
                    'profession'   => 'teacher',
                    'status'       => 'active',
                ]);
            }

            // Create academic terms
            foreach ($this->terms as $term) {
                \App\Models\AcademicTerm::create([
                    'school_id' => $school->id,
                    'academic_year_id' => $academicYear->id,
                    'name'       => $term['name'],
                    'start_date' => $term['start'],
                    'end_date'   => $term['end'],
                ]);
            }
        });
    }

    // ════════════════════════════════════════════════
    //  Curriculum Defaults (NCDC Uganda)
    // ════════════════════════════════════════════════
    private function curriculumDefaults(): array
    {
        return match ($this->schoolType) {
            'nursery' => [
                'classes' => [
                    ['name' => 'Baby Class'], ['name' => 'Middle Class'], ['name' => 'Top Class'],
                ],
                'subjects' => [
                    'Baby Class'   => ['English Rhymes & Stories', 'Numbers & Counting', 'Colour & Shapes', 'Creative Play'],
                    'Middle Class' => ['English Language Basics', 'Numeracy', 'Environmental Awareness', 'Art & Craft'],
                    'Top Class'    => ['Pre-Literacy (English)', 'Pre-Numeracy', 'Social Habits', 'Creative Arts', 'Religious Education'],
                ],
            ],
            'primary' => [
                'classes' => [
                    ['name' => 'Primary 1'], ['name' => 'Primary 2'], ['name' => 'Primary 3'],
                    ['name' => 'Primary 4'], ['name' => 'Primary 5'], ['name' => 'Primary 6'], ['name' => 'Primary 7'],
                ],
                'subjects' => [
                    'Primary 1' => ['English Language', 'Mathematics', 'Literacy I', 'Numeracy I', 'Religious Education'],
                    'Primary 2' => ['English Language', 'Mathematics', 'Literacy II', 'Numeracy II', 'Religious Education'],
                    'Primary 3' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education'],
                    'Primary 4' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
                    'Primary 5' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
                    'Primary 6' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
                    'Primary 7' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
                ],
            ],
            'mixed' => [
                'classes' => [
                    ['name' => 'Baby Class'], ['name' => 'Middle Class'], ['name' => 'Top Class'],
                    ['name' => 'Primary 1'], ['name' => 'Primary 2'], ['name' => 'Primary 3'],
                    ['name' => 'Primary 4'], ['name' => 'Primary 5'], ['name' => 'Primary 6'], ['name' => 'Primary 7'],
                ],
                'subjects' => [
                    'Baby Class'   => ['English Rhymes & Stories', 'Numbers & Counting', 'Creative Play'],
                    'Middle Class' => ['English Language Basics', 'Numeracy', 'Art & Craft'],
                    'Top Class'    => ['Pre-Literacy', 'Pre-Numeracy', 'Social Habits', 'Creative Arts'],
                    'Primary 1' => ['English Language', 'Mathematics', 'Literacy I', 'Numeracy I', 'Religious Education'],
                    'Primary 2' => ['English Language', 'Mathematics', 'Literacy II', 'Numeracy II', 'Religious Education'],
                    'Primary 3' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education'],
                    'Primary 4' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
                    'Primary 5' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
                    'Primary 6' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
                    'Primary 7' => ['English Language', 'Mathematics', 'Integrated Science', 'Social Studies', 'Religious Education', 'Local Language'],
                ],
            ],
            default => [
                'classes' => [['name' => 'Primary 1']],
                'subjects' => ['Primary 1' => ['English Language', 'Mathematics']],
            ],
        };
    }
}
