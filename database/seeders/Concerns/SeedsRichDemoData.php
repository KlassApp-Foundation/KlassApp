<?php

namespace Database\Seeders\Concerns;

use App\Models\AcademicTerm;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMarksSubmission;
use App\Models\Academics\ExamType;
use App\Models\Academics\Marks;
use App\Models\Academics\TimetableSlot;
use App\Models\Admission;
use App\Models\Attendance;
use App\Models\Events;
use App\Models\NoticeBoard;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\CurrentPlan;
use App\Models\Plan;
use App\Models\StudentParentLink;
use App\Models\Subject;
use App\Models\Teacherlink;
use App\Models\ReportGeneration;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Jobs\GenerateClassReportsJob;
use App\Services\FreeTierPlanService;
use App\Services\OnboardingStepsService;
use App\Support\DemoSeedManifest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rich demo data shared by the Junior and Senior demo seeders.
 *
 * Everything is RELATIVE to "today" and idempotent: re-running the seeder
 * tops up anything missing and never duplicates. This trait is used by
 * DemoJuniorSchoolSeeder and DemoSeniorSchoolSeeder; each school supplies
 * its shape through richConfig() (exam sections, walkthrough hints, etc.)
 * and must provide $this->school, $this->year, $this->staff (keyed: admin,
 * head, teacher1...) and a user() helper that sets passwords on CREATE ONLY.
 */
trait SeedsRichDemoData
{
    /** Ids the refresh command needs to restore the walkthrough baseline. */
    private array $richWalkthrough = [
        'skip_link_ids' => [],
        'open_exam_id' => null,
        'open_section_id' => null,
        'open_subject_id' => null,
    ];

    /**
     * Per-school rich shape. Seeds that use this trait must return:
     *   parents            => list of {name} (usergroup 7)
     *   admissions         => list of {standard, section, name, age, gender,
     *                         district, village, last_school, last_class,
     *                         father, father_job, mother, mother_job}
     *   exam_section_names => section names that get exam rounds
     *   skip_second        => {section, stream?} second walkthrough class
     *   fallback_open      => {section, subject} open-exam fallback pair
     *
     * @return array<string, mixed>
     */
    abstract protected function richConfig(): array;

    protected function seedRichDemoData(): void
    {
        $this->richSeedParents();
        $this->richSeedTimetable();
        $this->richSeedCommunication();
        $this->richSeedAdmissions();
        $this->richEnsureAttendance();
        $this->richSeedExamRounds();
        $this->richCompleteOnboarding();
        $this->richTeacherProfiles();
        $this->richSchoolProfile();
        $this->richSeedDemoReportCards();
        $this->richWriteManifest();
    }

    // ──────────────────────────────────────────────────────────── parents

    protected function richSeedParents(): void
    {
        $people = $this->richConfig()['parents'] ?? [];

        if ($people === []) {
            return;
        }

        $studentIds = StudentAcademic::where('school_id', $this->school->id)
            ->orderBy('user_id')
            ->pluck('user_id')
            ->values();

        if ($studentIds->isEmpty()) {
            return;
        }

        $made = 0;
        $linked = 0;
        $parentIds = [];

        foreach (array_values($people) as $i => $person) {
            $local = 'parent' . ($i + 1);
            $parent = $this->user($local, $person['name'], 7);
            $made++;

            // users has mobile_no — there is no phone / whatsapp_number column.
            $phone = $this->richFictionalPhone($local . '@' . $this->richDomain());

            if ($parent->mobile_no !== $phone) {
                $parent->forceFill(['mobile_no' => $phone])->save();
            }

            $parentIds[] = (int) $parent->id;
        }

        // Every student gets exactly one parent, round-robin over the pool, so
        // no child is left unlinked and each parent ends with one to two
        // children (the walkthrough expects one to three). Extra links added
        // by hand are left in place; soft-deleted ones are restored.
        $pool = count($parentIds);

        foreach ($studentIds as $idx => $studentId) {
            $parentId = $parentIds[$idx % $pool];

            $link = StudentParentLink::withTrashed()->firstOrCreate(
                [
                    'school_id' => $this->school->id,
                    'parent_id' => $parentId,
                    'student_id' => $studentId,
                ],
                ['status' => 1]
            );

            if ($link->trashed()) {
                $link->restore();
                $linked++;
            } elseif ($link->wasRecentlyCreated) {
                $linked++;
            }
        }

        $this->command?->info("Rich parents: {$made} accounts, {$linked} new child links.");
    }

    // ────────────────────────────────────────────────── onboarding steps

