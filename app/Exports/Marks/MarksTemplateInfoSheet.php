<?php

namespace App\Exports\Marks;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** The Exam info sheet of the marks-import template: label/value rows, including the template key. */
class MarksTemplateInfoSheet implements FromArray, WithStyles, WithTitle
{
    /** @param  list<array{0:string,1:string}>  $rows */
    public function __construct(
        private array $rows,
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Exam info';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            'A' => ['font' => ['bold' => true]],
        ];
    }
}
