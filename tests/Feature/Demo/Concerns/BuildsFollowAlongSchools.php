<?php

namespace Tests\Feature\Demo\Concerns;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\ExamType;
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
use Illuminate\Support\Facades\Http;

/**
 * A small demo school (one class, three students, one parent each, an older
 * and a latest exam in four subjects) and a school that is not a demo school
 * with one student. Fictional names, example.com emails, +1 555 numbers.
 */
trait BuildsFollowAlongSchools
{
    protected const APP_SECRET = 'follow-along-test-secret';

    /** @var array<string, mixed> */
    protected array $fx = [];

    protected function buildSchools(): void
    {
        $this->fx['demo'] = $this->makeFollowAlongSchool('Sample Demo Primary', true, [
            ['Amina Nakato', ['Mathematics' => 78, 'Science' => 64, 'English' => 91, 'Social Studies' => 55]],
            ['Brian Okello', ['Mathematics' => 66, 'Science' => 70, 'English' => 58, 'Social Studies' => 81]],
            ['Clare Auma', ['Mathematics' => 95, 'Science' => 88, 'English' => 74, 'Social Studies' => 69]],
        ]);
        $this->fx['real'] = $this->makeFollowAlongSchool('Hillside Real Primary', false, [
            ['Zed Realpupil', ['Mathematics' => 50, 'Science' => 50, 'English' => 50, 'Social Studies' => 50]],
        ]);
    }

