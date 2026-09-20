<?php

namespace App\Events;

use App\Models\Academics\Exam;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Marks were entered or updated by a teacher.
 *
 * When a teacher corrects marks on an ALREADY-SUBMITTED exam the school admin
 * must know — especially when the admin had reopened a locked submission,
 * because the correction then happens after their own approval decision.
 */
class MarksUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Exam $exam,
        public User $teacher,
        /** Reason given for correcting already-submitted marks (null for first entry). */
        public ?string $reason = null,
        /** True when the submission had been reopened by an admin before this edit. */
        public bool $afterReopen = false,
        /** How many marks rows this save touched. */
        public int $affectedMarkCount = 0,
    ) {}
}
