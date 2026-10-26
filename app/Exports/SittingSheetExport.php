<?php

namespace App\Exports;

use App\Services\Results\ResultCell;
use App\Services\Results\ResultSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * One sitting, from the same ResultSheet the page shows.
 * Totals, averages, positions and the "of" count are already on each row.
 */
class SittingSheetExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private readonly ResultSheet $sheet,
        private readonly bool $byStream,
    ) {
    }

    public function title(): string
    {
        return 'Combined sheet';
    }

    public function headings(): array
    {
        $subjects = array_map(
            fn (array $subject) => $subject['name'].' ('.$subject['submission'].')',
            $this->sheet->subjects,
        );

        return array_merge(
            ['Student', 'Stream'],
            $subjects,
            ['Total', 'Average', 'Aggregate', 'Position'],
        );
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->sheet->students as $student) {
            $row = [$student->name, $student->stream];
            foreach ($this->sheet->subjects as $subject) {
                $cell = $student->cell((int) $subject['id']);
                $row[] = ($cell !== null && $cell->status === ResultCell::MARKED && $cell->mark !== null)
                    ? $cell->mark
                    : '';
            }
            $row[] = $student->displayTotal;
            $row[] = $student->displayAverage;
            $row[] = $student->aggregate;
            $row[] = $student->placeLabel($this->byStream);
            $rows[] = $row;
        }

        return $rows;
    }
}
