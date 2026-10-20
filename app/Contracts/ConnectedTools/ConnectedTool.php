<?php

namespace App\Contracts\ConnectedTools;

use App\Models\User;

interface ConnectedTool
{
    public function key(): string;

    /**
     * Status for the signed-in user. Google cards use that user's own row.
     * WhatsApp and Slack stay school products and read that user's school only.
     *
     * @return array{
     *     status: string,
     *     attention: ?string,
     *     note: ?string,
     *     account_email: ?string,
     *     last_synced_at: ?\DateTimeInterface,
     *     action: ?string,
     *     action_url: ?string,
     *     action_method: string
     * }
     */
    public function stateFor(User $user): array;
}
