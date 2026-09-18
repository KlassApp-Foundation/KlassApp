<?php

namespace App\Contracts\Toshi;

use Illuminate\Support\Collection;

/**
 * Contract for Google Drive MCP connector (Developer Preview).
 *
 * All Skill / RouteTo* / UI code must type-hint this contract only.
 * Nothing outside the preview implementation may reference the concrete
 * Google client, its endpoint, or its tool names.
 *
 * TODO: re-verify against Google Workspace MCP docs before 2027-03-18.
 * @see docs/plans/toshi-mcp-connector-registry-and-shortlist-reeval-plan.md D.3
 */
interface GoogleDriveConnectorContract
{
    public function listFiles(array $options = []): Collection;

    public function search(string $query, array $options = []): Collection;

    public function readDocument(string $fileId): array;
}
