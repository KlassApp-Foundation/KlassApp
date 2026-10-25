<?php

namespace App\Onboarding\Steps;

use InvalidArgumentException;

/**
 * A step rejected an answer. The message is the issue cause, so existing
 * callers that read the exception text see the same wording as before.
 */
class StepIssueException extends InvalidArgumentException
{
    public function __construct(public readonly StepIssue $issue)
    {
        parent::__construct($issue->cause);
    }
}
