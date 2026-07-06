<?php

namespace App\States\Approval;

class Pending extends ApprovalState
{
    public function label(): string
    {
        return 'Pending';
    }

    public function color(): string
    {
        return '#D97706'; // Amber
    }
}
