<?php

namespace App\Services\Onboarding;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Fictional checklist workbooks for the Toshi onboarding proof of concept.
 * Nothing in here is a real school, learner, or phone.
 */
final class DemoChecklistWorkbook
{
    public const SCHOOL = 'Northwind Harbour School';

    /**
     * @return array{0: string, 1: string} problem workbook, all-correct twin
     */
    public function writePair(string $directory): array
    {
        $problem = $this->write($directory.'/poc-checklist-workbook.xlsx', true, true, 'Yes');
        $clean = $this->write($directory.'/poc-checklist-workbook-correct.xlsx', false, true, 'Yes');

        return [$problem, $clean];
    }

    public function write(string $path, bool $problems = true, bool $full = true, string $dpa = 'Yes'): string
    {
        $sections = $this->sections($full);
        $teachers = $this->teachers($full);
        $subjects = $full
            ? ['Mathematics', 'English', 'Science', 'Social Studies', 'Creative Arts']
            : ['Mathematics'];
        $terms = $full ? [
            ['2026', 'Term 1', '02/02/2026', '30/04/2026'],
            ['2026', 'Term 2', '04/05/2026', '31/07/2026'],
            ['2026', 'Term 3', '01/09/2026', '27/11/2026'],
        ] : [
            ['2026', 'Term 1', '02/02/2026', '30/04/2026'],
        ];

        $book = new Spreadsheet;
        $this->sheet($book, 'School Details', [
            ['Field', 'Answer'],
            ['School name', self::SCHOOL],
            ['Country', 'Kenya'],
        ], true);
        $this->sheet($book, 'Admin & Contacts', [
            ['Role *', 'Full name *', 'Email', 'Phone', 'WhatsApp on this number?', 'Notes'],
            ['Example', 'Example Person', 'person@northwind.example', '0712000098', 'Yes', ''],
            ['Head teacher', 'Nia Example', 'nia@northwind.example', '0712000099', 'Yes', ''],
        ]);
        $termRows = [['Academic year *', 'Term *', 'Start date *', 'End date *', 'Notes'], ['2026', 'Term 1', '02/02/2026', '30/04/2026', '']];
        foreach ($terms as $term) {
            $termRows[] = [$term[0], $term[1], $term[2], $term[3], ''];
        }
        $this->sheet($book, 'Terms & Exams', $termRows);

        $classRows = [['Class *', 'Stream', 'Number of learners (approx.)', 'Class teacher full name', 'Class teacher email', 'Notes'], ['Example', '', 1, '', '', '']];
        foreach ($sections as $index => $section) {
            $approx = $section['count'] + (($problems && $section['class'] === 'Baby Class' && $section['stream'] === '') ? 1 : 0);
            $teacher = $teachers[$index % count($teachers)];
            $classRows[] = [$section['class'], $section['stream'], $approx, $teacher['name'], '', ''];
        }
        $this->sheet($book, 'Classes & Streams', $classRows);

        $subjectRows = [['Subject or learning area *', 'Section', 'Classes that take it', 'Counts on report card total?', 'Short code', 'Notes'], ['Example', 'Core', 'All', 'Yes', 'EX', '']];
        foreach ($subjects as $subject) {
            $subjectRows[] = [$subject, 'Core', 'All', 'Yes', '', ''];
        }
        $this->sheet($book, 'Subjects', $subjectRows);

        $teacherRows = [['Full name *', 'Email *', 'Phone', 'Gender', 'Role', 'Notes'], ['Example Teacher', 'example.teacher@northwind.example', '0713000099', 'Female', 'Teacher', '']];
        foreach ($teachers as $teacher) {
            $teacherRows[] = [$teacher['name'], $teacher['email'], $teacher['phone'], $teacher['gender'], 'Teacher', ''];
        }
        $this->sheet($book, 'Teachers', $teacherRows);

        $assignmentRows = [['Teacher full name *', 'Class *', 'Stream', 'Subject *', 'Notes'], ['Example Teacher', 'Baby Class', '', 'Mathematics', '']];
        foreach ($teachers as $index => $teacher) {
            $section = $sections[$index % count($sections)];
            $assignmentRows[] = [$teacher['name'], $section['class'], $section['stream'], $subjects[$index % count($subjects)], ''];
        }
        $this->sheet($book, 'Teaching Assignments', $assignmentRows);

        $learnerRows = [[
            'Learner full name *', 'Class *', 'Stream', 'Gender', 'Date of birth',
            'Parent / guardian full name', 'Parent / guardian phone', 'Relationship',
            'Parent / guardian email', 'Second parent / guardian name', 'Second parent / guardian phone',
            'Existing school ID number', 'Notes',
        ], [
            'Example Child', 'Baby Class', '', 'Female', '01/01/2018',
            'Example Parent', '0712000000', 'Parent', 'example.parent@northwind.example', '', '', 'NW-EXAMPLE', '',
        ]];
        $number = 1;
        foreach ($sections as $section) {
            for ($i = 0; $i < $section['count']; $i++) {
                $learnerRows[] = $this->learner($number, $section['class'], $section['stream'], $this->phone(712, $number));
                $number++;
            }
        }
        if ($problems) {
            $learnerRows[] = $this->learner($number, 'Baby Class', '', '12345', 'NW-PHONE');
            $number++;
            $learnerRows[] = $this->learner($number, 'Primary Eight', '', $this->phone(712, 9000), 'NW-CLASS');
        }
        $this->sheet($book, 'All Learners (for import)', $learnerRows);

        $perClass = [['Class', 'Stream', 'Learners'], ['Example', '', 0]];
        foreach ($sections as $section) {
            $perClass[] = [$section['class'], $section['stream'], $section['count']];
        }
        $this->sheet($book, 'Learners per Class', $perClass);

        $this->sheet($book, 'Fees', [
            ['Fee name *', 'Class *', 'Amount *', 'Currency', 'Duration (Termly, One-off, Yearly) *', 'Compulsory?', 'Notes'],
            ['Example', 'All', 1, 'USD', 'Termly', 'Yes', ''],
            ['Tuition', 'All', 1200, 'USD', 'Termly', 'Yes', ''],
        ]);
        $this->sheet($book, 'Confirmations', [
            ['Statement', 'Answer'],
            ['Example', 'No'],
            ['We agree to the data processing agreement', $dpa],
            ['Contact details are correct', 'Yes'],
        ]);

        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        IOFactory::createWriter($book, 'Xlsx')->save($path);
        $book->disconnectWorksheets();

        return $path;
    }