    /**
     * A1: complete the setup steps for a school that already has data, so the
     * "Finish school setup" banner never shows on the demos. Everything goes
     * through the same records the steps service reads — no flags are faked.
     */
    protected function richCompleteOnboarding(): void
    {
        $school = $this->school->fresh();
        $admin = $this->richAdmin();

        // School profile fields the steps read. Values are clearly fictional.
        $school->forceFill([
            'student_size' => $school->student_size ?: 'Up to 500',
            'ministry_code' => $school->ministry_code ?: 'DEMO-EMIS-' . $school->id,
            'uneb_center_number' => $school->uneb_center_number ?: 'DEMO-UNEB-' . $school->id,
            'address' => $school->address ?: 'Plot 12, Sample Road',
            'motto' => $school->motto ?: 'Learning together',
        ])->save();

        // 'whatsapp_verify' reads the admin's WhatsAppUser row.
        WhatsAppUser::updateOrCreate(
            ['user_id' => $admin->id],
            [
                'phone' => $this->richFictionalPhone('wa-admin@' . $this->richDomain() . $school->id),
                'school_id' => $school->id,
                'verified_at' => now(),
                'opted_in' => true,
            ]
        );

        // 'plan_selection' reads CurrentPlan.
        if (! CurrentPlan::where('school_id', $school->id)->exists()) {
            $assigned = app(FreeTierPlanService::class)->assignIfEligible($school, $admin->id);

            if (! $assigned) {
                if (! Plan::query()->where('is_active', 1)->exists()) {
                    \Illuminate\Support\Facades\Artisan::call('db:seed', [
                        '--class' => \Database\Seeders\PlansTableSeeder::class,
                        '--force' => true,
                    ]);
                }

                $plan = Plan::query()
                    ->where('is_active', 1)
                    ->where(function ($q) {
                        $q->whereRaw('LOWER(name) = ?', ['freemium'])
                            ->orWhereRaw('LOWER(display_name) = ?', ['freemium']);
                    })
                    ->orderBy('order')
                    ->first()
                    ?? Plan::query()->where('is_active', 1)->orderBy('order')->first();

                if ($plan) {
                    CurrentPlan::create([
                        'school_id' => $school->id,
                        'plan_id' => $plan->id,
                        'status' => 'running',
                    ]);
                }
            }
        }

        OnboardingStepsService::markOnboardingFinished($school);
    }

    // ─────────────────────────────────────────────────────────── timetable

    protected function richSeedTimetable(): void
    {
        $sections = Section::where('school_id', $this->school->id)->orderBy('id')->get();

        if ($sections->isEmpty()) {
            return;
        }

        $subjectsBySection = Subject::where('school_id', $this->school->id)
            ->orderBy('id')
            ->get()
            ->groupBy('section_id');

        $teacherBySubject = Teacherlink::where('school_id', $this->school->id)
            ->pluck('teacher_id', 'subject_id');

        $fallbackTeacher = $this->richTeacher1()->id;

        $term = $this->richCurrentTerm();
        $yearId = $this->year->id;
        $schoolId = $this->school->id;

        $grid = [
            ['08:00', '08:40'],
            ['08:40', '09:20'],
            ['09:20', '10:00'],
            ['10:30', '11:10'],
            ['11:10', '11:50'],
            ['14:00', '14:40'],
        ];

        $busy = [];
        $created = 0;
        $touched = 0;

        foreach ($sections as $sectionIndex => $section) {
            $subs = ($subjectsBySection[$section->id] ?? collect())->values();

            if ($subs->isEmpty()) {
                continue;
            }

            $offset = (int) (crc32('section|' . $section->id) % $subs->count());

            foreach ([1, 2, 3, 4, 5] as $day) {
                foreach ($grid as $p => [$start, $end]) {
                    $chosen = null;

                    for ($attempt = 0; $attempt < $subs->count(); $attempt++) {
                        $candidate = $subs[($offset + $p + $attempt) % $subs->count()];
                        $teacherId = $teacherBySubject[$candidate->id] ?? $fallbackTeacher;

                        if (! isset($busy[$day][$start][$teacherId])) {
                            $chosen = [$candidate, $teacherId];
                            break;
                        }
                    }

                    $chosen ??= [$subs[($offset + $p) % $subs->count()], $fallbackTeacher];

                    [$subject, $teacherId] = $chosen;
                    $busy[$day][$start][$teacherId] = true;

                    $slot = TimetableSlot::firstOrCreate(
                        [
                            'school_id' => $schoolId,
                            'section_id' => $section->id,
                            'day_of_week' => $day,
                            'start_time' => $start,
                        ],
                        [
                            'academic_year_id' => $yearId,
                            'academic_term_id' => $term?->id,
                            'subject_id' => $subject->id,
                            'teacher_id' => $teacherId,
                            'end_time' => $end,
                            'room' => 'Room ' . ($sectionIndex + 1),
                        ]
                    );

                    if ($slot->wasRecentlyCreated) {
                        $created++;
                    }

                    $slot->syncCalendarEvent();
                    $touched++;
                }
            }
        }

        $this->command?->info("Rich timetable: {$created} new slots, {$touched} ensured across " . $sections->count() . ' classes.');
    }

    // ─────────────────────────────────────────────────────── communication

