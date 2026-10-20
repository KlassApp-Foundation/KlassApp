<?php

namespace App\Services\Results;

use App\Contracts\Results\ReportTemplates;
use App\Services\StudentReportCardService;

/**
 * The two views already shipped with the app. StudentReportCardService::TEMPLATES
 * stays the list admin validation reads. A package rebinds ReportTemplates
 * to add a view without editing generatePdf().
 */
final class DefaultReportTemplates implements ReportTemplates
{
    public function all(): array
    {
        return StudentReportCardService::TEMPLATES;
    }

    public function resolve(?string $requestedKey, ?string $schoolKey): array
    {
        $templates = $this->all();
        $key = $requestedKey ?? $schoolKey ?? '';

        return $templates[$key] ?? $templates['formal'];
    }
}