    /** @param array<int, array{0:string,1:array<string,int>}> $students */
    private function makeFollowAlongSchool(string $name, bool $demo, array $students): array
    {
        $school = School::create([
            'name' => $name,
            'slug' => str()->slug($name),
            'email' => str()->slug($name) . '@example.com',
            'registration_country' => 'Uganda',
            'curriculum' => 'UNEB',
            'status' => 1,
        ]);
        $school->forceFill(['is_demo' => $demo ? 1 : 0])->save();

        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026', 'description' => 'y', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 1]);
        $termTwo = AcademicTerm::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term II', 'status' => 'last', 'starts_on' => '2026-05-18', 'ends_on' => '2026-08-21']);
        $termThree = AcademicTerm::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term III', 'status' => 'current', 'starts_on' => '2026-08-24', 'ends_on' => '2026-11-28']);
        $standard = Standard::create(['school_id' => $school->id, 'name' => 'primary', 'order' => 1, 'status' => 1]);
        $section = Section::create(['school_id' => $school->id, 'name' => 'P.7', 'status' => 1]);
        $link = StandardLink::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'standard_id' => $standard->id, 'section_id' => $section->id, 'stream' => 'A', 'status' => 1]);
        $teacher = User::create(['school_id' => $school->id, 'usergroup_id' => 5, 'name' => 'Teacher ' . $school->id, 'email' => 'teacher' . $school->id . '@example.com', 'password' => bcrypt('x'), 'status' => 'active']);

        $eot = ExamType::firstOrCreate(['code' => 'EOT'], ['name' => 'End of Term Examination', 'contributes_to_report_total' => true]);
        $bot = ExamType::firstOrCreate(['code' => 'BOT'], ['name' => 'Beginning of Term Examination', 'contributes_to_report_total' => false]);

        $exams = [];
        foreach (['Mathematics', 'Science', 'English', 'Social Studies'] as $subjectName) {
            $subject = Subject::create(['school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id, 'academic_year_id' => $year->id, 'name' => $subjectName, 'type' => 'core', 'status' => 1]);
            $base = ['school_id' => $school->id, 'standard_id' => $standard->id, 'section_id' => $section->id, 'academic_year_id' => $year->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id];
            $exams['old'][$subjectName] = Exam::create($base + ['academic_term_id' => $termTwo->id, 'exam_type_id' => $bot->id, 'status' => 'done', 'scheduled_at' => '2026-06-01 09:00:00']);
            $exams['latest'][$subjectName] = Exam::create($base + ['academic_term_id' => $termThree->id, 'exam_type_id' => $eot->id, 'status' => 'undone', 'scheduled_at' => '2026-11-21 09:00:00']);
        }

        $rows = [];
        foreach ($students as $i => [$fullName, $marks]) {
            $n = $i + 1;
            $kls = sprintf('KLS%03d%04d', $school->id, $n);
            $student = User::create(['school_id' => $school->id, 'usergroup_id' => 6, 'name' => $fullName, 'email' => 'student' . $school->id . '-' . $n . '@example.com', 'password' => bcrypt('x'), 'status' => 'active']);
            $student->forceFill(['registration_number' => $kls])->save();
            [$first, $last] = explode(' ', $fullName, 2);
            Userprofile::create(['user_id' => $student->id, 'school_id' => $school->id, 'usergroup_id' => 6, 'firstname' => $first, 'lastname' => $last, 'status' => 'active']);
            StudentAcademic::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'user_id' => $student->id, 'standardLink_id' => $link->id]);

            $parent = User::create(['school_id' => $school->id, 'usergroup_id' => 7, 'name' => 'Parent of ' . $first, 'email' => 'parent' . $school->id . '-' . $n . '@example.com', 'password' => bcrypt('x'), 'status' => 'active']);
            StudentParentLink::create(['school_id' => $school->id, 'parent_id' => $parent->id, 'student_id' => $student->id, 'status' => 1]);
            WhatsAppUser::create(['phone' => sprintf('+25670019%04d', $school->id * 10 + $n), 'user_id' => $parent->id, 'school_id' => $school->id, 'opted_in' => true]);

            foreach ($marks as $subjectName => $score) {
                foreach (['old' => 40, 'latest' => $score] as $which => $value) {
                    $exam = $exams[$which][$subjectName];
                    Marks::create(['student_id' => $student->id, 'subject_id' => $exam->subject_id, 'section_id' => $section->id, 'exam_id' => $exam->id, 'teacher_id' => $teacher->id, 'school_id' => $school->id, 'marks' => $value, 'grade' => $this->fxGrade($value)]);
                }
            }

            $rows[] = ['user' => $student, 'parent' => $parent, 'kls' => $kls, 'code' => self::fxCode($kls), 'first' => $first];
        }

        return ['school' => $school, 'section' => $section, 'students' => $rows, 'teacher' => $teacher, 'link' => $link, 'exams' => $exams];
    }

    /** Existing release tests predate the approval rule. Mark every sitting approved. */
    protected function approveFollowAlongSittings(): void
    {
        foreach (['demo', 'real'] as $which) {
            $school = $this->fx[$which]['school'];
            $teacher = $this->fx[$which]['teacher'];
            foreach (Exam::query()->where('school_id', $school->id)->get() as $exam) {
                ExamMarksSubmission::query()->updateOrCreate(
                    [
                        'exam_id' => $exam->id,
                        'class_id' => $exam->section_id,
                        'subject_id' => $exam->subject_id,
                    ],
                    [
                        'school_id' => $school->id,
                        'teacher_id' => $teacher->id,
                        'status' => 'locked',
                        'approval_status' => 'approved',
                        'submitted_at' => now(),
                        'approved_at' => now(),
                    ],
                );
            }
        }
    }

    /** A Tier 2 release record of the latest exam (End of Term, Term III) of a fixture school. */
    protected function releaseLatestExam(string $key, string $status = 'released', string $scope = 'school'): void
    {
        $this->releaseExam($key, 'latest', $status, $scope);
    }

    /**
     * A Tier 2 release record of one fixture exam: $which is "old"
     * (Beginning of Term, Term II) or "latest" (End of Term, Term III).
     */
    protected function releaseExam(string $key, string $which, string $status = 'released', string $scope = 'school'): void
    {
        $fx = $this->fx[$key];
        \App\Models\MarksRelease::create([
            'school_id' => $fx['school']->id,
            'exam_id' => $fx['exams'][$which]['Mathematics']->id,
            'scope' => $scope,
            'standard_link_id' => $scope === 'class' ? $fx['link']->id : null,
            'scheduled_at' => now(),
            'released_at' => $status === 'released' ? now() : null,
            'status' => $status,
            'created_by' => $fx['teacher']->id,
            'timezone' => 'Africa/Kampala',
        ]);
    }

    /** The documented code rule, written out here: base 36 of the KLS number's digits. */
    private static function fxCode(string $kls): string
    {
        return strtoupper(base_convert(substr($kls, 3), 10, 36));
    }

    private function fxGrade(int $score): string
    {
        return match (true) {
            $score >= 95 => 'D1', $score >= 90 => 'D2', $score >= 75 => 'C3', $score >= 65 => 'C4',
            $score >= 60 => 'C5', $score >= 50 => 'C6', $score >= 45 => 'P7', $score >= 40 => 'P8', default => 'F9',
        };
    }

    /**
     * MySQL runs with strict mode off (config/database.php), so the delivery
     * log stores the status and category values the webhook writes even when
     * they are outside the column's enum. SQLite enforces the enum as a CHECK
     * constraint, which would stop the request before any reply. This test
     * seam recreates the log table with plain string columns so the real
     * webhook, service and HTTP fake can run end to end.
     */
    protected function relaxDeliveryLog(): void
    {
        \Illuminate\Support\Facades\Schema::drop('message_delivery_log');
        \Illuminate\Support\Facades\Schema::create('message_delivery_log', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('whatsapp_message_id', 100)->nullable();
            $table->string('phone', 20);
            $table->string('template_name', 100)->nullable();
            $table->string('direction')->default('outbound');
            $table->string('category')->nullable();
            $table->string('status')->default('sent');
            $table->decimal('cost_usd', 8, 6)->default(0);
            $table->text('content_preview')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('flow_type', 50)->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('error_message')->nullable();
        });
    }

    protected function fakeGraph(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out']]], 200)]);
    }

    /** Signed Meta webhook POST of one text message, as the given sender (digits, no +). */
    protected function inbound(string $from, string $text): \Illuminate\Testing\TestResponse
    {
        $body = json_encode(['entry' => [['changes' => [['value' => [
            'contacts' => [['profile' => ['name' => 'Guest'], 'wa_id' => $from]],
            'messages' => [['from' => $from, 'id' => 'wamid.in.' . uniqid(), 'type' => 'text', 'text' => ['body' => $text]]],
        ]]]]]]);

        return $this->call('POST', '/api/whatsapp/inbound', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Hub-Signature-256' => 'sha256=' . hash_hmac('sha256', $body, self::APP_SECRET),
        ], $body);
    }

    /** @return array<int, array{to:string, body:string}> every outbound Graph call, in order */
    protected function outbound(): array
    {
        return collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), 'graph.facebook.com'))
            ->map(function ($pair) {
                $data = $pair[0]->data();

                return [
                    'to' => (string) ($data['to'] ?? ''),
                    'body' => (string) ($data['text']['body'] ?? $data['interactive']['body']['text'] ?? json_encode($data)),
                ];
            })
            ->values()
            ->all();
    }
}