    protected function richSeedCommunication(): void
    {
        $yearId = $this->year->id;
        $adminId = $this->richAdmin()->id;

        $notices = [
            ['title' => 'Parents Meeting', 'in_days' => -2, 'expire_in' => 10, 'body' => 'Dear parents, our termly parents meeting takes place this Saturday at 9:00am in the main hall. Your attendance matters.'],
            ['title' => 'Sports Day Reminder', 'in_days' => -1, 'expire_in' => 14, 'body' => 'Inter-house sports day is coming up. Learners should carry their house colours and enough drinking water.'],
            ['title' => 'Fees Reminder', 'in_days' => -5, 'expire_in' => 21, 'body' => 'A reminder that outstanding term fees should be cleared before the end of the month. Thank you for your continued support.'],
        ];

        foreach ($notices as $notice) {
            NoticeBoard::firstOrCreate(
                ['school_id' => $this->school->id, 'title' => $notice['title']],
                [
                    'academic_year_id' => $yearId,
                    'standardLink_id' => null,
                    'type' => 'school',
                    'publish_date' => now()->addDays($notice['in_days']),
                    'expire_date' => now()->addDays($notice['expire_in']),
                    'description' => $notice['body'],
                    'attachment_file' => null,
                    'status' => 1,
                ]
            );
        }

        $events = [
            ['title' => 'Visitation Day', 'in_days' => 7, 'start' => '09:00', 'end' => '13:00', 'body' => 'Parents and guardians are welcome to visit, tour the classes and meet the teachers.'],
            ['title' => 'Inter-house Sports Gala', 'in_days' => 14, 'start' => '08:00', 'end' => '17:00', 'body' => 'A full day of athletics, football and music as the four houses compete for the trophy.'],
        ];

        foreach ($events as $event) {
            Events::firstOrCreate(
                ['school_id' => $this->school->id, 'title' => $event['title']],
                [
                    'academic_year_id' => $yearId,
                    'select_type' => 'school',
                    'description' => $event['body'],
                    'repeats' => 0,
                    'freq' => null,
                    'freq_term' => null,
                    'location' => 'Main Campus',
                    'category' => 'education',
                    'organised_by' => 'School Administration',
                    'start_date' => now()->addDays($event['in_days'])->setTimeFromTimeString($event['start']),
                    'end_date' => now()->addDays($event['in_days'])->setTimeFromTimeString($event['end']),
                    'allDay' => 0,
                    'status' => 'active',
                    'color' => 'green',
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                    'batch' => '',
                ]
            );
        }

        $this->command?->info('Rich communication: ' . count($notices) . ' notices, ' . count($events) . ' events ensured.');
    }

    // ─────────────────────────────────────────────────────────── admissions

    protected function richSeedAdmissions(): void
    {
        $applicants = $this->richConfig()['admissions'] ?? [];

        if ($applicants === []) {
            return;
        }

        $made = 0;

        foreach (array_values($applicants) as $i => $applicant) {
            $standard = Standard::where('school_id', $this->school->id)
                ->where('name', $applicant['standard'])
                ->first();

            $section = Section::where('school_id', $this->school->id)
                ->where('name', $applicant['section'])
                ->first();

            $phone = $this->richFictionalPhone('applicant-' . $this->school->id . '-' . $i);

            // NOTE: entry_term is stored as '1'|'2'|'3' (AdmissionStandardRequest
            // validates in:1,2,3), NOT 'Term I'. Ugandan fields exist as real
            // columns but are NOT in Admission::$fillable, so they are written
            // via forceFill exactly like the controller's property assignments.
            $base = [
                'school_id' => $this->school->id,
                'application_no' => 'ADM-' . $this->school->id . '-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
            ];

            $admission = Admission::withTrashed()->where($base)->first();

            if (! $admission) {
                $admission = new Admission;
                $admission->forceFill($base);
            }

            $admission->forceFill([
                'academic_year_id' => $this->year->id,
                'standard_id' => $standard?->id,
                'section_id' => $section?->id,
                'name' => $applicant['name'],
                'date_of_birth' => now()->subYears($applicant['age'])->subDays(120)->toDateString(),
                'gender' => $applicant['gender'],
                'height' => (string) (115 + $i * 7),
                'weight' => (string) (23 + $i * 4),
                'birth_place' => $applicant['district'],
                'nationality' => 'Ugandan',
                'religion' => 'Christian',
                'community' => 'Muganda',
                'mother_tongue' => 'Luganda',
                'blood_group' => null,
                'school_last_studied' => $applicant['last_school'],
                'reason_for_leaving' => 'Completed previous class.',
                'permanent_address' => $applicant['district'] . ', Uganda',
                'address_for_communication' => $applicant['district'] . ', Uganda',
                'siblings' => 'yes',
                'half_yearly_mark_details' => 'Aggregate 12, good standing.',
                'board_of_education' => 'UNEB',
                'choice_of_language' => 'English',
                'father_name' => $applicant['father'],
                'father_designation' => null,
                'father_occupation' => $applicant['father_job'],
                'father_organisation' => null,
                'father_income' => null,
                'father_mobile_no' => $phone,
                'father_email' => null,
                'mother_name' => $applicant['mother'],
                'mother_designation' => null,
                'mother_occupation' => $applicant['mother_job'],
                'mother_organisation' => null,
                'mother_income' => null,
                'mother_mobile_no' => $phone,
                'mother_email' => null,
                'emergency_contact_1' => $phone,
                'relation_with_student_1' => 'Uncle',
                'emergency_contact_2' => $phone,
                'relation_with_student_2' => 'Aunt',
                'medical_history' => 'no',
                'medical_details' => null,
                'extra_curricular_activities' => 'yes',
                'activities' => 'Football, Music',
                'mode_of_transport' => 'school_bus',
                'transport_details' => null,
                'application_status' => 'Pending',
                'payment_status' => 'unpaid',
                'fee_group_id' => null,
                'remarks' => 'Awaiting interview and placement confirmation.',
                'entry_term' => (string) (($i % 3) + 1),
                'entry_year' => (string) now()->addYear()->year,
                'boarding_type' => $i % 2 === 0 ? 'day' : 'boarding',
                'home_district' => $applicant['district'],
                'village_town' => $applicant['village'],
                'lin' => null,
                'birth_certificate' => null,
                'last_class_completed' => $applicant['last_class'],
                'ple_index_number' => null,
                'ple_aggregate' => null,
                'uce_index_number' => null,
                'uce_results_summary' => null,
                'father_relationship' => 'Father',
                'father_alt_phone' => null,
                'father_district' => $applicant['district'],
                'father_on_whatsapp' => 1,
                'mother_relationship' => 'Mother',
                'mother_alt_phone' => null,
                'mother_district' => $applicant['district'],
                'mother_on_whatsapp' => 1,
                'emergency_contact_name_1' => $applicant['father'],
                'medical_conditions' => null,
                'special_needs' => null,
            ])->save();

            if ($admission->wasRecentlyCreated) {
                $made++;
            }
        }

        $this->command?->info("Rich admissions: {$made} new pending applications.");
    }

