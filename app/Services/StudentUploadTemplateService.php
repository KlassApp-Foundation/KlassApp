<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\StandardLink;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Per-school student bulk-upload XLSX — sample Class/Stream from current sections.
 */
class StudentUploadTemplateService
{
    public function __construct(private ClassStructureService $structure)
    {
    }

    /**
     * @return list<array{class: string, stream: string}>
     */
    public function sampleClassStreamRows(School $school, ?AcademicYear $year = null): array
    {
        $year = $year ?? SiteHelper::getAcademicYear((int) $school->id);
        if (! $year) {
            return [];
        }

        $sectionIds = StandardLink::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->where(function ($q) {
                $q->where('status', 1)->orWhere('status', '1');
            })
            ->pluck('section_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        if ($sectionIds === []) {
            return [];
        }

        $sections = Section::query()
            ->where('school_id', $school->id)
            ->whereIn('id', $sectionIds)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $rows = [];
        foreach ($sections as $section) {
            $base = $this->structure->resolveBaseSection($section);
            $baseName = trim((string) $base->name);
            $sectionName = trim((string) $section->name);

            if ($baseName === '' || $sectionName === '') {
                continue;
            }

            if ((int) $base->id === (int) $section->id) {
                $rows[] = ['class' => $baseName, 'stream' => ''];

                continue;
            }

            $prefix = $baseName.' ';
            $streamLabel = str_starts_with($sectionName, $prefix)
                ? trim(substr($sectionName, strlen($prefix)))
                : '';

            $rows[] = [
                'class' => $baseName,
                'stream' => $streamLabel,
            ];
        }

        // Prefer stream sample rows first when streams exist; keep blank-stream
        // base rows so base enrollment stays documented.
        $withStream = [];
        $withoutStream = [];
        foreach ($rows as $row) {
            if (($row['stream'] ?? '') !== '') {
                $withStream[] = $row;
            } else {
                $withoutStream[] = $row;
            }
        }

        return array_values(array_merge($withStream, $withoutStream));
    }

    public function spreadsheet(School $school, ?AcademicYear $year = null): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Students');

        $headers = ['Name', 'Class', 'Stream', 'Parent Name', 'Parent Phone'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValue([$col + 1, 1], $header);
        }

        $samples = $this->sampleClassStreamRows($school, $year);
        if ($samples === []) {
            $samples = [
                ['class' => 'Primary One', 'stream' => ''],
            ];
        }

        $names = [
            'Amina Nakato',
            'Brian Okello',
            'Catherine Nampiima',
            'Daniel Wasswa',
            'Esther Kyomuhangi',
            'Francis Ssekitoleko',
            'Grace Mbabazi',
            'Henry Mugisha',
        ];
        $parents = [
            ['Sarah Nakato', '+256771234567'],
            ['Peter Okello', '+256772345678'],
            ['Grace Nampiima', '+256773456789'],
            ['Robert Wasswa', '+256774567890'],
            ['Joy Kyomuhangi', '+256775678901'],
            ['David Ssekitoleko', '+256776789012'],
            ['Martha Mbabazi', '+256777890123'],
            ['Paul Mugisha', '+256778901234'],
        ];

        $rowCount = min(count($samples), count($names));
        for ($i = 0; $i < $rowCount; $i++) {
            $excelRow = $i + 2;
            $sheet->setCellValue([1, $excelRow], $names[$i]);
            $sheet->setCellValue([2, $excelRow], $samples[$i]['class']);
            $sheet->setCellValue([3, $excelRow], $samples[$i]['stream']);
            $sheet->setCellValue([4, $excelRow], $parents[$i][0]);
            $sheet->setCellValue([5, $excelRow], $parents[$i][1]);
        }

        return $spreadsheet;
    }

    public function downloadResponse(School $school, ?AcademicYear $year = null): StreamedResponse
    {
        $spreadsheet = $this->spreadsheet($school, $year);
        $filename = 'student-upload-template-'.($school->slug ?: $school->id).'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
