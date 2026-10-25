<?php

namespace Tests\Support\Onboarding;

/**
 * Trimmed Sunrise and Hilltop inputs, taken from docs/demo-data.
 *
 * The academic year is the demo's literal 2027. It is not read from the clock:
 * StepIssueCatalog::academicYearCause() uses PHP date('Y'), and Carbon::setTestNow
 * does not freeze that.
 */
final class SunriseHilltopFixtures
{
    public static function sunrise(): OnboardingSchoolFixture
    {
        return new OnboardingSchoolFixture(
            key: 'sunrise',
            schoolName: 'Sunrise Primary and Nursery School',
            emis: 'DEMO-0001',
            category: 'primary_nursery',
            adminName: 'Grace Atuhaire',
            adminEmail: 'admin.sunrise@example.com',
            adminPhone: '+256700000300',
            whatsapp: '+256700000301',
            academicYearName: '2027',
            academicYearStart: '2027-01-01',
            academicYearEnd: '2027-12-31',
            streams: [
                'Primary One' => ['A', 'B'],
                'Primary Three' => ['A', 'B'],
                'Primary Five' => ['A', 'B'],
            ],
            extraSubjectClass: 'Baby Class',
            extraSubject: 'Creative Arts',
            students: [
                self::student('Richard Ssekitoleko', 'Baby Class', '', 'male', 'Herbert Ssekitoleko', '+256700000400', 'SUN-2027-001', '', '2023-12-11'),
                self::student('Sarah Wekesa', 'Primary Seven', '', 'female', 'Benon Wekesa', '+256700000510', 'SUN-2027-111', 'U0000/111', '2014-09-19'),
            ],
            terms: self::terms(),
            fees: [
                self::fee('Tuition Term 1', 250000, 'class', 'Baby Class', 'Term 1', false),
                self::fee('Lunch Term 1', 120000, 'whole_school', '', 'Term 1', false),
                self::fee('Development Fund', 50000, 'whole_school', '', '', true),
            ],
            teachers: [
                ['name' => 'Hellen Namusoke', 'email' => 'hellen.namusoke@example.com', 'classes' => 'Baby Class'],
            ],
        );
    }

    public static function hilltop(): OnboardingSchoolFixture
    {
        $streams = [];
        foreach (['Senior One', 'Senior Two', 'Senior Three', 'Senior Four'] as $class) {
            $streams[$class] = ['A', 'B'];
        }
        foreach (['Senior Five', 'Senior Six'] as $class) {
            $streams[$class] = ['BCM', 'HEG', 'MEG', 'PCM'];
        }

        return new OnboardingSchoolFixture(
            key: 'hilltop',
            schoolName: 'Hilltop O and A Level School',
            emis: 'DEMO-0002',
            category: 'o_a_level',
            adminName: 'Samuel Byaruhanga',
            adminEmail: 'admin.hilltop@example.com',
            adminPhone: '+256700000600',
            whatsapp: '+256700000601',
            academicYearName: '2027',
            academicYearStart: '2027-01-01',
            academicYearEnd: '2027-12-31',
            streams: $streams,
            extraSubjectClass: 'Senior Five',
            extraSubject: 'Mathematics',
            students: [
                self::student('Faith Kigozi', 'Senior One', 'A', 'female', 'Brian Kigozi', '+256700000700', 'HIL-2027-001', '', '2014-06-20'),
                self::student('John Mukasa', 'Senior Four', 'A', 'male', 'Tonny Mukasa', '+256700000810', 'HIL-2027-111', '', '2011-09-18'),
            ],
            terms: self::terms(),
            fees: [
                self::fee('Tuition Term 1', 650000, 'class', 'Senior One', 'Term 1', false),
                self::fee('Laboratory Term 1', 60000, 'whole_school', '', 'Term 1', false),
                self::fee('Development Fund', 100000, 'whole_school', '', '', true),
            ],
            teachers: [
                ['name' => 'Emmanuel Nampiima', 'email' => 'emmanuel.nampiima@example.com', 'classes' => 'Senior One, Senior Two'],
            ],
        );
    }

    /**
     * @return list<array{name: string, start: string, end: string, status: string}>
     */
    private static function terms(): array
    {
        return [
            ['name' => 'Term 1', 'start' => '2027-02-03', 'end' => '2027-05-02', 'status' => 'current'],
            ['name' => 'Term 2', 'start' => '2027-05-24', 'end' => '2027-08-22', 'status' => 'next'],
            ['name' => 'Term 3', 'start' => '2027-09-13', 'end' => '2027-12-05', 'status' => 'next'],
        ];
    }

    /**
     * @return array{name: string, class: string, stream: string, gender: string, parent: string, parent_phone: string, school_student_id: string, board_registration_number: string, date_of_birth: string}
     */
    private static function student(
        string $name,
        string $class,
        string $stream,
        string $gender,
        string $parent,
        string $parentPhone,
        string $schoolStudentId,
        string $boardReg,
        string $dob,
    ): array {
        return [
            'name' => $name,
            'class' => $class,
            'stream' => $stream,
            'gender' => $gender,
            'parent' => $parent,
            'parent_phone' => $parentPhone,
            'school_student_id' => $schoolStudentId,
            'board_registration_number' => $boardReg,
            'date_of_birth' => $dob,
        ];
    }

    /**
     * @return array{name: string, amount: int, scope: string, class: string, term: string, yearly: bool}
     */
    private static function fee(string $name, int $amount, string $scope, string $class, string $term, bool $yearly): array
    {
        return [
            'name' => $name,
            'amount' => $amount,
            'scope' => $scope,
            'class' => $class,
            'term' => $term,
            'yearly' => $yearly,
        ];
    }
}
