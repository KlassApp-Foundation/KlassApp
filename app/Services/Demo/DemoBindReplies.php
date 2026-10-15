<?php

namespace App\Services\Demo;

use App\Models\School;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Services\WhatsAppBusinessService;
use App\Support\DemoStudentCode;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp replies for the live demo follow-along:
 *
 *   BIND <code>      links the sender's number to one DEMO student for
 *                    DEMO_BIND_TTL_MINUTES (case-insensitive, extra spaces ok).
 *   MARKS / RESULTS  a bound number gets that student's latest exam as a
 *                    text table; a number that is not bound gets a short
 *                    reply pointing at the follow-along page.
 *
 * Every reply goes only to the number that sent the message. A code for a
 * student outside an is_demo school gets the same reply as an unknown code.
 * A number already linked to a school that is not a demo school is left to
 * the normal flow (handle() returns false), so nothing changes for it.
 */
final class DemoBindReplies
{
    public function __construct(private WhatsAppBusinessService $api)
    {
    }

    /** True when the message was answered here. */
    public function handle(string $phone, string $body, string $senderName = 'Demo User'): bool
    {
        if (preg_match('/^\s*bind(?:\s+(\S+))?\s*$/iu', $body, $m)) {
            return $this->bind($phone, (string) ($m[1] ?? ''), $senderName);
        }

        if (preg_match('/^\s*(marks|results)\s*$/iu', $body)) {
            return $this->marks($phone);
        }

        return false;
    }

    private function bind(string $phone, string $code, string $senderName): bool
    {
        $row = WhatsAppUser::query()->where('phone', $phone)->first();
        if ($row && $this->linkedToRealSchool($row)) {
            return false;
        }

        $student = $this->studentForCode($code);
        $school = $student ? School::query()->find($student->school_id) : null;
        if (! $student || ! $school || (int) $school->is_demo !== 1) {
            if ($student) {
                Log::info('[demo-bind] refused: code belongs to a school that is not a demo school');
            }
            $this->api->sendText($phone, $this->unknownCodeText($code), 'demo_bind_unknown', $row?->user_id);

            return true;
        }

        $academic = StudentAcademic::query()
            ->where('school_id', $school->id)
            ->where('user_id', $student->id)
            ->with('standardLink.section')
            ->orderByDesc('id')
            ->first();
        $guardianId = $this->guardianId((int) $school->id, (int) $student->id);
        if (! $academic || ! $guardianId) {
            $this->api->sendText($phone, $this->unknownCodeText($code), 'demo_bind_unknown', $row?->user_id);

            return true;
        }

        $row = WhatsAppUser::updateOrCreate(['phone' => $phone], [
            'user_id' => $guardianId,
            'school_id' => (int) $school->id,
            'demo_student_id' => (int) $student->id,
            'demo_linked_at' => now(),
            'demo_name' => $senderName,
            'opted_in' => true,
            'verified_at' => now(),
        ]);

        $link = $academic->standardLink;
        $class = trim(($link?->section?->name ?? '') . ' ' . ($link?->stream ?? ''));

        $text = "✅ You are now following *{$student->name}*"
            . ($class !== '' ? " in *{$class}*" : '')
            . " at {$school->name}.\n\n"
            . "What to expect: when the school releases this student's marks during the demo, they arrive in this chat.\n\n"
            . "Reply MARKS to see marks now.\n\n"
            . DemoFollowAlong::removalNotice();
        if (DemoFollowAlong::privacyUrl() !== '') {
            $text .= "\nPrivacy: " . DemoFollowAlong::privacyUrl();
        }

        $this->api->sendText($phone, $text, 'demo_bind', $row->user_id);

        return true;
    }

    private function marks(string $phone): bool
    {
        $row = WhatsAppUser::query()->where('phone', $phone)->first();

        if ($row && $this->linkedToRealSchool($row)) {
            return false;
        }
        if ($row && $row->user_id && ! $row->opted_in) {
            return false;
        }

        $active = $row
            && $row->demo_student_id
            && $row->demo_linked_at
            && $row->demo_linked_at->gt(DemoFollowAlong::bindCutoff())
            && $row->school_id
            && (int) School::query()->whereKey($row->school_id)->value('is_demo') === 1;

        if ($active) {
            $this->api->sendText($phone, $this->marksText((int) $row->school_id, (int) $row->demo_student_id), 'demo_marks', $row->user_id);

            return true;
        }

        if (! $row || ! $row->user_id || $row->demo_student_id) {
            $this->api->sendText(
                $phone,
                "This number is not following a demo student right now.\n\n"
                . 'Open ' . DemoFollowAlong::pageUrl() . ", tap *Bind* next to a student, then reply MARKS.",
                'demo_marks_unbound',
                $row?->user_id,
            );

            return true;
        }

        return false;
    }