    // ──────────────────────────────────────────────────────────── attendance

    protected function richEnsureAttendance(): void
    {
        $schoolId = $this->school->id;
        $days = $this->richSchoolDays(20);
        $today = now()->toDateString();
        // The demo day itself is always present — even when it falls on a
        // weekend. The walkthrough asserts "other classes have attendance
        // today", and demo days are real calendar days; richSchoolDays()
        // keeps its weekday meaning for the seeded stretch.
        if (! in_array($today, $days, true)) {
            $days[] = $today;
        }

        $links = StandardLink::where('school_id', $schoolId)->get();
        $studentsByLink = StudentAcademic::where('school_id', $schoolId)
            ->get(['user_id', 'standardLink_id'])
            ->groupBy('standardLink_id');

        $skipLinkIds = $this->richSkipLinkIds();

        // The walkthrough classes deliberately have NO attendance recorded for
        // today, so it can be taken live on a call.
        $removed = Attendance::where('school_id', $schoolId)
            ->whereIn('standardLink_id', $skipLinkIds)
            ->whereDate('date', $today)
            ->forceDelete();

        $existing = [];
        foreach (Attendance::where('school_id', $schoolId)->get(['user_id', 'date', 'session']) as $row) {
            $existing[$row->user_id . '|' . substr((string) $row->date, 0, 10) . '|' . $row->session] = true;
        }

        $created = 0;

        foreach ($studentsByLink as $linkId => $students) {
            $skipToday = in_array($linkId, $skipLinkIds, true);
            $teacherId = $links->firstWhere('id', $linkId)?->class_teacher_id ?? $this->richTeacher1()->id;

            foreach ($days as $day) {
                if ($skipToday && $day === $today) {
                    continue;
                }

                foreach (['forenoon', 'afternoon'] as $session) {
                    foreach ($students as $student) {
                        $key = $student->user_id . '|' . $day . '|' . $session;

                        if (isset($existing[$key])) {
                            continue;
                        }

                        $absent = (crc32($student->user_id . '|' . $day . '|' . $session . '|rich') % 100) < 4;

                        Attendance::create([
                            'school_id' => $schoolId,
                            'academic_year_id' => $this->year->id,
                            'standardLink_id' => $linkId,
                            'user_id' => $student->user_id,
                            'date' => $day,
                            'session' => $session,
                            'status' => ! $absent,
                            'reason_id' => null,
                            'remarks' => '',
                            'recorded_by' => $teacherId,
                        ]);

                        $created++;
                    }
                }
            }
        }

        $this->richWalkthrough['skip_link_ids'] = $skipLinkIds;

        $this->command?->info("Rich attendance: {$created} new rows over " . count($days) . " school days ({$removed} today rows cleared for " . count($skipLinkIds) . ' walkthrough classes).');
    }

    // ────────────────────────────────────────────────────────── exam rounds

