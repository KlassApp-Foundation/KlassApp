<?php

namespace App\States\Approval;

class Rejected extends ApprovalState
{
    public function label(): string
    {
        return 'Rejected';
    }

    public function color(): string
    {
        return '#EF4444'; // Red
    }
}