    /**
     * @return list<array{class: string, stream: string, count: int}>
     */
    private function sections(bool $full): array
    {
        if (! $full) {
            return [
                ['class' => 'Baby Class', 'stream' => '', 'count' => 2],
            ];
        }

        return [
            ['class' => 'Baby Class', 'stream' => '', 'count' => 14],
            ['class' => 'Middle Class', 'stream' => '', 'count' => 14],
            ['class' => 'Primary One', 'stream' => 'East', 'count' => 18],
            ['class' => 'Primary One', 'stream' => 'West', 'count' => 18],
            ['class' => 'Primary Two', 'stream' => '', 'count' => 18],
            ['class' => 'Primary Three', 'stream' => 'A', 'count' => 16],
            ['class' => 'Primary Three', 'stream' => 'B', 'count' => 14],
            ['class' => 'Primary Four', 'stream' => '', 'count' => 8],
        ];
    }

    /**
     * @return list<array{name: string, email: string, phone: string, gender: string}>
     */
    private function teachers(bool $full): array
    {
        $names = $full
            ? ['Amina Example', 'Ben Example', 'Chidi Example', 'Dina Example', 'Ezra Example', 'Farah Example', 'Gio Example', 'Hana Example']
            : ['Amina Example'];
        $teachers = [];
        foreach ($names as $index => $name) {
            $n = $index + 1;
            $teachers[] = [
                'name' => $name,
                'email' => 'teacher'.$n.'@northwind.example',
                'phone' => $this->phone(713, $n),
                'gender' => $n % 2 === 0 ? 'Male' : 'Female',
            ];
        }

        return $teachers;
    }

    /**
     * @return list<string>
     */
    private function learner(int $number, string $class, string $stream, string $phone, ?string $schoolId = null): array
    {
        $id = $schoolId ?? sprintf('NW-%04d', $number);
        $gender = $number % 2 === 0 ? 'Male' : 'Female';
        $year = 2014 + ($number % 6);

        return [
            sprintf('Learner %04d Example', $number),
            $class,
            $stream,
            $gender,
            sprintf('15/03/%d', $year),
            sprintf('Parent %04d Example', $number),
            $phone,
            'Parent',
            sprintf('parent%04d@northwind.example', $number),
            '',
            '',
            $id,
            '',
        ];
    }

    private function phone(int $prefix, int $number): string
    {
        return sprintf('0%d%06d', $prefix, $number);
    }

    /**
     * @param  list<list<int|string>>  $rows
     */
    private function sheet(Spreadsheet $book, string $title, array $rows, bool $first = false): Worksheet
    {
        $sheet = $first ? $book->getActiveSheet() : $book->createSheet();
        $sheet->setTitle($title);
        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $column => $value) {
                $sheet->setCellValueExplicit(
                    [$column + 1, $rowIndex + 1],
                    $value,
                    is_int($value) || is_float($value) ? 'n' : 's'
                );
            }
        }

        return $sheet;
    }
}
