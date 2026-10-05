<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * No-op import object: lets `Excel::toArray()` read the raw rows of a marks
 * spreadsheet (xlsx, xls or csv) without any heading-row interpretation.
 * Parsing and validation live in App\Services\MarksImport\MarksImportService.
 */
class MarksSheetReader implements ToCollection
{
    public function collection(Collection $collection): void
    {
    }
}
