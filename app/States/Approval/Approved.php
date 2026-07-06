<?php

namespace App\States\Approval;

class Approved extends ApprovalState
{
    public function label(): string
    {
        return 'Approved';
    }

    public function color(): string
    {
        return '#22C55E'; // Green
    }
}
