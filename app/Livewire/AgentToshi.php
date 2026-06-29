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
use App\Models\Subscription;
use App\Models\CurrentPlan;
use App\Models\FeesCategories;
use App\Models\AcademicTerm;
use App\Models\StudentAcademic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\CoAdminInviteMail;
use App\Models\OnboardingSession;

class AgentToshi extends Component
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
    public $substep = 0; // internal sub-step within a step

    // Collected data across steps
    public $schoolName = '';
    public $schoolType = 'primary';
    public $schoolLevel = '';   // o-level, a-level, both
    public $schoolGender = '';  // boys, girls, mixed
    public $schoolEmail = '';
    public $schoolPhone = '';
    public $adminName = '';
    public $adminEmail = '';
    public $adminPassword = '';
    public $coAdminName = '';
    public $coAdminEmail = '';
    public $coAdminUserId = null;
    public $academicYearLabel = '';
    public $standards = [];
    public $subjects = [];
    public $teacherList = [];
    public $teacherLinks = [];
    public $teacherPhones = [];
    public $studentList = [];
    public $terms = [];
    public $fees = [];
    public $exams = [];
    public $selectedPlanId = null;
    public $reviewData = []; // populated when entering review step

    public $steps = [
        'plan_selection',
        'school_info',
        'admin_account',
        'co_admin_invite',
        'academic_year',
        'standards',
        'subjects',
        'teachers',
        'teacher_links',
        'students',
        'terms',
        'fees',
        'exams',
        'whatsapp_verify',
        'review',
    ];

    /**
     * Steps that must be completed before a school can function.
     * Optional steps can be skipped and completed later via the admin panel.
     */
    public $mandatorySteps = [
        'plan_selection',
        'school_info',
        'admin_account',
        'academic_year',
        'standards',
        'subjects',
        'terms',
    ];

    public $whatsappPhone = '';
    public $whatsappSentOtp = '';
    public $whatsappVerified = false;

    public $mode = 'create'; // 'create' for super admin, 'complete' for school admin
    public $draftSessionId = null;

    public function mount()
    {
        $user = auth()->user();
        if (!$user) return;

        // ── Restore state from session (survives page refresh) ──
        if ($this->restoreState()) {
            // If in assistant mode, ensure greeting is visible
            if ($this->mode === 'assistant' && empty($this->messages)) {
                $this->botSay("I'm your AI assistant now. Ask me anything about your school — view reports, check stats, or manage settings.");
            }
            return;
        }

        // ── Check if school is fully set up (assistant mode) ──
        if ($user->usergroup_id === 3 && $user->school_id) {
            $missing = \App\Helpers\OnboardingHelper::getMissingSteps($user->school_id, $user->id);
            if (empty($missing)) {
                $this->mode = 'assistant';
                $this->schoolId = $user->school_id;
                $school = \App\Models\School::find($this->schoolId);
                $this->botSay("Hi! I'm Toshi. Ask me anything about **{$school->name}** — I can help with reports, stats, and school management.");
                return;
            }
        }

        // ── Draft resume check (super admin only) ──
        if ($user->usergroup_id === 1) {
            $draft = OnboardingSession::where('user_id', $user->id)
                ->where('status', 'draft')
                ->latest()
                ->first();
            if ($draft) {
                $this->draftSessionId = $draft->id;
                $this->restoreDraft($draft);
                $this->botSay("👋 Welcome back! I've restored your progress from last time.");
                $stepName = $this->steps[$this->step] ?? '';
                $this->botSay("You were on step: **" . ucfirst(str_replace('_', ' ', $stepName)) . "**.");
                $this->botSay("Type 'ok' to continue or 'reset' to start over.");
                return;
            }
        }

        // ── Detect mode ──
        if ($user->usergroup_id === 3) {
            $this->mode = 'complete';
            $this->schoolId = $user->school_id;
            $school = \App\Models\School::find($this->schoolId);
            $this->botSay("Hello! Let's finish setting up **{$school->name}** on KlassApp.");
            $this->detectMissingSteps();
            return;
        }

        // Super Admin — full creation mode
        $this->mode = 'create';
        $this->botSay("Hello! I'll help you set up a new school on KlassApp.");
        $this->botSay("First, let's choose a plan. | Select one of the plans below to get started.");
    }

    private function restoreDraft(OnboardingSession $draft)
    {
        $data = $draft->data;
        $this->step = $draft->step;
        $this->substep = $draft->substep;
        $this->mode = $data['mode'] ?? 'create';
        $this->schoolId = $draft->school_id;
        $this->schoolName = $data['schoolName'] ?? '';
        $this->schoolType = $data['schoolType'] ?? 'primary';
        $this->schoolLevel = $data['schoolLevel'] ?? '';
        $this->schoolGender = $data['schoolGender'] ?? '';
        $this->schoolEmail = $data['schoolEmail'] ?? '';
        $this->schoolPhone = $data['schoolPhone'] ?? '';
        $this->adminName = $data['adminName'] ?? '';
        $this->adminEmail = $data['adminEmail'] ?? '';
        $this->adminPassword = $data['adminPassword'] ?? '';
        $this->coAdminName = $data['coAdminName'] ?? '';
        $this->coAdminEmail = $data['coAdminEmail'] ?? '';
        $this->coAdminUserId = $data['coAdminUserId'] ?? null;
        $this->academicYearLabel = $data['academicYearLabel'] ?? '';
        $this->selectedPlanId = $data['selectedPlanId'] ?? null;
        $this->standards = $data['standards'] ?? [];
        $this->subjects = $data['subjects'] ?? [];
        $this->teacherList = $data['teacherList'] ?? [];
        $this->teacherLinks = $data['teacherLinks'] ?? [];
        $this->teacherPhones = $data['teacherPhones'] ?? [];
        $this->studentList = $data['studentList'] ?? [];
        $this->terms = $data['terms'] ?? [];
        $this->fees = $data['fees'] ?? [];
        $this->exams = $data['exams'] ?? [];
        $this->whatsappPhone = $data['whatsappPhone'] ?? '';
        $this->whatsappVerified = $data['whatsappVerified'] ?? false;
        $this->messages = $data['messages'] ?? [];
    }

    private function saveDraft()
    {
        if ($this->mode !== 'create') return; // only persist creation mode
        $user = auth()->user();
        if (!$user) return;

        $data = [
            'mode'              => $this->mode,
            'schoolName'        => $this->schoolName,
            'schoolType'        => $this->schoolType,
            'schoolLevel'       => $this->schoolLevel,
            'schoolGender'      => $this->schoolGender,
            'schoolEmail'       => $this->schoolEmail,
            'schoolPhone'       => $this->schoolPhone,
            'adminName'         => $this->adminName,
            'adminEmail'        => $this->adminEmail,
            'adminPassword'     => $this->adminPassword,
            'coAdminName'       => $this->coAdminName,
            'coAdminEmail'      => $this->coAdminEmail,
            'coAdminUserId'     => $this->coAdminUserId,
            'academicYearLabel' => $this->academicYearLabel,
            'selectedPlanId'    => $this->selectedPlanId,
            'standards'         => $this->standards,
            'subjects'          => $this->subjects,
            'teacherList'       => $this->teacherList,
            'teacherLinks'      => $this->teacherLinks,
            'teacherPhones'     => $this->teacherPhones,
            'studentList'       => $this->studentList,
            'terms'             => $this->terms,
            'fees'              => $this->fees,
            'exams'             => $this->exams,
            'whatsappPhone'     => $this->whatsappPhone,
            'whatsappVerified'  => $this->whatsappVerified,
            'messages'          => $this->messages,
        ];

        OnboardingSession::updateOrCreate(
            ['id' => $this->draftSessionId ?? 0],
            [
                'user_id'   => $user->id,
                'school_id' => $this->schoolId,
                'step'      => $this->step,
                'substep'   => $this->substep,
                'data'      => $data,
                'status'    => 'draft',
            ]
        );
    }

    private function deleteDraft()
    {
        if ($this->draftSessionId) {
            OnboardingSession::where('id', $this->draftSessionId)->delete();
            $this->draftSessionId = null;
        }
    }

    /**
     * School Admin mode: detect what's missing and jump to first incomplete step.
     */
    private function detectMissingSteps()
    {
        $sid = $this->schoolId;
        $missing = [];

        if (!\App\Models\Standard::where('school_id', $sid)->exists()) $missing[] = 'standards';
        if (!\App\Models\Subject::where('school_id', $sid)->exists()) $missing[] = 'subjects';
        if (!\App\Models\Teacherlink::where('school_id', $sid)->exists()) $missing[] = 'teachers';
        if (!\App\Models\AcademicTerm::where('school_id', $sid)->exists()) $missing[] = 'terms';
        if (!\App\Models\FeesCategories::where('school_id', $sid)->exists()) $missing[] = 'fees';
        if (!\App\Models\WhatsAppUser::where('user_id', auth()->id())->exists()) $missing[] = 'whatsapp_verify';

        if (empty($missing)) {
            $this->botSay("✅ Everything looks set up! Your school is ready to go.");
            $this->botSay("If you need help with anything, just ask.");
            $this->step = 99;
            return;
        }

        $this->botSay("I found **" . count($missing) . "** thing" . (count($missing) > 1 ? 's' : '') . " to set up:");
        foreach ($missing as $item) {
            $labels = [
                'standards' => '📚 Classes',
                'subjects'  => '📖 Subjects',
                'teachers'  => '👨‍🏫 Teachers',
                'terms'     => '📅 Academic terms',
                'fees'      => '💵 Fee structures',
                'whatsapp_verify' => '📱 WhatsApp verification',
            ];
            $this->botSay("  ❌ " . ($labels[$item] ?? $item));
        }

        // Jump to first missing step
        $stepNames = array_values($this->steps);
        foreach ($stepNames as $i => $name) {
            if (in_array($name, $missing)) {
                $this->step = $i;
                break;
            }
        }

        $current = $this->steps[$this->step] ?? '';
        $labels = [
            'standards' => "Let's start with classes. What classes does your school have?",
            'subjects'  => "Let's set up subjects per class.",
            'teachers'  => "Let's add teachers. Paste their names (one per line) or type 'skip'.",
            'terms'     => "Let's set up academic terms.",
            'fees'      => "Let's add fee categories. Type a fee name or 'skip'.",
            'whatsapp_verify' => "Let's verify your WhatsApp number for school notifications.",
        ];
        $this->botSay($labels[$current] ?? "Let's continue setting up.");
    }

    public function show() { $this->visible = true; }
    public function hide() { $this->visible = false; $this->maximized = false; }
    public function maximize() { $this->maximized = true; }
    public function restore() { $this->maximized = false; }

    // ── Button-driven confirm/edit ──
    public function confirmYes()
    {
        $this->input = 'yes';
        $this->send();
    }
    public function confirmNo()
    {
        $stepName = $this->steps[$this->step] ?? '';
        if ($this->substep >= 2) {
            $this->substep -= 2; // go back to data entry
        } else {
            $this->substep = 0;
        }
        if ($stepName === 'admin_account') {
            $this->botSay("Let's fix that. Type the correct value:");
        } else {
            $this->botSay("No problem — let's try again.");
        }
    }
    public function commit()
    {
        $this->input = 'commit';
        $this->send();
    }
    public function editBeforeCommit()
    {
        $this->reviewData = [];
        $this->botSay("No problem! Tell me what needs to change and we'll go back to fix it. | Type the step name: plan, school, admin, co-admin, classes, subjects, teachers, students, terms, fees, exams");
        $this->substep = 0;
    }
    public function resetOnboarding()
    {
        // In complete mode, transition to assistant mode (admin is done)
        if ($this->mode === 'complete' || $this->mode === 'assistant') {
            $this->mode = 'assistant';
            $this->step = 99;
            $this->substep = 0;
            $this->messages = [];
            $this->reviewData = [];
            $this->botSay("I'm your AI assistant now. Ask me anything about your school — view reports, check stats, or manage settings.");
            return;
        }

        // Create mode: full reset for new school
        $this->deleteDraft();
        $this->step = 0;
        $this->substep = 0;
        $this->messages = [];
        $this->reviewData = [];
        $this->schoolName = '';
        $this->schoolType = 'primary';
        $this->schoolLevel = '';
        $this->schoolGender = '';
        $this->adminName = '';
        $this->adminEmail = '';
        $this->schoolPhone = '';
        $this->adminPassword = '';
        $this->coAdminName = '';
        $this->coAdminEmail = '';
        $this->coAdminUserId = null;
        $this->academicYearLabel = '';
        $this->selectedPlanId = null;
        $this->standards = [];
        $this->subjects = [];
        $this->teacherList = [];
        $this->teacherLinks = [];
        $this->studentList = [];
        $this->terms = [];
        $this->fees = [];
        $this->exams = [];
        $this->whatsappPhone = '';
        $this->whatsappSentOtp = '';
        $this->whatsappVerified = false;
        $this->schoolId = null;
        $this->mode = 'create';
        $this->botSay("Hello! I'll help you set up a new school on KlassApp.");
        $this->botSay("First, let's choose a plan. | Select one of the plans below to get started.");
    }

    public function updatedAttachment()
    {
        $this->validate(['attachment' => 'file|mimes:csv,txt,xlsx,xls,pdf,png,jpg,jpeg,docx|max:5120']);

        $ext = strtolower($this->attachment->getClientOriginalExtension());
        $parsable = in_array($ext, ['csv', 'txt', 'xlsx', 'xls', 'pdf', 'docx']);

        if ($parsable) {
            $stepName = $this->steps[$this->step] ?? '';
            $names = $this->extractNamesFromFile($this->attachment->getRealPath(), $ext);

            if ($stepName === 'teacher_links') {
                $links = $this->extractTeacherLinksFromFile($this->attachment->getRealPath(), $ext);
                if (count($links) > 0) {
                    $this->teacherLinks = $links;
                    $this->userSay("📎 Uploaded " . count($links) . " teacher link(s) from file");
                    $this->botSay("Parsed **" . count($links) . "** teacher link(s) from your file. Continuing...");
                    // Auto-advance — file upload is explicit confirmation
                    $this->substep = 2;
                    $this->input = 'yes';
                    $this->send();
                    return;
                } else {
                    $this->botSay("I couldn't find any valid teacher links. Make sure your file has columns: Teacher, Subject, Class.");
                }
            } elseif (in_array($stepName, ['teachers', 'students']) && count($names) > 0) {
                if ($stepName === 'teachers') $this->teacherList = $names;
                else $this->studentList = $names;
                $this->userSay("📎 Uploaded " . count($names) . " names from file");
                $this->botSay("Parsed **" . count($names) . "** names from your file. Continue?");
            } elseif ($stepName === 'standards' && count($names) > 0) {
                $this->standards = array_map(fn($n) => ['name' => $n], $names);
                $this->userSay("📎 Uploaded " . count($names) . " class(es) from file");
                $this->botSay("Parsed **" . count($names) . "** class(es) from your file. Continue?");
            } elseif ($stepName === 'subjects' && count($names) > 0) {
                // For subjects, if file has 2+ columns, assume [class, subject] format
                $headers = [];
                $dataRows = [];
                try {
                    if (in_array($ext, ['xlsx', 'xls'])) {
                        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($this->attachment->getRealPath());
                        $reader->setReadDataOnly(true);
                        $spreadsheet = $reader->load($this->attachment->getRealPath());
                        $allRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
                        $headers = array_map('trim', $allRows[0] ?? []);
                        $dataRows = array_slice($allRows, 1);
                    } elseif ($ext === 'csv' || $ext === 'txt') {
                        $handle = fopen($this->attachment->getRealPath(), 'r');
                        $first = true;
                        while (($line = fgetcsv($handle)) !== false) {
                            if ($first) { $headers = array_map('trim', $line); $first = false; continue; }
                            $dataRows[] = $line;
                        }
                        fclose($handle);
                    }
                } catch (\Exception $e) {}

                if (!empty($headers) && count($headers) >= 2) {
                    $subjectsByClass = [];
                    foreach ($dataRows as $row) {
                        $className = trim($row[0] ?? '');
                        $subjectName = trim($row[1] ?? '');
                        if ($className && $subjectName) {
                            $subjectsByClass[$className][] = $subjectName;
                        }
                    }
                    if (!empty($subjectsByClass)) {
                        $this->subjects = $subjectsByClass;
                        $total = collect($subjectsByClass)->flatten()->count();
                        $this->userSay("📎 Uploaded subjects from file");
                        $this->botSay("Parsed **{$total}** subject(s) across **" . count($subjectsByClass) . "** class(es). Continue?");
                    } else {
                        $this->botSay("Couldn't parse subjects. Make sure your file has columns: Class, Subject.");
                    }
                } else {
                    // Single column — apply to all classes
                    if (!empty($this->standards)) {
                        $firstClass = $this->standards[0]['name'] ?? 'default';
                        $this->subjects = [$firstClass => $names];
                        $this->botSay("Parsed **" . count($names) . "** subject(s). Continue?");
                    } else {
                        $this->botSay("Please set up classes first, then upload subjects.");
                    }
                }
            } elseif (count($names) > 0) {
                $this->botSay("File received with " . count($names) . " names. We'll use this when we get to the teachers/students step.");
            } else {
                $this->botSay("I couldn't find any names in that file. Make sure it contains a list of names (one per line).");
            }
        } else {
            $fileName = $this->attachment->getClientOriginalName();
            $size = round($this->attachment->getSize() / 1024, 1);
            $this->userSay("📎 Uploaded: {$fileName} ({$size} KB)");
            $this->botSay("Received **{$fileName}**. I can extract names from spreadsheets (CSV, XLSX, XLS), documents (PDF, DOCX), and text files.");
        }

        $this->attachment = null;
    }

    /**
     * Extract names from a tabular file (CSV/TXT/XLSX/XLS).
     * Returns an array of full names (first + last where available).
     */
    private function extractNamesFromFile(string $path, string $ext): array
    {
        $headers = [];
        $dataRows = [];

        if (in_array($ext, ['xlsx', 'xls'])) {
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($path);
                $allRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
                if (empty($allRows)) return [];
                $headers = array_map('trim', $allRows[0]);
                $dataRows = array_slice($allRows, 1);
            } catch (\Exception $e) {
                \Log::warning('XLSX parse failed: ' . $e->getMessage());
                return [];
            }
        } elseif ($ext === 'pdf') {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($path);
                $text = $pdf->getText();
                return $this->parseNameList($text);
            } catch (\Exception $e) {
                \Log::warning('PDF parse failed: ' . $e->getMessage());
                return [];
            }
        } elseif ($ext === 'docx') {
            try {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
                $text = '';
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $text .= $element->getText() . "\n";
                        } elseif (method_exists($element, 'getElements')) {
                            foreach ($element->getElements() as $child) {
                                if (method_exists($child, 'getText')) {
                                    $text .= $child->getText() . "\n";
                                }
                            }
                        }
                    }
                }
                return $this->parseNameList($text);
            } catch (\Exception $e) {
                \Log::warning('DOCX parse failed: ' . $e->getMessage());
                return [];
            }
        } else {
            $handle = fopen($path, 'r');
            if (!$handle) return [];
            $headerLine = fgetcsv($handle);
            if ($headerLine === false) { fclose($handle); return []; }
            $headers = array_map('trim', $headerLine);
            while (($row = fgetcsv($handle)) !== false) {
                $dataRows[] = $row;
            }
            fclose($handle);
        }

        // Spreadsheet/CSV path — try column-based extraction
        if (empty($dataRows)) return [];

        // Find the name column index
        $nameIdx = null;
        foreach (['firstname', 'name', 'First Name', 'Name', 'student_name', 'teacher_name', 'full_name'] as $col) {
            $idx = array_search($col, $headers);
            if ($idx !== false) { $nameIdx = $idx; break; }
        }

        // Find lastname column index
        $lastIdx = array_search('lastname', array_map('strtolower', $headers));
        if ($lastIdx === false) {
            $lastIdx = array_search('last_name', array_map('strtolower', $headers));
        }

        $names = [];
        foreach ($dataRows as $row) {
            if (!is_array($row) || count($row) === 0) continue;
            $row = array_map('trim', $row);

            // Determine name value
            if ($nameIdx !== null && !empty($row[$nameIdx] ?? '')) {
                $name = $row[$nameIdx];
            } elseif (count($row) > 0 && !empty($row[0])) {
                $name = $row[0]; // fallback to first column
            } else {
                continue;
            }

            // Append lastname if available
            if ($lastIdx !== false && !empty($row[$lastIdx] ?? '')) {
                $name .= ' ' . $row[$lastIdx];
            }

            $names[] = $name;
        }

        return $names;
    }

    /**
     * Extract teacher-subject-class links from a spreadsheet (CSV/XLSX).
     * Expects columns: Teacher, Subject, Class, Phone (optional).
     */
    private function extractTeacherLinksFromFile(string $path, string $ext): array
    {
        $rows = [];

        try {
            if (in_array($ext, ['xlsx', 'xls'])) {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($path);
                $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            } elseif ($ext === 'csv' || $ext === 'txt') {
                $handle = fopen($path, 'r');
                while (($line = fgetcsv($handle)) !== false) {
                    $rows[] = $line;
                }
                fclose($handle);
            } else {
                return [];
            }
        } catch (\Exception $e) {
            \Log::warning('Teacher links file parse error: ' . $e->getMessage());
            return [];
        }

        if (empty($rows)) return [];

        // Find column indices from header row
        $header = array_map('strtolower', array_map('trim', $rows[0]));
        $colMap = ['teacher' => null, 'subject' => null, 'class' => null, 'phone' => null];
        foreach ($header as $i => $h) {
            if (str_contains($h, 'teacher') || str_contains($h, 'name')) $colMap['teacher'] = $i;
            if (str_contains($h, 'subject')) $colMap['subject'] = $i;
            if (str_contains($h, 'class') || str_contains($h, 'grade')) $colMap['class'] = $i;
            if (str_contains($h, 'phone') || str_contains($h, 'mobile') || str_contains($h, 'tel')) $colMap['phone'] = $i;
        }

        // If no header match, assume column order: Teacher, Subject, Class, Phone
        if ($colMap['teacher'] === null && count($rows[0]) >= 3) {
            $colMap = ['teacher' => 0, 'subject' => 1, 'class' => 2, 'phone' => 3];
        }

        if ($colMap['teacher'] === null || $colMap['subject'] === null || $colMap['class'] === null) {
            return [];
        }

        $parsed = [];
        $dataRows = array_slice($rows, 1); // skip header

        foreach ($dataRows as $row) {
            $teacher = trim($row[$colMap['teacher']] ?? '');
            $subject = trim($row[$colMap['subject']] ?? '');
            $class = trim($row[$colMap['class']] ?? '');
            $phone = $colMap['phone'] !== null ? trim($row[$colMap['phone']] ?? '') : '';

            if ($teacher && $subject && $class) {
                // Validate against existing teacher list and standards
                $teacherExists = in_array($teacher, $this->teacherList);
                $classExists = collect($this->standards)->pluck('name')->contains($class);

                if ($teacherExists && $classExists) {
                    $parsed[] = [
                        'teacher' => $teacher,
                        'subject' => $subject,
                        'class'   => $class,
                        'phone'   => $phone,
                    ];
                    if ($phone) {
                        $this->teacherPhones[$teacher] = $phone;
                    }
                }
            }
        }

        return $parsed;
    }

    public function render()
    {
        return view('livewire.agent-toshi');
    }

    // ── Agent says something ──
    private function botSay(string $message)
    {
        $this->messages[] = ['role' => 'bot', 'text' => $message];
        $this->persistState();
    }

    // ── User says something ──
    private function userSay(string $message)
    {
        $this->messages[] = ['role' => 'user', 'text' => $message];
        $this->persistState();
    }

    /**
     * Persist Toshi state to session so it survives page refreshes.
     */
    private function persistState(): void
    {
        session(['toshi_state' => [
            'messages'          => $this->messages,
            'step'              => $this->step,
            'substep'           => $this->substep,
            'mode'              => $this->mode,
            'schoolId'          => $this->schoolId,
            'schoolName'        => $this->schoolName,
            'schoolType'        => $this->schoolType,
            'schoolLevel'       => $this->schoolLevel,
            'schoolGender'      => $this->schoolGender,
            'schoolEmail'       => $this->schoolEmail,
            'schoolPhone'       => $this->schoolPhone,
            'adminName'         => $this->adminName,
            'adminEmail'        => $this->adminEmail,
            'adminPassword'     => $this->adminPassword,
            'coAdminName'       => $this->coAdminName,
            'coAdminEmail'      => $this->coAdminEmail,
            'coAdminUserId'     => $this->coAdminUserId,
            'academicYearLabel' => $this->academicYearLabel,
            'selectedPlanId'    => $this->selectedPlanId,
            'standards'         => $this->standards,
            'subjects'          => $this->subjects,
            'teacherList'       => $this->teacherList,
            'teacherLinks'      => $this->teacherLinks,
            'teacherPhones'     => $this->teacherPhones,
            'studentList'       => $this->studentList,
            'terms'             => $this->terms,
            'fees'              => $this->fees,
            'exams'             => $this->exams,
            'whatsappPhone'     => $this->whatsappPhone,
            'whatsappVerified'  => $this->whatsappVerified,
            'reviewData'        => $this->reviewData,
            'draftSessionId'    => $this->draftSessionId,
        ]]);
    }

    /**
     * Restore Toshi state from session after a page refresh.
     * Returns true if state was restored, false if no state found.
     */
    private function restoreState(): bool
    {
        $state = session('toshi_state');
        if (empty($state)) return false;

        foreach ($state as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        // Always start closed on refresh — user clicks pill to open
        $this->visible = false;
        $this->maximized = false;
        return true;
    }

    // ── Mark step as done ──
    private function advance(?int $to = null)
    {
        $this->step = $to ?? $this->step + 1;
        $this->saveDraft();
    }

    /**
     * Returns true if the current (or given) step is mandatory and cannot be skipped.
     */
    public function isStepMandatory(?string $stepName = null): bool
    {
        return in_array($stepName ?? $this->steps[$this->step] ?? '', $this->mandatorySteps);
    }

    // ════════════════════════════════════════════════
    //  Validation Helpers
    // ════════════════════════════════════════════════

    /**
     * Validate a required text field is non-empty with optional min length.
     * Returns the trimmed string on success, null on failure (botSay already called).
     */
    private function validateRequired(string $text, string $label, int $minLen = 1): ?string
    {
        $text = trim($text);
        if ($text === '') {
            $this->botSay("**{$label}** cannot be empty. Please enter a value.");
            return null;
        }
        if (mb_strlen($text) < $minLen) {
            $this->botSay("**{$label}** must be at least {$minLen} characters. You entered " . mb_strlen($text) . ".");
            return null;
        }
        return $text;
    }

    /**
     * Normalize a Ugandan phone number to +256XXXXXXXXX format.
     * Accepts: +256701234567, 256701234567, 0701234567, +256 701 234 567
     * Returns normalized number or null on failure.
     */
    private function normalizeUgandaPhone(string $phone): ?string
    {
        $phone = trim(preg_replace('/[\s\-\(\)]/', '', $phone));

        // Already in +256 format
        if (preg_match('/^\+256[0-9]{9}$/', $phone)) {
            return $phone;
        }

        // 256XXXXXXXXX (no +)
        if (preg_match('/^256[0-9]{9}$/', $phone)) {
            return '+' . $phone;
        }

        // 07XXXXXXXX or 07XXX XXX XXX
        if (preg_match('/^0[0-9]{9}$/', $phone)) {
            return '+256' . substr($phone, 1);
        }

        // 7XXXXXXXX (no prefix)
        if (preg_match('/^7[0-9]{8}$/', $phone)) {
            return '+256' . $phone;
        }

        $this->botSay("That doesn't look like a valid Ugandan phone number. Please use format +256701234567.");
        return null;
    }

    /**
     * Validate an email address.
     * Returns the trimmed email on success, null on failure.
     */
    private function validateEmail(string $email): ?string
    {
        $email = trim($email);
        if ($email === '') {
            $this->botSay("Email address cannot be empty.");
            return null;
        }
        // Remove common typos
        $email = str_replace([' ', '，'], ['', ','], $email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->botSay("**{$email}** doesn't look like a valid email. Please try again (e.g., admin@school.ug).");
            return null;
        }
        return $email;
    }

    /**
     * Check if a school name is already taken.
     */
    private function isDuplicateSchool(string $name): bool
    {
        return \App\Models\School::where('name', trim($name))->exists();
    }

    // ── Handle user input ──
    public function send()
    {
        $text = trim($this->input);
        if ($text === '') return;

        $this->userSay($text);
        $this->input = '';

        // Handle draft resume commands
        if ($this->draftSessionId && in_array(strtolower($text), ['reset', 'restart', 'start over'])) {
            $this->deleteDraft();
            $this->step = 0;
            $this->substep = 0;
            $this->messages = [];
            $this->schoolName = '';
            $this->selectedPlanId = null;
            $this->draftSessionId = null;
            $this->botSay("Restarting from scratch. First, let's choose a plan.");
            return;
        }

        $stepName = $this->steps[$this->step];

        // Block explicit skip on mandatory steps ('no' is still allowed — it routes to custom input)
        if ($this->isStepMandatory($stepName) && in_array(strtolower($text), ['skip', 'later'])) {
            $label = str_replace('_', ' ', $stepName);
            $this->botSay("**" . ucwords($label) . "** is required to set up the school. Please complete this step.");
            return;
        }

        match ($stepName) {
            'plan_selection' => $this->handlePlanSelection($text),
            'school_info'    => $this->handleSchoolInfo($text),
            'admin_account'  => $this->handleAdminAccount($text),
            'co_admin_invite'=> $this->handleCoAdminInvite($text),
            'academic_year'  => $this->handleAcademicYear($text),
            'standards'      => $this->handleStandards($text),
            'subjects'       => $this->handleSubjects($text),
            'teachers'       => $this->handleTeachers($text),
            'teacher_links'  => $this->handleTeacherLinks($text),
            'students'       => $this->handleStudents($text),
            'terms'          => $this->handleTerms($text),
            'fees'           => $this->handleFees($text),
            'exams'          => $this->handleExams($text),
            'whatsapp_verify'=> $this->handleWhatsAppVerify($text),
            'review'         => $this->handleReview($text),
            default          => $this->advance(),
        };
    }

    // ════════════════════════════════════════════════
    //  Step 0: Plan Selection
    //  (Buttons rendered in blade — this handles text fallback)
    // ════════════════════════════════════════════════
    public function selectPlan(int $planId)
    {
        $plan = \App\Models\Plan::find($planId);
        if (!$plan) {
            $this->botSay("I didn't recognize that plan. Please select from the buttons above.");
            return;
        }
        $this->selectedPlanId = $plan->id;
        $this->userSay("Selected plan: **{$plan->name}**");
        $this->botSay("**{$plan->name}** plan selected. | Now, what's the name of your school?");
        $this->advance();
    }

    private function handlePlanSelection(string $text)
    {
        $plan = \App\Models\Plan::where('name', strtolower($text))->first();
        if ($plan) {
            $this->selectPlan($plan->id);
            return;
        }
        $this->botSay("Please select a plan using the buttons above.");
    }

    // ════════════════════════════════════════════════
    //  Step 1: School Info
    //  Uses substep for confirm/edit pattern
    // ════════════════════════════════════════════════
    private function handleSchoolInfo(string $text)
    {
        if ($this->substep === 0) {
            // Collecting school name
            $name = $this->validateRequired($text, 'School name', 3);
            if ($name === null) return;
            if ($this->isDuplicateSchool($name)) {
                $this->botSay("A school named **{$name}** already exists. Please use a different name.");
                return;
            }
            $this->schoolName = $name;
            $this->botSay("🏫 **{$this->schoolName}**");
            $this->botSay("Is the name correct? (yes / no)");
            $this->substep = 1; // awaiting name confirmation
            return;
        }

        if ($this->substep === 1) {
            // Confirming school name
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if (!$yes) {
                $this->botSay("No problem — type the correct school name:");
                $this->substep = 0; // go back to name entry
                return;
            }
            // Name confirmed — school type is now button-driven in blade
            $this->botSay("Great! Now select the school type from the options below.");
            $this->substep = 2; // awaiting type selection (via button)
            return;
        }

        // substep === 2 — type already selected via button, shouldn't reach here via text
        $this->botSay("Please use the buttons above to select the school type.");
    }

    // ── Button-driven school type selection ──
    public function setSchoolType(string $type, string $level = '', string $gender = '')
    {
        $this->schoolType = $type;
        $this->schoolLevel = $level;
        $this->schoolGender = $gender;

        $label = ucfirst($type);
        if ($level) $label .= ' — ' . strtoupper($level);
        if ($gender) $label .= ' — ' . ucfirst($gender);

        $this->userSay("School type: {$label}");
        $this->botSay("**{$label}** — got it!");
        $this->botSay("Now let's set up the admin account. | What is the admin's email address?");
        $this->substep = 0;
        $this->advance();
    }

    // ════════════════════════════════════════════════
    //  Step 2: Admin Account (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleAdminAccount(string $text)
    {
        // substep 0: collect admin email
        if ($this->substep === 0) {
            $email = $this->validateEmail($text);
            if ($email === null) return;
            $this->adminEmail = $email;
            $this->botSay("You entered: **{$this->adminEmail}** | Is this correct? (yes / no)");
            $this->substep = 1;
            return;
        }

        // substep 1: confirm email
        if ($this->substep === 1) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->botSay("Email confirmed. | What is the admin's full name?");
                $this->substep = 2;
                return;
            }
            $this->botSay("No problem. Please enter the correct admin email:");
            $this->substep = 0;
            return;
        }

        // substep 2: collect admin name
        if ($this->substep === 2) {
            $name = $this->validateRequired($text, 'Admin name', 3);
            if ($name === null) return;
            $this->adminName = $name;
            $this->botSay("You entered: **{$this->adminName}** | Is this correct? (yes / no)");
            $this->substep = 3;
            return;
        }

        // substep 3: confirm name
        if ($this->substep === 3) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->botSay("Name confirmed. | What is the admin's WhatsApp phone number? This will be used for school notifications. (format: +256XXXXXXXXX)");
                $this->substep = 4;
                return;
            }
            $this->botSay("No problem. Please enter the correct admin name:");
            $this->substep = 2;
            return;
        }

        // substep 4: collect admin phone
        if ($this->substep === 4) {
            $phone = $this->normalizeUgandaPhone($text);
            if ($phone === null) return;
            $this->schoolPhone = $phone;
            $this->botSay("You entered: **{$phone}** | Is this correct? (yes / no)");
            $this->substep = 5;
            return;
        }

        // substep 5: confirm phone
        if ($this->substep === 5) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->botSay("Phone confirmed. | Set a password for the admin account (min 6 characters), or type 'default' to use the default password.");
                $this->substep = 6;
                return;
            }
            $this->botSay("No problem. Please enter the correct phone number:");
            $this->substep = 4;
            return;
        }

        // substep 6: collect password
        if ($this->substep === 6) {
            if (strtolower(trim($text)) === 'default') {
                $this->adminPassword = 'password';
                $this->botSay("Using default password. The admin can change this later.");
            } else {
                $pw = trim($text);
                if (strlen($pw) < 6) {
                    $this->botSay("Password must be at least 6 characters. Try again, or type 'default'.");
                    return;
                }
                $this->adminPassword = $pw;
                $this->botSay("✅ Password set.");
            }
            $this->substep = 0;
            $this->advance();
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 3: Co-admin Invite (optional — dual-mode)
    //  Create mode: enter email + name → new admin user
    //  Complete mode: pick an existing teacher to promote → usergroup_id 5→3
    // ════════════════════════════════════════════════
    public function getAvailableStaff()
    {
        if ($this->mode !== 'complete' || !$this->schoolId) return collect();
        return \App\Models\User::where('school_id', $this->schoolId)
            ->where('usergroup_id', 5)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function coAdminInviteYes()
    {
        $staff = $this->getAvailableStaff();
        if ($this->mode === 'complete' && $staff->isNotEmpty()) {
            $this->substep = 10;
            $count = $staff->count();
            $this->botSay("There **{$count}** teacher" . ($count > 1 ? 's' : '') . " on staff. Select one to promote to co-admin:");
            return;
        }
        $this->substep = 1;
        $this->botSay("Great! Enter the co-admin's email address:");
    }

    public function coAdminInviteSkip()
    {
        $this->botSay("Skipped. You can add more admins later from the admin panel.");
        $this->substep = 0;
        $this->advance();
    }

    /**
     * Promote an existing teacher (usergroup_id: 5) to co-admin (usergroup_id: 3).
     */
    public function promoteCoAdmin(int $userId)
    {
        $user = \App\Models\User::find($userId);
        if (!$user || $user->school_id !== $this->schoolId) {
            $this->botSay("Invalid selection. Please try again.");
            return;
        }

        $this->coAdminUserId = $user->id;
        $this->coAdminName = $user->name;
        $this->coAdminEmail = $user->email;
        $this->botSay("**{$user->name}** ({$user->email}) will be promoted to co-admin on commit.");
        $this->substep = 0;
        $this->advance();
    }

    public function promoteCoAdminAddNew()
    {
        $this->substep = 1;
        $this->botSay("Enter the new co-admin's email address:");
    }

    private function handleCoAdminInvite(string $text)
    {
        // substep 0: buttons only — text fallback (skip or email)
        if ($this->substep === 0) {
            $skip = in_array(strtolower($text), ['skip', 'later', 'no', 'none']);
            if ($skip) {
                $this->coAdminInviteSkip();
                return;
            }
            $email = $this->validateEmail($text);
            if ($email === null) return;
            $this->coAdminEmail = $email;
            $this->botSay("Co-admin email: **{$email}**. | What is their full name?");
            $this->substep = 2;
            return;
        }

        // substep 1: collect email (from "Yes" or "Add someone else")
        if ($this->substep === 1) {
            $email = $this->validateEmail($text);
            if ($email === null) return;
            $this->coAdminEmail = $email;
            $this->botSay("Co-admin email: **{$email}**. | What is their full name?");
            $this->substep = 2;
            return;
        }

        // substep 2: collect co-admin name
        if ($this->substep === 2) {
            $name = $this->validateRequired($text, 'Co-admin name', 3);
            if ($name === null) return;
            $this->coAdminName = $name;
            $this->botSay("Co-admin **{$name}** added. They'll receive login credentials when the school is created.");
            $this->substep = 0;
            $this->advance();
            return;
        }

        // substep 10: text fallback for teacher selection (email or name search)
        if ($this->substep === 10) {
            $this->botSay("Please use the buttons above to select a teacher, or type **skip**.");
        }
    }

    // ════════════════════════════════════════════════
    //  Step 5: Academic Year (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleAcademicYear(string $text)
    {
        // substep 0: show current year, ask if correct
        if ($this->substep === 0) {
            $year = date('Y');
            $this->academicYearLabel = (string) $year;
            $this->botSay("Academic year: **{$year}** | Is that correct? (yes / no)");
            $this->substep = 1;
            return;
        }

        // substep 1: confirm year or ask for custom
        if ($this->substep === 1) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("Please enter the academic year (e.g. 2025 or 2025/2026):");
            $this->substep = 2;
            return;
        }

        // substep 2: accept custom year
        if ($this->substep === 2) {
            $this->academicYearLabel = trim($text);
            $this->botSay("Academic year set to **{$this->academicYearLabel}**.");
            $this->substep = 0;
            $this->advance();
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 6: Standards / Classes (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleStandards(string $text)
    {
        // substep 0: load defaults, show list, ask if correct
        if ($this->substep === 0) {
            $defaults = $this->curriculumDefaults();
            $this->standards = $defaults['classes'] ?? [];
            $classList = implode(', ', array_column($this->standards, 'name'));
            $this->botSay("I'll create these classes: **{$classList}** | Is this list correct? (yes / no)");
            $this->substep = 1;
            return;
        }

        // substep 1: confirm or ask for custom list
        if ($this->substep === 1) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("Please enter the class names separated by commas (e.g. Primary 1, Primary 2, Primary 3):");
            $this->substep = 2;
            return;
        }

        // substep 2: parse custom list and advance
        if ($this->substep === 2) {
            $names = array_map('trim', explode(',', $text));
            $this->standards = [];
            foreach ($names as $name) {
                if (strlen($name) > 0) {
                    $this->standards[] = ['name' => $name];
                }
            }
            $classList = implode(', ', array_column($this->standards, 'name'));
            $this->botSay("Classes set to: **{$classList}**.");
            $this->substep = 0;
            $this->advance();
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 7: Subjects (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleSubjects(string $text)
    {
        // substep 0: load defaults, show sample, ask if fine
        if ($this->substep === 0) {
            $defaults = $this->curriculumDefaults();
            $this->subjects = $defaults['subjects'] ?? [];
            $subjectList = collect($this->subjects)->first() ?? [];
            if (is_array($subjectList)) {
                $subjectList = implode(', ', array_slice($subjectList, 0, 5)) . '...';
            }
            $this->botSay("Default subjects assigned per class (NCDC curriculum), e.g. {$subjectList} | Is this fine? (yes / no)");
            $this->substep = 1;
            return;
        }

        // substep 1: confirm or enter custom subjects (mandatory step)
        if ($this->substep === 1) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("Please enter the subjects separated by commas (e.g. Mathematics, English, Science):");
            $this->substep = 2;
            return;
        }

        // substep 2: parse custom subjects (mandatory — cannot skip)
        if ($this->substep === 2) {
            $names = array_map('trim', explode(',', $text));
            $names = array_filter($names, fn($n) => strlen($n) > 1);
            if (empty($names)) {
                $this->botSay("I need at least one subject. Please enter subjects separated by commas:");
                return;
            }
            $this->subjects = [
                ($this->standards[0]['name'] ?? 'default') => array_values($names),
            ];
            $this->botSay("Subjects saved: **" . implode(', ', $names) . "**.");
            $this->substep = 0;
            $this->advance();
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 8: Teachers (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleTeachers(string $text)
    {
        // substep 0: collect names or skip
        if ($this->substep === 0) {
            $skip = in_array(strtolower($text), ['skip', 'later', 'no', 'none']);
            if ($skip) {
                $this->botSay("Skipped. You can add teachers later in the admin panel.");
                $this->substep = 0;
                $this->advance();
                return;
            }
            $names = $this->parseNameList($text);
            if (count($names) > 0) {
                $this->teacherList = $names;
                $preview = implode(', ', array_slice($this->teacherList, 0, 3));
                $this->botSay("Parsed **" . count($this->teacherList) . "** teachers: {$preview}" . (count($this->teacherList) > 3 ? '...' : '') . " | Is this correct? (yes / no)");
                $this->substep = 1;
                return;
            }
            $this->botSay("I couldn't find any names. Please paste a list (one per line) or type 'skip' to add later.");
            return;
        }

        // substep 1: confirm teacher list
        if ($this->substep === 1) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("No problem. Please paste the correct teacher names (one per line) or type 'skip':");
            $this->substep = 0;
            return;
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
    //  Step 9: Teacher-Class-Subject Linking
    // ════════════════════════════════════════════════
    private function handleTeacherLinks(string $text)
    {
        // substep 0: explain format, collect input
        if ($this->substep === 0) {
            $skip = in_array(strtolower($text), ['skip', 'later', 'no', 'none']);
            if ($skip) {
                $this->botSay("Skipped. You can assign teachers to subjects later in the admin panel (Classes → select class → assign teachers).");
                $this->substep = 0;
                $this->advance();
                return;
            }

            // If teacher list is empty, skip automatically
            if (empty($this->teacherList)) {
                $this->botSay("No teachers to link. Moving on.");
                $this->substep = 0;
                $this->advance();
                return;
            }

            $classNames = collect($this->standards)->pluck('name')->implode(', ');
            $this->botSay(
                "Now let's link each teacher to their subjects and classes.\n\n"
                . "Your classes: *{$classNames}*\n"
                . "Your teachers: *" . implode(', ', $this->teacherList) . "*\n\n"
                . "Enter one line per teacher:\n"
                . "`Teacher Name | Subject | Class | Phone`\n\n"
                . "Phone is optional — use the teacher's WhatsApp number if known.\n\n"
                . "Example:\n"
                . "John Ssali | Mathematics | P.5 | +256701234567\n"
                . "Jane Okello | English | P.5\n"
                . "John Ssali | Science | P.6 | +256701234567\n\n"
                . "Type the full list, or type 'skip' to do this later."
            );
            $this->substep = 1;
            return;
        }

        // substep 1: parse the list
        if ($this->substep === 1) {
            $lines = array_filter(explode("\n", $text), fn($l) => trim($l) !== '');
            $parsed = [];
            $phones = [];

            foreach ($lines as $line) {
                $parts = array_map('trim', explode('|', $line));
                if (count($parts) < 3) continue;

                $teacherName = $parts[0];
                $subjectName = $parts[1];
                $className = $parts[2];
                $phone = $parts[3] ?? '';

                // Validate teacher exists in teacherList
                $teacherExists = in_array($teacherName, $this->teacherList);
                // Validate class exists in standards
                $classExists = collect($this->standards)->pluck('name')->contains($className);

                if ($teacherExists && $classExists) {
                    $parsed[] = [
                        'teacher' => $teacherName,
                        'subject' => $subjectName,
                        'class'   => $className,
                        'phone'   => $phone,
                    ];
                    // Store phone for teacher if provided
                    if ($phone) {
                        $phones[$teacherName] = $phone;
                    }
                }
            }

            if (empty($parsed)) {
                $this->botSay(
                    "I couldn't parse any valid entries. Make sure each line uses:\n"
                    . "`Teacher Name | Subject | Class | Phone`\n\n"
                    . "Example: John Ssali | Mathematics | P.5 | +256701234567\n\n"
                    . "Type your list again, or type 'skip'."
                );
                return;
            }

            $this->teacherLinks = $parsed;
            $this->teacherPhones = $phones;
            $preview = collect($parsed)->take(3)->map(
                fn($l) => "• {$l['teacher']} → {$l['subject']} ({$l['class']})" . ($l['phone'] ? " 📱{$l['phone']}" : '')
            )->implode("\n");

            $phoneCount = count($phones);
            $phoneNote = $phoneCount > 0 ? "\n📱 Phone numbers saved for {$phoneCount} teacher(s)." : '';

            $this->botSay(
                "Parsed **" . count($parsed) . "** teacher link(s):\n{$preview}{$phoneNote}"
                . (count($parsed) > 3 ? "\n...and " . (count($parsed) - 3) . " more" : '')
                . "\n\nIs this correct? (yes / no)"
            );
            $this->substep = 2;
            return;
        }

        // substep 2: confirmation
        if ($this->substep === 2) {
            if (in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok'])) {
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("Let's try again. Enter the teacher links one per line:\n`Teacher Name | Subject | Class | Phone`");
            $this->substep = 0;
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 10: Students (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleStudents(string $text)
    {
        // substep 0: collect names or skip
        if ($this->substep === 0) {
            $skip = in_array(strtolower($text), ['skip', 'later', 'no', 'none']);
            if ($skip) {
                $this->botSay("Skipped. You can add students later in the admin panel.");
                $this->substep = 0;
                $this->advance();
                return;
            }
            $names = $this->parseNameList($text);
            if (count($names) > 0) {
                $this->studentList = $names;
                $preview = implode(', ', array_slice($this->studentList, 0, 3));
                $this->botSay("Parsed **" . count($this->studentList) . "** students: {$preview}" . (count($this->studentList) > 3 ? '...' : '') . " | Is this correct? (yes / no)");
                $this->substep = 1;
                return;
            }
            $this->botSay("I couldn't find any names. Please paste a list (one per line) or type 'skip' to add later.");
            return;
        }

        // substep 1: confirm student list
        if ($this->substep === 1) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("No problem. Please paste the correct student names (one per line) or type 'skip':");
            $this->substep = 0;
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 11: Academic Terms (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleTerms(string $text)
    {
        // substep 0: set default 3 terms, show them, ask if correct
        if ($this->substep === 0) {
            $this->terms = [
                ['name' => 'Term I',   'start' => date('Y') . '-02-01', 'end' => date('Y') . '-04-30'],
                ['name' => 'Term II',  'start' => date('Y') . '-05-01', 'end' => date('Y') . '-08-31'],
                ['name' => 'Term III', 'start' => date('Y') . '-09-01', 'end' => date('Y') . '-12-31'],
            ];
            $this->botSay("Default Ugandan terms set: Term I (Feb-Apr), Term II (May-Aug), Term III (Sep-Dec). | Is this correct? (yes / no)");
            $this->substep = 1;
            return;
        }

        // substep 1: confirm or enter custom terms (mandatory step)
        if ($this->substep === 1) {
            $yes = in_array(strtolower($text), ['yes', 'y', 'correct', 'right', 'ok']);
            if ($yes) {
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("Please enter your custom terms (e.g. Term I, Term II, Term III):");
            $this->substep = 2;
            return;
        }

        // substep 2: parse custom terms (mandatory — cannot skip)
        if ($this->substep === 2) {
            $names = array_map('trim', explode(',', $text));
            $names = array_filter($names, fn($n) => strlen($n) > 1);
            if (empty($names)) {
                $this->botSay("I need at least one term name. Please enter your terms (e.g. Term I, Term II, Term III):");
                return;
            }
            $this->terms = [];
            foreach ($names as $name) {
                $this->terms[] = ['name' => $name, 'start' => now()->startOfYear(), 'end' => now()->endOfYear()];
            }
            $this->botSay("**" . count($this->terms) . "** terms saved: " . implode(', ', $names));
            $this->substep = 0;
            $this->advance();
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 12: Fees (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleFees(string $text)
    {
        // substep 0: collect first fee or skip
        if ($this->substep === 0) {
            $skip = in_array(strtolower($text), ['skip', 'later', 'no', 'none']);
            if ($skip) {
                $this->botSay("Skipped. You can set up fees later in the admin panel.");
                $this->substep = 0;
                $this->advance();
                return;
            }
            $fee = $this->validateRequired($text, 'Fee name');
            if ($fee === null) return;
            $this->fees[] = $fee;
            $this->botSay("Added **{$fee}**. | Add another fee category? (type name or 'done')");
            $this->substep = 1;
            return;
        }

        // substep 1: add more fees or finish
        if ($this->substep === 1) {
            $done = in_array(strtolower($text), ['done', 'no', 'skip', 'later', 'none']);
            if ($done) {
                $count = count($this->fees);
                $this->botSay("**{$count}** fee categor" . ($count === 1 ? 'y' : 'ies') . " saved.");
                $this->substep = 0;
                $this->advance();
                return;
            }
            $fee = $this->validateRequired($text, 'Fee name');
            if ($fee === null) return;
            $this->fees[] = $fee;
            $this->botSay("Added **{$fee}**. | Add another fee category? (type name or 'done')");
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 13: Exams (confirm/edit substeps)
    // ════════════════════════════════════════════════
    private function handleExams(string $text)
    {
        // substep 0: collect first exam or skip
        if ($this->substep === 0) {
            $skip = in_array(strtolower($text), ['skip', 'later', 'no', 'none']);
            if ($skip) {
                $this->botSay("Skipped. You can create exams later in the admin panel.");
                $this->substep = 0;
                $this->advance();
                return;
            }
            $exam = $this->validateRequired($text, 'Exam name');
            if ($exam === null) return;
            $this->exams[] = $exam;
            $this->botSay("Added **{$exam}**. | Add another exam? (type name or 'done')");
            $this->substep = 1;
            return;
        }

        // substep 1: add more exams or finish
        if ($this->substep === 1) {
            $done = in_array(strtolower($text), ['done', 'no', 'skip', 'later', 'none']);
            if ($done) {
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->exams[] = trim($text);
            $this->botSay("Added **" . trim($text) . "**. | Add another exam? (type name or 'done')");
            return;
        }
    }

    // ════════════════════════════════════════════════
    //  Step 14: WhatsApp Verification
    // ════════════════════════════════════════════════
    private function handleWhatsAppVerify(string $text)
    {
        // substep 0: confirm phone number or enter different one
        if ($this->substep === 0) {
            $phone = $this->schoolPhone ?: '';
            if ($phone) {
                $this->botSay("📱 We'll verify the admin's WhatsApp number: **{$phone}**");
                $this->botSay("Is this the right number? (yes / no)");
                $this->substep = 1;
                return;
            }
            $this->botSay("What's the admin's WhatsApp number? (e.g., +256701234567)");
            $this->substep = 2;
            return;
        }

        // substep 1: confirm phone
        if ($this->substep === 1) {
            if (in_array(strtolower($text), ['yes', 'y', 'correct', 'ok'])) {
                $this->whatsappPhone = $this->schoolPhone;
                $this->botSay("Sending verification code to **{$this->whatsappPhone}**...");
                $this->sendWhatsAppOtp();
                return;
            }
            $this->botSay("Enter the correct WhatsApp number:");
            $this->substep = 2;
            return;
        }

        // substep 2: enter different phone
        if ($this->substep === 2) {
            $phone = $this->normalizeUgandaPhone($text);
            if ($phone === null) return;
            $this->whatsappPhone = $phone;
            $this->botSay("Sending verification code to **{$this->whatsappPhone}**...");
            $this->sendWhatsAppOtp();
            return;
        }

        // substep 3: verify OTP
        if ($this->substep === 3) {
            $code = trim($text);
            if ($code === $this->whatsappSentOtp) {
                $this->whatsappVerified = true;
                $this->userSay("✅ Code verified!");
                $this->botSay("✅ WhatsApp verified for **{$this->whatsappPhone}**! The admin will receive school notifications here.");
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("That code doesn't match. Please try again, or type 'resend' for a new code.");
            $this->substep = 4;
            return;
        }

        // substep 4: wrong code — retry, resend, or skip
        if ($this->substep === 4) {
            if (strtolower($text) === 'resend') {
                $this->sendWhatsAppOtp();
                return;
            }
            if (in_array(strtolower($text), ['skip', 'later'])) {
                $this->botSay("You can verify WhatsApp later from the admin settings.");
                $this->substep = 0;
                $this->advance();
                return;
            }
            $code = trim($text);
            if ($code === $this->whatsappSentOtp) {
                $this->whatsappVerified = true;
                $this->userSay("✅ Code verified!");
                $this->botSay("✅ WhatsApp verified for **{$this->whatsappPhone}**!");
                $this->substep = 0;
                $this->advance();
                return;
            }
            $this->botSay("Still doesn't match. Type **resend** for a new code or **skip** to do this later.");
        }
    }

    private function sendWhatsAppOtp()
    {
        $otp = (string) rand(100000, 999999);
        $this->whatsappSentOtp = $otp;

        try {
            if ($this->whatsappPhone) {
                $sent = app(\App\Services\WhatsAppBusinessService::class)->sendTextSafe(
                    $this->whatsappPhone,
                    "Your KlassApp verification code is: {$otp}. It expires in 5 minutes.",
                );
                if (($sent['status'] ?? '') === 'success' || ($sent['success'] ?? false)) {
                    $this->botSay("📱 Verification code sent! Check WhatsApp on **{$this->whatsappPhone}**.");
                } else {
                    // Log the attempt but still show the code for demo/testing
                    \Log::info('WhatsApp OTP send result', $sent);
                    $this->botSay("📱 In production, a code would be sent via WhatsApp. For testing, use code: **{$otp}**");
                }
            }
        } catch (\Exception $e) {
            \Log::warning('WhatsApp OTP send failed: ' . $e->getMessage());
            $this->botSay("📱 Could not send via WhatsApp right now. For testing, use code: **{$otp}**");
        }

        $this->botSay("Enter the 6-digit code you received:");
        $this->substep = 3;
    }

    // ════════════════════════════════════════════════
    //  Step 15: Review & Commit
    // ════════════════════════════════════════════════
    private function handleReview(string $text)
    {
        // Build review data once (on first entry)
        if (empty($this->reviewData)) {
            $planName = $this->selectedPlanId ? \App\Models\Plan::find($this->selectedPlanId)?->name : '—';
            $schoolDisplay = $this->mode === 'complete'
                ? optional(\App\Models\School::find($this->schoolId))->name ?? 'Your school'
                : $this->schoolName;
            $this->reviewData = [
                'plan'         => $this->mode === 'create' ? ucfirst($planName ?: '—') : 'Already assigned',
                'schoolName'   => $schoolDisplay,
                'schoolType'   => $this->schoolType,
                'adminName'    => $this->adminName ?: (auth()->user()->name ?? '—'),
                'adminEmail'   => $this->adminEmail ?: (auth()->user()->email ?? '—'),
                'adminPhone'   => $this->schoolPhone,
                'coAdminName'  => $this->coAdminName ?: '',
                'coAdminEmail' => $this->coAdminEmail ?: '',
                'classCount'   => count($this->standards),
                'classList'    => implode(', ', array_column($this->standards, 'name')),
                'teacherCount' => count($this->teacherList),
                'teacherLinkCount' => count($this->teacherLinks),
                'studentCount' => count($this->studentList),
                'studentIds'   => count($this->studentList) > 0
                    ? count($this->studentList) . " students — each gets a unique KlassApp ID"
                    : '—',
                'termCount'    => count($this->terms),
                'feeCount'     => count($this->fees),
                'examCount'    => count($this->exams),
                'whatsapp'     => $this->whatsappVerified ? "✅ {$this->whatsappPhone}" : '⏳ Not verified',
                'mode'         => $this->mode,
            ];
            $action = $this->mode === 'complete' ? 'save these changes' : 'create this school';
            $this->botSay("📋 Review the changes below, then click **Confirm** to {$action}.");
        }

        if (strtolower($text) === 'commit') {
            try {
                $this->commitAll();
                $this->deleteDraft();
                $this->reviewData['committed'] = true;
                $this->reviewData['adminEmail'] = $this->adminEmail ?: (auth()->user()->email ?? '');
                $this->reviewData['adminPhone'] = $this->schoolPhone;
                $this->reviewData['adminPassword'] = $this->adminPassword ?: 'password';
                $this->reviewData['coAdminEmail'] = $this->coAdminEmail;
                $this->reviewData['coAdminPassword'] = $this->coAdminUserId ? null : ($this->adminPassword ?: 'password');
                $this->reviewData['coAdminPromoted'] = (bool) $this->coAdminUserId;
                $this->reviewData['mode'] = $this->mode;
                $this->step = 99;
                // Transition to assistant mode after successful commit
                $this->mode = 'assistant';
            } catch (\Illuminate\Database\QueryException $e) {
                \Log::error('Onboarding DB error: ' . $e->getMessage());
                $code = $e->getCode();
                if ($code == 23000) {
                    $this->botSay("This school or email already exists in the system. Please check for duplicates and try again.");
                } elseif ($code == 2002 || $code == 1045) {
                    $this->botSay("Unable to connect to the database. Please try again in a moment.");
                } else {
                    $this->botSay("A database error occurred. Please try again or contact support.");
                }
            } catch (\Exception $e) {
                \Log::error('Onboarding commit failed: ' . $e->getMessage());
                $this->botSay("Something unexpected happened. Please try again or contact support.");
            }
        }
    }

    // ════════════════════════════════════════════════
    //  Commit everything to the database
    // ════════════════════════════════════════════════
    private function commitAll()
    {
        DB::transaction(function () {
            // ════════════════════════════════════════════
            //  CREATE MODE: Full school + admin creation
            // ════════════════════════════════════════════
            if ($this->mode === 'create') {
                $school = School::create([
                    'name'    => $this->schoolName,
                    'email'   => $this->schoolEmail ?: Str::slug($this->schoolName) . '@klassapp.sch.ug',
                    'phone'   => $this->schoolPhone ?: '0700000000',
                    'status'  => 1,
                    'slug'    => Str::slug($this->schoolName),
                    'registration_country' => 'Uganda',
                ]);
                $this->schoolId = $school->id;
                $schoolId = $school->id;

                if ($this->selectedPlanId) {
                    CurrentPlan::create(['school_id' => $school->id, 'plan_id' => $this->selectedPlanId]);
                    Subscription::create([
                        'school_id'  => $school->id, 'plan_id' => $this->selectedPlanId,
                        'status' => 'active', 'start_date' => now(), 'end_date' => now()->addYear(),
                    ]);
                }

                $academicYear = AcademicYear::create([
                    'school_id' => $school->id, 'name' => $this->academicYearLabel ?: date('Y'),
                    'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(),
                    'type' => 'Current Academic Year',
                ]);

                $password = bcrypt($this->adminPassword ?: 'password');
                $adminUser = User::create([
                    'school_id' => $school->id, 'usergroup_id' => 3,
                    'name' => $this->adminName ?: 'School Admin',
                    'email' => $this->adminEmail ?: 'admin@' . Str::slug($this->schoolName) . '.sch.ug',
                    'password' => $password, 'status' => 'active', 'email_verified' => 1,
                ]);
                Userprofile::create([
                    'school_id' => $school->id, 'user_id' => $adminUser->id,
                    'usergroup_id' => 3, 'firstname' => $this->adminName ?: 'School',
                    'lastname' => 'Admin', 'status' => 'active',
                ]);

                // Co-admin
                $coAdminUser = null;
                if ($this->coAdminName && $this->coAdminEmail) {
                    $coAdminUser = User::create([
                        'school_id' => $school->id, 'usergroup_id' => 3,
                        'name' => $this->coAdminName,
                        'email' => $this->coAdminEmail,
                        'password' => $password, 'status' => 'active', 'email_verified' => 1,
                    ]);
                    Userprofile::create([
                        'school_id' => $school->id, 'user_id' => $coAdminUser->id,
                        'usergroup_id' => 3, 'firstname' => $this->coAdminName,
                        'lastname' => 'Co-Admin', 'status' => 'active',
                    ]);

                    try {
                        Mail::to($this->coAdminEmail)->queue(new CoAdminInviteMail(
                            $this->coAdminName, $this->coAdminEmail,
                            $this->adminPassword ?: 'password',
                            $school->name, false
                        ));
                    } catch (\Exception $e) {
                        \Log::warning('Co-admin invite email failed: ' . $e->getMessage());
                    }
                }

                $phase = Standard::create(['school_id' => $school->id, 'name' => $this->schoolType, 'order' => 1, 'status' => '1']);

                $firstStandardLink = null;

                foreach ($this->standards as $class) {
                    $section = Section::firstOrCreate(
                        ['school_id' => $school->id, 'name' => $class['name']],
                        ['value' => $class['name'], 'status' => '1']
                    );
                    $standardLink = StandardLink::create([
                        'school_id' => $school->id, 'academic_year_id' => $academicYear->id,
                        'standard_id' => $phase->id, 'section_id' => $section->id, 'status' => '1',
                    ]);
                    if (!$firstStandardLink) {
                        $firstStandardLink = $standardLink;
                    }
                    $classSubjects = $this->subjects[$class['name']] ?? [];
                    foreach ($classSubjects as $subjectName) {
                        Subject::firstOrCreate(
                            ['school_id' => $school->id, 'standard_id' => $phase->id, 'section_id' => $section->id, 'name' => $subjectName],
                            ['academic_year_id' => $academicYear->id, 'type' => 'core']
                        );
                    }
                }

                foreach ($this->teacherList as $name) {
                    $tEmail = Str::slug($name) . '@' . Str::slug($this->schoolName) . '.edu';
                    $phone = $this->teacherPhones[$name] ?? null;
                    $teacher = User::create([
                        'school_id' => $school->id, 'usergroup_id' => 5, 'name' => $name,
                        'email' => $tEmail, 'password' => $password, 'status' => 'active', 'email_verified' => 1,
                        'mobile_no' => $phone,
                    ]);
                    Userprofile::create([
                        'school_id' => $school->id, 'user_id' => $teacher->id, 'usergroup_id' => 5,
                        'firstname' => $name, 'lastname' => '', 'profession' => 'teacher', 'status' => 'active',
                    ]);
                }

                // Create teacher-class-subject links from parsed data
                foreach ($this->teacherLinks as $link) {
                    $teacherUser = User::where('school_id', $school->id)->where('name', $link['teacher'])->first();
                    $linkSection = Section::where('school_id', $school->id)->where('name', $link['class'])->first();
                    $linkStandardLink = $linkSection
                        ? StandardLink::where('school_id', $school->id)->where('section_id', $linkSection->id)->where('academic_year_id', $academicYear->id)->first()
                        : null;
                    $linkSubject = $linkSection
                        ? Subject::where('school_id', $school->id)->where('section_id', $linkSection->id)->where('name', $link['subject'])->first()
                        : null;

                    if ($teacherUser && $linkStandardLink && $linkSubject) {
                        Teacherlink::firstOrCreate([
                            'school_id' => $school->id,
                            'academic_year_id' => $academicYear->id,
                            'standardLink_id' => $linkStandardLink->id,
                            'subject_id' => $linkSubject->id,
                            'teacher_id' => $teacherUser->id,
                        ]);
                    }
                }

                // Create students from onboarding list
                foreach ($this->studentList as $index => $studentName) {
                    if (!trim($studentName)) continue;

                    $sEmail = 'student.' . ($index + 1) . '@' . Str::slug($this->schoolName) . '.sch.ug';
                    $studentUser = User::create([
                        'school_id' => $school->id, 'usergroup_id' => 6,
                        'name' => trim($studentName),
                        'email' => $sEmail, 'password' => $password,
                        'status' => 'active', 'email_verified' => 1,
                    ]);
                    Userprofile::create([
                        'school_id' => $school->id, 'user_id' => $studentUser->id, 'usergroup_id' => 6,
                        'firstname' => trim($studentName), 'lastname' => '', 'status' => 'active',
                    ]);
                    if ($firstStandardLink) {
                        // Generate KlassApp Student ID: KLS{school}{sequential}
                        $schoolCode = str_pad($school->id, 3, '0', STR_PAD_LEFT);
                        $seq = str_pad($index + 1, 4, '0', STR_PAD_LEFT);
                        $klassappId = "KLS{$schoolCode}{$seq}";

                        StudentAcademic::create([
                            'school_id' => $school->id,
                            'academic_year_id' => $academicYear->id,
                            'user_id' => $studentUser->id,
                            'standardLink_id' => $firstStandardLink->id,
                            'klassapp_student_id' => $klassappId,
                        ]);
                    }
                }

                foreach ($this->terms as $term) {
                    AcademicTerm::create([
                        'school_id' => $school->id, 'academic_year_id' => $academicYear->id,
                        'name' => $term['name'], 'start_date' => $term['start'], 'end_date' => $term['end'],
                    ]);
                }

                foreach ($this->fees as $feeName) {
                    if (is_string($feeName) && trim($feeName)) {
                        FeesCategories::create([
                            'school_id' => $school->id, 'standard_id' => $phase->id,
                            'name' => trim($feeName), 'amount' => 0,
                        ]);
                    }
                }

                if ($this->whatsappVerified && $this->whatsappPhone) {
                    \App\Models\WhatsAppUser::create([
                        'phone' => $this->whatsappPhone, 'user_id' => $adminUser->id,
                        'school_id' => $this->schoolId,
                        'verified_at' => now(), 'opted_in' => true,
                    ]);
                }
            }

            // ════════════════════════════════════════════
            //  COMPLETE MODE: Only add missing items
            // ════════════════════════════════════════════
            if ($this->mode === 'complete') {
                $schoolId = $this->schoolId;
                $user = auth()->user();

                $academicYear = \App\Models\AcademicYear::where('school_id', $schoolId)->first();
                if (!$academicYear) {
                    $academicYear = AcademicYear::create([
                        'school_id' => $schoolId, 'name' => date('Y'),
                        'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(),
                        'type' => 'Current Academic Year',
                    ]);
                }

                $phase = \App\Models\Standard::where('school_id', $schoolId)->first();
                if (!$phase) {
                    $phase = Standard::create(['school_id' => $schoolId, 'name' => 'default', 'order' => 1, 'status' => '1']);
                }

                // Create standards if missing
                foreach ($this->standards as $class) {
                    Section::firstOrCreate(
                        ['school_id' => $schoolId, 'name' => $class['name']],
                        ['value' => $class['name'], 'status' => '1']
                    );
                }

                // Create teachers if any
                foreach ($this->teacherList as $name) {
                    $tEmail = Str::slug($name) . '@school.edu';
                    $exists = User::where('school_id', $schoolId)->where('name', $name)->exists();
                    if (!$exists) {
                        $phone = $this->teacherPhones[$name] ?? null;
                        $teacher = User::create([
                            'school_id' => $schoolId, 'usergroup_id' => 5, 'name' => $name,
                            'email' => $tEmail, 'password' => bcrypt('password'), 'status' => 'active', 'email_verified' => 1,
                            'mobile_no' => $phone,
                        ]);
                        Userprofile::create([
                            'school_id' => $schoolId, 'user_id' => $teacher->id, 'usergroup_id' => 5,
                            'firstname' => $name, 'lastname' => '', 'profession' => 'teacher', 'status' => 'active',
                        ]);
                    }
                }

                // Create teacher-class-subject links from parsed data
                foreach ($this->teacherLinks as $link) {
                    $teacherUser = User::where('school_id', $schoolId)->where('name', $link['teacher'])->first();
                    $linkSection = Section::where('school_id', $schoolId)->where('name', $link['class'])->first();
                    $linkStandardLink = $linkSection
                        ? StandardLink::where('school_id', $schoolId)->where('section_id', $linkSection->id)
                            ->where('academic_year_id', $academicYear->id)->first()
                        : null;
                    $linkSubject = $linkSection
                        ? Subject::where('school_id', $schoolId)->where('section_id', $linkSection->id)
                            ->where('name', $link['subject'])->first()
                        : null;

                    if ($teacherUser && $linkStandardLink && $linkSubject) {
                        Teacherlink::firstOrCreate([
                            'school_id' => $schoolId,
                            'academic_year_id' => $academicYear->id,
                            'standardLink_id' => $linkStandardLink->id,
                            'subject_id' => $linkSubject->id,
                            'teacher_id' => $teacherUser->id,
                        ]);
                    }
                }

                // Create terms
                foreach ($this->terms as $term) {
                    AcademicTerm::firstOrCreate(
                        ['school_id' => $schoolId, 'name' => $term['name']],
                        ['academic_year_id' => $academicYear->id, 'start_date' => $term['start'], 'end_date' => $term['end']]
                    );
                }

                // Create fees
                foreach ($this->fees as $feeName) {
                    if (is_string($feeName) && trim($feeName)) {
                        FeesCategories::create([
                            'school_id' => $schoolId, 'standard_id' => $phase->id, 'name' => trim($feeName), 'amount' => 0,
                        ]);
                    }
                }

                // WhatsApp verification
                if ($this->whatsappVerified && $this->whatsappPhone) {
                    \App\Models\WhatsAppUser::firstOrCreate(
                        ['phone' => $this->whatsappPhone],
                        ['user_id' => $user->id, 'school_id' => $this->schoolId, 'verified_at' => now(), 'opted_in' => true]
                    );
                }

                // Promote selected teacher to co-admin (usergroup_id: 5 → 3)
                if ($this->coAdminUserId) {
                    $coAdminUser = \App\Models\User::find($this->coAdminUserId);
                    if ($coAdminUser && $coAdminUser->school_id === $schoolId) {
                        $coAdminUser->update(['usergroup_id' => 3]);
                        \App\Models\Userprofile::updateOrCreate(
                            ['user_id' => $coAdminUser->id],
                            ['usergroup_id' => 3, 'school_id' => $schoolId, 'status' => 'active']
                        );

                        try {
                            $schoolName = optional(\App\Models\School::find($schoolId))->name ?? 'your school';
                            Mail::to($coAdminUser->email)->queue(new CoAdminInviteMail(
                                $coAdminUser->name, $coAdminUser->email,
                                null, $schoolName, true
                            ));
                        } catch (\Exception $e) {
                            \Log::warning('Co-admin promotion email failed: ' . $e->getMessage());
                        }
                    }
                }
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
