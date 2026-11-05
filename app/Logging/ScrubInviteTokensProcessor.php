<?php

namespace App\Logging;

use App\Support\InviteTokenScrubber;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class ScrubInviteTokensProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: InviteTokenScrubber::scrub($record->message),
            context: InviteTokenScrubber::scrubMixed($record->context),
            extra: InviteTokenScrubber::scrubMixed($record->extra),
        );
    }
}
