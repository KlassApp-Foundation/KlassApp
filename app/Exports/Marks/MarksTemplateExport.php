<?php

namespace App\Exports\Marks;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * The marks-import template: two sheets, one file.
 *
 * Sheet "Marks" is the fill-in grid (KLS number, student, mark column); sheet
 * "Exam info" names the school, class, subject, exam, term, maximum marks and
 * carries the template key the importer checks before showing a preview.
 * Teacher and admin template routes both emit this exact shape.
 */
class MarksTemplateExport implements WithMultipleSheets
{
    /**
     * @param  list<string>  $headings
     * @param  list<array<int,mixed>>  $rows
     * @param  list<array{0:string,1:string}>  $infoRows
     */
    public function __construct(
        private array $headings,
        private array $rows,
        private array $infoRows,
    ) {
    }

    public function sheets(): array
    {
        return [
            new MarksTemplateMarksSheet($this->headings, $this->rows),
            new MarksTemplateInfoSheet($this->infoRows),
        ];
    }
}