    protected function richSeedExamRounds(): void
    {
        $schoolId = $this->school->id;
        $sectionNames = $this->richConfig()['exam_section_names'] ?? [];

        $eot = ExamType::firstOrCreate(['code' => 'EOT'], ['name' => 'End of Term Examination', 'contributes_to_report_total' => true]);
        $bot = ExamType::firstOrCreate(['code' => 'BOT'], ['name' => 'Beginning of Term Examination', 'contributes_to_report_total' => false]);
        $mid = ExamType::firstOrCreate(['code' => 'MID'], ['name' => 'Mid Term Examination', 'contributes_to_report_total' => false]);

        $terms = AcademicTerm::where('school_id', $schoolId)->orderByDesc('ends_on')->get();
        $current = $terms->firstWhere('status', 'current') ?? $terms->first();

        // Previous term = the LATEST term before current that actually has EOT
        // exams with marks. A generic "first term ending before current" would
        // resolve to Term I (an empty historical term with no students/exams)
        // and seed the whole exam round against the wrong term — leaving the
        // real previous term (Term II) and the current term without the marks
        // the live "Generate report cards" flow needs. Observed live: senior
        // and Academy got zero current-term EOT exams this way.
        $previous = $terms
            ->filter(fn ($term) => $current && $term->id !== $current->id
                && Carbon::parse($term->ends_on)->lt(Carbon::parse($current->starts_on))
                && Exam::where('school_id', $schoolId)
                    ->where('academic_term_id', $term->id)
                    ->where('exam_type_id', $eot->id)
                    ->whereHas('marks', fn ($q) => $q->where('school_id', $schoolId))
                    ->exists())
            ->sortByDesc(fn ($term) => Carbon::parse($term->ends_on)->getTimestamp())
            ->first();

        if (! $current || ! $previous) {
            $this->command?->warn('Rich exams skipped: could not resolve current/previous terms.');

            return;
        }

        $sections = Section::where('school_id', $schoolId)->whereIn('name', $sectionNames)->get();

        if ($sections->isEmpty()) {
            return;
        }

        [$openSection, $openSubject] = $this->richOpenPair($sections);
        $this->richWalkthrough['open_section_id'] = $openSection?->id;
        $this->richWalkthrough['open_subject_id'] = $openSubject?->id;

        $teacherBySubject = Teacherlink::where('school_id', $schoolId)->pluck('teacher_id', 'subject_id');
        $teacher1Id = $this->richTeacher1()->id;

        $newExams = 0;
        $newMarks = 0;

        foreach ($sections as $section) {
            $subjects = Subject::where('school_id', $schoolId)->where('section_id', $section->id)->get();

            $students = StudentAcademic::where('school_id', $schoolId)
                ->whereIn('standardLink_id', StandardLink::where('school_id', $schoolId)->where('section_id', $section->id)->pluck('id'))
                ->pluck('user_id');

            foreach ($subjects as $subject) {
                $teacherId = $teacherBySubject[$subject->id] ?? $teacher1Id;
                $isOpenPair = $openSection && $openSubject
                    && $openSection->id === $section->id
                    && $openSubject->id === $subject->id;

                if ($isOpenPair) {
                    $teacherId = $teacher1Id;
                }

                // 1) Previous term: mid-term + end-of-term, complete and locked.
                $midPrev = $this->richMakeExam($schoolId, $section, $subject, $mid, $previous, $teacherId, 'done', Carbon::parse($previous->starts_on)->addDays(40)->setTime(9, 0), Carbon::parse($previous->starts_on)->addDays(47)->setTime(17, 0));
                $newExams += (int) $midPrev->wasRecentlyCreated;
                $newMarks += $this->richEnsureMarks($midPrev, $students);
                $this->richEnsureSubmission($midPrev, $section, $subject, $teacherId, locked: true, deadline: Carbon::parse($previous->starts_on)->addDays(47)->setTime(17, 0));

                $eotPrev = $this->richMakeExam($schoolId, $section, $subject, $eot, $previous, $teacherId, 'done', Carbon::parse($previous->ends_on)->subDays(7)->setTime(9, 0), Carbon::parse($previous->ends_on)->setTime(17, 0));
                $newExams += (int) $eotPrev->wasRecentlyCreated;
                $newMarks += $this->richEnsureMarks($eotPrev, $students);
                $this->richEnsureSubmission($eotPrev, $section, $subject, $teacherId, locked: true, deadline: Carbon::parse($previous->ends_on)->setTime(17, 0));

                // 2) Current term: one complete (BOT).
                $botNow = $this->richMakeExam($schoolId, $section, $subject, $bot, $current, $teacherId, 'done', Carbon::parse($current->starts_on)->addDays(12)->setTime(9, 0), Carbon::parse($current->starts_on)->addDays(19)->setTime(17, 0));
                $newExams += (int) $botNow->wasRecentlyCreated;
                $newMarks += $this->richEnsureMarks($botNow, $students);

                // 3) Current term: one OPEN mid-term — the walkthrough teacher's
                //    subject intentionally stays unmarked until they enter it.
                $midNow = $this->richMakeExam($schoolId, $section, $subject, $mid, $current, $teacherId, 'undone', now()->subDays(3)->setTime(9, 0), now()->addDays(4)->setTime(17, 0));
                $newExams += (int) $midNow->wasRecentlyCreated;

                if ($isOpenPair) {
                    $this->richWalkthrough['open_exam_id'] = $midNow->id;
                } else {
                    $newMarks += $this->richEnsureMarks($midNow, $students);
                }

                // 4) Current term: one upcoming (EOT at the end of term).
                $eotNow = $this->richMakeExam($schoolId, $section, $subject, $eot, $current, $teacherId, 'undone', Carbon::parse($current->ends_on)->subDays(7)->setTime(9, 0), Carbon::parse($current->ends_on)->setTime(17, 0));
                $newExams += (int) $eotNow->wasRecentlyCreated;

                // Marks exist for every student who has a mark in the previous
                // term's EOT exam for this same section (nursery sections, whose
                // reporting is narrative, have none and stay mark-less). This is
                // what makes the CURRENT term's "Generate report cards" work
                // live: index() binds a class to its current-term eotExam, and
                // with no marks the generate button would have nothing to
                // render. The current term is still left UNGENERATED (no
                // report_generations rows), so it stays a live demo.
                $prevStudentIds = Marks::where('exam_id', $eotPrev->id)->distinct()->pluck('student_id');
                $newMarks += $this->richEnsureMarks($eotNow, $prevStudentIds);
            }
        }

        $this->command?->info("Rich exams: {$newExams} new exams, {$newMarks} new mark rows across " . $sections->count() . ' classes. Open exam for walkthrough teacher: ' . ($openSubject?->name ?? 'n/a') . ' (' . ($openSection?->name ?? 'n/a') . ').');
    }