    public function marksText(int $schoolId, int $studentId): string
    {
        $student = User::query()->where('school_id', $schoolId)->whereKey($studentId)->first();
        $academic = StudentAcademic::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $studentId)
            ->with('standardLink.section')
            ->orderByDesc('id')
            ->first();
        $link = $academic?->standardLink;
        $sectionId = (int) ($link?->section_id ?? 0);
        $name = (string) ($student?->name ?? 'this student');
        $class = trim(($link?->section?->name ?? '') . ' ' . ($link?->stream ?? ''));

        $exam = ($student && $sectionId) ? LatestExamMarks::forStudent($schoolId, $sectionId, $studentId) : null;
        if (! $exam || $exam['subjects'] === []) {
            return "📊 No marks yet for *{$name}*. They appear here once the school enters them.";
        }

        $bySubject = $exam['marks']->keyBy('subject_id');
        $width = max(7, ...array_map(fn ($s) => mb_strlen($s['name']), $exam['subjects'])) + 2;
        $lines = [str_pad('Subject', $width) . str_pad('Mark', 6, ' ', STR_PAD_LEFT) . '  Grade'];
        $total = 0.0;
        $count = 0;
        foreach ($exam['subjects'] as $subject) {
            $mark = $bySubject->get($subject['id']);
            if (! $mark) {
                continue;
            }
            $total += (float) $mark->marks;
            $count++;
            $lines[] = str_pad($subject['name'], $width)
                . str_pad($this->number((float) $mark->marks), 6, ' ', STR_PAD_LEFT)
                . '  ' . (string) $mark->grade;
        }
        $lines[] = str_repeat('-', $width + 13);
        $lines[] = str_pad('Total', $width) . str_pad($this->number($total), 6, ' ', STR_PAD_LEFT);
        $lines[] = str_pad('Average', $width) . str_pad(number_format($count ? $total / $count : 0, 1), 6, ' ', STR_PAD_LEFT);

        return "📊 *Marks for {$name}*" . ($class !== '' ? " · {$class}" : '') . "\n"
            . $exam['label'] . "\n\n"
            . "```\n" . implode("\n", $lines) . "\n```";
    }

    private function number(float $value): string
    {
        return fmod($value, 1.0) === 0.0 ? (string) (int) $value : number_format($value, 1);
    }

    private function unknownCodeText(string $code): string
    {
        $shown = preg_match('/^[0-9A-Za-z]{1,12}$/', $code) ? ' *' . strtoupper($code) . '*' : '';

        return "Sorry, we could not find a demo student with the code{$shown}.\n\n"
            . 'Open ' . DemoFollowAlong::pageUrl() . ' to see the demo class, then tap *Bind* next to a student.';
    }

    private function studentForCode(string $code): ?User
    {
        $kls = DemoStudentCode::toKls($code);
        if ($kls === null) {
            return null;
        }

        $student = User::query()->where('registration_number', $kls)->where('usergroup_id', 6)->first();
        if ($student) {
            return $student;
        }

        $academic = StudentAcademic::query()->where('klassapp_student_id', $kls)->first();

        return $academic ? User::query()->whereKey($academic->user_id)->where('usergroup_id', 6)->first() : null;
    }

    private function guardianId(int $schoolId, int $studentId): ?int
    {
        $parentId = StudentParentLink::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->where('status', 1)
            ->whereIn('parent_id', User::query()->select('id')->where('school_id', $schoolId))
            ->orderBy('id')
            ->value('parent_id');
        if ($parentId) {
            return (int) $parentId;
        }

        $demoParent = (int) config('services.whatsapp.demo_parent_user_id');
        if ($demoParent > 0 && (int) User::query()->whereKey($demoParent)->value('school_id') === $schoolId) {
            return $demoParent;
        }

        return null;
    }

    private function linkedToRealSchool(WhatsAppUser $row): bool
    {
        $schoolIds = array_filter([
            $row->school_id ? (int) $row->school_id : null,
            $row->user_id ? (int) User::query()->whereKey($row->user_id)->value('school_id') : null,
        ]);

        foreach ($schoolIds as $schoolId) {
            if ((int) School::query()->whereKey($schoolId)->value('is_demo') !== 1) {
                return true;
            }
        }

        return false;
    }
}
