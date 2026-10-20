<?php

namespace App\Contracts\Results;

/**
 * Report-card views a school can pick. A package registers another view
 * by rebinding this, instead of editing the report pipeline.
 */
interface ReportTemplates
{
    /**
     * @return array<string, array{label: string, view: string}>
     */
    public function all(): array;

    /**
     * Requested key, then the school's saved key, then the default view.
     *
     * @return array{label: string, view: string}
     */
    public function resolve(?string $requestedKey, ?string $schoolKey): array;
}