    private function richMakeExam(int $schoolId, Section $section, Subject $subject, ExamType $type, AcademicTerm $term, int $teacherId, string $status, Carbon $scheduled, Carbon $deadline): Exam
    {
        return Exam::firstOrCreate(
            [
                'school_id' => $schoolId,
                'section_id' => $section->id,
                'subject_id' => $subject->id,
                'exam_type_id' => $type->id,
                'academic_term_id' => $term->id,
            ],
            [
                // standard comes from the subject (sections have no standard_id;
                // the standard band is joined through standards_link).
                'standard_id' => $subject->standard_id,
                'academic_year_id' => $this->year->id,
                'teacher_id' => $teacherId,
                'status' => $status,
                'scheduled_at' => $scheduled,
                'default_deadline' => $deadline,
            ]
        );
    }

    private function richEnsureMarks(Exam $exam, $studentIds): int
    {
        $existing = DB::table('marks')->where('exam_id', $exam->id)->pluck('student_id')->all();
        $existing = array_flip(array_map('intval', $existing));

        $inserted = 0;

        foreach ($studentIds as $studentId) {
            if (isset($existing[(int) $studentId])) {
                continue;
            }

            $score = 42 + (int) (crc32($studentId . '|' . $exam->subject_id . '|' . $exam->id . '|rich') % 56);
            $grade = method_exists($this, 'gradeFor') ? $this->gradeFor($score) : 'C3';

            DB::table('marks')->insert([
                'student_id' => $studentId,
                'teacher_id' => $exam->teacher_id,
                'school_id' => $exam->school_id,
                'subject_id' => $exam->subject_id,
                'exam_id' => $exam->id,
                'section_id' => $exam->section_id,
                'remark_id' => null,
                'marks' => $score,
                'grade' => $grade,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $inserted++;
        }

        return $inserted;
    }

    private function richEnsureSubmission(Exam $exam, Section $section, Subject $subject, int $teacherId, bool $locked, Carbon $deadline): void
    {
        ExamMarksSubmission::updateOrCreate(
            [
                'school_id' => $exam->school_id,
                'exam_id' => $exam->id,
                'class_id' => $section->id,
                'subject_id' => $subject->id,
            ],
            [
                'teacher_id' => $teacherId,
                'status' => 'submitted',
                'deadline' => $deadline,
                'submitted_at' => $deadline->copy()->subHours(4),
                'locked_at' => $locked ? $deadline->copy()->addMinutes(30) : null,
                'reopened_at' => null,
                'reopened_by' => null,
                'reopen_reason' => null,
                'approval_status' => 'approved',
                'approved_at' => $deadline->copy()->addHours(2),
                'approved_by' => $this->richHead()?->id,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
            ]
        );
    }

    /**
     * Seed the previous term's report cards (merged, openable PDFs) through the
     * app's own GenerateClassReportsJob pipeline, so "Recent generations" is not
     * empty on a fresh demo school.
     *
     * The current term is deliberately left UNGENERATED so "Generate report
     * cards" works live for whoever is demoing. Both terms' EOT exams already
     * carry marks (seeded by seedExamsAndMarks / richSeedExamRounds), so the
     * current term is ready to generate on demand.
     */
    protected function richSeedDemoReportCards(): void
    {
        $schoolId = (int) $this->school->id;

        // Currency lives in school_details.meta_key='currency' (read by
        // DashboardV2DataService::currencyFor). Setting it here clears the
        // "Set your currency" dashboard hint without hard-coding a symbol.
        DB::table('school_details')->updateOrInsert(
            ['school_id' => $schoolId, 'meta_key' => 'currency'],
            ['meta_value' => 'UGX', 'created_at' => now(), 'updated_at' => now()]
        );

        $terms = AcademicTerm::where('school_id', $schoolId)->orderByDesc('ends_on')->get();
        $current = $terms->firstWhere('status', 'current') ?? $terms->first();

        // Previous term = the LATEST term before current that actually has EOT
        // exams. A generic "first term ending before current" would resolve to
        // Term I (an empty historical term with no exams) and leave the real
        // previous term (Term II, which carries the marks) unseeded — observed
        // live: junior generated against Term III instead of Term II.
        $eot = ExamType::firstOrCreate(['code' => 'EOT'], ['name' => 'End of Term Examination', 'contributes_to_report_total' => true]);
        $previous = $terms
            ->filter(fn ($t) => $current && $t->id !== $current->id
                && Carbon::parse($t->ends_on)->lt(Carbon::parse($current->starts_on))
                && Exam::where('school_id', $schoolId)
                    ->where('academic_term_id', $t->id)
                    ->where('exam_type_id', $eot->id)
                    ->whereHas('marks', fn ($q) => $q->where('school_id', $schoolId))
                    ->exists())
            ->sortByDesc(fn ($t) => Carbon::parse($t->ends_on)->getTimestamp())
            ->first();

        if (! $previous) {
            $this->command?->warn('Demo report cards skipped: no previous term with EOT exams.');

            return;
        }

        $links = StandardLink::where('school_id', $schoolId)->with('section')->get();

        $generated = 0;

        foreach ($links as $link) {
            // Pin the PREVIOUS term's EOT exam explicitly. resolveExam/the job's
            // default lookup has no term filter and would otherwise pick the
            // current term's newer exam (staging: Term III EOTs have higher ids).
            $exam = Exam::where('school_id', $schoolId)
                ->where('section_id', $link->section_id)
                ->where('standard_id', $link->standard_id)
                ->where('academic_term_id', $previous->id)
                ->where('exam_type_id', $eot->id)
                ->whereHas('marks', fn ($q) => $q->where('school_id', $schoolId))
                ->latest()
                ->first();

            if (! $exam) {
                continue;
            }

            $generation = ReportGeneration::firstOrCreate(
                [
                    'school_id' => $schoolId,
                    'standard_link_id' => $link->id,
                    'mode' => 'merged',
                    'academic_term_id' => $previous->id,
                ],
                [
                    'class_name' => $link->section->name ?? 'class',
                    'status' => 'pending',
                    'requested_by' => null,
                ]
            );

            // (Re)run through the real pipeline when pending/failed, or when the
            // row claims completed but the PDF file is gone (Laravel Cloud wipes
            // storage/app/reports on redeploy, leaving a stale "completed" row).
            $fileMissing = $generation->status === 'completed'
                && (! $generation->file_path || ! file_exists(storage_path('app/' . $generation->file_path)));

            if ($generation->status !== 'completed' || $fileMissing) {
                $generation->update(['status' => 'pending', 'error' => null]);

                // dispatchSync runs the job inline (no worker needed) and we pin
                // the exam so re-runs stay deterministic once the current term's
                // exams are newer than the previous term's.
                GenerateClassReportsJob::dispatchSync($generation->id, $exam->id);
                $generation->refresh();
            }

            if ($generation->status === 'completed') {
                $generated++;
            } else {
                $this->command?->warn("Demo report cards: {$link->section->name} generation {$generation->status} ({$generation->error}).");
            }
        }

        $this->command?->info("Demo report cards: {$generated}/".count($links)." previous-term ({$previous->name}) class PDFs ready; current term left ungenerated.");
    }

    // ───────────────────────────────────────────────────────────── manifest

    protected function richWriteManifest(): void
    {
        $emails = User::where('school_id', $this->school->id)->pluck('email')->all();

        DemoSeedManifest::write($this->school->id, [
            'school_id' => $this->school->id,
            'school_name' => $this->school->name,
            'seeder' => static::class,
            'seeded_at' => now()->toDateTimeString(),
            'user_emails' => array_values(array_filter($emails)),
            'walkthrough' => $this->richWalkthrough,
        ]);

        $this->command?->info('Demo manifest written to school_details (meta_key demo_manifest).');
    }

    // ─────────────────────────────────────────────────────────────── helpers

    /**
     * The last $count school days (weekdays), ascending, ending today.
     *
     * @return array<int, string>
     */
    protected function richSchoolDays(int $count): array
    {
        $days = [];
        $cursor = now()->copy()->startOfDay();

        while (count($days) < $count) {
            if (! $cursor->isWeekend()) {
                array_unshift($days, $cursor->toDateString());
            }

            $cursor->subDay();
        }

        return $days;
    }

    /**
     * Walkthrough classes whose attendance for "today" stays untaken:
     * teacher1's class plus the configured second class.
     *
     * @return array<int, int>
     */
    protected function richSkipLinkIds(): array
    {
        $schoolId = $this->school->id;
        $teacher1Id = $this->richTeacher1()->id;

        $ids = [];

        $teacherLink = StandardLink::where('school_id', $schoolId)->where('class_teacher_id', $teacher1Id)->orderBy('id')->first();

        if ($teacherLink) {
            $ids[] = $teacherLink->id;
        }

        $second = $this->richConfig()['skip_second'] ?? null;

        if ($second && ($section = Section::where('school_id', $schoolId)->where('name', $second['section'])->first())) {
            $query = StandardLink::where('school_id', $schoolId)->where('section_id', $section->id);

            if (! empty($second['stream'])) {
                $query->where('stream', $second['stream']);
            }

            $link = $query->orderBy('id')->first();

            if ($link) {
                $ids[] = $link->id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * The subject the walkthrough teacher will enter marks for: prefer a
     * subject teacher1 already teaches in an examined class, fall back to
     * the configured pair and make sure a teacher link exists.
     *
     * @param  \Illuminate\Support\Collection<int, Section>  $sections
     * @return array{0: Section|null, 1: Subject|null}
     */
    protected function richOpenPair($sections): array
    {
        $teacher1Id = $this->richTeacher1()->id;
        $sectionIds = $sections->pluck('id')->all();

        $candidates = Teacherlink::where('school_id', $this->school->id)
            ->where('teacher_id', $teacher1Id)
            ->with('subject')
            ->get()
            ->filter(fn ($link) => $link->subject && in_array($link->subject->section_id, $sectionIds, true));

        if ($candidates->isNotEmpty()) {
            $link = $candidates->sortByDesc(fn ($link) => (string) $link->subject->section_id)->first();
            $section = $sections->firstWhere('id', $link->subject->section_id);

            if ($section) {
                return [$section, $link->subject];
            }
        }

        $fallback = $this->richConfig()['fallback_open'] ?? null;

        if (! $fallback) {
            return [null, null];
        }

        $section = $sections->firstWhere('name', $fallback['section']);

        if (! $section) {
            return [null, null];
        }

        $subject = Subject::where('school_id', $this->school->id)
            ->where('section_id', $section->id)
            ->where('name', $fallback['subject'])
            ->first()
            ?? Subject::where('school_id', $this->school->id)->where('section_id', $section->id)->orderBy('id')->first();

        if (! $subject) {
            return [null, null];
        }

        // Guarantee the walkthrough teacher is linked as a teacher of this subject.
        $link = StandardLink::where('school_id', $this->school->id)
            ->where('section_id', $section->id)
            ->orderBy('id')
            ->first();

        if ($link) {
            Teacherlink::firstOrCreate(
                [
                    'school_id' => $this->school->id,
                    'academic_year_id' => $this->year->id,
                    'standardLink_id' => $link->id,
                    'subject_id' => $subject->id,
                ],
                ['teacher_id' => $teacher1Id]
            );
        }

        return [$section, $subject];
    }

    protected function richCurrentTerm(): ?AcademicTerm
    {
        return AcademicTerm::where('school_id', $this->school->id)
            ->orderByDesc('ends_on')
            ->get()
            ->firstWhere('status', 'current');
    }

    protected function richDomain(): string
    {
        return defined('static::DOMAIN') ? static::DOMAIN : 'demo.klassapp.test';
    }

    protected function richFictionalPhone(string $seed): string
    {
        return '070' . str_pad((string) (crc32($seed) % 10000000), 7, '0', STR_PAD_LEFT);
    }

    protected function richTeacherProfiles(): void
    {
        $female = ['Grace', 'Rita', 'Joan', 'Esther', 'Diana', 'Sarah', 'Alice'];
        $teachers = User::query()
            ->where('school_id', $this->school->id)
            ->where('usergroup_id', 5)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        foreach ($teachers as $index => $teacher) {
            $profile = \App\Models\Userprofile::firstOrNew(['user_id' => $teacher->id]);
            $first = (string) ($profile->firstname ?: 'Teacher');
            $profile->forceFill([
                'user_id' => $teacher->id,
                'school_id' => $this->school->id,
                'usergroup_id' => 5,
                'date_of_birth' => $profile->date_of_birth ?: Carbon::create(1988, ($index % 12) + 1, ($index % 27) + 1)->toDateString(),
                'gender' => in_array($first, $female, true) ? 'female' : 'male',
                'joining_date' => $profile->joining_date ?: '2022-02-07',
            ])->save();

            $row = DB::table('teacherprofile')->where('user_id', $teacher->id)->whereNull('deleted_at')->first();
            $values = [
                'school_id' => $this->school->id,
                'academic_year_id' => $this->year->id,
                'designation' => $row->designation ?? 'teacher',
                'employee_id' => $row->employee_id ?? sprintf('EMP-%d-%04d', $this->school->id, $index + 1),
                'job_type' => $row->job_type ?? 'full_time',
                'status' => 1,
                'updated_at' => now(),
            ];
            if ($row) {
                DB::table('teacherprofile')->where('id', $row->id)->update($values);
            } else {
                DB::table('teacherprofile')->insert($values + [
                    'user_id' => $teacher->id,
                    'created_at' => now(),
                ]);
            }
        }
    }

    protected function richSchoolProfile(): void
    {
        $countryId = DB::table('countries')->where('name', 'Uganda')->value('id');
        if (! $countryId) {
            $countryId = DB::table('countries')->insertGetId([
                'name' => 'Uganda',
                'short_name' => 'UG',
                'iso_code' => 'UG',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $stateId = DB::table('states')->where('country_id', $countryId)->where('name', 'Central')->value('id');
        if (! $stateId) {
            $stateId = DB::table('states')->insertGetId([
                'country_id' => $countryId,
                'name' => 'Central',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $cityId = DB::table('cities')->where('country_id', $countryId)->where('name', 'Kampala')->value('id');
        if (! $cityId) {
            $city = [
                'country_id' => $countryId,
                'name' => 'Kampala',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('cities', 'state_id')) {
                $city['state_id'] = $stateId;
            }
            $cityId = DB::table('cities')->insertGetId($city);
        }

        $profile = [
            'address' => $this->school->address ?: 'Plot 12, Sample Road',
            'motto' => $this->school->motto ?: 'Learning together',
        ];
        if (Schema::hasColumn('schools', 'country_id')) {
            $profile['country_id'] = $this->school->country_id ?: $countryId;
        }
        if (Schema::hasColumn('schools', 'state_id')) {
            $profile['state_id'] = $this->school->state_id ?: $stateId;
        }
        if (Schema::hasColumn('schools', 'city_id')) {
            $profile['city_id'] = $this->school->city_id ?: $cityId;
        }
        $this->school->forceFill($profile)->save();

        foreach ([
            'website' => 'https://demo.klassapp.test',
            'affiliated_by' => 'Sample examinations board',
            'landline_no' => '0414000100',
        ] as $key => $value) {
            DB::table('school_details')->updateOrInsert(
                ['school_id' => $this->school->id, 'meta_key' => $key],
                ['meta_value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    protected function richAdmin(): User
    {
        return $this->staff['admin'] ?? User::where('school_id', $this->school->id)->where('usergroup_id', 3)->orderBy('id')->firstOrFail();
    }

    protected function richHead(): ?User
    {
        return $this->staff['head'] ?? User::where('school_id', $this->school->id)->where('usergroup_id', 4)->orderBy('id')->first();
    }

    protected function richTeacher1(): User
    {
        return $this->staff['teacher1'] ?? User::where('school_id', $this->school->id)->where('usergroup_id', 5)->orderBy('id')->firstOrFail();
    }
}
