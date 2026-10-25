<?php

namespace Tests\Support\Onboarding;

/**
 * One trimmed school the wizard and Toshi both receive.
 *
 * Names, EMIS codes, and admin emails are the demo values. Each face adds its
 * own suffix at run time so two schools can sit in one database. The comparable
 * snapshot strips those fields.
 */
final class OnboardingSchoolFixture
{
    /**
     * @param  array<string, list<string>>  $streams  Base class name => stream labels. Classes with no streams are omitted; the category seeder still creates them.
     * @param  list<array{name: string, class: string, stream: string, gender: string, parent: string, parent_phone: string, school_student_id: string, board_registration_number: string, date_of_birth: string}>  $students
     * @param  list<array{name: string, start: string, end: string, status: string}>  $terms
     * @param  list<array{name: string, amount: int, scope: string, class: string, term: string, yearly: bool}>  $fees
     * @param  list<array{name: string, email: string, classes: string}>  $teachers  Present so the harness can prove it did not invent teacher rows.
     */
    public function __construct(
        public string $key,
        public string $schoolName,
        public string $emis,
        public string $category,
        public string $adminName,
        public string $adminEmail,
        public string $adminPhone,
        public string $whatsapp,
        public string $academicYearName,
        public string $academicYearStart,
        public string $academicYearEnd,
        public array $streams,
        public string $extraSubjectClass,
        public string $extraSubject,
        public array $students,
        public array $terms,
        public array $fees,
        public array $teachers,
        public string $planName = 'Freemium',
        public string $studentSize = 'Up to 500',
        public string $country = 'Uganda',
        public string $curriculum = 'uneb',
    ) {}
}
