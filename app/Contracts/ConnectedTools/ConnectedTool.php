<?php

namespace App\Contracts\ConnectedTools;

interface ConnectedTool
{
    public function key(): string;

    /**
     * Status for one school. Never read another school's rows.
     *
     * @return array{
     *     status: string,
     *     attention: ?string,
     *     last_synced_at: ?\DateTimeInterface,
     *     action: ?string,
     *     action_url: ?string,
     *     action_method: string
     * }
     */
    public function stateForSchool(int $schoolId): array;
}
