<?php

namespace App\Services\Google\Sheets;

use App\Models\Academics\Exam;
use App\Models\User;

/**
 * Read-only class mark sheet for Google Sheets export.
 *
 * TODO: switch the implementation to App\Services\Results\ResultsService
 * once PR #1118 is merged. That service is not on main (docs-only proposal
 * in docs/product/marks-flow.md). This interface keeps the Sheets connector
 * from growing a second grading engine.
 */
interface ClassMarkSheetReader
{
    /**
     * @return array{title: string, headings: list<string>, rows: list<list<mixed>>, info: list<array{0: string, 1: string}>}
     */
    public function forExam(Exam $exam, User $actor): array;
}
