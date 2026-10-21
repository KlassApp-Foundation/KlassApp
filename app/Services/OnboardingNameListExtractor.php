<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Shared name/list extraction for Toshi and the manual onboarding wizard.
 *
 * Parses CSV/TXT/XLSX/XLS (tabular), plus PDF/DOCX (line-based name lists).
 */
class OnboardingNameListExtractor
{
    public const ACCEPTED_MIMES = ['csv', 'xlsx', 'xls', 'txt', 'docx', 'pdf'];

    /**
     * Extract people rows from a tabular or document file.
     *
     * @return list<array{
     *     name: string,
     *     class: string,
     *     stream: string,
     *     parent: string,
     *     parent_phone: string,
     *     email: string,
     *     phone: string,
     *     subjects: string,
     *     classes: string,
     *     gender: string,
     *     school_student_id: string,
     *     lin: string,
     *     board_registration_number: string,
     *     date_of_birth: string
     * }>
     */
    public function extractNamesFromFile(string $path, string $ext): array
    {
        $ext = strtolower($ext);
        $headers = [];
        $dataRows = [];
        $headerRowIdx = 0;

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($path);
                $activeRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
                $allRows = $activeRows;
                $bestScore = $this->headerRowScore($activeRows);
                // A cover or instructions sheet left active must not hide the roster.
                if ($bestScore < 2) {
                    foreach ($spreadsheet->getAllSheets() as $sheet) {
                        $candidate = $sheet->toArray(null, true, true, false);
                        $score = $this->headerRowScore($candidate);
                        if ($score > $bestScore) {
                            $bestScore = $score;
                            $allRows = $candidate;
                        }
                    }
                }
                if (empty($allRows)) {
                    return [];
                }

                $headerRowIdx = $this->headerRowIndex($allRows);
                $headers = array_map(fn ($cell) => $this->cellText($cell), $allRows[$headerRowIdx] ?? []);
                $dataRows = array_slice($allRows, $headerRowIdx + 1);
            } catch (\Exception $e) {
                Log::warning('XLSX parse failed: '.$e->getMessage());

                return [];
            }
        } elseif ($ext === 'pdf') {
            try {
                $parser = new \Smalot\PdfParser\Parser;
                $pdf = $parser->parseFile($path);
                $text = $pdf->getText();

                return array_map(
                    fn (string $name) => $this->emptyRow(['name' => $name]),
                    $this->parseNameList($text)
                );
            } catch (\Exception $e) {
                Log::warning('PDF parse failed: '.$e->getMessage());

                return [];
            }
        } elseif ($ext === 'docx') {
            try {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
                $text = '';
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $text .= $element->getText()."\n";
                        } elseif (method_exists($element, 'getElements')) {
                            foreach ($element->getElements() as $child) {
                                if (method_exists($child, 'getText')) {
                                    $text .= $child->getText()."\n";
                                }
                            }
                        }
                    }
                }

                return array_map(
                    fn (string $name) => $this->emptyRow(['name' => $name]),
                    $this->parseNameList($text)
                );
            } catch (\Exception $e) {
                Log::warning('DOCX parse failed: '.$e->getMessage());

                return [];
            }
        } else {
            $handle = fopen($path, 'r');
            if (! $handle) {
                return [];
            }
            $headerLine = fgetcsv($handle);
            if ($headerLine === false) {
                fclose($handle);

                return [];
            }
            $headers = array_map(fn ($cell) => $this->cellText($cell), $headerLine);
            while (($row = fgetcsv($handle)) !== false) {
                $dataRows[] = $row;
            }
            fclose($handle);
        }

        if (empty($dataRows)) {
            return [];
        }

        $lowerHeaders = array_map(fn ($header) => $this->normaliseHeader((string) $header), $headers);
        $nameIdx = $this->findHeaderIndex($lowerHeaders, [
            'firstname',
            'first name',
            'first_name',
            'name',
            'student name',
            'student_name',
            'teacher name',
            "teacher's name",
            'teachers name',
            'teacher_name',
            'staff name',
            'full name',
            'full_name',
        ]);

        $lastIdx = array_search('lastname', $lowerHeaders, true);
        if ($lastIdx === false) {
            $lastIdx = array_search('last_name', $lowerHeaders, true);
        }

        $classIdx = $this->findHeaderIndex($lowerHeaders, ['class', 'section', 'grade', 'form', 'standard', 'class_name']);
        $streamIdx = $this->findHeaderIndex($lowerHeaders, ['stream', 'section_stream']);
        $parentIdx = $this->findHeaderIndex($lowerHeaders, ['parent name', 'parent', 'parent_name', 'guardian', 'guardian name']);
        $parentPhoneIdx = $this->findHeaderIndex($lowerHeaders, ['parent phone', 'parent_phone', 'guardian phone', 'guardian_phone']);
        $emailIdx = $this->findHeaderIndex($lowerHeaders, ['email', 'e-mail', 'mail']);
        $phoneIdx = $this->findHeaderIndex($lowerHeaders, ['phone', 'mobile', 'tel', 'telephone']);
        $subjectsIdx = $this->findHeaderIndex($lowerHeaders, ['subjects', 'subject']);
        $classesIdx = $this->findHeaderIndex($lowerHeaders, ['classes']);
        $genderIdx = $this->findHeaderIndex($lowerHeaders, ['gender', 'sex']);
        $schoolIdIdx = $this->findHeaderIndex($lowerHeaders, [
            'school student id',
            'school_student_id',
            'student id',
            'admission number',
            'admission no',
            'adm no',
        ]);
        // Distinct from school_student_id — Uganda national Learner Identification Number.
        $linIdx = $this->findHeaderIndex($lowerHeaders, [
            'lin',
            'learner identification number',
            'learner id',
            'learner_id',
            'emis lin',
            'emis_lin',
        ]);
        $boardRegIdx = $this->findHeaderIndex($lowerHeaders, [
            'uneb reg no.',
            'uneb reg no',
            'uneb registration',
            'board registration number',
            'board_registration_number',
            'uneb number',
            'uneb no',
        ]);
        $dobIdx = $this->findHeaderIndex($lowerHeaders, [
            'date of birth',
            'date_of_birth',
            'dob',
            'birth date',
            'birthday',
        ]);

        $names = [];
        $prevColB = '';
        foreach ($dataRows as $offset => $row) {
            if (! is_array($row) || count($row) === 0) {
                continue;
            }
            $row = array_map(fn ($v) => $this->cellText($v), $row);
            $sheetRow = $headerRowIdx + (int) $offset + 2;

            if ($prevColB !== '' && count($row) > 1 && $row[0] === $prevColB) {
                $prevColB = $row[1] ?? '';

                continue;
            }

            if ($nameIdx !== null) {
                $name = $row[$nameIdx] ?? '';
            } elseif (count($row) > 0 && $row[0] !== '') {
                $name = $row[0];
            } elseif (count($row) > 1 && ($row[1] ?? '') !== '') {
                $name = $row[1];
            } else {
                continue;
            }

            $prevColB = $row[1] ?? '';

            if ($nameIdx === null && count($row) > 1 && ($row[1] ?? '') !== '' && $row[0] !== $row[1]) {
                $candidate = $row[1];
                $keepCurrent = $this->cellLooksLikePerson($name) && $this->cellLooksLikeContact($candidate);
                if (! $keepCurrent && ! in_array(strtoupper($candidate), ['BOARDERS', 'DAY SCHOLAR', 'BOARDING', 'DAY'], true)) {
                    $name = $candidate;
                }
            }

            if (! $this->cellLooksLikePerson($name) && $nameIdx === null) {
                foreach ($row as $cell) {
                    if ($this->cellLooksLikePerson($cell)) {
                        $name = $cell;
                        break;
                    }
                }
            }

            if ($lastIdx !== false && ! empty($row[$lastIdx] ?? '') && $this->cellLooksLikePerson($name)) {
                $name .= ' '.$row[$lastIdx];
            }

            if (! $this->cellLooksLikePerson($name)) {
                $name = '';
            }

            $hasDetails = $name !== ''
                || ($emailIdx !== null && ($row[$emailIdx] ?? '') !== '')
                || ($phoneIdx !== null && ($row[$phoneIdx] ?? '') !== '')
                || ($classIdx !== null && ($row[$classIdx] ?? '') !== '')
                || ($classesIdx !== null && ($row[$classesIdx] ?? '') !== '')
                || ($subjectsIdx !== null && ($row[$subjectsIdx] ?? '') !== '');
            if (! $hasDetails) {
                continue;
            }

            $gender = ($genderIdx !== null && ! empty($row[$genderIdx] ?? '')) ? $row[$genderIdx] : '';
            $genderLower = strtolower($gender);
            if (in_array($genderLower, ['m', 'male', 'boy'], true)) {
                $gender = 'male';
            } elseif (in_array($genderLower, ['f', 'female', 'girl'], true)) {
                $gender = 'female';
            } else {
                $gender = '';
            }

            $built = $this->emptyRow([
                'name' => $name,
                'class' => ($classIdx !== null && ! empty($row[$classIdx] ?? '')) ? $row[$classIdx] : '',
                'stream' => ($streamIdx !== null && ! empty($row[$streamIdx] ?? '')) ? $row[$streamIdx] : '',
                'parent' => ($parentIdx !== null && ! empty($row[$parentIdx] ?? '')) ? $row[$parentIdx] : '',
                'parent_phone' => ($parentPhoneIdx !== null && ! empty($row[$parentPhoneIdx] ?? '')) ? $row[$parentPhoneIdx] : '',
                'email' => ($emailIdx !== null && ! empty($row[$emailIdx] ?? '')) ? $row[$emailIdx] : '',
                'phone' => ($phoneIdx !== null && ! empty($row[$phoneIdx] ?? '')) ? $row[$phoneIdx] : '',
                'subjects' => ($subjectsIdx !== null && ! empty($row[$subjectsIdx] ?? '')) ? $row[$subjectsIdx] : '',
                'classes' => ($classesIdx !== null && ! empty($row[$classesIdx] ?? '')) ? $row[$classesIdx] : '',
                'gender' => $gender,
                'school_student_id' => ($schoolIdIdx !== null && ! empty($row[$schoolIdIdx] ?? '')) ? $row[$schoolIdIdx] : '',
                'lin' => ($linIdx !== null && ! empty($row[$linIdx] ?? '')) ? $row[$linIdx] : '',
                'board_registration_number' => ($boardRegIdx !== null && ! empty($row[$boardRegIdx] ?? '')) ? $row[$boardRegIdx] : '',
                'date_of_birth' => ($dobIdx !== null && ! empty($row[$dobIdx] ?? '')) ? $row[$dobIdx] : '',
            ]);
            $built['row'] = $sheetRow;
            $names[] = $built;
        }

        return $names;
    }

    /**
     * Extract a single optional column (e.g. LIN) from a spreadsheet/CSV.
     *
     * @param  list<string>  $aliases
     * @return list<string>
     */
    public function extractColumnFromFile(string $path, string $ext, array $aliases): array
    {
        $ext = strtolower($ext);
        $headers = [];
        $dataRows = [];

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($path);
                $allRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
                if (empty($allRows)) {
                    return [];
                }
                $knownColumns = array_merge(['name', 'firstname', 'student_name'], $aliases);
                $headerRowIdx = 0;
                foreach ($allRows as $ri => $row) {
                    $rowLower = array_map('strtolower', array_map('trim', $row ?? []));
                    $matchCount = 0;
                    foreach ($knownColumns as $kc) {
                        if (in_array($kc, $rowLower, true)) {
                            $matchCount++;
                        }
                    }
                    if ($matchCount >= 2) {
                        $headerRowIdx = $ri;
                        break;
                    }
                }
                $headers = array_map('trim', $allRows[$headerRowIdx]);
                $dataRows = array_slice($allRows, $headerRowIdx + 1);
            } catch (\Exception $e) {
                Log::warning('XLSX column extraction failed: '.$e->getMessage());

                return [];
            }
        } elseif ($ext === 'csv' || $ext === 'txt') {
            $handle = fopen($path, 'r');
            if (! $handle) {
                return [];
            }
            $headerLine = fgetcsv($handle);
            if ($headerLine === false) {
                fclose($handle);

                return [];
            }
            $headers = array_map('trim', $headerLine);
            while (($row = fgetcsv($handle)) !== false) {
                $dataRows[] = $row;
            }
            fclose($handle);
        } else {
            return [];
        }

        if (empty($headers) || empty($dataRows)) {
            return [];
        }

        $lowerHeaders = array_map('strtolower', $headers);
        $colIdx = null;
        foreach ($aliases as $alias) {
            $idx = array_search(strtolower($alias), $lowerHeaders, true);
            if ($idx !== false) {
                $colIdx = $idx;
                break;
            }
        }
        if ($colIdx === null) {
            return [];
        }

        $values = [];
        foreach ($dataRows as $row) {
            if (! is_array($row) || count($row) <= $colIdx) {
                $values[] = '';

                continue;
            }
            $val = trim((string) ($row[$colIdx] ?? ''));
            $upper = strtoupper($val);
            if (in_array($upper, ['LIN', 'LEARNER ID', 'LEARNER IDENTIFICATION NUMBER', 'EMIS LIN', ''], true)) {
                $values[] = '';

                continue;
            }
            $values[] = $val;
        }

        return $values;
    }

    /**
     * Extract teacher-subject-class links from a spreadsheet (CSV/XLSX).
     * Expects columns: Teacher, Subject, Class, Phone (optional).
     *
     * @param  list<string>  $knownTeacherNames  When non-empty, only matching teachers are kept
     * @param  list<string>  $knownClassNames  When non-empty, only matching classes are kept
     * @return array{links: list<array{teacher: string, subject: string, class: string, phone: string}>, phones: array<string, string>}
     */
    public function extractTeacherLinksFromFile(
        string $path,
        string $ext,
        array $knownTeacherNames = [],
        array $knownClassNames = []
    ): array {
        $ext = strtolower($ext);
        $rows = [];

        try {
            if (in_array($ext, ['xlsx', 'xls'], true)) {
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
                return ['links' => [], 'phones' => []];
            }
        } catch (\Exception $e) {
            Log::warning('Teacher links file parse error: '.$e->getMessage());

            return ['links' => [], 'phones' => []];
        }

        if (empty($rows)) {
            return ['links' => [], 'phones' => []];
        }

        $header = array_map('strtolower', array_map('trim', $rows[0]));
        $colMap = ['teacher' => null, 'subject' => null, 'class' => null, 'phone' => null];
        foreach ($header as $i => $h) {
            if (str_contains($h, 'teacher') || str_contains($h, 'name')) {
                $colMap['teacher'] = $i;
            }
            if (str_contains($h, 'subject')) {
                $colMap['subject'] = $i;
            }
            if (str_contains($h, 'class') || str_contains($h, 'grade')) {
                $colMap['class'] = $i;
            }
            if (str_contains($h, 'phone') || str_contains($h, 'mobile') || str_contains($h, 'tel')) {
                $colMap['phone'] = $i;
            }
        }

        if ($colMap['teacher'] === null && count($rows[0]) >= 3) {
            $colMap = ['teacher' => 0, 'subject' => 1, 'class' => 2, 'phone' => 3];
        }

        if ($colMap['teacher'] === null || $colMap['subject'] === null || $colMap['class'] === null) {
            return ['links' => [], 'phones' => []];
        }

        $parsed = [];
        $phones = [];
        $dataRows = array_slice($rows, 1);
        $validateTeachers = $knownTeacherNames !== [];
        $validateClasses = $knownClassNames !== [];

        foreach ($dataRows as $row) {
            $teacher = trim((string) ($row[$colMap['teacher']] ?? ''));
            $subject = trim((string) ($row[$colMap['subject']] ?? ''));
            $class = trim((string) ($row[$colMap['class']] ?? ''));
            $phone = $colMap['phone'] !== null ? trim((string) ($row[$colMap['phone']] ?? '')) : '';

            if (! ($teacher && $subject && $class)) {
                continue;
            }

            $teacherOk = ! $validateTeachers || in_array($teacher, $knownTeacherNames, true);
            $classOk = ! $validateClasses || in_array($class, $knownClassNames, true);

            if ($teacherOk && $classOk) {
                $parsed[] = [
                    'teacher' => $teacher,
                    'subject' => $subject,
                    'class' => $class,
                    'phone' => $phone,
                ];
                if ($phone !== '') {
                    $phones[$teacher] = $phone;
                }
            }
        }

        return ['links' => $parsed, 'phones' => $phones];
    }

    /**
     * @return list<string>
     */
    public function parseNameList(string $text): array
    {
        $lines = preg_split('/[\n,]+/', $text) ?: [];
        $names = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) > 3 && ! is_numeric($line)) {
                $names[] = $line;
            }
        }

        return array_slice(array_values(array_unique($names)), 0, 50);
    }

    /**
     * @param  list<string>  $aliases
     * @param  list<string>  $lowerHeaders
     */
    private function findHeaderIndex(array $lowerHeaders, array $aliases): ?int
    {
        $normalised = array_map(fn ($header) => $this->normaliseHeader((string) $header), $lowerHeaders);
        foreach ($aliases as $col) {
            $idx = array_search($this->normaliseHeader($col), $normalised, true);
            if ($idx !== false) {
                return (int) $idx;
            }
        }

        return null;
    }

    /**
     * @param  list<array<int, mixed>|mixed>  $rows
     */
    private function headerRowScore(array $rows): int
    {
        $best = 0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $score = $this->headerMatchCount($row);
            if ($score > $best) {
                $best = $score;
            }
        }

        return $best;
    }

    /**
     * @param  list<array<int, mixed>|mixed>  $rows
     */
    private function headerRowIndex(array $rows): int
    {
        foreach ($rows as $index => $row) {
            if (is_array($row) && $this->headerMatchCount($row) >= 2) {
                return (int) $index;
            }
        }

        return 0;
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function headerMatchCount(array $row): int
    {
        $known = [
            'name', 'firstname', 'first name', 'first_name', 'student name', 'student_name',
            'teacher name', "teacher's name", 'teacher_name', 'staff name', 'full name', 'full_name',
            'class', 'classes', 'stream', 'subject', 'subjects', 'email', 'e-mail', 'phone', 'mobile',
            'parent name', 'parent phone',
        ];
        $cells = array_map(fn ($cell) => $this->normaliseHeader($this->cellText($cell)), $row);
        $matches = 0;
        foreach ($known as $header) {
            if (in_array($this->normaliseHeader($header), $cells, true)) {
                $matches++;
            }
        }

        return $matches;
    }

    private function cellText(mixed $value): string
    {
        if ($value === null || is_bool($value)) {
            return '';
        }

        return trim((string) $value);
    }

    private function normaliseHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/u', '', $header) ?? $header;
        $header = str_replace(['’', '`', '´'], "'", strtolower(trim($header)));
        $header = preg_replace('/\s+/', ' ', $header) ?? $header;

        return rtrim($header, " *:");
    }

    private function cellLooksLikePerson(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || $this->cellLooksLikeContact($value) || preg_match('/^\d+$/', $value) === 1) {
            return false;
        }
        if (in_array(strtoupper($value), ['BOARDERS', 'DAY SCHOLAR', 'BOARDING', 'DAY', 'STAFF', 'TEACHERS', 'STUDENTS', 'CLASS', 'NAME', 'NAMES'], true)) {
            return false;
        }
        if (preg_match('/^(?:p\.?\s*|primary\s+|s\.?\s*|senior\s+)(?:[1-7]|one|two|three|four|five|six|seven)\b/i', $value) === 1) {
            return false;
        }
        if (in_array(strtolower($value), ['baby', 'baby class', 'middle', 'middle class', 'top', 'top class'], true)) {
            return false;
        }

        return preg_match('/\p{L}/u', $value) === 1;
    }

    private function cellLooksLikeContact(string $value): bool
    {
        $value = trim($value);
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return strlen($digits) >= 7 && preg_match('/^\+?[\d\s().-]+$/', $value) === 1;
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array{
     *     name: string,
     *     class: string,
     *     stream: string,
     *     parent: string,
     *     parent_phone: string,
     *     email: string,
     *     phone: string,
     *     subjects: string,
     *     classes: string,
     *     gender: string,
     *     school_student_id: string,
     *     lin: string,
     *     board_registration_number: string,
     *     date_of_birth: string
     * }
     */
    private function emptyRow(array $overrides = []): array
    {
        return array_merge([
            'name' => '',
            'class' => '',
            'stream' => '',
            'parent' => '',
            'parent_phone' => '',
            'email' => '',
            'phone' => '',
            'subjects' => '',
            'classes' => '',
            'gender' => '',
            'school_student_id' => '',
            'lin' => '',
            'board_registration_number' => '',
            'date_of_birth' => '',
        ], $overrides);
    }
}
