<?php

namespace App\Events;

use App\Models\School;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A school finished onboarding for the first time.
 *
 * Dispatched once by FinishSchoolOnboarding. A second finish does not
 * dispatch it again. Nothing listens yet: no job, mail, or WhatsApp send.
 */
class SchoolOnboardingFinished
{
    use Dispatchable, SerializesModels;

    public function __construct(public School $school) {}
}
