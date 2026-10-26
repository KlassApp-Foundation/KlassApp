<?php

namespace Tests\Feature\Marks\Concerns;

use App\Models\Academics\Exam;
use App\Models\Section;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Demo\Concerns\BuildsFollowAlongSchools;

/**
 * Phase 6 release-rule fixtures: one admin per fixture school, a class teacher,
 * a second class in the same school, demo bindings, and an extra linked parent.
 *
 * Built on BuildsFollowAlongSchools, which gives a demo school (three students,
 * a linked parent and a WhatsApp row each) and a real school (one student).
 */
trait ReleaseRulesFixtures
{
    use BuildsFollowAlongSchools;

    protected function admin(string $which = 'demo'): User
    {
        $school = $this->fx[$which]['school'];
        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Admin ' . $which,
            'email' => 'admin-phase6-' . $which . '-' . $school->id . '@example.com',
            'password' => bcrypt('x'),
            'status' => 'active',
            'email_verified' => 1,
        ]);
        Userprofile::create([
            'user_id' => $admin->id,
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'firstname' => 'Admin',
            'lastname' => ucfirst($which),
            'status' => 'active',
        ]);

        return $admin;
    }

    /** The fixture teacher, made the class teacher of the fixture class. */
    protected function classTeacher(string $which = 'demo'): User
    {
        $teacher = $this->fx[$which]['teacher'];
        StandardLink::query()->whereKey($this->fx[$which]['link']->id)->update(['class_teacher_id' => $teacher->id]);

        return $teacher->fresh();
    }

    /** A second class in the same school and year, with no class teacher. */
    protected function secondClass(string $which = 'demo', string $name = 'P.6'): StandardLink
    {
        $fx = $this->fx[$which];
        $section = Section::create(['school_id' => $fx['school']->id, 'name' => $name, 'status' => 1]);

        return StandardLink::create([
            'school_id' => $fx['school']->id,
            'academic_year_id' => $fx['link']->academic_year_id,
            'standard_id' => $fx['link']->standard_id,
            'section_id' => $section->id,
            'stream' => 'B',
            'status' => 1,
        ]);
    }

    /** A number following a demo student, as BIND would leave it. */
    protected function bind(string $phone, int $studentId, array $overrides = []): WhatsAppUser
    {
        $row = WhatsAppUser::create(array_merge([
            'phone' => '+' . ltrim($phone, '+'),
            'user_id' => $this->fx['demo']['students'][0]['parent']->id,
            'school_id' => $this->fx['demo']['school']->id,
            'opted_in' => true,
            'demo_student_id' => $studentId,
            'demo_linked_at' => now(),
        ], $overrides));

        return $row;
    }

    /** An extra child in the fixture class (with marks on the latest sitting). */
    protected function addChild(string $which, User $parent, string $fullName, bool $withMarks = true): User
    {
        $fx = $this->fx[$which];
        $n = User::where('school_id', $fx['school']->id)->where('usergroup_id', 6)->count() + 1;
        $student = User::create([
            'school_id' => $fx['school']->id,
            'usergroup_id' => 6,
            'name' => $fullName,
            'email' => 'student-extra-' . $fx['school']->id . '-' . $n . '@example.com',
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);
        $student->forceFill(['registration_number' => sprintf('KLS%03d9%03d', $fx['school']->id, $n)])->save();
        [$first, $last] = array_pad(explode(' ', $fullName, 2), 2, '');
        Userprofile::create([
            'user_id' => $student->id,
            'school_id' => $fx['school']->id,
            'usergroup_id' => 6,
            'firstname' => $first,
            'lastname' => $last,
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $fx['school']->id,
            'academic_year_id' => $fx['link']->academic_year_id,
            'user_id' => $student->id,
            'standardLink_id' => $fx['link']->id,
        ]);
        StudentParentLink::create([
            'school_id' => $fx['school']->id,
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'status' => 1,
        ]);

        if ($withMarks) {
            foreach ($fx['exams']['latest'] as $exam) {
                DB::table('marks')->insert([
                    'student_id' => $student->id,
                    'subject_id' => $exam->subject_id,
                    'section_id' => $fx['section']->id,
                    'exam_id' => $exam->id,
                    'teacher_id' => $fx['teacher']->id,
                    'school_id' => $fx['school']->id,
                    'marks' => 60,
                    'grade' => 'C',
                ]);
            }
        }

        return $student;
    }

    /** Every sitting back to "pending", so the approval rule has work to do. */
    protected function disapproveFollowAlongSittings(): void
    {
        foreach (['demo', 'real'] as $which) {
            DB::table('exam_marks_submissions')
                ->where('school_id', $this->fx[$which]['school']->id)
                ->update(['approval_status' => 'pending']);
        }
    }

    /** @return array<string, mixed> */
    protected function payload(string $which = 'demo', ?int $linkId = null, ?int $examId = null): array
    {
        return [
            'exam_id' => $examId ?? $this->latestExamId($which),
            'standard_link_id' => $linkId,
            'when' => 'now',
            'timezone' => 'Africa/Kampala',
        ];
    }

    /** @return array<string, mixed> */
    protected function scheduled(string $which = 'demo', ?int $linkId = null, ?int $examId = null): array
    {
        return array_merge($this->payload($which, $linkId, $examId), [
            'when' => 'schedule',
            'scheduled_for' => '2026-12-01T09:00',
        ]);
    }

    /** The current-term sitting (Term III, end of term). */
    protected function latestExamId(string $which = 'demo'): int
    {
        return (int) Exam::where('school_id', $this->fx[$which]['school']->id)
            ->whereHas('examType', fn ($q) => $q->where('code', 'EOT'))
            ->min('id');
    }

    /** The earlier-term sitting (Term II, beginning of term). */
    protected function earlierExamId(string $which = 'demo'): int
    {
        return (int) Exam::where('school_id', $this->fx[$which]['school']->id)
            ->whereHas('examType', fn ($q) => $q->where('code', 'BOT'))
            ->min('id');
    }
}
