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
     *     classes: string
     * }>
     */
    public function extractNamesFromFile(string $path, string $ext): array
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

                $headerRowIdx = 0;
                $knownColumns = ['name', 'firstname', 'student_name', 'teacher_name', 'class', 'stream', 'subject', 'email', 'phone', 'parent name', 'parent phone'];
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
            $headers = array_map('trim', $headerLine);
            while (($row = fgetcsv($handle)) !== false) {
                $dataRows[] = $row;
            }
            fclose($handle);
        }

        if (empty($dataRows)) {
            return [];
        }

        $lowerHeaders = array_map('strtolower', $headers);
        $nameIdx = null;
        foreach (['firstname', 'name', 'first_name', 'student_name', 'teacher_name', 'full_name'] as $col) {
            $idx = array_search($col, $lowerHeaders, true);
            if ($idx !== false) {
                $nameIdx = $idx;
                break;
            }
        }

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

        $names = [];
        $prevColB = '';
        foreach ($dataRows as $row) {
            if (! is_array($row) || count($row) === 0) {
                continue;
            }
            $row = array_map(fn ($v) => trim((string) $v), $row);

            if ($prevColB !== '' && count($row) > 1 && $row[0] === $prevColB) {
                $prevColB = $row[1] ?? '';

                continue;
            }

            if ($nameIdx !== null && ! empty($row[$nameIdx] ?? '')) {
                $name = $row[$nameIdx];
            } elseif (count($row) > 0 && ! empty($row[0])) {
                $name = $row[0];
            } elseif (count($row) > 1 && ! empty($row[1])) {
                $name = $row[1];
            } else {
                continue;
            }

            $prevColB = $row[1] ?? '';

            $upper = strtoupper($name);
            if (in_array($upper, ['BOARDERS', 'DAY SCHOLAR', 'BOARDING', 'DAY', 'STAFF', 'TEACHERS', 'STUDENTS', 'CLASS', 'NAME', 'NAMES'], true)
                || preg_match('/^\d+$/', $name)) {
                continue;
            }

            if ($nameIdx === null && count($row) > 1 && ! empty($row[1]) && $row[0] !== $row[1]) {
                $candidate = $row[1];
                $upper2 = strtoupper($candidate);
                if (! in_array($upper2, ['BOARDERS', 'DAY SCHOLAR', 'BOARDING', 'DAY'], true)) {
                    $name = $candidate;
                }
            }

            if ($lastIdx !== false && ! empty($row[$lastIdx] ?? '')) {
                $name .= ' '.$row[$lastIdx];
            }

            $names[] = $this->emptyRow([
                'name' => $name,
                'class' => ($classIdx !== null && ! empty($row[$classIdx] ?? '')) ? $row[$classIdx] : '',
                'stream' => ($streamIdx !== null && ! empty($row[$streamIdx] ?? '')) ? $row[$streamIdx] : '',
                'parent' => ($parentIdx !== null && ! empty($row[$parentIdx] ?? '')) ? $row[$parentIdx] : '',
                'parent_phone' => ($parentPhoneIdx !== null && ! empty($row[$parentPhoneIdx] ?? '')) ? $row[$parentPhoneIdx] : '',
                'email' => ($emailIdx !== null && ! empty($row[$emailIdx] ?? '')) ? $row[$emailIdx] : '',
                'phone' => ($phoneIdx !== null && ! empty($row[$phoneIdx] ?? '')) ? $row[$phoneIdx] : '',
                'subjects' => ($subjectsIdx !== null && ! empty($row[$subjectsIdx] ?? '')) ? $row[$subjectsIdx] : '',
                'classes' => ($classesIdx !== null && ! empty($row[$classesIdx] ?? '')) ? $row[$classesIdx] : '',
            ]);
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
        foreach ($aliases as $col) {
            $idx = array_search($col, $lowerHeaders, true);
            if ($idx !== false) {
                return (int) $idx;
            }
        }

        return null;
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array{name: string, class: string, stream: string, parent: string, parent_phone: string, email: string, phone: string, subjects: string, classes: string}
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
        ], $overrides);
    }
}
